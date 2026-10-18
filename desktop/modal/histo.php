<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
require_once dirname(__FILE__) . '/../../core/class/jeedoreDaemon.class.php';

$id = init('id');
sendVarToJS('plugin', $id);

$translation = translate::getTranslation('jeedore');
$histoSimple = $translation['histo-simple'];
$histoAdvanced = $translation['histo-advanced'];
?>
<div id='div_jeedoreRemoteAlert' style="display: none;"></div>

<div class='jeedoreRemote'>

	<a class="btn btn-success jeedoreRemoteAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Save}}</a>

	<ul class="nav nav-tabs" role="tablist">
		<li role="presentation" class="active"><a href="#simple" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Simple}}</a></li>
		<li role="presentation"><a href="#advanced" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-cog"></i> {{Advanced}}</a></li>
		<li role="presentation"><a href="#help" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-cog"></i> {{Help}}</a></li>
	</ul>
	<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
		<div role="tabpanel" class="tab-pane active" id="simple">
			<br/>
			<div class="row">
				<div class="col-sm-12">
				<table id="table_simple" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th>{{Motif}}</th>
							<th>{{Default}}</th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach($histoSimple as $key => $value)
					{?>
					<tr>
							<td><?php echo $key;?></td>
							<td>
								<?php echo $value;?><br/>
								<input type="text" class="eqLogicAttr form-control histo" data-l1key="histo-simple-<?php echo $key;?>" placeholder="{{CustomMessage}}" value="<?php echo config::byKey('histo-simple-'.$key, 'jeedore'); ?>" />
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				</div>
			</div>
		</div>
		<div role="tabpanel" class="tab-pane" id="advanced">
			<br/>
			<div class="row">
				<div class="col-sm-12">
				<table id="table_advanced" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th>{{Motif}}</th>
							<th>{{Default}}</th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach($histoAdvanced as $key => $value)
					{?>
					<tr>
							<td><?php echo $key;?></td>
							<td><?php echo $value;?></td>
							<td><input type="text" class="eqLogicAttr form-control histo" data-l1key="histo-advanced-<?php echo $key;?>" placeholder="{{CustomMessage}}" value="<?php echo config::byKey('histo-advanced-'.$key, 'jeedore'); ?>" /></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				</div>
			</div>
		</div>
		<div role="tabpanel" class="tab-pane" id="help">
			<br/>
			<div class="row">
				<div class="col-sm-12">Les variables suivantes sont communes à tous les évènements</div>
				<div class="col-sm-12">#event# nom de l'évènement levé</div>
				<div class="col-sm-12">#date# la date de l'évenement avec le format dd/MM/yyyy</div>
				<div class="col-sm-12">#time# l'heure de l'évènement avec le format HH:mm:ss</div>
				<div class="col-sm-12">#datetime# la date et l'heure de l'évenement avec le format dd/MM/yyyy HH:mm:ss</div>
				<div class="col-sm-12"><br/></div>
				<div class="col-sm-12">La disponibilité des variables suivantes varie en fonction des commandes et de la configuration des équipements</div>
				<div class="col-sm-12">#nameCustom# retourne soit le nom du produit (le nom de l'utilisateur pour une télécommande), soit le nom préenregistré ou en dernier recours, le nom de l'équipement</div>
				<div class="col-sm-12">#accessCode# nom de l'utilisateur, retourné uniquement lors d'une action par le clavier </div>
				<div class="col-sm-12">#product# nom du produit</div>
				<div class="col-sm-12">#productfr_FR# retourne soit le nom du produit (le nom de l'utilisateur pour une télécommande), soit le nom préenregistré en français ou en dernier recours, le nom de l'équipement</div>
				<div class="col-sm-12">#producten_US# retourne soit le nom du produit (le nom de l'utilisateur pour une télécommande), soit le nom préenregistré en anglais ou en dernier recours, le nom de l'équipement</div>
				<div class="col-sm-12">#user# nom de l'utilisateur</div>
				<div class="col-sm-12">#zones# liste toutes les zones concernées</div>
			</div>
		</div>
	</div>
</div>

<script>


$('.jeedoreRemoteAction[data-action=save]').on('click',function(){
		var jeedoreRemote = $('.jeedoreRemote').getValues('.histo')[0];
		$.ajax({
			type: "POST",
			url: "plugins/jeedore/core/ajax/jeedore.ajax.php",
			data: {
				action: "saveMessage",
				jeedoreRemote: json_encode(jeedoreRemote),
			},
			dataType: 'json',
			error: function (request, status, error) {
				handleAjaxError(request, status, error,$('#div_jeedoreRemoteAlert'));
			},
			success: function (data) {
				if (data.state != 'ok') {
					$('#div_jeedoreRemoteAlert').showAlert({message: data.result, level: 'danger'});
					return;
				}
				$('#div_jeedoreRemoteAlert').showAlert({message: '{{SaveDone}}', level: 'success'});
			}
		});
	});


</script>
