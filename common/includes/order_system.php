<?php
/* =============================================================================
	FULL ORDER LOGIC.
============================================================================= */

/* =============================================================================
███████╗ █████╗  ██████╗████████╗ ██████╗ ██████╗ ██╗   ██╗
██╔════╝██╔══██╗██╔════╝╚══██╔══╝██╔═══██╗██╔══██╗╚██╗ ██╔╝
█████╗  ███████║██║        ██║   ██║   ██║██████╔╝ ╚████╔╝ 
██╔══╝  ██╔══██║██║        ██║   ██║   ██║██╔══██╗  ╚██╔╝  
██║     ██║  ██║╚██████╗   ██║   ╚██████╔╝██║  ██║   ██║   
╚═╝     ╚═╝  ╚═╝ ╚═════╝   ╚═╝    ╚═════╝ ╚═╝  ╚═╝   ╚═╝  
============================================================================= */
class OrderFactory {
    public static function createOrder($order_type, $actorId, $data, $board) {
    	logMessage('Factory Creating Order with <'.$order_type.'>, <'.$actorId.'>, <'.$data.'>.');

    	logForce(serialize($order_type));

        switch ($order_type) {
            case 'MOVE':
                $order = new MoveOrder($order_type, $actorId, $data, $board);
                break;
            case 'ROAD':
                $order = new RoadOrder($order_type, $actorId, $data, $board);
                break;
            case 'MOVE_ROAD':
                $order = new MoveRoadOrder($order_type, $actorId, $data, $board);
                break;
            case 'BUILD_CITY':
                $order = new BuildCityOrder($order_type, $actorId, $data, $board);
                break;
            case 'BUILD_UNIT':
                $order = new RecruitUnitOrder($order_type, $actorId, $data, $board);
                break;
            case 'BUILD_BUILDING':
                $order = new BuildBuildingOrder($order_type, $actorId, $data, $board);
                break;
            default:
                logForce('Unknown order type: <'.$order_type.'>.');
                return null;
        }
        
        if (!$order->validateData()) { $order->orderErrorLog("Invalid order data.", true); return null; }
        if (!$order->actorCanDo())	 { $order->orderErrorLog("Order impossible for such Object.", true); return null; }

        // An order is NEVER EVER able to be less than 1 turn long.
        $order->turn = $order->nextActionTurn();
        if ($order->turn<1) {
        	logForce('Something went wrong with createOrder '.$order_type.' ! ');
        	return null;
        }
        return $order;

    }
}

/* =============================================================================
	Main traits
============================================================================= */
trait GenericOrder {
	public function BDDClass() { return 'Order'; }
    public function BDDData() {
        $data = [
        	'owner_id' => $this->actorId,
        	'order_type' => $this->order_type,
        	'data' => $this->data,
        	'turn' => $this->turn
        ];
        return $data;
    }
    // What will Order define :
    // Calculate next action Turn (done at instanciation)
	protected function nextActionTurn(): int { return 0; }
	// Validate "data" field (done at instanciation) :
    protected function validateData(): bool { return true; }
    // Validate actor hability & context faisability  (done at instanciation) :
    public function actorCanDo():bool { return true; }
	// Do what to do (including creation of objects or new orders or even deleting from colection)
    public function action() { return true; }
}

trait UnitBasedOrder {
	public function actor() {
		$object = $this->board->getObjectByKey('Unit', [$this->actorId]);
		return $object;
	}
}

trait CityBasedOrder {
	public function actor() {
		$object = $this->board->getObjectByKey('City', [$this->actorId]);
		return $object;
	}
}

/* =============================================================================
	The M.O.A.Orders
============================================================================= */
abstract class OrderRoot {
	protected $order_type;
    protected $actorId;
    protected $data;
    protected $board;
    public $turn;

    public function __construct($order_type, $id, $data, $board) {
    	$this->order_type = $order_type;
        $this->actorId = $id ?? 0;
        $this->data = $data ?? '';
        $this->turn = 0; // Init, reclaculé plus tard.
        $this->board = $board;
    }

    public function orderErrorLog($string, $force=false) {
    	$errorstring = '['.$this->actorId.'] ('.$this->order_type.') '.$string;
    	if ($force) { logForce($errorstring); }
    	else 		{ logMessage($errorstring); }
    }

    public function boardDelete() {
    	$this->orderErrorLog('Deleting order @Board');
    	$orderBDDObj = $this->board->getObjectByKey('Order', [$this->actorId]);
    	$this->board->deleteFromCollection($orderBDDObj);
    	return;
    }

    public function havePath() {
    	return false;
    }

    public function boardUpdate() {
    	$this->orderErrorLog('Updating order @Board');

    	if (!$this->validateData())  {
    		$this->orderErrorLog('Order Data invalide (or dirty way to delete myself?', true);
    		$this->boardDelete();
    		return;
    	}

    	// Update my board BDD object !
    	$orderBDDObj = $this->board->getObjectByKey('Order', [$this->actorId]);
    	$orderBDDObj->turn = $this->nextActionTurn();
    	$orderBDDObj->data = $this->data;
    	$orderBDDObj->order_type = $this->order_type;
    	$this->board->updateCollection($orderBDDObj);
    	return;
    }

    /*--------------------------------------------------------------------------
    	To be overwrite :
    --------------------------------------------------------------------------*/
    public function nextActionTurn(): int 	{ return 0; }
    public function totalTurns(): int 		{ return 0; }
    public function validateData(): bool  	{ return false; }
    public function actorCanDo():bool 		{ return false; }

}

/* =============================================================================
 ██████╗ ██████╗ ██████╗ ███████╗██████╗ ███████╗
██╔═══██╗██╔══██╗██╔══██╗██╔════╝██╔══██╗██╔════╝
██║   ██║██████╔╝██║  ██║█████╗  ██████╔╝███████╗
██║   ██║██╔══██╗██║  ██║██╔══╝  ██╔══██╗╚════██║
╚██████╔╝██║  ██║██████╔╝███████╗██║  ██║███████║
 ╚═════╝ ╚═╝  ╚═╝╚═════╝ ╚══════╝╚═╝  ╚═╝╚══════╝

	Specific Orders & Traits :

	Traits Contains :
			- the action in itself (move for CanMove ...)
			- data validator if needed (if not, the object manage by himself)
			- the ability to do the action

	Order Contains :
		validateData() :
			- the data given at instanciations are OK or not
		nextActionTurn() :
			- calculate the turn the action is suppose to happen, so it's the
			time needed to do so
		orderPossible() :
			- check wether the object is able to do the order
		action()
			- implement the board modification logic if this order happen

============================================================================= */

/* -----------------------------------------------------------------------------
	Moving
----------------------------------------------------------------------------- */
trait CanMove {
	public function move() {
		$this->orderErrorLog('Moving !');

		$targetGround = $this->nextGround();
	    $actor = $this->actor();

	    $this->orderErrorLog('Going to '.$targetGround->col.','.$targetGround->row);

		// Mettre à jour les coordonnées de l'acteur
	    $actor->col = $targetGround->col;
	    $actor->row = $targetGround->row;

	    // Mettre à jour l'état sur le board
	    $this->board->updateCollection($actor);

	    // Reveler le terrain de cet acteur
	    $this->board->revealVisibleGrounds($actor);

	    // Retirer la coordonnée consommée de $this->data
	    $path = $this->path();
	    array_shift($path); // Supprime le premier élément
	    $this->data = implode('_', $path); // Reconstruit la chaîne

	    // Update board for ME only :
	    if ($this->data=='') {
	    	$this->orderErrorLog('End of movement !');
	    	$this->boardDelete();	
	    } else {
	    	$this->orderErrorLog('Planning next move.');
	    	$this->boardUpdate();	
	    }
	}

    public function havePath() {
    	return true;
    }

    // Dedicated fun for eventual SCOPE
    public function highestCol() {
        $maxCol = null;
        foreach ($this->path() as $eachStep) {
            $col = (int)explode(',', $eachStep)[0];
            if ($maxCol === null || $col > $maxCol) {
                $maxCol = $col;
            }
        }
        return $maxCol;
    }
    public function lowestCol() {
        $minCol = null;
        foreach ($this->path() as $eachStep) {
            $col = (int)explode(',', $eachStep)[0];
            if ($minCol === null || $col < $minCol) {
                $minCol = $col;
            }
        }
        return $minCol;
    }
    public function highestRow() {
        $maxRow = null;
        foreach ($this->path() as $eachStep) {
            $row = (int)explode(',', $eachStep)[1];
            if ($maxRow === null || $row > $maxRow) {
                $maxRow = $row;
            }
        }
        return $maxRow;
    }
    public function lowestRow() {
        $minRow = null;
        foreach ($this->path() as $eachStep) {
            $row = (int)explode(',', $eachStep)[1];
            if ($minRow === null || $row < $minRow) {
                $minRow = $row;
            }
        }
        return $minRow;
    }
    // ------------

	public function totalMoveTurns() {
		$this->orderErrorLog('Calculating all path long');

		// Problem HERE !!!
		// Have to load KNOWN LANDS if by players
		// Have to load *all* LANDS if by engine
		// But you shouldn't load anything... outside dedicated loaders (request_manager/turn_manager)

		$path = $this->path();
		$actor = $this->actor();	

		$totalTurns = 0;

		foreach($path as $eachStep) {
			$targetCoordinates = explode(',', $eachStep);
	    	$col = (int)$targetCoordinates[0];
	    	$row = (int)$targetCoordinates[1];
	    	$targetGround = $this->board->getGround($col, $row);

	    	$nextTurn = $actor->getUnitTypeMovement() + $targetGround->getLandTypeMove();
    		if ($targetGround->getRoad()>0) { $nextTurn = $nextTurn - 1; }
    		if ($nextTurn>6) {$nextTurn = 6;}
			if ($nextTurn<1) {$nextTurn = 1;}

	    	$totalTurns = $totalTurns + $nextTurn;
		}

		return $totalTurns;

	}

	public function nextMoveTurn() {
    	//--
    	$this->orderErrorLog('Calculating next turn move');
    	$actor = $this->actor();		
    	$targetGround = $this->nextGround();

    	// $actor->getUnitTypeMovement();
    	// $targetGround->getLandTypeMove();

    	// Units have Move Value : 
    	// 1 -> 4 (Fast/Medium/Slow/VerySlow)
    	//
    	// Lands have Move Value :
    	// (1) 2 -> 5 (Roaded/Easy/Medium/Hard/VeryHard)
    	//
    	//	MoveUnit + LandMove = Fastness, Caped to Very Slow.
    	//
    	//	Fast : 2 turntoGo. VerySlow : 6 Turns to go.
    	// 
    	// Warning : move 0 = nothing

    	$nextTurn = $actor->getUnitTypeMovement() + $targetGround->getLandTypeMove();

    	if ($targetGround->getRoad()>0) { $nextTurn = $nextTurn - 1; }
    	if ($nextTurn>6) {$nextTurn = 6;}
		if ($nextTurn<1) {$nextTurn = 1;}

		return $nextTurn;
	}

    public function validateMoveData() {

	    // Décoder les coordonnées à partir des données
	    if (empty($this->data)) {
	    	$this->orderErrorLog('Missing or invalid data for move.', true);
	        return false;
	    }

    	$actor = $this->actor();
    	$this->orderErrorLog('Is my move valid ?');

	    if (!$actor) {
	    	$this->orderErrorLog('Actor not found.', true);
	        return false;
	    }



	    $coordinatesList = explode('_', $this->data);
	    if (count($coordinatesList) === 0 || !str_contains($coordinatesList[0], ',')) {
	    	$this->orderErrorLog('Invalid coordinate format.', true);
	        return false;
	    }

	    $targetCoordinates = explode(',', $coordinatesList[0]);
	    if (count($targetCoordinates) !== 2) {
	    	$this->orderErrorLog('Target coordinates must have both column and row.', true);
	        return false;
	    }

    	// --
    	return true;
    }
    public function actorCanMove() {
    	$this->orderErrorLog('Is my actor able to move ?');
    	// Data are safe now. 
		$actor = $this->actor();
	    $targetGround = $this->nextGround();

	    $this->orderErrorLog('Checking order list...');
	    if (!$actor->haveOrder('MOVE')) { return false;}

	    if (!$this->board->allAreClose([$actor, $targetGround])) { 
	    	$this->orderErrorLog('Destination not reachable.', true);
	        return false;
	    }

	    if ($actor->getUnitTypeMovement()==0) {
	    	$this->orderErrorLog('Actor have no legs !', true);
	        return false;
	    }

	    // check water

    	return true;

    }

    // Path manipulations ------------------------------------------------------

    public function path() { return explode('_', $this->data); }

    public function nextGround() {

    	$this->orderErrorLog('Finding next move ground...');

		// Data are already checked before : 
    	$path = $this->path();
    	$this->orderErrorLog('Path = '.serialize($path));
	    $targetCoordinates = explode(',', $path[0]);

	    $this->orderErrorLog('Coords = '.serialize($targetCoordinates));

	    $col = (int)$targetCoordinates[0];
	    $row = (int)$targetCoordinates[1];

	    $this->orderErrorLog('col/row = '.$col.'/'.$row);

		$this->board->loadByColRow('Ground', $col, $row);
    	$targetGround = $this->board->getGround($col, $row);

    	$this->orderErrorLog('Found: '.serialize($targetGround));

    	return $targetGround;
    }

    public function tokenizePath($owner) {
    	$this->orderErrorLog('Tokenize PATH is yet to write !');
    	if ($owner) {
    		return $this->data;	
    	} else {
    		return $this->data;	
    		$path = $this->path();
    		return $path[1];
    	}
    	
    }
}

class MoveOrder extends OrderRoot {
	use GenericOrder;
    use UnitBasedOrder;
    use CanMove;

    public function nextActionTurn(): int 	{ return $this->nextMoveTurn(); }
    public function totalTurns(): int 		{ return $this->totalMoveTurns(); }
    public function validateData(): bool 	{ return $this->validateMoveData(); }
    public function actorCanDo():bool 		{ return $this->actorCanMove(); }
    public function action() 				{ $this->move(); }
}

/* -----------------------------------------------------------------------------
	Building Roads
----------------------------------------------------------------------------- */
trait CanBuildRoad {
    public function buildRoad() {
        //--
    }
    public function validateBuildRoadData() {
    	// Check if data is an intermediate order...
    	return true;
    }
    public function actorCanBuildRoad() {
    	// --
    	return true;
    }
    public function nextBuildRoadTurn() {
    	return 0;
    }
}

class RoadOrder extends OrderRoot {
	use GenericOrder;
    use UnitBasedOrder;
    use CanBuildRoad;

    public function nextActionTurn(): int { return $this->nextBuildRoadTurn(); }
    public function validateData(): bool { return $this->validateBuildRoadData(); }
    public function actorCanDo():bool { return $this->actorCanBuildRoad(); }    
    public function action() { 
    	// Should be something like : Choptree, BuildBridge, etc.
    	$this->buildRoad(); 
    }
}

/* -----------------------------------------------------------------------------
	Moving & Doing stuff
----------------------------------------------------------------------------- */

class MoveRoadOrder extends OrderRoot {
	use GenericOrder;
    use UnitBasedOrder;
    use CanMove;
    use CanBuildRoad;

    // use CanChopTree;

    public function nextActionTurn(): int {
    	//--
    	return 0;
    }

    public function validateData(): bool {
        //--
        return true;
    }

    public function actorCanDo():bool {
    	return $this->actorCanBuildRoad() && $this->actorCanMove();
    }    

    public function action() {
        $currentHex = $this->actor()->getPosition();

        if (!$this->board->hasRoad($currentHex)) {
            $this->buildRoad();
        } else {
            $this->move();
        }
    }
}

/* -----------------------------------------------------------------------------
	Building a city
----------------------------------------------------------------------------- */

trait CanBuildCity {
    public function nextBuildCityTurn() {
        $this->orderErrorLog('Is suppose to calculate time to build a city.');
        /*

			Should include : 
				- chopping tree
				- dry swamps
				- Inventory transfert of settlers
				- etc. etc.

        */
        return 1;
    }

    public function buildCity() {

		$this->orderErrorLog('Building a City !');
		$actor = $this->actor();
	    $this->orderErrorLog('Home sweet home: '.$actor->col.','.$actor->row);
	    $this->board->newCity($actor->owner_id, $actor->col, $actor->row, 'New City');

	    // $this->saveBoardCollection('City');

	    // Delete actor ... Bye !
	    $this->board->deleteFromCollection($actor);

		$this->orderErrorLog('End of City building !');
	    $this->boardDelete();	

    }

    public function actorCanBuildCity() {
    	$this->orderErrorLog('Checking order list...');
    	if (!$this->actor()->haveOrder('BUILD_CITY')) { return false;}
    	return true;
    }

}

class BuildCityOrder extends OrderRoot {
	use GenericOrder;
    use UnitBasedOrder;
    use CanBuildCity;
    // use CanChopTree;

    public function nextActionTurn(): int { 
    	return $this->nextBuildCityTurn(); 
    }

    public function validateData(): bool {
        $this->orderErrorLog('Is suppose to validate data of BuildCity order.', true);
        // Mayber usefull or useless, have to sort it according to choping tree or not...
        return true;
    }

    public function actorCanDo():bool {
    	return $this->actorCanBuildCity();
    }    

    public function action() {
    	$this->buildCity();
    }
}

/* -----------------------------------------------------------------------------
	Recruiting a unit
----------------------------------------------------------------------------- */

trait CanRecruitUnit {
    public function recruitUnit() {
        //--
    }
    public function actorCanRecruitUnit() {
    	// --
    	return true;
    }
}

class RecruitUnitOrder extends OrderRoot {
	use GenericOrder;
    use CityBasedOrder;
    use CanRecruitUnit;

    public function nextActionTurn(): int {
    	//--
    	return 0;
    }

    public function validateData(): bool {
        //--
        return true;
    }

    public function actorCanDo():bool {
    	return $this->actorCanRecruitUnit();
    }    

    public function action() {
    	$this->recruitUnit();
    }
}

/* -----------------------------------------------------------------------------
	Building a ... building. *sigh*
----------------------------------------------------------------------------- */

trait CanBuildBuilding {
    public function buildBuilding() {
        //--
    }
    public function actorCanBuildBuilding() {
    	// --
    	return true;
    }
}


class BuildBuildingOrder extends OrderRoot {
	use GenericOrder;
    use CityBasedOrder;
    use CanBuildBuilding;

    public function nextActionTurn(): int {
    	//--
    	return 0;
    }

    public function validateData(): bool {
        //--
        return true;
    }

    public function actorCanDo():bool {
    	return $this->actorCanBuildBuildingn();
    }    

    public function action() {
    	this->buildBuilding();
    }
}


