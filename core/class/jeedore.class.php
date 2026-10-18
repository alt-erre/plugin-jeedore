<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/jeedoreCmd.class.php';
require_once dirname(__FILE__) . '/jeedoreDaemon.class.php';

/**
 * Define a DeltaDore eqLogic.
 */
class jeedore extends eqLogic
{
	const dependency_files 		= '/tmp/jeedore_dependencies';
	const configuration_file 	= __DIR__ . '/../../jeedore_config.json';
	const configFilename 		= __DIR__ . '/../../jeedore_jeedomconfig.json';
	const cronFilename 		= __DIR__ . '/../../jeedore_cron.json';

    public static function dependancy_info() {
		log::add('jeedore', 'debug', 'dependancy_info...');
		$return = array();
		$return['log'] = 'jeedoredaemon';
		$return['progress_file'] = jeedore::dependency_files;
		$return['state'] = 'ok';
		return $return;
	}

    public static function dependancy_install() {
		log::add('jeedore', 'debug', 'dependancy_install...');
		log::remove(__CLASS__ . '_update');

		$path = realpath (dirname(__FILE__) . '/../../ressources/' );

		return array(
			'script' => $path . '/install_#stype#.sh',
			'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

	public static function deamon_info() {
		$return = array();
		$return['log'] = 'jeedoredaemon';
		$return['state'] = 'nok';
		$pid_file = jeedom::getTmpFolder('jeedore') . '/jeedoredaemon.pid';

		if (file_exists($pid_file)) {
			$pid = file_get_contents($pid_file);
			if (@posix_getsid(trim($pid))) {
					$return['state'] = 'ok';
				} else {
						shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
				}
		}
		$return['launchable'] = 'ok';
		return $return;
	}

	public static function cronHourly() {
		$energyHistoDeviceIds = config::byKey('energyHisto', 'jeedore');
		if(!isset($energyHistoDeviceIds)){
			return;
		}
		$deviceIds = explode(";", $energyHistoDeviceIds);

		foreach($deviceIds  as $deviceId){
			$logicalId = "energyHisto".$deviceId;

			$cmds = cmd::byLogicalId($logicalId);
			if(is_null($cmds)){
				continue;
			}
			foreach($cmds as $cmd){
				$conf = $cmd->getConfiguration('conf');
				foreach($conf['dests'] as $dest){
					foreach($conf['periods'] as $period){
						if($period !== "DAY"){
							continue;
						}

						$parameters = array(
							"deviceId" => $deviceId,
							"period" => $period,
							"destination" => $dest
						);
						JeedoreDaemon::execute('energyHisto', $parameters);
						sleep(20);
					}
				}
			}
		}
	}

	public static function cronDaily() {
		jeedore::cronEnergyHisto();
		jeedore::cronEnergyDistrib();
		jeedore::cronEnergyIndex();
	}

	public static function cronEnergyIndex(){
		$energyIndexDeviceIds = config::byKey('energyIndex', 'jeedore');
		if(!isset($energyIndexDeviceIds)){
			return;
		}
		$deviceIds = explode(";", $energyIndexDeviceIds);
		$dests = array('ELEC_TOTAL','ELEC_HEATING','ELEC_HOTWATER','ELEC_OUTLET','ELEC_OTHER','GAS_TOTAL','GAS_HEATING','GAS_HOTWATER','TEMP_INDOOR','TEMP_OUTDOOR');
        log::add ('jeedore','debug','cronEnergyIndex');
		foreach($deviceIds  as $deviceId){
			foreach($dests as $dest){
				if(strlen($deviceId) == 0){
					continue;
				}
				$parameters = array(
					"deviceId" => $deviceId,
					"destination" => $dest
					);
				JeedoreDaemon::execute('energyIndex', $parameters);
				sleep(20);
			}
		}
	}

	public static function cronEnergyDistrib(){
		$energyDistribDeviceIds = config::byKey('energyDistrib', 'jeedore');
		if(!isset($energyDistribDeviceIds)){
			return;
		}
		$deviceIds = explode(";", $energyDistribDeviceIds);
		$periods = array('MONTH', 'YEAR');
		$dests = array('ELEC', 'GAS');
		log::add ('jeedore','debug',"cronEnergyDistrib");

		foreach($periods as $period){
			foreach($dests as $dest){
				foreach($deviceIds as $deviceId){
					if(strlen($deviceId) == 0){
						continue;
					}

					$parameters = array(
						"deviceId" => $deviceId,
						"period" => $period,
						"destination" => $dest
					);
					JeedoreDaemon::execute('energyDistrib', $parameters);
					sleep(20);
				}
			}
		}
	}

	public static function cronEnergyHisto(){
		$energyHistoDeviceIds = config::byKey('energyHisto', 'jeedore');
		if(!isset($energyHistoDeviceIds)){
			return;
		}
		$deviceIds = explode(";", $energyHistoDeviceIds);
        log::add ('jeedore','debug',"cronEnergyHisto");

		foreach($deviceIds  as $deviceId){
			$logicalId = "energyHisto".$deviceId;

			$cmds = cmd::byLogicalId($logicalId);
			if(is_null($cmds)){
				continue;
			}
			foreach($cmds as $cmd){
				$conf = $cmd->getConfiguration('conf');
				foreach($conf['dests'] as $dest){
					foreach($conf['periods'] as $period){
						if($period === "DAY"){
							continue;
						}
						$parameters = array(
							"deviceId" => $deviceId,
							"period" => $period,
							"destination" => $dest
						);
						JeedoreDaemon::execute('energyHisto', $parameters);
						sleep(20);
					}
				}
			}
		}
	}

	public static function deamon_start($_debug = false) {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		log::remove('jeedoredaemon');

		exec(system::getCmdSudo() . 'chmod 777 /var/www/html/plugins/jeedore/ressources/jeedoredaemon-x86/Rezolv.JeeDore.Server > /dev/null 2>&1');
		exec(system::getCmdSudo() . 'chmod 777 /var/www/html/plugins/jeedore/ressources/jeedoredaemon-arm/Rezolv.JeeDore.Server > /dev/null 2>&1');
		exec(system::getCmdSudo() . 'chmod 777 /var/www/html/plugins/jeedore/ressources/jeedoredaemon-arm64/Rezolv.JeeDore.Server > /dev/null 2>&1');

		$architecture = config::byKey('architecture', 'jeedore');
		$daemonPath = realpath(dirname(__FILE__) . '/../../ressources/').'/jeedoredaemon-' . $architecture;
		$cmd = $daemonPath . '/Rezolv.JeeDore.Server --pid '.jeedom::getTmpFolder('jeedore') . '/jeedoredaemon.pid';
		log::add('jeedore', 'debug', 'Launching daemon: ' . $cmd);

		//		$result = exec($cmd . ' >> ' . log::getPathToLog('jeedoredaemon') . ' 2>&1 &');
		$result = exec($cmd . ' 1>> ' . log::getPathToLog('jeedoredaemon') . '  2>> '. log::getPathToLog('jeedorecron') . ' &');

		$i = 0;
		while ($i < 30) {
			$deamon_info = self::deamon_info();

			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}

		if ($i >= 30) {
			log::add('jeedore', 'error', 'Impossible to launch le daemon JeeDore...', 'unableStartDaemon');
			return false;
		}

		log::add('jeedore', 'debug', 'Daemon JeeDore launched');
		return true;
	}

	public static function deamon_stop() {

		$pid_file = jeedom::getTmpFolder('jeedore') . '/jeedoredaemon.pid';

		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			system::kill($pid);
		}
		system::fuserk(config::byKey('socketport', 'jeedore', 13666));
	}

	public static function createConfigurationFile(){
		$network = config::byKey('network', 'jeedore');
		$ip = config::byKey('ip', 'jeedore');
		$macAddress = config::byKey('macAddress', 'jeedore');
		$password = config::byKey('password', 'jeedore');
		$alarmCode = config::byKey('alarmCode', 'jeedore');
		$alarmId = config::byKey('alarmId', 'jeedore');
		$authentificationRequired = (bool)config::byKey('authentificationRequired', 'jeedore');
		$invertPosition = (bool)config::byKey('invertPosition', 'jeedore');

		$config = array(
			"network" => $network,
			"ip" => $ip,
			"macAddress" => $macAddress,
			"password" => $password,
			"alarmCode" => $alarmCode,
			"alarmId" => $alarmId,
			"invertPosition" => $invertPosition,
			"authentificationRequired" => $authentificationRequired
		);
		$configContent = json_encode($config);
		$configFilename = jeedore::configuration_file;

		file_put_contents($configFilename, $configContent);
		chmod($configFilename, 0777);


		$jeedomip	= config::byKey('jeedomip', 'jeedore');
		$jeedomport = config::byKey('jeedomport', 'jeedore');

		$config = array(
			"url" => $jeedomip,
			"port" => $jeedomport
		);
		$configContent = json_encode($config);
		file_put_contents(jeedore::configFilename, $configContent);
		chmod(jeedore::configFilename, 0777);
	}

	public static function synchronizeEquipments(){
		log::add('jeedore', 'debug', 'Refreshing all devices...');

		config::remove('alarmId', 'jeedore');

		$configsFile = JeedoreDaemon::execute('geteqlogics');
		sleep(2);
		$configsFile = JeedoreDaemon::execute('getcmds');
		sleep(2);
		$configsFile = JeedoreDaemon::execute('getdevicescmeta');
		sleep(2);
		$configsFile = JeedoreDaemon::execute('geteqdata');
		sleep(2);
		$configsFile = JeedoreDaemon::execute('getinfo');
		log::add('jeedore', 'debug', 'All devices has been refreshed...');
	}

	public static function identify($logicalId){
		log::add('jeedore', 'debug', 'identify');

		$parameters = array(
			"deviceId" => $logicalId
		);
		JeedoreDaemon::execute('identify', $parameters);
	}

	private static $imagesChmodDone = false;
	private static $imageCache = array();

	public function getImage() {
		return $this->getImageState();
	}

	private function resolveImage($filename, $theme = null) {
		if (!$theme) {
			$theme = config::byKey('theme', 'jeedore');
			if (!$theme) {
				$theme = 'light';
			}
		}
		$cacheKey = $theme . '/' . $filename;
		if (isset(self::$imageCache[$cacheKey])) {
			return self::$imageCache[$cacheKey];
		}

		$imagesDir = __DIR__ . '/../../ressources/images';
		$themeAbs = $imagesDir . '/' . $theme . '/' . $filename;
		$lightAbs = $imagesDir . '/light/' . $filename;

		if (!is_readable($lightAbs) && !self::$imagesChmodDone) {
			self::$imagesChmodDone = true;
			@exec(system::getCmdSudo() . 'chmod -R 755 ' . escapeshellarg($imagesDir) . ' > /dev/null 2>&1');
		}

		foreach (array($themeAbs, $lightAbs) as $absPath) {
			if (is_file($absPath) && is_readable($absPath)) {
				$data = @file_get_contents($absPath);
				if ($data !== false && strlen($data) > 0) {
					return self::$imageCache[$cacheKey] = 'data:image/png;base64,' . base64_encode($data);
				}
			}
		}

		$iconAbs = __DIR__ . '/../../plugin_info/jeedore_icon.png';
		if (is_file($iconAbs) && is_readable($iconAbs)) {
			$data = @file_get_contents($iconAbs);
			if ($data !== false && strlen($data) > 0) {
				return self::$imageCache[$cacheKey] = 'data:image/png;base64,' . base64_encode($data);
			}
		}
		return 'plugins/jeedore/plugin_info/jeedore_icon.png';
	}

	public function getImageState(){
		$tags = $this->getTags();
		$theme = config::byKey('theme', 'jeedore');
		if(!$theme){
			$theme = 'light';
		}
		$jeedoreLogo = "plugins/jeedore/plugin_info/jeedore_icon.png";

		switch($tags){
			case 'shutter':
			case 'klineShutter':
			case 'swingShutter':
			case 'awning':
				$cmd = jeedoreCmd::byLogicalId("position".$this->getLogicalId());
				if(!$cmd || !isset($cmd[0])){
					return $this->resolveImage("shutter_0.png", $theme);
				}
				$val = $cmd[0]->getValue();
				if(!is_numeric($val)){
					$val = $cmd[0]->execCmd();
				}
				$step = is_numeric($val) ? ((int)($val / 10)) * 10 : 0;
				$step = max(0, min(100, $step));
				return $this->resolveImage("shutter_".$step.".png", $theme);
			case 'hvac-electric':
				$outTemperature = $this->getCmd('info', "outTemperature".$this->getLogicalId());
				$thermicLevel = $this->getCmd('action', "thermicLevel".$this->getLogicalId());
				$hvacMode = $this->getCmd('info', "hvacMode".$this->getLogicalId());
				if($outTemperature){
					return $this->resolveImage("thermostat.png", $theme);
				}else{
					if($hvacMode){
						switch($hvacMode->getValue()){
							case 'STOP':
								return $this->resolveImage("electric_normal.png", $theme);
							case 'ANTI_FROST':
								return $this->resolveImage("electric_antifrost.png", $theme);
							default:
								return $this->resolveImage("electric_heating.png", $theme);
						}
					}
				}
				return $this->resolveImage("electric_normal.png", $theme);
			case 'scenario':
				return $this->resolveImage("scenario.png", $theme);
			case 'klineDoor':
				$cmd = jeedoreCmd::byLogicalId("openState".$this->getLogicalId());
				if(!$cmd || !isset($cmd[0])){
					return $this->resolveImage("door_locked.png", $theme);
				}
				return $cmd[0]->getValue() ? $this->resolveImage("door_locked.png", $theme) : $this->resolveImage("door_unlocked.png", $theme);
			case 'box':
				return $this->resolveImage("tydom1.png", $theme);
			case 'light':
			case 'plug':
				$cmd = jeedoreCmd::byLogicalId("level".$this->getLogicalId());
				if(!$cmd || !isset($cmd[0])){
					$plugCmd = $this->getCmd('info', "plugCmd".$this->getLogicalId());
					if($plugCmd && ($plugCmd->getValue() == 'ON' || $plugCmd->getValue() == '1' || $plugCmd->getValue() == 'True')){
						return $this->resolveImage("light_100.png", $theme);
					}
					return $tags === 'plug' ? $this->resolveImage("interrupteur.png", $theme) : $this->resolveImage("light_0.png", $theme);
				}
				$val = $cmd[0]->getValue();
				if(!is_numeric($val)){
					$val = $cmd[0]->execCmd();
				}
				$step = is_numeric($val) ? ((int)($val / 10)) * 10 : 0;
				$step = max(0, min(100, $step));
				return $this->resolveImage("light_".$step.".png", $theme);
			case 'hvac-boiler':
				$outTemperature = $this->getCmd('info', "outTemperature".$this->getLogicalId());
				$authorization = $this->getCmd('info', "authorization".$this->getLogicalId());
				$hvacMode = $this->getCmd('info', "hvacMode".$this->getLogicalId());

				if($outTemperature){
					return $this->resolveImage("thermostat.png", $theme);
				}else{
					if(!$authorization || !$hvacMode){
						return $this->resolveImage("boiler_normal.png", $theme);
					}
					if($authorization->getValue() == 'HEATING'){
						return $this->resolveImage("boiler_normal.png", $theme);
					}
					if($authorization->getValue() == 'STOP'){
						if($hvacMode->getValue() == 'ANTI_FROST'){
							return $this->resolveImage("boiler_antifrost.png", $theme);
						}
						return $this->resolveImage("boiler_normal.png", $theme);
					}
				}
				return $this->resolveImage("boiler_normal.png", $theme);
			case 'solar':
				return $this->resolveImage("solaire.png", $theme);
			case 'conso':
				return $this->resolveImage("conso.png", $theme);
			case 'others':
				return $this->resolveImage("others.png", $theme);
			case 'alarm':
				return $this->resolveImage("alarm.png", $theme);
			case 'motion':
				$motionDetect = $this->getCmd('info', "motionDetect".$this->getLogicalId());
				if($motionDetect){
					return $this->resolveImage("detecteurmouvement.png", $theme);
				}
				$techSmokeDefect = $this->getCmd('info', "techSmokeDefect".$this->getLogicalId());
				if($techSmokeDefect){
					return $this->resolveImage("detecteur-de-fumee.png", $theme);
				}
				return $this->resolveImage("detecteurmouvement.png", $theme);
			case 'klineWindowSliding':
				return $this->resolveImage("slidingdoor.png", $theme);
			case 'garage_door':
			case 'gate':
				return $this->resolveImage("garagedoor.png", $theme);
			case 'hvac-central':
				return $this->resolveImage("central.png", $theme);
			case 'toggle':
				$action = $this->getCmd('info', "action".$this->getLogicalId());
				if($action){
					return $this->resolveImage("interrupteur.png", $theme);
				}
				return $this->resolveImage("interrupteur.png", $theme);
			case '':
				$action = $this->getCmd('info', "action".$this->getLogicalId());
				$techSmokeDefect = $this->getCmd('info', "techSmokeDefect".$this->getLogicalId());
				$lightPower = $this->getCmd('info', "lightPower".$this->getLogicalId());
				if($action){
					return $this->resolveImage("interrupteur.png", $theme);
				}
				if($techSmokeDefect){
					return $this->resolveImage("detecteur-de-fumee.png", $theme);
				}
				if($lightPower){
					return $this->resolveImage("solaire.png", $theme);
				}
				break;
			case 'window':
			case 'windowFrench':
			case 'WindowFrench':
			case 'klineWindowFrench':
			case 'klineWindowOthers':
				$intrusionDetect = $this->getCmd('info', "intrusionDetect".$this->getLogicalId());
				if($intrusionDetect && $intrusionDetect->getValue() == 'True'){
					return $this->resolveImage("windowopen.png", $theme);
				}
				$openState = $this->getCmd('info', "openState".$this->getLogicalId());
				if($openState && ($openState->getValue() == 'OPENED' || $openState->getValue() == 'True' || $openState->getValue() == '1')){
					return $this->resolveImage("windowopen.png", $theme);
				}
				return $this->resolveImage("windowclose.png", $theme);
			case 'door':
			case 'belmDoor':
				$intrusionDetect = $this->getCmd('info', "intrusionDetect".$this->getLogicalId());
				if($intrusionDetect && $intrusionDetect->getValue() == 'True'){
					return $this->resolveImage("door_unlocked.png", $theme);
				}
				return $this->resolveImage("door_locked.png", $theme);
			case 'not-managed':
				$action = $this->getCmd('info', "action".$this->getLogicalId());
				$motionDetect = $this->getCmd('info', "motionDetect".$this->getLogicalId());
				$techSmokeDefect = $this->getCmd('info', "techSmokeDefect".$this->getLogicalId());
				$lightPower = $this->getCmd('info', "lightPower".$this->getLogicalId());
				if($action){
					return $this->resolveImage("interrupteur.png", $theme);
				}
				if($motionDetect){
					return $this->resolveImage("detecteurmouvement.png", $theme);
				}
				if($techSmokeDefect){
					return $this->resolveImage("detecteur-de-fumee.png", $theme);
				}
				if($lightPower){
					return $this->resolveImage("solaire.png", $theme);
				}
				break;
		}
		return $jeedoreLogo;
	}

	public function postConfig_architecture(){
		jeedore::createConfigurationFile();
	}


}
