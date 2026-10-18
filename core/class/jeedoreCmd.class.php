<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/jeedoreDaemon.class.php';

/**
 * Classe représentant une commande Jeedom associée à un équipement Delta Dore Tydom.
 * Traduit l'exécution d'une commande action Jeedom en requête vers le démon .NET JeeDore.
 */
class jeedoreCmd extends cmd{
	/**
	 * Exécute la commande action auprès du démon JeeDore en fonction du préfixe de son logicalId.
	 *
	 * @param array|null $_options Options passées par Jeedom lors de l'exécution (ex: 'slider', 'select')
	 */
	public function execute($_options = null) {
		log::add('jeedore', 'debug', "Sending to JeeDore Daemon : ".$this->getName());

		// Passage de l'équipement en mode association
		if(substr($this->getLogicalId(), 0, 11) === "setModeAsso"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId()
			);
			JeedoreDaemon::execute('setModeAsso', $parameters);
			return;
		}
		
		// Volets / Ouvrants : Descente (DOWN)
		if(substr($this->getLogicalId(), 0, 18) === "setPositionCmdDown"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "DOWN"
			);
			JeedoreDaemon::execute('setPositionCmd', $parameters);
			return;
		}

		// Volets / Ouvrants : Montée (UP)
		if(substr($this->getLogicalId(), 0, 16) === "setPositionCmdUp"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "UP"
			);
			JeedoreDaemon::execute('setPositionCmd', $parameters);
			return;
		}

		// Volets / Ouvrants : Arrêt (STOP)
		if(substr($this->getLogicalId(), 0, 18) === "setPositionCmdStop"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "STOP"
			);
			JeedoreDaemon::execute('setPositionCmd', $parameters);
			return;
		}

		// Volets / Ouvrants : Position favorite 1
		if(substr($this->getLogicalId(), 0, 22) === "setPositionCmdFavorit1"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "FAVORIT1"
			);
			JeedoreDaemon::execute('setPositionCmd', $parameters);
			return;
		}

		// Volets / Ouvrants : Position favorite 2
		if(substr($this->getLogicalId(), 0, 22) === "setPositionCmdFavorit2"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "FAVORIT2"
			);
			JeedoreDaemon::execute('setPositionCmd', $parameters);
			return;
		}

		// Volets / Ouvrants : Positionnement précis (0 à 100%)
		if(substr($this->getLogicalId(), 0, 11) === "setPosition"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"position" => $_options['slider']
			);
			JeedoreDaemon::execute('setPosition', $parameters);
			return;
		}

		// Brise-soleil orientable (BSO) : Bascule d'inclinaison des lames
		if(substr($this->getLogicalId(), 0, 17) === "setSlopeCmdToggle"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId()
			);
			JeedoreDaemon::execute('setSlopeCmdToggle', $parameters);
			return;
		}
		
		// Éclairages / Prises : Bascule marche/arrêt (TOGGLE)
		if(substr($this->getLogicalId(), 0, 17) === "setLevelCmdToggle"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "TOGGLE"
			);
			JeedoreDaemon::execute('setLevelCmd', $parameters);
			return;
		}

		// Éclairages : Niveau favori 1
		if(substr($this->getLogicalId(), 0, 19) === "setLevelCmdFavorit1"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "FAVORIT1"
			);
			JeedoreDaemon::execute('setLevelCmd', $parameters);
			return;
		}

		// Éclairages : Niveau favori 2
		if(substr($this->getLogicalId(), 0, 19) === "setLevelCmdFavorit2"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "FAVORIT2"
			);
			JeedoreDaemon::execute('setLevelCmd', $parameters);
			return;
		}

		// Éclairages / Prises : Allumer (niveau 100%)
		if(substr($this->getLogicalId(), 0, 13) === "setLevelCmdOn"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"level" => "100"
			);
			JeedoreDaemon::execute('setLevel', $parameters);
			return;
		}

		// Éclairages / Prises : Éteindre (niveau 0%)
		if(substr($this->getLogicalId(), 0, 14) === "setLevelCmdOff"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"level" => "0"
			);
			JeedoreDaemon::execute('setLevel', $parameters);
			return;
		}

		// Éclairages (variateur) : Réglage du niveau d'intensité (0 à 100%)
		if(substr($this->getLogicalId(), 0, 8) === "setLevel"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"level" => $_options['slider']
			);

			JeedoreDaemon::execute('setLevel', $parameters);
			return;
		}

		// Chauffage / Thermostat : Modification de la température de consigne
		if(substr($this->getLogicalId(), 0, 11) === "setSetpoint"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"point" => $_options['slider']
			);
			JeedoreDaemon::execute('setSetpoint', $parameters);
			return;
		}

		// Chauffage : Changement du mode CVC (NORMAL, STOP, ANTI_FROST, ...)
		if(substr($this->getLogicalId(), 0, 11) === "setHvacMode"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => $_options['select']
			);
			JeedoreDaemon::execute('setHvacMode', $parameters);
			return;
		}

		// Chaudière : Autorisation de chauffe (HEATING, STOP, ...)
		if(substr($this->getLogicalId(), 0, 16) === "setAuthorization"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => $_options['select']
			);
			JeedoreDaemon::execute('setAuthorization', $parameters);
			return;
		}

		// Chauffage : Niveau thermique (ECO, COMFORT, ABSENCE, ...)
		if(substr($this->getLogicalId(), 0, 15) === "setThermicLevel"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => $_options['select']
			);
			JeedoreDaemon::execute('setThermicLevel', $parameters);
			return;
		}

		// Chauffage : Passage en niveau thermique automatique
		if(substr($this->getLogicalId(), 0, 19) === "setAutoThermicLevel"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => 'AUTO'
			);
			JeedoreDaemon::execute('thermiclevel', $parameters);
			return;
		}

		// Mémorisation de la position favorite 1
		if(substr($this->getLogicalId(), 0, 10) === "setRecFav1"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"name" => 'FAVORIT1'
			);
			JeedoreDaemon::execute('setRecFav', $parameters);
			return;
		}

		// Mémorisation de la position favorite 2
		if(substr($this->getLogicalId(), 0, 10) === "setRecFav2"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"name" => 'FAVORIT2'
			);
			JeedoreDaemon::execute('setRecFav', $parameters);
			return;
		}

		// Temporisation (délai d'extinction ou de maintien)
		if(substr($this->getLogicalId(), 0, 12) === "setTimeDelay"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"value" => $_options['slider']
			);
			JeedoreDaemon::execute('setTimeDelay', $parameters);
			return;
		}

		// Activation du mode Boost
		if(substr($this->getLogicalId(), 0, 10) === "setBoostOn"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"value" => "True"
			);
			JeedoreDaemon::execute('setBoost', $parameters);
			return;
		}

		// Désactivation du mode Boost
		if(substr($this->getLogicalId(), 0, 11) === "setBoostOff"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"value" => "False"
			);
			JeedoreDaemon::execute('setBoost', $parameters);
			return;
		}

		// Alarme Tyxal+ : Commande globale (ON, OFF, ...)
		if(substr($this->getLogicalId(), 0, 11) === "setAlarmCmd"){
			$parameters = array(
				"mode" => $_options['select']
			);
			JeedoreDaemon::execute('setAlarmCmd', $parameters);
			return;
		}

		// Alarme : Commande par partition
		if(substr($this->getLogicalId(), 0, 10) === "setPartCmd"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => $_options['select'],
				"part" => $this->getConfiguration('conf')['part'][0]
			);
			JeedoreDaemon::execute('setPartCmd', $parameters);
			return;
		}

		// Alarme : Commande par zone (1 à 8)
		if(substr($this->getLogicalId(), 0, 10) === "setZoneCmd"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => $_options['select'],
				"zone" => $this->getConfiguration('conf')['zones'][0]
			);
			JeedoreDaemon::execute('setZoneCmd', $parameters);
			return;
		}
	
		// Alarme : Récupération de l'historique complet d'événements
		if(substr($this->getLogicalId(), 0, 5) === "histo"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"type" => $_options['select']
			);
			JeedoreDaemon::execute('histo', $parameters);
			return;
		}

		// Alarme : Récupération du dernier événement d'historique
		if(substr($this->getLogicalId(), 0, 9) === "lasthisto"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"type" => $_options['select']
			);
			JeedoreDaemon::execute('lasthisto', $parameters);
			return;
		}

		// Scénario Tydom : Exécution d'un scénario enregistré dans la box Tydom
		if(substr($this->getLogicalId(), 0, 4) === "play"){
			$parameters = array(
				"id" => $this->getEqlogic()->getLogicalId()
			);
			JeedoreDaemon::execute('putscenario', $parameters);
			return;
		}
	}
}
