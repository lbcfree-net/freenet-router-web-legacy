<?php
	$LOG_PATH = '/tmp/cd-';

	
	// zapsta do souboru dany obsah, nepovinny parametr attrs ridi pristup k souboru (w, a, w+, r ...)
	function writeFile($filename, $contents, $attrs = "w") 
        {
            if(($file = fopen($filename, $attrs)))
            {
                fwrite($file, $contents);
                fclose($file);
            }
	}
    
    
	// ulozi radek do logu
	function DEBUG($action, $message) {
		global $LOG_PATH;
		
		$log = Date('d.m. Y|H:i:s|');
		$log .= basename($_SERVER['PHP_SELF'], ".php").'|';
		$log .= "$action|$message\n";
		
		// vypsat log na obrazovku
		echo $log;
		// zapsat log - 'a' - atribut append - pripisuje do souboru na konec
		writeFile($LOG_PATH.Date('Y-m-d').'.log', $log, 'a');
	}
	
	function showLog($y = -1, $m = -1, $d = -1) {
		global $LOG_PATH;

		if ($y == -1) $y = Date('Y');
		if ($m == -1) $m = Date('m');
		if ($d == -1) $d = Date('d');
		
		$file = file("${LOG_PATH}$y-$m-$d.log");
		
		echo "<table border=1>";
		foreach($file as $line) {
			list($date, $time, $module, $action, $message) = explode('|', $line);
			echo "<tr><td>$date</td><td>$time</td><td>$module</td><td>$action</td><td>$message</td></tr>";
		}
		echo "</table>";
	}
	
	$ip = '10.93.10.10';
	DEBUG('edit', "interface eth0 ip: $ip");

showLog();	
?>
