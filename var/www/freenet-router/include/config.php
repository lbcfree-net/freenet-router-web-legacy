<?
# Upřesňující nastavení sítí mimo czela.net

# Název routeru zobrazovaného v názvu stránky
$header["routername"] = "router";

# Výpočet cost zařízení pro ospfd.conf
# fix  - cost je určen podle typu zařízení
# rate - cost je určen podle nastavené rychlosti qosu
$quagga["cost"] = "rate";

# Nastavení dead-interval pro ospfd.conf
# 240 - nastavení používané v síti czela.net
# 0   - defaultní nastavení pro quaggu
$quagga["dead-interval"] = "240";

# Služby, které budou spuštěny pomocí quagga daemona.
# Standardně se spouští jen zebra a ospfd, pro další služby je také
# třeba vytvořit příslušné konfigurační soubory v /etc/quagga!
$quagga["zebra"]  = "yes";
$quagga["bgpd"]   = "no";
$quagga["ospfd"]  = "yes";
$quagga["ospf6d"] = "no";
$quagga["ripd"]   = "no";
$quagga["ripngd"] = "no";
$quagga["isisd"]  = "no";

# Heslo pro správu quaggy pomocí telnetu
$quagga["password"] = "zebra";

# Pokud chceme zobrazovat MAC adresy i nepřihlášeným uživatelům nastavíme na true
$monitoring["show_mac"] = false;

# Jednotky pro zobrazení přenosových rychlostí v grafech a monitoringu, bytes/bits
$monitoring["rate_units"] = "bits"

?>
