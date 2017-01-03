<?php
include 'include/functions/general.php';
include 'include/header.php';
include 'include/functions/logs.php';
include 'include/functions/monitoring.php';
?>
<form method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
<?php
$LOG_DIR = "/var/log";
$LOG_FILES = array("auth.log","daemon.log","debug","dmesg","kern.log","mail.log","messages","snmp.log","sudo.log","syslog","user.log");

if (($_GET["show_file"] != "") && ($login)) {
?>
    <br/>
    <table class="bordered">
        <tr>
            <th>soubor</th>
            <th>hodina poslední změny</th>
            <th>den poslední změny</th>
            <th>velikost</th>
        </tr>
<?php
    // najdeme a zobrazíme podobné soubory
    if (preg_match('/\//', $_GET["show_file"])) {
        $BASE_NAME = substr(strrchr($_GET["show_file"],'/'),1);
        $POM_DIR = substr($_GET["show_file"],0,strrpos($_GET["show_file"], '/'))."/";
    } else {
        $BASE_NAME = $_GET["show_file"];
    }
    if (preg_match('/\./',$BASE_NAME)) {
        $BASE_NAME = substr($BASE_NAME,0,strpos($BASE_NAME, '.'));
    }
    if (is_dir($LOG_DIR."/".$POM_DIR)) {
        $dir = opendir($LOG_DIR."/".$POM_DIR);
        while ($FILE = readdir($dir)) {
            if (is_file($LOG_DIR."/".$POM_DIR.$FILE) && (eregi($BASE_NAME,$FILE))) {
?>
    <tr>
        <td align="left"><a href="<?= $_SERVER['PHP_SELF']."?show_file=".$POM_DIR.$FILE ?>"><?= $FILE ?></a></td>
        <td><?= date("H:i:s",filemtime($LOG_DIR."/".$POM_DIR.$FILE)) ?></td>
        <td><?= date("d.m.Y",filemtime($LOG_DIR."/".$POM_DIR.$FILE)) ?></td>
        <td><?= convert_units(filesize($LOG_DIR."/".$POM_DIR.$FILE),"bytes","k") ?></td>
    </tr>
<?php
            }
        }
    }
?>
    </table>
    <br/>
    <table class="bordered">
        <tr>
            <td width="75%"><br/></td>
            <td align="right" width="25%">hledat: <input title="zadejte hledaný výraz" type="text" name="find" value="<?= $_GET["find"] ?>" size="28"><input type="hidden" name="show_file" value="<?= $_GET["show_file"] ?>"></td>
        </tr>
    </table>
    <br/>
    <table width="100%" class="bordered">
        <tr>
            <th><a href="<?= $_SERVER['PHP_SELF'] ?>" class="sort_link"><?= $_GET["show_file"] ?></a></th>
        </tr>
        <?= logs_show_log_file($LOG_DIR."/".$_GET["show_file"],$_GET["find"]) ?>
    </table>
<?php
} else if ($login) {
    foreach ($LOG_FILES as $FILE) {
?>
    <br/>
    <table class="bordered">
        <tr>
            <th><a href="<?= change_url("show_file",$FILE) ?>" class="sort_link"><?= $FILE ?></a></th>
        </tr>
        <?= logs_show_part_of_log_file($LOG_DIR."/".$FILE,"10") ?>
    </table>
<?php
    }
}
?>
</form>
<?php
include 'include/footer.php';
?>
