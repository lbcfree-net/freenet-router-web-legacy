<?php
// načteme hlavičku a často používané funkce, create_selection, is_ip_from_subnet
include 'include/functions/general.php';
include 'include/functions/firewall.php';
// uložení firewallu
if (($_POST[save] == "uložit firewall.conf") && ($login)) {
    save_firewall($_POST["text"]);
}
// uložení qos
if (($_POST[qos_save] == "uložit qos.conf") && ($login)) {
    save_qos($_POST["text"]);
}
// test qos
if (($_POST[qos_test] == "uložit a testovat qos.conf") && ($login)) {
    save_qos($_POST["text"]);
    exec("sudo /etc/init.d/firewall qos_test 2>&1", $cmd_output, $cmd_retcode);
}
// spuštění/restart firewallu
if (($_POST[start] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall restart 2>&1", $cmd_output, $cmd_retcode);
}
// vypnutí firewallu
if (($_POST[stop] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall stop 2>&1", $cmd_output, $cmd_retcode);
}
// vypnutí qosu
if (($_POST[qos_stop] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall qos_stop 2>&1", $cmd_output, $cmd_retcode);
}
// zapnutí qosu
if (($_POST[qos_start] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall qos_start 2>&1", $cmd_output, $cmd_retcode);
}
// vypnutí macguarda
if (($_POST[macguard_stop] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall macguard_stop 2>&1", $cmd_output, $cmd_retcode);
}
// zapnutí macguarda
if (($_POST[macguard_start] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall macguard_start 2>&1", $cmd_output, $cmd_retcode);
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
<?php
    if (exec("tc qdisc | grep -E \"^qdisc \S+ 101:\"") == "") {
        echo '<input type="submit" name="qos_start" value="zapnout qos">';
    } else {
        echo '<input type="submit" name="qos_stop" value="vypnout qos">';
    }
?>
    </td>
    <td>
<?php
    if (exec("sudo /sbin/iptables -L -n | grep valid_mac_fwd") == "") {
        echo '<input type="submit" name="macguard_start" value="zapnout macguarda">';
    } else {
        echo '<input type="submit" name="macguard_stop" value="vypnout macguarda">';
    }
?>
    </td>
<?php
} else {
?>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
<?php
}
?>
    </tr>
</table>
<hr>
<?php
if ($cmd_output) {
	echo '<br>';
	echo '<table class=bordered><tr><th>Command output</th></tr></table>';
	echo '<br>';
	$rows = min(max(3, count($cmd_output) + 5) , 30);
    echo "<textarea cols=\"130\" rows=\"$rows\" name=\"text\" wrap=\"on\" spellcheck=\"false\" readonly=\"true\">";
    echo implode("\n", $cmd_output);
	echo "\n\n";
	echo "Return code: $cmd_retcode";
    echo '</textarea>';
}
?>
</form>
<table class=bordered><tr><th>firewall.conf</th></tr></table>
<br>
<form method=post action="<?=$_SERVER['PHP_SELF']?> ">
<?php
if ($login) {
    echo '<textarea cols="130" rows="30" tabindex="2" name="text" wrap="off" spellcheck="false">';
} else {
    echo '<textarea cols="130" rows="30" tabindex="2" name="text" wrap="off" spellcheck="false" disabled>';
}
if(($firewall = fopen('/etc/firewall/firewall.conf', 'r')))
{
    while (! feof($firewall)) {
        echo fgets($firewall, 1000);
    }
    fclose($firewall);
}
echo '</textarea>';
echo '<br><br>';
if ($login) {
    echo '<input type="submit" name="save" value="uložit firewall.conf">';
}
?>
</form>
<br>
<table class=bordered><tr><th>qos.conf</th></tr></table>
<br>
<form method=post action="<?=$_SERVER['PHP_SELF']?> ">
<?php
if ($login) {
    echo '<textarea cols="130" rows="30" tabindex="3" name="text" wrap="off" spellcheck="false">';
} else {
    echo '<textarea cols="130" rows="30" tabindex="3" name="text" wrap="off" spellcheck="false" disabled>';
}
if(($qos = fopen('/etc/firewall/qos.conf', 'r')))
{
    while (! feof($qos)) {
        echo fgets($qos, 1000);
    }
    fclose($qos);
}
echo '</textarea>';
echo '<br><br>';
if ($login) {
    echo '<input style="margin-right: 0.5em" type="submit" name="qos_save" value="uložit qos.conf">';
    echo '<input style="margin-left: 0.5em: 0.5em" type="submit" name="qos_test" value="uložit a testovat qos.conf">';
}
?>
</form>
<br>
<table class=bordered><tr><th>/etc/init.d/firewall qos_stats</th></tr></table>
<br>
<textarea cols="130" rows="30" name="text" wrap="off" spellcheck="false" readonly="true">
<?php
$qos_stats = shell_exec("sudo /etc/init.d/firewall qos_stats");
if ($qos_stats) {
    echo $qos_stats;
} else {
    echo "QoS vypnutý.";
}
echo '</textarea>';
echo '<br><br>';
?>
</form>
<?php
include 'include/footer.php';
?>
