<?php
/* =============================================================================
	COMMON INIT :
		-> Includes all variables & files & anything that is usefull for
		both backend & frontend
============================================================================= */
// ==== CONSTANTS ==============================================================
define('COMMON_PATH', __DIR__.'/..');
$ini_file = parse_ini_file(COMMON_PATH.'/param/config.ini', true);
// ---- SQL Init ---------------------------------------------------------------
define('DB_HOST', 	(string) $ini_file['sql']['host']);
define('DB_USER', 	(string) $ini_file['sql']['user']);
define('DB_PASS', 	(string) $ini_file['sql']['password']);
define('DB_NAME', 	(string) $ini_file['sql']['database']);
define('BATCH_SIZE', (int) $ini_file['sql']['batch_size']);
// ---- World Definition -------------------------------------------------------
define('WORLD_NAME',(string) $ini_file['world']['world_name']);
define('MAX_COL', 	(int) $ini_file['world']['max_col']);
define('MAX_ROW', 	(int) $ini_file['world']['max_row']);
define('DEMO', 		(boolean) $ini_file['world']['demo']);
define('TIC_SEC', 	(int) $ini_file['world']['tic_sec']);
define('WEBSITE', 	(string) $ini_file['world']['website']);
define('WORLD_IP', 	(string) $ini_file['world']['world_ip']);
define('CHAT_PORT', (int) $ini_file['world']['chat_port']);
define('GAME_PORT', (int) $ini_file['world']['game_port']);
// ==== INCLUDES ===============================================================
include_once COMMON_PATH.'/includes/session_manager.php';
include_once COMMON_PATH.'/includes/helpers.php';
include_once COMMON_PATH.'/includes/hexalib.php';
include_once COMMON_PATH.'/includes/bdd_io.php';
include_once COMMON_PATH.'/includes/world_info.php';
// ---- XML Objects ------------------------------------------------------------
include_once COMMON_PATH.'/includes/xml_objects/xml_object.php';
include_once COMMON_PATH.'/includes/xml_objects/xml_object_manager.php';
// ---- BDD Objects ------------------------------------------------------------
include_once COMMON_PATH.'/includes/sql_objects/bdd_object.php';
include_once COMMON_PATH.'/includes/sql_objects/bdd_object_manager.php';
include_once COMMON_PATH.'/includes/sql_objects/locatable.php';
include_once COMMON_PATH.'/includes/sql_objects/inventory_cell.php';
include_once COMMON_PATH.'/includes/sql_objects/player.php';
include_once COMMON_PATH.'/includes/sql_objects/order.php';
// ---- Other Objects ----------------------------------------------------------
include_once COMMON_PATH.'/includes/board.php';
include_once COMMON_PATH.'/includes/order_system.php';
include_once COMMON_PATH.'/includes/locatables/city.php';
include_once COMMON_PATH.'/includes/locatables/unit.php';
include_once COMMON_PATH.'/includes/locatables/ground.php';
// ==== GLOBALS ================================================================
$LOG_LEVEL = 0;
$TIMER_START = 0;
$TIMER_STEP = 0;
?>
