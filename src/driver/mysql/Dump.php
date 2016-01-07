<?php

namespace Driver\Mysql;

use Driver\Base\Dump as AbstractDump;

class Dump extends AbstractDump
{

    protected function _getTablesState()
    {
        $stmt = $this->_getDb()->prepare(
            'SELECT * FROM information_schema.TABLES
                  WHERE
                    TABLE_SCHEMA = :baseName AND
                    TABLE_TYPE = \'BASE TABLE\'
                  ORDER BY
                    TABLE_SCHEMA');
        $stmt->execute(array(':baseName' => DATABASE_NAME));
        return $stmt->fetchAll();
    }

    protected function _getColumnsState()
    {
        $stmt = $this->_getDb()->prepare(
            'SELECT DISTINCT
                    cl.TABLE_NAME,
                    cl.COLUMN_NAME,
                    cl.COLUMN_TYPE,
                    cl.COLUMN_DEFAULT,
                    cl.COLUMN_COMMENT,
                    cl.EXTRA,
                    cl.CHARACTER_SET_NAME,
                    cl.IS_NULLABLE
                  FROM information_schema.columns cl,  information_schema.TABLES ss
                  WHERE
                    cl.TABLE_NAME = ss.TABLE_NAME AND
                    cl.TABLE_SCHEMA = :baseName AND
                    ss.TABLE_TYPE = \'BASE TABLE\'
                  ORDER BY
                    cl.table_name, cl.ORDINAL_POSITION');
        $stmt->execute(array(':baseName' => DATABASE_NAME));
        return $stmt->fetchAll();
    }

    protected function _getIndexState()
    {
        $stmt = $this->_getDb()->prepare(
            'SELECT DISTINCT
                    TABLE_NAME,
                    INDEX_NAME,
                    COLUMN_NAME,
                    INDEX_TYPE,
                    NON_UNIQUE,
                    SEQ_IN_INDEX
                  FROM INFORMATION_SCHEMA.STATISTICS
                  WHERE
                    TABLE_SCHEMA = :baseName
                  ORDER BY
                    TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX');
        $stmt->execute(array(':baseName' => DATABASE_NAME));
        return $stmt->fetchAll();
    }

    protected function _getConstrain()
    {
        $stmt = $this->_getDb()->prepare(
            'SELECT
                u.CONSTRAINT_NAME,
                u.TABLE_NAME,
                u.COLUMN_NAME,
                u.REFERENCED_TABLE_NAME,
                u.REFERENCED_COLUMN_NAME,
                r.DELETE_RULE,
                r.UPDATE_RULE
            FROM
                information_schema.KEY_COLUMN_USAGE u,
                information_schema.REFERENTIAL_CONSTRAINTS r
            WHERE
                u.CONSTRAINT_NAME = r.CONSTRAINT_NAME AND
                u.REFERENCED_TABLE_NAME IS NOT NULL AND
                u.CONSTRAINT_SCHEMA = r.CONSTRAINT_SCHEMA AND
                u.CONSTRAINT_SCHEMA = :baseName');
        $stmt->execute(array(':baseName' => DATABASE_NAME));
        return $stmt->fetchAll();
    }


}