<?php
// načteme hlavičku a často používané funkce, create_selection, is_ip_from_subnet
include 'include/functions/general.php';
// uložení ospfd
if (($_POST[save_ospfd] == "uložit") && ($login)) {
    if(($soubor = fopen("/tmp/ospfd.conf","w")))
    {
        fwrite($soubor,stripslashes(str_replace("\r","",$_POST['text'])));
        fclose($soubor);
        exec("sudo /bin/cp /tmp/ospfd.conf /etc/quagga/ospfd.conf");
    }
}
// uložení zebra
if (($_POST[save_zebra] == "uložit") && ($login)) {
    if(($soubor = fopen("/tmp/zebra.conf","w")))
    {
        fwrite($soubor,stripslashes(str_replace("\r","",$_POST['text'])));
        fclose($soubor);
        exec("sudo /bin/cp /tmp/zebra.conf /etc/quagga/zebra.conf");
    }        
}
// vypnutí quaggy
if (($_POST[quagga_stop] != "") && ($login)) {
    exec("sudo /etc/init.d/quagga stop");
}
// zapnutí quaggy
if (($_POST[quagga_start] != "") && ($login)) {
    exec("sudo /etc/init.d/quagga start");
}
// restartování quaggy
if (($_POST[quagga_restart] != "") && ($login)) {
    exec("sudo /etc/init.d/quagga restart");
}
// editování souborů quaggy
$ospfd_edit = false;
$zebra_edit = false;
if (($_POST[quagga_ospfd] != "") && ($login)) {
    $ospfd_edit = true;
} else if (($_POST[quagga_zebra] != "") && ($login)) {
    $zebra_edit = true;
}
include 'include/header.php';
?>
<form method="post" action="<?=$_SERVER['PHP_SELF']?> ">
<table width=100%>
    <tr>
    <td width="20%">status quaggy: <font color=<?= (exec("ps ax | grep -v grep | grep quagga") == "") ? '"red">vypnutá' : '"green">zapnutá' ?></font></td>
<?php
if ($login) {
?>
    <td>možnosti quaggy:</td>
    <td><input type="submit" name="quagga_<?= (exec("ps ax | grep -v grep | grep quagga") == "") ? 'start" value="zapnout' : 'stop" value="vypnout' ?>"></td>
    <td><input type="submit" name="quagga_restart" value="restartovat"></td>
    <td><input type="submit" name="<?= ($ospfd_edit) ? 'clear" value="zobrazit routy' : 'quagga_ospfd" value="editovat ospfd.conf' ?>"></td>
    <td><input type="submit" name="<?= ($zebra_edit) ? 'clear" value="zobrazit routy' : 'quagga_zebra" value="editovat zebra.conf' ?>"></td>
<?php
} else {
?>
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
</form>
<form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
<?php
if ($ospfd_edit) {
?>
    <textarea cols="130" rows="50" tabindex="2" name="text" wrap="off">
<?php
    if(($file = fopen("/etc/quagga/ospfd.conf","r"))) {
	while (!feof($file)) {
    	    echo fgets($file, 1000);
	}
	fclose($file);
    }
?>
    </textarea>
    <br>
    <input type="submit" name="save_ospfd" value="uložit">
    <input type="hidden" name="quagga_ospfd" value="neco">
<?php
} else if ($zebra_edit) {
?>
    <textarea cols="130" rows="50" tabindex="2" name="text" wrap="off">
<?php
    if(($file = fopen("/etc/quagga/zebra.conf","r"))) {	
	while (!feof($file)) {
    	    echo fgets($file, 1000);
	}
	fclose($file);
    }
?>
    </textarea>
    <br>
    <input type="submit" name="save_zebra" value="uložit">
    <input type="hidden" name="quagga_zebra" value="neco">
<?php
} else {
?>
    <table align="center" width="98%">
<?php
    if ((($_POST['network'] != "") || ($_POST['gateway'] != "") || ($_POST['device'] != "") || ($_POST['type'] != "") || ($_POST['cost'] != "")) && ($_POST['clear'] == "")) {
?>
	<tr>
	<td colspan="5">
	<input type="submit" name="clear" value="zobrazit vše">
	</td>
	</tr>
<?php
    }
?>
    <tr>
    <th width=30%>síť</th>
    <th width=20%>brána</th>
    <th width=20%>rozhraní</th>
    <th width=10%>typ</th>
    <th width=10%>cost</th>
    </tr>
<?php
    exec("ip ro",$routes);
    foreach($routes as $I => $route) {
	$route = preg_split("/[\ ]+/", $route);
	// pokud je více cest se stejnou váhou
	if (eregi("nexthop",$route[0])) {
	    $route[0] = $network_prev;
	    $route[6] = $proto_prev;
	    $route[8] = $metric_prev;
	} else if ($route[1] == proto) {
	    $network_prev = $route[0];
	    $proto_prev = $route[2];
	    $metric_prev = $route[4];
	    continue;
	}
	// pokud se jedná o rozsah na lokálním rozhraní
	if ($route[1] == dev) {
	    $route[4] = $route[2];
	    $route[2] = $route[8];
	    $route[6] = "local";
	    $route[8] = 0;
	} else {
	    $route[6] = "quagga";
	}
	// zobrazíme data podle výběru
	if (((($_POST['network'] == $route[0]) || ($_POST['network'] == "")) && (($_POST['gateway'] == $route[2]) || ($_POST['gateway'] == "")) && (($_POST['device'] == $route[4]) || ($_POST['device'] == "")) && (($_POST['type'] == $route[6]) || ($_POST['type'] == "")) && (($_POST['cost'] == $route[8]) || ($_POST['cost'] == ""))) || ($_POST['clear'] != "")) {
	    // chceme postupný výběr
	    if (($_POST['network'] != "") && ($_POST['clear'] == "")) {
		echo '<input type="hidden" name="network" value="'.$route[0].'" class="submitlink">';
	    }
	    if (($_POST['gateway'] != "") && ($_POST['clear'] == "")) {
		echo '<input type="hidden" name="gateway" value="'.$route[2].'" class="submitlink">';
	    }
	    if (($_POST['device'] != "") && ($_POST['clear'] == "")) {
		echo '<input type="hidden" name="device" value="'.$route[4].'" class="submitlink">';
	    }
	    if (($_POST['type'] != "") && ($_POST['clear'] == "")) {
		echo '<input type="hidden" name="type" value="'.$route[6].'" class="submitlink">';
	    }
	    if (($_POST['cost'] != "") && ($_POST['clear'] == "")) {
		echo '<input type="hidden" name="cost" value="'.$route[8].'" class="submitlink">';
	    }
?>
	    <tr>
	    <td align=left><input type="submit" name="network" value="<?= $route[0] ?>" class="submitlink2"></td>
	    <td align=left><input type="submit" name="gateway" value="<?= $route[2] ?>" class="submitlink2"></td>
	    <td align=right><input type="submit" name="device" value="<?= $route[4] ?>" class="submitlink2"></td>
	    <td><input type="submit" name="type" value="<?= $route[6] ?>" class="submitlink2"></td>
	    <td align=right><input type="submit" name="cost" value="<?= $route[8] ?>" class="submitlink2"></td>
	    </tr>
<?php
	}
    }
?>
    </table>
<?php
}
?>
</form>
<?php
include 'include/footer.php';
?>
