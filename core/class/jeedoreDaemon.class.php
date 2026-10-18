<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

/**
 * Classe de communication avec le démon local .NET (Rezolv.JeeDore.Server).
 * Envoie les ordres au démon via un socket TCP sur 127.0.0.1:13666 au format JSON.
 */
class JeedoreDaemon
{
    /**
     * Exécute une commande auprès du démon JeeDore.
     *
     * @param string     $action Nom de la commande destinée au démon (ex: 'geteqlogics', 'setPosition', 'setLevel', ...)
     * @param array|null $args   Paramètres optionnels associés à la commande (ex: ['deviceId' => ..., 'position' => ...])
     * @return mixed             Réponse JSON décodée retournée par le démon
     */
    public static function execute($action, $args = null) {
        $cmd = new JeedoreDaemonCommand();
        $cmd->command = $action;
        if(isset($args)){
            $cmd->parameters = $args;
        }
        $json = json_encode($cmd);

        // Ouverture d'une connexion socket TCP locale vers le démon .NET (port 13666 par défaut)
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, "127.0.0.1", 13666);
		socket_write($socket, trim($json), strlen(trim($json)));
		$ret = socket_read($socket, 1024);
        
        return json_decode($ret);
    }
}

/**
 * Structure de données sérialisée en JSON pour envoyer une commande au démon.
 */
class JeedoreDaemonCommand{
    /** @var string Nom de la commande à exécuter par le démon */
    public $command;
}

/**
 * Utilitaire de débogage permettant d'écrire le contenu complet d'un objet (var_dump) dans les logs Jeedom.
 */
class DumpObj
{
    /**
     * Journalise le dump d'une variable dans le log debug du plugin 'jeedore'.
     *
     * @param mixed $obj Objet ou tableau à inspecter
     */
    public static function log($obj)
    {
        ob_start();
        var_dump($obj);
        $result = ob_get_clean();
        log::add('jeedore', 'debug', $result);
    }
}
?>