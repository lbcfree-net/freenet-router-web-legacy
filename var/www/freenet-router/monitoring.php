<?php
// načteme hlavičku a často používané funkce, create_selection, is_ip_from_subnet
include 'include/functions/general.php';
include 'include/functions/adapter_settings.php';
include 'include/functions/firewall.php';
include 'include/functions/networking.php';
include 'include/functions/interfaces.php';
include 'include/functions/monitoring.php';
include 'include/functions/system.php';
include 'include/functions/index.php';


include 'include/header.php';


foreach ($_REQUEST as $VALUE => $NAME) {
    if (eregi("QOS",$VALUE) && ($login)) {
	$VALUE = str_replace("_",".",substr($VALUE,4));
	monitoring_set_qos($VALUE,$NAME);
    }
}
if (($_POST["RESET_COUNTS"] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall account_reset");
} else if (($_POST["RESET_ALL"] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall account_graphs_reset");
} else if (($_REQUEST["macguard_conf"] != "") && ($login)) {
    monitoring_set_macguard_conf($_REQUEST["macguard_conf"]);
}

// přečteme data z ip
exec("ip addr show", $NETWORK);
// načteme data z arp abychom mohli dané mac přiřadit aktivní ip
exec("arp -n", $ARP_DATA);
// načteme csv soubor pro macguarda
if (file_exists("/home/safe/macguard/table-".$dummy_ip.".csv")) $MACGUARD_DATA = file("/home/safe/macguard/table-".$dummy_ip.".csv");
// načteme data z iwconfigu
exec("iwconfig 2>/dev/null",$IWCONFIGS);
// načteme dat z lspci
exec("lspci",$LSPCIS);
// načteme dat z ipt_ACCOUNT
if (file_exists("/var/log/account/data.txt")) $ACCOUNTS_DATA = file("/var/log/account/data.txt");
// načteme data z mikrotiků o wifi
if (file_exists("/var/log/account/mikrotik_wifi.txt")) $MIKROTIK_WIFI_DATA = file("/var/log/account/mikrotik_wifi.txt");
// některé další věci poznáme z interfaces
if (file_exists("/etc/network/interfaces")) $INTERFACES = file("/etc/network/interfaces");
// získáme data o QoSu
if (file_exists("/etc/firewall/qos.conf")) $QOS_DATA = file("/etc/firewall/qos.conf");

if (file_exists("/etc/init.d/firewall")) exec("cat /etc/init.d/firewall",$FIREWALL);

// získáme seznam zařízení
$ADAPTERS = array_unique(array_merge(get_interfaces_all($INTERFACES),get_networking_all($NETWORK)));
// seřadíme seznam taky aby bylo první dummy, pak eth, ath a wlan
usort($ADAPTERS,"sort_adapters");
// název pro zobrazení všech zařízení
$ADAPTERS = array_merge($ADAPTERS,array($ADAPTER_ALL));

// zalozky rozhrani
?>
<div id="menu">
    <ul>
<?php
$ADAPTER = $_GET['actual_interface'];
$int_num = 0;
foreach ($ADAPTERS as $a) {
// menu zobrazime i v případě že adapter neexistuje
    if (($a != "dummy0") && (!eregi("ifb",$a))) {
	if (($int_num == 0) && ($ADAPTER == '')) $ADAPTER = $a;
?>	<li><a class="<?= $ADAPTER == $a ? 'active' : '' ?>" href="<?= change_url("actual_interface",$a) ?>"><?= $a ?></a></li>
<?php	$int_num++;
    }
}
?>
    </ul>
</div>

<br clear="all">
<form method="post" action="<?= $_SERVER['PHP_SELF']."?actual_interface=".$ADAPTER ?>">
<?php
$int_num = 0;
if (((file_exists("/sys/class/net/$ADAPTER")) && ($ADAPTER != "dummy0") && (!eregi("ifb",$ADAPTER))) || ($ADAPTER == $ADAPTER_ALL)) {
    // získáme informace o adapteru
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
?>
    <table class="bordered">
        <tr><?= show_card_properites($ADAPTER) ?></tr>
    </table>
    <br/>
    <table class="bordered hoovered">
        <tr>
            <th width="5%"><a href="<?= change_url("sort_by","status") ?>" class="sort_link">status</a></th>
            <th width="18%"><a href="<?= change_url("sort_by","name") ?>" class="sort_link">název počítače</a></th>
            <th width="11%"><a href="<?= change_url("sort_by","ip") ?>" class="sort_link">ip adresa</a></th>
            <th width="15%"><a href="<?= change_url("sort_by","mac") ?>" class="sort_link">mac adresa</a></th>
            <th width="9%"><a href="<?= change_url("sort_by","upload") ?>" class="sort_link">odesláno</a></th>
            <th width="9%"><a href="<?= change_url("sort_by","upload_rate") ?>" class="sort_link">rychlost</a></th>
            <th width="9%"><a href="<?= change_url("sort_by","download") ?>" class="sort_link">přijato</a></th>
            <th width="9%"><a href="<?= change_url("sort_by","download_rate") ?>" class="sort_link">rychlost</a></th>
            <th width="9%"><a href="<?= change_url("sort_by","qos") ?>" class="sort_link">omezení</a></th>
            <th width="6%"><a href="<?= change_url("sort_by","signal") ?>" class="sort_link">signál</a></th>
        </tr>
<?php
    /* Informace pro dané rozhraní získáme z interfaces_data.txt */
    if (file_exists("/var/log/account/interfaces_data.txt")) {
        $ifaces_data = file("/var/log/account/interfaces_data.txt");
        foreach ($ifaces_data as $v) {
            $v = preg_split("/[ \t\n]+/",$v);
            if (($v[0] != $ADAPTER) && (($ADAPTER != $ADAPTER_ALL) || ($v[0] != "all"))) continue;
?>
        <tr class="monitoring_iface_stats">
            <td><span class="img_active">&nbsp;</span></td>
            <td align="left"><?= $hostname ?></td>
<?php
            if ($ADAPTER == $ADAPTER_ALL) {
?>
            <td><br/></td>
            <td><br/></td>
<?php
            } else {
?>
            <td align="left">
<?php
                foreach (get_interfaces_ip($INTERFACES,$ADAPTER) as $K => $ADAPTER_IP) {
?>
                <a href="graphs.php?interface=<?= $ADAPTER ?>"><?= $ADAPTER_IP[0] ?></a><br/>
<?php
                }
?>
            </td>
            <td><?= ((($monitoring["show_mac"]) || ($login)) ? $ADAPTER_MAC : "------------") ?></td>
<?php
            }
?>
            <td align="right"><?= convert_units($v[1],"bytes","M") ?></td>
            <td align="right"><?= convert_units($v[3],$monitoring["rate_units"],"k") ?>/s</td>
            <td align="right"><?= convert_units($v[2],"bytes","M") ?></td>
            <td align="right"><?= convert_units($v[4],$monitoring["rate_units"],"k") ?>/s</td>
<?php
            if ($ADAPTER == $ADAPTER_ALL) {
?>
            <td><br/></td>
<?php
            } else {
?>
            <td><?= ((get_firewall_qos($FIREWALL,$ADAPTER)) ? "zapnuto" : "vypnuto") ?></td>
<?php
            }
?>
            <td><br/></td>
        </tr>
<?php
            break;
        }
    }

    unset($CLIENTS);
    unset($MADWIFI_CLIENTS);
    if (file_exists("/proc/net/madwifi/".$ADAPTER)) {
        exec("wlanconfig $ADAPTER list", $MADWIFI_CLIENTS);
    }
    // zjistíme všechny klienty, z arp, z macguarda, z hostapu a madwifi
    // chceme pole ve kterém budeme mít strukturu
    /*  MAC = POVOLENA, AKTIVNI, OMEZENA, SIGNAL, IP_ADRESY()
        IP_ADRESA = POVOLENA, AKTIVNI, JMENO, UPLOAD, DOWNLOAD

        příklad:
            echo $CLIENTS2["00:0C:42:18:7B:23"][0]; - povolena
            echo $CLIENTS2["00:0C:42:18:7B:23"][4]["10.93.49.228"][4]; - download
    */
    $CLIENTS = monitoring_get_macs_info_all($ADAPTER, array_unique(array_merge(monitoring_get_macs_from_arp($ADAPTER),
        monitoring_get_macs_from_macguard($ADAPTER),
        monitoring_get_macs_from_hostap($ADAPTER),
        monitoring_get_macs_from_madwifi($ADAPTER))));
    // musíme přidat všechny další ip, u kterých neznáme mac adresu
    if ($ADAPTER == $ADAPTER_ALL) {
        $CLIENTS = array_merge($CLIENTS,monitoring_get_ips_info_all($ADAPTER,monitoring_get_ips_from_stats()));
    }
    ($_REQUEST['sort_by'] == "") ? $sort = "status" : $sort = $_REQUEST['sort_by'];
    usort($CLIENTS,"sort_by_".$sort);
    foreach ($CLIENTS as $OPTION) {
        /* tady začneme konečně vypisovat */
?>
        <tr>
<?php
        if (is_array($OPTION["ips"]) && (sizeof($OPTION["ips"]) > 0)) {
?>
            <td>
<?php
        foreach ($OPTION["ips"] as $OPTION_IP) {
            if (($OPTION_IP["enabled"]) && ($OPTION_IP["active"]))  {
                $pom_class = "active";
                $pom_type = "0";
            } else if ($OPTION_IP["enabled"]) {
                $pom_class = "enabled";
                $pom_type = "0";
            } else {
                $pom_class = "disabled";
                $pom_type = "1";
            }

            /* přidané nastavení macguarda */
            if (($OPTION_IP["macguard"]) == "1") {
                if ($OPTION_IP["active"]) {
                    $pom_class = "active";
                } else {
                    $pom_class = "enabled";
                }
                $pom_type = 2;
            } else if (($OPTION_IP["macguard"]) == "2") {
                $pom_class = "disabled";
                $pom_type = 3;
            }

            if ($login) {
?>
                <a class="img_<?= $pom_class ?>" href="<?= change_url("macguard_conf",$pom_type."_".(($OPTION["mac"] == "") ? "0" : $OPTION["mac"])."_".$OPTION_IP["ip"]) ?>">&nbsp;</a>
<?php
            } else {
?>
                <span class="img_<?= $pom_class ?>">&nbsp;</span>
<?php
            }
        }
?>
            </td>
            <td align="left">
<?php
        foreach ($OPTION["ips"] as $OPTION_IP) {
?>
                <?= $OPTION_IP["name"] ?><br/>
<?php
        }
?>
            </td>
            <td align="left">
<?php
        foreach ($OPTION["ips"] as $OPTION_IP) {
?>
                <span class="graphs_link_<?= (($OPTION_IP["enabled"]) && (($OPTION_IP["active"]))) ? 'green' : ( ($OPTION_IP["enabled"]) ? 'black' : 'red' ) ?>"><a href="graphs.php?ip=<?= $OPTION_IP["ip"] ?>"><?= $OPTION_IP["ip"] ?></a></span><br/>
<?php
        }
?>
            </td>
            <td><font color="<?= ($OPTION["enabled"] && ($OPTION["active"] || ($OPTION["signal"] != ""))) ? 'green' : ( ($OPTION["enabled"]) ? 'black' : 'red' ) ?>"><?= (($OPTION["mac"] != "")) ? ((($monitoring["show_mac"]) || ($login)) ? $OPTION["mac"] : "------------") : "<br/>" ?></font></td>
            <td align="right">
<?php
        foreach ($OPTION["ips"] as $OPTION_IP) {
?>
                <?= convert_units($OPTION_IP["upload"],"bytes","M") ?><br/>
<?php
        }
?>
            </td>
            <td align="right">
<?php
        foreach ($OPTION["ips"] as  $OPTION_IP) {
?>
                <?= convert_units($OPTION_IP["upload_rate"],$monitoring["rate_units"],"k") ?>/s<br/>
<?php
        }
?>
            </td>
            <td align="right">
<?php
        foreach ($OPTION["ips"] as  $OPTION_IP) {
?>
                <?= convert_units($OPTION_IP["download"],"bytes","M") ?><br/>
<?php
        }
?>
            </td>
            <td align="right">
<?php
        foreach ($OPTION["ips"] as  $OPTION_IP) {
?>
                <?= convert_units($OPTION_IP["download_rate"],$monitoring["rate_units"],"k") ?>/s<br/>
<?php
        }
?>
            </td>
            <td class="td_inset">
                <table class="inset_table">
<?php
        foreach ($OPTION["ips"] as $i => $OPTION_IP) {
?>
                    <tr>
                        <td class="td_50<?= ($OPTION_IP["qos"] ? " qos_class_".$OPTION_IP["qos"] : "") ?>"><?= ($login) ? '<a href="'.change_url("QOS_".$OPTION_IP["ip"],($OPTION_IP["qos"] ? 'povolit' : 'omezit')).'">ip</a>' : "ip" ?></td>
<?php
            if ($i == 0) {
                if  ($OPTION["mac"] != "") {
?>
                        <td rowspan="<?= sizeof($OPTION["ips"]) ?>" class="td_50<?= ($OPTION["qos"] ? " qos_class_".$OPTION["qos"] : "") ?>"><?= ($login) ? '<a href="'.change_url("QOS_".$OPTION["mac"],($OPTION["qos"] ? 'povolit' : 'omezit')).'">mac</a>' : "mac" ?></td>
<?php
                } else {
?>
                        <td rowspan="<?= sizeof($OPTION["ips"]) ?>" class="td_50"><br/></td>
<?php
                }
            }
?>
                    </tr>
<?php
        }
?>
                </table>
            </td>
<?php
        } else {
?>
            <td><span class="img_disabled">&nbsp;</span></td>
            <td><br/></td>
            <td><br/></td>
            <td><font color="<?= ($OPTION["enabled"] && ($OPTION["active"] || ($OPTION["signal"] != ""))) ? "green" : ( ($OPTION["enabled"]) ? "black" : "red" ) ?>"><?= (($OPTION["mac"] != "")) ? ((($monitoring["show_mac"]) || ($login)) ? $OPTION["mac"] : "------------") : "<br/>" ?></font></td>
            <td><br/></td>
            <td><br/></td>
            <td><br/></td>
            <td><br/></td>
            <td class="td_inset">
                <table class="inset_table">
                    <tr>
                        <td class="td_50"><br/></td>
                        <td class="td_50<?= ($OPTION["qos"] ? " qos_class_".$OPTION["qos"] : "") ?>"><?= ($login) ? '<a href="'.change_url("QOS_".$OPTION["mac"],($OPTION["qos"] ? "povolit" : "omezit")).'">mac</a>' : "mac" ?></td>
                    </tr>
                </table>
            </td>
<?php
        }
?>
            <td align="right">
<?php
            if ($OPTION["mac"] != "") {
                if (@file_exists("/var/log/account/rrd/signal-".str_replace(":","-",$OPTION["mac"]).".rrd")) {
?>
                <span class="graphs_link_black"><a href="graphs.php?signal=<?= rawurlencode((($monitoring["show_mac"]) || ($login)) ? $OPTION["mac"] : base64_encode($OPTION["mac"])) ?>"><?= (($OPTION["signal"] != "") ? $OPTION["signal"] : "-?? dB") ?></a></span>
<?php
                } else if ($OPTION["signal"] != "") {
?>
                <?= $OPTION["signal"] ?>
<?php
                } else {
?>
                <br/>
<?php
                }
            } else {
?>
                <br/>
<?php
            }
?>
            </td>
        </tr>
<?php
    }
?>
    </table>
<?php
}

if ($CLIENTS["0"]["mac"] != "") {
    if (is_array($ACCOUNTS_DATA)) {
        foreach($ACCOUNTS_DATA as $DATA) {
            if (preg_match('/#/',$DATA)) {
                $DATA = preg_split("/[\ #]+/",$DATA);
                $pom[0] = $DATA[2];
                $pom[1] = $DATA[3];
            } else {
                break;
            }
        }
    }
?>
    <br/>
    <table class="bordered">
        <tr>
            <td align="left" width="25%">statistiky přenesených dat od:</td>
            <td align="center" width="10%"><?= $pom[0] ?></td>
            <td align="center" width="10%"><?= $pom[1] ?></td>
            <td width="25%"><?= ($login) ? '<input type="submit" name="RESET_COUNTS" value="resetovat stažená data"/>' : "<br/>" ?></td>
            <td width="30%"><?= ($login) ? '<input type="submit" name="RESET_ALL" value="resetovat všechny grafy a data"/>' : "<br/>" ?></td>
        </tr>
    </table>
<?php
}
?>
    <input type="hidden" name="sort_by" value="<?= $_REQUEST["sort_by"] ?>"/>
</form>
<?php
include 'include/footer.php';
?>
