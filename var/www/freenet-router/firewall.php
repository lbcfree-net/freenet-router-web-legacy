<?php
// načteme hlavičku a často používané funkce, create_selection, is_ip_from_subnet
include 'include/functions/general.php';
include 'include/functions/firewall.php';
// uložení firewallu
if (($_POST[save] == "uložit") && ($login)) {
    save_firewall($_POST["text"]);
}
// spuštění/restart firewallu
if (($_POST[start] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall restart");
}
// vypnutí firewallu
if (($_POST[stop] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall stop");
}
// vypnutí qosu
if (($_POST[qos_stop] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall qos_stop");
}
// zapnutí qosu
if (($_POST[qos_start] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall qos_start");
}
// zakázání p2p sítí
if (($_POST[p2p_stop] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall p2p_allow");
}
// zakázání p2p sítí
if (($_POST[p2p_start] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall p2p_deny");
}
// vypnutí macguarda
if (($_POST[macguard_stop] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall macguard_stop");
}
// zapnutí macguarda
if (($_POST[macguard_start] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall macguard_start");
}
include 'include/header.php';
?>
<form method=post action="<?=$_SERVER['PHP_SELF']?> ">
<table width="100%">
    <tr>
    <td width="18%">
    status firewallu:
<?php
$firewall_status = "neznámý";
if ((exec("sudo /sbin/iptables -L FORWARD -n | wc -l") <= 2) && (exec("sudo /sbin/iptables -L INPUT -n | wc -l") <= 2) && (exec("sudo /sbin/iptables -L OUTPUT -n | wc -l") <= 2)) {
    $firewall_status = "<font color=red>vypnutý</font>";
} else {
    $firewall_status = "<font color=green>zapnutý</font>";
}
echo $firewall_status;
?>
    </td>
<?php
if ($login) {
?>
    <td>možnosti firewallu:</td>
    <td><input type="submit" name="start" value="zapnout/restartovat"></td>
    <td><input type="submit" name="stop" value="vypnout"></td>
    <td>
<?
    if (exec("tc qdisc | grep -v priomap") == "") {
	echo '<input type="submit" name="qos_start" value="zapnout qos">';
    } else {
	echo '<input type="submit" name="qos_stop" value="vypnout qos">';
    }
?>
    </td>
    <td>
<?
    if (exec("sudo /sbin/iptables -L -n | grep \"l7proto bittorrent reject-with icmp-port-unreachable\"") == "") {
	echo '<input type="submit" name="p2p_start" value="zakázat p2p sítě">';
    } else {
	echo '<input type="submit" name="p2p_stop" value="povolit p2p sítě">';
    }
?>
    </td>
    <td>
<?
    if (exec("sudo /sbin/iptables -L -n | grep valid_mac_fwd") == "") {
	echo '<input type="submit" name="macguard_start" value="zapnout macguarda">';
    } else {
	echo '<input type="submit" name="macguard_stop" value="vypnout macguarda">';
    }
?>
    </td>
<?
} else {
?>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
<?
}
?>
    </tr>
</table>
<hr>
</form>
<form method=post action="<?=$_SERVER['PHP_SELF']?> ">
<?
if ($login) {
    echo '<textarea cols="130" rows="50" tabindex="2" name="text" wrap=off>';
} else {
    echo '<textarea cols="130" rows="50" tabindex="2" name="text" wrap=off disabled>';
}
if(($firewall = fopen("/etc/init.d/firewall","r")))
{
    while (! feof($firewall)) {
        echo fgets($firewall, 1000);
    }
    fclose($firewall);
}
echo '</textarea>';
echo '<br>';
if ($login) {
    echo '<input type="submit" name="save" value="uložit">';
}
?>
</form>
<?php
include 'include/footer.php';
?>
