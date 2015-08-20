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
	  php console.php [command] [options]
	
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

	--------------------------------------------------
	
	Last Migration ID:  no migrations
	
	Available Migrations: No actual migrations
	
	--------------------------------------------------	
If you have connection problem you see error description. For example:

	--------------------------------------------------
	
	DATABASE ERROR :: SQLSTATE[HY000] [1049] Unknown database 'test2'
	
	--------------------------------------------------

	

Documentation
-------------

