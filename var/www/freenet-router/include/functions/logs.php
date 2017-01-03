<?php
function logs_show_part_of_log_file($FILE,$LAST_LINES) {
    $array = array();
    if (is_file($FILE) && (filesize($FILE) > 0)) {
	if (is_readable($FILE)) {
	    $file = file($FILE);
	} else {
	    exec("sudo /bin/cat ".$FILE,$file);
	}
	$I = (sizeof($file) - $LAST_LINES);
	while ($I < sizeof($file)) {
	    $array[] = $file[$I];
	    $I++;
	}
	krsort($array);
	foreach ($array as $line) {
?>
	    <tr><td align="left"><?= htmlspecialchars($line) ?></td></tr>
<?php
	}
    }
}

function logs_show_log_file($FILE,$FIND) {
    if (is_file($FILE) && (filesize($FILE) > 0)) {
	if (substr(strrchr($FILE, '.'), 1) != "gz") {
	    if (is_readable($FILE)) {
		$file = file($FILE);
	    } else {
		exec("sudo /bin/cat ".$FILE,$file);
	    }
	} else {
	    if (is_readable($FILE)) {
		exec("cat ".$FILE." | gunzip -c",$file);
	    } else {
		exec("sudo /bin/cat ".$FILE." | gunzip -c",$file);
	    }
	}
	krsort($file);
	foreach ($file as $line) {
            if ($FIND != "") {
                if (!mb_eregi($FIND,$line)) continue;
            }
?>
            <tr><td align="left"><?= htmlspecialchars($line) ?></td></tr>
<?php
        }
    }
}
?>
