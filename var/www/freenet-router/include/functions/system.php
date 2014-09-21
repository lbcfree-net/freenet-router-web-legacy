<?php
function system_get_cpu_model($CPUINFO) {
    foreach ($CPUINFO as $line) {
	if (ereg("model name",$line)) {
	    $line = split(":",$line);
	    $pom = preg_split("/[\ \t\n]+/",$line[1]);
	    return implode(" ",$pom);
	}
    }
    return "";
}
function system_get_cpu_temperature($SENSORS) {
    if(count($SENSORS)>2){
	$TEMPERATURE=explode(":        +",$SENSORS[2]);
	$TEMPERATURE=explode(" C",$TEMPERATURE[1]);
	$TEMPERATURE=$TEMPERATURE[0]."°C";
    }else{
	$TEMPERATURE="N/A";
    }
    return $TEMPERATURE;
}
function system_get_cpu_freq($CPUINFO) {
    foreach ($CPUINFO as $line) {
	if (ereg("cpu MHz",$line)) {
	    $line = split(":",$line);
	    $pom = preg_split("/[\ \t\n]+/",$line[1]);
	    return round($pom[1],0);
	}
    }
    return "";
}
function system_get_cpu_count($CPUINFO) {
    $pom = 0;
    foreach ($CPUINFO as $line) {
	if (ereg("processor",$line)) {
	    $pom++;
	}
    }
    return $pom;
}
function system_get_cpu_usage() {
    list(,$val) = preg_split("/[\ \t\n]+/",get_file_value("/var/log/account/cpu_load.txt"));
    return $val;
}
function system_get_memory($MEMINFO,$VALUE) {
    foreach ($MEMINFO as $line) {
	if (eregi($VALUE,$line)) {
	    $line = split(":",$line);
	    $pom = preg_split("/[\ \t\n]+/",$line[1]);
	    return round(($pom[1] / 1024),0);
	}
    }
    return 0;
}
function system_get_memory_total($MEMINFO) {
    return system_get_memory($MEMINFO,"MemTotal");
}
function system_get_memory_free($MEMINFO) {
    return system_get_memory($MEMINFO,"MemFree");
}
function system_get_swap_total($MEMINFO) {
    return system_get_memory($MEMINFO,"SwapTotal");
}
function system_get_swap_free($MEMINFO) {
    return system_get_memory($MEMINFO,"SwapFree");
}
function system_get_disk($DISKINFO,$VALUE,$TYPE) {
    foreach ($DISKINFO as $line) {
	$pom = preg_split("/[\ \t\n]+/",$line);
	if ($pom[5] == $VALUE) {
	    return round(($pom[$TYPE] / 1024),0);
	}
    }
    return 0;
}
function system_get_rootfs_total($DISKINFO) {
    return system_get_disk($DISKINFO,"/",1);
}
function system_get_rootfs_free($DISKINFO) {
    return system_get_disk($DISKINFO,"/",3);
}
function system_get_tmpfs_total($DISKINFO) {
    return system_get_disk($DISKINFO,"/var/tmpfs",1);
}
function system_get_tmpfs_free($DISKINFO) {
    return system_get_disk($DISKINFO,"/var/tmpfs",3);
}
function system_get_tmpfss_total($DISKINFO) {
    return system_get_disk($DISKINFO,"/var/tmpfs/small",1);
}
function system_get_tmpfss_free($DISKINFO) {
    return system_get_disk($DISKINFO,"/var/tmpfs/small",3);
}
function system_get_kernel_version() {
    $type = preg_split("/[\ \t\n]+/",exec("uname -v"));
    
    return exec("uname -r")." ".$type[1];
}
function system_get_os_version() {
    return get_file_value("/etc/issue.net");
}
function system_get_rootfs_status($MOUNTINFO) 
{
    if (!is_array($MOUNTINFO)) 
    {
	exec("mount",$MOUNTINFO);
    }
    
    $fs = '?';
    
    foreach ($MOUNTINFO as $line) 
    {
        $start = strpos($line, 'VOYAGE_FS');
        
        if($start !== false)
        {
            $start += strlen('VOYAGE_FS');
            $start = strpos($line, 'type', $start);
            
            if($start !== false)
            {
                $start += strlen('type');
                $end =  strpos($line, '(', $start);
                
                if($end !== false)
                {
                    $fs = substr($line, $start, $end - $start);                    
                }
            }
            
            break;
        }       	
    }
    
    return "souborový systém $fs ".(system_get_rootfs_status_ro($MOUNTINFO) ? "uzamčen proti zápisu" : "zápis povolen");
}
function system_get_rootfs_status_ro($MOUNTINFO) 
{
    if (!is_array($MOUNTINFO)) 
    {
	exec('mount',$MOUNTINFO);
    }
            
    foreach ($MOUNTINFO as $line) 
    {
        $pos = strpos($line, 'VOYAGE_FS');
        
        if($pos !== false)
        {
            return strpos($line, '(ro,') !== false;
        }       	
    }
    
    return false;
}

function system_get_ping_response($HOST) {
    $response = preg_split("/[\ \/\t\n]+/",exec("ping ".$HOST." -c 1 -s 1024 -q -v -W 1 | grep min"));
    return (is_numeric($response[6])) ? $response[6]." ms" : "no response";
}
function system_get_ping_response_from_defined_routers() {
    if (file_exists("/etc/firewall/routers.conf")) {
	$routers = file("/etc/firewall/routers.conf");
	foreach($routers as $router) {
	    if (!ereg("#", $router) && ereg("\.", $router)) {
		$router = preg_split("/[\"\t\n]+/",$router);
		system_table_entry_ping($router[0],$router[1],implode(" ",array_slice($router,2)),system_get_router_accessibility($router[0]));
	    }
	}
    }
}
function system_get_router_accessibility($router) {
    if (file_exists("/var/log/account/routers.txt")) {
	$file = file("/var/log/account/routers.txt");
	foreach($file as $line) {
	    if (!ereg("#", $line) && (ereg($router, $line))) {
		$line = preg_split("/[:]+/",$line);
		if ($line[0] == $router) {
		    return round((($line[1]/ ($line[1] + $line[2])) * 100),1)."%";
		}
	    }
	}
    }
    return "<br>";
}
function system_get_ping_response_from_all_dummy() {
    $array = array();
    exec("ip ro | grep -F \".0.\" | grep -v -F \"224.0.0.\"",$ips);
    foreach($ips as $ip) {
        $ip = split(" ",$ip);
        $array[] = $ip[0];
    }
    usort($array,"sort_by_ip");
    foreach($array as $ip) system_table_entry_ping($ip);
}

function system_table_entry_ping($string_1,$string_2 = "<br>",$string_3 = "<br>",$string_4 = "<br>") {
?>
    <tr>
    <td align="left"><a href="http://<?= $string_1 ?>" class="info_link"><?= $string_1 ?></a></td>
    <td align="left"><?= $string_2 ?></td>
    <td align="left"><?= $string_3 ?></td>
    <td align="right"><?= (is_file("/var/log/account/rrd/ping-".$string_1.".rrd")) ? '<a href="/graphs.php?ping='.$string_1.'" class="info_link">'.system_get_ping_response($string_1).'</a>' : system_get_ping_response($string_1) ?></td>
    <td align="right"><?= $string_4 ?></td>
    </tr>
<?
}
?>
