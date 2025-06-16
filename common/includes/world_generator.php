<?php
/* =============================================================================
    World Generator
    - to be used by others to randomly generate world lands
    - must be autonomous on main functions

============================================================================= */
class WorldGenerator {

    private $board;
    private $hexalib;
	private $attribution;
	private $climateLines;
	
	/***************************************************************************
	    Constructor: Initializes
	***************************************************************************/
    public function __construct(Board $board) {
        $this->board = $board;
        $this->hexalib = new Hexalib();

        $polar_lat = round((MAX_ROW - 1) / 14);
        $tropic_lat = round((MAX_ROW - 1) / 8);
        $equator = round((MAX_ROW - 1) / 2);

        $this->climateLines = [
			'equator' => $equator,
	    	'arctic_circle' =>  0 + $polar_lat,
	    	'tropic_north' => $equator - $tropic_lat,
	    	'tropic_south' => $equator + $tropic_lat,
	    	'antarctic_circle' => (MAX_ROW - 1) - $polar_lat
		];

    }

	/***************************************************************************
	    Creation functions
	***************************************************************************/

	/* -------------------------------------------------------------------------
	    Full world creation ! (for scripting purpose)
	------------------------------------------------------------------------- */
    public function create() {
    	logMessage('Et Dieu crea la Terre !');
        // Rien pour le moment, on clear pour simuler un changement complet
        $this->clear();
    }

    public function loadWorld() {
    	$this->board->loadGrounds();
    }

    public function saveWorld() {
    	$this->board->saveCollection('Ground', false);
    }

    public function FullCreation() {
    	$this->clearWorld(true, false);
    	$this->createTectonicPlates(false, false, null);
    	$this->Humidity(false, false);
    	$this->LatitudeClimate(false, false);
    	$this->Rivers(false, false);
    	$this->DesertsCoasts(false, true);
    }

	/* -------------------------------------------------------------------------
	    External Interactions
	------------------------------------------------------------------------- */

	public function getGround($col, $row) {
		$ground = $this->board->getGround((int) $col, (int) $row);
		return $ground;
	}

	/* -------------------------------------------------------------------------
	    Setting Grounds
	------------------------------------------------------------------------- */
	public function setGround($col, $row, $type) {
		// Ignorer tout ce qui est en trop :
		if ($row>=MAX_ROW or $row<0) {return;}
		// Jouer le cylindre :
		if ($col>=MAX_COL) { $col = $col - MAX_COL; }
		if ($col<0) { $col = MAX_COL + $col; }
		// logMessage('Setting '.$col.','.$row.' as a '.$type);
		$this->board->setGroundTypeByCoords((int) $col, (int) $row, (int) $type);
	}
	public function setGroundFromCoord($coord, $type) {
		$coord = $this->hexalib->convert($coord, 'Oddr');
		$this->setGround($coord->col, $coord->row, $type);
	}
	public function setGroundsFromCoords($coords, $type) {
		foreach($coords as $coord) {
			$this->setGroundFromCoord($coord, $type);
		}
	}
	public function setSpiralGroundsFromCoords($coords, $type, $radius) {
		foreach($coords as $coord) {
			$spiral_coords = $this->hexalib->spiral($coord,$radius);
			$this->setGroundsFromCoords($spiral_coords, $type);
		}
	}
	public function setGroundSpiral($col, $row, $radius, $type) {
		$coord = $this->hexalib->coord([$col,$row],'Oddr');
		$coords = $this->hexalib->spiral($coord,$radius);
		$this->setGroundsFromCoords($coords, $type);
	}

	/* -------------------------------------------------------------------------
	    Converting Grounds from array
	------------------------------------------------------------------------- */
	public function convertGround($coord, $fromLands, $toLand) { 
		// If not an array, force it (comaptibility purpose)
		if (!is_array($fromLands)) {$fromLands = [$fromLands];}
		$target = $this->getGround($coord->col, $coord->row);
		if ($target!=null) {
			if (in_array($target->ground_type(), $fromLands)) {
				$this->setGroundFromCoord($coord, $toLand);
				return true;
			}
		}
		return false;
	}
	public function convertGrounds($coords, $fromLands, $toLand) {
		$result_count = 0;
		foreach($coords as $coord) {
			$result = $this->convertGround($coord, $fromLands, $toLand);
			if ($result==true) {$result_count++;}
		}
		if ($result_count==count($coords)) {return true;}
		return false;
	}
	public function convertGroundSpiral($coord, $radius, $fromLands, $toLand) {
		$coords = $this->hexalib->spiral($coord,$radius);
		return $this->convertGrounds($coords, $fromLands, $toLand);
	}

	/* -------------------------------------------------------------------------
	    Other Utilitarians
	------------------------------------------------------------------------- */
	// Bien verifier si on s'en sert...
	public function randomNeighbourCoord($col, $row){
		$coord = $this->hexalib->coord([$col,$row],'Oddr');
		$dice = rand(0,5);
		$neighbour = $this->hexalib->neighbour($coord, $dice);
		return $neighbour;
	}

	public function randomCoord($min_col, $max_col, $min_row, $max_row) {
		$col = rand($min_col, $max_col);
		$row = rand($min_row, $max_row);
		$coord = $this->hexalib->coord([$col,$row],'Oddr');
		return $coord;
	}


	public function checkNeighbour($coord, $param, $value) {
		$neighbours = $this->hexalib->all_neighbours($coord);
		foreach($neighbours as $neighbour) {
			$neighbour = $this->hexalib->convert($neighbour, 'oddr');
			$neighbour->col = magicCylinder($neighbour->col);	
			$targetLand = $this->getGround($neighbour->col, $neighbour->row);
			if ($targetLand->$param==$value) { return true; }
		}
		return false;
	}


	/* -------------------------------------------------------------------------
	    Clear the world (full plains)
	------------------------------------------------------------------------- */
	public function clearWorld($load = true, $save = true){

		

		if ($load) {$this->loadWorld();}

		logMessage('Loading : NB Grounds = '.$this->board->nbGrounds()) ;

		

		// En cas de monde vierge, il faut creer les grounds :
	    for ($currRow = 0; $currRow < MAX_ROW; $currRow++) {
	        for ($currCol = 0; $currCol < MAX_COL; $currCol++) {
	            $this->setGround($currCol, $currRow, 8);
	        }
	    }

	    

	    logMessage('Setting Default : NB Grounds = '.$this->board->nbGrounds()) ;



	    logMessage('Board Cleaning...');

		// Ensuite il faut virer ce qui depasse :
		/*
		$this->board->forEachGroundObject(function ($object) {
			if ($object->col<0 or $object->col>=MAX_COL or $object->row<0 or $object->row>=MAX_ROW) {
				$this->board->deleteFromCollection($object);
			}
		});
		*/

		$this->board->forEachGround(function ($col, $row) {
		    if ($col < 0 || $col >= MAX_COL || $row < 0 || $row >= MAX_ROW) {
		        $this->board->deleteFromCollection(['col' => $col, 'row' => $row]); // Suppression basée sur les coordonnées
		    }
		});


		logMessage('Erasing too much : NB Grounds = '.$this->board->nbGrounds()) ;

		
		if ($save) {$this->saveWorld();}

		
	}

	/* -------------------------------------------------------------------------
	    Drawing Functions !!!!
	------------------------------------------------------------------------- */
	public function lineDrawer($test_mode, $terrain, $spotlines, $drawFunc, $size=3, $shift=0) {

		// $maxspl = count($spotlines);
		logMessage('Drawing Lines ... ');

		foreach($spotlines as $key => $spotline) {

			$n = count($spotline);
			$mid = floor( $n / 2); // Position du milieu
			$maxSize = 5;         // Taille maximale
			$prev_spot = null;    // Init au début

			foreach($spotline as $index => $spot){
            	if ($prev_spot === null) { $prev_spot = $spot; continue; }

	    		$shifted_coord1 = clone $prev_spot;
				$shifted_coord1->col += $shift;
	    		$shifted_coord1->row += $shift;

				$shifted_coord2 = clone $spot;
	    		$shifted_coord2->col += $shift;
	    		$shifted_coord2->row += $shift;

				if ($index <= $mid) { $size = ($maxSize / $mid) * $index; }
		    	else { $size = ($maxSize / ($n - $mid - 1)) * ($n - $index - 1); }
		    	$size=round($size);

	    		if ($test_mode) { $this->drawSimpleLine($terrain, $shifted_coord1, $shifted_coord2); }
	    		else { $this->$drawFunc($shifted_coord1, $shifted_coord2, $size); }
	    		$prev_spot = $spot;
			}
			unset($prev_spot);
		}
		return;
	}


	public function lineDrawer_new($test_mode, $terrain, $spotlines, $drawFunc, $size = 3, $shift = 0) {
	    logMessage('Drawing Lines ... ');

	    foreach ($spotlines as $spotline) {
	        $n = count($spotline);
	        if ($n < 2) continue; // S'assurer qu'il y a au moins deux points

	        $mid = floor($n / 2);
	        $maxSize = 5;
	        $sizes = [];

	        // Pré-calculer les tailles
	        for ($i = 0; $i < $n; $i++) {
	            $sizes[$i] = $i <= $mid
	                ? round(($maxSize / $mid) * $i)
	                : round(($maxSize / ($n - $mid - 1)) * ($n - $i - 1));
	        }

	        $prev_spot = $spotline[0]; // Initialiser avec le premier point
	        for ($index = 1; $index < $n; $index++) {
	            $spot = $spotline[$index];

	            // Calculer directement les coordonnées décalées
	    		$shifted_coord1 = clone $prev_spot;
				$shifted_coord1->col += $shift;
	    		$shifted_coord1->row += $shift;

				$shifted_coord2 = clone $spot;
	    		$shifted_coord2->col += $shift;
	    		$shifted_coord2->row += $shift;

	            if ($test_mode) {
	                $this->drawSimpleLine($terrain, $shifted_coord1, $shifted_coord2);
	            } else {
	                $this->$drawFunc($shifted_coord1, $shifted_coord2, $sizes[$index]);
	            }

	            $prev_spot = $spot;

	            unset($spot);
	        }
	    }
	}

	public function drawSimpleLine($terrain, $coord1, $coord2) {
		$coords_line = $this->hexalib->line_draw($coord1, $coord2);
	    $this->setGroundsFromCoords($coords_line, $terrain);
	}

	public function drawOcean($coord1, $coord2, $size=1) {

		if ($size>=3) {$radius = 2; $size=3;}
		if ($size<=2) {$radius = 2; $size=2;}

		$coords_line = $this->hexalib->noisyline_draw($coord1, $coord2, $size+1);

		foreach($coords_line as $index => $coord){
			if (!isset($prev_coord)) {$prev_coord = $coord; continue;}

			$sub_coords_line = $this->hexalib->line_draw($prev_coord, $coord);
			$this->setSpiralGroundsFromCoords($sub_coords_line, 1, $radius);

			$prev_coord = $coord;
		}

		return;
	}	
	public function drawMountain($coord1, $coord2, $size=1) {
		// Drawing hills
		$hillSize = $size +1;
		$coords_line = $this->hexalib->noisyline_draw($coord1, $coord2, $hillSize);
		foreach($coords_line as $index => $coord){
			if (!isset($prev_coord)) {$prev_coord = $coord; continue;}
			$sub_coords_line = $this->hexalib->noisyline_draw($prev_coord, $coord, 1);
			$this->setGroundsFromCoords($sub_coords_line, 3);
			$prev_coord = $coord;
		}
		if ($hillSize>1) {
			$coords_line = $this->hexalib->noisyline_draw($coord1, $coord2, 2);
			$this->setSpiralGroundsFromCoords($coords_line, 3, 2);
		}
		//Drawing Mountains 
		if ($size<1) {return;}
		$coords_line = $this->hexalib->noisyline_draw($coord1, $coord2, $size);
		foreach($coords_line as $index => $coord){
			if (!isset($prev_coord)) {$prev_coord = $coord; continue;}
			$sub_coords_line = $this->hexalib->noisyline_draw($prev_coord, $coord, 1);
			$this->setGroundsFromCoords($sub_coords_line, 4);
			$prev_coord = $coord;
		}
		if ($size>1) {
			$coords_line = $this->hexalib->noisyline_draw($coord1, $coord2, 1);
			$this->setSpiralGroundsFromCoords($coords_line, 4, 1);
		}
		return;
	}


	/* -------------------------------------------------------------------------
		Manage Boring zone :
			- Find places where there is only 1 type of lands in a radius of 
			5 hexs
			- Swap the central hex to something specific
			- Retry start -> ends, stops if needed.

	------------------------------------------------------------------------- */
	public function LessBoring($load = true, $save = true) {
		if ($load) {$this->loadWorld();}


		// if ($save) {$this->saveWorld();}
		return;
	}



	/* -------------------------------------------------------------------------
		Desert, Coast, and various changes

	------------------------------------------------------------------------- */
	public function DesertsCoasts($load = true, $save = true) {
		if ($load) {$this->loadWorld();}

		$desert_sources = [];
		
		$this->board->forEachGround(function ($currCol, $currRow) {
			$actualLand = $this->getGround($currCol, $currRow);

			if (isset($actualLand)) {
				$coord = $actualLand->coord('oddr');

				// Drawing coasts - +2 from lands
				if (count($this->board->getGroundNeighborOfTypes([1, 5], $coord->col, $coord->row, null, true))>0
					and ($actualLand->ground_type()==1 or $actualLand->ground_type()==5)) {
					$this->convertGroundSpiral($coord, 1, [1], 5);
				}

				// Drawing deserts / anything @$desert_factor from savanna borders
				$desert_factor = 3;
				if ($actualLand->ground_type()==10 or $actualLand->ground_type()==17) {
					// If nothing in a radius of X is something else than savana, can be a desert.
					if (count($this->board->getGroundNeighborOfTypes([10, 17, 7, 19, 4], $coord->col, $coord->row, null, true, $desert_factor))<1) {
						$this->convertGround($coord, [10], 7);	// 7
						$this->convertGround($coord, [17], 19);	// 19
					}
				}

			}
		});

		if ($save) {$this->saveWorld();}
		return;

	}

	/* -------------------------------------------------------------------------
		Wind & Humdity & Consequences !

		La ça va être super touchy, vu qu'on veux que des nuages qui se "chargent"
		en humidité, se "dechargent" sur les terre, en fonction de comment le 
		vent les a poussé. Une terre humide favorise les forets et les rivieres
		Mais de la pluie sur des rivieres, favorise les marecages !

		Hexalib Cardinal conv :
		Testé :
		0=E 1=NE 2=NW 3=W 4=SW 5=SE 

	------------------------------------------------------------------------- */
	public function windDirection($col, $row) {

		// Zone arctique :
		if ($row<=$this->climateLines['arctic_circle'] ) { return 4; }
		// Zone Temperee nord :
		$temperateZoneSize = ($this->climateLines['tropic_north'] - $this->climateLines['arctic_circle'])/3;
		if ($row>$this->climateLines['arctic_circle'] and $row<($this->climateLines['arctic_circle']+$temperateZoneSize)) { return 0; }
		if ($row>=($this->climateLines['arctic_circle']+$temperateZoneSize) and $row<($this->climateLines['arctic_circle']+$temperateZoneSize*2)) { return 1; }
		if ($row>=($this->climateLines['arctic_circle']+$temperateZoneSize*2) and $row<($this->climateLines['tropic_north'])) { return northDirection($row); }
		// Zone tropicale nord :
		$tropicalZoneSize = ($this->climateLines['equator'] - $this->climateLines['tropic_north'])/3;
		if ($row>=$this->climateLines['tropic_north'] and $row<($this->climateLines['tropic_north']+$tropicalZoneSize)) { return southDirection($row); }
		if ($row>=($this->climateLines['tropic_north']+$tropicalZoneSize) and $row<($this->climateLines['tropic_north']+$tropicalZoneSize*2)) { return 4; }
		if ($row>=($this->climateLines['tropic_north']+$tropicalZoneSize*2) and $row<=($this->climateLines['equator'])) { return 3; }
		// Zone tropicale sud :
		if ($row>=$this->climateLines['equator'] and $row<($this->climateLines['equator']+$tropicalZoneSize)) { return 3; }
		if ($row>=($this->climateLines['equator']+$tropicalZoneSize) and $row<($this->climateLines['equator']+$tropicalZoneSize*2)) { return 2; }
		if ($row>=($this->climateLines['equator']+$tropicalZoneSize*2) and $row<($this->climateLines['tropic_south'])) { return northDirection($row); }
		// Zone Temperee sud :
		if ($row>=$this->climateLines['tropic_south'] and $row<($this->climateLines['tropic_south']+$temperateZoneSize)) { return southDirection($row); }
		if ($row>=($this->climateLines['tropic_south']+$temperateZoneSize) and $row<($this->climateLines['tropic_south']+$temperateZoneSize*2)) { return 5; }
		if ($row>=($this->climateLines['tropic_south']+$temperateZoneSize*2) and $row<($this->climateLines['antarctic_circle'])) { return 0; }
		// Zone antarctique :
		if ($row>=$this->climateLines['antarctic_circle'] ) { return 2; }

		return;
	}


	public function Humidity($load = true, $save = true) {

		// allowLogs();

		if ($load) {$this->loadWorld();}
		

		// $base_water = round(MAX_COL/10);
		$base_water = 30;

		$humidity_score = [];
		$wetSpots = [];

		logMessage('Finding Wet Spot');

		// First : Finding "wetSpots", places where rains helps forests (plains
		// under oceans winds...)
		$this->board->forEachGround(function ($currCol, $currRow) use (&$humidity_score, &$wetSpots, &$base_water) {
		    // if Current place is an ocean ...
		    $actualLand = $this->getGround($currCol, $currRow);


		    if ($actualLand !== null && $actualLand->ground_type() == 1) { 

		        $direction = $this->windDirection($currCol, $currRow);
		        if ($direction !== null) {
		            // Find what type of neighbor...
					$neighbor = $this->board->getGroundNeighborOfTypes([8], $currCol, $currRow, $direction);
		            if ($neighbor !== null ) {
		            	$wetValue = $base_water;
		                $humidity_score[$neighbor->col . '-' . $neighbor->row] = $wetValue;
		                $wetSpots[] = $neighbor->coord('oddr');
		            }
		        }
		    }
		}, false);

		logMessage('Wet Spots Found '.count($wetSpots));

		$forests = [];

		foreach($wetSpots as $coord) {
			$water = $humidity_score[$coord->col.'-'.$coord->row];
			$result = true;
			$forests[] = $coord;
			$humidCol = $coord->col; $humidRow = $coord->row;
			while($result and $water>0) {
				$direction = $this->windDirection($humidCol,$humidRow);
				if ($direction!==null) {
					$oddrCoord = $this->hexalib->coord([$humidCol,$humidRow],'Oddr');
					$neighbourCoord = $this->hexalib->neighbour($oddrCoord, $direction);
					$neighbourCoord->col = magicCylinder($neighbourCoord->col);
					$targetLand = $this->getGround($neighbourCoord->col, $neighbourCoord->row);
					if ($targetLand!==null) {
						// Next hex in the loop : 
						$targetCoord = $this->hexalib->neighbour($oddrCoord, $direction);
						$targetCoord->col = magicCylinder($targetCoord->col);
						$humidCol = $targetCoord->col; $humidRow = $targetCoord->row;

						if ($coord->row<0 or $coord->row>MAX_ROW-1) {break;}
						
						$water = $water - 1;
						if ($targetLand->ground_type()==4) { $water = $water - 9; }
						if ($targetLand->ground_type()==3) { $water = $water - 4; }

						if ($targetLand->ground_type()==1) { $water = $water + 5; }
						if ($water>$base_water) {$water=$base_water;}

						if ($targetLand->ground_type()==8 or $targetLand->ground_type()==3) {
							$forests[] = $targetCoord;
						}

					} else {$result = false;}
				} else {$result = false;}

			}

			$humidity_score[$coord->col.'-'.$coord->row] = 0;
		}
		

		$this->drawForests($forests, false, 30);

		$seeds = $forests;
		$rate = 30;
		while(count($seeds)>0) {
			$seeds = $this->drawForests($seeds, true, $rate);	
			$rate = $rate - 10;
		}

		if ($save) {$this->saveWorld();}

		// disableLogs();

		return;
	}

	public function drawForests($forests, $reproduce = true, $rate_pct = 30) {
		$seeds = [];
		foreach ($forests as $forest_spot) {
			$this->convertGround($forest_spot, 8, 2);
			$this->convertGround($forest_spot, 3, 15);
			$possible_seeds = $this->hexalib->all_neighbours($forest_spot);
			foreach($possible_seeds as $each_possibility) {
				$each_possibility->col = magicCylinder($each_possibility->col);
				if (rand(0,100)<=$rate_pct) { $seeds[] = $each_possibility; }
			}
		}
		return $seeds;
	}



	/* -------------------------------------------------------------------------
		Rivers !

	------------------------------------------------------------------------- */
	public function Rivers($load = true, $save = true) {
		if ($load) {$this->loadWorld();}
		// Finding sources 
		$riverables = [2, 8, 9, 10, 12];
		$sources = [];
		$existingRivers = [];
		$this->board->forEachGround(function ($currCol, $currRow) use (&$sources, $riverables) {
			$old_source_count = count($sources);
			$actualLand = $this->getGround($currCol, $currRow);
			if (isset($actualLand)) {
				// Depuis à côté d'une montagne, ou d'une colline humide
				if ($actualLand->ground_type()==4 or $actualLand->ground_type()==15 or $actualLand->ground_type()==16) {
					$sources  = array_merge($sources, $this->board->getGroundNeighborOfTypes($riverables, $currCol, $currRow));
				}
				if ($actualLand->ground_type()==13) {
					$existingRivers[] = $actualLand;
				}
			}
		});

		// Shuffling allow a better "replayability"
		shuffle($sources);

		// Filtering too close sources ! 
		$valid_sources = [];
		foreach ($sources as $source) {
	        $too_close = false;

	        // On compare cette source à toutes les autres sources déjà validées
	        foreach ($valid_sources as $valid_source) {
	            $distance = $this->hexalib->distance($valid_source->coord('oddr'), $source->coord('oddr'));
	            if ($distance <= 25) {
	                $too_close = true; // Si la distance est trop courte, on marque cette source comme trop proche
	                break; // On arrête la comparaison dès qu'on trouve une source trop proche
	            }
	        }

	        // Si on a rien trouvé, on compare néanmoins au rivieres qui existe
	        if (!$too_close) {
		        // On compare cette source à toutes les rivieres qui existent
		        foreach ($existingRivers as $river) {
		            $distance = $this->hexalib->distance($river->coord('oddr'), $source->coord('oddr'));
		            if ($distance <= 25) {
		                $too_close = true; // Si la distance est trop courte, on marque cette source comme trop proche
		                break; // On arrête la comparaison dès qu'on trouve une source trop proche
		            }
		        }
		    }

	        // Si la source n'est pas trop proche des autres, on l'ajoute aux sources valides
	        if (!$too_close) { $valid_sources[] = $source; }
    	}

    	// echo('Total Sources ok = '.count($valid_sources).' / '.count($sources));
    	// echo(' --------------------------------------------------- ');
    	$valid_start_num = count($valid_sources);

		// River Trace
		$tries = 30;
		while($tries>0) {
			// On essaie de tracre le tout 3x car parfois une riviere permet a une autre de deboucher
			foreach($valid_sources as $key => $ground) {
				$done = $this->drawRiver($ground->coord('oddr')); 
				if ($done) {unset($valid_sources[$key]);}
			}
		$tries --;	
		}

    	// echo('Total Sources impossible à tracer = '.count($valid_sources));
    	// echo(' --------------------------------------------------- ');

		// Loop to add a lot of new rivers
    	if (count($valid_sources)*1.5 < $valid_start_num) { $this->Rivers(false, false); }

    	if ($save) {$this->saveWorld();}
    	return;
	}

	// 0=E 1=NE 2=NW 3=W 4=SW 5=SE 
	public function drawRiver($coord) {
		$finished = false;
		$finalRiverPath = [];
		$riverables = [2, 8, 9, 10, 12];
		// On simule une origine :
		$possible_prev = $this->board->getGroundNeighborOfTypes([4, 15, 16], $coord->col, $coord->row);
		$old_coord = $possible_prev[array_rand($possible_prev)]->coord('oddr');
		// La boucle !
		while(!$finished) {
			$finalRiverPath[] = $coord;
			// Ocean/Riviere touche = fini !
			if ($this->board->getGroundNeighborOfTypes([1, 13], $coord->col, $coord->row)) { $finished = true; break;}

			$originDirection = $this->hexalib->cube_direction($coord, $old_coord);
			$candidates = [];
    		for ($i = 2; $i <= 4; $i++) {
        		$nextDirection = ($originDirection + $i) % 6;
        		$choosen = $this->board->getGroundNeighborOfTypes($riverables, $coord->col, $coord->row, $nextDirection);
        		if (isset($choosen)) {
        			// Si le candidat a 2+ voisin riviere on l'exclus :
        			$riversNeighbors = $this->board->getGroundNeighborOfTypes([13], $choosen->col, $choosen->row);
        			if (count($riversNeighbors)<=1) {
						// Le candidat n'a pas le droit d'être un voisin immediat de ce que l'on a tracé, à l'exception des 2 derniers 
		        		$testPath = array_slice($finalRiverPath, 0, -2);
		        		if (!$this->isCoordNeighborOfArray($choosen->coord('oddr'), $testPath)) {
		        			$candidates[] = $choosen;
		        		}
					}
        		}
    		}
						
			if (empty($candidates)) { $finished = false; break;}

			$old_coord = $coord;
			$coord = $candidates[array_rand($candidates)]->coord('oddr');
		}

		$all_path_neighbours = [];

		if ($finished and count($finalRiverPath)>15) {
			
			foreach($finalRiverPath as $eachcoord) {
				// La riviere
				$this->setGroundFromCoord($eachcoord, 13); 
				// Les voisins :
				$neighbors = $this->board->getGroundNeighborOfTypes([10, 9, 2, 8], $eachcoord->col, $eachcoord->row);

				// On ne note les voisins de notre chemin de revieire qu'une fois unique !
				foreach($neighbors as $each_ground) {
					$key = $each_ground->col.'-'.$each_ground->row;
					$all_path_neighbours[$key] = $each_ground;
				}
			}

			foreach($all_path_neighbours as $each_ground) {
				// Plaines -> Marecages 1/6
				if (rand(1,6)<=1) {  $this->convertGround($each_ground->coord('oddr'), 8, 11); }
				// Les savanne deviennent plaines 1/2
				if (rand(1,6)<=3) { $this->convertGround($each_ground->coord('oddr'), 10, 8); }
				// Les foret & jungle mutent à 1/3 en marecages !
				if (rand(1,6)<=2) {
					$this->convertGround($each_ground->coord('oddr'), 9, 11);
					$this->convertGround($each_ground->coord('oddr'), 2, 11);						
				}
			}

			return true;
		}
		return false;
	}

	function isCoordInArray($coord, $array) {
	    foreach ($array as $item) { if ($coord->col == $item->col and $coord->row == $item->row) { return true; } }
	    return false;
	}

	function isCoordNeighborOfArray($coord, $array) {
	    foreach ($array as $item) {
	    	if (!empty($this->hexalib->common_neighbours($item, $coord))) { return true; } 
	    }
	    return false;
	}


	/* -------------------------------------------------------------------------
		Hot & Cold zone

	------------------------------------------------------------------------- */

	public function temperature($row) {
		// HOT PLACES !
		if ($row>$this->climateLines['tropic_north'] and $row<$this->climateLines['tropic_south']) { return 'hot';  }
		if ($row==$this->climateLines['tropic_north'] or $row==$this->climateLines['tropic_south']) { 
			if (rand(0,1)==1) { return 'hot'; }
			return 'temperate';
		}

		// Frozen Places (must be before cold as we have generic '<' comparaison)
		if ($row<3 or $row>(MAX_ROW-4)) { return 'frozen'; }
		if ($row==3 or $row==(MAX_ROW-4)) {
			if (rand(0,1)==1) { return 'frozen'; }
			return 'cold';
		}

		// Cold places 
		if ($row>$this->climateLines['antarctic_circle'] or $row<$this->climateLines['arctic_circle']) { return 'cold';  }
		if ($row==$this->climateLines['antarctic_circle'] or $row==$this->climateLines['arctic_circle']) { 
			if (rand(0,1)==1) { return 'cold'; }
			return 'temperate';
		}

		// Anyway, temperate...
		return 'temperate';
	}

	public function LatitudeClimate($load = true, $save = true) {

		if ($load) {$this->loadWorld();}

		// Adapting plains (and oceans) to current climate !
		$this->board->forEachGround(function ($currCol, $currRow) {
		    $actualLand = $this->getGround($currCol, $currRow);
		    if ($actualLand !== null) { 
		    	$temperature = $this->temperature($actualLand->row);
		    	if ($temperature=='hot') {
		    		$this->convertGround($actualLand->coord('oddr'), 8, 10);
		    		$this->convertGround($actualLand->coord('oddr'), 2, 9);
		    		$this->convertGround($actualLand->coord('oddr'), 3, 17);
		    		$this->convertGround($actualLand->coord('oddr'), 15, 16);
		    	}
		    	if ($temperature=='temperate') {
		    		// Nothing...
		    	}
		    	if ($temperature=='cold') {
		    		$this->convertGround($actualLand->coord('oddr'), 8, 12);
		    		$this->convertGround($actualLand->coord('oddr'), 3, 14);
		    		if (rand(0,1)==1) {
		    			$this->convertGround($actualLand->coord('oddr'), 2, 12);
		    			$this->convertGround($actualLand->coord('oddr'), 15, 14);
		    		}
		    	}
				if ($temperature=='frozen') {
					$this->convertGround($actualLand->coord('oddr'), 8, 6);
					$this->convertGround($actualLand->coord('oddr'), 12, 6);
					$this->convertGround($actualLand->coord('oddr'), 3, 18);
					$this->convertGround($actualLand->coord('oddr'), 2, 12);
					if (rand(0,1)==1) {$this->convertGround($actualLand->coord('oddr'), 1, 6);}
				}
		    }
		}, false);

		// Les bords Arides sont modifes en fonction de l'humidite
		$this->board->forEachGround(function ($currCol, $currRow) {
		    $actualLand = $this->getGround($currCol, $currRow);

		    if ($actualLand !== null) {
		    	// If is a Savanna
		    	if ($actualLand->ground_type() == 10) {
			        // Check Humdity neigbors
			        $jungleNeighbors = $this->board->getGroundNeighborOfTypes([9,16], $currCol, $currRow);
			        if (!empty($jungleNeighbors)) { 
			            $this->setGround($currCol, $currRow, 8);  // Convert savanna to fertile plain

			            // Finding savana close for random variations 
			            $savanaNeighbors = $this->board->getGroundNeighborOfTypes([10], $currCol, $currRow);
			            if (!empty($savanaNeighbors)) {
			                $neighbour_to_convert = $savanaNeighbors[array_rand($savanaNeighbors)]; // Récupérer un voisin au hasard
			                $this->convertGround($neighbour_to_convert->coord('oddr'), 10, 8);  // Conversion du voisin
			            }
			        }
		    	}

		    	//A forest can't be close to banquise...
		    	if ($actualLand->ground_type() == 15 or $actualLand->ground_type() == 2) {
					// Check Banquise neigbors
				    $banquiseNeighbors = $this->board->getGroundNeighborOfTypes([6,18], $currCol, $currRow);
				    if (!empty($banquiseNeighbors)) { 
				    	// Convert forest to toundra
				    	$this->convertGround($actualLand->coord('oddr'), 15, 14);
				    	$this->convertGround($actualLand->coord('oddr'), 2, 12);
				    }
		    	}
		    	
		    }
		}, false);

		if ($save) {$this->saveWorld();}
		return;
	}



	/* -------------------------------------------------------------------------
		Tectonic plates are recognizable as  oceans deeps, or mountains chains
		- moutains chains are beetween lands & lands or lands & oceans
		- There are various spots randopmly on the map
		- then spots are linked by lines : mountains or oceans or moutain&oceans

	------------------------------------------------------------------------- */
	public function addSommet($sommets, $key, $coord) {
		$sommets[$key]=[];
		$sommets[$key]['coord']=$coord;
		$sommets[$key]['key']=$key;
		$sommets[$key]['pos']='';
		if ($coord->row==0) {$sommets[$key]['pos']='north';}
		if ($coord->row>=MAX_ROW-1) {$sommets[$key]['pos']='south';}
		return $sommets;
	}

	public function addJunction(&$junctions, $sommet1key, $sommet2key) {
		if ($sommet1key==$sommet2key) {return;}
		$key1 = $sommet1key.'>'.$sommet2key;
		$key2 = $sommet2key.'>'.$sommet1key;
		if (isset($junctions[$key1])) {return $key1;}
		if (isset($junctions[$key2])) {return $key2;}
		$junctions[$key1] = [];
	    $junctions[$key1]['s1'] = $sommet1key;
	    $junctions[$key1]['s2'] = $sommet2key;
		return $key1;
	}
	
	public function deleteJunction(&$junctions, &$sommets, $key) {
		if (!isset($junctions[$key])) {return false;}
		$sommet1 = $junctions[$key]['s1'];
		$sommet2 = $junctions[$key]['s2'];
		for($i=1; $i<=8; $i++){
			if (isset($sommets[$sommet1]['j'.$i]) and $sommets[$sommet1]['j'.$i]==$key) 
				{ unset($sommets[$sommet1]['j'.$i]); }
			if (isset($sommets[$sommet2]['j'.$i]) and $sommets[$sommet2]['j'.$i]==$key) 
				{ unset($sommets[$sommet2]['j'.$i]); }			
		}
		unset($junctions[$key]);
		return true;
	}

	public function createFail(&$sommets, &$junctions, $first_key, $junc_origin) {

		// Initialize the first node (vertex) and the previous junction origin
		$last_sommet = $sommets[$first_key];
		$prev_origin = $junc_origin;
		$notend=true;
		$failSpots = [];
		$first_loop = true;

    	// Directions map: each junction has its possible choices and reverse direction
		// 1 2 3
		// 4 + 5
		// 6 7 8
	    $directions = [
	        'j1' => ['choices' => ['j5', 'j7', 'j8'], 'reverse' => 'j8'],
	        'j2' => ['choices' => ['j6', 'j7', 'j8'], 'reverse' => 'j7'],
	        'j3' => ['choices' => ['j6', 'j7', 'j4'], 'reverse' => 'j6'],
	        'j4' => ['choices' => ['j3', 'j5', 'j8'], 'reverse' => 'j5'],
	        'j5' => ['choices' => ['j1', 'j4', 'j6'], 'reverse' => 'j4'],
	        'j6' => ['choices' => ['j2', 'j3', 'j5'], 'reverse' => 'j3'],
	        'j7' => ['choices' => ['j1', 'j2', 'j3'], 'reverse' => 'j2'],
	        'j8' => ['choices' => ['j1', 'j2', 'j4'], 'reverse' => 'j1'],
	    ];

		while ($notend) {

			$failSpots[] = $last_sommet['coord'];

			// reaching north or south, break the loop :
			if ($last_sommet['pos']!='' and !$first_loop) { break; }

        	// Get the possible choices for the next junction based on the previous origin
			$choices = $directions[$prev_origin]['choices'];
			// Filter valid choices that exist in the current node
			$real_choices = array_filter($choices, function ($choice) use ($last_sommet) { return isset($last_sommet[$choice]); });

			if (empty($real_choices)) {$notend = false; break;}

			// If there's exactly one valid choice, just move to that node
			if (count($real_choices)==1) {
				$notend = false; 
            	// The loop doesn't break, we just wait to reach the spot without crossing it
            	// to avoid creating a 'cross' in the mapap
			}

			$dest = $real_choices[array_rand($real_choices)];
			$junckey = $last_sommet[$dest];
			// Determine the reverse direction for the next move
			$prev_origin = $directions[$dest]['reverse'];
	    	$lastkey = $last_sommet['key'];
        	// Move to the next node based on the current key
        	// If the current node matches s1, move to s2, otherwise to s1
        	$s1 = $junctions[$junckey]['s1'];
        	$s2 = $junctions[$junckey]['s2'];
			$last_sommet = ($lastkey === $s1) ? $sommets[$s2] : $sommets[$s1];
			
        	// Delete the junction from the list after passing through it
	    	$this->deleteJunction($junctions, $sommets, $junckey);
	    	$first_loop = false;
		}
		return $failSpots;
	}

	public function createTectonicPlates($load = true, $save = true, $size = null) {

		// allowLogs();

		if ($load) {$this->loadWorld();}

		

		if ($size==null) { $size = round(MAX_ROW/10); }

		$sommets = [];
		$junctions = [];

    	// Pré-calculer les limites de la carte une seule fois
    	$max_row_limit = MAX_ROW - 1;
    	$max_col_limit = MAX_COL - 1;

		// Decouper ma map :
		for ($currRow = 0; $currRow < MAX_ROW+$size; $currRow += $size) {
		    for ($currCol = 0; $currCol < MAX_COL+$size; $currCol += $size) {
		        // On s'assure que les coordonnées finales ne sortent pas de la carte
		        $x = $currCol;
		        $y = min($currRow, MAX_ROW - 1);
		        // Ajouter la coordonnée du coin supérieur gauche
		        $coord = $this->hexalib->coord([$x, $y], 'Oddr');
		        $sommets = $this->addSommet($sommets, $x.'-'.$y, $coord);
		    }
		}
		

		logMessage('Map decoupee');

		// Determiner les lignes existantes :
	    foreach ($sommets as $key => $sommet) {
	    	$currCoord = $sommet['coord'];
			$col = $currCoord->col;
	        $row = $currCoord->row;

        	// Calculer les clés de voisinage une seule fois
        	$rightcol = $col + $size;
        	$bottomrow = ($row + $size >= MAX_ROW) ? $max_row_limit : $row + $size;
        	$right_key = "$rightcol-$row";
        	$bottom_key = "$col-$bottomrow";

			// 1 	2 	3
			// 4	+	5
			// 6	7	8

	        // Ligne horizontale (droite)
	        if (isset($sommets[$right_key])) {
	        	$junckey = $this->addJunction($junctions, $key, $right_key); 
	        	if (isset($junckey)) {
	        		$sommets[$key]['j5'] = $junckey;
	        		$sommets[$right_key]['j4'] = $junckey;
	        	}
	        }

	        // Ligne verticale (en bas)
	        if (isset($sommets[$bottom_key])) {
	        	$junckey = $this->addJunction($junctions, $key, $bottom_key); 
	        	if (isset($junckey)) {
	        		$sommets[$key]['j7'] = $junckey;
	        		$sommets[$bottom_key]['j2'] = $junckey;
	        	}
	        }

	        // Pour la beauté de la map, on n'autorise qu'une des deux diagonale:
	        if (rand(0,1)==0) {
				// Diagonale descendante droite
				$diag_right_key = $rightcol . '-' . $bottomrow;
			    if (isset($sommets[$diag_right_key]) and $bottomrow>$row) {
		        	$junckey = $this->addJunction($junctions, $key, $diag_right_key); 
		        	if (isset($junckey)) {
		        		$sommets[$key]['j8'] = $junckey;
		        		$sommets[$diag_right_key]['j1'] = $junckey;
		        	}
		    	}
	        } else {
			    // Diagonale descendante gauche
			    if (isset($sommets[$right_key]) and isset($sommets[$bottom_key])) {
		        	$junckey = $this->addJunction($junctions, $right_key, $bottom_key); 
		        	if (isset($junckey)) {
		        		$sommets[$bottom_key]['j3'] = $junckey;
		        		$sommets[$right_key]['j6'] = $junckey;
		        	}
			    }
	        }
	    }

		// Disperser un peu les sommets !
		$randfact = round($size/3);
		foreach ($sommets as $sommet) {
			$sommet['coord']->col = max(0, min($sommet['coord']->col + mt_rand(-$randfact,$randfact), MAX_COL-1));
			if ($sommet['coord']->row!=0 and $sommet['coord']->row!=MAX_ROW-1) {
	    		$sommet['coord']->row = max(0, min($sommet['coord']->row + mt_rand(-$randfact,$randfact), MAX_ROW-1));				
			}
		}

	    $genre_lines = [ 'mountains' => [], 'oceans' => [], 'mountOceans' => [], 'oceansMount' => [] ];
	    $totals = [ 'mountains' => 0, 'mountOceans' => 0, 'oceansMount' => 0, 'oceans' => 0 ];
		$finished = false;

		while(!$finished) {
			$key = array_rand($sommets);
			$result = $this->createFail($sommets, $junctions, $key, 'j2');
			$genre = $this->predictibleAttribution();
			$totals[$genre] += count($result);
			$genre_lines[$genre][] = $result;
        	if (($totals['mountOceans']+$totals['oceansMount'])>40 && $totals['oceans'] > 40 && $totals['mountains'] > 50)
				{$finished = true;}
		}

		$test_mode = false;

		logMessage('Go draw lines !');

		// ------------------------------------------------------------
		//	Mountains Chain - simple, classical
		// ------------------------------------------------------------
		logMessage('Mountains.');
		$this->lineDrawer($test_mode, 4, $genre_lines['mountains'], 'drawMountain', 1, 0);
		
		// ------------------------------------------------------------
		// LeftHand  & RightHand Mountains in border of Oceans
		// ------------------------------------------------------------		
		logMessage('MountainsBorders 1.');
		$this->lineDrawer($test_mode, 7, $genre_lines['mountOceans'], 'drawMountain', 1, 6);
		logMessage('MountainsBorders 2.');
		$this->lineDrawer($test_mode, 7, $genre_lines['oceansMount'], 'drawMountain', 1, -6);
		logMessage('MountainsBorders 3.');
		$this->lineDrawer($test_mode, 7, $genre_lines['mountOceans'], 'drawOcean', 2, 0);
		logMessage('MountainsBorders 4.');
		$this->lineDrawer($test_mode, 7, $genre_lines['oceansMount'], 'drawOcean', 2, 0);
	    
	    // ------------------------------------------------------------
	    //	OCEANS !!! AT LEAST :) 
	    // ------------------------------------------------------------

		logMessage('Oceans.');

	    $this->lineDrawer($test_mode, 1, $genre_lines['oceans'], 'drawOcean', 2, 0);

	    // --

	    logMessage('Saving...');

	    if ($save) {$this->saveWorld();}

	    // disableLogs();

		return;
	}

	public function predictibleAttribution() {
	    if (!isset($this->attribution) || $this->attribution > 4) { $this->attribution = 0; }
	    $genres = ['mountains', 'oceans', 'mountOceans', 'oceansMount', 'oceans'];
	    $genre = $genres[$this->attribution];
	    $this->attribution++;
	    return $genre;
	}


}

?>