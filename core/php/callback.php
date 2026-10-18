<?php
/**
 * Point d'entrée HTTP de retour (Callback) appelé par le démon .NET (Rezolv.JeeDore.Server).
 *
 * Le démon .NET maintient la connexion WebSocket permanente avec la box Delta Dore Tydom
 * et pousse vers ce script (en POST JSON) tous les événements, états, équipements découverts
 * et historiques en précisant l'action via le paramètre GET `cmd` :
 *  - `geteqlogic`            : Création ou mise à jour des équipements et scénarios Tydom.
 *  - `getcmd`                : Création/mise à jour de la structure des commandes d'un équipement.
 *  - `getcmddata`            : Mise à jour en temps réel des valeurs des commandes (états).
 *  - `box`                   : Création/mise à jour de l'équipement représentant la box Tydom.
 *  - `gethistoric`           : Enregistrement d'un historique brut de valeurs datées.
 *  - `gethistorictranslated` : Enregistrement d'un historique traduit via modèle (ex: événements alarme).
 *  - `getinstant`            : Mise à jour de valeurs instantanées avec historisation immédiate.
 *  - `pingBeat`              : Battement de cœur (heartbeat) confirmant que la connexion Tydom est active.
 */
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
require_once dirname(__FILE__) . "/../class/jeedoreDaemon.class.php";
require_once dirname(__FILE__) . "/../class/jeedore.class.php";

// Lecture et décodage de la charge utile JSON envoyée en POST par le démon .NET
$rawInput = file_get_contents('php://input');
$daemon = json_decode($rawInput);

// =========================================================================
// 1. Découverte et synchronisation des équipements et scénarios (geteqlogic)
// =========================================================================
if($_GET["cmd"] == 'geteqlogic'){
    foreach($daemon as $k => $endpoint){
        log::add ('jeedore','debug',"Get from daemon :".$endpoint->usage." with id:".$endpoint->id);
        
        // Cas particulier : scénario Tydom (création de l'équipement et de sa commande d'exécution "play")
        if($endpoint->usage == "scenario"){
            $eqLogic = jeedore::byLogicalId($endpoint->id, 'jeedore');
            $createPlayCommand = false;
            if(!is_object($eqLogic)){
                log::add ('jeedore','debug',"Adding new eqLogic-scenario :".$endpoint->id);
                $eqLogic = new jeedore();
                $eqLogic->setLogicalId($endpoint->id);
                $createPlayCommand = true;
                $eqLogic->setName($endpoint->name);
                $eqLogic->setIsEnable(1);
                $eqLogic->setIsVisible(1);
                $eqLogic->setCategory('automatism', 1);
                $eqLogic->setEqType_name('jeedore');
                $eqLogic->setTags($endpoint->usage);
                $eqLogic->save();
            }
            if($createPlayCommand){
                $cmd = new jeedoreCmd();
                $cmd->setEqLogic_id($eqLogic->getId());
                $cmd->setLogicalId("play".$eqLogic->getLogicalId());
                $cmd->setConfiguration('type','commands');
                $cmd->setName('play');
                $cmd->setType('action');
                $cmd->setSubType('slider');
                $cmd->setOrder(1);
                $cmd->setEqLogic($eqLogic);
                $cmd->save();
            }
        }
        // Cas général : équipement standard Tydom (volet, lumière, prise, alarme, chauffage, ouvrant, etc.)
        else {
            $eqLogic = jeedore::byLogicalId($endpoint->id, 'jeedore');
            if(!is_object($eqLogic)){
                // Nouvel équipement : initialisation avec le nom et l'état par défaut
                log::add ('jeedore','debug',"Adding new eqLogic-".$endpoint->usage." :".$endpoint->id);
                $eqLogic = new jeedore();
                $eqLogic->setLogicalId($endpoint->id);
                $eqLogic->setName($endpoint->name);
                $eqLogic->setIsEnable(1);
                $eqLogic->setIsVisible(1);
                $eqLogic->setCategory('automatism', 1);
            } else if ($eqLogic->getTags() !== $endpoint->usage) {
                // Équipement existant : journalise le changement d'usage (catégorie Tydom) sans écraser le nom ni l'objet parent
                log::add('jeedore', 'debug', "Updating usage tag for eqLogic " . $endpoint->id . " from '" . $eqLogic->getTags() . "' to '" . $endpoint->usage . "'");
            }
            // Mise à jour systématique du tag (catégorie d'usage Tydom : light, plug, shutter, etc.)
            $eqLogic->setTags($endpoint->usage);
            $eqLogic->setEqType_name('jeedore');
            if($endpoint->usage == 'hvac-central'){
                $eqLogic->setConfiguration('hvacids', $endpoint->configuration);
            }
            $eqLogic->save();
        }
    }
}

// =========================================================================
// 2. Mise à jour des valeurs d'état des commandes (getcmddata)
// =========================================================================
if($_GET["cmd"] == 'getcmddata'){
    
    $hvacCentral = jeedore::byLogicalId('hvac-central', 'jeedore');
    foreach($daemon as $k => $endpoint){
        $cmds = jeedoreCmd::byLogicalId($endpoint->cmd);
        if(!$cmds)
        {
            log::add ('jeedore','error',"Impossible to find command with id : ".$endpoint->cmd);
            continue;
        }
        
        foreach ($cmds as $cmd){
            $conf = $cmd->getConfiguration('conf');
            if(isset($endpoint->validity))
            {
                $cmd->setConfiguration('validity', $endpoint->validity);
            }
            $cmd->setValue($endpoint->value);


            // Masque automatiquement les zones d'alarme non utilisées ("UNUSED")
            $logicalIdPrefix = substr($cmd->getLogicalId(), 0, 10);
            if($endpoint->value === "UNUSED" &&
                (
                $logicalIdPrefix === "zone1State" ||
                $logicalIdPrefix === "zone2State" ||
                $logicalIdPrefix === "zone3State" ||
                $logicalIdPrefix === "zone4State" ||
                $logicalIdPrefix === "zone5State" ||
                $logicalIdPrefix === "zone6State" ||
                $logicalIdPrefix === "zone7State" ||
                $logicalIdPrefix === "zone8State")
            ){
                $cmd->setIsVisible(false);
            }

            $cmd->save();
            // Notifie Jeedom de la nouvelle valeur (déclenche les scénarios, widgets, etc.)
            $cmd->event($endpoint->value);

            // Agrégation de l'état global pour le chauffage central (hvac-central) si toutes les zones concordent
            if($hvacCentral)
            {
                $cmdstocheck = array('hvacMode', 'authorization');
                foreach($cmdstocheck as $cmdtocheck)
                {
                    $summary = null;
                    $cmdToSummary = $hvacCentral->getCmd('info', $cmdtocheck.'hvac-central');
                    if(!$cmdToSummary){
                        log::add ('jeedore','info',"Command". $cmdtocheck.'hvac-central'. " not found");
                        continue;
                    }
                    $hvacids = $cmdToSummary->getConfiguration('conf');

                    if(is_array($hvacids))
                    {
                        foreach($hvacids as $index => $hvacid){
                            $logicalId = $cmdtocheck.$hvacid;
                            $cmdToCheck = jeedoreCmd::byLogicalId($logicalId, 'info');
                        
                            if(!isset($cmdToCheck) || count($cmdToCheck) == 0){
                                continue;
                            }
                            $cmdToCheck = $cmdToCheck[0];
                            $validity = $cmdToCheck->getConfiguration('validity');
                            if($validity == 'upToDate')
                            {
                                if(!isset($summary)){
                                    $summary = $cmdToCheck->getValue();
                                }else{
                                    if($summary != $cmdToCheck->getValue()){
                                        $summary = false;
                                    }
                                }
                            }
                        }
                    }
                    if($summary)
                    {
                        //log::add ('jeedore','info',"Set value:".$summary." for command:".$cmdToSummary->getName());
                        $cmdToSummary->setValue($summary);
                        $cmdToSummary->save();
                    }
                }
            }
           
        }
    }
}

// =========================================================================
// 3. Création et configuration des commandes d'équipements (getcmd)
// =========================================================================
$links = array();
if($_GET["cmd"] == 'getcmd'){
    foreach($daemon as $k => $endpoint){
        $eqLogic = jeedore::byLogicalId($endpoint->id, 'jeedore');
        if(!is_object($eqLogic)){
            log::add ('jeedore','debug',"Eqlogic is not found, probably your equipment is not yet implemented...");
            continue;
        }
        $cmdLogicalId = $endpoint->name.$eqLogic->getLogicalId();
        $cmd = $eqLogic->getCmd($endpoint->type, $cmdLogicalId);
        if(!is_object($cmd)){
            log::add ('jeedore','debug',"Adding ".$endpoint->name." command");
            $cmd = new jeedoreCmd();
            $cmd->setEqLogic_id($eqLogic->getId());
            $cmd->setLogicalId($cmdLogicalId);
            $cmd->setConfiguration('type','commands');
            $cmd->setName($endpoint->name);
            $cmd->setOrder(1);
            $cmd->setEqLogic($eqLogic);
            $cmd->setType($endpoint->type);
        }

        // Détection d'une centrale d'alarme : enregistre son identifiant et le besoin d'authentification (code PIN)
        if($endpoint->name == 'setAlarmCmd'){
            log::add ('jeedore','debug',"An alarm is detected");
            $configFile = file_get_contents(jeedore::configuration_file);
            if ($configFile === false) {
                log::add ('jeedore','error',"Impossible to find config file");
                continue;
            }
            $config = json_decode($configFile, true);
            if ($config === null) {
                log::add ('jeedore','error',"Impossible to parse config file");
                continue;
            }
            
            $config["alarmId"] = $endpoint->id;
            $authentificationRequired = (property_exists($endpoint, "configuration") && property_exists($endpoint->configuration, "pwd") && $endpoint->configuration->pwd === "hexstring");
            $config["authentificationRequired"] = $authentificationRequired;
            config::save('authentificationRequired', $authentificationRequired, 'jeedore');
            config::save('alarmId', $endpoint->id, 'jeedore');

            if ($authentificationRequired){
                log::add ('jeedore','debug', 'Setup command to specify required authentification');
            }
            $configContent = json_encode($config);

            file_put_contents(jeedore::configuration_file, $configContent);

            log::add ('jeedore','debug', 'Alarm has been setup');
        }
        // Détection des compteurs de consommation instantanée nécessitant un rafraîchissement périodique (cron)
        if(substr($endpoint->name, 0, 13)  == 'energyInstant'){
            if(
                (substr($endpoint->name, 0, 17)  == 'energyInstantTi1I') ||
                (substr($endpoint->name, 0, 21)  == 'energyInstantTi1I_Max') ||
                (substr($endpoint->name, 0, 21)  == 'energyInstantTi1I_Min') ||
                (substr($endpoint->name, 0, 17)  == 'energyInstantTi1P') ||
                (substr($endpoint->name, 0, 21)  == 'energyInstantTi1P_Max') ||
                (substr($endpoint->name, 0, 21)  == 'energyInstantTi1P_Min') ||
                (substr($endpoint->name, 0, 20)  == 'energyInstantTotElec') ||
                (substr($endpoint->name, 0, 21)  == 'energyInstantTotElecP') ||
                (substr($endpoint->name, 0, 24)  == 'energyInstantTotElec_Max') ||
                (substr($endpoint->name, 0, 23)  == 'energyInstantTotElec_Min') ||
                (substr($endpoint->name, 0, 25)  == 'energyInstantTotElec_P_Max') ||
                (substr($endpoint->name, 0, 25)  == 'energyInstantTotElec_P_Min')
            ){
            }else{

                log::add ('jeedore','debug',"Energy instant is detected");
                $cmdConfig = array("CommandName" => "energyInstant");
    
                $parameters["deviceId"] = $endpoint->id;
                $parameters["unit"] = substr($endpoint->name, 14);
                $cmdConfig["parameters"] = $parameters;
                $cmdConfigs = array($cmdConfig);
                $configContent = json_encode($cmdConfigs);
    
                file_put_contents(jeedore::cronFilename, $configContent);
            }
        }

        // Application des métadonnées de la commande (valeurs possibles, bornes min/max, unité, historisation, type générique)
        if(property_exists($endpoint, "listValue")){
            $cmd->setConfiguration('listValue', $endpoint->listValue);
        }
        if(property_exists($endpoint, "min")){
            $cmd->setConfiguration('minValue', $endpoint->min);
        }
        if(property_exists($endpoint, "max")){
            $cmd->setConfiguration('maxValue', $endpoint->max);
        }
        if(property_exists($endpoint, "step")){
            $cmd->setConfiguration('step', $endpoint->step);
        }
        if(property_exists($endpoint, "unit")){
            $cmd->setUnite($endpoint->unit);
        }
        if(property_exists($endpoint, "isHistorized")){
            $cmd->setIsHistorized($endpoint->isHistorized);
        }
        if(property_exists($endpoint, "configuration")){
            $cmd->setConfiguration('conf', $endpoint->configuration);
        }
        if(property_exists($endpoint, "historizeMode")){
            $cmd->setConfiguration('historizeMode', $endpoint->historizeMode);
        }
        if(property_exists($endpoint, "generic_type")){
            $cmd->setGeneric_type($endpoint->generic_type);
        }
        $cmd->setSubType($endpoint->subType);
        
        // Inscription des équipements de suivi énergétique aux tâches planifiées (cronHourly / cronDaily)
        if(substr($endpoint->name, 0, 11)  == 'energyHisto'){
            AddCron($endpoint, 'energyHisto');
        }
        if(substr($endpoint->name, 0, 11)  == 'energyIndex'){
            AddCron($endpoint, 'energyIndex');
        }
        if(substr($endpoint->name, 0, 13)  == 'energyDistrib'){
            AddCron($endpoint, 'energyDistrib');
        }
        $cmd->save();
        if(property_exists($endpoint, "linked_to")){
            $links[$cmd->getId()] = $endpoint->linked_to;
        }
        log::add ('jeedore','debug',"Saved command ".$endpoint->name." for id :".$endpoint->id );
    }
    // Liaison entre les commandes "action" et leurs commandes "info" d'état associées
    foreach($links as $id => $logicalId)
    {
        $info = jeedoreCmd::byLogicalId($logicalId, 'info')[0];
        $action = jeedoreCmd::byId($id);
        if(!is_object($info) || !is_object($action)){
            continue;
        }
        
        $action->setValue($info->getId());
        $action->save();
        log::add ('jeedore','debug','Command linked' );
    }
}

// =========================================================================
// 4. Informations système de la passerelle Tydom (box)
// =========================================================================
if($_GET["cmd"] == 'box'){
    UpsertBox($daemon);
}

// =========================================================================
// 5. Réception d'historiques bruts (gethistoric)
// =========================================================================
if($_GET["cmd"] == 'gethistoric'){
    $historic = new history();
    $applyValueToCmd = is_array($daemon) && count($daemon)>0;
    foreach($daemon as $k => $value){
        $cmds = cmd::byLogicalId($value->cmd, 'info');
        if(!$cmds){
            continue;
        }
        foreach($cmds as $cmd)
        {
            if($applyValueToCmd){
                $cmd->setValue($value->value);
                $cmd->save();
                $cmd->event($value->value, $value->datetime);
            }
            $historic->setCmd_id($cmd->getId());
            $historic->setValue($value->value);
            $historic->setDatetime($value->datetime);
            $historic->save();
        }
    }
}

// =========================================================================
// 6. Réception d'historiques d'événements traduits (gethistorictranslated)
// =========================================================================
if($_GET["cmd"] == 'gethistorictranslated'){
    $applyValueToCmd = is_array($daemon) && count($daemon)>0;
    $forbiddenChars = array(":", " ", "-");

    //mkdir("../../debug/");

    $dbhisto = config::byKey('historytype', 'jeedore');
    if(!isset($dbhisto)) { $dbhisto = 'histo-simple'; }

    foreach($daemon as $k => $value){
        $cmds = cmd::byLogicalId($value->cmd, 'info');

        
        if(!$cmds){
            continue;
        }
        
        foreach($cmds as $cmd)
        {
            $json = json_encode($value->value, true);
            $strs = json_decode($json,true);
            
            $templateKey = $dbhisto."-".$strs["#event#"];
            
            // Récupération du modèle de message personnalisé ou, à défaut, de la traduction par défaut
            $template = config::byKey($templateKey, 'jeedore');
            if($template == ""){
                $template = translate::loadTranslation('jeedore')[$dbhisto][$strs["#event#"]];
            }
            if (config::byKey('language', 'core', 'fr_FR') == 'en_US'){
                $strs['#product#'] =  $strs['#producten_US#'];
            }else{
                $strs['#product#'] =  $strs['#productfr_FR#'];
            }
            
            $text = template_replace($strs, $template);
            $historyCount = count(history::all($cmd->getId(), $value->datetime));

            $cmd->setValue($text);
            $cmd->addHistoryValue($text, $value->datetime);
            if($historyCount == 0){
                $cmd->save();
                $cmd->event($text, $value->datetime);
            }

            $basefilename = str_replace($forbiddenChars, "", $value->datetime);

            $filename = "../../debug/".$strs["#event#"].$basefilename.".txt";
            //file_put_contents($filename,$strs['#debug#']);
        }
    }
}

// =========================================================================
// 7. Réception de mesures instantanées historisées (getinstant)
// =========================================================================
if($_GET["cmd"] == 'getinstant'){
    foreach($daemon as $k => $value){
        $cmds = cmd::byLogicalId($value->cmd, 'info');
        if(!$cmds){
            continue;
        }

        foreach($cmds as $cmd)
        {
            $cmd->setValue($value->value);
            if(property_exists($value, "min")){
                $cmd->setConfiguration('minValue', $value->min);
            }
            if(property_exists($value, "unit")){
                $cmd->setUnite($value->unit);
            }
            if(property_exists($value, "max")){
                $cmd->setConfiguration('maxValue', $value->max);
            }
            $cmd->save();
            $cmd->event($value->value);

            $historic = new history();
            $historic->setCmd_id($cmd->getId());
            $historic->setValue($value->value);
            $historic->setDatetime($value->datetime);
            $historic->save($cmd);
            
        }
    }
}


// =========================================================================
// 8. Battement de cœur (pingBeat) de la passerelle Tydom
// =========================================================================
if($_GET["cmd"]== 'pingBeat'){
    $box = jeedore::byLogicalId($daemon->mac, 'jeedore');
    if(!$box){
        return;
    }
    $cmd = cmd::byEqLogicIdCmdName($box->getId(), 'pingBeat');
    if(is_object($cmd)){
        $cmd->addHistoryValue(1);
    }
}

/**
 * Ajoute l'identifiant d'un équipement dans la configuration Jeedom pour une clé de tâche planifiée (cron).
 *
 * @param object $endpoint Objet équipement/commande envoyé par le démon.
 * @param string $key      Clé de configuration cron (`energyHisto`, `energyIndex`, `energyDistrib`).
 * @return void
 */
function AddCron($endpoint, $key){
    log::add ('jeedore','debug',"Cron adding : ".$endpoint->name." for id :".$endpoint->id );
    
    $id = ";".$endpoint->id.";";
    
    $cron = config::byKey($key, 'jeedore');
    if(!isset($cron)){
        $cron = '';
    }
    if(strpos($cron, $id) === false)
    {
        $cron .= $id;
        config::save($key, $cron, 'jeedore');
        log::add ('jeedore','debug',"Cron added : ".$endpoint->name." for id :".$endpoint->id );
    }
}

/**
 * Crée ou met à jour l'équipement représentant la box Delta Dore Tydom ainsi que ses commandes d'information système.
 *
 * @param object $daemon Données d'état de la passerelle Tydom envoyées par le démon.
 * @return void
 */
function UpsertBox($daemon){
    $box = jeedore::byLogicalId($daemon->mac, 'jeedore');

    if(!is_object($box)){
        log::add ('jeedore','debug',"DeltaDore box does not exists");
        $box = new jeedore();
        $box->setName($daemon->productName);
        $box->setLogicalId($daemon->mac);
        $box->setEqType_name('jeedore');
        $box->setIsEnable(1);
        $box->setIsVisible(1);
        $box->setCategory('automatism', 1);
        $box->setTags('box');
        $box->setCategory('automatism', 1);
        $box->save();
        log::add ('jeedore','debug',"Adding a DeltaDore box");
    }

    if($daemon->{'bddEmpty'}){ $bddEmpty = 1; }else{ $bddEmpty = 0; }
    if($daemon->{'summerOffset'}){ $summerOffset = 1; }else{ $summerOffset = 0; }

    
    UpsertCmdBox($daemon, $box, 'productName',      'other',    $daemon->productName,       false, false);
    UpsertCmdBox($daemon, $box, 'mac',              'other',    $daemon->mac,               false, false);
    UpsertCmdBox($daemon, $box, 'config',           'other',    $daemon->config,            false, false);
    UpsertCmdBox($daemon, $box, 'bddEmpty',         'binary',   $bddEmpty,                  false, false);
    UpsertCmdBox($daemon, $box, 'bddStatus',        'numeric',  $daemon->bddStatus,         false, false);
    UpsertCmdBox($daemon, $box, 'apiMode',          'binary',   $daemon->apiMode,           false, false);
    UpsertCmdBox($daemon, $box, 'mainVersionSW',    'other',    $daemon->mainVersionSW,     false, false);
    UpsertCmdBox($daemon, $box, 'mainId',           'other',    $daemon->mainId,            false, false);
    UpsertCmdBox($daemon, $box, 'mainReference',    'other',    $daemon->mainReference,     false, false);
    UpsertCmdBox($daemon, $box, 'keyVersionSW',     'other',    $daemon->keyVersionSW,      false, false);
    UpsertCmdBox($daemon, $box, 'keyVersionHW',     'other',    $daemon->keyVersionHW,      false, false);
    UpsertCmdBox($daemon, $box, 'keyVersionStack',  'other',    $daemon->keyVersionStack,   false, false);
    UpsertCmdBox($daemon, $box, 'keyReference',     'other',    $daemon->keyReference,      false, false);
    UpsertCmdBox($daemon, $box, 'bootReference',    'other',    $daemon->bootReference,     false, false);
    UpsertCmdBox($daemon, $box, 'bootVersion',      'other',    $daemon->bootVersion,       false, false);
    UpsertCmdBox($daemon, $box, 'tydomDat',         'numeric',  $daemon->tydomDat,          false, false);
    UpsertCmdBox($daemon, $box, 'updateAvailable',  'binary',   $daemon->updateAvailable,   false, false);
    UpsertCmdBox($daemon, $box, 'pingBeat',         'binary',   null,                       false, true);
}

/**
 * Crée ou met à jour une commande d'information spécifique sur l'équipement Box Tydom.
 *
 * @param object  $daemon       Données de la box envoyées par le démon.
 * @param jeedore $box          Instance de l'équipement Box Tydom.
 * @param string  $name         Nom de la commande.
 * @param string  $subType      Sous-type Jeedom (`other`, `binary`, `numeric`).
 * @param mixed   $value        Valeur à affecter à la commande.
 * @param bool    $visible      Visibilité par défaut de la commande.
 * @param bool    $isHistorized Indique si la commande doit être historisée.
 * @return void
 */
function UpsertCmdBox($daemon, $box, $name, $subType, $value, $visible, $isHistorized){
    $cmd = cmd::byEqLogicIdCmdName($box->getId(), $name);

    if(!is_object($cmd)){
        $cmd = new jeedoreCmd();
        $cmd->setEqLogic_id($box->getId());
        $cmd->setLogicalId($daemon->mac);
        $cmd->setIsVisible((int)$visible);
        $cmd->setIsHistorized((int)$isHistorized);
        $cmd->setName($name);
        $cmd->setType('info');
        $cmd->setSubType($subType);
        $cmd->setEqLogic($box);
        $cmd->setConfiguration('type','commands');
    }
    $cmd->setValue($value);
    $cmd->save();
}


?>