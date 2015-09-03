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

    public function generate($migrationName = null, $commands = null)
    {
        if(!$migrationName) Console::getInstance()->writeError("Migration name is required.\nUse: _$ php nasgrate generate [migration_name]");
        if(preg_match('/[^A-z0-9-\.\_]/', $migrationName, $s)) Console::getInstance()->writeError("Migration name contain wrong symbol: [".$s[0].']');
        $time = date('YmdHis');

        $name = $time.'_'.implode("_", array_map(function($item){ return ucfirst($item); }, preg_split('/[-\_\.]/', $migrationName)));
        $content = str_replace(
            array('MigrationXXXTTXXX', '---<>---'),
            array(CLASS_PREFIX.$name, DEFAULT_DESCRIPTION_MESSAGE),
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
            $this->_addVersionId($name);
        }
        $filePath = DIR_MIGRATION.'/'.$name.'.php';
        if(!is_writable(DIR_MIGRATION)) {
            Console::getInstance()->writeError('FILE SYSTEM ERROR :: Migration directory '.DIR_MIGRATION.' is not writable! ');
        }
        file_put_contents($filePath, $content);
        Console::getInstance()->write("\n\033[33mGenerate new migration ID: ".$time."\033[0m\n".'Please edit file: '.str_replace(DIR_ROOT, '', $filePath).($commands?"\nThis migration marked as executed":'')."\n");
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
        if($content){
            foreach($content as $item){
                $migrationId = 'Migration :: '.$item['migrationId'];
                Console::getInstance()->write("\n".str_repeat("=", strlen($migrationId)));
                Console::getInstance()->write("\n".$migrationId);
                if($item['description']) {
                    Console::getInstance()->write('Description: '.$item['description']);
                }
                Console::getInstance()->write("\n\033[33m".implode("\n----\n", $item['sql'])."\033[0m\n");
            }
        }else{
            Console::getInstance()->write('No actual migrations!');
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

    private function _sqlContentRun($content, $isUp = true)
    {
        if($content) {
            foreach ($content as $item) {
                $migrationId = 'Migration :: '.$item['migrationId'];
                Console::getInstance()->write("\n".str_repeat("=", strlen($migrationId)));
                Console::getInstance()->write("\n".$migrationId);
                if($item['description']) {
                    Console::getInstance()->write('Description: '.$item['description']);
                }

                foreach ($item['sql'] as $sqlQuery) {
                    try{
                        Console::getInstance()->write("\n\033[33m".$sqlQuery."\033[0m\n");
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
    }
    // ---------------------------


    public function migrationList()
    {
        $migrationInBase = $this->_getMigrationIdFromBase();
        $migrationFromFiles = $this->_getMigrations();
        Console::getInstance()->write( "\n\033[33m".(!$migrationFromFiles ? 'No actual migrations' : 'Migration list:')."\033[0m" );
        rsort($migrationFromFiles);
        foreach($migrationFromFiles as $migrationId){
            preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $migrationId, $parts);
            Console::getInstance()->write(' - '.'['.($parts[3].'.'.$parts[2].'.'.$parts[1].' '.$parts[4].':'.$parts[5].':'.$parts[6]).'] '."\033[33m".str_replace(".php", "", $migrationId)."\033[0m".' - '.(in_array($migrationId, $migrationInBase) ? 'executed': 'new'));
        }
        Console::getInstance()->write("\n");
    }


    public function status()
    {
        Console::getInstance()->write("\n\033[33mLast Migration ID: ".($this->getLastMigrationId()?$this->getLastMigrationId():' no migrations')."\033[0m\n");

        if($this->_getActualMigrations()){
            Console::getInstance()->write('Available Migrations:');
            foreach($this->_getActualMigrations() as $item){
                Console::getInstance()->write(' + '.$item);
            }
        }else{
            Console::getInstance()->write("\n".'Available Migrations: No actual migrations');
        }
        Console::getInstance()->write("\n");
        return $this;
    }

    private function _getUpSql($migrationId = null)
    {
        $out = array();
        $migrations = $this->_getActualMigrations();
        if($migrationId && !in_array($migrationId, $migrations) && !in_array($migrationId, array_map(function($item){ return substr($item, 0, 14); }, $migrations) )) throw new Exception('Wrong Migration ID: '.$toMigrationId);

        foreach($migrations as $migration){
            preg_match('/^(\d{14})/', $migration, $s);
            $mid = $s[1];
            if($migrationId && $mid > $migrationId) continue;

            require_once DIR_MIGRATION.'/'.$migration.'.php';
            $className = CLASS_PREFIX.str_replace(".php", "", $migration);
            $class = new $className;
            if($class->isSkip() || ($migrationId && $mid > $migrationId)) continue;
            echo $mid .'  --  '. $migrationId ."\n";
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
        $migrations = $this->_getMigrations();
        if(!$migrations) Console::getInstance()->writeError('No actual migrations');
        if(!in_array($migrationId, $migrations) && !in_array($migrationId, array_map(function($item){ return substr($item, 0, 14); }, $migrations) )) Console::getInstance()->writeError('Wrong Migration ID: migration '.$migrationId.' not exist');

        $validMigrationId = array();
        preg_match('/^(\d{14})/', $this->getLastMigrationId(), $s);
        $lastMigrationId = $s[1];
        preg_match('/^(\d{14})/', $migrationId, $s);
        $mid = $s[1];


        foreach($migrations as $mId){
            preg_match('/^(\d{14})/', $mId, $part);
            if($part[0] > $lastMigrationId || $part[0] < $mid) continue;
            $validMigrationId[] = $mId;
        }

        if(!$validMigrationId) return array();

        $migrations = $validMigrationId;
        rsort($migrations);

        $out = array();
        foreach($migrations as $migration){
            require_once DIR_MIGRATION.'/'.$migration.'.php';
            $className = CLASS_PREFIX.$migration;
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
                if($fileInfo->isDot() || !preg_match('/^([\d]{14}).+\.php$/', $fileInfo->getFilename(), $s)) continue;
                $this->_migrationFiles[] = str_replace(".php", "", $fileInfo->getFilename());
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
        // $versionId = preg_replace('/[^\d]+/', '', $versionId);
        $stmt = $this->_getDb()->prepare('INSERT INTO '.VERSION_TABLE_NAME.' (version) VALUES(?)');
        $stmt->execute(array($versionId));
        return $this;
    }

    private function _removeVersionId($versionId)
    {
        // $versionId = preg_replace('/[^\d]+/', '', $versionId);
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
            $this->_getDb()->exec('CREATE TABLE '.VERSION_TABLE_NAME.' ( version varchar(255) NOT NULL, PRIMARY KEY (version) ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
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

        return "Nasgrate is a console utility that let you organise database schema migration process at a consistent and easy way. It supports mysql, mssql, postgresql, oracle and other databases

Usage:
  \033[33mphp nasgrate [command] [options]\033[0m

Command:
  \033[33mstatus\033[0m     - displays migration status
  \033[33mgenerate\033[0m   - creates new migration (migration file)
  \033[33mup:show\033[0m    - displays (but not executes) SQL-query, executed by migration update
  \033[33mup:down\033[0m    - display (but not execute) SQL-query, executed by migration revert
  \033[33mup:run\033[0m     - executes migration update
  \033[33mdown:run\033[0m   - executes migration revert
  \033[33mhelp\033[0m       - shows this help page

Examples:
  \033[33mphp nasgrate generate [migration name]\033[0m
  create new migration

  \033[33mphp nasgrate down:show XXXXXXXXXXX\033[0m
  where XXXXXXXXXXX - id existed migration

  \033[33mphp nasgrate up:run\033[0m
  execute all non running migration step by step

  \033[33mphp nasgrate down:run XXXXXXXXXXX\033[0m
  revert all changes before XXXXXXXXXXX

";

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