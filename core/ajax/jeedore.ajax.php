<?php
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
if ($action == 'synchronize') {
    jeedore::synchronizeEquipments();
    ajax::success();
}

if($action == 'shutterState'){
    $id = init('id');
    $eqLogic = jeedore::byId($id);
    if(is_object($eqLogic)){
        ajax::success($eqLogic->getImageState());
    }else{
        ajax::error("plugins/jeedore/ressources/images/error.svg");
    }
}
if($action == 'lightState'){
    $id = init('id');
    $eqLogic = jeedore::byId($id);
    if(is_object($eqLogic)){
        ajax::success($eqLogic->getImageState());
    }else{
        ajax::error("plugins/jeedore/ressources/images/error.svg");
    }
}

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
