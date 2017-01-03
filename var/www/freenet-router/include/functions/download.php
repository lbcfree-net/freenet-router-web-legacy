<?php
/*
 * popis:       Funkce pro stahování souborů
 * typ:         základní
 * verze:       1.0
 */

/*
 * Funkce pro stažení souboru uživatelem, parametry:
 * file         - obsah stahovaného souboru, cesta k stahovanému souboru, nebo obsahuje příkaz pro získání souboru
 * name         - název souboru
 * type         - typ stahování, dostupné možnosti: "image" (obrázek vytvořený pomocí gd), "command" (příkaz), "" (normální)
 * length       - pokud je typ command, tak neznáme dopředu velikost, ale jsme schopni očekávat teoretickou velikost
 * download     - pokud chceme soubor stáhnout jako stream, tak je nutné tento parametr nastavit na true
 * delete       - pokud stahujeme soubor, tak ho po stažení mažeme, standardně false
 */
function download_file($file,$name,$type = "",$length = "",$download = false,$delete = false) {
    $ext = substr($name,(strrpos($name,".") + 1));
    $name = rawurlencode($name);

    header("Pragma: public" );
    header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
    header("Cache-Control: no-store, max-age=0, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);

    /* Zde by měla začínat session, jinak může zlobit stahování v IE */
    header("Cache-Control: private");

    /* Vybereme jestli chceme soubor jen stáhnout jako stream, nebo spracovat prohlížečem */
    if ($download) {
        header("Content-Type: application/octet-stream; name=\"".$name."\"; charset="._encoding);
        header("Content-Disposition: attachment; filename=\"".$name."\"");
    } else {
        header("Content-Type: ".get_mime_type($name)."; charset="._encoding);
        header("Content-Disposition: inline; filename=\"".$name."\"");
    }

    /* Podle typu použijeme příslušný postup stahování dat */
    switch ($type) {
        case "command":
            /*
             * Máme teoreticky 2 možnosti jak zajistit správné fungování flashplayeru
             * 1. vypočítáme teoretickou velikost souboru a ohlásíme jí pomocí Content-Length
             *    - nevýhody: občas může být větší, občas menší, nezajistíme u flashe konstantní datový tok, takže i posun je chybný, největší problémy u krátkých záznamů
             *    - výhody: zobrazuje procenta nahrávání, firefox bufferuje správně, IE se také chová lépe
             * 2. použijeme php pipe a začátek uložíme do bufferu
             *    - nevýhody: IE neukazuje nahraná procenta, není možné se posouvat v tom co máme nahrané, na pomalé lince Firefox spadne, protože nedostane včas data
             *    - výhody: vždy dojde správně až na konec, rychlost startu určuje velikost bufferu
             * Po delším testování vybírám možnost 1
             */
            if (($length) && ($length > 0)) {
                /*
                 * Connection: close je nutné abychom nepokračovali pokud bude špatně spočítaná délka,
                 * toto odstraní zásadní nedostatek 1 možnosti.
                 */
                header("Content-Length: ".$length);
                header("Connection: close");
            }
            /*
             * popen se chová lépe při odpojení uživatele a umožňuje pokročilejší řízení zasílání dat,
             * ale mnohem více zatěžuje web server, hlavně při vyšších přenosových rychlostech nad 5 MB/s
             */
            popen_buffer($file,10000,0,0,(1024*1024));
            //passthru($file);
            break;
        case "ffmpeg_buffer":
            /*
             * Cyklíme v nekonečné smyčce a jen kontrolujeme, jestli byl soubor s daty změněn, pokud ne,
             * tak čekáme na změnu, při každé změně musíme zároveň mazat cache funkcí jako je filemtime.
             *
             * Speciální výjimky pro flash player:
             * + Jakýkoliv soubor by neměl být pro Flash Player větší a roven 2 GB.
             * + Pro přehrávání MP3 souboru je dokonce možné že je ptřeba velikost Content-Length snížit na
             *   maximálně 221183499.
             */
            if ($_REQUEST["flash"]) {
                if (($length >= 2*1024*1024*1024) || !($length > 0)) {
                    $length = (2*1024*1024*1024 - 1);
                }
                /* Pro mp3 soubory nemáme ověřeno toto chování */
                //if ((($length >= 221183500) || !($length > 0)) && ($mime_type == "audio/mpeg")) $length = 221183499;
            }

            if ($length > 0) {
                header("Content-Length: ".$length);
                header("Connection: close");
            }

            /* Hlavička */
            while (true) {
                clearstatcache();
                if (!file_exists($file."_header.bin")) {
                    sleep(1);
                    continue;
                }
                $content = open_file($file."_header.bin");
                echo $content;
                break;
            }

            /* Tělo video souboru, tělo je generováno postupně do bufferu. */
            while (true) {
                clearstatcache();
                if (($mtime == @filemtime($file."_buffer.bin")) || !file_exists($file."_buffer.bin")) {
                    sleep(1);
                    continue;
                }
                $mtime = filemtime($file."_buffer.bin");
                $content = open_file($file."_buffer.bin");
                /* Soubor byl v průběhu čtení změněn? */
                clearstatcache();
                if ($mtime != filemtime($file."_buffer.bin")) {
                    $mtime = filemtime($file."_buffer.bin");
                    $content = open_file($file."_buffer.bin");
                    /*
                     * Soubor byl v průběhu čtení opět změněn, toto by nemělo nastat,
                     * proto ukončíme přehrávání.
                     */
                    clearstatcache();
                    if ($mtime != filemtime($file."_buffer.bin")) break;
                }
                if ($mime_type == "video/x-flv") {
                    //echo flash_update_timestamp($content,&$diff,&$last_timestamp,&$last_body_size,&$fps);
                } else {
                    echo $content;
                }
            }
            break;
        case "file":
            /* Nepoužijeme funkci open_file, protože má mnohem vyšší paměťové nároky a je pomalejší */
            header("Content-Length: ".filesize($file));
            $f = fopen($file,"r");
            while (!feof($f)) {
                echo fgets($f,1000);
            }
            if ($delete) unlink($file);
            break;
        case "image":
            /* na základě názvu souboru zašleme buď jpeg, png, nebo gif */
            if ($ext == "png") {
                imagepng($file);
            } else if ($ext == "gif") {
                imagegif($file);
            } else {
                imagejpeg($file);
            }
            imagedestroy($file);
            break;
        default:
            header("Content-Length: ".strlen($file));
            /*
             * TODO: Měli bychom použít nějaké bufferování, jinak může být stahování velkých souborů pomalé
             */
            echo $file;
            break;
    }

    /* musíme ukončit běh php skriptu */
    exit;
}

/*
 * Touto funkcí mužeme bufferovat zasílaná data z passthru, dále můžeme i limitovat rychlost zasílání
 * dat pomocí funkcí microtime a usleep, parametry:
 * command      - příkaz který spustíme
 * buffer       - velikost počátečního bufferu, který naplníme a pak ho teprve zašleme [bytů]
 * max_time     - maximální čas strávený vykonávaný strávený vykonáváním této funkce [s]
 * max_size     - maximální velikost souboru, který zašleme [bytů]
 * max_rate     - maximální rychlost zasílání dat [bytů/s]
 */
function popen_buffer($command,$buffer = "1024",$max_time = 0,$max_size = 0,$max_rate = 0) {
    $start_time = microtime(true);
    $size = 0;
    $last_size = 0;
    /* při nastavení limitu rychlosti provedeme každých přenesených 10 KB kontrolu a případné zpomalení */
    $limit_step = 10 * 1024;

    $output = "";
    $str = "";
    $sem = true;

    /* pokusíme se otevřít pipe */
    if (!($pipe = popen($command,"r"))) return false;

    /* načítáme data až do konce */
    while (!feof($pipe)) {
        /* základní a primitivní kontrola na překročení maximálního času */
        if (($max_time > 0) && (($start_time + $max_time) < microtime(true))) break;

        /* základní a primitivní kontrola na překročení maximální velikosti zasílaných dat */
        if (($max_size > 0) && ($size > $max_size)) break;

        /* základní a primitivní omezení rychlosti zasílaných dat */
        if (($max_rate > 0) && (!$sem) && ($size >= ($last_size + $limit_step))) {
            /* spočítáme čas pro přenesení jednoho stringu */
            $comp_time = ($size - $last_size) / $max_rate;
            $time_diff = microtime(true) - $last_time;
            /* vyčkáme vypočítaný počet mikrosekund */
            if (($last_time > 0) && ($time_diff > 0) && ($comp_time > $time_diff)) usleep(round((($comp_time - $time_diff) * 1000000),0));
            $last_size = $size;
            $last_time = microtime(true);
        }

        /* data načítáme po 1024 bytech, ale u roury se ne vždy načte opravdu tolik dat! */
        $str = fgets($pipe,1024);

        /* pokud ještě nebyl zapsán buffer, tak ho naplňujeme a až po naplnění zapíšeme */
        if ($sem) {
            $output .= $str;
            if (strlen($output) >= $buffer) {
                echo $output;
                $sem = false;
            }
        } else {
            echo $str;
        }

        /* vypočítáváme velikost souboru v bytech */
        $size += strlen($str);
    }

    /*
     * Celý výstup z roury byl menší, než buffer, takže nebyl vůbec vypsán,
     * proto ho vypíšeme až teď.
     */
    if ($sem) echo $output;

    /* zavřeme pipe */
    return pclose($pipe);
}

/* Rozpoznání základních typů souborů na základě koncovky */
function get_mime_type($name) {
    /* poslední tečku bereme jako oddělující název od koncovky */
    $ext = substr($name,(strrpos($name,".") + 1));
    switch ($ext) {
        case "asf":
            return "video/x-ms-asf";
        case "avi":
            return "video/x-msvideo";
        case "flv":
            return "video/x-flv";
        case "mp3":
            return "audio/mpeg";
        case "mp4":
            return "video/mp4";
        case "mpg":
            return "video/mpeg";
        case "ogg":
            return "application/ogg";
        case "jpg":
            return "image/jpeg";
        case "png":
            return "image/png";
        case "wmv":
            return "video/x-ms-wmv";
        default:
            return "application/".$ext;
    }
}

/*
 * Funkce pro stáhnutí konkrétní stránky, parametry:
 * page         - adresa stránky, kterou stahujeme
 * post         - pole s POST parametry
 * cookie       - soubor s cookie parametry
 * password     - autorizace uživatel:heslo
 * strip        - pokud chceme odstranit nové řádky
 */
function download_page($page,$post = "",$cookie = "",$password = "",$strip = false) {
    if ($page == "") return false;
    $curl = curl_init();

    /* agent */
    $agent = "Mozilla/5.0 (X11; U; Linux i686; cs; rv:1.8.1.12) Gecko/20080201 Firefox/2.0.0.12";

    /* hlavička */
    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en,cs;q=0.5";
    $header[] = "Pragma: ";

    /* nastavení vlastností */
    curl_setopt($curl,CURLOPT_URL,$page);
    curl_setopt($curl,CURLOPT_USERAGENT,$agent);
    curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
    curl_setopt($curl,CURLOPT_ENCODING,'gzip,deflate');
    curl_setopt($curl,CURLOPT_HEADER,0);
    curl_setopt($curl,CURLOPT_FOLLOWLOCATION,1);
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($curl,CURLOPT_TIMEOUT,100);
    if ($cookie != "") {
        curl_setopt($curl,CURLOPT_COOKIEJAR,$cookie);
        curl_setopt($curl,CURLOPT_COOKIEFILE,$cookie);
    }
    if (is_array($post)) {
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$post);
    }
    if ($password != "") {
        curl_setopt($curl,CURLOPT_HTTPAUTH,CURLAUTH_ANY);
        curl_setopt($curl,CURLOPT_USERPWD,$password);
    }

    /* otevření stránky */
    $html = curl_exec($curl);
    curl_close($curl);

    /* ořezání výstupu ze stránky o nové řádky */
    if ($strip) return preg_replace("/[\n\r]+/","",$html);

    return $html;
}

/*
 * Uložení obsahu do souboru na disku, parametry:
 * file         - cesta a název souboru
 * body         - obsah souboru
 */
function save_file($file,$body) {
    if (($file == "") || ($body == "")) return false;
    $f = fopen($file,"w");
    fwrite($f,$body);
    fclose($f);
    return true;
}

/*
 * Načtení obsahu souboru z disku, parametry:
 * file         - cesta a název souboru
 * sys          - buď načteme najednou celý soubor (false), nebo ho čteme po částech (true)
 */
function open_file($file,$sys = false) {
    if ($file == "") return false;
    if (!is_readable($file)) return false;
    $f = fopen($file,"r");

    /* Čtení pomocí fgets dovoluje přečíst i speciální soubory */
    if ($sys) {
        $body = "";
        while (!feof($f)) {
            $body .= fgets($f,1024);
        }
    } else {
        $body = fread($f,filesize($file));
    }

    fclose($f);

    /* odstraníme bílé znaky ze začátku a konce textu */
    if ($sys) return trim($body);
    return $body;
}

?>
