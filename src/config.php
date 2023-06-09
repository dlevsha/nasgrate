<?php
define('IS_PHAR', strpos(dirname(__FILE__), 'phar://') === 0);
define('DIR_SRC', IS_PHAR ? dirname(Phar::running(false)) : substr(dirname(__FILE__), 0, strlen(dirname(__FILE__)) - 4));
define('DIR_ROOT', substr(dirname(__FILE__), 0, strlen(dirname(__FILE__)) - 4));

define('ENVIRONMENT_FILE', DIR_SRC . '/.env');

if (!file_exists(ENVIRONMENT_FILE)) die('File "' . ENVIRONMENT_FILE . '" not exist');

if (($_ENV['DATABASE_DRIVER'] ?? null) && ($_ENV['DATABASE_DRIVER'] ?? null)) {
    $params = $_ENV;
    if (!($params['FILE_EXTENSION'] ?? null)) {
        $params['FILE_EXTENSION'] = 'sql';
    }
    if (!($params['DEFAULT_DESCRIPTION_MESSAGE'] ?? null)) {
        $params['DEFAULT_DESCRIPTION_MESSAGE'] = 'Created by CURRENT_USER, CURRENT_DATE';
    }
    foreach ($params as $paramName => $paramValue) {
        $params['DEFAULT_DESCRIPTION_MESSAGE'] = str_replace($paramName, $paramValue, $params['DEFAULT_DESCRIPTION_MESSAGE']);
    }
    if (!($params['DIR_MIGRATION'] ?? null)) {
        $params['DIR_MIGRATION'] = 'DIR_ROOT/data/migrations';
    } else {
        $params['DIR_MIGRATION'] = 'DIR_ROOT/' . $params['DIR_MIGRATION'];
    }
    if (!($params['DIR_DBSTATE'] ?? null)) {
        $params['DIR_DBSTATE'] = 'DIR_ROOT/data/dbstate';
    } else {
        $params['DIR_DBSTATE'] = 'DIR_ROOT/' . $params['DIR_DBSTATE'];
    }
} else {
    foreach (file(ENVIRONMENT_FILE) as $string) {
        if (!trim($string) || preg_match('/^ *#/', $string)) {
            continue;
        }
        list($paramName, $paramValue) = explode('=', trim($string), 2);
        $paramValue = preg_replace('/#.*$/', '', $paramValue);
        $params[trim($paramName)] = $paramValue ? trim($paramValue) : null;
    }
}

$requiredParams = array(
    'DATABASE_DRIVER',
    'DATABASE_HOST',
    'DATABASE_NAME',
    'DATABASE_USER',
    'DATABASE_PASSWORD',
    'VERSION_TABLE_NAME',
    'FILE_EXTENSION',
    'DIR_MIGRATION',
    'DIR_DBSTATE',
    'DEFAULT_DESCRIPTION_MESSAGE',
);

array_map(function ($name) use ($params) {
    if (!isset($params[$name])) {
        die('Param ' . $name . ' not set in file ' . ENVIRONMENT_FILE);
    }
}, $requiredParams);

define('DATABASE_DRIVER', trim($params['DATABASE_DRIVER']));
define('DATABASE_HOST', trim($params['DATABASE_HOST']));
define('DATABASE_NAME', trim($params['DATABASE_NAME']));
define('DATABASE_USER', trim($params['DATABASE_USER']));
define('DATABASE_PASSWORD', trim($params['DATABASE_PASSWORD']));
define('DATABASE_PORT', trim($params['DATABASE_PORT']));

define('DATABASE_DSN', DATABASE_DRIVER . ':host=' . DATABASE_HOST . (DATABASE_PORT ? ';port=' . DATABASE_PORT : '') . ';dbname=' . DATABASE_NAME);

define('VERSION_CONTROL_STRATEGY', isset($params['VERSION_CONTROL_STRATEGY']) ? $params['VERSION_CONTROL_STRATEGY'] : null);

if (isset($params['DATABASE_HOST_SECONDARY'])) {
    define('DATABASE_HOST_SECONDARY', trim($params['DATABASE_HOST_SECONDARY']?:''));
    define('DATABASE_NAME_SECONDARY', trim($params['DATABASE_NAME_SECONDARY']?:''));
    define('DATABASE_USER_SECONDARY', trim($params['DATABASE_USER_SECONDARY']?:''));
    define('DATABASE_PASSWORD_SECONDARY', trim($params['DATABASE_PASSWORD_SECONDARY']?:''));
    define('DATABASE_PORT_SECONDARY', trim($params['DATABASE_PORT_SECONDARY']?:''));

    define('DATABASE_DSN_SECONDARY', DATABASE_DRIVER . ':host=' . DATABASE_HOST_SECONDARY . (DATABASE_PORT_SECONDARY ? ';port=' . DATABASE_PORT_SECONDARY : '') . ';dbname=' . DATABASE_NAME_SECONDARY);
}

define('VERSION_TABLE_NAME', $params['VERSION_TABLE_NAME']);

define('FILE_EXTENSION', $params['FILE_EXTENSION']);

define('DIR_MIGRATION', strpos($params['DIR_MIGRATION'], 'DIR_ROOT') === 0 ? DIR_SRC . str_replace('DIR_ROOT', '', $params['DIR_MIGRATION']) : $params['DIR_MIGRATION']);
define('DIR_DBSTATE', strpos($params['DIR_DBSTATE'], 'DIR_ROOT') === 0 ? DIR_SRC . str_replace('DIR_ROOT', '', $params['DIR_DBSTATE']) : $params['DIR_DBSTATE']);

define('DEFAULT_DESCRIPTION_MESSAGE', str_replace(array('CURRENT_USER', 'CURRENT_DATE'), array(get_current_user(), date('Y-m-d H:i:s')), $params['DEFAULT_DESCRIPTION_MESSAGE']));

spl_autoload_register(function ($className) {
    $className = str_replace(array('\\'), array('/'), $className);

    $splited = explode('/', $className);
    foreach ($splited as $i => $part) {
        $pathUnited[] = $i < count($splited) - 1 ? strtolower($part) : $part;
    }

    $path = DIR_ROOT . '/src/' . implode('/', $pathUnited) . '.php';
    // echo $path.'<br />'."\n";
    if (file_exists($path)) {
        require_once $path;
        return;
    }
    throw new Exception('Class ' . $className . ' not found');
});