<?php

namespace Driver\Mysql;

use Driver\Base\Generator as AbstractGenerator;

class Generator extends AbstractGenerator
{
    const
        DELIMITER = '~';


    private function _getStructure($data)
    {
        $tables = array();
        foreach ($data as $k => $v) {
            if (isset($v['TABLE_SCHEMA'])) {
                $tables[$v['TABLE_NAME']]['table'] = $v;
            }
            if (isset($v['COLUMN_TYPE'])) {
                $tables[$v['TABLE_NAME']]['columns'][$v['COLUMN_NAME']] = $v;
            }
            if (isset($v['INDEX_NAME'])) {
                $tables[$v['TABLE_NAME']]['indexes'][$v['INDEX_NAME']][$v['COLUMN_NAME']] = $v;
            }
            if (isset($v['REFERENCED_TABLE_NAME'])) {
                $tables[$v['TABLE_NAME']]['constraints'][$v['CONSTRAINT_NAME']] = $v;
            }
        }
        return $tables;
    }


    protected function _generateSql($dataBefore, $dataAfter)
    {
        $sql = array();
        $isFlipIteration = false;
        foreach (array($dataBefore, $dataAfter) as $data) {
            $flipStructure = $this->_getStructure($isFlipIteration ? $dataBefore : $dataAfter);
            foreach ($this->_getStructure($isFlipIteration ? $dataAfter : $dataBefore) as $tableName => $data) {
                if (isset($data['table'])) {
                    // generate full table SQL
                    $sql[] = $this->_getTableSql($data, $tableName, $isFlipIteration);
                } else {
                    // generate alter column
                    if (isset($data['columns'])) {
                        $previousColumnName = null;
                        foreach ($data['columns'] as $columnName => $col) {
                            if (isset($flipStructure[$tableName]['columns'][$columnName])) {
                                $sql[] = array(
                                    $isFlipIteration ? 'down' : 'up' => "ALTER TABLE `{$tableName}` CHANGE `{$columnName}` " . $this->_getColumnString($col),
                                    $isFlipIteration ? 'up' : 'down' => "ALTER TABLE `{$tableName}` CHANGE `{$columnName}` " . $this->_getColumnString($flipStructure[$tableName]['columns'][$columnName])
                                );
                            } else {
                                $sql[] = array(
                                    $isFlipIteration ? 'down' : 'up' => "ALTER TABLE `{$tableName}` ADD " . $this->_getColumnString($col) . ($previousColumnName ? " AFTER `{$previousColumnName}`" : null),
                                    $isFlipIteration ? 'up' : 'down' => "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`"
                                );
                            }
                            $previousColumnName = $columnName;
                        }
                    }
                    // generate alter indexes
                    if (isset($data['indexes'])) {
                        foreach ($data['indexes'] as $indexName => $indexData) {
                            $indexString = $this->_getIndexString($indexData);
                            $firstIndexData = reset($indexData);
                            $sql[] = array(
                                $isFlipIteration ? 'down' : 'up' => "ALTER TABLE `{$tableName}` ADD " . $indexString,
                                $isFlipIteration ? 'up' : 'down' => "ALTER TABLE `{$tableName}` DROP " . ($firstIndexData['INDEX_NAME'] == 'PRIMARY' ? ' PRIMARY KEY' : ' KEY `' . $indexName . '`')
                            );


                        }
                    }
                    // generate alter constrain
                    if (isset($data['constraints'])) {
                        foreach ($data['constraints'] as $constraintName => $indxData) {
                            $indexString = $this->_getIndexString($indexData);
                            $firstIndexData = reset($indexData);
                            $sql[] = array(
                                $isFlipIteration ? 'down' : 'up' => "ALTER TABLE `{$tableName}` ADD " . $indexString,
                                $isFlipIteration ? 'up' : 'down' => "ALTER TABLE `{$tableName}` DROP `" . $constraintName . '`'
                            );


                        }
                    }
                }
            }
            $isFlipIteration = true;
        }
        return $sql;
    }

    protected function _convertArray(array $data)
    {
        $out = array();
        foreach ($data as $type => $items) {
            foreach ($items as $item) {
                if ($type == 'tables') {
                    $u = array();
                    foreach (array('TABLE_NAME', 'ENGINE', 'TABLE_COLLATION', 'TABLE_COMMENT') as $key) {
                        $u[] = $item[$key];
                    }
                    $uid = implode(self::DELIMITER, $u);
                } else {
                    $uid = implode(self::DELIMITER, array_values($item));
                }
                $out[$type . self::DELIMITER . $uid] = $item;
            }
        }
        return $out;
    }

    /**
     * @param $data
     * @param $tableName
     * @param $sql
     * @return array
     */
    private function _getTableSql($data, $tableName, $isFlip = false)
    {
        $columns = array();
        foreach ($data['columns'] as $col) {
            $columns[] = $this->_getColumnString($col);
        }
        $indexes = array();
        if (isset($data['indexes'])) {
            foreach ($data['indexes'] as $indexName => $indxData) {
                $indexes[] = $this->_getIndexString($indxData);
            }
        }
        $constrains = array();
        if (isset($data['constraints'])) {
            foreach ($data['constraints'] as $constraintName => $indxData) {
                $constrains[] = $this->_getConstrainString($indxData);
            }
        }
        $charset = explode("_", $data['table']['TABLE_COLLATION']);
        $charset = reset($charset);
        $tableComment = addslashes($data['table']['TABLE_COMMENT']);
        $sql[$isFlip ? 'down' : 'up'] = "CREATE TABLE `{$tableName}` (\n    " . implode(",\n    ", array_merge($columns, $indexes, $constrains)) . "\n) ENGINE={$data['table']['ENGINE']} AUTO_INCREMENT=1 DEFAULT CHARSET={$charset} " . ($data['table']['TABLE_COMMENT'] ? " COMMENT '{$tableComment}'" : '');
        $sql[$isFlip ? 'up' : 'down'] = "DROP TABLE IF EXISTS `{$tableName}`";
        return $sql;
    }


    private function _getColumnString($col)
    {
        $nullCondition = $col['IS_NULLABLE'] == 'YES' ? '' : 'NOT NULL';
        if ($col['COLUMN_DEFAULT']) {
            $defaultCondition = 'DEFAULT \'' . $col['COLUMN_DEFAULT'] . '\'';
        } elseif ($col['IS_NULLABLE'] == 'YES') {
            $defaultCondition = 'DEFAULT NULL';
        } else {
            $defaultCondition = '';
        }
        $commentCondition = $col['COLUMN_COMMENT'] ? " COMMENT '" . addslashes($col['COLUMN_COMMENT']) . "'" : "";
        return "`{$col['COLUMN_NAME']}` {$col['COLUMN_TYPE']} {$nullCondition} {$defaultCondition} {$col['EXTRA']}{$commentCondition}";
    }

    private function _getIndexString($indxData)
    {
        $firstCol = reset($indxData);
        if ($firstCol['INDEX_NAME'] == 'PRIMARY') {
            return "PRIMARY KEY (`{$firstCol['COLUMN_NAME']}`)";
        } else {
            $cols = array_keys($indxData);
            return ($firstCol['NON_UNIQUE'] == '0' ? 'UNIQUE ' : '') . ($firstCol['INDEX_TYPE'] != 'BTREE' ? $firstCol['INDEX_TYPE'] . ' ' : '') . "KEY `{$firstCol['INDEX_NAME']}` (`" . implode('`, `', $cols) . "`)";
        }
    }

    private function _getConstrainString($indxData)
    {
        $constrStr = "CONSTRAINT `{$indxData['CONSTRAINT_NAME']}` FOREIGN KEY (`{$indxData['COLUMN_NAME']}`) REFERENCES `{$indxData['REFERENCED_TABLE_NAME']}` (`{$indxData['REFERENCED_COLUMN_NAME']}`)";
        if ($indxData['DELETE_RULE'] != 'RESTRICT') $constrStr .= ' ON DELETE ' . $indxData['DELETE_RULE'];
        if ($indxData['UPDATE_RULE'] != 'RESTRICT') $constrStr .= ' ON UPDATE ' . $indxData['UPDATE_RULE'];
        return $constrStr;
    }

    protected function _getDump()
    {
        if (null === $this->_dump) {
            $this->_dump = new Dump();
        }
        return $this->_dump;
    }

}