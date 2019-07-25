<?php
// načteme hlavičku a často používané funkce, create_selection, is_ip_from_subnet
include 'include/functions/general.php';
include 'include/functions/index.php';
include 'include/functions/system.php';
include 'include/header.php';
?>
<?php
if (isset($_POST['save']) && $login) {
    set_hostname($_POST["HOSTNAME"]);
    set_dns($_POST["PRIMARY_DNS"],$_POST["SECONDARY_DNS"],$_POST["DOMAIN"]);
    set_internal_ip($_POST["INTERNAL_IP"]);
    set_admin_email($_POST["ADMIN_EMAIL"]);
    set_mail_server($_POST["MAIL_SERVER"]);
    set_physical_location($_POST["PHYSICAL_LOCATION"]);
    set_startup("apache",$_POST["APACHE"]);
    set_startup("dhcp",$_POST["DHCP"]);
    set_startup("firewall",$_POST["FIREWALL"]);
    set_startup("macguard",$_POST["MACGUARD"]);
    set_startup("account",$_POST["ACCOUNT"]);
    set_startup("quagga",$_POST["QUAGGA"]);
    set_startup("snmp",$_POST["SNMP"]);
    set_startup("ssh",$_POST["SSH"]);
} else if (isset($_POST["set_rw"]) && $login) {
    set_rootfs_rw();
} else if (isset($_POST["set_ro"]) && $login) {
    set_rootfs_ro();
} else if (isset($_POST["APACHE_BUTTON"]) && $login) {
    service("apache",$_POST["APACHE_BUTTON"]);
} else if (isset($_POST["DHCP_BUTTON"]) && $login) {
    service("dhcp",$_POST["DHCP_BUTTON"]);
} else if (isset($_POST["FIREWALL_BUTTON"]) && $login) {
    service("firewall",$_POST["FIREWALL_BUTTON"]);
} else if (isset($_POST["MACGUARD_BUTTON"]) && $login) {
    service("macguard",$_POST["MACGUARD_BUTTON"]);
} else if (isset($_POST["ACCOUNT_BUTTON"]) && $login) {
    service("account",$_POST["ACCOUNT_BUTTON"]);
} else if (isset($_POST["QUAGGA_BUTTON"]) && $login) {
    service("quagga",$_POST["QUAGGA_BUTTON"]);
} else if (isset($_POST["SNMP_BUTTON"]) && $login) {
    service("snmp",$_POST["SNMP_BUTTON"]);
} else if (isset($_POST["SSH_BUTTON"]) && $login) {
    service("ssh",$_POST["SSH_BUTTON"]);
} else if (isset($_GET["password"]) && $login) {
    change_password($user,$_GET["password"]);
?>
    <script language="JavaScript">
    <!--
    window.location=('/index.php')
    //-->
    </script>
<?php
}
?>
<form method=post action="<?=$_SERVER['PHP_SELF']?> ">
<table width="100%">
    <tr>
    <td align="left" valign="top">
    <div id="text2">
    <div id="login">
    <span id="head2">přihlášení:</span><br>
    <table width="98%">
<?php
if ($login)  {
?>
    <tr>
    <td align="left" colspan="2" width="50%">jste přihlášen jako uživatel: <?= $user ?></td>
    <td width="5%"></td>
    <?php
        $ro = system_get_rootfs_status_ro('')
    ?>
    <td colspan="2" align="left">
        zápis na disk: <em class="<?= $ro ? 'ro' : 'rw'?>"><?= $ro ? 'nepovolen' : 'povolen' ?></em>
    </td>
    </tr>
    <tr>
    <td align="left"><input type="submit" name="logout" value="odhlásit se"></td>
    <td align="left">
        <input type="button" name="change_password" value="změnit heslo" onclick="get_variable('password','nové heslo');">
    </td>
    <td></td>
    <td colspan="2" align="right">
        <input type="submit" name="set_<?= $ro ? 'rw' : 'ro' ?>" value="<?= $ro ? 'povolit zápis' : 'uzamknout' ?>" disabled>
    </td>
    </tr>
<?php
} else {
?>
    <tr><td align="left" width="11%">jméno:</td><td align="left" width="20%"><input type="text" name="jmeno" size="14"></td><td></td><td></td></tr>
    <tr><td align="left">heslo:</td><td align="left"><input type="password" name="heslo" size="14"></td><td></td><td></td></tr>
    <tr><td colspan="2" align="left"><input type="submit" name="login" value="přihlásit se"></td><td></td><td></td></tr>
<?php
}
?>
    </table>
    </div>
    </div>
    <table width="98%">
<?php
table_line();
?>
    <tr>
    <td colspan="2">obecné</td>
    </tr>
<?php
table_line();
table_text_array("název routeru", "HOSTNAME", get_hostname(),"23","");
table_text_array("primární dns server", "PRIMARY_DNS", get_primary_dns(),"23","");
table_text_array("sekundární dns server", "SECONDARY_DNS", get_secondary_dns(),"23","");
table_text_array("doména", "DOMAIN", get_domain(),"23","");
table_text_array("vnitřní rozsahy sítě", "INTERNAL_IP", get_internal_ip(),"23","");
if ($login) {
    table_text_array("email správce", "ADMIN_EMAIL", get_admin_email(),"23","");
} else {
    table_text_array("email správce", "ADMIN_EMAIL", get_admin_email_hash(),"23","");
}
table_text_array("mail server", "MAIL_SERVER", get_mail_server(),"23","");
table_text_array("fyzické umístění routeru", "PHYSICAL_LOCATION", get_physical_location(),"23","");
table_line();
?>
    <tr>
    <td colspan="2">služby spouštěné po startu systému</td>
    </tr>
<?php
table_line();
exec('ps ax', $services);
exec('sudo /sbin/iptables -L -n', $iptables);
$output = '';
$result = false;
exec('sudo sysv-rc-conf --list', $sysv);
$selection = ['ano', 'ne'];
create_selection_service('apache','APACHE', $selection, get_startup('apache', $sysv), get_running('apache', $services, $iptables));
create_selection_service('dhcp server','DHCP', $selection, get_startup('dhcp', $sysv) ,get_running('dhcp', $services, $iptables));
create_selection_service('firewall','FIREWALL', $selection, get_startup('firewall', $sysv), get_running('firewall', $services, $iptables));
create_selection_service('macguard','MACGUARD', $selection, get_startup('macguard', $sysv), get_running('macguard', $services, $iptables));
create_selection_service('tvorba grafů','ACCOUNT', $selection, get_startup('account', $sysv), get_running('account', $services, $iptables));
create_selection_service('quagga','QUAGGA', $selection, get_startup('quagga', $sysv), get_running('quagga', $services, $iptables));
create_selection_service('snmp','SNMP', $selection, get_startup('snmp', $sysv), get_running('snmp', $services, $iptables));
create_selection_service('ssh','SSH', $selection, get_startup('ssh', $sysv), get_running('ssh', $services, $iptables));
if ($login) {
    table_line();
?>
    <tr>
    <td colspan="2"><input type="submit" name="save" value="uložit"></td>
    </tr>
<?php
}
?>
    </table>
    </td>
    <td width="475" align="left" valign="top">
    <table width="98%">
    <tr>
    <td align="left">
    <span id="active_ips_text">připojených počítačů: </span>
    </td>
    <td width="150" align="right">
    <span id="active_ips"><a href="/graphs.php?users=0"><?= file_exists('/var/log/account/users.txt') ? get_file_value('/var/log/account/users.txt') : '0'; ?></a></span>
    </td>
    </tr>
    </table>
    <table width="98%">
    <tr>
    <td align="left" >
    <span id="active_ips_text">celkový přenos dat: </span>
    </td>
    <td width="200" align="right">
<?php
    $total_rate = 0;
    if (file_exists("/var/log/account/interfaces_data.txt")) {
        $ifaces_data = file("/var/log/account/interfaces_data.txt");
        foreach ($ifaces_data as $v) {
            $v = preg_split("/[ \t\n]+/",$v);
            if ($v[0] != "all") continue;
            $total_rate = $v[3] + $v[4];
            break;
        }
    }
?>
    <span id="total_rate"><a href="/graphs.php?interface=<?= $ADAPTER_ALL ?>"><?= convert_units($total_rate,$monitoring["rate_units"],"M",2) ?>/s</a></span>
    </td>
    </tr>
    <tr height="30">
    </tr>
    </table>
<?php
if ($login) {
?>
    <ul class="bullets">
    <li>Na této stránce můžete nastavit spouštění služeb po startu, název routeru, dns servery a další vlastnosti routeru.</li>
    <li>Pro nastavení jednotlivých síťových karet, přejděte na stránku <a href="/network.php">nastavení síťových karet</a>.</li>
    <li>Pokud chcete vypnout, restartovat, nebo editovat firewall, přejděte na stránku <a href="/firewall.php">firewall</a>.</li>
    <li>Chcete-li vypnout, aktualizovat, nebo upravit data pro macguarda, přejděte na stránku <a href="/macguard.php">macguard</a>.</li>
    <li>Zozbrazení rout, start, restart quaggy a editaci souborů quaggy můžete provést na stránce <a href="/quagga.php">quagga</a>.</li>
    </ul>
    <hr>
    <br/>
<?php
}
?>
    <ul class="bullets gray">
    <li>Vítejte, tyto stránky slouží k administraci routeru s operačním systémem Freenet router.</li>
    <li>Po přihlášení získáte dodatečná práva, budete moci editovat konfigurační soubory, ukládat nastavení i restartovat router.</li>
    <li>Bez přihlášení si můžete prohlížet statistiky, grafy a některé další systémové informace.</li>
    <li>Chcete-li si prohlédnout kolik jste stáhl(a) za poslední měsíc dat nebo jestli je Váš počítač povolen v macguardovi přejděte na stránku <a href="/monitoring.php">monitoring</a>.</li>
    <li>Další informace o routeru a použítém softwaru najde na stránce <a href="/dalsi.php">další</a>.</li>
    </ul>
    <br>
    </td>
    </tr>
</table>
</form>
<?php
include 'include/footer.php';
?>
