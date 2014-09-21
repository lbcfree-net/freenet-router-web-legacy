<?php
function get_hostname() {
    return get_file_value("/etc/hostname");
}
function get_startup($SERVICE) {
    switch ($SERVICE) {
	case "apache":
	    if (is_link("/etc/rc2.d/S91apache2")) {
		return true;
	    }
	    break;
	case "dhcp":
	    if (is_link("/etc/rc2.d/S40dhcp3-server")) {
		return true;
	    }
	    break;
	case "firewall":
            $pom = false;
	    if (($f = fopen("/etc/init.d/firewall","r"))) {
    		while (!feof($f)) {        	    
		    $value = preg_split("/[\ =\"\t\n]+/", fgets($f,1024));
		    if (($value[0] == "FIREWALL") && ($value[1] == "yes")) {
	    		$pom = true;
		    }
		}
    		fclose($f);
	    }
	    if (is_link("/etc/rcS.d/S42firewall") && ($pom)) {
		return true;
	    }
	    break;
	case "macguard":
            $pom = false;
            $pom2 = false;
	    if (($f = fopen("/etc/init.d/firewall","r"))) {
    		while (!feof($f)) {        	    
		    $value = preg_split("/[\ =\"\t\n]+/", fgets($f,1024));
	    	    if (($value[0] == "MACGUARD") && ($value[1] == "yes")) {
	    		$pom2 = true;
		    } else if (($value[0] == "FIREWALL") && ($value[1] == "yes")) {
	    		$pom = true;
		    }
		}
    		fclose($f);
	    }
	    if (is_link("/etc/rcS.d/S42firewall") && ($pom) && ($pom2)) {
		return true;
	    }
	    break;
	case "account":
            $pom1 = false;
            $pom2 = false;
            $pom3 = false;
	    if (($f = fopen("/etc/init.d/firewall","r"))) {
    		while (!feof($f)) {        	    
		    $value = preg_split("/[\ =\"\t\n]+/", fgets($f,1024));
	    	    if (($value[0] == "ACCOUNT") && ($value[1] == "yes")) {
	    		$pom1 = true;
	    	    } else if (($value[0] == "ACCOUNT_GRAPHS") && ($value[1] == "yes")) {
	    		$pom2 = true;
		    } else if (($value[0] == "FIREWALL") && ($value[1] == "yes")) {
	    		$pom3 = true;
		    }
		}
    		fclose($f);
	    }
	    if (is_link("/etc/rcS.d/S42firewall") && ($pom1) && ($pom2) && ($pom3)) {
		return true;
	    }
	    break;
	case "quagga":
	    if (is_link("/etc/rc2.d/S20quagga")) {
		return true;
	    }
	    break;
	case "snmp":
	    if (is_link("/etc/rc2.d/S20snmpd")) {
		return true;
	    }
	    break;
	case "ssh":
	    if (is_link("/etc/rc2.d/S16ssh")) {
		return true;
	    }
	    break;
    }
    return false;
}
function get_running($SERVICE,$SERVICES,$IPTABLES) {
    switch ($SERVICE) {
	case "apache":
	    return array_eregi_search("/usr/sbin/apache2",$SERVICES);
	    break;
	case "dhcp":
	    return array_eregi_search('/usr/sbin/dhcpd', $SERVICES);
	    break;
	case "firewall":
	    if ((exec("sudo /usr/sbin/iptables -L FORWARD -n | wc -l") <= 2) && (exec("sudo /usr/sbin/iptables -L INPUT -n | wc -l") <= 2) && (exec("sudo /usr/sbin/iptables -L OUTPUT -n | wc -l") <= 2)) {
		return false;
	    }
	    return true;
	    break;
	case "macguard":
	    return array_eregi_search("valid_mac_fwd",$IPTABLES);
	    break;
	case "account":
	    return array_eregi_search("ACCOUNT",$IPTABLES);
	    break;
	case "quagga":
	    return array_eregi_search("/usr/lib/quagga/zebra",$SERVICES);
	    break;
	case "snmp":
	    return array_eregi_search("/usr/sbin/snmpd",$SERVICES);
	    break;
	case "ssh":
	    return array_eregi_search("/usr/sbin/sshd",$SERVICES);
	    break;
    }
    return false;
}
function get_primary_dns() {
    if (($f = fopen("/etc/resolvconf/resolv.conf.d/base","r"))) {        
        while (!feof($f)) {            
	    $value = preg_split("/[\ \t\n]+/", fgets($f,1024));
	    if ($value[0] == "nameserver") {
		return $value[1];
	    }
        }
        fclose($f);
    }
    return "";
}
function get_internal_ip() {
    if (($f = fopen("/etc/init.d/firewall","r")))
    {	
	while (!feof($f)) {	    
	    $value = preg_split("/[\ =\"\t\n]+/", fgets($f,1024));
	    if ($value[0] == "INTERNAL_IP") {
		return $value[1];
	    }
	}
    	fclose($f);
    }
    return "";
}
function get_secondary_dns() {
    if (($f = fopen("/etc/resolvconf/resolv.conf.d/base","r"))) {
	$pom = false;        
        while (!feof($f)) {            
	    $value = preg_split("/[\ \t\n]+/", fgets($f,1024));
	    if (($value[0] == "nameserver") && (!$pom)) {
		$pom = true;
	    } else if (($value[0] == "nameserver") && ($pom)) {
		return $value[1];
	    }
        }
        fclose($f);
    }
    return "";
}
function get_admin_email() {
    return get_file_value("/etc/admin_email");
}
function get_admin_email_hash() {
    return ereg_replace("@","<at>",get_file_value("/etc/admin_email"));
}
function get_mail_server() {
    if (($f = fopen("/etc/ssmtp/ssmtp.conf","r"))) {        
        while (!feof($f)) {            
	    $value = preg_split("/[\ =\t\n]+/", fgets($f,1024));
	    if ($value[0] == "mailhub") {
		return $value[1];
	    }
        }
        fclose($f);
    }
    return "";
}
function get_domain() {
    if (($f = fopen("/etc/resolv.conf","r"))) {
        while (!feof($f)) {            
	    $value = preg_split("/[\ \t\n]+/", fgets($f,1024));
	    if($value[0] == "search") 
            {
		return $value[1];
	    }
        }
        fclose($f);
    }
    // doména musí být zadaná!
    return "lbcfree.czf";
}
// funkce set -----------------
function set_hostname($hostname) {
    if (($soubor = fopen("/tmp/hostname","w")))
    {
        fwrite($soubor,$hostname."\n");
        fclose($soubor);
    }
    exec("sudo /bin/hostname ".$hostname);
    exec("sudo /bin/cp /tmp/hostname /etc/hostname");
    // správně musíme ještě upravit /etc/hosts, /etc/ssmtp/ssmtp.conf a další, ale to uděláme vše v set_dns
}
function set_dns($dns_primary,$dns_secondary,$domain) {
    global $dummy_ip;
    // nastavíme soubory:
    // /etc/hosts
    if (($soubor = fopen("/tmp/hosts","w")))
    {
        fwrite($soubor, "# created by Freenet Router web interface ".date("H:i j.n.Y")."\n");
        fwrite($soubor,"127.0.0.1\tlocalhost\n");
        fwrite($soubor,"127.0.0.1\t".get_hostname().".".$domain."\t".get_hostname()."\n");
        if ($dummy_ip != "") {
            fwrite($soubor,$dummy_ip."\t".get_hostname().".".$domain."\t".get_hostname()."\n");
        }
        fclose($soubor);
    }
    exec("sudo /bin/cp /tmp/hosts /etc/hosts");
    // /etc/resolv.conf
    if(($soubor = fopen("/tmp/resolv.conf","w")))
    {
        fwrite($soubor, "# created by Freenet Router web interface ".date("H:i j.n.Y")."\n");
        fwrite($soubor,"search ".$domain."\n");
        fwrite($soubor,"nameserver ".$dns_primary."\n");
        fwrite($soubor,"nameserver ".$dns_secondary."\n");
        fclose($soubor);
    }
    exec("sudo /bin/cp /tmp/resolv.conf /etc/resolvconf/resolv.conf.d/base");
    exec('sudo resolvconf -u');
    
    // /etc/init.d/firewall
    if(($soubor = fopen("/tmp/firewall","w")))
    {
        if (($f = fopen('/etc/init.d/firewall', 'r'))) 
        {  
            $pom1 = false;
            $pom2 = false;
            $pom3 = false;
            while (!feof($f)) {
              $value = fgets($f,1024);
              $value_array = preg_split("/[\ \"=\t\n]+/", $value);
              $value_array_pom = preg_split("/[#\n]+/", $value);
              if (($value_array[0] == "DNS_PRIMARY") && (!$pom1)) {
                    fwrite($soubor,"DNS_PRIMARY=\"".$dns_primary."\"\t\t#".$value_array_pom[1]."\n");
                    $pom1 = true;
                } else if (($value_array[0] == "DNS_SECONDARY") && (!$pom2)) {
                    fwrite($soubor,"DNS_SECONDARY=\"".$dns_secondary."\"\t#".$value_array_pom[1]."\n");
                    $pom2 = true;
                } else if (($value_array[0] == "DOMAIN") && (!$pom3)) {
                    fwrite($soubor,"DOMAIN=\"".$domain."\"\t\t#".$value_array_pom[1]."\n");
                    $pom3 = true;
                // pokud jsme nezadali dns ani doménu a už je položka LO_IFACE, pak asi ve firewallu není a tak je tam dodáme
                } else if ($value_array[0] == "LO_IFACE") {
                    if (!$pom1) {
                        fwrite($soubor,"DNS_PRIMARY=\"".$dns_primary."\"\n");
                        $pom1 = true;
                    }
                    if (!$pom2) {
                        fwrite($soubor,"DNS_SECONDARY=\"".$dns_secondary."\"\n");
                        $pom2 = true;
                    }
                    if (!$pom3) {
                        fwrite($soubor,"DOMAIN=\"".$domain."\"\n");
                        $pom3 = true;
                    }
                    fwrite($soubor,$value);
                } else {
                  fwrite($soubor,$value);
              }
          }
          fclose($f);
        }
        fclose($soubor);
        exec("sudo /bin/cp /tmp/firewall /etc/init.d/firewall");
    }    
    // správně bychom měli i /etc/dhcpd.conf, ale o ten se v normální situaci stará macguard
    // správně musíme ještě nastavit ssmtp.conf, ale protože se stejně volá v index.html až potom, tak je to zbytečné
}
function set_admin_email($email) {
    if(($soubor = fopen("/tmp/admin_email","w")))
    {
        fwrite($soubor,$email."\n");
        fclose($soubor);
        exec("sudo /bin/cp /tmp/admin_email /etc/admin_email");
    }    
}
function set_mail_server($server) {
    if (!is_dir("/tmp/ssmtp")) {
	mkdir("/tmp/ssmtp");
    }
    if(($soubor = fopen("/tmp/ssmtp/ssmtp.conf","w")))
    {
        if(($f = fopen("/etc/ssmtp/ssmtp.conf","r"))) 
        {
            $pom = true;
            $pom1 = false;
            $pom2 = false;
            $pom3 = false;
            
            while (!feof($f)) {
              $value = fgets($f,1024);
              $value_array = preg_split("/[\ =\t\n]+/", $value);
              if ($value_array[0] == "mailhub") {
                  fwrite($soubor,"mailhub=".$server."\n");
                  $pom1 = true;
              } else if ($value_array[0] == "hostname") {
                  fwrite($soubor,"hostname=".get_hostname().".".get_domain()."\n");
                  $pom2 = true;
              } else if ($value_array[0] == "root") {
                  fwrite($soubor,$value);
                  $pom3 = true;
              } else {
                  fwrite($soubor,$value);
              }
            }                          
            fclose($f);
        }
        if (!$pom) {
            fwrite($soubor,"# Config file for sSMTP sendmail\n");
        }
        if (!$pom1) {
            fwrite($soubor,"mailhub=".$server."\n");
        }
        if (!$pom2) {
            fwrite($soubor,"hostname=".get_hostname().".".get_domain()."\n");
        }
        if (!$pom3) {
            fwrite($soubor,"root=postmaster\n");
        }
        if (!$pom) {
            fwrite($soubor,"#rewriteDomain=".get_domain()."\n");
            fwrite($soubor,"FromLineOverride=NO\n");
        }
        fclose($soubor);
        exec("sudo /bin/cp -a /tmp/ssmtp /etc/");
    }
}
function set_startup($SERVICE,$VALUE) {
    switch ($SERVICE) {
	case "apache":
	    $files = array("/etc/rc0.d/K09apache2","/etc/rc1.d/K09apache2","/etc/rc2.d/S91apache2","/etc/rc3.d/S91apache2","/etc/rc4.d/S91apache2","/etc/rc5.d/S91apache2","/etc/rc6.d/K09apache2");
	    if (convert_czech_to_english($VALUE) == "yes") {
		foreach ($files as $file) {
		    exec("sudo /bin/ln -s ../init.d/apache2 ".$file);
		}
	    } else {
		exec("sudo /bin/rm -f ".implode(" ",$files));
	    }
	    break;
	case "dhcp":
	    $files = array("/etc/rc1.d/K40dhcp3-server","/etc/rc2.d/S40dhcp3-server","/etc/rc3.d/S40dhcp3-server","/etc/rc4.d/S40dhcp3-server","/etc/rc5.d/S40dhcp3-server");
	    if (convert_czech_to_english($VALUE) == "yes") {
		foreach ($files as $file) {
		    exec("sudo /bin/ln -s ../init.d/dhcp3-server ".$file);
		}
	    } else {
		exec("sudo /bin/rm -f ".implode(" ",$files));
	    }
	    break;
	case "firewall":
	    if(($soubor = fopen("/tmp/firewall","w")))
            {
                if(($f = fopen("/etc/init.d/firewall","r")))
                {
                    $pom1 = false;                   
                    while (!feof($f)) {
                        $value = fgets($f,1024);
                        $value_array = preg_split("/[\ \"=\t\n]+/", $value);
                        $value_array_pom = preg_split("/[#\n]+/", $value);
                        if (($value_array[0] == "FIREWALL") && (!$pom1)) {
                            fwrite($soubor,"FIREWALL=\"".convert_czech_to_english($VALUE)."\"\t\t\t#".$value_array_pom[1]."\n");
                            $pom1 = true;
                        } else {
                            fwrite($soubor,$value);
                        }
                    }
                    fclose($f);
                }
                fclose($soubor);
                exec("sudo /bin/cp /tmp/firewall /etc/init.d/firewall");
            }	    
	    if (!is_link("/etc/rcS.d/S42firewall") && ($pom1) && (convert_czech_to_english($VALUE) == "yes")) {
		exec("sudo /bin/ln -s ../init.d/firewall /etc/rcS.d/S42firewall");
	    }
	    break;
	case "macguard":
	    if(($soubor = fopen("/tmp/firewall","w")))
            {
                if(($f = fopen("/etc/init.d/firewall","r")))
                {
                    $pom1 = false;
                    $pom2 = false;
                    while (!feof($f)) {
                        $value = fgets($f,1024);
                        $value_array = preg_split("/[\ \"=\t\n]+/", $value);
                        $value_array_pom = preg_split("/[#\n]+/", $value);
                        if (($value_array[0] == "MACGUARD") && (!$pom1)) {
                            fwrite($soubor,"MACGUARD=\"".convert_czech_to_english($VALUE)."\"\t\t\t#".$value_array_pom[1]."\n");
                            $pom1 = true;
                        } else if (($value_array[0] == "FIREWALL") && (!$pom2)) {
                            if (convert_czech_to_english($VALUE) == "yes") {
                                fwrite($soubor,"FIREWALL=\"yes\"\t\t\t#".$value_array_pom[1]."\n");
                            } else {
                                fwrite($soubor,$value);
                            }
                            $pom2 = true;
                        } else {
                            fwrite($soubor,$value);
                        }
                    }
                    fclose($f);
                }
                fclose($soubor);
                exec("sudo /bin/cp /tmp/firewall /etc/init.d/firewall");
            }	    
	    if (!is_link("/etc/rcS.d/S42firewall") && ($pom1) && ($pom2) && (convert_czech_to_english($VALUE) == "yes")) {
		exec("sudo /bin/ln -s ../init.d/firewall /etc/rcS.d/S42firewall");
	    }
	    break;
	case "account":
	    if(($soubor = fopen("/tmp/firewall","w")))
            {
               if(($f = fopen("/etc/init.d/firewall","r")))
               {
                   $pom1 = false;
                   $pom2 = false;
                   $pom3 = false;                   
                    while (!feof($f)) {
                        $value = fgets($f,1024);
                        $value_array = preg_split("/[\ \"=\t\n]+/", $value);
                        $value_array_pom = preg_split("/[#\n]+/", $value);
                        if (($value_array[0] == "ACCOUNT") && (!$pom1)) {
                            fwrite($soubor,"ACCOUNT=\"".convert_czech_to_english($VALUE)."\"\t\t\t#".$value_array_pom[1]."\n");
                            $pom1 = true;
                        } else if (($value_array[0] == "ACCOUNT_GRAPHS") && (!$pom2)) {
                            fwrite($soubor,"ACCOUNT_GRAPHS=\"".convert_czech_to_english($VALUE)."\"\t\t#".$value_array_pom[1]."\n");
                            $pom2 = true;
                        } else if (($value_array[0] == "FIREWALL") && (!$pom3)) {
                            if (convert_czech_to_english($VALUE) == "yes") {
                                fwrite($soubor,"FIREWALL=\"yes\"\t\t\t#".$value_array_pom[1]."\n");
                            } else {
                                fwrite($soubor,$value);
                            }
                            $pom3 = true;
                        } else {
                            fwrite($soubor,$value);
                        }
                    }
                    fclose($f);
               }
                fclose($soubor);
                exec("sudo /bin/cp /tmp/firewall /etc/init.d/firewall");
            }
	    if (!is_link("/etc/rcS.d/S42firewall") && ($pom1) && ($pom2) && ($pom3) && (convert_czech_to_english($VALUE) == "yes")) {
		exec("sudo /bin/ln -s ../init.d/firewall /etc/rcS.d/S42firewall");
	    }
	    break;
	case "quagga":
	    $files = array("/etc/rc0.d/K20quagga","/etc/rc1.d/K20quagga","/etc/rc2.d/S20quagga","/etc/rc3.d/S20quagga","/etc/rc4.d/S20quagga","/etc/rc5.d/S20quagga","/etc/rc6.d/K20quagga");
	    if (convert_czech_to_english($VALUE) == "yes") {
		foreach ($files as $file) {
		    exec("sudo /bin/ln -s ../init.d/quagga ".$file);
		}
	    } else {
		exec("sudo /bin/rm -f ".implode(" ",$files));
	    }
	    break;
	case "snmp":
	    $files = array("/etc/rc0.d/K20snmpd","/etc/rc1.d/K20snmpd","/etc/rc2.d/S20snmpd","/etc/rc3.d/S20snmpd","/etc/rc4.d/S20snmpd","/etc/rc5.d/S20snmpd","/etc/rc6.d/K20snmpd");
	    if (convert_czech_to_english($VALUE) == "yes") {
		foreach ($files as $file) {
		    exec("sudo /bin/ln -s ../init.d/snmpd ".$file);
		}
	    } else {
		exec("sudo /bin/rm -f ".implode(" ",$files));
	    }
	    break;
	case "ssh":
	    $files = array("/etc/rc1.d/K84ssh","/etc/rc2.d/S16ssh","/etc/rc3.d/S16ssh","/etc/rc4.d/S16ssh","/etc/rc5.d/S16ssh");
	    if (convert_czech_to_english($VALUE) == "yes") {
		foreach ($files as $file) {
		    exec("sudo /bin/ln -s ../init.d/ssh ".$file);
		}
	    } else {
		exec("sudo /bin/rm -f ".implode(" ",$files));
	    }
	    break;
    }
}
function set_internal_ip($VALUE) {
    if(($soubor = fopen("/tmp/firewall","w")))
    {
        if(($f = fopen("/etc/init.d/firewall","r")))
        {
            $pom1 = false;
            while (!feof($f)) {
                $value = fgets($f,1024);
                $value_array = preg_split("/[\ \"=\t\n]+/", $value);
                $value_array_pom = preg_split("/[#\n]+/", $value);
                if (($value_array[0] == "INTERNAL_IP") && (!$pom1)) {
                    fwrite($soubor,"INTERNAL_IP=\"".$VALUE."\"\t#".$value_array_pom[1]."\n");
                    $pom1 = true;
                } else {
                    fwrite($soubor,$value);
                }
            }
            fclose($f);
        }
        fclose($soubor);
        exec("sudo /bin/cp /tmp/firewall /etc/init.d/firewall");
    }
}
function service($SERVICE,$VALUE) {
    switch ($SERVICE) {
	case "apache":
	    exec("sudo /etc/init.d/apache2 ".convert_czech_to_english($VALUE));
	    break;
	case "dhcp":
	    exec("sudo /etc/init.d/dhcp3-server ".convert_czech_to_english($VALUE));
	    break;
	case "firewall":
	    exec("sudo /etc/init.d/firewall ".convert_czech_to_english($VALUE));
	    break;
	case "macguard":
	    exec("sudo /etc/init.d/firewall macguard_".convert_czech_to_english($VALUE));
	    break;
	case "account":
	    exec("sudo /etc/init.d/firewall account_".convert_czech_to_english($VALUE));
	    break;
	case "quagga":
	    exec("sudo /etc/init.d/quagga ".convert_czech_to_english($VALUE));
	    break;
	case "snmp":
	    exec("sudo /etc/init.d/snmpd ".convert_czech_to_english($VALUE));
	    break;
	case "ssh":
	    exec("sudo /etc/init.d/ssh ".convert_czech_to_english($VALUE));
	    break;
    }
}

function set_rootfs_rw() 
{
    exec('sudo /usr/local/sbin/rw');
}

function set_rootfs_ro() 
{    
    exec('sudo /usr/local/sbin/ro');
}
?>
