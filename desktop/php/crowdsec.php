<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('crowdsec');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoSecondary" data-action="add">
				<i class="fas fa-plus-circle"></i><br><span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i><br><span>{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-table"></i> {{Mes équipements}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement CrowdSec trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $eqLogic->getImage() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div>

	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span></a>
				<a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
				<a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											echo '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>

							<legend><i class="fas fa-plug"></i> {{Connexion à la LAPI}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label help" data-help="{{Adresse de l'API locale CrowdSec. Utilisez https si la LAPI est sur une autre machine : la clé est envoyée à chaque requête.}}">{{URL de la LAPI}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="lapiUrl" placeholder="http://127.0.0.1:8080">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label help" data-help="{{Clé créée sur la machine CrowdSec avec : cscli bouncers add jeedom. Stockée chiffrée.}}">{{Clé bouncer}}</label>
								<div class="col-sm-6 input-group">
									<input type="password" autocomplete="new-password" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="apiKey">
									<span class="input-group-btn">
										<a class="btn btn-default form-control bt_csShowPass roundedRight" title="{{Afficher / masquer}}"><i class="fas fa-eye"></i></a>
									</span>
								</div>
							</div>

							<legend><i class="fas fa-ban"></i> {{Bannir / débannir (optionnel)}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label help" data-help="{{Compte créé sur la machine CrowdSec avec : cscli machines add jeedom --password <mot de passe>. Laissez vide pour un usage en lecture seule.}}">{{Compte machine}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="machineId" placeholder="jeedom">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label help" data-help="{{Stocké chiffré.}}">{{Mot de passe machine}}</label>
								<div class="col-sm-6 input-group">
									<input type="password" autocomplete="new-password" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="machinePassword">
									<span class="input-group-btn">
										<a class="btn btn-default form-control bt_csShowPass roundedRight" title="{{Afficher / masquer}}"><i class="fas fa-eye"></i></a>
									</span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label help" data-help="{{Durée des bannissements faits depuis Jeedom, au format CrowdSec : 4h, 30m, 1h30m...}}">{{Durée de bannissement}}</label>
								<div class="col-sm-3">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="banDuration" placeholder="4h">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label"></label>
								<div class="col-sm-6">
									<a class="btn btn-default" id="bt_csTest" title="{{Teste la configuration enregistrée : sauvegardez d'abord vos modifications.}}"><i class="fas fa-rss"></i> {{Tester la connexion}}</a>
									<a class="btn btn-default" id="bt_csRefresh"><i class="fas fa-sync"></i> {{Actualiser maintenant}}</a>
									<div id="div_csResult"></div>
								</div>
							</div>
						</div>

						<div class="col-lg-6">
							<legend><i class="fas fa-cogs"></i> {{Paramètres spécifiques}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Auto-actualisation}}</label>
								<div class="col-sm-5">
									<div class="input-group">
										<input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="autorefresh" placeholder="*/10 * * * *">
										<span class="input-group-btn">
											<a class="btn btn-default cursor jeeHelper roundedRight" data-helper="cron" title="{{Assistant cron}}">
												<i class="fas fa-question-circle"></i>
											</a>
										</span>
									</div>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div>

			<div role="tabpanel" class="tab-pane" id="commandtab">
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">{{ID}}</th>
								<th style="min-width:240px;width:300px;">{{Nom}}</th>
								<th>{{État}}</th>
								<th style="min-width:200px;width:250px;">{{Options}}</th>
								<th style="min-width:120px;width:140px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'crowdsec', 'js', 'crowdsec'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
