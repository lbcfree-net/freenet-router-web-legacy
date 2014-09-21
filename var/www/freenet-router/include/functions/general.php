<?php
// automatické znovunačítání obsahu, hlavně pro operu
header("Cache-Control: no-cache, must-revalidate");
/* Nastavíme PATH */
putenv("PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin");
// zjistíme jméno serveru
$hostname = exec('hostname');
// zjistime ip adresu dummy rozhrani
$dummy_ip = exec("ip addr show dummy0 | grep inet | grep -v inet6 | awk '{print \$2}' | cut -d \"/\" -f1");
// jak je název všech adapterů
$ADAPTER_ALL = "vše";
// funkce přihlášení přes pam
$login = false;
$cookie_name = "cd_admin_". $hostname ."_name";
$cookie_pass = "cd_admin_". $hostname ."_pass";
$user = isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : null;
$password = isset($_COOKIE[$cookie_pass]) ? $_COOKIE[$cookie_pass] : null;
// prvotní přihlášení na hlavní stránce
if (isset($_POST['login'])) 
{
    if (isset($_POST['jmeno']) && isset($_POST['heslo'])) 
    {

	$username = escapeshellcmd($_POST['jmeno']);
	$passwd = escapeshellcmd($_POST['heslo']);
	
	exec('sudo web-auth '.$username.' '.$passwd,$output,$loginResult);

	//if (pam_auth($_POST['jmeno'], $_POST['heslo'], $error)) 
	if ($loginResult === 0)
        {
	    //setcookie($cookie_name,$_POST['jmeno'],time()+2592000);
	    //setcookie($cookie_pass,$_POST['heslo'],time()+2592000);
	    setcookie($cookie_name,$username,time()+2592000);
	    setcookie($cookie_pass,$passwd,time()+2592000);
	    $user = $_POST['jmeno'];
	    $login = true;
	}
        /*
	else
        {
            echo $error;
        }
	*/
    }
}
// nechceme se zkoušet přihlásit s prázdným jménem a heslem
if (($user != "") && ($password != "") && (!$login)) 
{
    //if (pam_auth($user, $password, $error)) 
    exec('sudo web-auth '.$user.' '.$password,$output,$loginResult);
    if ($loginResult === 0)
    {
        // obnovíme cookies
        setcookie($cookie_name,$user,time()+2592000);
        setcookie($cookie_pass,$password,time()+2592000);
        $login = true;
    } 
    else 
    {
	// pokud přihlášení neplatí, tak vymažeme cookies
	setcookie($cookie_name,'',time()-2592000);
	setcookie($cookie_pass,'',time()-2592000);
        //echo $error;
    }
}
// odhlášení
if ($_POST['logout'] != "") {
    setcookie($cookie_name,'',time()-2592000);
    setcookie($cookie_pass,'',time()-2592000);
    $login = false;
}

// soubor s konfigurací pro cizí sítě
if (file_exists("include/config.php")) {
    include "include/config.php";
}

// funkce pro vytvoření selectu
function create_selection($name ,$select_name, $list, $selected, $units) {
?>
    <tr>
    <td align=left><?= $name ?>: </td>
    <td align="right">
<?  
    if ((!is_array($list)) && ereg(":",$list)) {
	$pom = split("[:]",$list);
	unset($list);
	// krok
	if ($pom[2] != "") {
	    for ($i=$pom[0]; $i<=$pom[1]; $i=$i+$pom[2]) {
		$list[] = $i;
	    }
	} else {
	    for ($i=$pom[0]; $i<=$pom[1]; $i++) {
		$list[] = $i;
	    }
	}
    }
    // true považujeme za ano, false za ne
    if (is_bool($selected)) {
	if ($selected == true) {
	    $selected = "ano";
	} else if ($selected == false) {
	    $selected = "ne";
	}
    }
?>
        <select name="<?= $select_name ?>" size="1">
<?
    $selected_pom = false;
    foreach ($list as $I => $option) {
        if ((ereg_replace("[^0-9]","",$option) != "") && ((ereg_replace("[^0-9]","",$selected) != "") || ($selected == "auto")) && ($units != "")) {
            if (ereg_replace("[^0-9]","",$option) == ereg_replace("[^0-9]","",$selected)) $selected_pom = true;
?>
            <option <?= ((ereg_replace("[^0-9]","",$option) == ereg_replace("[^0-9]","",$selected)) ? "selected=\"selected\"" : "") ?> value="<?= $option ?>"><?= $option ?><?= ((is_array($units)) ? " ".$units[$I] : (($units != "none") ? " ".$units : "" )) ?></option>
<?
        } else {
            if ($option == $selected) $selected_pom = true;
?>
            <option <?= (($option == $selected) ? "selected=\"selected\"" : "") ?> value="<?= $option ?>"><?= $option ?><?= ((is_array($units)) ? " ".$units[$I] : "") ?></option>
<?
        }
    }

    if ((!$selected_pom) && (preg_replace("/[\s]+/i","",$selected) != "")) {
?>
            <option selected="selected" value="<?= $selected ?>">! <?= $selected ?><?= ((($units != "") && ($units != "none") && (!is_array($units))) ? " ".$units : "") ?> !</option>
<?
    }
?>
        </select>
    </td>
    </tr>
<?
}

// patri dana ip do subnetu - subnet je ve formatu a.b.c.d/y
function is_ip_from_subnet($ip, $subnet) {
	// prevod ip na cislo
	$ip = ip2long($ip);
	// rozseknuti subnetu na ip a pocet bitu masky
	list($subnet, $bits) = explode('/', $subnet);
	// prevod ip subnetu na cislo
	$subnet = ip2long($subnet);
	// prevod poctu bitu masky na bitovou masku
	$netmask = $bits == 0 ? 0 : (~0 << (32 - $bits));
	
	// vlastni kontrola - pro oboji se pocita adresa site a kdyz sedi tak je to o
	return 0 + (($ip & $netmask) == ($subnet & $netmask));
}

function netmask2CIDR($netmask) {
    $netmask = ip2long($netmask);
    $netbin = decbin($netmask);
    for($n = 0;$n < 32; $n++) if($netbin[$n]==0) break;
    return $n;
}

// funkce na prohledání pole, hledá jen část stringu
function array_eregi_search($string,$array) {
    if (is_array($array)) {
	foreach ($array as $array_element) {
	    if (eregi($string,$array_element)) {
		return true;
	    }
	}
    }
    return false;
}
// funkce pro vytvoření jednoduchého záznamu v tabulce
function table_entry($string_1,$string_2,$width_auto = false) {
?>
    <tr>
    <td align="left" <?= ($width_auto) ? '' : 'width="35%"' ?>><?= $string_1 ?>: </td>
    <td align="right" <?= ($width_auto) ? '' : 'width="65%"' ?>><?= $string_2 ?></td>
    </tr>
<?
}
function table_text_array($string_1,$string_2,$string_3,$length,$units,$string_4 = "") {
?>
    <tr>
    <td align="left"><?= $string_1 ?>: </td>
    <td align="right"><input title="<?= $string_4 ?>" type="text" name="<?= $string_2 ?>" value="<?= $string_3 ?>" <?= ($length != "") ? "size=\"".$length."\"" : "" ?>> <?= $units ?></td>
    </tr>
<?
}
function table_button($string_1,$string_2) {
?>
    <tr>
    <td align="left"></td>
    <td align="right"><input type="submit" name="<?= $string_1 ?>" value="<?= $string_2 ?>"></td>
    </tr>
<?
}
function table_button_action($string_1,$string_2,$string_3) {
?>
    <tr>
    <td align="left"></td>
    <td align="right"><input type="button" name="<?= $string_1 ?>" value="<?= $string_2 ?>" onclick="<?= $string_3 ?>"></td>
    </tr>
<?
}
// funkce pro vytvoření odřádkování v tabulce
function table_line() {
?>
    <tr><td colspan="2"><hr></td></tr>
<?
}
// funkce pro vytvoření odřádkování v tabulce
function table_head($name) {
?>
    <tr><th colspan="2"><?= $name ?></th></tr>
<?
}
function show_card_properites($ADAPTER) {
    global $wifi;
    global $ethernet;
    global $ADAPTER_LINK;
    global $ADAPTER_RATE;
    global $ADAPTER_DUPLEX;
    global $ADAPTER_AUTONEG;
    global $ADAPTER_MODEL;
    global $ADAPTER_DRIVER;
    global $ADAPTER_BRIDGE;
    global $ADAPTER_MODE;
    global $ADAPTER_ESSID;
    global $ADAPTER_BSSID;
    global $ADAPTER_CHANNEL;
    global $ADAPTER_FREQUENCY;
    global $ADAPTER_TXPOWER;
    global $ADAPTER_SIGNAL;
?>
    <td align="left" width="10%">adapter: <span class="graphs_link_black"><a href="/graphs.php?interface=<?= $ADAPTER ?>"><b><?= $ADAPTER ?></b></a></span></td>
<?
    if ($wifi) {
?>
        <td width="9%">režim: <b><?= $ADAPTER_MODE ?></b></td>
        <td width="22%">essid: <b><?= $ADAPTER_ESSID ?></b></td>
        <td width="17%">bssid: <b><?= $ADAPTER_BSSID ?></b></td>
        <td width="14%">frekvence: <b><?= $ADAPTER_FREQUENCY ?></b></td>
        <td width="28%">
            <table class="inset_table">
                <tr>
                    <td>kanál: <b><?= $ADAPTER_CHANNEL ?></b></td>
                    <td>rychlost: <b><?= $ADAPTER_RATE ?></b></td>
                    <td>výkon: <b><?= $ADAPTER_TXPOWER ?></b></td>
                </tr>
            </table>
        </td>
<?
    } else if ($ethernet) {
?>
        <td width="9%">linka: <b><?= $ADAPTER_LINK ?></b></td>
        <td width="13%">rychlost: <b><?= $ADAPTER_RATE ?></b></td>
        <td width="12%">duplex: <b><?= $ADAPTER_DUPLEX ?></b></td>
        <td width="46%">model: <b><?= $ADAPTER_MODEL ?></b></td>
        <td width="10%">ovladač: <b><?= $ADAPTER_DRIVER ?></b></td>
<?  }
}
// funkce pro získání dat ze souboru
function get_file_value($file) {
    if (($f = fopen($file,"r")))
    {        
        if (!feof($f)) {
            $value = fgets($f,1024);
        }
        fclose($f);
        return trim($value);
    }
    return false;
}
// pěkné seřazení adapterů
function sort_adapters($a,$b) {
    if (eregi("dummy",$a)) {
	$dummy_a = true;
    }
    if (eregi("dummy",$b)) {
	$dummy_b = true;
    }
    if (eregi("br",$a)) {
	$bridge_a = true;
    }
    if (eregi("br",$b)) {
	$bridge_b = true;
    }
    if (eregi("eth",$a)) {
	$eth_a = true;
    }
    if (eregi("eth",$b)) {
	$eth_b = true;
    }
    if (eregi("ath",$a)) {
	$ath_a = true;
    }
    if (eregi("ath",$b)) {
	$ath_b = true;
    }
    if (eregi("wlan",$a)) {
	$wlan_a = true;
    }
    if (eregi("wlan",$b)) {
	$wlan_b = true;
    }
    if (($dummy_a) && ($dummy_b)) {
	return strcmp($a,$b);
    } else if ($dummy_a) {
	return -1;
    } else if ($dummy_b) {
	return 1;
    } else if (($bridge_a) && ($bridge_b)) {
	return strcmp($a,$b);
    } else if ($bridge_a) {
	return -1;
    } else if ($bridge_b) {
	return 1;
    } else if (($eth_a) && ($eth_b)) {
	return strcmp($a,$b);
    } else if ($eth_a) {
	return -1;
    } else if ($eth_b) {
	return 1;
    } else if (($ath_a) && ($ath_b)) {
	return strcmp($a,$b);
    } else if ($ath_a) {
	return -1;
    } else if ($ath_b) {
	return 1;
    } else if (($wlan_a) && ($wlan_b)) {
	return strcmp($a,$b);
    } else if ($wlan_a) {
	return -1;
    } else if ($wlan_b) {
	return 1;
    }
}
function get_broadcast($IP,$MASK) {
    $IP = explode(".",$IP);
    $MASK = explode(".",$MASK);
    return (($IP[0] - ($IP[0] % (256 - $MASK[0]))) + (256 - $MASK[0]) - 1).".".(($IP[1] - ($IP[1] % (256 - $MASK[1]))) + (256 - $MASK[1]) - 1).".".(($IP[2] - ($IP[2] % (256 - $MASK[2]))) + (256 - $MASK[2]) - 1).".".(($IP[3] - ($IP[3] % (256 - $MASK[3]))) + (256 - $MASK[3]) - 1);
}
function convert_czech_to_english($string) {
    switch ($string) {
	case "ano":
	    return "yes";
	case "ne":
	    return "no";
	case "zastavit":
	    return "stop";
	case "spustit":
	    return "start";
    }
}
function get_network($IP,$MASK) {
    $IP = explode(".",$IP);
    $MASK = explode(".",$MASK);
    return ($IP[0] - ($IP[0] % (256 - $MASK[0]))).".".($IP[1] - ($IP[1] % (256 - $MASK[1]))).".".($IP[2] - ($IP[2] % (256 - $MASK[2]))).".".($IP[3] - ($IP[3] % (256 - $MASK[3])))."/".(32 - log((256 - $MASK[0]),2) - log((256 - $MASK[1]),2) - log((256 - $MASK[2]),2) - log((256 - $MASK[3]),2));
}
// funkce pro vytvoření selectu
function create_selection_service($name ,$select_name, $list, $selected, $active) {
    global $login;
?>
    <tr>
    <td align=left>
    <table width="100%">
    <tr>
    <td align="left" width="100"><?= $name ?>: </td>
    <td width="20"><img src="images/<?= ($active) ? "active" : "inactive" ?>.png" alt="<?= ($active) ? "zapnutý" : "vypnutý" ?>"/></td>
    <td><?= ($login) ? '<input type="submit" name="'.$select_name.'_BUTTON" value="'.(($active) ? "zastavit" : "spustit" ).'">' : "" ?></td>
    </tr>
    </table>
    </td>
    <td align=right>
<?
    if ((!is_array($list)) && ereg(":",$list)) {
	$pom = split("[:]",$list);
	unset($list);
	// krok
	if ($pom[2] != "") {
	    for ($i=$pom[0]; $i<=$pom[1]; $i=$i+$pom[2]) {
		$list[] = $i;
	    }
	} else {
	    for ($i=$pom[0]; $i<=$pom[1]; $i++) {
		$list[] = $i;
	    }
	}
    }
    // true považujeme za ano, false za ne
    if (is_bool($selected)) {
	if ($selected == true) {
	    $selected = "ano";
	} else if ($selected == false) {
	    $selected = "ne";
	}
    }
?>
    <select name="<?= $select_name ?>" size="1">
<?
    foreach ( $list as $I => $option ) {
	if ((ereg_replace("[^0-9]","",$option) != "") && (ereg_replace("[^0-9]","",$selected) != "")) {
	    if (ereg_replace("[^0-9]","",$option) == ereg_replace("[^0-9]","",$selected)) {
		echo "<option selected=\"selected\" value=\"$option\">$option\n";
	    } else {
		echo "<option value=\"$option\">$option\n";
	    }
	} else {
	    if ($option == $selected) {
		echo "<option selected=\"selected\" value=\"$option\">$option\n";
	    } else {
		echo "<option value=\"$option\">$option\n";
	    }
	}
    }
?>
    </select>
    </td>
    </tr>
<?
}
function change_password ($username,$password) {
    exec("sudo /usr/sbin/pwd_change ".$username." ".$password);
}

/* převod jednotek */
function convert_units($value,$unit,$limit,$prec = 1) {
    /* bity používají násobky 1000, zatímco byty používájí násobky 1024 */
    switch ($unit) {
        case "bites":
            $value = (8 * $value);
            $unit = "b";
            $base = 1000;
            break;
        case "bytes":
            $unit = "B";
            $base = 1024;
            break;
    }

    if (($value <= $base) && ($limit == "")) {
        return $value." ".$unit;
    } else if (($value <= pow($base,2)) && ($limit == "k")) {
        return round(($value / pow($base,1)),$prec)." k".$unit;
    } else if (($value <= pow($base,3)) && (($limit == "M") || ($limit == "k"))) {
        return round(($value / pow($base,2)),$prec)." M".$unit;
    } else if (($value <= pow($base,4)) && (($limit == "M") || ($limit == "k") || ($limit == "G"))) {
        return round(($value / pow($base,3)),$prec)." G".$unit;
    } else {
        return round(($value / pow($base,4)),$prec)." T".$unit;
    }
}
?>
