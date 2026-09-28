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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class crowdsec extends eqLogic {

  /** Configuration keys stored encrypted (see encrypt()/decrypt()). */
  const SECRET_KEYS = ['apiKey'];

  const DEFAULT_LAPI_URL = 'http://127.0.0.1:8080';

  /** Decision origins fed by the community (CAPI) or subscribed blocklists; every other origin is local (crowdsec, cscli, appsec...). */
  const COMMUNITY_ORIGINS = ['CAPI', 'lists'];

  /** Info commands: logicalId => [name, subType]. */
  const INFO_CMDS = [
    'decisions_total' => ['Décisions actives', 'numeric'],
    'decisions_local' => ['Décisions locales', 'numeric'],
    'decisions_community' => ['Décisions communautaires', 'numeric'],
    'local_ip_list' => ['IP bloquées localement', 'string'],
    'last_local_ip' => ['Dernière IP bloquée localement', 'string'],
  ];

  public static function cron() {
    /** @var crowdsec */
    foreach (self::byType(__CLASS__, true) as $eqLogic) {
      $autorefresh = $eqLogic->getConfiguration('autorefresh', '*/10 * * * *');
      if ($autorefresh == '') continue;
      try {
        $cron = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
        if ($cron->isDue()) {
          $eqLogic->refresh();
        }
      } catch (\Throwable $e) {
        log::add(__CLASS__, 'error', "Error during refresh of {$eqLogic->getHumanName()}: " . $e->getMessage());
      }
    }
  }

  public function decrypt() {
    foreach (self::SECRET_KEYS as $key) {
      $this->setConfiguration($key, utils::decrypt($this->getConfiguration($key)));
    }
  }
  public function encrypt() {
    foreach (self::SECRET_KEYS as $key) {
      $this->setConfiguration($key, utils::encrypt($this->getConfiguration($key)));
    }
  }

  public function postSave() {
    foreach (self::INFO_CMDS as $logicalId => [$name, $subType]) {
      $this->createCmd($logicalId, $name, 'info', $subType);
    }
    $this->createCmd('refresh', 'Rafraîchir', 'action', 'other');
  }

  private function createCmd($logicalId, $name, $type, $subType) {
    if (is_object($this->getCmd(null, $logicalId))) return;
    $cmd = new crowdsecCmd();
    $cmd->setEqLogic_id($this->getId());
    $cmd->setLogicalId($logicalId);
    $cmd->setName(__($name, __FILE__));
    $cmd->setType($type);
    $cmd->setSubType($subType);
    $cmd->save();
  }

  /**
   * GET on the CrowdSec local API, authenticated with the bouncer key.
   *
   * @return array decoded JSON body ([] when the LAPI answers "null", i.e. no decision)
   * @throws Exception on transport error or non-2xx HTTP code
   */
  private function lapiGet($path) {
    $url = rtrim($this->getConfiguration('lapiUrl', self::DEFAULT_LAPI_URL), '/') . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT        => 30,
      CURLOPT_USERAGENT      => 'jeedom-crowdsec',
      CURLOPT_HTTPHEADER     => ['X-Api-Key: ' . $this->getConfiguration('apiKey')],
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($body === false) {
      throw new Exception(__('LAPI injoignable', __FILE__) . " : $curlErr");
    }
    if ($httpCode == 403) {
      throw new Exception(__('Clé bouncer refusée par la LAPI (HTTP 403)', __FILE__));
    }
    if ($httpCode < 200 || $httpCode >= 300) {
      throw new Exception(__('Réponse inattendue de la LAPI', __FILE__) . " (HTTP $httpCode)");
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : [];
  }

  /**
   * @return array ['ok' => bool, 'message' => string]
   */
  public function testConnection() {
    try {
      // requête filtrée sur une IP : valide la clé sans télécharger toutes les décisions
      $this->lapiGet('/v1/decisions?ip=127.0.0.1');
      return ['ok' => true, 'message' => __('Connexion à la LAPI réussie.', __FILE__)];
    } catch (Exception $e) {
      return ['ok' => false, 'message' => $e->getMessage()];
    }
  }

  /**
   * Reads the active decisions and updates the info commands.
   *
   * @return bool false if the LAPI could not be read
   */
  public function refresh() {
    try {
      $decisions = $this->lapiGet('/v1/decisions');
    } catch (Exception $e) {
      log::add(__CLASS__, 'warning', "{$this->getHumanName()} : " . $e->getMessage());
      return false;
    }
    $local = array_filter($decisions, function ($d) {
      return !in_array($d['origin'] ?? '', self::COMMUNITY_ORIGINS, true);
    });
    usort($local, function ($a, $b) {
      return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
    });
    $localIps = array_values(array_unique(array_column($local, 'value')));

    $this->checkAndUpdateCmd('decisions_total', count($decisions));
    $this->checkAndUpdateCmd('decisions_local', count($local));
    $this->checkAndUpdateCmd('decisions_community', count($decisions) - count($local));
    $this->checkAndUpdateCmd('local_ip_list', implode(', ', $localIps));
    $this->checkAndUpdateCmd('last_local_ip', $localIps[0] ?? '');
    return true;
  }
}

class crowdsecCmd extends cmd {

  public function execute($_options = array()) {
    /** @var crowdsec */
    $eqLogic = $this->getEqLogic();
    switch ($this->getLogicalId()) {
      case 'refresh':
        $eqLogic->refresh();
        break;
    }
  }
}
