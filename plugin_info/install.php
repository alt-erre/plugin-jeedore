<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../core/class/jeedore.class.php';

function jeedore_install() {
    log::add('jeedore', 'debug', "install...");
    exec('sudo chmod 777 /var/www/html/plugins/jeedore/ressources/install_apt.sh');
    exec('sudo chmod 777 /var/www/html/plugins/jeedore/ressources/jeedoredaemon-x86/Rezolv.JeeDore.Server');
    exec('sudo chmod 777 /var/www/html/plugins/jeedore/ressources/jeedoredaemon-arm/Rezolv.JeeDore.Server');
    exec('sudo chmod 777 /var/www/html/plugins/jeedore/ressources/jeedoredaemon-arm64/Rezolv.JeeDore.Server');

    config::save('functionality::cron5::enable', 1, 'jeedore');
    config::save('functionality::cron::enable', 1, 'jeedore');

    config::save('jeedomip','localhost', 'jeedore');
    config::save('jeedomport', '80', 'jeedore');
    update_architecure();
    log::add('jeedore', 'debug', "installed");
}

function jeedore_update() {
    log::add('jeedore', 'debug', "update...");
    update_architecure();
}

function jeedore_remove() {
    log::add('jeedore', 'debug', "remove...");
    config::remove('architecture', 'jeedore');
    config::remove('macAddress', 'jeedore');
    config::remove('password', 'jeedore');
    config::remove('jeedomip', 'jeedore');
    config::remove('jeedomport', 'jeedore');
    config::remove('alarmId', 'jeedore');
    config::remove('alarmCode', 'jeedore');
}

function update_architecure(){
    // //detect cpu architecture
    $cmd = "lscpu | grep -i \"^Architecture\" | awk -F\": \" '{print $2}' | head -1 | sed 's/ \+/ /g'";
    $architecture = system($cmd);
    
    if (strpos($architecture, 'x86') !== FALSE || strpos($architecture, 'i686') !== FALSE)
    {
        $architecture = 'x86';
    }
    if (strpos($architecture, 'arm') !== FALSE)
    {
        $architecture = 'arm';
    }
    if (strpos($architecture, 'aarch64') !== FALSE)
    {
        $architecture = 'arm64';
    }
    config::save('architecture', $architecture, 'jeedore');
}
?>
