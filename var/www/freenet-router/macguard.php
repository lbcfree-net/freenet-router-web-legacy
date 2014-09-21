<?php
// načteme hlavičku a často používané funkce, create_selection, is_ip_from_subnet
include 'include/functions/general.php';

// uložení firewallu
if (($_POST["save"] == "uložit") && ($login)) {
    if(($firewall = fopen("/tmp/table-$dummy_ip.csv","w")))
    {
        fwrite($firewall,stripslashes(str_replace("\r","",$_POST['text'])));
        fclose($firewall);
        exec("sudo /bin/cp /tmp/table-$dummy_ip.csv /home/safe/macguard/table-$dummy_ip.csv");
        exec("sudo /bin/chown safe:safe /home/safe/macguard/table-$dummy_ip.csv");
    }
}

// vypnutí macguarda
if (($_POST["macguard_stop"] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall macguard_stop");
}

// zapnutí macguarda
if (($_POST["macguard_start"] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall macguard_start");
}

// zapnutí macguarda
if (($_POST["macguard_update"] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall macguard_update");
}

// zapnutí macguarda
if (($_POST["macguard_update_force"] != "") && ($login)) {
    exec("sudo /etc/init.d/firewall macguard_update force");
}

include "include/header.php";

$macguard_status = "neznámý";
if (exec("sudo /sbin/iptables -L -n | grep valid_mac_fwd") == "") {
    $macguard_status = "<font color=red>vypnutý</font>";
} else {
    $macguard_status = "<font color=green>zapnutý</font>";
}

$macguard_last_update = "neznámá";
if (file_exists("/home/safe/macguard/table-$dummy_ip.csv")) {
    $macguard_last_update = date("H:i d.m.Y",filemtime("/home/safe/macguard/table-$dummy_ip.csv"));
}

?>
<form method=post action="<?=$_SERVER['PHP_SELF']?>">
<table width="100%">
    <tr>
        <td width="20%">status macguarda: <?= $macguard_status ?></td>
        <td width="26%">poslední změna dat: <?= $macguard_last_update ?></td>
<?
if ($login) {
?>
        <td>možnosti macguarda:</td>
        <td>
<?
if (exec("sudo /sbin/iptables -L -n | grep valid_mac_fwd") == "") {
    echo '<input type="submit" name="macguard_start" value="zapnout">';
} else {
    echo '<input type="submit" name="macguard_stop" value="vypnout">';
}
?>
        </td>
        <td><input type="submit" name="macguard_update" value="aktualizovat"></td>
        <td><input type="submit" name="macguard_update_force" value="vynucená aktualizace"></td>
<?
} else {
?>
        <td></td>
<?
}
?>
    </tr>
</table>
<hr>
</form>
<form method=post action="<?=$_SERVER['PHP_SELF']?> ">
    <textarea cols="130" rows="50" tabindex="2" name="text" wrap="off" readonly>
<?
if (($login) || ($monitoring["show_mac"])) {
    if(($firewall = fopen("/home/safe/macguard/table-$dummy_ip.csv","r"))) 
    {       
        while (! feof($firewall)) 
        {
            echo fgets($firewall, 1000);
        }
        fclose($firewall);
    }
}
?>
    </textarea>
}
?>
</form>
<?
include "include/footer.php";
?>
