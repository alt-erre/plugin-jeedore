<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class JeedoreDaemon
{
    public static function execute($action, $args = null) {
        $cmd = new JeedoreDaemonCommand();
        $cmd->command = $action;
        if(isset($args)){
            $cmd->parameters = $args;
        }
        $json = json_encode($cmd);

        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, "127.0.0.1", 13666);
		socket_write($socket, trim($json), strlen(trim($json)));
		$ret = socket_read($socket, 1024);
        
        return json_decode($ret);
    }
}

class JeedoreDaemonCommand{
    public $command;
}

class DumpObj
{
    public static function log($obj)
    {
        ob_start();
        var_dump($obj);
        $result = ob_get_clean();
        log::add('jeedore', 'debug', $result);
    }
}
?>