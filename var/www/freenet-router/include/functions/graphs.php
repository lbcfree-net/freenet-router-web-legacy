<?php

function graphs_create_ip($ip,$period) {
    global $monitoring;

    $file_rrd = "host-$ip.rrd";

    $label = "ip: $ip";
    $low = '0';
    $high = 'no';
    $scale = 'yes';

    if ($monitoring['rate_units'] == 'bits') {
        $v_label = 'rychlost [bits/s]';
        $base = '1000';
        $units = 'b/s';
        $multiply = '8';
    }
    else{
        $v_label = 'rychlost [bytes/s]';
        $base = '1024';
        $units = 'B/s';
        $multiply = '';
    }

    /* velké S zajistí aby byly všechny ostatní jednotky stejné, malé s opak */
    $data[] = array('out', '87B1EE', 'odesláno', '003482', '', true, true, true, true, '%6.2lf %s'.$units);
    $data[] = array('in', 'FE962F', 'přijato', '854200', '', true, true, true, true, '%6.2lf %s'.$units);

    return graphs_create($file_rrd, $period, $label, $low, $high, $scale, $v_label, $base, $multiply, $data);
}

function graphs_create_interface($interface,$period) {
    global $monitoring;

    $ADAPTER_ALL = 'vše';
    $file_rrd = 'interface-' . (($interface != $ADAPTER_ALL) ? $interface : 'all') . '.rrd';

    $label = "rozhraní: $interface";
    $low = '0';
    $high = 'no';
    $scale = 'yes';
    $v_label = 'rychlost [bytes/s]';
    $base = '1024';
    $units = 'B/s';

    if ($monitoring['rate_units'] == 'bits') {
        $v_label = 'rychlost [bits/s]';
        $base = '1000';
        $units = 'b/s';
        $multiply = '8';
    }

    if ($interface == $ADAPTER_ALL) {
        $data[] = array('all', 'FE962F', 'celkem', '854200', '', true, true, true, true, '%6.2lf %s' . $units);
    } else {
        $data[] = array('out', '87B1EE', 'odesláno', '003482', '', true, true, true, true, '%6.2lf %s' . $units);
        $data[] = array('in', 'FE962F', 'přijato', '854200', '', true, true, true, true, '%6.2lf %s' . $units);
    }

    return graphs_create($file_rrd, $period, $label, $low, $high, $scale, $v_label, $base, $multiply, $data);
}

function graphs_create_cpu($cpu, $period) {
    $file_rrd = 'system.rrd';
    $label = "cpu: $cpu";
    $low = '0';
    $high = '100';
    $scale = 'no';
    $v_label = 'vytížení [%]';
    $base = '1000';
    $multiply = '';

    if ($cpu == 'all') $cpu = '';

    $data[] = array('cpu' . $cpu . '_load',         'FE962F', 'celkové vytížení cpu', '854200', '',
        true, true, true, true, '%5.1lf %%');
    $data[] = array('cpu' . $cpu . '_load_system',  '',       '',                     '003482', 'systémové   aplikace',
        true, true, true, true, '%5.1lf %%');
    $data[] = array('cpu' . $cpu . '_load_user',    '',       '',                     '0D9D3E', 'uživatelské aplikace',
        true, true, true, true, '%5.1lf %%');
    $data[] = array('cpu' . $cpu . '_load_hardirq', '',       '',                     'FBF54E', 'hardwarová přerušení',
        true, true, true, true, '%5.1lf %%');
    $data[] = array('cpu' . $cpu . '_load_softirq', '',       '',                     '7E103C', 'softwarová přerušení',
        true, true, true, true, '%5.1lf %%');

    return graphs_create($file_rrd, $period, $label, $low, $high, $scale, $v_label, $base, $multiply, $data);
}

function graphs_create_memory($period) {
    $file_rrd = 'system.rrd';
    $total_memory = (exec("grep MemTotal /proc/meminfo | awk '{print $2}'") * 1024);
    $label = 'využití operační paměti';
    $low = '0';
    $high = $total_memory;
    $scale = 'no';
    $v_label = 'využití [B]';
    $base = '1024';
    $multiply = '';

    $data[] = array('mem_used',    'FE962F', 'celkové obsazení operační paměti', '854200',
        '',                                     false, true, true, true, '%6.2lf %SB');
    $data[] = array('mem_buffers', '',       '',                                 '003482',
        'krátkodobá paměť pro diskové operace', false, true, true, true, '%6.2lf %SB');
    $data[] = array('mem_cached',  '',       '',                                 '0D9D3E',
        'cache pro často používané soubory',    false, true, true, true, '%6.2lf %SB');
    $data[] = array('mem_active',  '',       '',                                 'FBF54E',
        'obsazená paměť využívaná velmi často', false, true, true, true, '%6.2lf %SB');
    $data[] = array('mem_inact',   '',       '',                                 '7E103C',
        'obsazená paměť nevyužívaná často',     false, true, true, true, '%6.2lf %SB');
    $data[] = array('mem_mapped',  '',       '',                                 '666666',
        'namapované soubory, hlavně knihovny',  false, true, true, true, '%6.2lf %SB');
    $data[] = array('mem_slab',    '',       '',                                 '7E13EC',
        'vyrovnávací paměť pro součásti jádra', false, true, true, true, '%6.2lf %SB');

    $rules[] = array('H', $total_memory, '000000');

    return graphs_create($file_rrd, $period, $label, $low, $high, $scale, $v_label, $base, $multiply, $data, $rules);
}

function graphs_create_swap($period) {
    $file_rrd = 'system.rrd';
    $total_swap = (exec("grep SwapTotal /proc/meminfo | awk '{print $2}'") * 1024);
    $label = 'využití swap paměti';
    $low = '0';
    $high = $total_swap;
    $scale = 'no';
    $v_label = 'využití [B]';
    $base = '1024';
    $multiply = '';

    $data[] = array('swap_used',  'FE962F', 'obsazení swap paměti', '854200', '', true,  true,  true,  true, '%3.0lf %SB');
    $data[] = array('swap_total', '',       '',                     '000000', '', false, false, false, false,'%3.0lf %SB');

    return graphs_create($file_rrd, $period, $label, $low, $high, $scale, $v_label, $base, $multiply, $data);
}


function graphs_create_drive($drive,$period,$tmpfs = false) {
    /* jen hlavní disky jsou v systémovém grafu */
    if (($drive == 'root') || ($drive == 'tmpfs') || ($drive == 'tmpfss')) {
        $file_rrd = 'system.rrd';
        $prefix = $drive . '_';
    } else if ($tmpfs) {
        $file_rrd = 'tmpfs-' . $drive . '.rrd';
        $prefix = '';
    } else {
        $file_rrd = 'drive-' . $drive . '.rrd';
        $prefix = '';
    }

    $label = "disk: $drive";
    $low = '0';
    $high = 'no';
    $scale = 'yes';
    $v_label = 'využití [B]';
    $base = '1024';
    $multiply = '';

    $data[] = array($prefix . 'used',  'FE962F', 'využité místo', '854200', '', true,  true,  true,  true,  '%5.1lf %SB');
    $data[] = array($prefix . 'total', '',       '',              '000000', '', false, false, false, false, '%5.1lf %SB');

    return graphs_create($file_rrd, $period, $label, $low, $high, $scale, $v_label, $base, $multiply, $data);
}

function graphs_create_tmpfs($drive,$period) {
    return graphs_create_drive($drive,$period,true);
}

function graphs_create_users($period) {
    $file_rrd = 'system.rrd';
    $label = 'počet aktivních počítačů';
    $low = '0';
    $high = 'no';
    $scale = 'yes';
    $v_label = 'počet počítačů [-]';
    $base = '1000';
    $multiply = '';

    $data[] = array('active_ips', 'FE962F', 'počet aktivních počítačů', '854200', '', true, true, true, true, '%3.0lf');

    return graphs_create($file_rrd, $period, $label, $low, $high, $scale, $v_label, $base, $multiply, $data);
}

function graphs_create_ping($ip,$period) {
    $file_rrd = 'ping-' . $ip . '.rrd';

    $label = "ping na ip: $ip";
    $low = '0';
    $high = 'no';
    $scale = 'yes';
    $v_label = 'odezva [ms]';
    $base = '1000';
    $multiply = '';

    $data[] = array('response', 'FE962F', 'odezva', '854200', '', true, true, true, true, '%6.2lf ms');

    return graphs_create($file_rrd, $period, $label, $low, $high, $scale, $v_label, $base, $multiply, $data);
}

function graphs_create_signal($mac,$period) {
    global $login;
    global $monitoring;

    $file_rrd = 'signal-' . str_replace(':','-', $mac) . '.rrd';

    $label = 'signál mac: ' . ((($monitoring['show_mac']) || ($login)) ? $mac : '------------');
    $low = '-100';
    $high = '-40';
    $scale = 'no';
    $v_label = 'signál [dB]';
    $base = '1000';
    $multiply = '';

    $data[] = array('signal', '', '', '000000', 'signál', true, true, true, true, '%5.1lf dB');

    $rules[] = array('H', '-92', 'FF0000');

    return graphs_create($file_rrd, $period, $label, $low, $high, $scale, $v_label, $base, $multiply, $data, $rules);
}

function graphs_create($file_rrd, $period, $label, $low, $high, $scale, $v_label, $base, $multiply, $data, $rules = []) {
    $file_rrd = '/var/log/account/rrd/' . $file_rrd;

    $exec = 'rrdtool graph - ';

    switch ($period) {
        case 'hourly':
            $exec .= "-t \"".$label." - hodinový\" ";
            $exec .= "-s now-1h ";
            break;
        case 'daily':
            $exec .= "-t \"".$label." - denní\" ";
            $exec .= "-s now-1d ";
            break;
        case 'weekly':
            $exec .= "-t \"".$label." - týdenní\" ";
            $exec .= "-s now-1w ";
            break;
        case 'monthly':
            $exec .= "-t \"".$label." - měsíční\" ";
            $exec .= "-s now-1m ";
            break;
        case 'year':
            $exec .= "-t \"".$label." - roční\" ";
            $exec .= "-s now-1y ";
            break;
    }

    /* velikost grafu v px */
    $exec .= '-h 120 -w 700 ';
    /* nejnižší hodnota, nutné pro nulu na y souřadnici */
    if ($low != 'no') $exec.= "-l $low ";
    /* nejvyšší hodnota */
    if ($high != 'no') $exec.= "-u $high ";
    /* vertikální název osy */
    if ($v_label != 'no') $exec.= "-v \"".$v_label."\" ";
    /* nedovolíme scalovaní */
    if ($scale != 'yes') $exec.= '-r ';
    /* pokud je měřena velikost paměti, tak je Kb 1024 bytů, a je potřeba použít parametr b */
    if (($base != '1000') && ($base != '')) $exec .= "-b $base ";
    /* konec pro x souřadnici */
    $exec .= '-e now ';

    foreach ($data as $val) {
        $exec .= 'DEF:' . $val[0] . '=' . $file_rrd . ':' . $val[0] . ':AVERAGE ';
    }

    /* násobení hodnot, hlavně pro převod bytes -> bits */
    if ($multiply != '') {
        foreach ($data as $i => $val) {
            $exec .= 'CDEF:' . $val[0] . '_multi=' . $val[0] . ',' . $multiply . ',* ';
            $data[$i][0] = $val[0] . '_multi';
        }
    }

    /*
     * Pořadí jednotlivých zobrazení je velmi důležité, preferujeme nejprve
     * zobrazení všech area, poté zobrazení čar a os
     */
    foreach ($data as $val) {
        if ($val[1] == "") continue;
        $exec.= "AREA:".$val[0]."#".$val[1];
        if ($val[2] != "") $exec.= ":\"".$val[2]."\\:\t\"";
        $exec.= " ";

        if (($val[2] == "") || !($val[5] || $val[6] || $val[7] || $val[8])) continue;
        if ($val[5]) $exec.= "\"GPRINT:".$val[0].":MIN: minimum\\: ".$val[9]."\" ";
        if ($val[6]) $exec.= "\"GPRINT:".$val[0].":AVERAGE: průměr\\: ".$val[9]."\" ";
        if ($val[7]) $exec.= "\"GPRINT:".$val[0].":MAX: maximum\\: ".$val[9]."\" ";
        if ($val[8]) $exec.= "\"GPRINT:".$val[0].":LAST: aktuálně\\: ".$val[9]."\" ";
        $exec.= "\"COMMENT:\\n\" ";
    }

    foreach ($data as $val) {
        if ($val[3] == "") continue;
        $exec.= "LINE1:".$val[0]."#".$val[3];
        if ($val[4] != "") $exec.= ":\"".$val[4]."\\:\t\"";
        $exec.= " ";

        if (($val[4] == "") || !($val[5] || $val[6] || $val[7] || $val[8])) continue;
        if ($val[5]) $exec.= "\"GPRINT:".$val[0].":MIN: minimum\\: ".$val[9]."\" ";
        if ($val[6]) $exec.= "\"GPRINT:".$val[0].":AVERAGE: průměr\\: ".$val[9]."\" ";
        if ($val[7]) $exec.= "\"GPRINT:".$val[0].":MAX: maximum\\: ".$val[9]."\" ";
        if ($val[8]) $exec.= "\"GPRINT:".$val[0].":LAST: aktuálně\\: ".$val[9]."\" ";
        $exec.= "\"COMMENT:\\n\" ";
    }

    foreach ($rules as $val) {
        /* násobení hodnot */
        if ($multiply != '') $val[1] = ($val[1] * $multiply);

        if ($val[0] == 'H') {
            $exec .= 'HRULE:' . $val[1] . '#' . $val[2];
        } else if ($val[0] == 'V') {
            $exec .= 'VRULE:' . $val[1] . '#' . $val[2];
        }

        if ($val[3] != '') $exec.= ":\"".$val[3]."\\:\t\"";
        $exec .= ' ';
    }

    return imagecreatefromstring(shell_exec("LANG=\"cs_CZ.UTF-8\" LC_ALL=\"cs_CZ.UTF-8\" " . $exec));
}

?>