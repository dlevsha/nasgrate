<?php

require_once 'config.php';
require_once 'lib/Migration.php';
require_once 'lib/Console.php';
require_once 'lib/Migration.php';
require_once 'lib/Migration/Abstract.php';

$migration = Migration::getInstance();

if(!isset($_SERVER['argv'][1])) {
    echo $migration->getHelp();
    exit;
}

switch($_SERVER['argv'][1]){
    case "generate":

        $migration->generate($_SERVER['argv'][2], isset($_SERVER['argv'][3]) ? $_SERVER['argv'][3] : null);
        break;
    case "status":
        $migration->status();
        break;
    case "up:show":
        $migration->upShow( isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null );
        break;
    case "down:show":
        $migration->downShow( isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null );
        break;
    case "up:run":
        $migration->up( isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null );
        break;
    case "down:run":
        $migration->down( isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null );
        break;
    case "list":
        $migration->migrationList();
        break;
    case "help":
        echo $migration->getHelp();
        break;
    default:
        Console::getInstance()->writeError('Command '.$_SERVER['argv'][1].' not found');
}