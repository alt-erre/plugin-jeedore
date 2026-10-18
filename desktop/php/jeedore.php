<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../core/class/jeedoreDaemon.class.php';

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('jeedore');
sendVarToJS('eqType', $plugin->getId());
sendVarToJS('eqType', 'jeedore');
$eqLogics = eqLogic::byType($plugin->getId());


$alarms = array();
$hvacs = array();
$lights = array();
$shutters = array();
$others = array();
$windows = array();
$awnings = array();
$notmanageds = array();
$toggles = array();
$doors = array();

usort($eqLogics, function($a, $b) {
    return strcmp($a->getTags() . $a->getName(), $b->getTags() . $b->getName());
});

foreach ($eqLogics as $eqLogic) {
    switch ($eqLogic->getTags())
    {
        case 'alarm':
        case 'motion':
            array_push($alarms, $eqLogic);
            break;
        case 'hvac-boiler':
        case 'hvac-electric':
        case 'hvac-central':
            array_push($hvacs, $eqLogic);
            break;
        case 'light':
            array_push($lights, $eqLogic);
            break;
        case 'toggle':
            array_push($toggles, $eqLogic);
            break;
        case 'belmDoor':
        case 'klineDoor':
        case 'door':
            array_push($doors, $eqLogic);
            break;    
        case 'not-managed':
            array_push($notmanageds, $eqLogic);
            break;
        case 'awning':
            array_push($awnings, $eqLogic);
            break;
        case 'shutter':
        case 'klineShutter':
        case 'swingShutter':
            array_push($shutters, $eqLogic);
            break;
        case 'klineWindowSliding':
        case 'klineWindowOthers':
        case 'klineWindowFrench':
        case 'WindowFrench':
        case 'windowFrench':
        case 'window':
            array_push($windows, $eqLogic);
            break;
        default:
            array_push($others, $eqLogic);
    }
} 

function displayCategory($eqLogics, $title)
{
    echo '<legend><i class="fas fa-table"></i> {{' . $title . '}}</legend>';
    echo '<div class="eqLogicThumbnailContainer">';
    foreach ($eqLogics as $eqLogic) {
        $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
        echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-logical-id="' . $eqLogic->getLogicalId() . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
        echo '<img src="' . $eqLogic->getImageState() . '"/>';
        echo '<br>';
        echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
        echo '</div>';
    }
    echo '</div>';
}

?>
<div class="row row-overflow">
<div class="col-lg-2 col-sm-3 col-sm-4" id="hidCol" style="display: none;">
    <div class="bs-sidebar">
      <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
        <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Find}}" style="width: 100%"/></li>
        <?php
        foreach ($eqLogics as $eqLogic) {
            echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true,true) . '</a></li>';
        }
        ?>
      </ul>
    </div>
  </div>
    <div class="col-xs-12 eqLogicThumbnailDisplay" id="listCol">
        <legend><i class="fas fa-cog"></i> {{Manage equipment}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction logoPrimary" id="bt_synchronizeEquipments">
                <i class="fas fa-bullseye"></i>
                <br />
                <span>{{Get all equipments}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                <i class="fas fa-wrench"></i>
                <br />
                <span>{{Configuration}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" id="bt_editevents">
                <i class="far fa-comments"></i>
                <br />
                <span>{{Manage histo message}}</span>
            </div>
        </div>

        <div class="input-group" style="margin:5px;">
            <input class="form-control roundedLeft" placeholder="{{Search}}" id="in_searchEqlogic" />
            <div class="input-group-btn">
                <a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>
                <a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>
            </div>
        </div>

        <legend><i class="fas fa-table" id="butCol"></i> {{My DeltaDore equipment}}</legend>
        
        <?php
        if(count($alarms)>0){
            displayCategory($alarms, 'Alarm');
        }
        if(count($hvacs)>0){
            displayCategory($hvacs, 'Hvac & Sensor');
        }
        if(count($lights)>0){
            displayCategory($lights, 'Light');
        }
        if(count($shutters)>0){
            displayCategory($shutters, 'Shutter');
        }
        if(count($doors)>0){
            displayCategory($doors, 'Door');
        }
        if(count($windows)>0){
            displayCategory($windows, 'Window');
        }
        if(count($awnings)>0){
            displayCategory($awnings, 'Awning');
        }
        if(count($toggles)>0){
            displayCategory($toggles, 'Toggle');
        }
        if(count($others)>0){
            displayCategory($others, 'Other');
        }
        if(count($notmanageds)>0){
            displayCategory($notmanageds, 'Not managed');
        }
        ?>
    </div>
    <div class="col-xs-12 eqLogic" style="display: none;">
        <div class="input-group pull-right" style="display:inline-flex">
            <span class="input-group-btn">
                <a class="btn btn-success btn-sm eqLogicAction roundedLeft" data-action="identify" data-l1key="id"><i class="fas fa-sync"></i> {{Identify}}</a>
                <a class="btn btn-default btn-sm eqLogicAction" data-action="configure"><i class="fas fa-cogs"></i> {{Advanced configuration}}</a>
                <a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Save}}</a>
                <a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Delete}}</a>
            </span>
        </div>
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay">
                <i class="fas fa-arrow-circle-left"></i></a>
            </li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commands}}</a></li>
        </ul>

        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{DeltaDore equipment name}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{DeltaDore equipment name}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Parent object}}</label>
                            <div class="col-sm-3">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{None}}</option>
                                    <?php
                                    $options = '';
                                    foreach ((jeeObject::buildTree(null, false)) as $object) {
                                        $options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
                                    }
                                    echo $options;
                                    ?>
                               </select>
                           </div>
                       </div>
                       <div class="form-group">
                            <label class="col-sm-3 control-label">{{Category}}</label>
                            <div class="col-sm-9">
                                <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-9">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activate}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>

            <div role="tabpanel" class="tab-pane" id="commandtab">
                <table id="table_commands" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 250px;">{{Name}}</th>
                        <th style="width: 100px;">{{Type}}</th>
                        <th style="width: 100px;">{{Unit}}</th>
                        <th style="width: 150px;">{{Parameters}}</th>
                        <th style="width: 100px;">{{Actions}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_file('desktop', 'jeedore', 'js', 'jeedore');?>
<?php include_file('core', 'plugin.template', 'js');?>


