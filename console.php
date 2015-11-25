<?php

require_once 'config.php';

$migration = Process\Console::getInstance();

if (!isset($_SERVER['argv'][1])) {
    echo $migration->getHelp();
    exit;
}

switch ($_SERVER['argv'][1]) {
    case "generate":
        $migration->generate(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null, isset($_SERVER['argv'][3]) ? $_SERVER['argv'][3] : null);
        break;
    case "status":
        $migration->status();
        break;
    case "up:show":
        $migration->upShow(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case "down:show":
        $migration->downShow(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case "up":
    case "up:run":
        $migration->up(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case "down":
    case "down:run":
        $migration->down(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case "undo":
        $migration->undo(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case "redo":
        $migration->redo(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case "list":
        $migration->mlist();
        break;
    case "help":
        echo $migration->getHelp();
        break;
    default:
        Console::getInstance()->writeError('Command ' . $_SERVER['argv'][1] . ' not found');
}