<?php

require_once dirname(__FILE__).'/../src/config.php';

use Process\Console as Migration;

$migration = Migration::getInstance();

/*
$_SERVER['argv'] = array(
    1 => 'generate',
    2 => 'Test',
    3 => 'diff'
);
*/

if (!isset($_SERVER['argv'][1])) {
    echo $migration->getHelp();
    exit;
}

switch ($_SERVER['argv'][1]) {
    case Migration::STATUS_GENERATE:
    case Migration::STATUS_DIFF:
        $migration->generate(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null, $_SERVER['argv'][3] == Migration::STATUS_DIFF ? 'diff' : null );
        break;
    case Migration::STATUS_STATUS:
        $migration->status();
        break;
    case Migration::STATUS_UP_SHOW:
        $migration->upShow(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case Migration::STATUS_DOWN_SHOW:
        $migration->downShow(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case Migration::STATUS_UP_RUN:
        $migration->up(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case Migration::STATUS_DOWN_RUN:
        $migration->down(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case Migration::STATUS_UNDO:
        $migration->undo(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case Migration::STATUS_REDO:
        $migration->redo(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null);
        break;
    case Migration::STATUS_LIST:
        $migration->mlist();
        break;
    case Migration::STATUS_HELP:
        echo $migration->getHelp();
        break;
    default:
        \Util\Console::getInstance()->writeError('Command ' . $_SERVER['argv'][1] . ' not found');
}