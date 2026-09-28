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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init();

    /** @return crowdsec the equipment designated by the "id" parameter */
    $getEqLogic = function () {
        $eqLogic = eqLogic::byId((int) init('id'));
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'crowdsec') {
            throw new Exception(__('Équipement introuvable', __FILE__) . ' : ' . init('id'));
        }
        return $eqLogic;
    };

    switch (init('action')) {
        case 'testConnection':
            ajax::success($getEqLogic()->testConnection());
            break;

        case 'refresh':
            $eqLogic = $getEqLogic();
            if (!$eqLogic->getIsEnable()) {
                throw new Exception(__("L'équipement est désactivé", __FILE__));
            }
            if (!$eqLogic->refresh()) {
                throw new Exception(__('La LAPI ne répond pas : vérifiez la connexion (bouton « Tester la connexion ») et le log crowdsec.', __FILE__));
            }
            ajax::success(['message' => __('Équipement actualisé.', __FILE__)]);
            break;
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
