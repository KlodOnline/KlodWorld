<?php
/* =============================================================================
    worldgenerator.php
    - BDD->HTML
    	- load & save
    	- world gen steps

============================================================================= */
ob_start();

include_once __DIR__.'/gm_init.php';

// Initialisation de Board et WorldGenerator
// session_start();

$board = new Board();
$world_generator = new WorldGenerator($board);

/* -----------------------------------------------------------------------------
	Action Buttons !
----------------------------------------------------------------------------- */
$action = $_GET['action'] ?? null;


$log = true;

/*

	We should LOCK the world here !!! AND DON'T DO SHIT IF WORLD IS LOCKED BEFORE !
	
	And adapt turn manager wich should not do anythinng if the world is locked 
	BEFORE HIM !!!!

*/

if ($action && method_exists($world_generator, $action)) {
	
    header('Content-Type: application/json');

    // allowLogs();

    logMessage('Doing '.$action.'...', $log);

	// Call method from world_generator dynamically
    $world_generator->$action();
	
	// Show the json
	logMessage($action.' done ! Generating json ...', $log);
    echo $board->collection_to_json('Ground');
    logMessage('Json done !', $log);

    // disableLogs();

	// Force l'envoi des données au client
    ob_flush();
    flush();
    ignore_user_abort(true);

} else {
	// No need to be explicit for those hacker bastards
    echo "404";
}

// session_write_close();

//If nothing to do no need to continue !

exit();
?>