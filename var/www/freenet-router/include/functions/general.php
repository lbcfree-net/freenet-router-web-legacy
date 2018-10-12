<?php

// Switch off notices due to planty uninitialized variables in the original code
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);

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
session_start();
$user = $_SESSION['user'];
$login = $_SESSION['login'];

// prvotní přihlášení na hlavní stránce
if (isset($_POST['login'])){

    if (isset($_POST['jmeno']) && isset($_POST['heslo'])){

        $username = escapeshellcmd($_POST['jmeno']);
        $passwd = escapeshellcmd($_POST['heslo']);

        exec("logger Freenet Router login: $username");
        exec("sudo web-auth $username $passwd",$output,$loginResult);

        if ($loginResult === 0){

            $_SESSION['user'] = $username;
            $_SESSION['login'] = true;
            $user = $username;
            $login = true;
        }
    }
}

// odhlášení
if (isset($_POST['logout'])) {

    unset($_SESSION['user']);
    unset($_SESSION['login']);
    $login = false;
}

// soubor s konfigurací pro cizí sítě
if (file_exists("include/config.php")) {
    include "include/config.php";
}

// funkce pro vytvoření selectu
function create_selection($name, $select_name, $list, $selected, $units, $tooltip = '') {
?>
    <tr>
    <td align=left><?= $name ?>: </td>
    <td align="right" title="<?= $tooltip ?>">
<?php
    if ((!is_array($list)) && preg_match('/:/',$list)) {
	$pom = explode('[:]', $list);
    $list = [];
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
<?php
    $selected_pom = false;
    foreach ($list as $I => $option) {
        if ((preg_replace("[^0-9]","",$option) != "") && ((preg_replace("[^0-9]","",$selected) != "") || ($selected == "auto")) && ($units != "")) {
            if (preg_replace("[^0-9]","",$option) == preg_replace("[^0-9]","",$selected)) $selected_pom = true;
?>
            <option <?= ((preg_replace("[^0-9]","",$option) == preg_replace("[^0-9]","",$selected)) ? "selected=\"selected\"" : "") ?> value="<?= $option ?>"><?= $option ?><?= ((is_array($units)) ? " ".$units[$I] : (($units != "none") ? " ".$units : "" )) ?></option>
<?php
        } else {
            if ($option == $selected) $selected_pom = true;
?>
            <option <?= (($option == $selected) ? "selected=\"selected\"" : "") ?> value="<?= $option ?>"><?= $option ?><?= ((is_array($units)) ? " ".$units[$I] : "") ?></option>
<?php
        }
    }

    if ((!$selected_pom) && (preg_replace("/[\s]+/i","",$selected) != "")) {
?>
            <option selected="selected" value="<?= $selected ?>">! <?= $selected ?><?= ((($units != "") && ($units != "none") && (!is_array($units))) ? " ".$units : "") ?> !</option>
<?php
    }
?>
        </select>
    </td>
    </tr>
<?php
}

// Does the given IP belong to the subnet?
// 10.101.111.199 in 10.101.111.193/26 => true
function is_ip_from_subnet($ip, $subnet)
{
    list($network, $cidr) = explode('/', $subnet);
    
    if ((ip2long($ip) & ~((1 << (32 - $cidr)) - 1) ) == ip2long($network) - 1)
    {
        return true;
    }

    return false;
}

// 255.255.255.125 => 25
function netmask2CIDR($netmask)
{
    $bits = 0;
    $netmask = explode(".", $netmask);

    foreach($netmask as $octect)
    $bits += strlen(str_replace("0", "", decbin($octect)));

    return $bits;
}

// funkce na prohledání pole, hledá jen část stringu
function array_eregi_search($strin, $array) {
    if (is_array($array)) {
        foreach ($array as $array_element) {
            if (preg_match("/$string/i", $array_element)) {
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
<?php
}
function table_text_array($string_1,$string_2,$string_3,$length,$units,$string_4 = "") {
?>
    <tr>
    <td align="left"><?= $string_1 ?>: </td>
    <td align="right"><input title="<?= $string_4 ?>" type="text" name="<?= $string_2 ?>" value="<?= $string_3 ?>" <?= ($length != "") ? "size=\"".$length."\"" : "" ?>> <?= $units ?></td>
    </tr>
<?php
}
function table_button($string_1,$string_2) {
?>
    <tr>
    <td align="left"></td>
    <td align="right"><input type="submit" name="<?= $string_1 ?>" value="<?= $string_2 ?>"></td>
    </tr>
<?php
}
function table_button_action($string_1,$string_2,$string_3) {
?>
    <tr>
    <td align="left"></td>
    <td align="right"><input type="button" name="<?= $string_1 ?>" value="<?= $string_2 ?>" onclick="<?= $string_3 ?>"></td>
    </tr>
<?php
}
// funkce pro vytvoření odřádkování v tabulce
function table_line() {
?>
    <tr><td colspan="2"><hr></td></tr>
<?php
}
// funkce pro vytvoření odřádkování v tabulce
function table_head($name) {
?>
    <tr><th colspan="2"><?= $name ?></th></tr>
<?php
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
<?php
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
<?php
    } else if ($ethernet) {
?>
        <td width="9%">linka: <b><?= $ADAPTER_LINK ?></b></td>
        <td width="13%">rychlost: <b><?= $ADAPTER_RATE ?></b></td>
        <td width="12%">duplex: <b><?= $ADAPTER_DUPLEX ?></b></td>
        <td width="46%">model: <b><?= $ADAPTER_MODEL ?></b></td>
        <td width="10%">ovladač: <b><?= $ADAPTER_DRIVER ?></b></td>
<?php  }
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
    if (preg_match('/dummy/i',$a)) {
	$dummy_a = true;
    }
    if (preg_match('/dummy/i',$b)) {
	$dummy_b = true;
    }
    if (preg_match('/br/i',$a)) {
	$bridge_a = true;
    }
    if (preg_match('/br/i',$b)) {
	$bridge_b = true;
    }
    if (preg_match('/eth/i',$a)) {
	$eth_a = true;
    }
    if (preg_match('/eth/i',$b)) {
	$eth_b = true;
    }
    if (preg_match('/ath/i',$a)) {
	$ath_a = true;
    }
    if (preg_match('/ath/i',$b)) {
	$ath_b = true;
    }
    if (preg_match('/wlan/i',$a)) {
	$wlan_a = true;
    }
    if (preg_match('/wlan/i',$b)) {
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
<?php
    if ((!is_array($list)) && preg_match('/:/',$list)) {
	$pom = explode('[:]',$list);
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
<?php
    foreach ( $list as $I => $option ) {
	if ((preg_replace("[^0-9]","",$option) != "") && (preg_replace("[^0-9]","",$selected) != "")) {
	    if (preg_replace("[^0-9]","",$option) == preg_replace("[^0-9]","",$selected)) {
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
<?php
}
function change_password ($username,$password) {
    exec("sudo /usr/sbin/pwd_change ".$username." ".$password);
}

/* převod jednotek */
function convert_units($value,$unit,$limit,$prec = 1) {
    /* bity používají násobky 1000, zatímco byty používájí násobky 1024 */
    switch ($unit) {
        case "bits":
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
