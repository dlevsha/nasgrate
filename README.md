README
======

What is Nasgrate?
-----------------

Nasgrate is a console utility lets you organise database schema migration process in a consistent and easy way.
It support mysql, mssql, postgresql, oracle and other databases (you can find informaton [here](http://php.net/manual/en/pdo.drivers.php) )

Requirements
------------

Nasgrate is only supported on PHP 5.3.0 and up.

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

You can find more information on official [PHP PDO documentation](http://php.net/manual/en/pdo.drivers.php) 

`DATABASE_HOST` - name or IP your database host

`DATABASE_NAME` - your database name

`DATABASE_USER` and `DATABASE_PASSWORD` - login and password to access your database

You can check your settings simply run 

	_$ php nasgrate

You need to view help page described base commands

	Nasgrate is a console utility lets you organise database schema migration process in a consistent and easy way.
	It support mysql, mssql, postgresql, oracle (you can find informaton here http://php.net/manual/en/pdo.drivers.php)
	
	Usage:
	  php nasgrate [command] [options]
	
	Command:
	  status     - display migration status
	  generate   - create new migration (migration file)
	  up:show    - display (but not execute) SQL-query, executed by migration update
	  up:down    - display (but not execute) SQL-query, executed by migration revert
	  up:run     - execute migration update
	  down:run   - execute migration revert
	  help       - show this help
	  ...

If you use Linux or MacOS for your convenience you can setup nasgrate script

Run 

	_$ which php
	
You'll see something like 

	_$ which php
	/usr/local/php5/bin/php	

Copy your php path and add it as a first line in `nasgrate` file like 

	#!/usr/local/php5/bin/php -q
	
Your file after look like 

	#!/usr/local/php5/bin/php -q
	<?php
	require_once 'console.php';
	
Go to console and run 

	_$ chmod +x nasgrate
	
Now you can run Nasgrate simply type

	_$ ./nasgrate	
	
Lets check your database connection settings

	_$ php nasgrate status
	
If all ok you see

	Last Migration ID:  no migrations
	Available Migrations: No actual migrations
	
If you have connection problem you'll see error description. For example:

	DATABASE ERROR :: SQLSTATE[HY000] [1049] Unknown database 'test2'

	

Documentation
-------------

### Create migration

Every time than you create migration - you create `.php` file having at least two methods: `up()` and `down()`.

`up()` method contain SQL-queries using to update exist database schema. For example:

	CREATE TABLE test (
	  id int(11) unsigned NOT NULL AUTO_INCREMENT,
	  PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	
`down()` method conatain SQL-queries	using to revert database schema. For example:

	DROP TABLE test
	
Let's	create our first migration

	_$ php nasgrate generate CreateTestMigration
	
display 
	
	Generate new migration ID: 20150821112753_CreateTestMigration
	Please edit file: /migrations/20150821112753_CreateTestMigration.php
	
By default migration file stored in `/migrations` directory. You can change this location in `config.php` file in `DIR_MIGRATION` constant. 

If you look closely - you'll see that migration ID is a timestamp:

`20150821112753` -> `2015-08-21 11:27:53`

You can change migration class prefix in `config.php` in `CLASS_PREFIX` constant.

Created file contain three methods

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
	
`getDescription()` contain migration description. You can change or expand it. 

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
	
You can add as many sql as you want. Each sql query need to be in separate `_addSql()` method. Each update sql in `up()` method need to have mirrow sql in `down()` method.	

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
Before we run our first migation let's view query in our migration

	_$ php nasgrate up:show
	
display

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

We can see each query which executed during migration process. If all ok - let's run migration.

	php nasgrate up:run
	
display

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

If you look to your database you see three tables.

	__migrationVersions
	test
	test2

`__migrationVersions` - service table cretaed by migration script. It contain executed migration ID. If you want change name of this table edit `VERSION_TABLE_NAME` constant in `config.php`. Never remove this table - you loss you migration information. 

`test` and `test2` - tables, created through migration process.

If you want update databse schema before certain migration you need set this migration ID as argument

	_$ php nasgrate up:run 20150821132420

### Revert database schema

If something going wrong and you want to rollback you changes you need to use revert process. Before you run you need to know migration ID to which you want revert database schema. 

You can display all migration ID in your database runinig

	_$ php nasgrate list
	
display

	Migration list:
	 - [26.08.2015 19:39:39] 20150826193939_CreateFirstMigration - new
	 - [26.08.2015 19:30:33] 20150826193033_New_Table_Test - executed
	 
You see that you have four migrations in your database. Migration `20150821112753` already executed, three other not.

Let's imagine you want to revert `20150821112753_CreateFirstMigration` migration.

	_$ php nasgrate down:show 20150821112753
	
or	

	_$ php nasgrate down:show 20150821112753_CreateFirstMigration

	
display

	Migration :: 20150821112753_CreateFirstMigration
	Description: The first migration. Created by dlevsha, 2015-08-21 11:27:53
	
	DROP TABLE test
	DROP TABLE test2	

Lets run revert process

	_$ php nasgrate down:run 20150821112753_CreateFirstMigration
	
display

	Migration :: 20150821112753_CreateFirstMigration
	
	DROP TABLE test
	DROP TABLE test2
	
	... complete	

If you look in your database you see that `test` and `test2` tables was remove.

Run again `list` command

	_$ php nasgrate list
	
display

	Migration list:
	 - [26.08.2015 19:39:39] 20150826193939_CreateFirstMigration - new
	 - [26.08.2015 19:30:33] 20150826193033_New_Table_Test - new
	 
### Generated migration based on existed database schema (for MySQL database only)

Suppose you already have `test` and `test2` tables in your database and you want to create migration based on this tables.

Run

	_$ php nasgrate generate AddTwoDatabases table:test,test2
	
Display 

	Generate new migration ID: 20150821141007_AddTwoDatabases
	Please edit file: /migrations/Migration20150821141007_AddTwoDatabases.php
	This migration marked as executed		
	
Than you look inside `Migration20150821141007_AddTwoDatabases.php` you see that this file already have `up()` and `down()` method with SQL-queries. 

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