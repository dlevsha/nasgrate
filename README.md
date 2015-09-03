README
======

What is Nasgrate?
-----------------

Nasgrate is a console utility that let you organise database schema migration process at a consistent and easy way.
It supports mysql, mssql, postgresql, oracle and other databases (you can find informaton [here](http://php.net/manual/en/pdo.drivers.php) )

Requirements
------------

Nasgrate is only supported by PHP 5.3.0 and up.

Installation
------------
	_$ git clone https://github.com/dlevsha/nasgrate.git
	_$ cd nasgrate
	
Open config.php file and change your database settings:

	define('DATABASE_DRIVER', 'mysql');
	define('DATABASE_HOST', 'localhost');
	define('DATABASE_NAME', 'test');
	define('DATABASE_USER', 'root');
	define('DATABASE_PASSWORD', '');
	
	
`DATABASE_DRIVER`

* mysql - MySQL database
* sqlsrv - MS SQL Server and SQL Azure databases
* mssql - FreeTDS
* pgsql - PostgreSQL
* oci - Oracle 

You can find more information at official [PHP PDO documentation](http://php.net/manual/en/pdo.drivers.php) 

`DATABASE_HOST` - database host name or IP

`DATABASE_NAME` - database name

`DATABASE_USER` and `DATABASE_PASSWORD` - login and password to access your database

You can check your settings by simply running 

	_$ php nasgrate

and you are to see the help page describing base commands

	Nasgrate is a console utility that let you organise database schema migration process at a consistent and easy way.
	It supports mysql, mssql, postgresql, oracle (you can find informaton here http://php.net/manual/en/pdo.drivers.php)
	
	Usage:
	  php nasgrate [command] [options]
	
	Command:
	  status     - displays migration status
	  generate   - creates new migration (migration file)
	  up:show    - displays (but not executes) SQL-query, executed by migration update
	  up:down    - displays (but not executes) SQL-query, executed by migration revert
	  up:run     - executes migration update
	  down:run   - executes migration revert
	  help       - shows this help page
	  ...

If you use Linux or MacOS for your convenience you can setup nasgrate script

Run 

	_$ which php
	
You'll see something like this

	_$ which php
	/usr/local/php5/bin/php	

Copy your php path and add it as a first line in `nasgrate` file like here

	#!/usr/local/php5/bin/php -q
	
Your file will lool like this 

	#!/usr/local/php5/bin/php -q
	<?php
	require_once 'console.php';
	
Go to console and run 

	_$ chmod +x nasgrate
	
Now you can run Nasgrate by simply typing

	_$ ./nasgrate	
	
Lets check your database connection settings

	_$ php nasgrate status
	
If all is ok you will see

	Last Migration ID:  no migrations
	Available Migrations: No actual migrations
	
If you have a connection problem you'll see an error description. For example:

	DATABASE ERROR :: SQLSTATE[HY000] [1049] Unknown database 'test2'

	

Documentation
-------------

### Create migration

Every time you create migration - you create `.php` file having at least two methods: `up()` and `down()`.

`up()` method contains SQL-queries that are used to update exist database schema. For example:

	CREATE TABLE test (
	  id int(11) unsigned NOT NULL AUTO_INCREMENT,
	  PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	
`down()` method conatains SQL-queries that are used to revert database schema. For example:

	DROP TABLE test
	
Let's	create our first migration

	_$ php nasgrate generate CreateTestMigration
	
and it will display 
	
	Generate new migration ID: 20150821112753_CreateTestMigration
	Please edit file: /migrations/20150821112753_CreateTestMigration.php
	
By default migration file is stored at `/migrations` directory. You can change this location at `config.php` file at `DIR_MIGRATION` constant. 

If you look closely you'll see that migration ID is a timestamp:

`20150821112753` -> `2015-08-21 11:27:53`

You can change migration class prefix at `config.php` at `CLASS_PREFIX` constant.

The created file contains three methods

	<?php
	// Please edit this file
	class Migration20150821112753_CreateTestMigration extends Migration_Abstract
	{
	    public function up()
	    {
	        // please add UP SQL query here
	        // $this->_addSql('');
	    }
	
	    public function down()
	    {
	        // please add DOWN SQL query here
	        // $this->_addSql('');
	    }
	
	    public function getDescription()
	    {
	        return 'Created by dlevsha, 2015-08-21 11:27:53';
	    }
	}
	
`getDescription()` contains migration description. You can change or expand it. 

	    public function getDescription()
	    {
	        return "The first migration. Created by dlevsha, 2015-08-21 11:27:53";
	    } 	
	    
Add sql to `up()` and `down()` methods.	 

   	<?php
	// Please edit this file
	class Migration20150821112753_CreateTestMigration extends Migration_Abstract
	{
	    public function up()
	    {
	        // please add UP SQL query here
	        
	        $this->_addSql('CREATE TABLE test (
				  id int(11) unsigned NOT NULL AUTO_INCREMENT,
				  PRIMARY KEY (id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8');
	    }
	
	    public function down()
	    {
	        // please add DOWN SQL query here
	        $this->_addSql('DROP TABLE test');
	    }
	
	    public function getDescription()
	    {
	        return 'The first migration. Created by dlevsha, 2015-08-21 11:27:53';
	    }
	}
	
You can add as many sql queries as you want. Each sql query needs to be at separate `_addSql()` method. Each sql query at `up()` method needs to have mirrow sql query at `down()` method.	

	    public function up()
	    {
	        // please add UP SQL query here
	        
	        $this->_addSql('CREATE TABLE test (
				  id int(11) unsigned NOT NULL AUTO_INCREMENT,
				  PRIMARY KEY (id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8');
	        
	        $this->_addSql('CREATE TABLE test2 (
				  id int(11) unsigned NOT NULL AUTO_INCREMENT,
				  PRIMARY KEY (id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8');
	    }
	
	    public function down()
	    {
	        // please add DOWN SQL query here
	        $this->_addSql('DROP TABLE test');
	        $this->_addSql('DROP TABLE test2');
	    }

	
### Update database schema (run migration)	
Before we run our first migation let's view query at our migration

	_$ php nasgrate up:show
	
and it will display

	Migration :: 20150821112753_CreateTestMigration
	Description: The first migration. Created by dlevsha, 2015-08-21 11:27:53
	
	CREATE TABLE test (
	              id int(11) unsigned NOT NULL AUTO_INCREMENT,
	              PRIMARY KEY (id)
	            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
	
	CREATE TABLE test2 (
	              id int(11) unsigned NOT NULL AUTO_INCREMENT,
	              PRIMARY KEY (id)
	            ) ENGINE=InnoDB DEFAULT CHARSET=utf8

We can see each query which will be executed during migration process. If all is ok let's run migration.

	php nasgrate up:run
	
and it will display

	Migration :: 20150821112753_CreateTestMigration
	
	CREATE TABLE test (
	              id int(11) unsigned NOT NULL AUTO_INCREMENT,
	              PRIMARY KEY (id)
	            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
	
	
	CREATE TABLE test2 (
	              id int(11) unsigned NOT NULL AUTO_INCREMENT,
	              PRIMARY KEY (id)
	            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
	
	... complete

If you look at your database you will see three tables.

	__migrationVersions
	test
	test2

`__migrationVersions` - service table cretaed by migration script. It contains an executed migration ID. If you want to change the name of this table edit `VERSION_TABLE_NAME` constanted at `config.php`. Never remove this table - you will loose you migration information. 

`test` and `test2` - tables created through migration process.

If you want to update database schema before certain migration you need to set this migration ID as an argument

	_$ php nasgrate up:run 20150821132420

### Revert database schema

If something goes wrong and you want to rollback your changes you need to use revert process. Before you run this update you need to know migration ID to which you want to use revert database schema process. 

You can display all migration ID at your database by runinig

	_$ php nasgrate list
	
and it will display

	Migration list:
	 - [26.08.2015 19:39:39] 20150826193939_CreateFirstMigration - new
	 - [26.08.2015 19:30:33] 20150826193033_New_Table_Test - executed
	 
You see that you have four migrations at your database. Migration `20150821112753` is already executed, three others are not.

Let's imagine you want to revert `20150821112753_CreateFirstMigration` migration.

	_$ php nasgrate down:show 20150821112753
	
or	

	_$ php nasgrate down:show 20150821112753_CreateFirstMigration

	
and it will display

	Migration :: 20150821112753_CreateFirstMigration
	Description: The first migration. Created by dlevsha, 2015-08-21 11:27:53
	
	DROP TABLE test
	DROP TABLE test2	

Lets run revert process

	_$ php nasgrate down:run 20150821112753_CreateFirstMigration
	
and it will display

	Migration :: 20150821112753_CreateFirstMigration
	
	DROP TABLE test
	DROP TABLE test2
	
	... complete	

If you look at your database you can see that `test` and `test2` tables were removed.

Run again `list` command

	_$ php nasgrate list
	
and it will display

	Migration list:
	 - [26.08.2015 19:39:39] 20150826193939_CreateFirstMigration - new
	 - [26.08.2015 19:30:33] 20150826193033_New_Table_Test - new
	 
### Generated migration based on existed database schema (for MySQL database only)

Suppose you already have `test` and `test2` tables at your database and you want to create migration based on these tables.

Run

	_$ php nasgrate generate AddTwoDatabases table:test,test2
	
and it will display 

	Generate new migration ID: 20150821141007_AddTwoDatabases
	Please edit file: /migrations/Migration20150821141007_AddTwoDatabases.php
	This migration marked as executed		
	
When you look into `Migration20150821141007_AddTwoDatabases.php` you will see that this file already has `up()` and `down()` methods with SQL-queries. 

    public function up()
    {
        // please add UP SQL query here
			$this->_addSql('CREATE TABLE test2 (
			  id int(11) unsigned NOT NULL AUTO_INCREMENT,
			  PRIMARY KEY (id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8');
			
			$this->_addSql('CREATE TABLE test (
			  id int(11) unsigned NOT NULL AUTO_INCREMENT,
			  PRIMARY KEY (id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    public function down()
    {
        // please add DOWN SQL query here
			$this->_addSql('DROP TABLE IF EXISTS test2');
			$this->_addSql('DROP TABLE IF EXISTS test');
    }

LICENSE
-------

Copyright (c) 2015, Levsha Dmitry

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.