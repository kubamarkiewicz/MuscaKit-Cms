<?php

/* Front Controller */

use App\Kuba;

require_once 'protected/vendor/autoload.php';
require_once 'protected/config.php';


/*
	

	$db = new Musca_DB(HOST, USER, PASSWORD, DB_NAME);
	$i18n = new Musca_I18n(LANGS, $db);
	$template = new Musca_Smarty($i18n);
	$config = new Musca_Config($db);

	$dispatcher = new Musca_Dispatcher($db, $i18n, $template, $config);
	$dispatcher->ignite($_SERVER['REQUEST_URI']);

*/