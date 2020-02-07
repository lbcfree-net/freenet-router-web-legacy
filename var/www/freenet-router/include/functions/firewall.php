<?php
function save_firewall($text){
    if(($soubor = fopen('/tmp/firewall.conf', 'w'))){
        fwrite($soubor, stripslashes(str_replace("\r", '', $text)));
        fclose($soubor);
        exec('sudo /bin/cp /tmp/firewall.conf /etc/firewall/firewall.conf');
    }
}

function save_firewall_converted($DATA){    
    if(($soubor = fopen('/tmp/firewall.conf', 'w'))){
        if(($soubor_orig = fopen('/etc/firewall/firewall.conf', 'r'))){
            while (!feof($soubor_orig)) {
                $pom2 = fgets($soubor_orig, 4096);
                if (preg_match('/IFACE/',$pom2) && (preg_match('/DUMMY/',$pom2) || preg_match('/DEV/',$pom2))) {
                    break;
                } else {
                    fwrite($soubor,$pom2);
                }
            }
            $I = 0;
            $pom = false;
            foreach ($DATA as $NAME => $VALUE) {
                $NAME = explode("_",$NAME);
                unset($VLAN);
                unset($VLAN_POM);
                // vlan ve formatu eth0_4000_ACTIVE
                if ($NAME[2] == "ACTIVE") {
                   if (is_numeric($NAME[1])) {
                       $VLAN = ".".$NAME[1];
                       $VLAN_POM = "_".$NAME[1];
                       $NAME[1] = $NAME[2];
                   }
                }
                // nebudeme ukládat dummy
                if (($NAME[1] == "ACTIVE") && (!get_adapter_settings_is_dummy($NAME[0])) && ($DATA[$NAME[0].$VLAN_POM."_REMOVE"] == "") && ($VALUE == "ano")) {
                // výjimka pro bridge
                if ((!preg_match('/br/',$DATA[$NAME[0]."_BRIDGE"])) || ($DATA[$DATA[$NAME[0]."_BRIDGE"]."_REMOVE"] != "")) {
                    fwrite($soubor,"DEV".$I."_IFACE=\"".$NAME[0].$VLAN."\"\n");
                    if ($DATA[$NAME[0].$VLAN_POM."_MACGUARD"] != "") {
                        fwrite($soubor, "DEV".$I."_MACGUARD=\"".convert_czech_to_english($DATA[$NAME[0].$VLAN_POM."_MACGUARD"])."\"\n");
                    }
                    if ($DATA[$NAME[0].$VLAN_POM."_DHCP"] != "") {
                        fwrite($soubor, "DEV".$I."_MACGUARD_DHCP=\"".convert_czech_to_english($DATA[$NAME[0].$VLAN_POM."_DHCP"])."\"\n");
                    }
                    if ($DATA[$NAME[0].$VLAN_POM."_DESCRIPTION"] != "") {
                        fwrite($soubor, "DEV".$I."_DESCRIPTION=\"".$DATA[$NAME[0].$VLAN_POM."_DESCRIPTION"]."\"\n");
                    } else {
                        fwrite($soubor, "DEV".$I."_DESCRIPTION=\"\"\n");
                    }
                    fwrite($soubor, "\n");
                    $I++;
                }
                } else if (($NAME[1] == "ACTIVE") && (get_adapter_settings_is_dummy($NAME[0])) && (!$pom) && ($DATA[$NAME[0].$VLAN_POM."_REMOVE"] == "") && ($VALUE == "ano")) {
                    fwrite($soubor, "DUMMY_IFACE=\"".$NAME[0]."\"\n");
                    fwrite($soubor, "\n");
                    $pom = true;
                }
            }
            while (!feof($soubor_orig)) {
                $pom2 = fgets($soubor_orig, 4096);
                $pom3 = false;
                if ((!preg_match('/DEV/', $pom2)) && (!preg_match('/DUMMY_IFACE=/', $pom2))) {
                    if ((($pom2 != "\n") && ($pom2 != '') && ($pom2 != "\t")) || ($pom3)) {
                        $pom3 = true;
                        fwrite($soubor, $pom2);
                    }
                }
            }
            fclose($soubor_orig);
        }
        fclose($soubor);
    }
    exec('sudo /bin/cp /tmp/firewall.conf /etc/firewall/firewall.conf');
}

function save_qos($text){
    if(($soubor = fopen('/tmp/qos.conf', 'w'))){
        fwrite($soubor, stripslashes(str_replace("\r", '', $text)));
        fclose($soubor);
        exec('sudo /bin/cp /tmp/qos.conf /etc/firewall/qos.conf');
    }
}

function get_firewall_qos_direction($qos, $adapter) {
	$qos_link = "";

	foreach ($qos as $line) {
		if ($qos_link != "") {
			if (preg_match("/^${qos_link}_DIRECTION=\"(.*)\"/", $line, $dir)) {
				return $dir[1];
			}
		} else if (preg_match("/^(QOS_LINK[0-9]+)_IFACES=\".*$adapter.*\"/", $line, $m)) {
			$qos_link = $m[1];
		}
	}

	return "LAN";
}

function get_firewall_macguard($FIREWALL,$ADAPTER) {
    $FIREWALL_DATA_DEV = '';
    foreach ($FIREWALL as $LINE) {
        if ($FIREWALL_DATA_DEV != '') {
            if (preg_match("/^${FIREWALL_DATA_DEV}_MACGUARD=\"yes\"/",$LINE)) {
                return true;
    	    }
        } else {
            if (preg_match("/^DEV._IFACE=\"$ADAPTER\"/",$LINE)) {
                $FIREWALL_DATA_DEV = explode("_",$LINE);
                $FIREWALL_DATA_DEV = $FIREWALL_DATA_DEV[0];
            }
        }
    }
    return false;
}

function get_firewall_description($FIREWALL,$ADAPTER) {
    $FIREWALL_DATA_DEV = '';
    foreach ($FIREWALL as $LINE) {
        if ($FIREWALL_DATA_DEV != "") {
            if (preg_match("/^${FIREWALL_DATA_DEV}_DESCRIPTION=/",$LINE)) {
		$LINE = preg_split("/[=\"]+/",$LINE);
                return $LINE[1];
    	    }
        } else {
            if (preg_match("/^DEV._IFACE=\"$ADAPTER\"/",$LINE)) {
                $FIREWALL_DATA_DEV = explode("_",$LINE);
                $FIREWALL_DATA_DEV = $FIREWALL_DATA_DEV[0];
            }
        }
    }
    return false;
}

function get_firewall_dhcp($FIREWALL,$ADAPTER) {
    $FIREWALL_DATA_DEV = '';
    foreach ($FIREWALL as $LINE) {
        if ($FIREWALL_DATA_DEV != "") {
            if (preg_match("/^${FIREWALL_DATA_DEV}_MACGUARD_DHCP=\"yes\"/",$LINE)) {
                return true;
    	    }
        } else {
            if (preg_match("/^DEV._IFACE=\"$ADAPTER\"/i",$LINE)) {
                $FIREWALL_DATA_DEV = explode("_",$LINE);
                $FIREWALL_DATA_DEV = $FIREWALL_DATA_DEV[0];
            }
        }
    }
    return false;
}
?>
