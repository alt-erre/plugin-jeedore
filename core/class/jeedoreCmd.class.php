<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/jeedoreDaemon.class.php';

class jeedoreCmd extends cmd{
	public function execute($_options = null) {
		log::add('jeedore', 'debug', "Sending to JeeDore Daemon : ".$this->getName());

		if(substr($this->getLogicalId(), 0, 11) === "setModeAsso"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId()
			);
			JeedoreDaemon::execute('setModeAsso', $parameters);
			return;
		}
		
		if(substr($this->getLogicalId(), 0, 18) === "setPositionCmdDown"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "DOWN"
			);
			JeedoreDaemon::execute('setPositionCmd', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 16) === "setPositionCmdUp"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "UP"
			);
			JeedoreDaemon::execute('setPositionCmd', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 18) === "setPositionCmdStop"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "STOP"
			);
			JeedoreDaemon::execute('setPositionCmd', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 22) === "setPositionCmdFavorit1"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "FAVORIT1"
			);
			JeedoreDaemon::execute('setPositionCmd', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 22) === "setPositionCmdFavorit2"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "FAVORIT2"
			);
			JeedoreDaemon::execute('setPositionCmd', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 11) === "setPosition"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"position" => $_options['slider']
			);
			JeedoreDaemon::execute('setPosition', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 17) === "setSlopeCmdToggle"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId()
			);
			JeedoreDaemon::execute('setSlopeCmdToggle', $parameters);
			return;
		}
		
		if(substr($this->getLogicalId(), 0, 17) === "setLevelCmdToggle"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "TOGGLE"
			);
			JeedoreDaemon::execute('setLevelCmd', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 19) === "setLevelCmdFavorit1"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "FAVORIT1"
			);
			JeedoreDaemon::execute('setLevelCmd', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 19) === "setLevelCmdFavorit2"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => "FAVORIT2"
			);
			JeedoreDaemon::execute('setLevelCmd', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 13) === "setLevelCmdOn"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"level" => "100"
			);
			JeedoreDaemon::execute('setLevel', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 14) === "setLevelCmdOff"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"level" => "0"
			);
			JeedoreDaemon::execute('setLevel', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 8) === "setLevel"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"level" => $_options['slider']
			);

			JeedoreDaemon::execute('setLevel', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 11) === "setSetpoint"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"point" => $_options['slider']
			);
			JeedoreDaemon::execute('setSetpoint', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 11) === "setHvacMode"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => $_options['select']
			);
			JeedoreDaemon::execute('setHvacMode', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 16) === "setAuthorization"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => $_options['select']
			);
			JeedoreDaemon::execute('setAuthorization', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 15) === "setThermicLevel"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => $_options['select']
			);
			JeedoreDaemon::execute('setThermicLevel', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 19) === "setAutoThermicLevel"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => 'AUTO'
			);
			JeedoreDaemon::execute('thermiclevel', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 10) === "setRecFav1"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"name" => 'FAVORIT1'
			);
			JeedoreDaemon::execute('setRecFav', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 10) === "setRecFav2"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"name" => 'FAVORIT2'
			);
			JeedoreDaemon::execute('setRecFav', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 12) === "setTimeDelay"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"value" => $_options['slider']
			);
			JeedoreDaemon::execute('setTimeDelay', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 10) === "setBoostOn"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"value" => "True"
			);
			JeedoreDaemon::execute('setBoost', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 11) === "setBoostOff"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"value" => "False"
			);
			JeedoreDaemon::execute('setBoost', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 11) === "setAlarmCmd"){
			$parameters = array(
				"mode" => $_options['select']
			);
			JeedoreDaemon::execute('setAlarmCmd', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 10) === "setPartCmd"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => $_options['select'],
				"part" => $this->getConfiguration('conf')['part'][0]
			);
			JeedoreDaemon::execute('setPartCmd', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 10) === "setZoneCmd"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"mode" => $_options['select'],
				"zone" => $this->getConfiguration('conf')['zones'][0]
			);
			JeedoreDaemon::execute('setZoneCmd', $parameters);
			return;
		}
	
		if(substr($this->getLogicalId(), 0, 5) === "histo"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"type" => $_options['select']
			);
			JeedoreDaemon::execute('histo', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 9) === "lasthisto"){
			$parameters = array(
				"deviceId" => $this->getEqlogic()->getLogicalId(),
				"type" => $_options['select']
			);
			JeedoreDaemon::execute('lasthisto', $parameters);
			return;
		}

		if(substr($this->getLogicalId(), 0, 4) === "play"){
			$parameters = array(
				"id" => $this->getEqlogic()->getLogicalId()
			);
			JeedoreDaemon::execute('putscenario', $parameters);
			return;
		}
	}
}
