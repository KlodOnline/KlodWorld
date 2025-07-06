<?php

function updateSessionStatus($log = false) {
    $world_info = new WorldInfo();
    $file_mod_time = $world_info->getModTime();

    // Vérifie si le fichier est plus récent ou si la session n'a pas encore de données 'status'
    $sessionStatus = SessionManager::get('status');
    if (!isset($sessionStatus['mod_time']) || $sessionStatus['mod_time'] < $file_mod_time) {
        $status = $world_info->getStatus();
        $prev_turn = $sessionStatus['last_turn'] ?? null;

        // Met à jour les informations dans la session
        $status['sec'] = TIC_SEC;
        $status['server_time'] = time();
        SessionManager::set('status', $status);

        logMessage("Session status updated.", $log);

        // Si last_turn a changé, on vide le cache
        if ($prev_turn !== null && $prev_turn != $status['last_turn']) {
            logMessage('Cache Cleared !', $log);
            SessionManager::delete('cache');
        }
    } else {
        logMessage("Session status is up-to-date.", $log);
    }
}

/* -------------------------------------------------------------------------
    Functions whose purpose is to manipulate $col, $row & coords objects
    May be usable by any system needed to work with <worldgrid> - a concept,
    not a variable - supposed to be intensively used.
    When $direction is used, here is the cheatSheet : 
    	0=E 1=NE 2=NW 3=W 4=SW 5=SE

    Usually we work with (col, row) (beacause it's closer from BDD) but some
    function may accept (col, row) and coord object, but it should be crystal 
    clear in the description

------------------------------------------------------------------------- */
function southDirection($row) {
	if ( $row % 2 === 0) { return 4; }
	return 5;
}
function northDirection($row) {
	if ( $row % 2 === 0) { return 2; }
	return 1;
}	

function convert_to_int($value) {
    if (isset($value) && is_numeric($value) && (int)$value == $value) {
        return (int)$value;
    }
    return null;
}
//------------------- TO PU IN HEXALIB
function uniqueCoords(array $coords): array {
	$uniqueMap = [];
	foreach ($coords as $coord) {
        $key = $coord->stg();
        $uniqueMap[$key] = $coord;
    }
	return array_values($uniqueMap);
}

// Workdnly with Heaxalib Cube Coode
function coordsIntersect(array $list1, array $list2): bool {
    foreach ($list1 as $c1) {
        foreach ($list2 as $c2) {
            if ($c1->col === $c2->col && $c1->row === $c2->row) {
                return true;
            }
        }
    }
    return false;
}
//-------------------

function randomAround(float $base, float $percent = 0.2): int {
    $min = (int) round((1 - $percent) * $base);
    $max = (int) round((1 + $percent) * $base);
    return (int) round(mt_rand($min, $max));
}



function magicCylinder($col) {
    // Far East
    if ($col >= MAX_COL) { $col = $col % MAX_COL; }
    // Far West 
    if ($col < 0) { $col = (($col % MAX_COL) + MAX_COL) % MAX_COL; }
    return $col;
}

function is_jwt_valid($jwt, $secret = 'secret') {
	// split the jwt
	$tokenParts = explode('.', $jwt);
	$header = base64_decode($tokenParts[0]);
	$payload = base64_decode($tokenParts[1]);
	$signature_provided = $tokenParts[2];

	// Rebuild signature to check that shit !
	$signature = hash_hmac('sha256',"$tokenParts[0].$tokenParts[1]",$secret,true);
	$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

	// Verify ....
	return ($base64UrlSignature === $signature_provided);
}

function convert($size) {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

function randomColor_old() {
	    return sprintf("#%06X", mt_rand(0, 0xFFFFFF));
}

function randomColor() {
    $keys = [
        'Ba', 'Rb', 'Wb', 'Or', 'Cr', 'Da', 'Db', 
        'Lf', 'Kg', 'Nb', 'Te', 'Bl', 'Hm', 'Pi', 'Dl'
    ];

    // Récupère deux clés aléatoires distinctes
    $randomKeys = array_rand(array_flip($keys), 2);
    return implode(' ', $randomKeys);
}
/* -----------------------------------------------------------------------------
	PERFORMANCE AUDIT
----------------------------------------------------------------------------- */
function startTimer($name='') {
	global $TIMER_START, $TIMER_STEP;
	$TIMER_START = microtime(true);
	allowLogs();
	logMessage("[TIMER] Started. ".$name);
	disableLogs();
}
function stepTimer($name='') {
	global $TIMER_START, $TIMER_STEP;
	if ($TIMER_STEP === 0) {
		$TIMER_STEP = microtime(true);
		$time_elapsed = $TIMER_STEP - $TIMER_START;	
	} else {
		$new_step = microtime(true);
		$time_elapsed = $new_step - $TIMER_STEP;
		$TIMER_STEP = $new_step;
	}
	allowLogs();
	logMessage("[TIMER] Step ".$name." Time: ".number_format($time_elapsed, 4)." sec.");
	disableLogs();
}
function stopTimer($name='') {
	global $TIMER_START, $TIMER_STEP;
    if ($TIMER_START === 0) {
    	allowLogs();
        logMessage("[TIMER] Error: Timer not started.");
        disableLogs();
        return;
    }
    $new_step = microtime(true);
    if ($TIMER_STEP > 0) {
		$last_step_time_elapsed = microtime(true) - $TIMER_STEP;
		allowLogs();
		logMessage("[TIMER] Step Time: ".number_format($last_step_time_elapsed, 4)." sec.");
		disableLogs();
    }
	$time_elapsed = microtime(true) - $TIMER_START;
	allowLogs();
	logMessage("[TIMER] Stopped. Total ".$name." Time : ".number_format($time_elapsed, 4)." sec.");
	disableLogs();
}

/* -----------------------------------------------------------------------------
	LOGGING LOGIC
----------------------------------------------------------------------------- */
function allowLogs($level = 1) {
	global $LOG_LEVEL;
	$LOG_LEVEL = (int) $level;
}
function disableLogs() {
	global $LOG_LEVEL;
	$LOG_LEVEL = 0;
}
function memory_int_to_string($memInt) {
	if (floor($memInt/1000000000)>0) { return floor($memInt/1000000000).'Go'; }
	if (floor($memInt/1000000)>0) { return floor($memInt/1000000).'Mo'; }
	if (floor($memInt/1000)>0) { return floor($memInt/1000).'Ko'; }
	return floor($memInt).'o';
}
function logForce($string) {
	global $LOG_LEVEL;
	$old_log_level = $LOG_LEVEL;
	$LOG_LEVEL = 1;
	logMessage($string);
	$LOG_LEVEL = $old_log_level;
}

function logMessage($string) {
	global $LOG_LEVEL;
	if ($LOG_LEVEL<=0) {return;}
    $backtrace = debug_backtrace();
    // Retrieve the file and line of the caller
    $caller = $backtrace[0];
    $file = $caller['file'];
    $line = $caller['line'];
    unset($backtrace);
	$mem = memory_int_to_string(memory_get_usage());
    $txt = date('d/m/Y [H:i:s]') . " [" . $mem . "] " . $string . " (File: " . $file. ", Line: " . $line . ")";
    error_log($txt);
}


?>