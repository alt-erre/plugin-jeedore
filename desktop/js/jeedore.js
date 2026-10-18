$('#bt_synchronizeEquipments').on('click', function () {
    synchronizeEquipments();
});

$("#butCol").click(function(){
    $("#hidCol").toggle("slow");
    document.getElementById("listCol").classList.toggle('col-lg-12');
    document.getElementById("listCol").classList.toggle('col-lg-10');
});

$('#bt_editevents').on('click', function () {
  $('#md_modal2').dialog({title: "{{Manage histo messages}}"});
  $('#md_modal2').load('index.php?v=d&plugin=jeedore&modal=histo&id=jeedore').dialog('open');
});

$(".li_eqLogic").on('click', function (event) {
  if (event.ctrlKey) {
    var type = $('body').attr('data-page')
    var url = '/index.php?v=d&m='+type+'&p='+type+'&id='+$(this).attr('data-eqlogic_id')
    window.open(url).focus()
  } else {
    jeedom.eqLogic.cache.getCmd = Array();
    if ($('.eqLogicThumbnailDisplay').html() != undefined) {
      $('.eqLogicThumbnailDisplay').hide();
    }
    $('.eqLogic').hide();
    if ('function' == typeof (prePrintEqLogic)) {
      prePrintEqLogic($(this).attr('data-eqLogic_id'));
    }
    if (isset($(this).attr('data-eqLogic_type')) && isset($('.' + $(this).attr('data-eqLogic_type')))) {
      $('.' + $(this).attr('data-eqLogic_type')).show();
    } else {
      $('.eqLogic').show();
    }
    $(this).addClass('active');
    $('.nav-tabs a:not(.eqLogicAction)').first().click()
    $.showLoading()
    jeedom.eqLogic.print({
      type: isset($(this).attr('data-eqLogic_type')) ? $(this).attr('data-eqLogic_type') : eqType,
      id: $(this).attr('data-eqLogic_id'),
      status : 1,
      error: function (error) {
        $.hideLoading();
        $('#div_alert').showAlert({message: error.message, level: 'danger'});
      },
      success: function (data) {
        $('body .eqLogicAttr').value('');
        if(isset(data) && isset(data.timeout) && data.timeout == 0){
          data.timeout = '';
        }
        $('body').setValues(data, '.eqLogicAttr');
        if ('function' == typeof (printEqLogic)) {
          printEqLogic(data);
        }
        if ('function' == typeof (addCmdToTable)) {
          $('.cmd').remove();
          for (var i in data.cmd) {
            addCmdToTable(data.cmd[i]);
          }
        }
        $('body').delegate('.cmd .cmdAttr[data-l1key=type]', 'change', function () {
          jeedom.cmd.changeType($(this).closest('.cmd'));
        });

        $('body').delegate('.cmd .cmdAttr[data-l1key=subType]', 'change', function () {
          jeedom.cmd.changeSubType($(this).closest('.cmd'));
        });
        addOrUpdateUrl('id',data.id);
        $.hideLoading();
        modifyWithoutSave = false;
        setTimeout(function(){
          modifyWithoutSave = false;
        },1000)
      }
    });
  }
  return false;
});

$('.eqLogicAction[data-action=identify]').on('click', function () {
  $.ajax({
    type: "POST",
    url: "plugins/jeedore/core/ajax/jeedore.ajax.php",
    data: {
      action: "identify",
      id: $('.eqLogicAttr[data-l1key=id]').value(),
    },
    dataType: 'json',
    error: function (request, status, error) {
        handleAjaxError(request, status, error);
    },
    success: function (data_init) {
      if (data_init.state != 'ok') {
        $('#div_DashboardAlert').showAlert({message: data_init.result, level: 'danger'});
        return;
      }else{
        $('#div_DashboardAlert').showAlert({message: 'tagada' , level: 'danger'});
        return;
      }
    }
  });
})

$("#table_commands").delegate(".listEquipementInfo", 'click', function () {
  var el = $(this);
    jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
    var calcul = el.closest('tr').find('.cmdAttr[data-l1key=configuration][data-l2key=' + el.data('input') + ']');
    calcul.atCaret('insert', result.human);
  });
});
  
$("#table_commands").delegate(".listEquipementAction", 'click', function () {
  var el = $(this);
  var subtype = $(this).closest('.cmd').find('.cmdAttr[data-l1key=subType]').value();
  jeedom.cmd.getSelectModal({cmd: {type: 'action', subType: subtype}}, function (result) {
    var calcul = el.closest('tr').find('.cmdAttr[data-l1key=configuration][data-l2key=' + el.attr('data-input') + ']');
    calcul.atCaret('insert', result.human);
  });
});
  
$("#table_commands").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
      var _cmd = {configuration: {}};
  }
  
  if (init(_cmd.type) == 'info') {
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;"/></td>';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" disabled style="margin-bottom : 5px;" />';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" style="width : 90px;" placeholder="{{Unité}}">';
    tr += '</td><td>';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
    if (_cmd.subType == "string") {
      tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
    }
    if (_cmd.subType == "binary") {
      tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
      tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="display" data-l2key="invertBinary" />{{Inverser}}</label></span>';
    }
    if (_cmd.subType == "numeric") {
      tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
      tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 40%;display : inline-block;"> ';
      tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 40%;display : inline-block;">';
    }
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
      tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
      tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_commands tbody').append(tr);
    $('#table_commands tbody tr:last').setValues(_cmd, '.cmdAttr');
  }

  if (init(_cmd.type) == 'action') {
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<div class="row">';
    tr += '<div class="col-lg-6">';
    tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> Icône</a>';
    tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
    tr += '</div>';
    tr += '<div class="col-lg-6">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
    tr += '</div>';
    tr += '</div>';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="action" disabled style="margin-bottom : 5px;" />';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '" style=""></span>';
    tr += '<input class="cmdAttr" data-l1key="configuration" data-l2key="virtualAction" value="1" style="display:none;" >';
    tr += '</td>';
    tr += '<td>';
    tr += '</td><td>';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="margin-top : 5px;"> ';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="margin-top : 5px;">';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
      tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
      tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_commands tbody').append(tr);
    //$('#table_commands tbody tr:last').setValues(_cmd, '.cmdAttr');
    var tr = $('#table_commands tbody tr:last');
    jeedom.eqLogic.builSelectCmd({
      id:  $('.eqLogicAttr[data-l1key=id]').value(),
      filter: {type: 'info'},
      error: function (error) {
        $('#div_alert').showAlert({message: error.message, level: 'danger'});
      },
      success: function (result) {
        tr.find('.cmdAttr[data-l1key=value]').append(result);
        tr.setValues(_cmd, '.cmdAttr');
        jeedom.cmd.changeType(tr, init(_cmd.subType));
      }
    });

  }
$('body').on('jeedore::found', function (_event,_options) {
  window.location.href = 'index.php?v=d&p=jeedore&m=jeedore&id=' + _options+'&nocache=' + (new Date()).getTime();
});

$('body').on('jeedore::notfound', function (_event,_options) {
  $('#div_alert').showAlert({message: '{{Equipement non trouvé. Veuillez vérifier l\'IP et relancer.}}', level: 'danger'});
});
}


function synchronizeEquipments()
{
  $.ajax({
    type: "POST",
    url: "plugins/jeedore/core/ajax/jeedore.ajax.php",
    dataType: 'json',
    data: {
      action: "synchronize"
    },
    error: function (request, status, error) {
      handleAjaxError(request, status, error);
    },
    success: function (data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({message: data.result, level: 'danger'});
        return;
      }
      $('#div_alert').showAlert({message: 'Commande envoyée. Vous pouvez rafraichir la page.', level: 'info'});
    }
  });
}