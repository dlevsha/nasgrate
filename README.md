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

	_$ php nasgrate generate
	
display 
	
	Generate new migration ID: 20150821112753
	Please edit file: /migrations/Migration20150821112753.php
	
By default migration file stored in `/migrations` directory. You can change this location in `config.php` file in `DIR_MIGRATION` constant. 

If you look closely - you'll see that migration ID is a timestamp:

`20150821112753` -> `2015-08-21 11:27:53`

If you want you can change migration file prefix in `config.php` in `FILE_PREFIX` constant.

Created file contain three methods

	<?php
	// Please edit this file
	class Migration20150821112753 extends Migration_Abstract
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
	class Migration20150821112753 extends Migration_Abstract
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
