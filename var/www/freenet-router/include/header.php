<?php
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
    <head>
<?php
if ($_SERVER['PHP_SELF'] == "/dalsi.php") {
?>
<script language="JavaScript" type="text/javascript">
<!--
function doplnit_nulu(what){
    var output=(what.toString().length==1)? "0"+what : what;
    return output;
}
var serverdate = new Date('<?=date("F d, Y H:i:s", time())?>');
function zobrazit_datum_a_cas(){
    serverdate.setSeconds(serverdate.getSeconds()+1);
    var datestring=doplnit_nulu(serverdate.getDate())+"."+doplnit_nulu(serverdate.getMonth())+"."+serverdate.getFullYear();
    var timestring=doplnit_nulu(serverdate.getHours())+":"+doplnit_nulu(serverdate.getMinutes())+":"+doplnit_nulu(serverdate.getSeconds());
    document.getElementById("datum_a_cas").innerHTML=timestring+" "+datestring;
    window.setTimeout("zobrazit_datum_a_cas()",1000);
}
var serveruptime = '<?=exec("cat /proc/uptime | awk '{print $1}'")?>';
var a = 0;
var days_str = "den";
function zobrazit_uptime(){
    a = a + 1;
    seconds = parseInt((parseInt(serveruptime) + a) % 60);
    minutes = parseInt((parseInt(serveruptime) + a) / 60 % 60);
    hours = parseInt((parseInt(serveruptime) + a) / 3600 % 24);
    days = parseInt((parseInt(serveruptime) + a) / 86400);
    if (days > 1) {
        days_str = "dny";
    }
    if (days > 5) {
        days_str = "dnů";
    }
    document.getElementById("uptime").innerHTML=+days+" "+days_str+" "+doplnit_nulu(hours)+":"+doplnit_nulu(minutes)+":"+doplnit_nulu(seconds);
    window.setTimeout("zobrazit_uptime()",1000);
}
//-->
</script>
<?php
} else if (($_SERVER['PHP_SELF'] == "/network.php") || ($_SERVER['PHP_SELF'] == "/index.php") || ($_SERVER['PHP_SELF'] == "/monitoring.php")) {
?>
<script language="JavaScript" type="text/javascript">
<!--
var actual_interface = 0;
function get_variable(name,description){
    variable = prompt("Zadejte " + description + ":");
    window.location=("?" + name + "=" + variable);
}
//-->
function set(num) {
    actual_interface = num;
    for(n = 0; n < 50; n++) {
        if (document.getElementById('menu' + n) != null) {
            document.getElementById('interface' + n).style.display = (n == num) ? 'block' : 'none';
            document.getElementById('menu' + n).className = (n == num) ? 'active' : '';
            document.networkform.action = '<?= $_SERVER['PHP_SELF'] ?>?actual_interface=' + actual_interface;
        }
    }
}
</script>
<?php
}
if ($_SERVER['PHP_SELF'] == "/monitoring.php") {
?>
<script language="JavaScript" type="text/javascript">
<!--
var reloadTimer = null;

window.onload = function() {
    setReloadTime(100); // Pass a default value of 5 seconds.
}

function setReloadTime(secs) {
    if (arguments.length == 1) {
        if (reloadTimer) {
            clearTimeout(reloadTimer);
        }
        reloadTimer = setTimeout("setReloadTime()", Math.ceil(parseFloat(secs) * 1000));
    } else {
        window.location.replace(window.location.href);
    }
}
//-->
</script>
<?php
}
?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="content-language" content="cs">
	<title><?= $header["routername"]." ".$hostname ?> - Freenet router 3.1</title>
	<link href="styles/style.css" rel="stylesheet" type="text/css">
    </head>
    <body id="page_bg" <?= ($_SERVER['PHP_SELF'] == "/dalsi.php") ? 'onload="zobrazit_datum_a_cas();zobrazit_uptime();"' : "" ?>>
	<div id="center">
	    <div id="main_bg">
	    <div id="header">
	    <div class="middle">
	    <div class="right">
	      <div class="pad"></div>
	      <div class="menu">
		<div class="firstseparator"></div>
<?php
	$menu = array(
			array('title' => 'další', 'url' => '/dalsi.php'),
			array('title' => 'logy', 'url' => '/logs.php'),
			array('title' => 'quagga', 'url' => '/quagga.php'),
			array('title' => 'macguard', 'url' => '/macguard.php'),
			array('title' => 'firewall', 'url' => '/firewall.php'),
			array('title' => 'monitoring', 'url' => '/monitoring.php'),
			array('title' => 'nastavení sítě', 'url' => '/network.php')
		);
	
	foreach($menu as $item) {
		$title = $item['title'];
		$url = $item['url'];
?>
		<div class="menuitem">
		    <div class="separator"></div>
		    <div class="contents"><a href="<?= $url ?>"><?= $title ?></a></div>
		</div>
<?php } ?>
	
	      </div>
	    </div>
	    <div class="left">
	      <div class="pad"></div>
	      <div class="menu">
		<div class="lastseparator"></div>
		<div class="menuitem home">
		    <div class="contents"><a href="/">home</a></div>
	
		    <div class="separator"></div>
		</div>
	      </div>
	    </div>
	    </div><a name="begin"></a><br style="clear: both;" />
	  </div>
          <div id="text">
            <div id="head">
<?php
switch ($_SERVER['PHP_SELF']) {
    case "/dalsi.php":
?>
		další informace
<?php
	break;
    case "/quagga.php":
?>
		quagga - dynamické routování
<?php
	break;
    case "/macguard.php":
?>
		úprava a nastavení macguarda
<?php
	break;
    case "/firewall.php":
?>
		úprava a nastavení firewallu
<?php
	break;
    case "/monitoring.php":
?>
		monitorování členů
<?php
	break;
    case "/logs.php":
?>
		zobrazení logů
<?php
	break;
    case "/network.php":
?>
		nastavení síťových karet
<?php
	break;
    case "/index.php":
?>
		rozhraní pro správu Freenet routeru
<?php
	break;
    case "/graphs.php":
	if ($_GET["ip"] != "") {
?>
		grafy pro ip <?= $_GET["ip"] ?>
<?php
	} else if ($_GET["cpu"] != "") {
?>
		grafy zatížení pro cpu <?= $_GET["cpu"] ?>
<?php
	} else if ($_GET["memory"] != "") {
?>
		grafy využití operační paměti
<?php
	} else if ($_GET["drive"] != "") {
?>
		grafy obsazení disku <?= $_GET["drive"] ?>
<?php
	} else if ($_GET["users"] != "") {
?>
		grafy připojených počítačů
<?php
	} else if ($_GET["interface"] != "") {
?>
		grafy pro rozhraní <?= $_GET["interface"] ?>
<?php
	} else if ($_GET["ping"] != "") {
?>
		grafy pro ip <?= $_GET["ping"] ?>
<?php
	}
	break;
}
?>
            </div>
