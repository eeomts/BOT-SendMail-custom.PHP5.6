<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('memory_limit', '256M');
set_time_limit(8000);

error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_ALL, 'pt_BR.UTF8');
mb_internal_encoding('UTF8');
mb_regex_encoding('UTF8');


define("PDO_HOST", "localhost");
define("PDO_USER", "root");
define("PDO_DB", "contratafashion");
define("PDO_PASS", "");
define("PDO_DRIVER", "mysql");
define("PDO_PORT", "3307");

require_once 'Db.php';
