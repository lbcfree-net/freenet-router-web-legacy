<?php
function save_interfaces($TEXT) {
    if(($soubor = fopen("/tmp/interfaces","w")))
    {
        fwrite($soubor,stripslashes(str_replace("\r","",$TEXT)));
        fclose($soubor);
        exec("sudo /bin/cp /tmp/interfaces /etc/network/interfaces");
    }
}
function save_interfaces_converted($DATA) {
    if(($soubor = fopen("/tmp/interfaces","w")))
    {
        // loopback je také třeba
        fwrite($soubor, "auto lo\n");
        fwrite($soubor, "iface lo inet loopback\n");
        fwrite($soubor, "\n");
        foreach ($DATA as $NAME => $VALUE) {
            $NAME = explode("_",$NAME);
            unset($VLAN);
            unset($VLAN_POM);
            unset($BRIDGE_DEVICES);
            // vlan ve formatu eth0_4000_ACTIVE
            if ($NAME[2] == "ACTIVE") {
                if (is_numeric($NAME[1])) {
                    $VLAN = ".".$NAME[1];
                    $VLAN_POM = "_".$NAME[1];
                    $NAME[1] = $NAME[2];
                }
            }
            if (($NAME[1] == "ACTIVE") && ($DATA[$NAME[0].$VLAN_POM."_REMOVE"] == "")) {
                // pokud je zařízení v režimu bridge, tak ho nesmíme nahazovat ani VLAN, jen pokud odstraňujeme bridge rozhraní!
                if (($VALUE == "ano") && ((!preg_match('/br/',$DATA[$NAME[0]."_BRIDGE"])) || ($DATA[$DATA[$NAME[0]."_BRIDGE"]."_REMOVE"] != ""))) {
                    fwrite($soubor, "auto ".$NAME[0].$VLAN."\n");
                }
                if ($DATA[$NAME[0].$VLAN_POM."_DHCP_CLIENT"] == "ano") {
                    fwrite($soubor, "iface ".$NAME[0].$VLAN." inet dhcp\n");
                } else {
                    if(filter_var($DATA[$NAME[0].$VLAN_POM."_IP_0"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
                        fwrite($soubor, "iface ".$NAME[0].$VLAN." inet static\n");
                    }else{
                        fwrite($soubor, "iface ".$NAME[0].$VLAN." inet6 static\n");
                    }
                }
                $J = 0;
                $K = 0;
                while ($J < 20) {
                    // přidání ip
                    if (($DATA[$NAME[0].$VLAN_POM."_ADD_IP"] != "") && (!$pom) && ($DATA[$NAME[0].$VLAN_POM."_IP_".$J] == "")) {
                        $DATA[$NAME[0].$VLAN_POM."_IP_".$J] = "0.0.0.0";
                        $DATA[$NAME[0].$VLAN_POM."_MASK_".$J] = "255.255.255.0";
                        $pom = true;
                    }
                    // zapsání ip
                    if ($DATA[$NAME[0].$VLAN_POM . '_IP_' . $J] != '') {
                        if ($K != 0) {
                            if (($VALUE == 'ano') && ((!preg_match('/br/', $DATA[$NAME[0] . '_BRIDGE'])) &&
                                    filter_var($DATA[$NAME[0] . $VLAN_POM . '_IP_' . $J], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
                                    ($DATA[$DATA[$NAME[0] . '_BRIDGE'] . '_REMOVE'] != ''))) {
                                fwrite($soubor, 'auto ' . $NAME[0] . $VLAN . ':' . $J . "\n");
                            }
                            if(filter_var($DATA[$NAME[0].$VLAN_POM."_IP_".$J], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
                                fwrite($soubor, "iface ".$NAME[0].$VLAN.":".$J." inet static\n");
                            }else{
                                fwrite($soubor, 'iface ' . $NAME[0] . "$VLAN inet6 static\n");
                            }
                        }
                        fwrite($soubor,"\taddress ".$DATA[$NAME[0].$VLAN_POM."_IP_".$J]."\n");
                        if ($DATA[$NAME[0].$VLAN_POM."_MASK_".$J] != "") {
                            fwrite($soubor, "\tnetmask ".$DATA[$NAME[0].$VLAN_POM."_MASK_".$J]."\n");
                            if(filter_var($DATA[$NAME[0].$VLAN_POM."_IP_".$J], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
                                fwrite($soubor, "\tbroadcast ".get_broadcast($DATA[$NAME[0].$VLAN_POM."_IP_".$J], $DATA[$NAME[0].$VLAN_POM."_MASK_".$J])."\n");
                            }
                        }
                        if (($DATA[$NAME[0].$VLAN_POM."_GATEWAY_".$J] != "") && ($DATA[$NAME[0].$VLAN_POM."_DEL_GATEWAY"] == "")) {
                            fwrite($soubor, "\tgateway ".$DATA[$NAME[0].$VLAN_POM."_GATEWAY_".$J]."\n");
                        } else if ($DATA[$NAME[0].$VLAN_POM."_ADD_GATEWAY"] != "") {
                            $DATA[$NAME[0].$VLAN_POM."_GATEWAY_".$J] = "0.0.0.0";
                            fwrite($soubor, "\tgateway ".$DATA[$NAME[0].$VLAN_POM."_GATEWAY_".$J]."\n");
                        }
                    }
                    // bridge
                    if (($J == 0) && (preg_match('/br/',$NAME[0].$VLAN))) {
                        foreach ($DATA as $N => $V) {
                            if ($V == $NAME[0].$VLAN) {
                                $N = explode("_",$N);
                                $BRIDGE_DEVICES[] = $N[0];
                            }
                        }
                        if (is_array($BRIDGE_DEVICES)) {
                            fwrite($soubor, "\tbridge_ports ".implode(" ",$BRIDGE_DEVICES)."\n");
                        }
                    }
                    if ($DATA[$NAME[0].$VLAN_POM."_IP_".$J] != "") {
                        fwrite($soubor, "\n");
                        $K++;
                    }
                    $J++;
                }
                if ($K == 0) {
                    fwrite($soubor, "\n");
                }
            }
        }
        fclose($soubor);
        exec("sudo /bin/cp /tmp/interfaces /etc/network/interfaces");
    }
}
function restart_interfaces() {
    exec("sudo /etc/init.d/networking restart");
}
function get_interfaces_all($INTERFACES) {
    $array = array();
    foreach ($INTERFACES as $LINE) {
	$LINE = preg_split("/[\ :\t]+/", $LINE);
	if (($LINE[0] == "iface") && ($LINE[2] == "inet"||$LINE[2] == "inet6")) {
	    if ($LINE[1] != "lo") {
		$array[] = $LINE[1];
	    }
	}
    }
    return $array;
}
// zjistíme všechny ip adresy nastavené v interfaces
function get_interfaces_ip($INTERFACES,$ADAPTER) {
    $pom = false;
    $I = "0";
    $array = array();
    
    foreach ($INTERFACES as $LINE_NOT_SPLIT) {
	$LINE = preg_split("/[\ :\t]+/", $LINE_NOT_SPLIT);
	// ip adresa rozhraní
	if (($LINE[0] == "iface") && ($LINE[1] == $ADAPTER) && (($LINE[2] == "inet")||($LINE[2] == "inet6"))) {
	    if ($pom) {
		$I++;
	    }
	    $pom = true;
            $pom_ipv4=(($LINE[2]=="inet")?true:false);
	//dodatečné ip adresy rozhraní
	} else if (($LINE[0] == "iface") && ($LINE[1] == $ADAPTER) && ($LINE[2] != "inet") && ($LINE[2] != "inet6")) {
	    if ($pom) {
		$I++;
	    }
	    $pom = true;
            $pom_ipv4=(($LINE[3]=="inet")?true:false);
	} else if ($LINE[0] == "iface") {
	    $pom = false;
	} else if (($LINE[1] == "address") && ($pom)) {
            if($pom_ipv4){
                //normalni ipv4
                $array[$I][0] = trim($LINE[2]);
            }else{//jedna se o ipv6
                $addr_split=  explode("address ", $LINE_NOT_SPLIT);
                $array[$I][0] = $addr_split[1];
            }
	} else if (($LINE[1] == "netmask") && ($pom)) {
	    $array[$I][1] = trim($LINE[2]);
	} else if (($LINE[1] == "gateway") && ($pom)) {
	    $array[$I][2] = trim($LINE[2]);
	}
    }
    return $array;
}
// zjistíme jestli na zařízení běží dhcp klient
function get_interfaces_dhcp($INTERFACES,$ADAPTER) {
    foreach ($INTERFACES as $LINE) {
	$LINE = preg_split("/[\ :\t]+/", $LINE);
	if (($LINE[0] == "iface") && ($LINE[1] == $ADAPTER) && ($LINE[2] == "inet") && ($LINE[3] == "dhcp")) {
	    return true;
	} else if (($LINE[0] == "iface") && ($LINE[1] == $ADAPTER) && ($LINE[2] != "inet") && ($LINE[3] == "inet") && ($LINE[4] == "dhcp")) {
	    return true;
	}
    }    
    return false;
}
function get_interfaces_active($INTERFACES,$ADAPTER) {
    foreach ($INTERFACES as $LINE) {
	$LINE = preg_split("/[\ :\t]+/", $LINE);
	if (($LINE[0] == "auto") && ($LINE[1] == $ADAPTER)) {
	    return true;
	}
    }
    return false;
}
function get_interfaces_bridge($INTERFACES,$ADAPTER) {
    foreach ($INTERFACES as $LINE) {
	$LINE = preg_split("/[\ :\t]+/", $LINE);
	if (($LINE[0] == "iface") && (preg_match('/br/', $LINE[1])) && ($LINE[2] == "inet")) {
	    $BRIDGE_DEV = $LINE[1];
	}
	if (($LINE[1] == "bridge_ports")) {
	    foreach ($LINE as $VALUE) {
		if ($VALUE == $ADAPTER) {
		    return $BRIDGE_DEV;
		}
	    }
	}
    }
    return false;
}
function get_interfaces_all_bridges($INTERFACES) {
    $array = array();
    foreach ($INTERFACES as $LINE) {
	$LINE = preg_split("/[\ :\t]+/", $LINE);
	if (($LINE[0] == "iface") && (preg_match('/br/',$LINE[1])) && ($LINE[2] == "inet")) {
	    $array[] = $LINE[1];
	}
    }
    return $array;
}
function get_interfaces_vlan($INTERFACES,$ADAPTER) {
    $array = array();
    foreach ($INTERFACES as $LINE) {
	$LINE = preg_split("/[\ .\t]+/i", $LINE);
	if (($LINE[0] == "iface") && ($ADAPTER == $LINE[1]) && (($LINE[3] == "inet") || ($LINE[4] == "inet"))) {
	    $LINE = explode(":", $LINE[2]);
	    if (!in_array($LINE[0],$array)) {
		$array[] = $LINE[0];
	    }
	}
    }
    return $array;
}
?>