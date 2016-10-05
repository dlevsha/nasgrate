<?php

namespace Process;

use Util\Console as UtilConsole;

class Console extends Base
{
    const
        STATUS_GENERATE   = 'generate',
        STATUS_DIFF       = 'diff',
        STATUS_STATUS     = 'status',
        STATUS_UP_SHOW    = 'up:show',
        STATUS_DOWN_SHOW  = 'down:show',
        STATUS_UP_RUN     = 'up',
        STATUS_DOWN_RUN   = 'down',
        STATUS_UNDO       = 'undo',
        STATUS_REDO       = 'redo',
        STATUS_LIST       = 'list',
        STATUS_HELP       = 'help';

    protected static $_instance = null;

    private
        $_dumper = null,
        $_generator = null;

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
        UtilConsole::getInstance()->write($message);
    }

    protected function _writeError($message)
    {
        UtilConsole::getInstance()->writeError($message);
    }

    public function generate($migrationName = null, $commands = null)
    {
        if (!$migrationName) $this->_writeError("Migration name is required.\nUse: _$ php nasgrate generate [migration_name]");
        if (preg_match('/[^A-z0-9-\.\_ ]/', $migrationName, $s)) $this->_writeError("Migration name contain wrong symbol: [" . $s[0] . ']');
        $time = date('YmdHis');

        $name = $time . '_' . implode("_", array_map(function ($item) {
                return ucfirst($item);
            }, preg_split('/[-\_\. ]/', $migrationName)));
        $content = str_replace(
            array(
                '{{ name }}',
                '{{ date }}',
                '{{ description }}',
            ),
            array(
                $migrationName,
                date('d.m.Y H:i:s'),
                DEFAULT_DESCRIPTION_MESSAGE
            ),
            file_get_contents($this->_getTemplatePath())
        );

        if ($commands) {
            foreach (explode('|', $commands) as $block) {
                if (strpos($block, ':')) {
                    list($command, $argument) = explode(":", $block);
                } else {
                    $command = trim($block);
                    $argument = null;
                }
                $methodName = '_command' . ucfirst($command);
                if (!method_exists($this, $methodName)) {
                    $this->_writeError('Unknown command "' . $command . '"');
                } else {
                    $commandContent = $this->$methodName($argument);
                    if ($commandContent) {
                        $refil = function (array $arr, $type) {
                            return implode("\n\n", array_map(function ($item) use ($type) {
                                return isset($item[$type]) ? trim($item[$type]) . ';' : '';
                            }, $arr));
                        };

                        foreach (array('up', 'down') as $st) {
                            $content = str_replace('-- ' . strtoupper($st) . ' --', '-- ' . strtoupper($st) . ' --' . "\n\n" . $refil($commandContent, $st), $content);
                        }
                    }
                }
            }
            $this->_addVersionId($name);
        }

        // add migration
        $filePath = DIR_MIGRATION . '/' . $name . '.' . FILE_EXTENSION;
        if (!is_writable(DIR_MIGRATION)) {
            $this->_writeError('FILE SYSTEM ERROR :: Migration directory ' . DIR_MIGRATION . ' is not writable! ');
        }
        file_put_contents($filePath, $content);
        $this->_write("\n\033[33mGenerate new migration ID: " . $time . "\033[0m" . "\n" . 'Please edit file: ' . str_replace(DIR_ROOT, '', $filePath) . ($commands ? "\nThis migration marked as executed" : '') . "\n");

        if ($this->_getDumper()) {
            // add database current state
            $dbFilePath = DIR_DBSTATE . '/' . $name . '.txt';
            if (!file_exists(DIR_DBSTATE)) {
                $this->_writeError('FILE SYSTEM ERROR :: Database state directory ' . DIR_DBSTATE . ' is not exist! ');
            }
            if (!is_writable(DIR_DBSTATE)) {
                $this->_writeError('FILE SYSTEM ERROR :: Database state directory ' . DIR_DBSTATE . ' is not writable! ');
            }
            file_put_contents($dbFilePath, $this->_getDumper()->getStateSerialized());
            return $this;
        }
    }

    private function _getDumper()
    {
        if (null === $this->_dumper) {
            switch (DATABASE_DRIVER) {
                case 'mysql':
                    $this->_dumper = new \Driver\Mysql\Dump();
                    break;
                default:
                    throw new \Exception('Can\'t find Dumper for ' . DATABASE_DRIVER);
            }
        }
        return $this->_dumper;
    }

    private function _getGenerator()
    {
        if (null === $this->_generator) {
            switch (DATABASE_DRIVER) {
                case 'mysql':
                    $this->_generator = new \Driver\Mysql\Generator();
                    break;
                default:
                    throw new \Exception('Can\'t find Dumper for ' . DATABASE_DRIVER);
            }
        }
        return $this->_generator;
    }

    private function _getLastSavedState()
    {
        return $this->_getDumper()->getLastSavedState();
    }

    private function _commandDiff()
    {
        $generator = $this->_getGenerator()
            ->setFirstDataSource($this->_getDumper()->getState());

        switch (VERSION_CONTROL_STRATEGY) {
            case "file":
                $generator->setSecondDataSource($this->_getLastSavedState());
                break;
            case "database":
                $secondaryDumper = clone $this->_getDumper();
                $secondaryDumper
                    ->setConnection(\Util\Db::getInstance()->getSecondaryConnection());
                $generator->setSecondDataSource($secondaryDumper->getState());
                break;
            default:
                throw new \Exception('Wrong version control strategy. Check .environment file VERSION_CONTROL_STRATEGY parameter');
        }

        return $generator->getDiff();
    }

    public function getHelp()
    {

        return "Nasgrate is a console utility that let you organise database schema migration process at a consistent and easy way.\nIt supports mysql, mssql, postgresql, oracle and other databases

Usage:
  \033[33mphp nasgrate [command] [options]\033[0m

Command:
  \033[33mstatus\033[0m     - displays migration status
  \033[33mgenerate\033[0m   - creates new empty migration (migration file)
  \033[33mdiff\033[0m       - save current database state and create migration with database schema diff
  \033[33mup:show\033[0m    - displays (but not executes) SQL-query, executed by migration update
  \033[33mdown:show\033[0m  - display (but not execute) SQL-query, executed by migration revert
  \033[33mup\033[0m         - executes migration update
  \033[33mdown\033[0m       - executes migration revert
  \033[33mhelp\033[0m       - shows this help page

Examples:
  \033[33mphp nasgrate generate [migration name]\033[0m
  create new migration

  \033[33mphp nasgrate down:show XXXXXXXXXXX\033[0m
  where XXXXXXXXXXX - id existed migration

  \033[33mphp nasgrate up\033[0m
  execute all non running migration step by step

  \033[33mphp nasgrate down XXXXXXXXXXX\033[0m
  revert all changes before XXXXXXXXXXX

";

    }
}