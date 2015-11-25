<?php

namespace Driver\Mysql;

use Driver\Base\Helper as AbstractHelper;

class Helper extends AbstractHelper
{
    public function createMigrationTable()
    {
        $stmt = $this->_getDb()->prepare('SHOW TABLES LIKE \'' . VERSION_TABLE_NAME . '\'');
        $stmt->execute();
        if (!$stmt->fetch()) {
            $this->_getDb()->exec('CREATE TABLE ' . VERSION_TABLE_NAME . ' ( version varchar(255) NOT NULL, PRIMARY KEY (version) ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
        }
    }
}