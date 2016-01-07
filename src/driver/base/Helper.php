<?php
namespace Driver\Base;

use Util\Db as Db;

abstract class Helper
{
    public function getLastMigrationId()
    {
        $stmt = $this->_getDb()->prepare('SELECT MAX(version) maxVersion FROM ' . VERSION_TABLE_NAME);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['maxVersion'] ?: 0;
    }

    public function addVersionId($versionId)
    {
        // $versionId = preg_replace('/[^\d]+/', '', $versionId);
        $stmt = $this->_getDb()->prepare('INSERT INTO ' . VERSION_TABLE_NAME . ' (version) VALUES(?)');
        $stmt->execute(array($versionId));
        return $this;
    }

    public function removeVersionId($versionId)
    {
        // $versionId = preg_replace('/[^\d]+/', '', $versionId);
        $stmt = $this->_getDb()->prepare('DELETE FROM ' . VERSION_TABLE_NAME . ' WHERE version = ?');
        $stmt->execute(array($versionId));
        return $this;
    }

    public function getMigrationIdFromBase()
    {
        $stmt = $this->_getDb()->prepare('SELECT version FROM ' . VERSION_TABLE_NAME);
        $stmt->execute();
        return array_map(
            function ($item) {
                return $item['version'];
            },
            $stmt->fetchAll()
        );
    }

    protected function _getDb()
    {
        return Db::getInstance()->getPrimaryConnection();
    }

    abstract public function createMigrationTable();

}