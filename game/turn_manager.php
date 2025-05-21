<?php
/* =============================================================================
	TURN_MANAGER
		-> Manager turn with "playTurn"
		-> Create Status file
============================================================================= */

class TurnManager {
    
    private $board;
    private $turn;
    private $timestamp;
    private $status_file = COMMON_PATH.'/status.json';
    private $log = false;

	/***************************************************************************
	    Constructor
	***************************************************************************/

    public function __construct() {

    	$this->board = new Board();
    	$result = $this->board->loadAll();
    	$this->turn = $this->last_turn();

        logMessage("Last known game turn was n°".$this->turn.".");
    }

	/***************************************************************************
	    Utilitarian
	***************************************************************************/

    public function lockServer($lock=false) {

    	if ($lock === true)  { logMessage('Locking Server ...'); }
    	if ($lock === false) { logMessage('Unlocking Server ...'); }

		$status = [
        	'timestamp' => $this->timestamp,
			'last_turn' => $this->turn,
    		'lock' => $lock,
    		'nb_players' => $this->board->nbPlayers()
		];

		$world_info = new WorldInfo();
		$result = $world_info->setStatus($status);

		if ($result==true)	{ logMessage('Server lock changed !'); } 
		if ($result==false) { logMessage('Cannot change lock ?!'); }

    }

    private function last_turn() {
    	$world_info = new WorldInfo();
    	$status = $world_info->getStatus();
    	return $status['last_turn'];
    }

	public function turn_inc() { 
		logMessage('Next turn ! Bye turn '.$this->turn);
		$this->turn++; 
		logMessage('We are now in turn : '.$this->turn);
	}

	public function setTimestamp($timestamp = 0) {
		if ($timestamp == 0) { $this->timestamp = time(); }
		else  {$this->timestamp = $timestamp;}
	}

	public function fonctionnaire() {
        logMessage('Simulating Activity ...');
		$start = microtime(true);
		while ((microtime(true) - $start) < 3) {
    		// Effectuer un calcul inutile pour consommer du temps
    		$x = cos(mt_rand()) * sin(mt_rand());
		}
	}

	/***************************************************************************
	    Game Logic
	***************************************************************************/

    public function playTurn() {

    	// $this->board = new Board();
    	

        // Carefull here : first load status file, then lock for others, then 
        // load the BDD !
        $this->lockServer(true); // lock server
        // Update timestamp with turn beginning BUT not telling it to client for
        // now - it mess up client clocks.
        $this->setTimestamp();

// ==== HERE COMES THE TURN LOGIC !!! ==========================================


        // Ici la logique voudrait que l'on ne load rien vu qu'on a tout loadé
        // au départ ?
        // Sauf que certaines choses peuvent changer du fait des joueurs...
        //	-> Les joueurs en eux meme, les villes, les unités, les ordre, tout
        // en fait (vu qu'un joueur arrive potentiellement entre deux tour)
        
        // Clean what can change beetween turns :
        $this->board->cleanCollectionByClass('Player'); 	// a player can pop ...
        $this->board->cleanCollectionByClass('Unit');		// ...with his unit
        $this->board->cleanCollectionByClass('Order');		// and give orders !
        $this->board->cleanCollectionByClass('Locatable');	// Includes units.

        $result = $this->board->loadAll();

        allowLogs();

        $this->turn_inc();

        $this->board->turnOrderDecrease();
        $this->board->turnOrderExecute();

        // Simuler une charge serveur de 10 secondes
        // $this->fonctionnaire();
        
		disableLogs();

		// $this->cleanUnits();

        $result = $this->board->saveAll();

// ==== HERE ENDS THE TURN LOGIC !!! ===========================================

		
        $this->lockServer(false); // unlock server
        logMessage("Game turn n°".$this->turn." completed. ----");
    }
/*
    private function cleanUnits() {
    	// delete all city_less Units
    	$this->board->statusOfCollections();
    	logMessage($this->board->collection_to_json('Unit'));
    	$units = $this->board->getObjectsByDataProperty('Unit', 'city_id', 0);
    	logMessage('Unit without city support : '.count($units));
    	foreach($units as $unit) { $this->board->deleteUnit($unit);	 }
    }
*/
}

?>
