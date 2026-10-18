<?php
/**
 * Contrôleur AJAX du plugin JeeDore.
 *
 * Gère les requêtes asynchrones provenant de l'interface web Jeedom (administrateur) :
 *  - `identify`      : Fait clignoter / réagir physiquement un équipement Delta Dore pour le repérer.
 *  - `synchronize`   : Déclenche l'interrogation de la passerelle Tydom via le démon .NET.
 *  - `shutterState`  : Retourne l'image d'état d'un volet roulant.
 *  - `lightState`    : Retourne l'image d'état d'un éclairage.
 *  - `boilerState`   : Retourne l'image d'état, la température et la consigne d'un thermostat/chaudière.
 *  - `electricState` : Retourne l'image d'état, la température, la consigne et le mode d'un radiateur fil pilote.
 *  - `saveMessage`   : Sauvegarde les modèles de messages personnalisés pour l'historique d'événements.
 */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../class/jeedore.class.php';

include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
}

ajax::init();

// $rawInput = file_get_contents('php://input');
// $ajax = json_decode($rawInput);

$action = init('action');

// Identification physique d'un équipement (clignotement / va-et-vient)
if ($action == 'identify') {
    $id = init('id');
    log::add ('jeedore','debug',"identifying".$id );
    $eqLogic = jeedore::byId($id);

    if(is_object($eqLogic)){
        jeedore::identify( $eqLogic->getLogicalId() );
        ajax::success( $eqLogic->getLogicalId() );
        log::add ('jeedore','debug','identified!' );
    }else{
        log::add ('jeedore','debug',"identified!" );
        ajax::error("Impossible to get identify id :" . $id);
    }
}
// Synchronisation complète des équipements et états depuis la box Tydom
if ($action == 'synchronize') {
    jeedore::synchronizeEquipments();
    ajax::success();
}

// Récupération de l'icône d'état d'un volet
if($action == 'shutterState'){
    $id = init('id');
    $eqLogic = jeedore::byId($id);
    if(is_object($eqLogic)){
        ajax::success($eqLogic->getImageState());
    }else{
        ajax::error("plugins/jeedore/ressources/images/error.svg");
    }
}
// Récupération de l'icône d'état d'une lumière
if($action == 'lightState'){
    $id = init('id');
    $eqLogic = jeedore::byId($id);
    if(is_object($eqLogic)){
        ajax::success($eqLogic->getImageState());
    }else{
        ajax::error("plugins/jeedore/ressources/images/error.svg");
    }
}

// Récupération de l'état complet d'un équipement de chauffage / chaudière
if($action == 'boilerState'){
    $id = init('id');

    $eqLogic = jeedore::byId($id);

    if($eqLogic){
        $img = $eqLogic->getImageState();
        if(!$img){
            $boiler->img = "plugins/jeedore/ressources/images/error.svg";
        }else{
            $boiler->img = $img;
        }
        $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'temperature'.$eqLogic->getId());
        if(is_object($cmd)){
            $boiler->temperature = $cmd->getValue();
        }
        $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'setPoint'.$eqLogic->getId());
        if(is_object($cmd)){
            $boiler->setPoint = $cmd->getValue();
        }
        ajax::success($boiler);
    }else{
        $boiler->img = "plugins/jeedore/ressources/images/error.svg";
        $boiler->temperature = '-';
        $boiler->setPoint = 0;
        ajax::error($boiler);
    }
}
// Récupération de l'état complet d'un chauffage électrique (fil pilote)
if($action == 'electricState'){
    $id = init('id');

    $eqLogic = jeedore::byId($id);



    if(!$eqLogic){
        ajax::error("plugins/jeedore/ressources/images/error.svg");
    }else{
        $electric = new object();
        $electric->img = $eqLogic->getImageState();
        log::add('jeedore', 'debug', 'la#');
        $electric->temperature = $eqLogic->getLogicalId();
        $electric->setPoint = '-';
        $electric->thermiclevel = '-';
        

        $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'temperature'.$eqLogic->getLogicalId());
        if(!$cmd){
        }else{
            $electric->temperature = $cmd->getValue();
        }
        
        $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'setPoint'.$eqLogic->getLogicalId());
        if(!$cmd){
        }else{
            $electric->setPoint = $cmd->getValue();
        }

        $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'thermiclevel'.$eqLogic->getLogicalId());
        if(!$cmd){
        }else{
            $electric->thermiclevel = $cmd->getValue();
        }
        ajax::success($electric);
    }
}

// Sauvegarde des modèles de messages personnalisés pour l'historique d'alarme
if($action == 'saveMessage'){
    try {
        $messages = jeedom::fromHumanReadable(json_decode(init('jeedoreRemote'), true));

        foreach($messages as $key => $message){
            if($message == ''){
                config::remove($key, 'jeedore');
            }else{
                config::save($key, $message, 'jeedore');
            }
        }
        ajax::success();
    } catch (Exception $e) {
        ajax::error(displayExeption($e), $e->getCode());
    }
}

?>
