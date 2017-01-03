<?php
function get_networking_all($NETWORK) {
    $array = array();
    foreach ($NETWORK as $LINE) {
        $LINE = preg_split("/[\ :@]+/", $LINE);
	if (($LINE[0] != "") && (is_numeric($LINE[0]))) {
	    if ((get_adapter_settings_is_adapter($LINE[1])) || (get_adapter_settings_is_bridge($LINE[1])) || (get_adapter_settings_is_dummy($LINE[1])) || (get_adapter_settings_is_vlan($LINE[1]))) {
		$array[] = $LINE[1];
	    }
	}
    }
    return $array;
}
function get_networking_adapter_exists($ADAPTER) {
    if (file_exists("/sys/class/net/".$ADAPTER)) {
	return true;
    }
    return false;
}
function get_networking_adapter_ip($NETWORK,$ADAPTER) {
    $I = "0";
    $array = array();
    $last_iface="";
    foreach ($NETWORK as $LINE_NOT_SPLIT) {
        $LINE = preg_split("/[\ :@]+/", $LINE_NOT_SPLIT);
        if($LINE[0]!="")
            $last_iface=$LINE[1];
        // ip adresa rozhraní
        if (($ADAPTER == $LINE[7]) && ("inet" == $LINE[1])) {
	    $LINE = explode("/", $LINE[2]);
	    $array[$I][0] = $LINE[0];
	    // přepočet na normální masku
	    if ($LINE[1] >= 24) {
		$array[$I][1] = "255.255.255.".(256 - (1 << (32 - $LINE[1])));
	    } else if ($LINE[1] >= 16) {
		$array[$I][1] = "255.255.".(256 - (1 << (24 - $LINE[1]))).".0";
	    } else if ($LINE[1] >= 8) {
		$array[$I][1] = "255.".(256 - (1 << (16 - $LINE[1]))).".0";
	    } else if ($LINE[1] >= 0) {
		$array[$I][1] = "".(256 - (1 << (8 - $LINE[1])))."0.0.0";
	    }
	    $I++;
        }else if(("inet6" == $LINE[1]) && ($ADAPTER==$last_iface)){
            $LINE_SPLIT2=  explode("inet6", $LINE_NOT_SPLIT);
            if(strpos($LINE_SPLIT2[1], "fe80") !== 0){
                $addr=preg_split("/[\ \/]+/", $LINE_SPLIT2[1]);
                    $array[$I][0]=$addr[1];
                    $array[$I][1]=$addr[2];
                    $I++;
            }
        }
    }
    return $array;
}
function get_networking_adapter_vlan($NETWORK,$ADAPTER) {
    $array = array();
    if ((get_adapter_settings_is_adapter($ADAPTER)) || (get_adapter_settings_is_bridge($ADAPTER))) {
	foreach ($NETWORK as $LINE) {
    	    $LINE = preg_split("/[\ \.@]+/", $LINE);
	    if (($LINE[1] == $ADAPTER) && (is_numeric($LINE[2]))) {
		$array[] = $LINE[2];
	    }
	}
    }
    if (sizeof($array) > 0) {
	return implode(",", $array);
    }
    return "ne";
}
function get_network_adapter_active($NETWORK,$ADAPTER) {
    foreach ($NETWORK as $I => $LINE) {
        $LINE = preg_split("/[\ :@]+/", $LINE);
        if ($ADAPTER == $LINE[1]) {
            if (preg_match('/UP/',$NETWORK[$I])) {
                return "ano";
            }
        }
    }
    if (get_networking_adapter_exists($ADAPTER)) {
        return "ne";
    }
    return "<font color=red>zařízení nebylo nalezeno</font>";
}
function get_network_adapter_mac($NETWORK,$ADAPTER) {
    foreach ($NETWORK as $I => $LINE) {
        $LINE = preg_split("/[\ :@]+/", $LINE);
        if ($ADAPTER == $LINE[1]) {
            if (preg_match('/link\/ether/i',$NETWORK[($I+1)])) {
                $pom = preg_split("/[\ @]+/", $NETWORK[($I+1)]);
                return strtoupper($pom[2]);
            }
        }
    }
    return "neznámá";
}
?>
