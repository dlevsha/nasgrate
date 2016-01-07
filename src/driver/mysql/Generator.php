<?php

namespace Driver\Mysql;

use Driver\Base\Generator as AbstractGenerator;

class Generator extends AbstractGenerator
{
    const
        FIELD_DELIMITER = ',',
        DELIMITER = '~';


    /**
     * Modify base structure and split to section
     *
     * @param $data
     * @return array
     */
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
                $tables[$v['TABLE_NAME']]['indexes'][$v['INDEX_NAME']] = $v;
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
                    $sql[] = $this->_getTableSql($data, $tableName, $flipStructure, $isFlipIteration);
                } else {

                    // generate alter column
                    $renaimedRepository = null;
                    if (isset($data['columns'])) {
                        $previousColumnName = null;
                        foreach ($data['columns'] as $columnName => $col) {
                            $renaimed = null;
                            // is field was renaimed
                            if (isset($flipStructure[$tableName]['columns'])) {
                                $couid = $this->_getUid($col);
                                foreach ($flipStructure[$tableName]['columns'] as $columnNameFlip => $colFlip) {
                                    if ($couid == $this->_getUid($colFlip)) {
                                        $renaimed = array(
                                            'to' => $col,
                                            'from' => $colFlip,
                                        );
                                    }
                                }
                            }

                            if (isset($flipStructure[$tableName]['columns'][$columnName])) {
                                $sql[] = array(
                                    $isFlipIteration ? 'down' : 'up' => "ALTER TABLE `{$tableName}` CHANGE `{$columnName}` " . $this->_getColumnString($col),
                                );
                            } elseif ($renaimed) {
                                $sql[] = array(
                                    $isFlipIteration ? 'down' : 'up' => "ALTER TABLE `{$tableName}` CHANGE `{$renaimed['from']['COLUMN_NAME']}` " . $this->_getColumnString($renaimed['to']),
                                );
                                $renaimedRepository[] = $renaimed;
                            } else {
                                $sql[] = array(
                                    $isFlipIteration ? 'down' : 'up' => "ALTER TABLE `{$tableName}` ADD " . $this->_getColumnString($col) . ($previousColumnName ? " AFTER `{$previousColumnName}`" : null),
                                    $isFlipIteration ? 'up' : 'down' => "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`"
                                );
                            }
                            $previousColumnName = $columnName;
                        }
                    }

                    // определяем есть ли у нас индексы, которые не нужно переделывать, так как они возникли в результате переименования
                    if ($renaimedRepository) {
                        $renaimedRepositoryKey = array_map(function ($item) {
                            return $item['to']['COLUMN_NAME'];
                        }, $renaimedRepository);
                        $pktDiff = array();
                        foreach ($data['indexes'] as $name => $item) {
                            if (!isset($flipStructure[$tableName]['indexes'][$name])) continue;
                            $indexColumns = explode(self::FIELD_DELIMITER, $item['COLUMN_NAME']);
                            $flipIndexColumns = explode(self::FIELD_DELIMITER, $flipStructure[$tableName]['indexes'][$name]['COLUMN_NAME']);
                            $colDiff = array_diff($indexColumns, $flipIndexColumns);
                            $pktDiff = array_diff($colDiff, $renaimedRepositoryKey);
                            if (!$pktDiff) unset($data['indexes'][$name]);
                        }
                    }

                    // generate alter indexes
                    if (isset($data['indexes'])) {
                        foreach ($data['indexes'] as $indexName => $indexData) {
                            $indexString = $this->_getIndexString($indexData);
                            $sql[] = array(
                                $isFlipIteration ? 'down' : 'up' => "ALTER TABLE `{$tableName}` ADD " . $indexString,
                                $isFlipIteration ? 'up' : 'down' => "ALTER TABLE `{$tableName}` DROP " . ($indexData['INDEX_NAME'] == 'PRIMARY' ? ' PRIMARY KEY' : ' KEY `' . $indexName . '`')
                            );


                        }
                    }
                    // generate alter constrain
                    if (isset($data['constraints'])) {
                        foreach ($data['constraints'] as $constraintName => $indxData) {
                            $indexString = $this->_getIndexString($indexData);
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
        $indexGrouped = array();
        foreach ($data['indexes'] as $item) {
            if ($item['INDEX_NAME'] == 'PRIMARY') continue;
            $indexGrouped[$item['INDEX_NAME']][] = $item['COLUMN_NAME'];
        }

        $indexGrouped = array_filter($indexGrouped, function ($item) {
            return count($item) > 1;
        });

        foreach ($data['indexes'] as &$item) {
            if (isset($indexGrouped[$item['INDEX_NAME']])) {
                $item['COLUMN_NAME'] = implode(self::FIELD_DELIMITER, $indexGrouped[$item['INDEX_NAME']]);
            }
        }

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


    private function _getUid($col)
    {
        return
            str_replace(
                self::DELIMITER . $col['COLUMN_NAME'] . self::DELIMITER,
                self::DELIMITER . '$col$' . self::DELIMITER,
                implode(self::DELIMITER, array_values($col)));
    }


    /**
     * Create uid based on certain table fields
     *
     * @param $tableData
     * @return mixed
     */
    private function _getTableUid($tableData)
    {

        foreach (array('TABLE_CATALOG', 'TABLE_SCHEMA', 'TABLE_TYPE', 'ENGINE', 'TABLE_COLLATION', 'TABLE_COMMENT') as $fieldKey) {
            $tableKey[$fieldKey] = $tableData['table'][$fieldKey];
        }

        foreach (array('columns', 'indexes', 'constraints') as $tableSchemaPart) {
            if (isset($tableData[$tableSchemaPart])) {
                foreach ($tableData[$tableSchemaPart] as $key => &$objectParams) {
                    $objectParams['TABLE_NAME'] = null;
                }
            }
        }

        $tableData['table'] = $tableKey;
        return $tableData;
    }

    /**
     * Get table SQL
     *
     * @param $data
     * @param $tableName
     * @param $flipData
     * @param bool|false $isFlip
     * @return array
     */
    private function _getTableSql($data, $tableName, $flipData, $isFlip = false)
    {

        // skip if tableName is version table name
        if ($tableName == VERSION_TABLE_NAME) return array();

        // detect if table was only renaimed
        $tableKey = md5(serialize($this->_getTableUid($data)));
        foreach ($flipData as $flipTableName => $flipTableData) {
            if (isset($flipTableData['table'])) {
                $flipTableKey = md5(serialize($this->_getTableUid($flipTableData)));

                if ($tableKey == $flipTableKey) {
                    $sql[$isFlip ? 'down' : 'up'] = "ALTER TABLE `{$flipTableName}` RENAME `{$tableName}`";
                    // $sql[$isFlip ? 'up' : 'down'] = "ALTER TABLE `{$tableName}` RENAME `{$flipTableName}`";
                    return $sql;
                }
            }
        }

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


    /**
     * Get column SQL
     *
     * @param $col
     * @return string
     */
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

    /**
     * Get indexes SQL
     *
     * @param $indxData
     * @return string
     */
    private function _getIndexString($indxData)
    {
        if ($indxData['INDEX_NAME'] == 'PRIMARY') {
            return "PRIMARY KEY (`{$indxData['COLUMN_NAME']}`)";
        } else {
            $cols = explode(self::FIELD_DELIMITER, $indxData['COLUMN_NAME']);
            return ($indxData['NON_UNIQUE'] == '0' ? 'UNIQUE ' : '') . ($indxData['INDEX_TYPE'] != 'BTREE' ? $indxData['INDEX_TYPE'] . ' ' : '') . "KEY `{$indxData['INDEX_NAME']}` (`" . implode('`, `', $cols) . "`)";
        }
    }

    /**
     * Get constrain SQL
     *
     * @param $indxData
     * @return string
     */
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