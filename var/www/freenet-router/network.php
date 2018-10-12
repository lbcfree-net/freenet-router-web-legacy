<?php
// načteme hlavičku a často používané funkce, create_selection, is_ip_from_subnet
include 'include/functions/adapter_settings.php';
include 'include/functions/firewall.php';
include 'include/functions/general.php';
include 'include/functions/interfaces.php';
include 'include/functions/quagga.php';
include 'include/functions/networking.php';
include 'include/header.php';
?>
<?php
if (($_POST["save"] != "") && ($_POST["edit"] != "") && ($login)) {
    save_interfaces($_POST["text"]);
}
if (($_POST["restart_interfaces"] != "") && ($login)) {
    restart_interfaces();
}
if (($_POST["reboot"] != "") && ($login)) {
    exec("sudo /sbin/reboot");
}
$remove = false;
foreach ($_POST as $NAME => $VALUE) {
    if (preg_match('/REMOVE/',$NAME)) {
	$remove = true;
    }
    if (preg_match('/ADD_IP/',$NAME)) {
	$add_ip = true;
    }
    if (preg_match('/ADD_GATEWAY/',$NAME)) {
	$add_gateway = true;
    }
    if (preg_match('/DEL_GATEWAY/',$NAME)) {
	$del_gateway = true;
    }
    if (preg_match('/ADD_VLAN/',$NAME)) {
	$add_vlan = true;
    }
}
if (((($_POST["save"] != "") && ($_POST["edit"] == "")) || ($remove) || ($add_ip) || ($add_gateway) || ($del_gateway)) && ($login)) {
    save_interfaces_converted($_POST);
}
if (((($_POST["save"] != "") && ($_POST["edit"] == "")) || ($remove)) && ($login)) {
    save_firewall_converted($_POST);
    save_quagga_daemons();
    save_quagga_zebra();
    save_quagga_ospfd($_POST);
}
if ((($_POST["save"] != "") && ($_POST["edit"] == "")) && ($login)) {
    foreach ($_POST as $NAME => $VALUE) {
	$NAME = explode("_",$NAME);
	if (($NAME[1] = "ACTIVE") || ($NAME[2] = "ACTIVE")) {
	    $ADAPTERS_ONLY[] = $NAME[0];
	}
    }
    foreach (array_unique($ADAPTERS_ONLY) as $ADAPTER_ONLY) {
	save_adapter_settings($_POST,$ADAPTER_ONLY);
    }
}
$interfaces_edit = false;
if (($_POST["edit"] != "") && ($login) && ($_POST["edit_stop"] == "")) $interfaces_edit = true;

?>
<form method="post" name="networkform" action="<?= $_SERVER['PHP_SELF'] ?>">
<?php
if ($login) {
?>
	<table "width=100%">
	<tr>
	<td align=left>možnosti sítě:</td>
	<td>
	<input type="button" name="ADD_ADAPTER" value="přidat adapter" onclick="get_variable('adapter_name','název nového adapteru');">
	</td>
	<td>
	<input type="button" name="ADD_BRIDGE" value="přidat bridge" onclick="window.location=('network.php?add_bridge=true');">
	</td>
	<td>
	<input type="submit" name="save" value="uložit">
	</td>
	<td>
	<input type="submit" name="edit<?= ($interfaces_edit) ? "_stop" : "" ?>" value="<?= ($interfaces_edit) ? "ukončit editaci interfaces" : "editovat soubor interfaces" ?>">
	</td>
	<td>
	<input type="submit" name="restart_interfaces" value="restartovat networking">
	</td>
	<td>
	<input type="submit" name="reboot" value="restartovat router">
	</td>
	</tr>
    </table>
<?php
}
?>
<?php
if ($interfaces_edit) {
?>  <hr>
    <br>
    <textarea cols="130" rows="50" tabindex="2" name="text" wrap=off>
<?php
    if(($file = fopen("/etc/network/interfaces","r")))
    {
        while(!feof($file)) 
        {
            echo fgets($file, 1000);
        }
        fclose($file);
    }    
?>  </textarea>
    <br>
    <input type="submit" name="save" value="uložit">
    <input type="hidden" name="edit" value="neco">
<?php
} else {
// přečteme data z ip
exec("ip addr show", $NETWORK);
// jestli je na zařízení quagga aktivní poznáme podle ospfd.conf
exec("cat /etc/quagga/ospfd.conf", $QUAGGA);
// macguarda a qos přečteme z konfigurace firewallu
exec("cat /etc/firewall/firewall.conf",$FIREWALL);
// načteme data z iwconfigu
exec("sudo /sbin/iwconfig 2>/dev/null",$IWCONFIGS);
// načteme dat z lspci
exec("lspci",$LSPCIS);
// některé další věci poznáme z interfaces
exec("cat /etc/network/interfaces", $INTERFACES);
// nastavení bridge poznáme z brctl
exec("brctl show", $BRCTL);
// zobrazíme hezkou tabulku
$ADAPTERS = array_unique(array_merge(get_interfaces_all($INTERFACES),get_networking_all($NETWORK)));
if (($_GET["adapter_name"] != "") && ($_GET["adapter_name"] != "null")) {
    if (!in_array($_GET["adapter_name"],$ADAPTERS)) {
	$ADAPTERS[] = $_GET["adapter_name"];
    }
} else if (($_GET["adapter"] != "") && ($_GET["adapter"] != "null") && ($_GET["vlan_number"] != "") && ($_GET["vlan_number"] != "null")) {
    if (!in_array($_GET["adapter"].".".$_GET["vlan_number"],$ADAPTERS)) {
	$ADAPTERS[] = $_GET["adapter"].".".$_GET["vlan_number"];
    }
} else if ($_GET["add_bridge"] == "true") {
    $A = 0;
    while (true) {
	if (in_array("br".$A,$ADAPTERS)) {
	    $A++;
	} else {
	    $ADAPTERS[] = "br".$A;
	    break;
	}
    }
}
usort($ADAPTERS,"sort_adapters");

// zalozky rozhrani
?>
    <div id="menu"><ul>
<?php
$actual_num = $_GET['actual_interface'];
$int_num = 0;
foreach ($ADAPTERS as $ADAPTER) { ?>
	<li><a class="<?= ((($actual_num == "") && ($int_num == 0)) || ($actual_num == $int_num)) ? 'active' : '' ?>" id="menu<?= $int_num ?>" href="JavaScript: set(<?= $int_num ?>);"><?= $ADAPTER ?></a></li>
<?php	$int_num++;
}
?>
    </ul></div>
    <br clear="all">
<?php

$int_num = 0;
foreach ($ADAPTERS as $ADAPTER) {
?>
    <div class="interface" id="interface<?= $int_num ?>">
<?php  // získáme informace o adapteru
    $loopback = get_adapter_settings_is_loopback($ADAPTER);
    $dummy = get_adapter_settings_is_dummy($ADAPTER);
    $adapter = get_adapter_settings_is_adapter($ADAPTER);
    $ethernet = get_adapter_settings_is_ethernet($ADAPTER);
    $wifi = get_adapter_settings_is_wifi($IWCONFIGS,$ADAPTER);
    $madwifi = get_adapter_settings_is_madwifi($ADAPTER);
    $vlan = get_adapter_settings_is_vlan($ADAPTER);
    $bridge = get_adapter_settings_is_bridge($ADAPTER);
    // všeobecné
    $ADAPTER_ACTIVE = get_network_adapter_active($NETWORK,$ADAPTER);
    $ADAPTER_RATE = get_adapter_settings_rate($IWCONFIGS,$ADAPTER);
    $ADAPTER_MODEL = get_adapter_settings_model($LSPCIS,$ADAPTER);
    $ADAPTER_DRIVER = get_adapter_settings_driver($ADAPTER);
    $ADAPTER_IRQ = get_adapter_settings_irq($ADAPTER);
    $ADAPTER_MAC = get_network_adapter_mac($NETWORK,$ADAPTER);
    $ADAPTER_BRIDGE = get_adapter_settings_bridge($BRCTL,$ADAPTER);
    $ADAPTER_VLAN = get_networking_adapter_vlan($NETWORK,$ADAPTER);
    // ethernet
    $ADAPTER_LINK = get_adapter_settings_ethtool_link($ADAPTER);
    $ADAPTER_DUPLEX = get_adapter_settings_ethtool_duplex($ADAPTER);
    $ADAPTER_AUTONEG = get_adapter_settings_ethtool_autoneg($ADAPTER);
    // wifi
    $ADAPTER_MODE = get_adapter_settings_iwconfig_mode($IWCONFIGS,$ADAPTER);
    $ADAPTER_ESSID = get_adapter_settings_iwconfig_essid($IWCONFIGS,$ADAPTER);
    $ADAPTER_BSSID = get_adapter_settings_iwconfig_bssid($IWCONFIGS,$ADAPTER);
    $ADAPTER_CHANNEL = get_adapter_settings_iwconfig_channel($IWCONFIGS,$ADAPTER);
    $ADAPTER_FREQUENCY = get_adapter_settings_iwconfig_frequency($IWCONFIGS,$ADAPTER);
    $ADAPTER_TXPOWER = get_adapter_settings_iwconfig_txpower($IWCONFIGS,$ADAPTER);
    $ADAPTER_KEY = get_adapter_settings_iwconfig_wep($IWCONFIGS,$ADAPTER);
    $ADAPTER_SIGNAL = get_adapter_settings_iwconfig_signal($IWCONFIGS,$ADAPTER);
    // zobrazíme danou tabulku
?>  <table class="main" width="100%">
    <tr>
    <td colspan=6 width="100%" align="left">
    <table class="settings" width="100%">
    <tr><?= show_card_properites($ADAPTER) ?></tr>
    </table>
    <table class="main" width="100%">
    <tr>
    <td width="31%" align="left" valign=top>
    <table width="100%" class="settings">
<?php  // hlavička
    table_head("základní nastavení");
    // zjistíme jestli je karta nahozená
    create_selection("aktivní","${ADAPTER}_ACTIVE", array("ano", "ne"), get_interfaces_active($INTERFACES,$ADAPTER),"");
    // popis zařízení
    if ((!$dummy) && (!$loopback)) {
	table_text_array("popis", $ADAPTER."_DESCRIPTION", get_firewall_description($FIREWALL,$ADAPTER),"","");
    }
    // zjistime jestli na rozhrani je zapnuta quagga
    create_selection('přijímat OSPF',"${ADAPTER}_QUAGGA", array('ano', 'ne'), get_quagga_ospf($QUAGGA,$ADAPTER),'', 'Pouze pro páteřní spoje a propoje AP!');
    if ((!$dummy) && (!$loopback)) {
	// zjistime jestli na rozhrani běží dhcp server
	create_selection("dhcp server","${ADAPTER}_DHCP", array("ano", "ne"), get_firewall_dhcp($FIREWALL, $ADAPTER),"");
	// zjistime jestli na rozhrani je aktivovan macguard a qos
	// macguard
	create_selection("macguard",$ADAPTER."_MACGUARD", array("ano", "ne"), get_firewall_macguard($FIREWALL, $ADAPTER),"");
	// qos
	create_selection("qos",$ADAPTER."_QOS", array("ano", "ne"), get_firewall_qos($FIREWALL, $ADAPTER),"");
	create_selection("qos - typ",$ADAPTER."_QOS_DIRECTION", array("LAN", "WAN", "NAT", "WBCK", "LBCK"), get_firewall_qos_direction($FIREWALL, $ADAPTER),"");
	table_text_array("rychlost", $ADAPTER."_QOS_RATE", get_firewall_qos_rate($FIREWALL, $ADAPTER),"6","kbit/s","Reálně dosažitelná rychlost na rozhraní!");
	create_selection("dhcp klient",$ADAPTER."_DHCP_CLIENT", array("ano", "ne"), get_interfaces_dhcp($INTERFACES,$ADAPTER),"");
    }
    table_line();
    // ip adresa
    unset($ADAPTER_GATEWAY);
    foreach (get_interfaces_ip($INTERFACES,$ADAPTER) as $K => $ADAPTER_IP) {
	table_text_array("ip adresa", $ADAPTER."_IP_".($K), $ADAPTER_IP[0],"","","IP adresu odeberete smazáním tohoto pole a následným uložením.");
	table_text_array("maska sítě", $ADAPTER."_MASK_".($K), $ADAPTER_IP[1],"","");
	if ($ADAPTER_IP[2] != "") {
	    table_text_array("brána", $ADAPTER."_GATEWAY_".($K), $ADAPTER_IP[2],"","");
	    $ADAPTER_GATEWAY = $ADAPTER_IP[2];
	}
    }
    table_button($ADAPTER."_ADD_IP","přidat adresu");
    if ((!$dummy) && (!$loopback)) {
	if ($ADAPTER_GATEWAY != "") {
	    table_button($ADAPTER."_DEL_GATEWAY","odebrat bránu");
	} else {
	    table_button($ADAPTER."_ADD_GATEWAY","přidat bránu");
	}
	if ((!$bridge) && (!$vlan)) {
	    table_line();
	    create_selection("bridge","${ADAPTER}_BRIDGE", array_merge(get_interfaces_all_bridges($INTERFACES), array("ne")), get_interfaces_bridge($INTERFACES,$ADAPTER),"");
	}
	if (!$vlan) {
	    table_line();
	    if (sizeof(get_interfaces_vlan($INTERFACES,$ADAPTER)) != 0) {
		table_entry("vlan", implode(",",get_interfaces_vlan($INTERFACES,$ADAPTER)));
	    }
	    table_button_action($ADAPTER."_ADD_VLAN","přidat vlan","get_variable('adapter=".$ADAPTER."&vlan_number','číslo vlan tagu');");
	}
    }
    table_line();
    table_button($ADAPTER."_REMOVE","odstranit adapter");
    echo "</table>";
    echo "</td>";
    
    // vložka
    echo '<td class="vlozka" width="2%" align="left" valign=top></td>';
    
    // pokročilá nastavení
    echo "<td width=\"30%\" align=\"left\" valign=top>";
    echo '<table width="100%" class="settings">';
    table_head("pokročilá nastavení");
    if ($adapter) {
	// načteme informace z nastavení v souboru ethX, wlanX a athX
	unset($ADAPTER_INFO);
	if (file_exists("/etc/network/$ADAPTER")) {
	    exec("cat /etc/network/$ADAPTER", $ADAPTER_INFO);
	}
	// načteme další informace o adapteru
	if ($wifi) {
	    // pro wifi nás zajímá iwpriv a případně iwlist
	    unset($ADAPTER_IWPRIVS);
	    exec("iwpriv ".$ADAPTER, $ADAPTER_IWPRIVS);
	    // zobrazíme výběr režimu, ap|sta|adhoc
	    create_selection("režim",$ADAPTER."_MODE", array("ap", "sta", "adhoc"), get_adapter_settings_value($ADAPTER_INFO,"MODE"),"");
	    // zobrazíme essid
	    table_text_array("essid",$ADAPTER."_ESSID", get_adapter_settings_value($ADAPTER_INFO,"ESSID"),"30","");
	    // zobrazíme kanál, podle dostupných kanálů z iwlistu
	    create_selection("kanál",$ADAPTER."_CHANNEL", get_adapter_settings_iwlist_channels($ADAPTER), get_adapter_settings_value($ADAPTER_INFO,"CHANNEL"),"");
	    // wep klíč není žádná legrace
	    $WEP_KEY_VALUE = get_adapter_settings_value($ADAPTER_INFO,"KEY");
	    unset($WEP_KEY_TYPE);
	    if ($WEP_KEY_VALUE == "off") {
		$WEP_KEY_VALUE = "";
		$WEP_KEY_TYPE = "žádný";
	    } else if ((substr($WEP_KEY_VALUE,0,2) == "s:") && (strlen($WEP_KEY_VALUE) <= 7)) {
		$WEP_KEY_TYPE = "64bit ASCII";
		$WEP_KEY_VALUE = substr($WEP_KEY_VALUE,2);
	    } else if ((substr($WEP_KEY_VALUE,0,2) == "s:") && (strlen($WEP_KEY_VALUE) > 7)) {
		$WEP_KEY_TYPE = "128bit ASCII";
		$WEP_KEY_VALUE = substr($WEP_KEY_VALUE,2);
	    } else if ((substr($WEP_KEY_VALUE,0,2) != "s:") && (strlen(preg_replace("[^0-9A-F]","",$WEP_KEY_VALUE)) <= 10)) {
		$WEP_KEY_TYPE = "64bit HEXA";
	    } else if ((substr($WEP_KEY_VALUE,0,2) != "s:") && (strlen(preg_replace("[^0-9A-F]","",$WEP_KEY_VALUE)) > 10)) {
		$WEP_KEY_TYPE = "128bit HEXA";
	    }
	    if (!$login) {
		create_selection("typ wep klíče","${ADAPTER}_KEY_TYPE", array("skrytý"), "skrytý", "");
	    } else {
		create_selection("typ wep klíče","${ADAPTER}_KEY_TYPE", array("žádný","64bit ASCII","64bit HEXA","128bit ASCII","128bit HEXA"), $WEP_KEY_TYPE, "");
	    }
	    if (!$login) {
		table_text_array("wep klíč", ${ADAPTER}."_KEY", "skrytý","30","");
	    } else {
		table_text_array("wep klíč", ${ADAPTER}."_KEY", $WEP_KEY_VALUE,"30","");
	    }
	    table_line();
	    // získat rychlost není taky žádná legrace
	    create_selection("rychlost","${ADAPTER}_RATE", array_merge(array("auto"),get_adapter_settings_iwlist_rates($ADAPTER)), get_adapter_settings_value($ADAPTER_INFO,"RATE"),"Mb/s");
	    // retry získáme snadno, ale u madwifi toto nastavení nechodí
	    create_selection("retry","${ADAPTER}_RETRY", "1:20", get_adapter_settings_value($ADAPTER_INFO,"RETRY"),"");
	    // W_MODE mají jen některé karty
	    if (array_eregi_search("mode",$ADAPTER_IWPRIVS)) {
		create_selection("wifi mód","${ADAPTER}_W_MODE", array("0","1","2","3"), get_adapter_settings_value($ADAPTER_INFO,"W_MODE"), array("(auto)","(802.11a)","(802.11b)","(802.11g)"));
	    }
	    // TURBO také mají jen některé karty
	    if (array_eregi_search("turbo",$ADAPTER_IWPRIVS)) {
		create_selection("turbo","${ADAPTER}_TURBO", array("0 (ne)","1 (ano)"), get_adapter_settings_value($ADAPTER_INFO,"TURBO"), "none");
	    }
	    // sensitivita je podporována jen někde, ale je možné jí nastavit asi na všech typech
	    create_selection("sensitivita","${ADAPTER}_SENSITIVITY", "-98:-50", get_adapter_settings_value($ADAPTER_INFO,"SENSITIVITY"),"dB");
	    // txpower je také velmi zapeklité nastavení
	    create_selection("výkon","${ADAPTER}_TXPOWER", get_adapter_settings_iwlist_txpowers($ADAPTER), get_adapter_settings_value($ADAPTER_INFO,"TXPOWER"), "dBm");
	    // bridgování na ap
	    create_selection("ap bridge","${ADAPTER}_AP_BRIDGE", array("0 (ne)","1 (ano)"), get_adapter_settings_value($ADAPTER_INFO,"AP_BRIDGE"), "none");
	    // distance a anténu nastavujeme jen pro madwifi
	    if ($madwifi) {
		table_line();
		create_selection("vzdálenost","${ADAPTER}_DISTANCE", "1:10", get_adapter_settings_value($ADAPTER_INFO,"DISTANCE"),"km");
		create_selection("anténa","${ADAPTER}_ANTENNA", array("0 (auto)","1","2"), get_adapter_settings_value($ADAPTER_INFO,"ANTENNA"), "none");
		create_selection("režim spojení","${ADAPTER}_CONNECTION", array("madwifi","generic"), get_adapter_settings_value($ADAPTER_INFO,"CONNECTION"), "");
		create_selection("wds","${ADAPTER}_WDS", array("0","1"), get_adapter_settings_value($ADAPTER_INFO,"WDS"), array("(ne)","(ano)"));
	    }
	} else {
	    // pro ethernet nás zajímá ethtool, případně mii-tool (neimplementováno)
	    create_selection("rychlost","${ADAPTER}_RATE", get_adapter_settings_ethtool_rates($ADAPTER), get_adapter_settings_value($ADAPTER_INFO,"RATE_LAN"),"");
	    // typ je celkem snadné zjistit
	    create_selection("typ","${ADAPTER}_TYPE", array("F","A"), get_adapter_settings_value($ADAPTER_INFO,"TYPE"), array("(vynutit)","(doporučit)"));
	}
    }
    echo "</table>";
    echo "</td>";

    // vložka
    echo '<td class="vlozka" width="2%" align="left" valign=top></td>';
    
    // statistiky
    echo "<td width=\"35%\" align=left valign=top>";
    echo '<table width="100%" class="settings">';
    table_head("statistiky");
    // typ adapteru
    if ($wifi) {
	table_entry("typ", "wifi");
    } else if ($dummy) {
	table_entry("typ", "dummy");
    } else if ($bridge) {
	table_entry("typ", "bridge");
    } else if ($ethernet) {
	table_entry("typ", "ethernet");
    } else if ($vlan) {
	table_entry("typ", "vlan");
    } else if ($loopback) {
	table_entry("typ", "loopback");
    }
    if ($adapter) {
	// model adapteru
	table_entry("model", $ADAPTER_MODEL);
	// ovladač adapteru
	table_entry("ovladač", $ADAPTER_DRIVER);
	table_entry("irq", $ADAPTER_IRQ);
    }
    table_line();
    // status adapteru
    table_entry("aktivní", $ADAPTER_ACTIVE);
    // mac adapteru
    table_entry("mac adresa", $ADAPTER_MAC);
    // ip adresy a masky adapteru
    foreach (get_networking_adapter_ip($NETWORK,$ADAPTER) as $K => $ADAPTER_IP) {
        table_entry("ip adresa", $ADAPTER_IP[0]);
        table_entry("maska", $ADAPTER_IP[1]);
	if ($ADAPTER_IP[2] != "") {
	    table_entry("brána", $ADAPTER_IP[2]);
	}
    }
    if (($adapter) || ($bridge)) {
	table_entry("bridge", $ADAPTER_BRIDGE);
	table_entry("vlan", $ADAPTER_VLAN);
    }
    if ($wifi) {
	table_line();
	table_entry("režim", $ADAPTER_MODE);
	table_entry("essid", $ADAPTER_ESSID);
	table_entry("bssid", $ADAPTER_BSSID);
	table_entry("kanál", $ADAPTER_CHANNEL);
	table_entry("frekvence", $ADAPTER_FREQUENCY);
	table_entry("rychlost", $ADAPTER_RATE);
	if ($login) {
	    table_entry("wep klíč", $ADAPTER_KEY);
	} else {
	    table_entry("wep klíč", "skrytý");
	}
	table_entry("výkon", $ADAPTER_TXPOWER);
	if (array_eregi_search("mode",$ADAPTER_IWPRIVS)) {
	    table_entry("wifi mód", get_adapter_settings_iwpriv_value($ADAPTER, "get_mode"));
	}
	if (array_eregi_search("turbo",$ADAPTER_IWPRIVS)) {
	    table_entry("turbo", get_adapter_settings_iwpriv_value($ADAPTER, "get_wds"));
	}
	if (array_eregi_search(" bridge_packets ",$ADAPTER_IWPRIVS)) {
	    table_entry("ap bridge", get_adapter_settings_iwpriv_value($ADAPTER, "getbridge_packe"));
	}
	if (array_eregi_search(" ap_bridge ",$ADAPTER_IWPRIVS)) {
	    table_entry("ap bridge", get_adapter_settings_iwpriv_value($ADAPTER, "get_ap_bridge"));
	}
	if (array_eregi_search(" wds ",$ADAPTER_IWPRIVS)) {
	    table_entry("wds", get_adapter_settings_iwpriv_value($ADAPTER, "get_wds"));
	}
	if ($madwifi) {
	    $ADAPTER_PARENT = get_file_value("/proc/sys/net/$ADAPTER/%parent");
	    table_entry("nadřazený adapter", $ADAPTER_PARENT);
	    table_entry("tx, rx, diverzita", "[".get_file_value("/proc/sys/dev/$ADAPTER_PARENT/txantenna").",".get_file_value("/proc/sys/dev/$ADAPTER_PARENT/rxantenna").",".get_file_value("/proc/sys/dev/$ADAPTER_PARENT/diversity")."]");
	    table_entry("vzdálenost", "cca ".((get_file_value("/proc/sys/dev/$ADAPTER_PARENT/slottime") - 9) * 300)." m");
	    table_entry("ack, cts, slottime", "[".get_file_value("/proc/sys/dev/$ADAPTER_PARENT/acktimeout").",".get_file_value("/proc/sys/dev/$ADAPTER_PARENT/ctstimeout").",".get_file_value("/proc/sys/dev/$ADAPTER_PARENT/slottime")."]");
	}
	unset($ADAPTER_AR);
	unset($ADAPTER_WMM);
	unset($ADAPTER_BURST);
	unset($ADAPTER_FF);
	if (array_eregi_search(" ar ",$ADAPTER_IWPRIVS)) {
	    $ADAPTER_AR = get_adapter_settings_iwpriv_value($ADAPTER, "get_ar");
	}
	if (array_eregi_search(" wmm ",$ADAPTER_IWPRIVS)) {
	    $ADAPTER_WMM = get_adapter_settings_iwpriv_value($ADAPTER, "get_wmm");
	}
	if (array_eregi_search(" burst ",$ADAPTER_IWPRIVS)) {
	    $ADAPTER_BURST = get_adapter_settings_iwpriv_value($ADAPTER, "get_burst");
	}
	if (array_eregi_search(" ff ",$ADAPTER_IWPRIVS)) {
	    $ADAPTER_FF = get_adapter_settings_iwpriv_value($ADAPTER, "get_ff");
	}
	if (($ADAPTER_AR == "1") && ($ADAPTER_WMM == "1") && ($ADAPTER_BURST == "1") && ($ADAPTER_FF == "1")) {
	    table_entry("režim spojení", "madwifi");
	} else if (($ADAPTER_AR == "0") && ($ADAPTER_WMM == "0") && ($ADAPTER_BURST == "0") && ($ADAPTER_FF == "0")) {
	    table_entry("režim spojení", "generic");
	}
	if (($ADAPTER_AR != "") && ($ADAPTER_WMM != "") && ($ADAPTER_BURST != "") && ($ADAPTER_FF != "")) {
	    table_entry("ar, wmm, burst, ff", "[$ADAPTER_AR,$ADAPTER_WMM,$ADAPTER_BURST,$ADAPTER_FF]");
	}
    } else if ($adapter) {
	table_line();
	table_entry("rychlost", $ADAPTER_RATE);
	table_entry("duplex", $ADAPTER_DUPLEX);
	table_entry("linka", $ADAPTER_LINK);
    }
?>
    </table>
    </td>
    </tr>
    </table>
    </td>
    </tr>
    </table>
    <hr>
    </div>
<?php  $int_num++;
}
}
?>
</form>
<script type="text/javascript">
    set(<?= 0 + $_GET['actual_interface'] ?>, 99);
</script>
<?php
include 'include/footer.php';
?>
