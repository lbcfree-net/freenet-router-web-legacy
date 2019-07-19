<?php

function get_hostname() 
{
  $output = '';
  $result = false;
  
  exec('hostname', $output, $result); 
  
  if (!$result)
  {
    return $output[0];
  }
  
  return $output;
}

function get_startup($SERVICE) 
{
  $output = '';
  $result = false;
  
  exec('sudo sysv-rc-conf --list', $output, $result);
  
  switch ($SERVICE) 
  {
    case 'apache':
      $sout = '';
      $res = false;
      exec('sudo systemctl is-enabled apache2', $sout, $res);

      return ($res == 0) && (sizeof($sout) > 0) && ($sout[0] == 'enabled'); 
    case 'dhcp':
      foreach($output as $item)
      {
        if (preg_match('/^isc-dhcp-ser.*\d:on/', $item))
        {
          return true;
        }
      } 

      break;
    case 'firewall':
      foreach($output as $item)
      {
        if (preg_match('/^firewall.*\d:on/', $item) && preg_match('/^firewall6.*\d:on/', $item))
        {
          return true;
        }
      } 

      break;
    case 'macguard':
      $pom1 = false;
      $pom2 = false;

      if (($f = fopen('/etc/firewall/firewall.conf', 'r')))
      {
        while (!feof($f)) 
        {        	    
          $value = preg_split("/[\ =\"\t\n]+/", fgets($f,1024));

          if (($value[0] == 'MACGUARD') && ($value[1] == 'yes')) 
          {
            $pom2 = true;
          } 
          else if (($value[0] == 'FIREWALL') && ($value[1] == 'yes')) 
          {
            $pom1 = true;
          }
        }

        fclose($f);
      }

      foreach($output as $item)
      {
        if (preg_match('/^firewall.*\d:on/', $item))
        {
          return $pom1 && $pom2;
        }
      } 

      break;
    case 'account':
      $pom1 = false;
      $pom2 = false;
      $pom3 = false;

      if (($f = fopen('/etc/firewall/firewall.conf', 'r')))
      {
        while (!feof($f)) 
        {        	    
          $value = preg_split('/[\ =\"\t\n]+/', fgets($f,1024));

          if (($value[0] == 'ACCOUNT') && ($value[1] == 'yes')) 
          {
            $pom1 = true;
          } 
          else if (($value[0] == 'ACCOUNT_GRAPHS') && ($value[1] == 'yes')) 
          {
            $pom2 = true;
          } 
          else if (($value[0] == 'FIREWALL') && ($value[1] == 'yes')) 
          {
            $pom3 = true;
          }
        }

        fclose($f);
      }

      foreach($output as $item)
      {
        if (preg_match('/^firewall.*\d:on/', $item))
        {
          return $pom1 && $pom2 && $pom3;
        }
      } 

      break;
    case 'quagga':
      $sout = '';
      $res = false;
      exec('sudo systemctl is-enabled zebra', $sout, $res);

      if (($res == 0) && (sizeof($sout) > 0) && ($sout[0] == 'enabled')) {

        $sout = '';
        $res = false;
        exec('sudo systemctl is-enabled ospfd', $sout, $res);

        return ($res == 0) && (sizeof($sout) > 0) && ($sout[0] == 'enabled');
      }

      return false;
    case 'snmp':
        $sout = '';
        $res = false;
        exec('sudo systemctl is-enabled snmpd', $sout, $res);

        return ($res == 0) && (sizeof($sout) > 0) && ($sout[0] == 'enabled'); 
    case 'ssh':
        $sout = '';
        $res = false;
        exec('sudo systemctl is-enabled ssh', $sout, $res);

        return ($res == 0) && (sizeof($sout) > 0) && ($sout[0] == 'enabled'); 
  }

  return false;
}

function get_running($SERVICE, $SERVICES, $IPTABLES) 
{
  $output = '';
  $result = false;
  
  switch ($SERVICE) 
  {
    case 'apache':
      $sout = '';
      $res = false;
      exec('sudo systemctl is-active apache2', $sout, $res);

      return ($res == 0) && (sizeof($sout) > 0) && ($sout[0] == 'active'); 
    case 'dhcp':
      exec('sudo service isc-dhcp-server status', $output, $result);
      return !$result;
    case 'firewall':
      exec('sudo service firewall6 status', $output, $result);
      return !$result && !((exec('sudo iptables -L FORWARD -n | wc -l') <= 2) && (exec('sudo iptables -L INPUT -n | wc -l') <= 2) && (exec('sudo iptables -L OUTPUT -n | wc -l') <= 2));
    case 'macguard':
      return array_eregi_search('valid_mac_fwd', $IPTABLES);	    
    case 'account':
      return array_eregi_search('ACCOUNT', $IPTABLES);	    
    case 'quagga':
      $sout = '';
      $res = false;
      exec('sudo systemctl is-active zebra', $sout, $res);

      if (($res == 0) && (sizeof($sout) > 0) && ($sout[0] == 'active')) {

        $sout = '';
        $res = false;
        exec('sudo systemctl is-active ospfd', $sout, $res);

        return ($res == 0) && (sizeof($sout) > 0) && ($sout[0] == 'active');
      }

      return false;
    case 'snmp':
      $sout = '';
      $res = false;
      exec('sudo systemctl is-active snmpd', $sout, $res);

      return ($res == 0) && (sizeof($sout) > 0) && ($sout[0] == 'active'); 
    case 'ssh':
      $sout = '';
      $res = false;
      exec('sudo systemctl is-active ssh', $sout, $res);

      return ($res == 0) && (sizeof($sout) > 0) && ($sout[0] == 'active'); 
  }
  
  return false;
}
function get_primary_dns() {
    if (($f = fopen('/etc/resolv.conf', 'r'))) {
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
    if (($f = fopen('/etc/firewall/firewall.conf', 'r')))
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
    if (($f = fopen('/etc/resolv.conf','r'))) {
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
    return str_replace('@', '<at>', get_file_value('/etc/admin_email'));
}

function get_mail_server() 
{
  $filename = '/etc/ssmtp/ssmtp.conf';
  
  if(!file_exists($filename))
  {
    return '';
  }
  
  if (($f = fopen($filename, 'r'))) 
  {        
      while (!feof($f)) 
      {            
        $value = preg_split('/[\ =\t\n]+/', fgets($f, 1024));

        if ($value[0] == 'mailhub') 
        {
          return $value[1];
        }
      }
      
      fclose($f);
  }
  
  return '';
}

function get_physical_location() {
    $filename="storage/physical_location";
    //uvazujeme volani z rootu webu(vola se tak frontend), pri odlisne implementaci by bylo vhodne predavat cestu
    if(!file_exists($filename)){
        return "";
    }
    $filecontent=  file($filename);
    if(is_array($filecontent)){
        return array_shift($filecontent);
    }else{
        return"";
    }
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
function set_hostname($hostname) 
{
  if (($soubor = fopen('/tmp/hostname', 'w')))
  {
    fwrite($soubor, "$hostname\n");
    fclose($soubor);
  }

  exec("sudo /bin/hostname $hostname");
  exec('sudo /bin/cp /tmp/hostname /etc/hostname');
  // správně musíme ještě upravit /etc/hosts, /etc/ssmtp/ssmtp.conf a další, ale to uděláme vše v set_dns    
}

function set_dns($dns_primary,$dns_secondary,$domain) {
    global $dummy_ip;
    // nastavíme soubory:
    // /etc/hosts
    if (($soubor = fopen("/tmp/hosts","w")))
    {
        fwrite($soubor, "# created by Freenet Router web interface ".date("H:i j.n.Y")."\n");
        fwrite($soubor, "#\n");
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
        fwrite($soubor, "#\n");
        fwrite($soubor,"search ".$domain."\n");
        fwrite($soubor,"nameserver ".$dns_primary."\n");
        fwrite($soubor,"nameserver ".$dns_secondary."\n");
        fclose($soubor);
    }
    exec('sudo /bin/cp /tmp/resolv.conf /etc/resolv.conf');
    
    // /etc/firewall/firewall.conf
    if(($soubor = fopen('/tmp/firewall.conf', 'w')))
    {
        if (($f = fopen('/etc/firewall/firewall.conf', 'r')))
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
        exec("sudo /bin/cp /tmp/firewall.conf /etc/firewall/firewall.conf");
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

function set_physical_location($location) {
    $filename="storage/physical_location";
    //uvazujeme volani z rootu webu(vola se tak frontend), pri odlisne implementaci by bylo vhodne predavat cestu
    if(!system_get_rootfs_status_ro("")){
        if(!file_exists("storage")){
            mkdir("storage");
        }
        if(($soubor = fopen($filename,"w")))
        {
            fwrite($soubor, $location);
            fclose($soubor);
            chmod($filename, 0777);
        } 
    }
}

function set_startup($SERVICE, $VALUE) 
{
  switch ($SERVICE) 
  {
    case 'apache':	    
      $status = (convert_czech_to_english($VALUE) == 'yes') ? 'enable' : 'disable';
      exec("sudo systemctl $status apache2");      
      break;
    case 'dhcp':      
      $status = (convert_czech_to_english($VALUE) == 'yes') ? 'on' : 'off';
      exec("sudo sysv-rc-conf isc-dhcp-server $status");      
      break;
    case 'firewall':
      if(($soubor = fopen('/tmp/firewall.conf', 'w')))
      {
        if(($f = fopen('/etc/firewall/firewall.conf', 'r')))
        {                       
          while (!feof($f)) 
          {
            $value = fgets($f, 1024);
            $value_array = preg_split("/[\ \"=\t\n]+/", $value);
            $value_array_pom = preg_split("/[#\n]+/", $value);

            if ($value_array[0] == 'FIREWALL') 
            {
                fwrite($soubor, 'FIREWALL="' . convert_czech_to_english($VALUE) . "\"\t\t\t#$value_array_pom[1]\n");                
            } 
            else 
            {
              fwrite($soubor,$value);
            }
          }

          fclose($f);
        }

        fclose($soubor);
        exec('sudo cp /tmp/firewall.conf /etc/firewall/firewall.conf');
      }	
      
      $status = (convert_czech_to_english($VALUE) == 'yes') ? 'on' : 'off';
      exec("sudo sysv-rc-conf firewall $status");
      exec("sudo sysv-rc-conf firewall6 $status");
      break;
    case 'macguard':
      if(($soubor = fopen('/tmp/firewall.conf', 'w')))
      {
        if(($f = fopen('/etc/firewall/firewall.conf', 'r')))
        {
          $pom1 = false;
          $pom2 = false;

          while (!feof($f)) 
          {
            $value = fgets($f, 1024);
            $value_array = preg_split("/[\ \"=\t\n]+/", $value);
            $value_array_pom = preg_split("/[#\n]+/", $value);

            if (($value_array[0] == 'MACGUARD') && (!$pom1)) 
            {
              fwrite($soubor, 'MACGUARD="' . convert_czech_to_english($VALUE) . "\"\t\t\t#$value_array_pom[1]\n");
              $pom1 = true;
            } 
            else if (($value_array[0] == 'FIREWALL') && (!$pom2)) 
            {
              if (convert_czech_to_english($VALUE) == 'yes') 
              {
                fwrite($soubor, "FIREWALL=\"yes\"\t\t\t#$value_array_pom[1]\n");
              } 
              else 
              {
                 fwrite($soubor, $value);
              }

              $pom2 = true;
            } 
            else 
            {               
              fwrite($soubor,$value);
            }
          }

          fclose($f);
        }

        fclose($soubor);
        exec('sudo cp /tmp/firewall.conf /etc/firewall/firewall.conf');
      }	    
       
      $status = (convert_czech_to_english($VALUE) == 'yes') ? 'on' : 'off';
      
      if ($status == 'on' && $pom1 && $pom2) 
      {        
        exec("sudo sysv-rc-conf firewall $status");      
      }
      
      break;
    case 'account':
      if(($soubor = fopen('/tmp/firewall.conf', 'w')))
      {
        if(($f = fopen('/etc/firewall/firewall.conf', 'r')))
        {
          $pom1 = false;
          $pom2 = false;
          $pom3 = false;                   

            while (!feof($f)) 
            {
              $value = fgets($f, 1024);
              $value_array = preg_split('/[\ \"=\t\n]+/', $value);
              $value_array_pom = preg_split('/[#\n]+/', $value);

              if (($value_array[0] == 'ACCOUNT') && (!$pom1)) 
              {
                fwrite($soubor, 'ACCOUNT="' . convert_czech_to_english($VALUE) . "\"\t\t\t#$value_array_pom[1]\n");
                $pom1 = true;
              } 
              else if (($value_array[0] == 'ACCOUNT_GRAPHS') && (!$pom2)) 
              {
                fwrite($soubor, 'ACCOUNT_GRAPHS="' . convert_czech_to_english($VALUE) . "\"\t\t#$value_array_pom[1]\n");
                $pom2 = true;
              } 
              else if (($value_array[0] == 'FIREWALL') && (!$pom3)) 
              {
                if (convert_czech_to_english($VALUE) == 'yes') 
                {
                  fwrite($soubor, "FIREWALL=\"yes\"\t\t\t#$value_array_pom[1]\n");
                } 
                else 
                {
                  fwrite($soubor,$value);
                }

                $pom3 = true;                        
              } 
              else 
              {
                fwrite($soubor,$value);
              }
           }

           fclose($f);
        }

        fclose($soubor);
        exec('sudo cp /tmp/firewall.conf /etc/firewall/firewall.conf');
      }
        
      $status = (convert_czech_to_english($VALUE) == 'yes') ? 'on' : 'off';
      
      if ($status == 'on' && $pom1 && $pom2 && $pom3) 
      {        
        exec("sudo sysv-rc-conf firewall $status");      
      }
      
      break;
    case 'quagga':
      $status = (convert_czech_to_english($VALUE) == 'yes') ? 'enable' : 'disable';
      exec("sudo systemctl $status zebra");      
      exec("sudo systemctl $status ospfd");      
      break;
    case 'snmp':
      $status = (convert_czech_to_english($VALUE) == 'yes') ? 'enable' : 'disable';
      exec("sudo systemctl $status snmpd");      
      break;
    case 'ssh':
      $status = (convert_czech_to_english($VALUE) == 'yes') ? 'enable' : 'disable';
      exec("sudo systemctl $status ssh");      
      break;
  }
}

function set_internal_ip($VALUE) {
    if(($soubor = fopen('/tmp/firewall.conf', 'w')))
    {
        if(($f = fopen('/etc/firewall/firewall.conf', 'r')))
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
        exec('sudo /bin/cp /tmp/firewall.conf /etc/firewall/firewall.conf');
    }
}

function service($SERVICE, $VALUE) 
{
  $cmd = convert_czech_to_english($VALUE);

  switch ($SERVICE) 
  {
    case 'apache':
      exec("sudo systemctl $cmd apache2");
      break;
    case 'dhcp':
      exec("sudo service isc-dhcp-server $cmd");
      break;
    case 'firewall':
      exec("sudo service firewall $cmd");
      break;
    case 'macguard':
      exec("sudo service firewall macguard_$cmd");
      break;
    case 'account':
      exec("sudo service firewall account_$cmd");
      break;
    case 'quagga':
      exec("sudo systemctl $cmd zebra");
      exec("sudo systemctl $cmd ospfd");
      break;
    case 'snmp':
      exec("sudo systemctl $cmd snmpd");
      break;
    case 'ssh':
      exec("sudo systemctl $cmd ssh");
      break;
  }
}

function set_rootfs_rw() 
{
  exec('sudo rw');
}

function set_rootfs_ro() 
{    
  exec('sudo ro');
}

function get_fr_version()
{
    $output = '';
    $result = 1;
    $version = '';

    exec('sudo apt show freenet-router-web-legacy3.2', $output, $result);

    if($result == 0){

        foreach($output as $line){

            if(preg_match('/^Version: 0.(.*)$/', $line, $matches)){

                $version .= 'r' . $matches[1];

            }
            elseif(preg_match('/^Date:(.*)$/', $line, $matches)){

                $version .= $matches[1];

            }

        }

    }


    return $version;
}


?>
