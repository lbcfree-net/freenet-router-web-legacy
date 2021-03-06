<?php
function get_quagga_ospf($ospf, $adapter)
{
    foreach ($ospf as $line) {

        if ($line == "interface $adapter") {

            return true;
        }
    }

    return false;
}

function save_quagga_daemons() {
    global $quagga;
    if(($soubor = fopen('/tmp/daemons', 'w')))
    {
        fwrite($soubor, '# created by Freenet Router web interface ' . date('H:i j.n.Y') . "\n");
        fwrite($soubor, "#\n");
        fwrite($soubor, 'zebra=' . (($quagga['zebra'] != '') ? $quagga['zebra'] : 'yes') . "\n");
        fwrite($soubor, 'bgpd=' . (($quagga['bgpd'] != '') ? $quagga['bgpd'] : 'no') . "\n");
        fwrite($soubor, 'ospfd=' . (($quagga['ospfd'] != '') ? $quagga['ospfd'] : 'yes') . "\n");
        fwrite($soubor, 'ospf6d=' . (($quagga['ospf6d'] != '') ? $quagga['ospf6d'] : 'no') . "\n");
        fwrite($soubor, 'ripd=' . (($quagga['ripd'] != '') ? $quagga['ripd'] : 'no') . "\n");
        fwrite($soubor, 'ripngd=' . (($quagga['ripngd'] != '') ? $quagga['ripngd'] : 'no') . "\n");
        fwrite($soubor, 'isisd=' . (($quagga['isisd'] != '') ? $quagga['isisd'] : 'no') . "\n");
        fwrite($soubor, "\n");
        fclose($soubor);
        exec('sudo /bin/cp /tmp/daemons /etc/quagga/daemons');
        exec('sudo /bin/chown quagga:quagga /etc/quagga/daemons');
        exec('sudo /bin/chmod 0644 /etc/quagga/daemons');
    }
}

function save_quagga_zebra()
{
    global $hostname;
    global $quagga;

    // Ukládáme jen při změně hostname
    exec('sudo cat /etc/quagga/zebra.conf', $zebra);

    foreach($zebra as $line)
    {
        if (preg_match("/hostname $hostname/", $line)) {
            $pom = true;
            break;
        }
    }
    
    if (!$pom)
    {
	    if(($soubor = fopen('/tmp/zebra.conf', 'w')))
        {
            fwrite($soubor, '# created by Freenet Router web interface ' . date('H:i j.n.Y') . "\n");
            fwrite($soubor, "#\n");
            fwrite($soubor, "hostname $hostname\n");
            fwrite($soubor, 'password ' . (($quagga['password'] != '') ? $quagga['password'] : 'zebra') . "\n");
            fwrite($soubor, 'enable password ' . (($quagga['password'] != '') ? $quagga['password'] : 'zebra') . "\n");
            fwrite($soubor, "log stdout\n");
            fwrite($soubor, "#log file /var/log/quagga/zebra.log\n");
            fwrite($soubor, "#\n");
            fwrite($soubor, "service advanced-vty\n");
            fwrite($soubor, "#\n");
            fwrite($soubor, "# OSPF-ALL.MCAST.NET\n");
            fwrite($soubor, "ip route 224.0.0.5/32 127.0.0.1\n");
            fwrite($soubor, "#\n");
            fwrite($soubor, "# OSPF-DSIG.MCAST.NET\n");
            fwrite($soubor, "ip route 224.0.0.6/32 127.0.0.1\n");
            fwrite($soubor, "#\n");
            fwrite($soubor, "# RIP2-ROUTERS.MCAST.NET (ok, so we don't use rip, but we might as well have it here).\n");
            fwrite($soubor, "ip route 224.0.0.9/32 127.0.0.1\n");
            fwrite($soubor, "#\n");
            fwrite($soubor, "# example of static route\n");
            fwrite($soubor, "#ip route 10.93.249.128/26 10.93.249.250\n");
            fwrite($soubor, "#ip route 0.0.0.0/0 10.93.249.249\n");
            fwrite($soubor, "#\n");
            fwrite($soubor, "access-list term permit 127.0.0.1/32\n");
            fwrite($soubor, "access-list term deny any\n");
            fwrite($soubor, "#\n");
            fwrite($soubor, "line vty\n");
            fwrite($soubor, " access-class vtylist\n");
            fwrite($soubor, "#\n");
            fwrite($soubor, "\n");
            fclose($soubor);

            exec('sudo /bin/cp /tmp/zebra.conf /etc/quagga/zebra.conf');
            exec('sudo /bin/chmod 0644 /etc/quagga/zebra.conf');
        }
    }
}
function save_quagga_ospfd($DATA) {
    global $hostname;
    global $dummy_ip;
    global $quagga;
    $pom = false;
    // načteme firewall
    exec("cat /etc/firewall/firewall.conf", $FIREWALL);
    if(($soubor = fopen("/tmp/ospfd.conf","w")))
    {
        fwrite($soubor, "# created by Freenet Router web interface ".date("H:i j.n.Y")."\n");
        fwrite($soubor, "#\n");
        fwrite($soubor, "hostname ".$hostname."\n");
        fwrite($soubor, "password ".(($quagga["password"] != "") ? $quagga["password"] : "zebra")."\n");
        fwrite($soubor, "enable password ".(($quagga["password"] != "") ? $quagga["password"] : "zebra")."\n");
        fwrite($soubor, "log stdout\n");
        fwrite($soubor, "#log file /var/log/quagga/zebra.log\n");
        fwrite($soubor, "#\n");
        fwrite($soubor, "service advanced-vty\n");
        fwrite($soubor, "#\n");
        fwrite($soubor, "interface lo\n");
        fwrite($soubor, "#\n");
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
            if (($NAME[1] == "ACTIVE") && (!get_adapter_settings_is_dummy($NAME[0])) && ($DATA[$NAME[0].$VLAN_POM."_REMOVE"] == "") && ($DATA[$NAME[0].$VLAN_POM."_QUAGGA"] == "ano")) {
                fwrite($soubor, "interface ".$NAME[0].$VLAN."\n");
                fwrite($soubor, " description ".get_quagga_adapter_description($NAME[0].$VLAN)." ".get_firewall_description($FIREWALL,$NAME[0].$VLAN)."\n");
		fwrite($soubor, " ip ospf cost ".get_quagga_adapter_cost_value($DATA[$NAME[0].$VLAN_POM."_OSPF_COST"])."\n");
                if (($quagga["dead-interval"] > 0) || ($quagga["dead-interval"] == "")) {
                    fwrite($soubor, " ip ospf dead-interval ".(($quagga["dead-interval"] != "") ? $quagga["dead-interval"] : "240")."\n");
                }
                fwrite($soubor, "#\n");
            } else if (($NAME[1] == "ACTIVE") && (get_adapter_settings_is_dummy($NAME[0])) && (!$pom) && ($DATA[$NAME[0].$VLAN_POM."_REMOVE"] == "") && ($DATA[$NAME[0].$VLAN_POM."_QUAGGA"] == "ano")) {
                fwrite($soubor, "interface ".$NAME[0].$VLAN."\n");
                fwrite($soubor, "#\n");
                $pom = true;
            }
        }
        fwrite($soubor, "#\n");
        fwrite($soubor, "router ospf\n");
        fwrite($soubor, " ospf router-id ".$dummy_ip."\n");
        fwrite($soubor, " redistribute connected route-map just-10\n");
        fwrite($soubor, " redistribute static metric-type 1\n");
        fwrite($soubor, " redistribute kernel metric-type 1\n");
        fwrite($soubor, "#\n");
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
            if (($NAME[1] == "ACTIVE") && ($DATA[$NAME[0].$VLAN_POM."_REMOVE"] == "") && ($DATA[$NAME[0].$VLAN_POM."_QUAGGA"] == "ano")) {
                $J = 0;
                while ($J < 20) {
                    if ((filter_var($DATA[$NAME[0].$VLAN_POM."_IP_".$J], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) && ($DATA[$NAME[0].$VLAN_POM."_MASK_".$J] != "")) {
                        fwrite($soubor, "network ".get_network($DATA[$NAME[0].$VLAN_POM."_IP_".$J],$DATA[$NAME[0].$VLAN_POM."_MASK_".$J])." area 0\n");
                    }
                    $J++;
                }
            }
        }
        fwrite($soubor, "#\n");
        fwrite($soubor, "area 0 authentication message-digest\n");
        fwrite($soubor, "#\n");
        fwrite($soubor, "!access-list net-10 permit 10.0.0.0/8\n");
        fwrite($soubor, "access-list term permit 127.0.0.1/32\n");
        fwrite($soubor, "access-list term deny any\n");
        fwrite($soubor, "#\n");
        fwrite($soubor, "route-map just-10 permit 10\n");
        fwrite($soubor, "match ip address net-10\n");
        fwrite($soubor,"access-list net-10 permit 10.0.0.0/8\n");
        fwrite($soubor, "#\n");
        fwrite($soubor, "line vty\n");
        fwrite($soubor, " access-class term\n");
        fwrite($soubor, "#\n");
        fclose($soubor);
        exec('sudo /bin/cp /tmp/ospfd.conf /etc/quagga/ospfd.conf');
        exec('sudo /bin/chmod 0644 /etc/quagga/ospfd.conf');
    }
}
function get_quagga_adapter_description($ADAPTER) {
    if (get_adapter_settings_is_bridge($ADAPTER)) {
	    return 'BRIDGE';
    } else if (get_adapter_settings_is_ethernet($ADAPTER)) {
	    return 'LAN';
    } else if (get_adapter_settings_is_madwifi($ADAPTER)) {
	    return 'ATH';
    } else if (get_adapter_settings_is_wifi('', $ADAPTER)) {
	    return 'WIFI';
    } else if (get_adapter_settings_is_vlan($ADAPTER)) {
	    return 'VLAN';
    } else {
	    return 'DEVICE';
    }
    return '';
}

function get_quagga_adapter_cost_string($OSPF, $ADAPTER) {
    $primary_cost = "primární (10)";
    $primary_secondary = "záložní (17)";

    $adapter_section_reached = false;
    foreach ($OSPF as $line) {
        if (!$adapter_section_reached && $line == "interface $ADAPTER") {
            $adapter_section_reached = true;
        }
        else if ($adapter_section_reached) {
            if (preg_match("/^[ \t]*ip ospf cost [0-9]+/" , $line)) {
               $cost = end(explode(" ",$line));
               if ($cost == 10) {
		    return $primary_cost;
               } else if ($cost == 17) {
		    return $primary_secondary;
               } else {
                    return $cost;
               }
            }
        } 
    }
    return $primary_cost; //default cost
}

function get_quagga_adapter_cost_value($COST_STRING) {
  if (is_numeric($COST_STRING)) {
    return $COST_STRING;
  } else {
    return explode(")", explode("(", $COST_STRING)[1])[0];
  }

}

?>
