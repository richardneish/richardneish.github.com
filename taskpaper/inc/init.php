<?php
/* 
 * Basic initialisation, config
 */
// globally used app path, for all includes and requires
define('APP_PATH', realpath(dirname('__FILE__')) . '/');

// default extension for all taskpaper files (txt is probably best)
define('EXT', ".txt");

// if we want debug messages logged
define('DEBUG', true);

// basic app functions, incl. debug function
require_once(APP_PATH.'inc/common.php');

// load the global app config and language arrays
$config = array();
$lang = array();
require_once(APP_PATH.'conf/config.php');

// user editable config
require_once(APP_PATH.'inc/ini.class.php');
$ini = new Ini(APP_PATH.'conf/config.ini');

// the main taskpaper model
require_once(APP_PATH.'inc/taskpaper.class.php');
$taskpapers = new Taskpapers($ini->item('active_taskpaper'),
                             $ini->item('archive_taskpaper'),
                             $ini->item('taskpaper_folder'));
?>
