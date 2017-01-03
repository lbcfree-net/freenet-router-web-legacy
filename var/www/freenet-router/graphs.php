<?php
include "include/functions/general.php";
include "include/functions/graphs.php";

$ip = $_GET["ip"];
$cpu = $_GET["cpu"];
$memory = $_GET["memory"];
$swap = $_GET["swap"];
$drive = $_GET["drive"];
$tmpfs = $_GET["tmpfs"];
$users = $_GET["users"];
$interface = $_GET["interface"];
$ping = $_GET["ping"];
$signal = $_GET["signal"];

/* stažení obrázku */
if (isset($_REQUEST["download"])) {
    include "include/functions/download.php";

    $period = $_REQUEST["period"];

    if ($ip != "") {
        download_file(graphs_create_ip($ip,$period),"ip-".$ip."-".$period.".png","image");
    } else if ($cpu != "") {
        download_file(graphs_create_cpu($cpu,$period),"cpu-".$cpu."-".$period.".png","image");
    } else if ($memory != "") {
        download_file(graphs_create_memory($period),"memory-".$period.".png","image");
    } else if ($swap != "") {
        download_file(graphs_create_swap($period),"swap-".$period.".png","image");
    } else if ($drive != "") {
        download_file(graphs_create_drive($drive,$period),"drive-".$drive."-".$period.".png","image");
    } else if ($tmpfs != "") {
        download_file(graphs_create_tmpfs($tmpfs,$period),"tmpfs-".$tmpfs."-".$period.".png","image");
    } else if ($users != "") {
        download_file(graphs_create_users($period),"users-".$period.".png","image");
    } else if ($interface != "") {
        download_file(graphs_create_interface($interface,$period),"interface-".$interface."-".$period.".png","image");
    } else if ($ping != "") {
        download_file(graphs_create_ping($ping,$period),"ping-".$ping."-".$period.".png","image");
    } else if ($signal != "") {
        if ((!$monitoring["show_mac"]) && (!$login)) {
            $signal = base64_decode($signal);
            $signal_display = "------------";
        } else {
            $signal_display = str_replace(":","-",$signal);
        }
        download_file(graphs_create_signal($signal,$period),"signal-".$signal_display."-".$period.".png","image");
    }
}

include "include/header.php";

$periods = array("hourly","daily","weekly","monthly");

?>
<table width="100%">
<?php
foreach ($periods as $period) {
?>
    <tr>
    <td>
<?php
    if ($ip != "") {
?>
        <img src="graphs.php?ip=<?= $ip ?>&amp;period=<?= $period ?>&amp;download=1" alt="graf přenosu dat ip adresy" />
<?php
    } else if ($cpu != "") {
?>
        <img src="graphs.php?cpu=<?= $cpu ?>&amp;period=<?= $period ?>&amp;download=1" alt="graf vytížení procesoru" />
<?php
    } else if ($memory != "") {
?>
        <img src="graphs.php?memory=1&amp;period=<?= $period ?>&amp;download=1" alt="graf obsazení operační paměti" />
<?php
    } else if ($swap != "") {
?>
        <img src="graphs.php?swap=1&amp;period=<?= $period ?>&amp;download=1" alt="graf obsazení swap paměti" />
<?php
    } else if ($drive != "") {
?>
        <img src="graphs.php?drive=<?= $drive ?>&amp;period=<?= $period ?>&amp;download=1" alt="graf využití pevného disku" />
<?php
    } else if ($tmpfs != "") {
?>
        <img src="graphs.php?tmpfs=<?= $tmpfs ?>&amp;period=<?= $period ?>&amp;download=1" alt="graf využití tmpfs disku" />
<?php
    } else if ($users != "") {
?>
        <img src="graphs.php?users=1&amp;period=<?= $period ?>&amp;download=1" alt="graf připojených počítačů" />
<?php
    } else if ($interface != "") {
?>
        <img src="graphs.php?interface=<?= rawurlencode($interface) ?>&amp;period=<?= $period ?>&amp;download=1" alt="graf přenosu na rozhraní" />
<?php
    } else if ($ping != "") {
?>
        <img src="graphs.php?ping=<?= $ping ?>&amp;period=<?= $period ?>&amp;download=1" alt="graf odezvy (ping)" />
<?php
    } else if ($signal != "") {
?>
        <img src="graphs.php?signal=<?= rawurlencode($signal) ?>&amp;period=<?= $period ?>&amp;download=1" alt="graf síly signálu" />
<?php
    }
?>
    </td>
    </tr>
<?php
}
?>
</table>
<?php
include "include/footer.php";
?>
