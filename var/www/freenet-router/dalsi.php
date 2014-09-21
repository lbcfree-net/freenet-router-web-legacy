<?php
// načteme hlavičku a často používané funkce, create_selection, is_ip_from_subnet
include 'include/functions/general.php';
include 'include/functions/monitoring.php';
include 'include/functions/system.php';
include 'include/header.php';
?>
<?
$CPUINFO = file("/proc/cpuinfo");
$MEMINFO = file("/proc/meminfo");
exec("df",$DISKINFO);
exec("mount",$MOUNTINFO);
?>
<?
if (($_GET["reset_response"]) && ($login)) {
    exec("sudo /bin/rm -f /var/log/account/routers.txt");
}
?>
<table width="100%">
    <tr>
        <td width="50%" valign="top">
            <table class="bordered">
                <tr><th colspan="2">systémové informace</th></tr>
<?
    table_entry("model procesoru",system_get_cpu_model($CPUINFO),true);
    table_entry("frekvence procesoru",system_get_cpu_freq($CPUINFO)." MHz",true);
    table_entry("<a href=\"/graphs.php?cpu=0\">vytížení procesoru</a>",system_get_cpu_usage()."%",true);
    table_entry("počet procesorů",system_get_cpu_count($CPUINFO),true);
    table_entry("velikost operační paměti",system_get_memory_total($MEMINFO)." MB",true);
    table_entry("<a href=\"/graphs.php?memory=0\">velikost volné operační paměti</a>",system_get_memory_free($MEMINFO)." MB",true);
    table_entry("velikost swap paměti",system_get_swap_total($MEMINFO)." MB",true);
    table_entry("velikost volné swap paměti",system_get_swap_free($MEMINFO)." MB",true);
    table_entry("velikost systémového disku",system_get_rootfs_total($DISKINFO)." MB",true);
    table_entry("<a href=\"/graphs.php?drive=root\">volné místo na systémovém disku</a>",system_get_rootfs_free($DISKINFO)." MB",true);
    table_entry("stav systémového disku",system_get_rootfs_status($MOUNTINFO),true);
    table_entry("velikost tmpfs",system_get_tmpfs_total($DISKINFO)." MB",true);
    table_entry("<a href=\"/graphs.php?drive=tmpfs\">volné místo na tmpfs</a>",system_get_tmpfs_free($DISKINFO)." MB",true);
    table_entry("velikost tmpfs pro malé soubory",system_get_tmpfss_total($DISKINFO)." MB",true);
    table_entry("<a href=\"/graphs.php?drive=tmpfss\">volné místo na tmpfs pro malé soubory</a>",system_get_tmpfss_free($DISKINFO)." MB",true);
    table_entry("verze linuxového jádra",system_get_kernel_version(),true);
    table_entry("verze operačního systému",system_get_os_version(),true);
    table_entry("aktuální čas a datum serveru","<span id=\"datum_a_cas\"></span><br>",true);
    table_entry("doba od posledního startu","<span id=\"uptime\"></span><br>",true);
?>
            </table>
        </td>
        <td width="50%" valign="top">

            <table class="bordered">
                <tr><th colspan="5">odezvy routerů</th></tr>
<?
if ($_GET["response_from_all_dummy"]) {
    system_get_ping_response_from_all_dummy();
} else {
    system_get_ping_response_from_defined_routers();
}
?>
                <tr>
<?
if ($_GET["response_from_all_dummy"] || (!$login))  {
?>
                    <td colspan="5"><a href="?response_from_all_dummy=<?= (!$_GET["response_from_all_dummy"]) ?>">zobrazit odezvu jen definovaných routerů</a></td>
<?
} else {
?>
                    <td colspan="3"><a href="?response_from_all_dummy=1">zobrazit odezvu všech dummy routerů</a></td>
                    <td colspan="2"><a href="?reset_response=1">reset dostupnosti</a></td>
<?
}
?>
                </tr>
            </table>
        </td>
    </tr>
</table>
<?
include "include/footer.php";
?>
