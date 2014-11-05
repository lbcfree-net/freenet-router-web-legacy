<?
function monitoring_get_ips_info_all($ADAPTER,$IPS) {
    global $CLIENTS;
    $array = array();
    if (is_array($IPS)) {
	foreach ($IPS as $I => $line) {
	    $pom = true;
	    foreach ($CLIENTS as $CLIENT) {
		foreach ($CLIENT["ips"] as $CLIENT_IPS) {
		    if ($line["ip"] == $CLIENT_IPS["ip"]) {
			$pom = false;
			break;
		    }
		}
	    }

            if (!$pom) continue;

            $array[$I]["mac"] = "";
            $array[$I]["enabled"] = true;
            $array[$I]["active"] = $line["active"];
            $array[$I]["signal"] = "";
            /* pro ip adresy, které nemají MAC adresu, nikdy QoS na MAC nemůže být aktivní */
            $array[$I]["qos"] = false;
            $array[$I]["ips"][0] = $line;
            $array[$I]["ips"][0]["qos"] = monitoring_get_is_in_qos($ADAPTER,$line["ip"]);
            $array[$I]["ips"][0]["macguard"] = monitoring_get_is_in_macguard_conf("0",$line["ip"]);
        }
    }
    return $array;
}
function monitoring_get_macs_info_all($ADAPTER, $MACS) {
    // pro tyto řazení musíme řadit ještě vnitřní pole s ip adresami pro každou mac
    if (in_array($_GET['sort_by'],array("name","ip","upload","download","upload_rate","download_rate","name_desc","ip_desc","upload_desc","download_desc","upload_rate_desc","download_rate_desc","qos","qos_desc"))) $pom = true;
    $array = array();
    if (is_array($MACS)) {
	foreach ($MACS as $I => $line) {
	    $array[$I]["mac"] = $line;
	    $array[$I]["enabled"] = monitoring_get_mac_is_enabled($ADAPTER,$line);
	    $array[$I]["active"] = monitoring_get_mac_is_active($ADAPTER,$line);
	    $array[$I]["qos"] = monitoring_get_is_in_qos($ADAPTER,$line);
	    $array[$I]["signal"] = monitoring_get_mac_signal($ADAPTER,$line);
	    $array[$I]["ips"] = monitoring_get_mac_ips($ADAPTER,$line);
	    // nebudeme zbytečně řadit tam kde máme jen jednu ip
	    if (($pom) && is_array($array[$I]["ips"][1])) usort($array[$I]["ips"],"sort_by_".$_GET['sort_by']);
        }
    }
    return $array;
}
function monitoring_get_mac_ips($ADAPTER,$MAC) {
    $array = array();
    // co je z arpu, to je aktivní
    // jednodušší způsob jsem zavrhl, nedá se dobře řadit: $array[$IP]["active"] = true;
    $I = 0;
    foreach (monitoring_get_mac_ips_from_arp($ADAPTER,$MAC) as $IP) {
	$array[$I]["ip"] = $IP;
	$array[$I]["active"] = true;
	$I++;
    }
    // co je z macguarda, to je povolené
    foreach (monitoring_get_mac_ips_from_macguard($ADAPTER,$MAC) as $IP => $NAME) {
	$pom2 = false;
	foreach ($array as $J => $VALUES) {
	    if ($VALUES["ip"] == $IP) {
		$array[$J]["name"] = $NAME;
		$array[$J]["enabled"] = true;
		$pom2 = true;
	    }
	}
	if (!$pom2) {
	    $array[$I]["ip"] = $IP;
	    $array[$I]["name"] = $NAME;
	    $array[$I]["enabled"] = true;
	    $I++;
	}
    }
    // pozdeji muzeme data cist jeste z nf_conntrack
    // získáme přenosy pro všechny ip
    foreach ($array as $K => $VALUES) {
	$pom = monitoring_get_ip_stats($VALUES["ip"]);
	$array[$K]["upload"] = $pom[0];
	$array[$K]["download"] = $pom[1];
	$array[$K]["upload_rate"] = $pom[2];
	$array[$K]["download_rate"] = $pom[3];
	$array[$K]["qos"] = monitoring_get_is_in_qos($ADAPTER,$VALUES["ip"]);
	$array[$K]["macguard"] = monitoring_get_is_in_macguard_conf($MAC,$VALUES["ip"]);
    }
    return $array;
}

/* Matchujeme MAC adresu, nebo IP adresu v souboru /etc/firewall/qos.conf */
function monitoring_get_is_in_qos($ADAPTER,$MAC) {
    global $QOS_DATA;
    global $ADAPTER_ALL;
    
    if (!is_array($QOS_DATA)) return false;

    foreach ($QOS_DATA as $line) {
        /* přeskočíme zamřížkované řádky */
        if ($line[0] == "#") continue;
        /* pokud řádek neobsahuje mac adresu, nebo ip adresu, tak ho také přeskočíme, urychlí match */
        if (!eregi($MAC,$line)) continue;
        $line = preg_split("/[\ \"=\t\n]+/",$line);
        /* pokud první znak z prvního matche je opět mřížka, tak řádek přeskočíme, musíme použít ' */
        if (!preg_match('/CLASS_([\d]+)\[\$\{#CLASS_[\d]+\[\*\]\}\]/',$line[0],$class)) continue;

        /* pokud mac adresa neodpovídá hledané mac adrese, tak přeskočíme řádek */
        $match = false;
        for ($i = 3; $i < sizeof($line); $i++) {
            if (strcasecmp($line[$i],$MAC) != 0) continue;
            $match = true;
            break;
        }

        if (!$match) continue;

        /* match na základě adapteru */
        if (strcasecmp($line[1],$ADAPTER) == 0) return $class[1];
        if (strcasecmp($line[1],"all") == 0) return $class[1];
        if ($ADAPTER == $ADAPTER_ALL) return $class[1];
    }

    return false;
}

function monitoring_get_mac_is_active($ADAPTER,$MAC) {
    global $ARP_DATA;
    global $ADAPTER_ALL;
    $array = array();
    if (is_array($ARP_DATA)) {
	foreach ($ARP_DATA as $line) {
	    if (eregi($MAC,$line)) {
		$line = preg_split("/[\ \t\n]+/", $line);
		if (((strcasecmp($line[4],$ADAPTER) == 0) || ($ADAPTER == $ADAPTER_ALL)) && (strcasecmp($line[2],$MAC) == 0)) {
	    	    return true;
    		}
	    }
        }
    }
    return false;
}
function monitoring_get_mac_signal($ADAPTER,$MAC) {
    $signal = monitoring_get_madwifi_signal($ADAPTER, $MAC);
    $signal.= monitoring_get_hostap_signal($ADAPTER, $MAC);
    $signal.= monitoring_get_mikrotik_signal($ADAPTER, $MAC);
    return $signal;
}
function monitoring_get_mac_is_enabled($ADAPTER,$MAC) {
    global $MACGUARD_DATA;
    global $INTERFACES;
    global $dummy_ip;
    global $ADAPTER_ALL;
    $array = array();
    if (is_array($MACGUARD_DATA)) {
	foreach ($MACGUARD_DATA as $line) {
	    unset ($pom);
	    if (eregi($MAC,$line)) {
		$line = preg_split("/[\ ;\t\n]+/", $line);
		foreach (get_interfaces_ip($INTERFACES,$ADAPTER) as $ADAPTER_IP) {
		    if (!$pom) {
			$pom = is_ip_from_subnet($line[0],$ADAPTER_IP[0]."/".netmask2CIDR($ADAPTER_IP[1]));
		    }
		}
		if (($pom) || ($ADAPTER == $ADAPTER_ALL)) {
		    // polotransparentní bridge
		    $pom2 = explode(".",$line[4]);
		    if (($dummy_ip == $line[4]) || ($pom2[2] == 0)) {
			if ($MAC == strtoupper($line[1])) {
			    return true;
			}
		    } else {
			if ($MAC == strtoupper($line[5])) {
			    return true;
			}
		    }
		}
    	    }
        }
    }
    return false;
}
function monitoring_get_ip_stats($IP) {
    global $ACCOUNTS_DATA;
    $array = array();
    if (is_array($ACCOUNTS_DATA)) {
	foreach ($ACCOUNTS_DATA as $line) {
	    if (eregi($IP.":",$line)) {
		$line = preg_split("/[\ :\t\n]+/", $line);
	        if ($IP == $line[0]) {
		    $array[0] = $line[1];
		    $array[1] = $line[2];
		    $array[2] = $line[3];
		    $array[3] = $line[4];
		    $pom = true;
		}
	    }
	}
    }
    if (!$pom) {
	$array[0] = "0";
	$array[1] = "0";
	$array[2] = "0";
	$array[3] = "0";
    }
    return $array;
}
function monitoring_get_ips_from_stats() {
    global $ACCOUNTS_DATA;
    $array = array();
    $I = "0";
    if (is_array($ACCOUNTS_DATA)) {
	foreach ($ACCOUNTS_DATA as $line) {
	    if (!eregi("#",$line)) {
		$line = preg_split("/[\ :\t\n]+/", $line);
	        if ($line[0] != "") {
		    $array[$I]["ip"] = $line[0];
		    $array[$I]["name"] = "";
		    $array[$I]["enabled"] = true;
		    $array[$I]["active"] = (($line[3] > 0) || ($line[4] > 0));
		    $array[$I]["upload"] = $line[1];
		    $array[$I]["download"] = $line[2];
		    $array[$I]["upload_rate"] = $line[3];
		    $array[$I]["download_rate"] = $line[4];
		    $I++;
		}
	    }
	}
    }
    return $array;
}
function monitoring_get_mac_ips_from_arp($ADAPTER,$MAC) {
    global $ARP_DATA;
    global $ADAPTER_ALL;
    $array = array();
    if (is_array($ARP_DATA)) {
	foreach ($ARP_DATA as $line) {
	    if (eregi($MAC,$line)) {
		$line = preg_split("/[\ \t\n]+/", $line);
		if (((strcasecmp($line[4],$ADAPTER) == 0) || ($ADAPTER == $ADAPTER_ALL)) && (strcasecmp($line[2],$MAC) == 0)) {
	    	    $array[] = $line[0];
    		}
	    }
        }
    }
    return array_unique($array);
}
function monitoring_get_mac_ips_from_macguard($ADAPTER,$MAC) {
    global $MACGUARD_DATA;
    global $INTERFACES;
    global $dummy_ip;
    global $ADAPTER_ALL;
    $array = array();
    if (is_array($MACGUARD_DATA)) {
	foreach ($MACGUARD_DATA as $line) {
	    unset ($pom);
	    if (eregi($MAC,$line)) {
		$line = preg_split("/[\ ;\t\n]+/", $line);
		foreach (get_interfaces_ip($INTERFACES,$ADAPTER) as $ADAPTER_IP) {
		    if (!$pom) {
		        $pom = is_ip_from_subnet($line[0],$ADAPTER_IP[0]."/".netmask2CIDR($ADAPTER_IP[1]));
		    }
		}
		if (($pom) || ($ADAPTER == $ADAPTER_ALL)) {
		    // polotransparentní bridge
		    $pom2 = explode(".",$line[4]);
		    if (($dummy_ip == $line[4]) || ($pom2[2] == 0)) {
		        if ($MAC == strtoupper($line[1])) {
		    	    $array[$line[0]] = $line[2];
			}
		    } else {
		        if ($MAC == strtoupper($line[5])) {
		    	    $array[$line[0]] = $line[2];
		        }
		    }
		}
    	    }
	}
    }
    return array_unique($array);
}
function monitoring_get_macs_from_arp($ADAPTER) {
    global $ARP_DATA;
    global $ADAPTER_ALL;
    $array = array();
    if (is_array($ARP_DATA)) {
	foreach ($ARP_DATA as $line) {
	    $line = preg_split("/[\ \t\n]+/", $line);
	    if (((strcasecmp($line[4],$ADAPTER) == 0) || ($ADAPTER == $ADAPTER_ALL)) && eregi(":", $line[2])) {
	        $array[] = strtoupper($line[2]);
    	    }
        }
    }
    return array_unique($array);
}
function monitoring_get_macs_from_macguard($ADAPTER) {
    global $MACGUARD_DATA;
    global $INTERFACES;
    global $dummy_ip;
    global $ADAPTER_ALL;
    $array = array();
    if (is_array($MACGUARD_DATA)) {
	foreach ($MACGUARD_DATA as $line) {
	    unset ($pom);
	    $line = preg_split("/[\ ;\t\n]+/", $line);
	    if (eregi(":", $line[1])) {
		foreach (get_interfaces_ip($INTERFACES,$ADAPTER) as $ADAPTER_IP) {
		    if (!$pom) {
			$pom = is_ip_from_subnet($line[0],$ADAPTER_IP[0]."/".netmask2CIDR($ADAPTER_IP[1]));                        
		    }
		}
		if (($pom) || ($ADAPTER == $ADAPTER_ALL)) {
		    // polotransparentní bridge
		    $pom2 = explode(".",$line[4]);
		    if (($dummy_ip == $line[4]) || ($pom2[2] == 0)) {
	    		$array[] = strtoupper($line[1]);
		    } else {
			$array[] = strtoupper($line[5]);
		    }
		}
    	    }
        }
    }
    return array_unique($array);
}
function monitoring_get_macs_from_hostap($ADAPTER) {
    global $ADAPTER_ALL;
    $array = array();
    $ADAPTERS = array();
    if ($ADAPTER == $ADAPTER_ALL) {
	if ($handle = @opendir("/proc/net/hostap")) {
	    while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
	    	    $ADAPTERS[] = strtolower($file);
	        }
	    }
	    closedir($handle);
	}
    } else {
	$ADAPTERS[] = $ADAPTER;
    }
    foreach ($ADAPTERS as $ADAPTER) {
	if (file_exists("/proc/net/hostap/".$ADAPTER)) {
	    if ($handle = @opendir("/proc/net/hostap/".$ADAPTER)) {
		while (false !== ($file = readdir($handle))) {
		    if ($file != "." && $file != ".." && eregi(":", $file)) {
	    		$array[] = strtoupper($file);
	    	    }
		}
		closedir($handle);
	    }
	}
    }
    return array_unique($array);
}
function monitoring_get_macs_from_madwifi($ADAPTER) {
    global $MADWIFI_CLIENTS;
    global $ADAPTER_ALL;
    $array = array();
    $ADAPTERS = array();
    if ($ADAPTER == $ADAPTER_ALL) {
	if ($handle = @opendir("/proc/net/madwifi")) {
	    while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
	    	    $ADAPTERS[] = strtolower($file);
	        }
	    }
	    closedir($handle);
	}
    } else {
	$ADAPTERS[] = $ADAPTER;
    }
    foreach ($ADAPTERS as $ADAPTER) {
	if (file_exists("/proc/net/madwifi/".$ADAPTER)) {
	    if (is_array($MADWIFI_CLIENTS)) {
		foreach ($MADWIFI_CLIENTS as $line) {
		    $line = preg_split("/[\ \t\n]+/", $line);
		    if (eregi(":", $line[0])) {
	    		$array[] = strtoupper($line[0]);
    		    }
    		}
    	    }
	}
    }
    return array_unique($array);
}
function monitoring_get_madwifi_signal($ADAPTER, $MAC) {
    global $MADWIFI_CLIENTS;
    global $ADAPTER_ALL;
    $ADAPTERS = array();
    if ($ADAPTER == $ADAPTER_ALL) {
	if ($handle = @opendir("/proc/net/madwifi")) {
	    while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
	    	    $ADAPTERS[] = strtolower($file);
	        }
	    }
	    closedir($handle);
	}
    } else {
	$ADAPTERS[] = $ADAPTER;
    }
    foreach ($ADAPTERS as $ADAPTER) {
	if (file_exists("/proc/net/madwifi/".$ADAPTER)) {
	    if (is_array($MADWIFI_CLIENTS) && (eregi(":",$MAC))) {
		foreach ($MADWIFI_CLIENTS as $line) {
		    $line = preg_split("/[\ \t\n]+/", $line);
		    if (strcasecmp($line[0],$MAC) == 0) {
		        return $line[5]." dB";
		    }
		}
	    }
	}
    }
    return "";
}
function monitoring_get_hostap_signal($ADAPTER, $MAC) {
    global $ADAPTER_ALL;
    $ADAPTERS = array();
    if ($ADAPTER == $ADAPTER_ALL) {
	if ($handle = @opendir("/proc/net/hostap")) {
	    while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
	    	    $ADAPTERS[] = strtolower($file);
	        }
	    }
	    closedir($handle);
	}
    } else {
	$ADAPTERS[] = $ADAPTER;
    }
    foreach ($ADAPTERS as $ADAPTER) {
	if (file_exists("/proc/net/hostap/".$ADAPTER."/".strtolower($MAC))) {
	    foreach (file("/proc/net/hostap/".$ADAPTER."/".strtolower($MAC)) as $line) {
		if (eregi("signal",$line)) {
		    $line = preg_split("/[\ =:\t\n]+/", $line);
		    if (($line[0] == "last_rx") && ($line[5] != "")) {
			return $line[5]." dB";
		    }
		}
	    }
	}
    }
    return "";
}
function monitoring_get_mikrotik_signal($ADAPTER, $MAC) {
    global $MIKROTIK_WIFI_DATA;
    if (is_array($MIKROTIK_WIFI_DATA)) {
        foreach ($MIKROTIK_WIFI_DATA as $line) {
            if (eregi($MAC,$line)) {
                $line = preg_split("/[\ =d\t\n]+/", $line);
                if (($line[3] == $MAC) && ($line[5] != "")) {
                    return $line[5]." dB";
                } else if (($line[4] == $MAC) && ($line[6] != "")) {
                    return $line[6]." dB";
                }
            }
        }
    }
    return "";
}
function monitoring_set_qos($mac,$limit) {
    if ($mac == "") return true;

    $limit = (substr($limit,0,6) == "omezit");

    if(($tmp_file = fopen("/tmp/qos.conf","w")))
    {
        $inserted = false;

        if(($file = fopen("/etc/firewall/qos.conf","r")))
        {            
            while (!feof($file)) {
                $line = fgets($file,1024);
                /* přeskočíme zamřížkované řádky */
                if ($line[0] == "#") {
                    fwrite($tmp_file,$line);
                    continue;
                }

                /* rozdělíme data do pole */
                $line_array = preg_split("/[\ \"=\t\r\n]+/",$line);

                /* občas zůstane na konci pole prázdná buňka, je potřeba jí odstranit */
                if ($line_array[(sizeof($line_array) - 1)] == "") unset ($line_array[(sizeof($line_array) - 1)]);

                /* najdeme danou ip, nebo mac */
                $match = false;
                for ($i = 3; $i < sizeof($line_array); $i++) {
                    if (strcasecmp($line_array[$i],$mac) != 0) continue;
                    $match = true;
                    break;
                }

                if ($match) $inserted = true;

                if (($match) && (!$limit)) {
                    /* musíme odstranit danou ip, nebo mac ze seznamu, pokud není více IP, tak řádek nezachováme */
                    if (sizeof($line_array) > 4) {
                        /* mělo by zůstat správné $i z předchozí funkce */
                        $macs = array();
                        for ($j = 3; $j < sizeof($line_array); $j++) {
                            if ($j == $i) continue;
                            $macs[] = $line_array[$j];
                        }
                        fwrite($tmp_file,'CLASS_1[${#CLASS_1[*]}]="all '.$line_array[2].' '.implode(" ",$macs)."\"\n");
                    }
                } else {
                    fwrite($tmp_file,$line);
                }
            }
            fclose($file);
        }

        if ((!$inserted) && ($limit)) {
            fwrite($tmp_file,'CLASS_1[${#CLASS_1[*]}]="all web '.$mac."\"\n");
        }

        fclose($tmp_file);
    }

    /* je potřeba odemknout fs */
    if (system_get_rootfs_status_ro("")) {
        set_rootfs_rw();
        exec("sudo /bin/cp /tmp/qos.conf /etc/firewall/qos.conf");
        set_rootfs_ro();
    } else {
        exec("sudo /bin/cp /tmp/qos.conf /etc/firewall/qos.conf");
    }

    if ($limit) {
        exec("sudo /firewall qos_guaranted_class_add_user 1 ".$mac." all");
    } else {
        exec("sudo /firewall qos_guaranted_class_del_user ".$mac);
    }

    unlink("/tmp/qos.conf");

    return true;
}
function sort_by_mac($a,$b) {
    return strcmp($a["mac"],$b["mac"]);
}
function sort_by_status($a,$b) {
    // nejprve chceme aktivni, pote aktivni ale zakazane, pak aktivni bez ip a nakonec neaktivni
    if (($a["active"] || ($a["signal"] != "")) == ($b["active"] || ($b["signal"] != ""))) {
	// pokud jsou obě aktivní, jako první bude povolená
	if ($a["active"] == $b["active"]) {
	    return (($a["enabled"] != "") <= ($b["enabled"] != "")) ? 1 : -1;
	// pokud obě mají signál, jako první bude aktivní
	} else if ($a["signal"] == $b["signal"]) {
	    return ($a["active"] <= $b["active"]) ? 1 : -1;
	// pokud a má signál a b je aktivní, pak b nemá signál a a není aktivní
	} else if ($a["signal"] == $b["active"]) {
	    return ($a["active"] <= $b["signal"]) ? 1 : -1;
	// pokud a je aktivní a b má signál, pak b není aktivní a a nemá signál
	} else if ($a["active"] == $b["signal"]) {
	    return ($a["signal"] <= $b["active"]) ? 1 : -1;
	}
    } else {
	return (($a["active"] || ($a["signal"] != "")) <= ($b["active"] || ($b["signal"] != ""))) ? 1 : -1;
    }
}
function sort_by_qos($a,$b) {
    // jako první chceme omezené
    if (is_array($a) || is_array($b)) {
	// velmi složitý případ, řazení nejen podle omezených ip, ale ještě řazení podle omezené mac
	if (($a["ip"] != "") || ($b["ip"] != "")) {
	    return ($a["qos"] <= $b["qos"]) ? 1 : -1;
	} else {
	    foreach ($a["ips"] as $a_ip) {
		if ($a_ip["qos"]) {
		    $a_pom = true;
		    break;
		}
	    }
	    foreach ($b["ips"] as $b_ip) {
		if ($b_ip["qos"]) {
		    $b_pom = true;
		    break;
		}
	    }
	    return (($a_pom || $a["qos"]) <= ($b_pom || $b["qos"])) ? 1 : -1;
	}
    }
}
function sort_by_signal($a,$b) {
    // jako první chceme s nejlepším signálem, nakonec bez signálu
    if ($a["signal"] == "") $a["signal"] = "-99 dB";
    if ($b["signal"] == "") $b["signal"] = "-99 dB";
    return ($a["signal"] >= $b["signal"]) ? 1 : -1;
}
function sort_by_ip($a,$b) {
    if (is_array($a) || is_array($b)) {
	if (($a["ip"] != "") || ($b["ip"] != "")) {
	    return (ip2long($a["ip"]) >= ip2long($b["ip"])) ? 1 : -1;
	} else {
	    return (ip2long($a["ips"][0]["ip"]) >= ip2long($b["ips"][0]["ip"])) ? 1 : -1;
	}
    } else {
	return (ip2long($a) >= ip2long($b)) ? 1 : -1;
    }
}
function sort_by_name($a,$b) {
    if (($a["name"] != "") || ($b["name"] != "")) {
	return strcasecmp($a["name"],$b["name"]);
    } else {
	return strcasecmp($a["ips"][0]["name"],$b["ips"][0]["name"]);
    }
}
function sort_by_upload($a,$b) {
    if (($a["upload"] != "") || ($b["upload"] != "")) {
	return ($a["upload"] <= $b["upload"]) ? 1 : -1;
    } else {
	return ($a["ips"][0]["upload"] <= $b["ips"][0]["upload"]) ? 1 : -1;
    }
}
function sort_by_upload_rate($a,$b) {
    if (($a["upload_rate"] != "") || ($b["upload_rate"] != "")) {
	return ($a["upload_rate"] <= $b["upload_rate"]) ? 1 : -1;
    } else {
	return ($a["ips"][0]["upload_rate"] <= $b["ips"][0]["upload_rate"]) ? 1 : -1;
    }
}
function sort_by_download($a,$b) {
    if (($a["download"] != "") || ($b["download"] != "")) {
	return ($a["download"] <= $b["download"]) ? 1 : -1;
    } else {
	return ($a["ips"][0]["download"] <= $b["ips"][0]["download"]) ? 1 : -1;
    }
}
function sort_by_download_rate($a,$b) {
    if (($a["download_rate"] != "") || ($b["download_rate"] != "")) {
	return ($a["download_rate"] <= $b["download_rate"]) ? 1 : -1;
    } else {
	return ($a["ips"][0]["download_rate"] <= $b["ips"][0]["download_rate"]) ? 1 : -1;
    }
}
function sort_by_mac_desc($a, $b) { return sort_by_mac($b, $a); }
function sort_by_status_desc($a, $b) { return sort_by_status($b, $a); }
function sort_by_qos_desc($a, $b) { return sort_by_qos($b, $a); }
function sort_by_signal_desc($a, $b) {
    // signal je trochu jiný, chceme odfiltrovat nulové hodnoty
    return ($a["signal"] <= $b["signal"]) ? 1 : -1;
}
function sort_by_ip_desc($a, $b) { return sort_by_ip($b, $a); }
function sort_by_name_desc($a, $b) { return sort_by_name($b, $a); }
function sort_by_upload_desc($a, $b) { return sort_by_upload($b, $a); }
function sort_by_download_desc($a, $b) { return sort_by_download($b, $a); }
function sort_by_upload_rate_desc($a, $b) { return sort_by_upload_rate($b, $a); }
function sort_by_download_rate_desc($a, $b) { return sort_by_download_rate($b, $a); }
function change_url($name,$value) {
    $array = array();
    foreach ($_GET as $NAME => $VALUE) {
	if ($NAME == $name) {
	    // budeme chtit dělat i něco jiného
	    if (($VALUE == $value) && ($NAME == "sort_by") && (!ereg("desc",$VALUE))) {
		$array[] = urlencode($NAME)."=".urlencode($value)."_desc";
	    } else {
		$array[] = urlencode($NAME)."=".urlencode($value);
	    }
	    $pom = true;
	} else if ((substr(strtolower($NAME),0,3) != "qos") && (strtolower($NAME) != "macguard_conf")) {
	    $array[] = urlencode($NAME)."=".urlencode($VALUE);
	}
    }
    if (!$pom) $array[] = urlencode($name)."=".urlencode($value);
    return $_SERVER['PHP_SELF']."?".implode("&amp;",$array);
}

function monitoring_get_is_in_macguard_conf($mac,$ip) 
{   
    if(($file = fopen("/etc/firewall/macguard.conf","r")))
    {
        while (!feof($file)) 
        {
            $line = fgets($file,1024);
            /* přeskočíme zamřížkované řádky */
            if ($line[0] == "#") continue;

            /* rozdělíme data do pole */
            $line_array = preg_split("/[\ \"\t\r\n]+/",$line);

            if ((strcasecmp($mac,$line_array[1]) != 0) || (strcasecmp($ip,$line_array[2]) != 0)) continue;

            if (strcasecmp("ALLOW",$line_array[0]) == 0)
            {
                fclose($file);
                return 1;
            }
            if (strcasecmp("DENY",$line_array[0]) == 0)
            {
                fclose($file);
                return 2;
            }
            fclose($file);
            return false;
        }
        fclose($file);
    }

    return false;
}

function monitoring_set_macguard_conf($val) {
    /* ip a mac jsou odděleny podtržítky */
    $val = explode("_",$val);

    $inserted = false;

    /*
     * typ:
     *     0 - zakážeme přístup a přidáme do configu
     *     1 - povolíme přístup a přidáme do configu
     *     2 - zakážeme přístup a odebereme z configu
     *     3 - povolíme přístup a odebereme z configu
     */
    $type = $val[0];
    $mac = $val[1];
    $ip = $val[2];

    if(($tmp_file = fopen("/tmp/macguard.conf","w")))
    {
        if(($file = fopen("/etc/firewall/macguard.conf","r"))) {
            while (!feof($file)) {
                $line = fgets($file,1024);
                /* přeskočíme zamřížkované řádky */
                if ($line[0] == "#") {
                    fwrite($tmp_file,$line);
                    continue;
                }

                /* rozdělíme data do pole */
                $line_array = preg_split("/[\ \"\t\r\n]+/",$line);

                /* porovnáme ip a mac adresu */
                if ((strcasecmp($mac,$line_array[1]) != 0) || (strcasecmp($ip,$line_array[2]) != 0)) {
                    fwrite($tmp_file,$line);
                    continue;
                }

                if ($type == "0") {
                    fwrite($tmp_file,"DENY ".$mac." ".$ip."\n");
                } else if ($type == "1") {
                    fwrite($tmp_file,"ALLOW ".$mac." ".$ip."\n");
                }
                $inserted = true;
            }
            fclose($file);
        }

        if (!$inserted) {
            if ($type == "0") {
                fwrite($tmp_file,"DENY ".$mac." ".$ip."\n");
            } else if ($type == "1") {
                fwrite($tmp_file,"ALLOW ".$mac." ".$ip."\n");
            }
        }

        fclose($tmp_file);
    }

    /* je potřeba odemknout fs */
    if (system_get_rootfs_status_ro("")) {
        set_rootfs_rw();
        exec("sudo /bin/cp /tmp/macguard.conf /etc/firewall/macguard.conf");
        set_rootfs_ro();
    } else {
        exec("sudo /bin/cp /tmp/macguard.conf /etc/firewall/macguard.conf");
    }

    if (($type == "0") || ($type == "2")) {
        exec("sudo /firewall macguard_deny_user \"".$mac."\" \"".$ip."\"");
    } else if (($type == "1") || ($type == "3")) {
        exec("sudo /firewall macguard_allow_user \"".$mac."\" \"".$ip."\"");
    }

    unlink("/tmp/macguard.conf");

    return true;
}

?>
