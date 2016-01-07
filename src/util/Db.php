<?php

namespace Util;

class Db
{
    protected static $_instance = null;

    private
        $_primaryConnection = null,
        $_secondaryConnection = null;

    /**
     * @return Db
     */

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public function getPrimaryConnection()
    {
        if (null === $this->_primaryConnection) {
            $this->_primaryConnection = $this->_getConnection(DATABASE_DSN, DATABASE_USER, DATABASE_PASSWORD);
        }
        return $this->_primaryConnection;

    }

    public function getSecondaryConnection()
    {
        if (null === $this->_secondaryConnection) {
            if (!define('DATABASE_DSN_SECONDARY')) $this->_writeError('DATABASE ERROR :: secondary database params not set');
            $this->_secondaryConnection = $this->_getConnection(DATABASE_DSN_SECONDARY, DATABASE_USER_SECONDARY, DATABASE_PASSWORD_SECONDARY);
        }
        return $this->_secondaryConnection;

    }

    private function _getConnection($databaseDsn, $databaseUser, $databasePassword)
    {
        try {
            $opt = array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            );
            return new \PDO($databaseDsn, $databaseUser, $databasePassword, $opt);
        } catch (Exception $e) {
            $this->_writeError('DATABASE ERROR :: ' . $e->getMessage());
        }
    }

    private function _writeError($message)
    {
        throw new \Exception($message);
    }
}