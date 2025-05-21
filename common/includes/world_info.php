<?php
/* =============================================================================
	WorldInfo class
		Allows to read/write status.json.
============================================================================= */
class WorldInfo {

    // Path to the status file
	private const FILE_PATH = COMMON_PATH.'/status.json';

    // List of required status keys
    private $status_keys = [
        'timestamp',
        'last_turn',
        'lock',
        'nb_players'
    ];

    private int $timestamp = 0;
    private int $mod_time = 0;
    private int $last_turn = 0;
    private bool $lock = true;
    private int $nb_players = 0;

    public function __construct() {
        $this->timestamp = time();
        $this->mod_time = time();
    }

    private function defaultStatus() {
		$status = [
			'timestamp' => $this->timestamp,
	        'last_turn' => $this->last_turn,
	        'lock' 		=> $this->lock,
	        'nb_players'=> $this->nb_players,
	        'mod_time' 	=> $this->mod_time
		];
    	return $status;
    }

    private function updateStatus($currStatus, $status) {
    	if (is_array($status) && is_array($currStatus)) { 
		    foreach ($status as $key => $value) {
		        if (in_array($key, $this->status_keys)) {
		            $currStatus[$key] = $value;
		        }
		    }
    	}
    	return $currStatus;
    }

    // Only check file modification status to avoid opening it.
    public function getModTime() {
    	$file_mod_time = 0;
    	if (file_exists(self::FILE_PATH)) { $file_mod_time = filemtime(self::FILE_PATH); }
    	return $file_mod_time;
    }

	public function getStatus() {
	    $current_status = $this->defaultStatus();
	    if (file_exists(self::FILE_PATH)) {
	        $status = json_decode(file_get_contents(self::FILE_PATH), true);
	        if (is_array($status)) {
	            $current_status = $this->updateStatus($current_status, $status);
	        } else {
	            logMessage("Error while trying to read 'status.json'.");
	        }
	        $current_status['mod_time'] = $this->getModTime();
	    }
	    return $current_status;
	}

    // Set the status array to the JSON file
    public function setStatus($status, $log = false) {
    	$current_status = $this->getStatus();
	    $current_status = $this->updateStatus($current_status, $status);
        // Encode the status array as JSON
        $json_data = json_encode($current_status);
        // Attempt to write the data to the status.json file
        if (file_put_contents(self::FILE_PATH, $json_data)) {
            logMessage("File 'status.json' written.");
            return true;
        } else {
            logMessage("Error while trying to write 'status.json'.");
            return false;
        }
    }
}
