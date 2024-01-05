<?php
use function PHPUnit\Framework\fileExists;

$_SERVER['DOCUMENT_ROOT'] = __DIR__.'/../tests';
define('TESTING_WORK_DIR', sys_get_temp_dir().'/translating-autoloader-tmp');
define('TRANSLATIONS_ROOT', TESTING_WORK_DIR.'/translations');
define('TRANSLATIONS_CACHE', TESTING_WORK_DIR.'/cache');
?>