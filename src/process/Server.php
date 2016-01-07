<?php

namespace Process;

use Driver\Base\Migration as Migration;

class Server extends Base
{
    private
        $_messages = array();

    protected static $_instance = null;

    /**
     * @return Migration
     */

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
            self::$_instance->_createMigrationTable();
        }
        return self::$_instance;
    }

    protected function _write($message)
    {
        $this->_messages[] = $message;
        return $this;
    }

    protected function _writeError($message)
    {
        throw new Exception($message);
    }

    public function getSql()
    {
        $out = array();
        $executedMigrations = $this->getMigrationIdFromBase();
        $migrations = $this->_getMigrations();
        rsort($migrations);
        foreach ($migrations as $migrationId) {
            $migration = new Migration($migrationId);
            $migration->setIsExecuted(in_array($migrationId, $executedMigrations));
            $out[] = $migration;
        }
        return $out;
    }
}