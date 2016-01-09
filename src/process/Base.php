<?php

namespace Process;

use Util\Db as Db;
use Driver\Base\Migration as Migration;

abstract class Base
{
    const
        TEMPLATE_FILE = 'template.sql';

    protected
        $_helper = null,
        $_migrationFiles = null,
        $_connection = null;


    public function getLastMigrationId()
    {
        return $this->_getHelper()->getLastMigrationId();
    }


    // ---------------------------
    public function upShow($migrationId = null)
    {
        $content = $this->_getUpSql($migrationId);
        $this->_sqlContentShow($content);
        return $this;
    }

    public function downShow($migrationId)
    {
        if (!$migrationId) $this->_writeError('ERROR :: migration ID not set' . "\n" . "Example: php console.php down::show 000000000000000");
        $content = $this->_getDownSql($migrationId);
        $this->_sqlContentShow($content);
        return $this;
    }


    protected function _sqlContentShow($content)
    {
        if ($content) {
            foreach ($content as $item) {
                $migrationId = 'Migration :: ' . $item['migrationId'];
                $this->_write("\n" . str_repeat("=", strlen($migrationId)));
                $this->_write("\n" . $migrationId);
                if ($item['description']) {
                    $this->_write('Description: ' . $item['description']);
                }
                $this->_write("\n\033[33m" . implode("\n----\n", $item['sql']) . "\033[0m\n");
            }
        } else {
            $this->_write('No actual migrations!');
        }
    }
    // ---------------------------


    // ---------------------------
    public function up($migrationId = null)
    {
        $content = $this->_getUpSql($migrationId);
        $this->_sqlContentRun($content);
    }

    public function down($migrationId = null)
    {
        $content = $this->_getDownSql($migrationId);
        $this->_sqlContentRun($content, false);
    }

    public function undo($migrationId)
    {
        foreach ($this->_getDownSql($migrationId, false) as $item) {
            if ($item['migrationId'] == $migrationId) {
                return $this->_sqlContentRun(array($item), false);
            }
        }
        $this->_writeError('Migration ' . $migrationId . ' not found');
    }

    public function redo($migrationId)
    {
        foreach ($this->_getUpSql($migrationId) as $item) {
            if ($item['migrationId'] == $migrationId) {
                return $this->_sqlContentRun(array($item), true);
            }
        }
        $this->_writeError('Migration ' . $migrationId . ' not found');

    }


    protected function _sqlContentRun($content, $isUp = true)
    {
        if ($content) {
            foreach ($content as $item) {
                $migrationId = 'Migration :: ' . $item['migrationId'];
                $this->_write("\n" . str_repeat("=", strlen($migrationId)));
                $this->_write("\n" . $migrationId);
                if ($item['description']) {
                    $this->_write('Description: ' . $item['description']);
                }

                foreach ($item['sql'] as $sqlQuery) {
                    try {
                        $this->_write("\n\033[33m" . $sqlQuery . "\033[0m\n");
                        $this->_getDb()->exec($sqlQuery);
                    } catch (Exception $e) {
                        $this->_writeError($sqlQuery . "\n\nDATABASE ERROR :: " . $e->getMessage());
                    }
                }
                if ($isUp) {
                    $this->_addVersionId($item['migrationId']);
                } else {
                    $this->_removeVersionId($item['migrationId']);
                }
                $this->_write('... complete');
            }
        } else {
            $this->_write('No actual migrations!');
        }
    }

    // ---------------------------


    public function mlist()
    {
        $migrationInBase = $this->getMigrationIdFromBase();
        $migrationFromFiles = $this->_getMigrations();
        $this->_write("\n\033[33m" . (!$migrationFromFiles ? 'No actual migrations' : 'Migration list:') . "\033[0m");
        rsort($migrationFromFiles);
        foreach ($migrationFromFiles as $migrationId) {
            preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $migrationId, $parts);
            $this->_write(' - ' . '[' . ($parts[3] . '.' . $parts[2] . '.' . $parts[1] . ' ' . $parts[4] . ':' . $parts[5] . ':' . $parts[6]) . '] ' . "\033[33m" . str_replace(".php", "", $migrationId) . "\033[0m" . ' - ' . (in_array($migrationId, $migrationInBase) ? 'executed' : 'new'));
        }
        $this->_write("\n");
    }


    public function status()
    {
        $this->_write("\n\033[33mLast Migration ID: " . ($this->getLastMigrationId() ? $this->getLastMigrationId() : ' no migrations') . "\033[0m\n");

        if ($this->_getActualMigrations()) {
            $this->_write('Available Migrations:');
            foreach ($this->_getActualMigrations() as $item) {
                $this->_write(' + ' . $item);
            }
        } else {
            $this->_write("\n" . 'Available Migrations: No actual migrations');
        }
        $this->_write("\n");
        return $this;
    }

    protected function _getUpSql($migrationId = null)
    {
        $out = array();
        $actualMigrationId = $this->_getActualMigrations();
        if ($migrationId && !in_array($migrationId, $actualMigrationId) && !in_array($migrationId, array_map(function ($item) {
                return substr($item, 0, 14);
            }, $actualMigrationId))
        ) $this->_writeError('Wrong Migration ID: ' . $migrationId);

        foreach ($actualMigrationId as $currentMigrationId) {
            preg_match('/^(\d{14})/', $currentMigrationId, $s);
            $currentMigrationIdOnlyDigits = $s[1];
            if ($migrationId && $currentMigrationIdOnlyDigits > $migrationId) continue;

            $migration = new Migration($currentMigrationId);

            if ($migration->isSkip()) continue;
            $out[] = array(
                'migrationId' => $migration->getMigrationId(),
                'sql' => $migration->getUpSql(),
                'description' => $migration->getDescription()
            );
        }
        return $out;
    }

    protected function _getDownSql($migrationId, $checkLastMigrationId = true)
    {
        if (!$migrationId) $this->_writeError('Need Migration ID');
        $allMigrationId = $this->_getMigrations();
        if (!$allMigrationId) $this->_writeError('No actual migrations');
        if (!in_array($migrationId, $allMigrationId) && !in_array($migrationId, array_map(function ($item) {
                return substr($item, 0, 14);
            }, $allMigrationId))
        ) $this->_writeError('Wrong Migration ID: migration ' . $migrationId . ' not exist');

        if ($this->getLastMigrationId()) {
            preg_match('/^(\d{14})/', $this->getLastMigrationId(), $s);
            $lastMigrationIdOnlyDigits = $s[1];
        } else {
            $lastMigrationIdOnlyDigits = 0;
        }
        preg_match('/^(\d{14})/', $migrationId, $s);
        $currentMigrationIdOnlyDigits = $s[1];


        $validMigrationId = array();
        if ($checkLastMigrationId) {
            foreach ($allMigrationId as $currentMigrationId) {
                preg_match('/^(\d{14})/', $currentMigrationId, $part);
                if ($part[0] > $lastMigrationIdOnlyDigits || $part[0] < $currentMigrationIdOnlyDigits) continue;
                $validMigrationId[] = $currentMigrationId;
            }
        } else {
            $validMigrationId = $allMigrationId;
        }

        if (!$validMigrationId) return array();

        rsort($validMigrationId);

        $out = array();
        foreach ($validMigrationId as $currentMigrationId) {
            $migration = new Migration($currentMigrationId);
            $out[] = array(
                'migrationId' => $migration->getMigrationId(),
                'sql' => $migration->getDownSql(),
                'description' => $migration->getDescription()
            );
        }
        return $out;
    }


    protected function _getMigrations()
    {
        if (null === $this->_migrationFiles) {
            $this->_migrationFiles = array();
            if (!file_exists(DIR_MIGRATION)) $this->_writeError('FILE SYSTEM ERROR :: Directory ' . DIR_MIGRATION . ' doesn\'t exists');
            if (!is_writable(DIR_MIGRATION)) $this->_writeError('FILE SYSTEM ERROR :: Directory ' . DIR_MIGRATION . ' is not writable');
            foreach (new \DirectoryIterator(DIR_MIGRATION) as $fileInfo) {
                if ($fileInfo->isDot() || !preg_match('/^([\d]{14}).+\.' . FILE_EXTENSION . '$/', $fileInfo->getFilename(), $s)) continue;
                $this->_migrationFiles[] = str_replace("." . FILE_EXTENSION, "", $fileInfo->getFilename());
            }
            sort($this->_migrationFiles);
        }
        return $this->_migrationFiles;
    }

    protected function _getActualMigrations()
    {
        $out = array();
        $migrationInBase = $this->getMigrationIdFromBase();
        foreach ($this->_getMigrations() as $migrationId) {
            if (!in_array($migrationId, $migrationInBase)) $out[] = $migrationId;
        }
        return $out;
    }

    protected function _addVersionId($versionId)
    {
        $this->_getHelper()->addVersionId($versionId);
        return $this;
    }

    protected function _removeVersionId($versionId)
    {
        $this->_getHelper()->removeVersionId($versionId);
        return $this;
    }

    public function getMigrationIdFromBase()
    {
        return $this->_getHelper()->getMigrationIdFromBase();
    }

    protected function _createMigrationTable()
    {
        $this->_getHelper()->createMigrationTable();
        return $this;
    }

    protected function _getTemplatePath()
    {
        return DIR_ROOT.'/src/'.self::TEMPLATE_FILE;
    }

    private function _getHelper()
    {
        if (null === $this->_helper) {
            switch (DATABASE_DRIVER) {
                case 'mysql':
                    $this->_helper = new \Driver\Mysql\Helper();
                    break;
                default:
                    throw new \Exception('Unrecognized driver ' . DATABASE_DRIVER);
            }
        }
        return $this->_helper;
    }

    protected function _getDb()
    {
        return Db::getInstance()->getPrimaryConnection();
    }

    abstract protected function _write($message);

    abstract protected function _writeError($message);
}