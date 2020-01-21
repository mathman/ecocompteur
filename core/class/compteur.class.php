<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class compteur extends eqLogic {
    /*     * *************************Attributs****************************** */



    /*     * ***********************Methode static*************************** */
    
    public static function sendRequest($ip, $path) {
        //Initialize cURL.
        $ch = curl_init();

        //Set the URL that you want to GET by using the CURLOPT_URL option.
        curl_setopt($ch, CURLOPT_URL, "http://".$ip."/".$path);

        //Set CURLOPT_RETURNTRANSFER so that the content is returned as a variable.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //Execute the request.
        $data = curl_exec($ch);

        //Close the cURL handle.
        curl_close($ch);
        
        return $data;
    }
    
    public static function getConso($string)
    {
        $data = trim(str_replace('"conso_base" :', "", substr(strstr($string, '"conso_base" :'), 0, strpos(strstr($string, '"conso_base" :'), ","))));
        return (int)$data;
    }
    
    public static function pull() {
		foreach (self::byType('compteur') as $eqLogic) {
			$eqLogic->refresh();
		}
	}

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {

      }
     */


    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {

      }
     */



    /*     * *********************Méthodes d'instance************************* */
    
    public function refresh() {
        if ($this->getIsEnable()) {
            $eqpNetwork = eqLogic::byTypeAndSearhConfiguration('networks', $this->getConfiguration('compteur_ip'))[0];
            if (is_object($eqpNetwork)) {
                $statusCmd = $eqpNetwork->getCmd(null, 'ping');
                if (is_object($statusCmd) && $statusCmd->execCmd() == $statusCmd->formatValue(true)) {
                    $compteur_ip = $this->getConfiguration('compteur_ip','');
                    $instR = compteur::sendRequest($compteur_ip, "inst.json");
                    $dataR = compteur::sendRequest($compteur_ip, "data.json");
                    $dataR = trim($dataR);
                    $inst = json_decode($instR, true);

                    $consoValue = compteur::getConso($dataR);
                    $counter = $this->getCmd(null, 'counter');
                    if (is_object($counter)) {
                        if ($counter->formatValue(($consoValue/1000)) != $counter->execCmd()) {
                            $counter->setCollectDate('');
                            $counter->event(($consoValue/1000));
                        }
                    }
                    
                    $puissanceC1 = $this->getCmd(null, 'puissanceC1');
                    if (is_object($puissanceC1)) {
                        if ($puissanceC1->formatValue($inst['data1']) != $puissanceC1->execCmd()) {
                            $puissanceC1->setCollectDate('');
                            $puissanceC1->event($inst['data1']);
                        }
                    }
                    
                    $puissanceC2 = $this->getCmd(null, 'puissanceC2');
                    if (is_object($puissanceC2)) {
                        if ($puissanceC2->formatValue($inst['data2']) != $puissanceC2->execCmd()) {
                            $puissanceC2->setCollectDate('');
                            $puissanceC2->event($inst['data2']);
                        }
                    }
                    
                    $puissanceC3 = $this->getCmd(null, 'puissanceC3');
                    if (is_object($puissanceC3)) {
                        if ($puissanceC3->formatValue($inst['data3']) != $puissanceC3->execCmd()) {
                            $puissanceC3->setCollectDate('');
                            $puissanceC3->event($inst['data3']);
                        }
                    }
                    
                    $puissanceC4 = $this->getCmd(null, 'puissanceC4');
                    if (is_object($puissanceC4)) {
                        if ($puissanceC4->formatValue($inst['data4']) != $puissanceC4->execCmd()) {
                            $puissanceC4->setCollectDate('');
                            $puissanceC4->event($inst['data4']);
                        }
                    }
                    
                    $puissanceC5 = $this->getCmd(null, 'puissanceC5');
                    if (is_object($puissanceC5)) {
                        if ($puissanceC5->formatValue($inst['data5']) != $puissanceC5->execCmd()) {
                            $puissanceC5->setCollectDate('');
                            $puissanceC5->event($inst['data5']);
                        }
                    }

                    $refresh = $this->getCmd(null, 'updatetime');
                    if (is_object($refresh)) {
                        $refresh->event(date("d/m/Y H:i",(time())));
                    }
                    
                    $mc = cache::byKey('compteurWidgetmobile' . $this->getId());
                    $mc->remove();
                    $mc = cache::byKey('compteurWidgetdashboard' . $this->getId());
                    $mc->remove();
                    $this->toHtml('mobile');
                    $this->toHtml('dashboard');
                    $this->refreshWidget();
                }
            }
        }
    }

    public function preInsert() {
        
    }

    public function postInsert() {
        
    }

    public function preSave() {
        
    }

    public function postSave() {
        
    }

    public function preUpdate() {
        
    }

    public function postUpdate() {
        if ( $this->getIsEnable() )
		{
            $refresh = $this->getCmd(null, 'refresh');
            if (!is_object($refresh)) {
                $refresh = new compteurCmd();
            }
            $refresh->setName('Rafraichir');
            $refresh->setOrder(0);
            $refresh->setEqLogic_id($this->getId());
            $refresh->setLogicalId('refresh');
            $refresh->setType('action');
            $refresh->setSubType('other');
            $refresh->save();
            
            $cmd = $this->getCmd(null,'counter');
            if (!is_object($cmd)) {
                $cmd = new compteurCmd();
            }
            $cmd->setName('Consommation');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('counter');
            $cmd->setOrder(1);
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setUnite('kWh');
            $cmd->setTemplate('dashboard', 'tile');
            $cmd->setTemplate('mobile', 'tile');
            $cmd->setDisplay('generic_type', 'GENERIC_INFO');
            $cmd->setDisplay('forceReturnLineAfter', '1');
            $cmd->setIsHistorized(1);
            $cmd->save();
            
            $cmd = $this->getCmd(null,'puissanceC1');
            if (!is_object($cmd)) {
                $cmd = new compteurCmd();
            }
            $cmd->setName('Puissance Chauffage');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('puissanceC1');
            $cmd->setOrder(2);
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setUnite('W');
            $cmd->setTemplate('dashboard','tile');
			$cmd->setTemplate('mobile','tile');
            $cmd->setDisplay('generic_type', 'GENERIC_INFO');
            $cmd->setDisplay('forceReturnLineAfter', '1');
            $cmd->setIsHistorized(1);
            $cmd->save();
            
            $cmd = $this->getCmd(null,'puissanceC2');
            if (!is_object($cmd)) {
                $cmd = new compteurCmd();
            }
            $cmd->setName('Puissance Micro Station');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('puissanceC2');
            $cmd->setOrder(3);
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setUnite('W');
            $cmd->setTemplate('dashboard','tile');
			$cmd->setTemplate('mobile','tile');
            $cmd->setDisplay('generic_type', 'GENERIC_INFO');
            $cmd->setDisplay('forceReturnLineAfter', '1');
            $cmd->setIsHistorized(1);
            $cmd->save();
            
            $cmd = $this->getCmd(null,'puissanceC3');
            if (!is_object($cmd)) {
                $cmd = new compteurCmd();
            }
            $cmd->setName('Puissance ECS');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('puissanceC3');
            $cmd->setOrder(4);
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setUnite('W');
            $cmd->setTemplate('dashboard','tile');
			$cmd->setTemplate('mobile','tile');
            $cmd->setDisplay('generic_type', 'GENERIC_INFO');
            $cmd->setDisplay('forceReturnLineAfter', '1');
            $cmd->setIsHistorized(1);
            $cmd->save();
            
            $cmd = $this->getCmd(null,'puissanceC4');
            if (!is_object($cmd)) {
                $cmd = new compteurCmd();
            }
            $cmd->setName('Puissance prise de courant 1');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('puissanceC4');
            $cmd->setOrder(5);
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setUnite('W');
            $cmd->setTemplate('dashboard','tile');
			$cmd->setTemplate('mobile','tile');
            $cmd->setDisplay('generic_type', 'GENERIC_INFO');
            $cmd->setDisplay('forceReturnLineAfter', '1');
            $cmd->setIsHistorized(1);
            $cmd->save();
            
            $cmd = $this->getCmd(null,'puissanceC5');
            if (!is_object($cmd)) {
                $cmd = new compteurCmd();
            }
            $cmd->setName('Puissance prise de courant 2');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('puissanceC5');
            $cmd->setOrder(6);
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setUnite('W');
            $cmd->setTemplate('dashboard','tile');
			$cmd->setTemplate('mobile','tile');
            $cmd->setDisplay('generic_type', 'GENERIC_INFO');
            $cmd->setDisplay('forceReturnLineAfter', '1');
            $cmd->setIsHistorized(1);
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'updatetime');
			if ( ! is_object($cmd)) {
				$cmd = new compteurCmd();
            }
            $cmd->setName('Dernier refresh');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('updatetime');
            $cmd->setOrder(7);
            $cmd->setType('info');
            $cmd->setSubType('string');
            $cmd->save();
        }
    }

    public function preRemove() {
        
    }

    public function postRemove() {
        
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class compteurCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
		log::add('compteur','debug','get '.$this->getLogicalId());
		$option = array();
		switch ($this->getLogicalId()) {
            case "refresh":
                $eqLogic->refresh();
                return true;
		}
        return true;
    }

    /*     * **********************Getteur Setteur*************************** */
}


