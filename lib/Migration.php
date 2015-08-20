<?php

class Migration
{
    const
        TEMPLATE_PATH = 'lib/Migration/Template.php';


    private
        $_migrationFiles = null,
        $_connection = null;

    protected static $_instance = null;

    /**
     *  @return Migration
     */

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
            self::$_instance->_createMigrationTable();
        }
        return self::$_instance;
    }

    public function generate($commands = null)
    {
        $time = date('YmdHis');
        $name = FILE_PREFIX.$time;
        $content = str_replace(
            array('MigrationXXXTTXXX', '---<>---'),
            array($name, DEFAULT_DESCRIPTION_MESSAGE),
            file_get_contents($this->_getTemplatePath())
        );
        if($commands){
            foreach(explode('|', $commands) as $block){
                list($command, $argument) = explode(":", $block);
                $methodName =  '_command'.ucfirst($command);
                if(!method_exists($this, $methodName)){
                    Console::getInstance()->writeError('Unknown command "'.$command.'"');
                }else{
                    foreach($this->$methodName($argument) as $sql){
                        $upTemplatePhrase = '// please add UP SQL query here';
                        $content = str_replace($upTemplatePhrase, $upTemplatePhrase."\n".$sql['up']."\n", $content);
                        $downTemplatePhrase = '// please add DOWN SQL query here';
                        $content = str_replace($downTemplatePhrase, $downTemplatePhrase."\n".$sql['down']."\n", $content);
                    }
                }
            }
            $this->_addVersionId($time);
        }
        $filePath = DIR_MIGRATION.'/'.$name.'.php';
        if(!is_writable(DIR_MIGRATION)) {
            Console::getInstance()->writeError('FILE SYSTEM ERROR :: Migration directory '.DIR_MIGRATION.' is not writable! ');
        }
        file_put_contents($filePath, $content);
        Console::getInstance()->writeHeader("Generate new migration ID: ".$time."\n".'Please edit file: '.str_replace(DIR_ROOT, '', $filePath).($commands?"\nThis migration marked as executed":''));
        return $this;
    }

    public function getLastMigrationId()
    {
        $stmt = $this->_getDb()->prepare('SELECT MAX(version) maxVersion FROM '.VERSION_TABLE_NAME);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['maxVersion'];
    }


    // ---------------------------
    public function upShow($migrationId = null)
    {
        $content = $this->_getUpSql($migrationId);
        $this->_showSqlContentShow($content);
        return $this;
    }

    public function downShow($migrationId)
    {
        if(!$migrationId) Console::getInstance()->writeError('ERROR :: migration ID not set'."\n"."Example: php console.php down::show 000000000000000");
        $content = $this->_getDownSql($migrationId);
        $this->_showSqlContentShow($content);
        return $this;
    }


    private function _showSqlContentShow($content)
    {
        Console::getInstance()->line();
        if($content){
            foreach($content as $item){
                Console::getInstance()->write('Migration :: '.$item['migrationId']);
                if($item['description']) {
                    Console::getInstance()->write('Description: '.$item['description']);
                }
                Console::getInstance()->write("\n".implode("\n\n", $item['sql'])."\n");
            }
        }else{
            Console::getInstance()->write('No actual migrations!');
        }
        Console::getInstance()->line();
    }
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

    private function _sqlContentRun($content, $isUp = true)
    {
        Console::getInstance()->line();
        if($content) {
            foreach ($content as $item) {
                Console::getInstance()->write('Migration :: '.$item['migrationId']);
                foreach ($item['sql'] as $sqlQuery) {
                    try{
                        Console::getInstance()->write("\n".$sqlQuery."\n");
                        $this->_getDb()->exec($sqlQuery);
                    }catch(Exception $e){
                        Console::getInstance()->writeError($sqlQuery."\n\nDATABASE ERROR :: ".$e->getMessage());
                    }
                }
                if($isUp){
                    $this->_addVersionId($item['migrationId']);
                }else{
                    $this->_removeVersionId($item['migrationId']);
                }
                Console::getInstance()->write('... complete');
            }
        }else{
            Console::getInstance()->write('No actual migrations!');
        }
        Console::getInstance()->line();
    }



    public function status()
    {
        Console::getInstance()->line();
        Console::getInstance()->write('Last Migration ID: '.($this->getLastMigrationId()?$this->getLastMigrationId():' no migrations')."\n");

        if($this->_getActualMigrations()){
            Console::getInstance()->write('Available Migrations:');
            foreach($this->_getActualMigrations() as $item){
                Console::getInstance()->write(' + '.$item);
            }
        }else{
            Console::getInstance()->write('Available Migrations: No actual migrations');
        }
        Console::getInstance()->line();
        return $this;
    }

    private function _getUpSql($toMigrationId = null)
    {
        $out = array();
        $migrations = $this->_getActualMigrations();
        if($toMigrationId && !in_array($toMigrationId, $migrations)) throw new Exception('Wrong Migration ID: '.$toMigrationId);

        foreach($migrations as $migration){
            if($toMigrationId && $migration > $toMigrationId) continue;

            require_once DIR_MIGRATION.'/'.FILE_PREFIX.$migration.'.php';
            $className = FILE_PREFIX.$migration;
            $class = new $className;
            if($class->isSkip() || ($toMigrationId && $migration > $toMigrationId)) continue;
            $out[] = array(
                'migrationId' => $migration,
                'sql' => $class->getUpSql(),
                'description' => $class->getDescription()
            );
        }
        return $out;
    }

    private function _getDownSql($migrationId)
    {
        if(!$migrationId) Console::getInstance()->writeError('Need Migration ID');
        $sql = array();
        $migrations = $this->_getMigrations();
        if(!$migrations) Console::getInstance()->writeError('No actual migrations');
        if(!in_array($migrationId, $migrations)) Console::getInstance()->writeError('Wrong Migration ID: migration '.$migrationId.' not exist');

        $validMigrationId = array();
        foreach($migrations as $mId){
            if($mId > $this->getLastMigrationId() || $mId < $migrationId) continue;
            $validMigrationId[] = $mId;
        }

        if(!$validMigrationId) return array();

        $migrations = $validMigrationId;
        rsort($migrations);

        $out = array();
        foreach($migrations as $migration){
            require_once DIR_MIGRATION.'/'.FILE_PREFIX.$migration.'.php';
            $className = FILE_PREFIX.$migration;
            $class = new $className;
            if($class->isSkip()) continue;
            $out[] = array(
                'migrationId' => $migration,
                'sql' => $class->getDownSql(),
                'description' => $class->getDescription()
            );
        }
        return $out;
    }

    private function _getMigrations()
    {
        if(null === $this->_migrationFiles){
            $this->_migrationFiles = array();
            if(!file_exists(DIR_MIGRATION)) Console::getInstance()->writeError('FILE SYSTEM ERROR :: Directory '.DIR_MIGRATION.' doesn\'t exists');
            if(!is_writable(DIR_MIGRATION)) Console::getInstance()->writeError('FILE SYSTEM ERROR :: Directory '.DIR_MIGRATION.' is not writable');
            foreach (new DirectoryIterator(DIR_MIGRATION) as $fileInfo) {
                if($fileInfo->isDot() || strpos($fileInfo->getFilename(), FILE_PREFIX) !== 0) continue;
                $this->_migrationFiles[] = str_replace(array(".php", FILE_PREFIX), array("", ""), $fileInfo->getFilename());
            }
            sort($this->_migrationFiles);
        }
        return $this->_migrationFiles;
    }

    private function _getActualMigrations()
    {
        $out = array();
        $migrationInBase = $this->_getMigrationIdFromBase();
        foreach($this->_getMigrations() as $migrationId){
            if(!in_array($migrationId, $migrationInBase) ) $out[] = $migrationId;
        }
        return $out;
    }

    private function _addVersionId($versionId)
    {
        $versionId = preg_replace('/[^\d]+/', '', $versionId);
        $stmt = $this->_getDb()->prepare('INSERT INTO '.VERSION_TABLE_NAME.' (version) VALUES(?)');
        $stmt->execute(array($versionId));
        return $this;
    }

    private function _removeVersionId($versionId)
    {
        $versionId = preg_replace('/[^\d]+/', '', $versionId);
        $stmt = $this->_getDb()->prepare('DELETE FROM '.VERSION_TABLE_NAME.' WHERE version = ?');
        $stmt->execute(array($versionId));
        return $this;
    }

    private function _getMigrationIdFromBase()
    {
        $stmt = $this->_getDb()->prepare('SELECT version FROM '.VERSION_TABLE_NAME);
        $stmt->execute();
        return array_map(
            function($item){ return $item['version']; },
            $stmt->fetchAll()
        );
    }

    private function _createMigrationTable()
    {
        $stmt = $this->_getDb()->prepare('SHOW TABLES LIKE \''.VERSION_TABLE_NAME.'\'');
        $stmt->execute();
        if(!$stmt->fetch()){
            $this->_getDb()->exec('CREATE TABLE '.VERSION_TABLE_NAME.' ( version varchar(50) NOT NULL, PRIMARY KEY (version) ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
        }

    }

    private function _getTemplatePath()
    {
        return self::TEMPLATE_PATH;
    }

    // generate commands

    private function _commandTable($arguments)
    {
        if(DATABASE_DRIVER != 'mysql'){
            Console::getInstance()->writeError('Command [table] available only for MySQL database driver');
        }
        foreach(explode(',', $arguments) as $tableName){
            try{
                $stmt = $this->_getDb()->prepare('SHOW CREATE TABLE '.$tableName);
                $stmt->execute();
                $result = $stmt->fetch();
                $out[] = array(
                    'up' => '$this->_addSql(\''.str_replace(array('`', '\''), array('', '\\\''), $result['Create Table']).'\');',
                    'down' => '$this->_addSql(\'DROP TABLE IF EXISTS '.$tableName.'\');'
                );
            }catch(Exception $e){
                Console::getInstance()->writeError("DATABASE ERROR :: ".$e->getMessage());
            }
        }
        return $out;
    }

    public function getHelp()
    {

        return 'Nasgrate lets you organise database schema migration process in a consistent and easy way.
It support mysql, mssql, postgresql, oracle (you can find informaton here http://php.net/manual/en/pdo.drivers.php)

Usage:
  php console.php [command] [options]

Command:
  status     - display migration status
  generate   - create new migration (migration file)
  up:show    - display (but not execute) SQL-query, executed by migration update
  up:down    - display (but not execute) SQL-query, executed by migration revert
  up:run     - execute migration update
  down:run   - execute migration revert
  help       - show this help

Examples:
  php console.php generate
  create new migration

  php console.php down:show XXXXXXXXXXX
  where XXXXXXXXXXX - id existed migration

  php console.php up:run
  execute all non running migration step by step

  php console.php down:run XXXXXXXXXXX
  revert all changes before XXXXXXXXXXX

';

    }

    private function _getDb()
    {
        if(null===$this->_connection){
            try{
                $opt = array(
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                );
                $this->_connection = new PDO(DATABASE_DSN, DATABASE_USER, DATABASE_PASSWORD, $opt);
                // create version table if not exist
            }catch(Exception $e){
                Console::getInstance()->writeError('DATABASE ERROR :: '.$e->getMessage());
            }
        }
        return $this->_connection;
    }


}