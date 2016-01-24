README
======

What is Nasgrate?
-----------------

Nasgrate is a console utility that let you organise database schema migration process at a consistent and easy way.
It supports mysql, mssql, postgresql, oracle and other databases (you can find informaton [here](http://php.net/manual/en/pdo.drivers.php) )

The key features:

- native SQL syntaxes for migrations
- automatically generates migrations based on saved database states (you don't need to write migrations yourself, for MySQL database only, but I plan to add support for PostgreSQL, MS SQL and Oracle databases later). 
- user-friendly interface to view saved migrations

Requirements
------------

Nasgrate is only supported by PHP 5.3.0 and up with PDO extension.

Installation
------------

Clone project from GitHub

```bash
$ git clone https://github.com/dlevsha/nasgrate.git
$ cd nasgrate
```

or use composer
	
    $ composer require dlevsha/nasgrate	
	
Open `.environment` file and change your settings:

```ini
[Primary connection params]
; possible drivers: 'mysql' - MySQL database, 'sqlsrv' - MS SQL Server and SQL Azure databases
; 'mssql' - FreeTDS, 'pgsql' - PostgreSQL, 'oci' - Oracle
DATABASE_DRIVER = mysql
DATABASE_HOST = localhost
DATABASE_NAME = test
DATABASE_USER = root
DATABASE_PASSWORD =

[Migration params]
VERSION_TABLE_NAME = __migrationVersions
FILE_EXTENSION = sql
DIR_MIGRATION = DIR_ROOT/migrations
DEFAULT_DESCRIPTION_MESSAGE = Created by CURRENT_USER, CURRENT_DATE

[Database version control]
DIR_DBSTATE = DIR_ROOT/dbstate
; possible values - file / database
VERSION_CONTROL_STRATEGY = file


; --------------------------------------------------------------------
; These params are needed only if you use second database as data source
; to compare database structure. Please read documentation.
[Secondary connection params]
DATABASE_HOST_SECONDARY = localhost
DATABASE_NAME_SECONDARY = test
DATABASE_USER_SECONDARY = root
DATABASE_PASSWORD_SECONDARY =
```
`[Primary connection params]` section describes connection settings
	
`DATABASE_DRIVER` - set one of the drivers which is supported by PHP PDO extension

* mysql - MySQL database
* sqlsrv - MS SQL Server and SQL Azure databases
* mssql - FreeTDS
* pgsql - PostgreSQL
* oci - Oracle 

You can find more information at official [PHP PDO documentation](http://php.net/manual/en/pdo.drivers.php) 

`DATABASE_HOST` - database host name or IP

`DATABASE_NAME` - database name

`DATABASE_USER` and `DATABASE_PASSWORD` - login and password to access your database

Next section `[Migration params]` describes how the script stores information about migrations

`VERSION_TABLE_NAME` - name of a table, where migration script stores service information

`FILE_EXTENSION` - migration file extension (by default `sql`)

`DIR_MIGRATION`  - where script stores migration files. By default it stores it inside  `migrations` directory. 

If you plan to share your migrations between team members or servers using version control system  (git for example) you need to move this directory to your project folder and change this path.

For example if you have project in `/var/www/project/` and plan to store migrations in `/var/www/project/service/scripts/migrations` directory, you need to change `DIR_MIGRATION` to

	DIR_MIGRATION = /var/www/project/service/scripts/migrations


`DEFAULT_DESCRIPTION_MESSAGE` - each migration has its own description.

By default a message looks like `Created by CURRENT_USER, CURRENT_DATE`, where `CURRENT_USER` and `CURRENT_DATE` - is a predefined constant which is changed to user name and current date respectively. So this message becomes `Created by dlevsha, 2015-12-21 17:53:41` in my case.

Next section `[Database version control]` describes version control settings. The most powerful feature of this script is ability to track database changes and automatically create diff file which contains all database changes between migrations. 

`VERSION_CONTROL_STRATEGY` - describes which strategy you use to store database changes. There are two possible values - `file` and `database`.

If you have two databases (`prod` and `test` for example ) and you want to generate diff file which describes differences between databases, your choice will be `database` and you need to feel next section `[Secondary connection params]` which describes  connection settings to reference database.

Or you can set `file` value and script will automatically save database state each time you create migration (in this case you do not need to feel `[Secondary connection params]` section).

You can check your settings by simply running 

	$ php bin/nasgrate

and you are to see the help page describing base commands

	Nasgrate is a console utility that let you organise database schema migration process at a consistent and easy way.
	It supports mysql, mssql, postgresql, oracle (you can find informaton here http://php.net/manual/en/pdo.drivers.php)
	
	Usage:
	  php nasgrate [command] [options]
	
	Command:
	  status     - displays migration status
	  generate   - creates new migration (migration file)
	  diff       - save current database state and create migration with database schema diff
	  up:show    - displays (but not executes) SQL-query, executed by migration update
	  down:show  - displays (but not executes) SQL-query, executed by migration revert
	  up         - executes migration update
	  down       - executes migration revert
	  help       - shows this help page
	  ...

If you use Linux or MacOS for your convenience you can setup nasgrate script

Go to console and run 

	$ chmod +x bin/nasgrate
	
Now you can run Nasgrate by simply typing

	$ ./bin/nasgrate	
	
Lets check your database connection settings

	$ ./bin/nasgrate status
	
If all is ok you will see

	Last Migration ID:  no migrations
	Available Migrations: No actual migrations
	
If you have a connection problem you'll see an error description. For example:

	DATABASE ERROR :: SQLSTATE[HY000] [1049] Unknown database 'test2'

	

Documentation
-------------

### Create migration

Every time you create migration - you create `.sql` file having at least two sections: `-- UP --` and `-- DOWN --`.

`-- UP --` section contains SQL-queries that are used to update exist database schema. For example:

```sql
CREATE TABLE test (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```
	
`-- DOWN --` method conatains SQL-queries that are used to revert database schema. For example:

```sql
DROP TABLE test
```	
	
Let's	create our first migration

	$ ./bin/nasgrate generate CreateTestMigration
	
and it will display 
	
	Generate new migration ID: 20150821112753_CreateTestMigration
	Please edit file: /migrations/20150821112753_CreateTestMigration.sql
	
By default migration will be placed in `migrations` directory. You can change this location in `.environment` file at `DIR_MIGRATION` param. 

If you look closely you'll see that migration ID is a timestamp:

`20150821112753` -> `2015-08-21 11:27:53`

The created file looks like

```sql
-- Skip: no
-- Name: Test
-- Date: 01.12.2015 20:28:08
-- Description: Created by dlevsha, 2015-12-01 20:28:08

-- UP --

-- DOWN --

```	

`Skip:` - if migration needs to be skiped. Possible values `yes|no`. Default: `no`. Sometimes you need to skip certain migration for any reason. You can do this by setting `Skip:` to `yes`.

`Name:` - your migration name

`Date:` - the date when the file was created

`Description:` - describes current migration

`-- UP --` and `-- DOWN --` section contains SQL-expressions. You can add as many sql queries as you want. Each sql query needs to be at a new line. Each sql query at `-- UP --` section needs to have mirrow sql query at `-- DOWN --` section.

For example:

```sql
-- Skip: no
-- Name: Test
-- Date: 01.12.2015 20:28:08
-- Description: The first migration. Created by dlevsha, 2015-12-01 20:28:08

-- UP --
CREATE TABLE test (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE test2 (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DOWN --
DROP TABLE test;
DROP TABLE test2;

```	

### Create migration (database schema diff) automatically 

You can automaticaly create database schema diff, so you don't need describe changes in database.

If you don't know what `database schema` is, read this StackOverflow answer: [what is purpose of database schema?](http://stackoverflow.com/questions/2674222/what-is-purpose-of-database-schema)

Each time you create new migration, Nasgrate saves current `database schema state` in a special file at `dbstate` directory. This file contain snapshot of you database schema as a php serialized array (tables, rows name and type, indexes and etc.).

When you change you database schema later, you can compare it with saved state and automatically create new migration with all database changes.

Another option is if you have two databases (`prod` and `test` for example), you make changes in `test` database and want to create new migration which contains all changes, the script can automatically do it. 
 
You can use prefered database tools to modify database schema (for example Sequel Pro or phpMyAdmin) and no need to remember what you have changed in the database since the last migration.

By default script use `file` strategy to track changes in your database. If you want to compare changes in two databases using one of them as a standart - change `VERSION_CONTROL_STRATEGY` in `.environment` file to `database` and fill `[Secondary connection params]` section.

Let me give you an example

Suppose you add a new table at your database using Sequel Pro: 

![UI example](https://cloud.githubusercontent.com/assets/1639576/11975111/840f5d62-a97b-11e5-9a2a-8b3c6845df80.png)

Run

	$ ./bin/nasgrate diff AddNewTable

and it will display (in my case)

	Generate new migration ID: 20151223133618
	Please edit file: /migrations/20151223133618_AddNewTable.sql
	This migration marked as executed
	
When you look at `20151223133618_AddNewTable.sql` you will see that this file already has `-- UP --` and `-- DOWN --` sections with SQL-queries. 

```sql
-- Skip: no
-- Name: AddNewTable
-- Date: 23.12.2015 13:36:18
-- Description: Created by dlevsha, 2015-12-23 13:36:18

-- UP --

CREATE TABLE `test` (
    `id` int(11) unsigned NOT NULL  auto_increment,
    `name` varchar(200)  DEFAULT NULL ,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


-- DOWN --

DROP TABLE IF EXISTS `test`;
```

Suppose you decide to change `name` field to `VARCHAR(255)` and add index for `name` field using the program.

![](https://cloud.githubusercontent.com/assets/1639576/11975178/22726562-a97c-11e5-9f5a-49e0e5a9fc13.png)

Run

	$ ./bin/nasgrate generate ChangeMyTestTable diff

display 	

	Generate new migration ID: 20151223135246
	Please edit file: /migrations/20151223135246_ChangeMyTestTable.sql
	This migration marked as executed	

and create automatically

```sql
-- Skip: no
-- Name: ChangeMyTestTable
-- Date: 23.12.2015 13:52:46
-- Description: Created by dlevsha, 2015-12-23 13:52:46

-- UP --

ALTER TABLE `test` CHANGE `name` `name` varchar(255)  DEFAULT NULL;

ALTER TABLE `test` ADD KEY `name` (`name`);


-- DOWN --

ALTER TABLE `test` CHANGE `name` `name` varchar(200)  DEFAULT NULL;

ALTER TABLE `test` DROP  KEY `name`;
```	

### View migrations list

Before we run our first migation let's view query at our migration

	$ ./bin/nasgrate up:show
	
and it will display

```
Migration :: 20150821112753_CreateTestMigration
Description: The first migration. Created by dlevsha, 2015-12-01 20:28:08 
	
CREATE TABLE test (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
	
CREATE TABLE test2 (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
```

We can see each query which will be executed during migration process. 

Another option is you can view all transactions (executed and non-executed) via web interface. Just run command

	php -S localhost:9000 
	
inside script directory and type in your browser `localhost:9000`. 

You'll see your migrations

![Migrations list](https://cloud.githubusercontent.com/assets/1639576/12373875/dbb39fc4-bc9a-11e5-9d54-202e7c221bb5.png)	
	
### Update database schema (run migration)	
If all is ok let's run migration.

	./bin/nasgrate up
	
and it will display

```
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
```

If you look at your database you will see three tables.

	__migrationVersions
	test
	test2

`__migrationVersions` is a service table created by migration script. It contains an executed migration ID. If you want to change the name of this table edit `VERSION_TABLE_NAME` constant at `.environment` file. Never remove this table or you will loose you migration information. 

`test` and `test2` are the tables created through migration process.

If you want to update database schema before a certain migration you need to set this migration ID as an argument

	$ ./bin/nasgrate up:run 20150821132420

### Revert database schema

If something goes wrong and you want to rollback your changes you need to use revert process. Before you run this update you need to know migration ID to which you want to apply revert database schema process. 

You can display all migration IDs at your database by runing

	$ ./bin/nasgrate list

or using web-interface described above and it will display

	Migration list:
	 - [26.08.2015 19:39:39] 20150826193939_CreateFirstMigration - new
	 - [26.08.2015 19:30:33] 20150826193033_New_Table_Test - executed
	 
You see that you have two migrations at your database. Migration `20150821112753` is already executed, `20150826193939_CreateFirstMigration` is not executed.

Let's imagine you want to revert `20150821112753_CreateFirstMigration` migration.

	$ ./bin/nasgrate down:show 20150821112753
	
or	

	$ ./bin/nasgrate down:show 20150821112753_CreateFirstMigration

	
and it will display

	Migration :: 20150821112753_CreateFirstMigration
	Description: The first migration. Created by dlevsha, 2015-08-21 11:27:53
	
	DROP TABLE test
	DROP TABLE test2	

Lets run revert process

	$ ./bin/nasgrate down:run 20150821112753_CreateFirstMigration
	
and it will display

	Migration :: 20150821112753_CreateFirstMigration
	
	DROP TABLE test
	DROP TABLE test2
	
	... complete	

If you look at your database you can see that `test` and `test2` tables were removed.

Run again `list` command

	$ ./bin/nasgrate list
	
and it will display

	Migration list:
	 - [26.08.2015 19:39:39] 20150826193939_CreateFirstMigration - new
	 - [26.08.2015 19:30:33] 20150826193033_New_Table_Test - new
	 

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