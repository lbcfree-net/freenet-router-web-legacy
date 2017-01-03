<?php
function save_adapter_settings($DATA,$ADAPTER) {
if (get_adapter_settings_is_adapter($ADAPTER)) {
    if(($soubor = fopen("/tmp/".$ADAPTER,"w")))
    {
        fwrite($soubor, "#!/bin/bash\n");
        fwrite($soubor, "# Zakladni udaje pro nastaveni karty:\n");
        $SETTINGS = array("MODE","ESSID","CHANNEL","KEY","RATE","RATE_LAN","RETRY","W_MODE","TURBO","SENSITIVITY","TXPOWER","DISTANCE","ANTENNA","AP_BRIDGE","WDS","CONNECTION","DUPLEX","TYPE");
        $SETTINGS_UNITS = array("","","","","M","M","","","","dB","dB","km","","","","","","","");
        $SETTINGS_DESCRIPTIONS = array("\t\t# [sta|ap|monitor|adhoc] rezim site, doporuceny je pouze sta a ap, ostatni rezimy mohou zlobit","\t# [standardni ESSID]\tnastaveni nazvu bezdratove site","\t\t# [36-165 pro A,1-13 pro B a G] u DFS je nutne zadat rozsah {100-120}, rezim sta vybira kanal automaticky","\t\t# [s: pro ASCII, bez s: pro HEXA, off pro vypnuti] sifrovani spoje, WPA zatim neni podporovano","\t\t# [6,9,12,18,24,36,48,54 - A a G|1,2,5.5,11 - B|auto] vynucene nastaveni rychlosti spojeni","\t\t# [100M pro 100Mbit, 10M pro 10Mbit, 1000M pro 1Gbit]","\t\t# [0-20]\t\ttato funkce zatim neni v ovladaci madwifi podporovana","\t\t# [0=auto|1=A|2=B|3=G]\trezim v jakem karta pracuje, rezim \"A\" se nastavuje automaticky podle kanalu","\t\t# [0=off|1=on]\t\tturbo neni v CR pro 5GHz povoleno, proto je nutne zmenit countrycode aby chodil","\t# [od -100 do -30dB]\tu ovladace madwifi zatim toto nastaveni nefunguje","\t\t# [0.01-50mW|od 0 do 17dB|auto] velmi problematicke nastaveni, 0=off, nektere nastaveni muze zlobit","\t\t# [1m - 20km]\t\tpokud je spoj provozovan na delsi vzdalenost a v pasmu 5GHz, pak je treba zmenit","\t\t# [0|1|2]\t\tnastavuje tx a rx antenu, \"0\" znamena auto, doporucene nastaveni \"1\" main konektor","\t\t# [0=off|1=on]\t\tzakaze, nebo povoli primou komunikaci klientum na ap","\t\t\t# [0=off|1=on]\t\tpokud je karta v interfaces nastavena na bridge, pak se wds rezim nastavi automaticky","\t# [generic|madwifi]\tpokud je druha strana take madwifi ovladac (atheros), pak spravne nastaveni muze\n\t\t\t#\t\t\tzarucit mnohem lepsi rychlost spoje, stabilitu i odezvu, pokud je vsak druha\n\t\t\t#\t\t\tstrana jine zarizeni, pak muze spatne nastaveni vyrazne zhorsit stabilitu spoje\n\t\t\t#\t\t\tpokud jste si jisti ze druha strana vyuziva ovladac madwifi a je nastavena stejne\n\t\t\t#\t\t\tpak je doporucene nastaveni \"madwifi\" jinak pouzivejte \"generic\"","\t\t# [HD=poloduplexni rezim|FD=plneduplexni rezim]","\t\t# [F=vynutit rychlost a duplex,A=doporucit rychlost a duplex]");
        foreach ($SETTINGS as $I => $SETTING) {
            if ($DATA[$ADAPTER."_".$SETTING] != "") {
                if (($SETTING == "RATE") && ($DATA[$ADAPTER."_ESSID"] != "") || ($SETTING != "RATE")) {
                    $pom = explode(" ",$DATA[$ADAPTER."_".$SETTING]);
                    if ($SETTING == "KEY") {
                        if ($DATA[$ADAPTER."_KEY_TYPE"] == "žádný") {
                            $pom[0] = "off";
                        } else if ($DATA[$ADAPTER."_KEY_TYPE"] == "64bit ASCII") {
                            $pom[0] = "s:".$pom[0];
                        } else if ($DATA[$ADAPTER."_KEY_TYPE"] == "128bit ASCII") {
                            $pom[0] = "s:".$pom[0];
                        }
                    } else if ($SETTING == "RATE") {
                        if ($pom[0] == "auto") {
                            $SETTINGS_UNITS[$I] = "";
                        }
                    }
                    fwrite($soubor, $SETTING."=\"".$pom[0].$SETTINGS_UNITS[$I]."\"".$SETTINGS_DESCRIPTIONS[$I]."\n");
                }
            } else if (($SETTING == "RATE_LAN") && ($DATA[$ADAPTER."_ESSID"] == "")) {
                $pom = explode(" ",$DATA[$ADAPTER."_RATE"]);
                fwrite($soubor, "RATE=\"".ereg_replace("[^0-9]","",$pom[0]).$SETTINGS_UNITS[$I]."\"".$SETTINGS_DESCRIPTIONS[$I]."\n");
            } else if (($SETTING == "DUPLEX") && ($DATA[$ADAPTER."_ESSID"] == "")) {
                $pom = explode(" ",$DATA[$ADAPTER."_RATE"]);
                if (eregi("half",$pom[0])) {
                    $pom = "HD";
                } else {
                    $pom = "FD";
                }
                fwrite($soubor, $SETTING."=\"".$pom."\"".$SETTINGS_DESCRIPTIONS[$I]."\n");
            // žádný wep spolu s essid znamená wep = off
            } else if (($SETTING == "KEY") && ($DATA[$ADAPTER."_ESSID"] != "")) {
                fwrite($soubor, $SETTING."=\"off".$SETTINGS_UNITS[$I]."\"".$SETTINGS_DESCRIPTIONS[$I]."\n");
            }
        }
        fwrite($soubor, "# Dale uz nic nenastavujeme!\n\n");
        fwrite($soubor, 'if [ "$1" == "" ]; then'."\n");
        fwrite($soubor, "\t".'/etc/init.d/set_card $0'."\n");
        fwrite($soubor, "fi\n");
        fclose($soubor);
    }
    exec("chmod 755 /tmp/".$ADAPTER);
    exec("sudo /bin/cp /tmp/".$ADAPTER." /etc/network/".$ADAPTER);
}
}
// funkce na získání nastavení karty ze souboru
function get_adapter_settings_value($ADAPTER_INFO,$SETTING) {
    if (is_array($ADAPTER_INFO)) {
	foreach ($ADAPTER_INFO as $ADAPTER_INFO_DATA) {
	    $ADAPTER_INFO_DATA = preg_split("/[\ \"]+/", $ADAPTER_INFO_DATA);
	    if ($ADAPTER_INFO_DATA[0] == $SETTING."=") {
		return $ADAPTER_INFO_DATA[1];
	    } else if (($ADAPTER_INFO_DATA[0] == "RATE=") && ($SETTING == "RATE_LAN")) {
		$pom = get_adapter_settings_value($ADAPTER_INFO,"DUPLEX");
		if ($pom == "HD") {
		    $pom = "Half";
		} else {
		    $pom = "Full";
		}
		return ereg_replace("[^0-9]","",$ADAPTER_INFO_DATA[1])."baseT/".$pom;
	    }
	}
    }
    switch ($SETTING) {
	case "MODE":
	    return "ap";
	case "ESSID":
	    return "Freenet";
	case "CHANNEL":
	    return "01";
	case "RATE":
	    return "11";
	case "RATE_LAN":
	    return "100baseT/Full";
	case "RETRY":
	    return "8";
	case "W_MODE":
	    return "0";
	case "TURBO":
	    return "0";
	case "SENSITIVITY":
	    return "-98";
	case "TXPOWER":
	    return "16";
	case "DISTANCE":
	    return "1";
	case "ANTENNA":
	    return "0";
	case "CONNECTION":
	    return "madwifi";
	case "AP_BRIDGE":
	    return "1";
	case "WDS":
	    return "0";
	case "TYPE":
	    return "A";
    }
    return "";
}
// funkce pro získání dat z iwpriv
function get_adapter_settings_iwpriv_value($adapter,$string) {
    $value = explode(":",exec("iwpriv ".$adapter." ".$string));
    return $value[1];
}
function get_adapter_settings_is_loopback($ADAPTER) {
    if (preg_match('/lo/i',$ADAPTER)) {
	return true;
    }
    return false;
}
function get_adapter_settings_is_bridge($ADAPTER) {
    if (eregi("br",$ADAPTER)) {
	return true;
    }
    return false;
}
function get_adapter_settings_is_dummy($ADAPTER) {
    if (eregi("dummy",$ADAPTER)) {
	return true;
    }
    return false;
}
function get_adapter_settings_is_vlan($ADAPTER) {
    if (eregi("\.",$ADAPTER)) {
	return true;
    }
    return false;
}
function get_adapter_settings_is_ethernet($ADAPTER) {
    if (eregi("eth",$ADAPTER) && ((!eregi(":",$ADAPTER)) && (!eregi("\.",$ADAPTER)))) {
	return true;
    }
    return false;
}
function get_adapter_settings_is_adapter($ADAPTER) {
    if ((eregi("eth",$ADAPTER) || eregi("wlan",$ADAPTER) || eregi("ath",$ADAPTER)) && ((!eregi(":",$ADAPTER)) && (!eregi("\.",$ADAPTER)))) {
	return true;
    }
    return false;
}
function get_adapter_settings_is_wifi($IWCONFIG,$ADAPTER) {
    if (is_array($IWCONFIG)) {
	foreach ($IWCONFIG as $LINE) {
	    $LINE = preg_split("/[\ ]+/", $LINE);
	    if ($LINE[0] == $ADAPTER) {
		return true;
	    }
	}
    }
    if ((get_adapter_settings_is_adapter($ADAPTER)) && (eregi("ath",$ADAPTER) || eregi("wlan",$ADAPTER))) {
	return true;
    }
    return false;
}
function get_adapter_settings_is_madwifi($ADAPTER) {
    //if ((get_adapter_settings_is_wifi($IWCONFIG,$ADAPTER)) && eregi("ath",$ADAPTER)) {
    if ((get_adapter_settings_is_adapter($ADAPTER)) && eregi("ath",$ADAPTER)) {
	return true;
    }
    return false;
}
function get_adapter_settings_iwconfig_value($IWCONFIG,$ADAPTER,$VALUE) {
    if (get_adapter_settings_is_wifi($IWCONFIG,$ADAPTER)) {
	$pom = false;
	foreach ($IWCONFIG as $LINE) {
	    $LINE = preg_split("/[\ :\"=]+/", $LINE);
	    if ($LINE[0] == $ADAPTER) {
		$pom = true;
	    } else if ($LINE[0] != "") {
		$pom = false;
	    } 
	    if ($pom) {
		foreach ($LINE as $I => $LINE_PART) {
		    if (strcasecmp($VALUE,$LINE_PART) == 0) {
			// bssid je složeno z :, proto musíme vzít všechny!
			if ($VALUE == "point") {
			    return $LINE[($I+1)].":".$LINE[($I+2)].":".$LINE[($I+3)].":".$LINE[($I+4)].":".$LINE[($I+5)].":".$LINE[($I+6)];
			}
			return $LINE[($I+1)];
		    }
		}
	    }
	}
    }
    return "";
}
function get_adapter_settings_iwconfig_essid($IWCONFIG,$ADAPTER) {
    $pom = get_adapter_settings_iwconfig_value($IWCONFIG,$ADAPTER,"essid");
    if ($pom != "") {
	return $pom;
    }
    return "neznámé";
}
function get_adapter_settings_iwconfig_mode($IWCONFIG,$ADAPTER) {
    $pom = get_adapter_settings_iwconfig_value($IWCONFIG,$ADAPTER,"mode");
    if ($pom != "") {
	if (strcasecmp($pom,"master") == 0) {
	    return "ap";
	} else if (strcasecmp($pom,"managed") == 0) {
	    return "klient";
	}
	return $pom;
    }
    return "neznámý";
}
function get_adapter_settings_iwconfig_frequency($IWCONFIG,$ADAPTER) {
    $pom = get_adapter_settings_iwconfig_value($IWCONFIG,$ADAPTER,"frequency");
    if ($pom != "") {
	return ($pom * 1000)." MHz";
    }
    return "neznámá";
}
function get_adapter_settings_iwconfig_channel($IWCONFIG,$ADAPTER) {
    $pom = get_adapter_settings_iwconfig_value($IWCONFIG,$ADAPTER,"frequency");
    if ($pom != "") {
	$pom = ($pom * 1000);
	if (($pom > 2400) && ($pom < 2473)) {
	    return round((($pom - 2407) / 5),0);
	} else if (($pom > 2472) && ($pom < 2500)) {
	    return 14;
	} else if (($pom > 5100) && ($pom < 6000)) {
	    return round((($pom - 5000) / 5),0);
	}
    }
    return "neznámý";
}
function get_adapter_settings_iwconfig_rate($IWCONFIG,$ADAPTER) {
    $pom = get_adapter_settings_iwconfig_value($IWCONFIG,$ADAPTER,"rate");
    if ($pom != "") {
	return $pom." Mb/s";
    }
    return "neznámá";
}
function get_adapter_settings_iwconfig_bssid($IWCONFIG,$ADAPTER) {
    $pom = get_adapter_settings_iwconfig_value($IWCONFIG,$ADAPTER,"point");
    if ($pom != "") {
	return $pom;
    }
    return "neznámé";
}
function get_adapter_settings_iwconfig_txpower($IWCONFIG,$ADAPTER) {
    $pom = get_adapter_settings_iwconfig_value($IWCONFIG,$ADAPTER,"tx-power");
    if ($pom != "") {
	// speciality hostapu a madwifi
	if (file_exists("/proc/net/hostap/$ADAPTER/")) {
            if ($pom > 18) {
                if ($pom > 127) {
                    $pom = round((((255 - $pom) / 10.6) + 5),0);
                } else {
            	    $pom = round((((127 - $pom) / 6.1) - 16),0);
                }
            }
	} else if (file_exists("/proc/net/madwifi/$ADAPTER")) {
	    // zatím asi není třeba nic
	}
	return $pom." dB";
    }
    return "neznámý";
}
function get_adapter_settings_iwconfig_signal($IWCONFIG,$ADAPTER) {
    $pom = get_adapter_settings_iwconfig_value($IWCONFIG,$ADAPTER,"level");
    if ($pom != "") {
	return $pom." dB";
    }
    return "neznámý";
}
function get_adapter_settings_iwconfig_wep($IWCONFIG,$ADAPTER) {
    $pom = get_adapter_settings_iwconfig_value($IWCONFIG,$ADAPTER,"key");
    if ($pom != "") {
	return $pom;
    } else {
	return "off";
    }
    return "neznámý";
}

function get_adapter_settings_rate($IWCONFIG,$ADAPTER) {
    if (get_adapter_settings_is_wifi($IWCONFIG,$ADAPTER)) {
	$pom = get_adapter_settings_iwconfig_value($IWCONFIG,$ADAPTER,"rate");
    } else if (get_adapter_settings_is_ethernet($ADAPTER)) {
	$pom = get_adapter_settings_ethtool_rate($ADAPTER);
    }
    if ($pom != "") {
	return $pom." Mb/s";
    }
    return "neznámá";
}
function get_adapter_settings_ethtool_rate($ADAPTER) {
    if (get_adapter_settings_is_ethernet($ADAPTER)) {
	exec("sudo /sbin/ethtool ".$ADAPTER,$ETHTOOL);
	foreach ($ETHTOOL as $LINE) {
	    if (eregi("speed",$LINE)) {
		$LINE = preg_split("/[\ M]+/", $LINE);
		return $LINE[1];
	    }
	}
    }
    return "";
}
function get_adapter_settings_ethtool_link($ADAPTER) {
    exec("sudo /sbin/ethtool ".$ADAPTER,$ETHTOOL);
    foreach ($ETHTOOL as $LINE) {
    	if (eregi("link detected: yes",$LINE)) {
	    return "aktivní";
	} else if (eregi("link detected: no",$LINE)) {
    	    return "není";
        }
    }
    return "neznámá";
}
function get_adapter_settings_ethtool_duplex($ADAPTER) {
    if (get_adapter_settings_is_ethernet($ADAPTER)) {
	exec("sudo /sbin/ethtool ".$ADAPTER,$ETHTOOL);
	foreach ($ETHTOOL as $LINE) {
	    if (eregi("duplex: full",$LINE)) {
		return "plný";
	    } else if (eregi("duplex: half",$LINE)) {
		return "poloviční";
	    }
	}
    }
    return "neznámý";
}
function get_adapter_settings_ethtool_autoneg($ADAPTER) {
    if (get_adapter_settings_is_ethernet($ADAPTER)) {
	exec("sudo /sbin/ethtool ".$ADAPTER,$ETHTOOL);
	foreach ($ETHTOOL as $LINE) {
	    if (eregi("auto-negotiation: on",$LINE)) {
		return "ano";
	    } else if (eregi("auto-negotiation: off",$LINE)) {
		return "ne";
	    }
	}
    }
    return "neznámý";
}
function get_adapter_settings_model($LSPCI,$ADAPTER) {
    if ((get_adapter_settings_is_adapter($ADAPTER))) {
	if (file_exists("/sys/class/net/".$ADAPTER."/device")) {
	    $pom = basename(readlink("/sys/class/net/".$ADAPTER."/device"));
	    if ($pom != "") {
		foreach ($LSPCI as $LINE) {
                    /* musíme převést formát 0000:00:13.0 na formát, který nabízí lspci */
		    if ((strpos($LINE,$pom) === 0) || (strpos("0000:".$LINE,$pom) === 0)) {
			$pom = explode(': ', $LINE);
			return $pom[1];
		    }
		}
	    }
	}
    }
    return "neznámý";
}
function get_adapter_settings_driver($ADAPTER) {
    if ((get_adapter_settings_is_adapter($ADAPTER))) {
	if (file_exists("/sys/class/net/".$ADAPTER."/device/driver")) {
	    $pom = basename(readlink("/sys/class/net/".$ADAPTER."/device/driver"));
	    if ($pom != "") {
		return $pom;
	    }
	}
    }
    return "neznámý";
}
function get_adapter_settings_irq($ADAPTER) {
    if ((get_adapter_settings_is_adapter($ADAPTER))) {
	$pom = get_file_value("/sys/class/net/$ADAPTER/device/irq");
	if ($pom != "") {
	    return $pom;
	}
    }
    return "neznámé";
}
function get_adapter_settings_bridge($BRCTL,$ADAPTER) {
    $pom = false;
    // v jakém bridge rozhraní a jakými adaptery je daný adapter
    if ((get_adapter_settings_is_adapter($ADAPTER))) {
	if (array_eregi_search($ADAPTER,$BRCTL)) {
	    foreach ($BRCTL as $LINE) {
		$LINE = preg_split("/[\ \t]+/", $LINE);
		if (get_adapter_settings_is_bridge($LINE[0])) {
		    if ($pom) {
			break;
		    }
		    $BRIDGE_DEV = $LINE[0];
		    unset($BRIDGE_DEVICES);
		    if ($LINE[3] == $ADAPTER) {
			$pom = true;
		    } else {
			$BRIDGE_DEVICES[] = $LINE[3];
		    }
		} else if (get_adapter_settings_is_adapter($LINE[1])) {
		    if ($LINE[1] == $ADAPTER) {
			$pom = true;
		    } else {
			$BRIDGE_DEVICES[] = $LINE[1];
		    }
		}
	    }
	}
    // jedná se o rozhraní bridge, takže nás zajímá jaká rozhraní v něm jsou
    } else if (get_adapter_settings_is_bridge($ADAPTER)) {
	if (array_eregi_search($ADAPTER,$BRCTL)) {
	    foreach ($BRCTL as $LINE) {
		$LINE = preg_split("/[\ \t]+/", $LINE);
		if ($ADAPTER == $LINE[0]) {
		    $BRIDGE_DEV = $LINE[0];
		    $BRIDGE_DEVICES[] = $LINE[3];
		} else if ((get_adapter_settings_is_bridge($LINE[0])) && ($BRIDGE_DEV != "")) {
		    break;
		} else if ((get_adapter_settings_is_adapter($LINE[1])) && ($BRIDGE_DEV != "")) {
		    $BRIDGE_DEVICES[] = $LINE[1];
		}
	    }
	}
    }
    if (is_array($BRIDGE_DEVICES) && ($BRIDGE_DEV != "")) {
	return "zařízení je v ".$BRIDGE_DEV." spolu s ".implode(", ",$BRIDGE_DEVICES);
    } else if (is_array($BRIDGE_DEVICES)) {
	return implode(", ",$BRIDGE_DEVICES);
    } else if (get_adapter_settings_is_bridge($ADAPTER)) {
	return "žádné zařízení";
    }
    return "ne";
}
// funkce pro získání dat z iwlist
function get_adapter_settings_iwlist_channels($adapter) {
    $array = array();
    exec("iwlist ".$adapter." chan | grep Channel | grep -v -i current | awk '{print $2}'", $array);
    if (sizeof($array) == 0) {
	$array = array("01","02","03","04","05","06","07","08","09","10","11","12","13");
    }
    return $array;
}
function get_adapter_settings_iwlist_rates($adapter) {
    $array = array();
    exec("iwlist ".$adapter." rate | grep Mb | grep -v -i current | awk '{print $1}'", $array);
    if (sizeof($array) == 0) {
	$array = array("1","2","5.5","11");
    }
    return $array;
}
function get_adapter_settings_iwlist_txpowers($adapter) {
    $array = array();
    exec("iwlist ".$adapter." txpower | grep dBm | grep -v -i current | awk '{print $1}'", $array);
    if ((sizeof($array) == 0) || ($array[4] == "")) {
	$array = array("0","1","2","3","4","5","6","7","8","9","10","11","12","13","14","15","16","17","18","19","20");
    }
    return $array;
}
function get_adapter_settings_ethtool_rates($adapter) {
    $array = array();
    $array_pom = array();
    exec("sudo /sbin/ethtool ".$adapter." | grep baseT", $array_pom);
    foreach ($array_pom as $pom) {
	$pom = preg_split("/[\ ]+/", $pom);
	foreach ($pom as $p) {
	    if (eregi("10",$p) && (!in_array($p,$array))) {
		$array[] = $p;
	    }
	}
    }
    if (sizeof($array) == 0) {
	$array = array("10baseT/Half","10baseT/Full","100baseT/Half","100baseT/Full","1000baseT/Full");
    }
    return $array;
}
?>
