<?php
/* =============================================================================
    KLODGAME DAEMON
        -> Is launched as a service on system
        -> have to manage system calls
        -> is the "clock", fire turn with turnManager)

============================================================================= */
// ===== Daemon dependencies =====
include_once 'backend_init.php';

// Prevent multi-launch of this script
$scriptName = basename(__FILE__);
$cmd = "ps aux | grep $scriptName | grep -v grep | grep -v 'sh -c'";
exec($cmd, $output);
if (count($output) > 1) {
    logMessage("Attempt to launch klodgame.php multiple times. Exiting.");
    exit;
}

// ===== MAIN INITIALIZATION =====
logMessage("Starting server '".WORLD_NAME."'...");

// TurnManager is GameManager I guess... change name ?
$turnManager = new TurnManager();

logMessage("Server started successfully.");

// ===================================================================
//  GAME LOOP
// ===================================================================

// Handle system signals for graceful shutdown
$running = true;

pcntl_signal(SIGTERM, function () use (&$running) {
    logMessage("Shutting down '".WORLD_NAME."'...");
    $running = false;
});

// Timer management
$tic_duration = TIC_SEC;

$pause_duration = round($tic_duration / 5);
if ($pause_duration > 5) {
    $pause_duration = 5;
}

$last_turn_time = time();
logMessage("Sleep time between TICs: $tic_duration sec. Next: " . date("d/m/Y H:i:s", $last_turn_time + $tic_duration));

// Time stamp is to be set at the BEGINNING of a turn - or a the server startup !
$turnManager->setTimestamp($last_turn_time);

// Unlock server to avoid waiting at startup.
$turnManager->lockServer(false);

// Infinite loop
while ($running) {
    pcntl_signal_dispatch();

    $current_time = time();
    $time_elapsed = $current_time - $last_turn_time;

    /*
        Eventuellement réfléchir à un systeme de pre-lock 3 secondes avant le
        tour, pour que les unités soient verrouillées et ensuite, un refresh
        des client avant le tour en lui meme pour que les joueurs voient ce qui
        sera pris en compte et joué réllement par le système.
    */

    if ($time_elapsed >= $tic_duration) {

        logMessage("Time for a new Turn.");
        $start_time = time();
        $turnManager->playTurn();
        $end_time = time();
        $execution_time = $end_time - $start_time;
        logMessage("Turn length: $execution_time sec. Next: " . date("d/m/Y H:i:s", $current_time + $tic_duration));
        $last_turn_time = $current_time;

    }

    sleep($pause_duration); // max 5 sec. to avoid to wait at service stop/restart

}

$turnManager->lockServer(true);

logMessage("Server shutdown complete.");
?>

