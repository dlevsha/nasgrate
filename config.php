<?php

// Change your database settings
define('DATABASE_DRIVER', 'mysql');
define('DATABASE_HOST', 'localhost');
define('DATABASE_NAME', 'test2');
define('DATABASE_USER', 'root');
define('DATABASE_PASSWORD', '');
// \-------------------------------



// MySQL sample config
define('DATABASE_DSN',  DATABASE_DRIVER.':host='.DATABASE_HOST.';dbname='.DATABASE_NAME);

define('VERSION_TABLE_NAME', '__migrationVersions');

define('FILE_PREFIX', 'Migration');

define('DIR_ROOT', dirname(__FILE__));
define('DIR_MIGRATION', DIR_ROOT.'/migrations');

define('DEFAULT_DESCRIPTION_MESSAGE', 'Created by '.get_current_user().', '.date('Y-m-d H:i:s'));