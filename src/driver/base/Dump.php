<?php

namespace Driver\Base;

use  Util\Db as Db;

abstract class Dump
{

    protected
        $_dbConnection = null;

    public function getState()
    {
        return array(
            'tables' => $this->_getTablesState(),
            'columns' => $this->_getColumnsState(),
            'indexes' => $this->_getIndexState(),
            'constrains' => $this->_getConstrain()
        );
    }


    public function getLastSavedState()
    {
        if ($this->_getLastSavedStateFileName()) {
            return unserialize(file_get_contents(DIR_DBSTATE . '/' . $this->_getLastSavedStateFileName()));
        } else {
            return $this->_getEmptyState();
        }
    }

    public function getStateSerialized()
    {
        return serialize($this->getState());
    }

    abstract protected function _getTablesState();

    abstract protected function _getColumnsState();

    abstract protected function _getIndexState();

    abstract protected function _getConstrain();

    public function setConnection(\PDO $connection)
    {
        $this->_dbConnection = $connection;
        return $this;
    }

    protected function _getDb()
    {
        if (null === $this->_dbConnection) {
            $this->_dbConnection = Db::getInstance()->getPrimaryConnection();
        }
        return $this->_dbConnection;
    }


    private function _getLastSavedStateFileName()
    {
        $dbStateId = array();
        foreach (new \DirectoryIterator(DIR_DBSTATE) as $fileInfo) {
            if ($fileInfo->isDot() || !preg_match('/^(\d+)/', $fileInfo->getFilename(), $s)) continue;
            $dbStateId[$s[1]] = $fileInfo->getFilename();
        }
        arsort($dbStateId);
        return reset($dbStateId);
    }

    private function _getEmptyState()
    {
        return array(
            'tables' => array(),
            'columns' => array(),
            'indexes' => array(),
            'constrains' => array()
        );
    }


}