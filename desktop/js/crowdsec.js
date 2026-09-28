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

'use strict';

var CROWDSEC_AJAX = 'plugins/crowdsec/core/ajax/crowdsec.ajax.php';

function csEsc(v) {
  return String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/* Appel AJAX d'une action liée à l'équipement courant (test de connexion, actualisation) */
function csCallAction(action, waitText) {
  var idEl = document.querySelector('.eqLogicAttr[data-l1key=id]');
  var id = idEl ? idEl.value : '';
  var out = document.getElementById('div_csResult');
  if (!id) {
    jeedomUtils.showAlert({ message: '{{Sauvegardez l\'équipement avant de continuer.}}', level: 'warning' });
    return;
  }
  out.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + waitText;
  domUtils.ajax({
    type: 'POST',
    url: CROWDSEC_AJAX,
    data: { action: action, id: id },
    dataType: 'json',
    global: true,
    error: function (req, status, err) { out.innerHTML = ''; handleAjaxError(req, status, err); },
    success: function (data) {
      var ok = data.state === 'ok' && data.result.ok !== false;
      var message = data.state === 'ok' ? data.result.message : data.result;
      out.innerHTML = '<div class="alert alert-' + (ok ? 'success' : 'danger') + '">' + csEsc(message) + '</div>';
    }
  });
}

document.registerEvent('click', function (e) {
  if (e.target.closest('#bt_csTest')) {
    csCallAction('testConnection', '{{Test en cours...}}');
  } else if (e.target.closest('#bt_csRefresh')) {
    csCallAction('refresh', '{{Actualisation en cours...}}');
  }
});

/* Hook appelé par plugin.template.js après chargement d'un équipement */
function printEqLogic(_eqLogic) {
  var out = document.getElementById('div_csResult');
  if (out) out.innerHTML = '';
}

/* plugin.template.js appelle addCmdToTable pour chaque commande (créées par le plugin, non modifiables en type) */
function addCmdToTable(_cmd) {
  var cmd = _cmd || {};
  var isInfo = cmd.type === 'info';
  var options = '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible">{{Afficher}}</label>';
  if (isInfo && cmd.subType !== 'string') {
    options += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized">{{Historiser}}</label>';
  }
  var actions = '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a>';
  if (!isInfo) {
    actions += ' <a class="btn btn-success btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
  }
  var row = document.createElement('tr');
  row.className = 'cmd';
  row.dataset.cmd_id = cmd.id || '';
  row.innerHTML =
    '<td class="hidden-xs"><span class="cmdAttr" data-l1key="id"></span></td>'
    + '<td><input class="cmdAttr form-control input-sm" data-l1key="name">'
    + '<input type="hidden" class="cmdAttr" data-l1key="type"><input type="hidden" class="cmdAttr" data-l1key="subType"></td>'
    + '<td>' + (isInfo ? '<span class="cmdAttr" data-l1key="htmlstate"></span>' : '') + '</td>'
    + '<td>' + options + '</td>'
    + '<td>' + actions + '</td>';
  document.querySelector('#table_cmd tbody').appendChild(row);
  row.setJeeValues(cmd, '.cmdAttr');
}
