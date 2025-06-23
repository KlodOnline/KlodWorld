<?php
/* =============================================================================
    BOARD
    	Permet de recueillir les élements du jeu (la "board"). Ils sont
    	transformés en une collection indexée, que l'on peut ensuite parcourir 
    	ou filtrer rapidemment.


	(Ajout/Modification/Destruction)
		Cf. saveCollection()

	!!! BOARD ne doit JAMAIS LOADER par lui-même !!!

IL EST TEMPS
	De refactoriser
	Et de preparer les fonction
			GET/SET Des collections :3
			( par Coordonnée, ou par Index )
		Et de preparer les gestions des doublons etc.
		Soit en laissant la main ou pas à celui qui demande
			(penser "oh regarde il y a vait deux pions en fait !")

    Fonts :
    https://patorjk.com/software/taag/#p=display&f=ANSI%20Shadow&t=TRASH%0A
============================================================================= */
class Board {
	// Collections :
	private $Player;
	private $InventoryCell;
	private $Order;
	private $Inventory;
	// Locatables Collections !
	private $City;
	private $Ground;
	private $Unit;
	// Additional Indexation :
	private $locationsIndex = [];
	private $next_id = -1;
	// Others :
    private $hexalib;
    private $cache = []; // Litterally last thing read in BDD.

	/***************************************************************************
	    Constructor
	***************************************************************************/
	public function __construct($refresh_with_cache = false) {
		
		$this->hexalib = new Hexalib();
		$this->cache = [];	

    }

/*========================================================================================
 ██████╗ ██████╗ ██╗     ██╗     ███████╗ ██████╗████████╗██╗ ██████╗ ███╗   ██╗███████╗
██╔════╝██╔═══██╗██║     ██║     ██╔════╝██╔════╝╚══██╔══╝██║██╔═══██╗████╗  ██║██╔════╝
██║     ██║   ██║██║     ██║     █████╗  ██║        ██║   ██║██║   ██║██╔██╗ ██║███████╗
██║     ██║   ██║██║     ██║     ██╔══╝  ██║        ██║   ██║██║   ██║██║╚██╗██║╚════██║
╚██████╗╚██████╔╝███████╗███████╗███████╗╚██████╗   ██║   ██║╚██████╔╝██║ ╚████║███████║
 ╚═════╝ ╚═════╝ ╚══════╝╚══════╝╚══════╝ ╚═════╝   ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝╚══════╝
========================================================================================*/
	/***************************************************************************
	    Collection Management
	***************************************************************************/
	// cleaning
	public function cleanCollectionByClass($class) {
	    $collectionName = $class;
	    if (!property_exists($this, $collectionName)) {
	        logMessage("Collection not found for class: $class");
	        return;
	    }
	    $this->{$collectionName} = null;
	    logMessage("Collection for class $class has been cleaned.");
	}
	// Collections are null at first.
	private function &getCollectionByClass($class) {
		$collectionName = $class;
		if (!property_exists($this, $collectionName)) {
			logMessage("Collection not found for class: $class");
		}
		return $this->{$collectionName};
	}
	// Check what is in collections
	public function statusOfCollections() {
		global $LOG_LEVEL;
		if ($LOG_LEVEL===0) {return;}
		$col_names = ['Player', 'InventoryCell', 'Order', 'Inventory', 'City', 'Ground', 'Unit']; 
		foreach($col_names as $collectionName) {
			if ($this->$collectionName!==null) {
				logMessage('Collection Status : '.$collectionName.' => '.sizeof($this->$collectionName, 1).' elements.'); 
			} else {
				logMessage('Collection '.$collectionName.' uninitialized.'); 
			}
		}
	}
	// Convert (all)/(part of) a collection to json data object list
	public function collection_to_json($objectClass, $ids = null) {
		$collection = &$this->getCollectionByClass($objectClass);
		$filteredCollection = array_filter($collection, function($object) { return $object !== null; });
		// -> it's a LIST ! So it's beetween '[]' for javascript !!!
		$jsonText='[';
		$jsonText .= implode(',', array_map(function($object) {
		    // return $object->jsonData();
		    return $object->exposedData();
		}, $filteredCollection));
        $jsonText .= ']';
        return $jsonText;
	}

	public function fullCollectionLogTxt($collection) {
		$txt = "";
		foreach ($collection as $key => $object) {
			if ($object===null) {
				$txt .= 'K = ['.$key.'] == NULL ';
			} else {
				$class = get_class($object);
				$txt .= 'Ck/Sk/id = ['.$key.'/'
					.$this->collectionKey($object).'/'
					.$object->key().'] ';
			}
		}
		return $txt;
	}

	/***************************************************************************
	    Object In Collection Manageement !
	***************************************************************************/
	// Fresh object to digest in collections !
	private function digestObjects($objects) {
		$classes = [] ;
	    foreach ($objects as $object) {
	    	$classes[] = get_class($object);
	        $this->updateCollection($object);
	        $this->addToCache($object);
	    }
	    $classes = array_unique($classes);
	    logMessage('Objects loaded : ' . count($objects).' as Classes '.implode(' ', $classes));
	}
    // updateCollection overwrite any object with same ID
    // Used safely whenever you KNOW the ID is the one.
    public function updateCollection($object) {
    	$class = get_class($object);
        $collection = &$this->getCollectionByClass($class);
        $collection[$this->collectionKey($object)] = $object;
		if ($object->isLocatable()) { $this->addToLocationsIndex($object); }
    }
    // Delete : Uses if an object must be deleted from BDD next save
    public function deleteFromCollection($object) {
    	if ($object===null) {return;}
    	$class = get_class($object);
    	if ($object->isLocatable()) {$this->deleteFromLocationsIndex($object);}
        $collection = &$this->getCollectionByClass($class);
        $collection[$this->collectionKey($object)] = null;
    }
    // Erase : Uses if an object don't even exist in BDD in first place.
    public function eraseFromCollection($object) {
    	if ($object===null) {return;}
    	$class = get_class($object);
    	if ($object->isLocatable()) {$this->deleteFromLocationsIndex($object);}
        $collection = &$this->getCollectionByClass($class);
        unset($collection[$this->collectionKey($object)]);
    }
	public function collectionKey($object) {
		$key = [];
		$key[] = $object->key() ;
		return implode('_', $key);
	}
	public function changeId($object, $newId) {
		logMessage('ID Correction '.$object->key().' => '.$newId);
		if ($object->isLocatable()) {$this->deleteFromLocationsIndex($object);}
		$this->eraseFromCollection($object);
		$object->changeKeysValues([$newId]);
		$this->updateCollection($object);
	}
	private function addToLocationsIndex($object) {
		$key = $object->col . '-' . $object->row;
		if (!isset($this->locationsIndex[$key])) { 
			$this->locationsIndex[$key] = [];
		} 
		if (!in_array($this->collectionKey($object), $this->locationsIndex[$key])) {
			$this->locationsIndex[$key][] = $this->collectionKey($object);
		}
	}
	private function deleteFromLocationsIndex($object) {
	    $key = $object->col . '-' . $object->row;
	    if (isset($this->locationsIndex[$key])) {
	        $collectionKey = $this->collectionKey($object);
	        $index = array_search($collectionKey, $this->locationsIndex[$key]);
	        if ($index !== false) {
	            unset($this->locationsIndex[$key][$index]);
	            // Réindexer le tableau si nécessaire
	            $this->locationsIndex[$key] = array_values($this->locationsIndex[$key]);
	        }
	        // Si le tableau devient vide, on peut supprimer la clé de l'index
	        if (empty($this->locationsIndex[$key])) { unset($this->locationsIndex[$key]); }
	    }
	}
	/***************************************************************************
	    Finding in Collections ...
	***************************************************************************/

	public function forEachInCollection($objectClass, callable $callback) {
		$collection = &$this->getCollectionByClass($objectClass);
		if ($collection === null) {return;}
		foreach($collection as $object) {	
			if ($object===null) {continue;}
			$callback($object);
		}
	}

	public function getObjectsByCoords($class, $col, $row) {
	    // Récupération de la collection
	    $collection = &$this->getCollectionByClass($class);
	    if ($collection === null || $collection === []) {
	        return [];
	    }
	    $matchingIndexes = [];
	    $keyIndex = $col . '-' . $row;
	    if (isset($this->locationsIndex[$keyIndex])) {
	        foreach ($this->locationsIndex[$keyIndex] as $objIndex) {
	            $matchingIndexes[] = $objIndex;
	        }
	    }
	    $matchingObjects = [];
	    foreach ($matchingIndexes as $index) {
	    	if (isset($collection[$index])) {
	        	$matchingObjects[] = $collection[$index];
	        } else {
	        	logMessage('Something should have been here ?!');
	        }
	    }
	    return $matchingObjects;
	}

	public function getObjectsByProperties($class, $properties, $values) {
		$collection = &$this->getCollectionByClass($class);
	    // Cas où la collection est null
	    if ($collection === null) {
	        logMessage("No collection to filter for class: $class (null collection)");
	        return [];  // Retourner un tableau vide si la collection est null
	    }
	    // Cas où la collection est vide
	    if ($collection === []) {
	        logMessage("No objects to filter for class: $class (empty collection)");
	        return [];  // Retourner un tableau vide si la collection est vide
	    }
	    $filteredObjects = [];
	    foreach ($collection as $object) {
	       	// check if object get ALL properties we want @ value we want.
	    	foreach($properties as $key => $property) {
		        if (isset($object->$property) && $object->$property === $values[$key]) {
		            $filteredObjects[] = $object;
		        }
	    	}
	    }

	    // Retourner les objets filtrés ou un tableau vide si aucun objet trouvé
	    return $filteredObjects;

	}

	// Find all Objects wich property is '===' to value.
	public function getObjectsByProperty($class, $property, $value) {
	    $collection = &$this->getCollectionByClass($class);
	    
	    // Cas où la collection est null
	    if ($collection === null) {
	        logMessage("No collection to filter for class: $class (null collection)");
	        return [];  // Retourner un tableau vide si la collection est null
	    }

	    // Cas où la collection est vide
	    if ($collection === []) {
	        logMessage("No objects to filter for class: $class (empty collection)");
	        return [];  // Retourner un tableau vide si la collection est vide
	    }

	    $filteredObjects = [];
	    foreach ($collection as $object) {
	        // Vérifier si l'objet possède la propriété et si sa valeur correspond
	        if (isset($object->$property) && $object->$property === $value) {
	            $filteredObjects[] = $object;
	        }
	    }

	    // Retourner les objets filtrés ou un tableau vide si aucun objet trouvé
	    return $filteredObjects;
	}

	// Find an object by is key - wich can be a unique or an array to implode.
	public function getObjectByKey($class, $key = []) {
		$collection = &$this->getCollectionByClass($class);
		$searchKey = implode('_', $key);
		if (isset($collection[$searchKey])) { return $collection[$searchKey]; }
		logMessage('Object $collection["'.$class.'"]["'.$searchKey.'"] not found');
		return;
	}

/*========================================================================================
		███████╗ █████╗ ██╗   ██╗███████╗    ██╗██╗      ██████╗  █████╗ ██████╗ 
		██╔════╝██╔══██╗██║   ██║██╔════╝   ██╔╝██║     ██╔═══██╗██╔══██╗██╔══██╗
		███████╗███████║██║   ██║█████╗    ██╔╝ ██║     ██║   ██║███████║██║  ██║
		╚════██║██╔══██║╚██╗ ██╔╝██╔══╝   ██╔╝  ██║     ██║   ██║██╔══██║██║  ██║
		███████║██║  ██║ ╚████╔╝ ███████╗██╔╝   ███████╗╚██████╔╝██║  ██║██████╔╝
		╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚══════╝╚═╝    ╚══════╝ ╚═════╝ ╚═╝  ╚═╝╚═════╝ 

	If something is slow, don't try to build something fancy in PHP.
	RELY on the BDD Engine ! (InnoDB)

	Use 'EXPLAIN' & check 'INDEXES'. Ask ChatGPT if you don't know.

========================================================================================*/

    public function saveAll() {
        logMessage("Saving Board...");

        // According to dependencies order !!!!
        $this->statusOfCache();
        $this->statusOfCollections();
        // --
        $this->saveCollection('Player');
        // --
        $this->saveCollection('City');
        $this->saveCollection('Ground');
        $this->saveCollection('Unit');
        // --
        $this->saveCollection('Order');
        // --
        $this->saveCollection('InventoryCell');
        $this->saveCollection('Inventory');
        
        logMessage("Board saved!");
    }

	// Not sure if usefull : 
    public function loadOnePlayerObjects($id) {
    	// Pas compris (oublié)
    	$cache_value = $this->statusOfCache();
    	if ($cache_value>0) {
    		logMessage("Somebody try to load playerData I should already have, abort...");
    		return;
    	}
    	// Loading Player and Cities & All player-relatables.
    	$this->loadByIds('Player', [$id]);
        if ($this->playerExists($id)) {
	        $this->loadByColumnsAndValues('Locatable', ['owner_id'], [[$id]]);
	        // Load all lands "visibles" :
	        // >>> THIS HAVE TO BE DONE BEFORE I CAN FINISH ORDERSYSTEM !!!
	        // >>> Should rely on DISCOVER setting !
	        
	        // $this->loadGrounds();

	        return true;
        }
        return false;
    }

    public function loadPlayerCities($id) {

    }

    public function loadAll() {
    	// This is only used by turn_manager for now, and I guess
    	// We have to forget what we now before
    	// because its changing in between eachload and can coorupt collections
    	// 
        logMessage("Loading Board...");
        $this->loadByIds('Player', null);
		$this->loadByIds('Locatable', null);
    	$this->loadByIds('InventoryCell', null);
    	$this->loadByIds('Order', null);
        logMessage("Board loaded!");
    }

    public function loadGrounds() {
        $this->loadByColumnsAndValues('Ground', ['kind'], [["'Ground'"]]);
    }

    public function loadOrder($actor_id = null) {
    	if ($actor_id===null) {return;}
        $this->loadByIds('Order', [$actor_id]);
    }

    public function loadByColRow($objectClass, $col, $row) {
		if (!property_exists($objectClass, 'col') or !property_exists($objectClass, 'row')) {
			logMessage('Error: Trying to Scope an object with no coords ... ');
			return;
		}
		$manager = new BDDObjectManager($objectClass);

		return $this->loadByColumnsAndValues($objectClass, ['col', 'row'], [[$col,$row]]);
    }

	public function loadByScope($objectClass, $col1, $row1, $col2, $row2) {
		if (!property_exists($objectClass, 'col') or !property_exists($objectClass, 'row')) {
			logMessage('Error: Trying to Scope an object with no coords ... ');
			return;
		}
		$manager = new BDDObjectManager($objectClass);
		if ($objectClass=='Ground' or $objectClass=='Unit' or $objectClass=='City') {
			$objects = $manager->getInScope($col1, $row1, $col2, $row2, $objectClass);	
		} else {
			$objects = $manager->getInScope($col1, $row1, $col2, $row2);	
		}
		
	    $this->digestObjects($objects);
	    return true;
	}	

	private function loadByIds($objectClass, $ids = [null]) {
		$columns = $objectClass::keyFields();
		if ($ids===null) {$ids=[null];}
	    return $this->loadByColumnsAndValues($objectClass, $columns, [$ids]);
	}

/*========================================================================================
		██████╗ ██████╗ ██████╗     ██╗    ██╗ ██████╗ 
		██╔══██╗██╔══██╗██╔══██╗    ██║   ██╔╝██╔═══██╗
		██████╔╝██║  ██║██║  ██║    ██║  ██╔╝ ██║   ██║
		██╔══██╗██║  ██║██║  ██║    ██║ ██╔╝  ██║   ██║
		██████╔╝██████╔╝██████╔╝    ██║██╔╝   ╚██████╔╝
		╚═════╝ ╚═════╝ ╚═════╝     ╚═╝╚═╝     ╚═════╝ 

	RULES
		We update our BDD according to collection status. 

	Possibilities :
(A)	$collection['Class']['Key'] === null 		-> isset() gives false, array_key_exists() gives true.
(B)	$collection['Class']['Key'] === $someObject	-> isset() gives true,  array_key_exists() gives true.
(C)	$collection['Class']['Key'] is not set 		-> isset() gives false, array_key_exists() gives false.
		
	Results :
(A) -> Delete what is in BDD
(B) -> Save/Update what is in BDD
(C) -> Don't touch anything

	Enfin, à noter : on ne verrouille le Monde que lors du "play turn". Lors
	des sauvegarde de truc par des joueurs, on n'impacte pas les autres, donc
	on ne verrouille rien. Au pire on échouera car il y aura une erreur de levée

	Créer une ville ou une cité ou un joueur ne marche pas sur les pieds des 
	autres donc pas besoin de verrou. C'est le "playturn" qui fait changer les 
	choses avec interactions.

	PREVOIR QUE LES ECHANGE D'INVENTAIRE SOIENT DES ORDRES !!!!!!!

========================================================================================*/

	public function tempId() {
		$this->next_id = $this->next_id - 1;
		return $this->next_id + 1;
	}

	/* -----------------------
		$columns = 	1 or more columns by whom we do requests.
		$values = 	Array of Array, who are the values correponding to columns.
		Example :
			$columns = ['col', 'row']
			$values = [[12, 32], [5, 6]]

			$columns = ['id']
			$values = [[12], [6]]

	*/
	private function loadByColumnsAndValues($objectClass, $columns = ['id'], $values = [[null]]) {
		// $columns Validation
		if (!is_array($columns)) { logMessage('Error: $columns must be an array.'); }
	    // $values Validation
	    if (!is_array($values) || empty($values)) {
	    	logMessage('Error: $values must be an array. Content:'.serialize($values));
	    }
	    foreach ($values as $row) {
	        if (!is_array($row) || count($row) !== count($columns)) {
	        	logMessage('Error: $values must have sub array with same number of entries as $columns. Loading '.$objectClass.' with '.serialize($values));
	        }
	    }
		// When someone asks for loading a collection, he asks to initilize it if no results are founds. So :
		$collection = &$this->getCollectionByClass($objectClass);
		if ($collection === null) {
			logMessage('Load with uninitialized collection <'.$objectClass.'>. Setting collection ...');
			$collection = [];
		}
		// Try to grab data :
	    $manager = new BDDObjectManager($objectClass);
	    // If ANY of $values is null, grab all.
	    $grabAll = false;
		foreach ($values as $row) {
	        foreach($row as $value) {
	        	if ($value === null) {
	        		$grabAll = true;
	        		break; break;
	        	}
	        }
	    }
    	if ($grabAll) {
	        logMessage('Getting all '.$objectClass.'...');
	        $objects = $manager->getAll();
	    } else {
	    	logMessage('Getting some '.$objectClass.'...');
	    	$objects = $manager->getSome( $columns, $values);
	    }
	    $this->digestObjects($objects);
	    return true;
	}

	public function saveCollection($objectClass) {
		logMessage('Trying to save '.$objectClass.'...');
	    $collection = &$this->getCollectionByClass($objectClass);

	    if ($collection===null) {
	    	logMessage('Do not save uninitialized collection. ');
	    	return; 
	    }

	    // logForce('BEFORE SAVE => '.$this->fullCollectionLogTxt($collection));

	    // WHAT TO SAVE OR UPDATE ? (Rule B) -----------------------------------
	    $what_to_save_in_collection = [];
		foreach ($collection as $key => $object) {
			if ($object!==null) {
				$cacheObject = $this->objectFromCache($objectClass, $key);

				if ($cacheObject===null) { logMessage('Warning ! No cache for '.$objectClass.' key='.$key); }

			    if (!$cacheObject || serialize(get_object_vars($cacheObject)) !== serialize(get_object_vars($object))) {
			        $what_to_save_in_collection[] = $object;
			        $this->addToCache($object);
			    }				
			}
		}

		logMessage(count($what_to_save_in_collection).' Objects to save.');
				
		// WHAT TO DELETE ? (Rule A) -------------------------------------------
		$what_to_delete_in_collection = [];
		$this->forEachObjectFromCache(function ($key, $cacheObject) use (&$what_to_delete_in_collection, $collection) {
			if ( !isset($collection[$key]) and array_key_exists($key, $collection) ) {
				$what_to_delete_in_collection[] = $cacheObject;
				$this->removeFromCache($cacheObject);
				unset($collection[$key]);
			}
			if (!isset($collection[$key]) and !array_key_exists($key, $collection)) { 
				logMessage('Warning ! Cached not in collection - key='.$key);
			}
		}, $objectClass);
		logMessage(count($what_to_delete_in_collection).' Objects to delete.');
		
		// APPLYING ! ----------------------------------------------------------	
		// Créer un gestionnaire pour le type d'objet spécifié
		$objectManager = new BDDObjectManager($objectClass);

		// La mapping_table permet de faire une correspondance entre les ID negatif
		// qui sont des objet "neuf" et leurs ID une fois inscrit en BDD.
		$mapping_table = [];
		foreach($what_to_save_in_collection as $each_object) {
			$mapping_table[$each_object->key()] = $each_object->key(); 
		}

	    $mapping_table = $objectManager->saveCollection($what_to_save_in_collection, $mapping_table);

	    foreach($what_to_save_in_collection as $each_object) {
	    	if ($each_object->key()<0) {
	    		$this->changeId($each_object, $mapping_table[$each_object->key()]);
	    	}
	    }

	    $objectManager->deleteCollection($what_to_delete_in_collection);
	    foreach($what_to_delete_in_collection as $object)  {
	    	if ($object->isLocatable()) {$this->deleteFromLocationsIndex($object);}
	    	$this->eraseFromCollection($object);
	    }

	    // PENSEZ a voir pour clear les caches si save impacte les autres joueurs ?


	    // $collection = &$this->getCollectionByClass($objectClass);
		// logForce('AFTER SAVE => '.$this->fullCollectionLogTxt($collection));

	}

/*========================================================================================
		 ██████╗ █████╗  ██████╗██╗  ██╗███████╗
		██╔════╝██╔══██╗██╔════╝██║  ██║██╔════╝
		██║     ███████║██║     ███████║█████╗  
		██║     ██╔══██║██║     ██╔══██║██╔══╝  
		╚██████╗██║  ██║╚██████╗██║  ██║███████╗
		 ╚═════╝╚═╝  ╚═╝ ╚═════╝╚═╝  ╚═╝╚══════╝

	Care : This is not your usual cache system, it's used to determine
   	what to save in BDD (if something is in the cache then it should not 
   	be saved unless it's has been modified) ...

	So don't try to clear it or anything to "improve perfs".
   	Basically, you should Cf. saveCollection()

========================================================================================*/
	public function statusOfCache() {
		global $LOG_LEVEL;
		if ($LOG_LEVEL===0) {return;}
		logMessage('Cache Status : '.sizeof($this->cache, 1).' elements.');
		foreach($this->cache as $key => $value) {
			logMessage('Cache Status : '.$key.' => '.sizeof($value, 1).' elements.');
		}
		return sizeof($this->cache, 1);
	}

	private function refreshCollectionsFromCache() {
		logMessage('Refreshing from cache');
       	foreach($this->cache as $key => $value) {
       		$this->$key = [];
       		foreach($this->cache[$key] as $objKeys => $object) {
       			$this->$key[$objKeys] = clone $object;
       			if ($object->isLocatable()) { $this->addToLocationsIndex($object); }
       		}
       	}
	}

	private function objectFromCache($objectClass, $key) {
		return $this->cache[$objectClass][$key] ?? null;
	}

	private function forEachObjectFromCache(callable $callback, $objectClass) {
		if (isset($this->cache[$objectClass])) {
			foreach ($this->cache[$objectClass] as $key => $cacheObject) {
				$callback($key, $cacheObject);
			}
		}
	}

	private function addToCache($object) {
	    if (!is_object($object)) {
	        logMessage('Erreur : $object n’est pas un objet, mais un ' . gettype($object).' <'.serialize($object).'>');
	        return;
	    }
		$objectClass = get_class($object);
		if (!isset($this->cache[$objectClass])) { $this->cache[$objectClass] = []; }
		$this->cache[$objectClass][$this->collectionKey($object)] = clone $object;
	}

	private function removeFromCache($object) {
	    if (!is_object($object)) {
	        logMessage('Erreur : $object n’est pas un objet, mais un ' . gettype($object).' <'.serialize($object).'>');
	        return;
	    }
		$objectClass = get_class($object);
		unset($this->cache[$objectClass][$this->collectionKey($object)]);
	}

/*========================================================================================
		██╗  ██╗██╗ ██████╗ ██╗  ██╗██╗    ██╗   ██╗██╗     
		██║  ██║██║██╔════╝ ██║  ██║██║    ██║   ██║██║     
		███████║██║██║  ███╗███████║██║    ██║   ██║██║     
		██╔══██║██║██║   ██║██╔══██║██║    ╚██╗ ██╔╝██║     
		██║  ██║██║╚██████╔╝██║  ██║███████╗╚████╔╝ ███████╗
		╚═╝  ╚═╝╚═╝ ╚═════╝ ╚═╝  ╚═╝╚══════╝ ╚═══╝  ╚══════╝

	High level means : any specialized functions that embed complex logic, that
	you can use	as it to do something in the game (deleteUnit, revealVisibleGrounds,
	etc)

========================================================================================*/

	/*--------------------------------------------------------------------------
		Common
	--------------------------------------------------------------------------*/
	public function tokenize($object, $playerIdAsking = -999) {
		$owner = $this->findOwner($object);
		
		if ($owner!==null) {
			$true_owner = ($owner->meta_id==$playerIdAsking);	
		} else {
			$true_owner = false;
		}
		
		// Not same info for my owner or a stranger.
		$token = $object->tokenize($this, $true_owner);
		// No owner, no color.
		if ($owner!==null) { $token['co'] = $owner->color;}
		else  { $token['co'] = 'Bl Bl';}
		return $token;
	}


	public function allAreClose($locatables) {
	    // If not enough objects or only 1, return false, it's a kind of error?
	    if (count($locatables) < 2) { return false; }

	    $locatables = array_values($locatables); // Re-index to ensure proper iteration

	    for ($i = 0; $i < count($locatables) - 1; $i++) {
	        $A_coord = $this->hexalib->coord([$locatables[$i]->col, $locatables[$i]->row], 'oddr');
	        for ($j = $i + 1; $j < count($locatables); $j++) {
	            $B_coord = $this->hexalib->coord([$locatables[$j]->col, $locatables[$j]->row], 'oddr');
	            logMessage('Comparing '.serialize($locatables[$i]).' <-> '.serialize($locatables[$j]));
	            if (!$this->hexalib->is_neighbour($A_coord, $B_coord)) { return false; }
	        }
	    }

	    // If we are here, all are close!
	    return true;
	}

	public function forEachNeighbor($object, $class = 'Ground', $radius = 1, callable $callback) {
		$oddrCoords = $this->hexalib->coord([$object->col, $object->row], 'oddr');
	    $neighborCoords = $this->hexalib->spiral($oddrCoords, $radius);
	    foreach ($neighborCoords as $eachCoords) {
	        $eachCoords = $this->hexalib->convert($eachCoords, 'oddr');
	        $eachCoords->col = magicCylinder($eachCoords->col);
	        $neighbors = $this->getObjectsByCoords($class, $eachCoords->col, $eachCoords->row);
	        foreach($neighbors as $eachNeighbor) {
	        	$callback($eachNeighbor);	
	        }
		}
	}

	/*--------------------------------------------------------------------------
		Ground
	--------------------------------------------------------------------------*/
	public function findSuitableStart() {
		allowLogs();

		// Get all plains or Savana Close to a river/coast
		$waterlist = [];

		logMessage('Finding suitable start ...');

	    $this->forEachInCollection('Ground', function($currGround) use (&$waterlist) {
			// coast or river
			if ($currGround->getGroundType()==5 or $currGround->getGroundType()==13) {
				$waterlist[]=$currGround;
			}
	    });

	    logMessage(count($waterlist), ' water ground found !');

		// Randomly choose one neighbour of those with no town/unit/road/owner close (radius 1) and wich are savana plain!
		shuffle($waterlist);
		foreach($waterlist as $waterGround) {

			logMessage('Analysis of neighbor of this water :');
			$validatedGround = null;
			$this->forEachNeighbor($waterGround, 'Ground', 1, function ($waterNeighbor) use (&$validatedGround) {
				if ($waterNeighbor->getGroundType()==8 or $waterNeighbor->getGroundType()==10) {
					logMessage('Candidate ground ...');
					$clear = true;
					$this->forEachNeighbor($waterNeighbor, 'Unit', 1, function($unit) use (&$clear) {
						if ($unit!==null) { 
							logMessage('Impossible, unit here.');
							$clear = false; 
						}
					});
					$this->forEachNeighbor($waterNeighbor, 'City', 1, function($city) use (&$clear) {
						if ($city!==null) { 
							logMessage('Impossible, city here.');
							$clear = false; 
						}
					});
					$this->forEachNeighbor($waterNeighbor, 'Ground', 1, function($ground) use (&$clear) {
						if ($ground->getRoad()>0) {
							logMessage('Impossible, road here.');
							$clear = false;
						}
						if ($ground->owner_id!=null) {
							logMessage('Impossible, owned here.');
							$clear = false;
						}
					});
					if ($clear) {
						$validatedGround = $waterNeighbor;
						return;
					}
				}
			});

			if ($validatedGround!=null) {
				logMessage('Suitable place found !');
				return $validatedGround;
			}
		}
	}

	public function forEachGround(callable $callback) {
	    $this->forEachInCollection('Ground', function($locatableObject) use ($callback) {
	        if ($locatableObject !== null && $locatableObject->kind === 'Ground') {
	            $currCol = $locatableObject->col;
	            $currRow = $locatableObject->row;
	            $callback($currCol, $currRow);
	        }
	    });
	}

	// ATTENTION pour utiliser set il faut être sur qu'on a tout chargé avant.
	// Malin ? je ne crois pas.
	public function setGroundTypeByCoords($col, $row, $type) {
		$ground = $this->getGround($col, $row);
		if ($ground === null ) {
			logMessage('No grounds @'.$col.','.$row);
			$id = $this->tempId('Ground');
			$data = ['id'=>$id, 'col'=>$col, 'row'=>$row];
			$ground = new Ground($data);
		}
		$ground->setJsonData('ground_type', $type);
		$this->updateCollection($ground);
	}

	public function nbGrounds() {
		$collection = &$this->getCollectionByClass('Ground');
		return count($collection);
	}

	public function getGround(int $col, int $row) {
		$grounds = $this->getObjectsByCoords('Ground', $col, $row);
		if (count($grounds)<1) {return null;}
	    if (count($grounds)>1) {
	    	logForce('Too much ground ('.count($grounds).') @'.$col.'-'.$row); 
	    	foreach($grounds as $key => $each_object) { 
	    		if ($key>0) {
	    			logForce('Deleting ...'.serialize($each_object)); 
	    			$this->deleteFromCollection($each_object);
	    		} else {
	    			logForce('Keeping ...'.serialize($each_object)); 
	    		}
	    	}
	    }
		$ground = $grounds[0];
		return $ground;
	}

	// Finding neighbor of type $groundTypes.
	// If inverted, find neighbor NOT of type $groundTypes
	public function getGroundNeighborOfTypes(array $groundTypes, $col, $row, $direction = null, $invert = false, $radius = 1) {
	    $currentCoord = $this->hexalib->coord([$col, $row], 'oddr');
	    // Si une direction spécifique est fournie, cherche uniquement dans cette direction
	    if ($direction !== null) {
	        $neighborCoord = $this->hexalib->neighbour($currentCoord, $direction);
	        $neighborCoord->col = magicCylinder($neighborCoord->col);
	        $neighbor = $this->getGround($neighborCoord->col, $neighborCoord->row);
	        // Vérification avec tableau de types et condition invert
	        return ($neighbor !== null && 
	                (($invert && !in_array($neighbor->getGroundType(), $groundTypes)) || 
	                 (!$invert && in_array($neighbor->getGroundType(), $groundTypes)))) ? $neighbor : null;
	    }
	    // Sinon, parcours tous les voisins
	    $neighbors = [];
	    $neighborCoords = $this->hexalib->spiral($currentCoord, $radius);
	    foreach ($neighborCoords as $eachCoord) {
	        $eachCoord = $this->hexalib->convert($eachCoord, 'oddr');
	        $eachCoord->col = magicCylinder($eachCoord->col);

	        $neighbor = $this->getGround($eachCoord->col, $eachCoord->row);

	        // Vérification avec tableau de types et condition invert
	        if ($neighbor !== null && 
	            (($invert && !in_array($neighbor->getGroundType(), $groundTypes)) || 
	             (!$invert && in_array($neighbor->getGroundType(), $groundTypes)))) {
	            $neighbors[] = $neighbor;
	        }
	    }
	    return $neighbors;
	}

	public function revealVisibleGrounds($actor) {
		// 2 things can "view" : unit & city
		// fov is a native thing for unit, and depend of city size for city
		// 	+ fov of the current ground
		logMessage('Revealing visible ground !');

		$ground = $this->getGround($actor->col, $actor->row);
		$total_fov = $ground->getLandTypeFov() + $actor->getFov();
		if ($total_fov<=0) {
			logForce('<CRITICAL> FOV<=0 ?! I guess something is broken.');
			return false;
		}
		$player = $this->findOwner($actor);

		// Now we have a player, and a fov "radius", now we must modify known map
		// of the player (adding $radius coords) and save this shit

	}

	/*--------------------------------------------------------------------------
		Order
	--------------------------------------------------------------------------*/
	// Change turn value for all orders :
	public function turnOrderDecrease() {
	    $this->forEachInCollection('Order', function($orderBDDObj) {
	        $orderBDDObj->turn--;
	        $this->updateCollection($orderBDDObj);
	    });
	}

	public function turnOrderExecute() {
	    $this->forEachInCollection('Order', function($orderBDDObj) {
	        if ($orderBDDObj->turn<=0) {
	        	logMessage('Doing '.$orderBDDObj->order_type.' to '.$orderBDDObj->owner_id);
	        	// Instanciate
	        	$real_order = OrderFactory::createOrder($orderBDDObj->order_type, $orderBDDObj->owner_id, $orderBDDObj->data, $this);
	        	
	        	if ($real_order==false) {
	        		// The order is invalide, and have to be delete.
	        		logForce("<CRITICAL> Order of this turn wrong ?! Give up on this one.");
	        		$this->deleteFromCollection($orderBDDObj);
	        		return;

	        	} else {

		        	// Do what to do (including creation of objects or new orders or even deleting from colection...)
		        	$real_order->action();
	        	}
	        }
	    });
	}

	/*--------------------------------------------------------------------------
		City
	--------------------------------------------------------------------------*/
	public function newCity($player_id, $col, $row, $name='DaisyTown') {
		$id = $this->tempId();
		$data = [
			'id' => $id,
			'col' => $col, 
			'row' => $row, 
			'owner_id' => $player_id,
			'kind' => 'City',
		];
		$city = new City($data);
		$city->setJsonData('name', $name);
		$city->setJsonData('population', 100);
		$this->updateCollection($city);
		$this->revealVisibleGrounds($city);
	}

	public function deleteCity($city_to_del, $city_to_relink = null) {
		// Deleting a city implies to delete all Unit linked, or to link them away
		$city_units = $this->getObjectsByProperty('Unit', 'city_id', $city_to_del->id);
    	foreach($city_units as $unit) {
    		if ($city_to_relink === null) {
    			$this->deleteUnit($unit);	
    		} else {
    			$this->relinkUnit($unit, $city_to_relink);
    		}
    	}
    	$this->deleteFromCollection($city_to_del);
	}

	/*--------------------------------------------------------------------------
		Units
	--------------------------------------------------------------------------*/
	public function newUnit($unit_type, $player_id, $col, $row, $city_id=0) {
		$id = $this->tempId();
		$data = [
			'id' => $id,
			'col' => $col, 
			'row' => $row, 
			'owner_id' => $player_id,
			'kind' => 'Unit',
		];
		$unit = new Unit($data);
		$unit->setJsonData('unit_type', $unit_type);
		$unit->setJsonData('city_id', $city_id);
		$unit->setJsonData('name', $unit->getUnitTypeName());
		$this->updateCollection($unit);
		$this->revealVisibleGrounds($unit);
	}


	public function deleteUnit($unit) {
		$this->deleteFromCollection($unit);
		
	}

	public function deleteUnitOrder($order) {
		$this->deleteFromCollection($order);
	}

	public function relinkUnit($unit, $city) {
		
	}

	/*--------------------------------------------------------------------------
		Player
	--------------------------------------------------------------------------*/
	public function nbPlayers() {
		if (!isset($this->Player) or $this->Player===null) {return 0;}
		return count($this->Player);
	}

	public function playerExists($id) {
		$player = $this->getObjectByKey('Player', [$id]);
		if ($player === null) {return false;}
		return true;
	}

	public function player_age($id) {
		return 0;
	}

	public function playerOwns($player_id, $object) {
		$owner = $this->findOwner($object);
		return $owner->meta_id === $player_id;
	}

	public function findOwner($object) {
		// logMessage('Asking for owner of '.serialize($object));
		if (isset($object->meta_id)) { return $object; }
		if (isset($object->player_id)) { return $this->getObjectByKey('Player', [$object->player_id]); }
		if (isset($object->owner_id)) { return $this->getObjectByKey('Player', [$object->owner_id]); }
		return null;
	}

	public function playerUnits($player_id){
		return $this->getObjectsByProperty('Unit', 'owner_id', $player_id);
	}

	public function playerCities($player_id){
		return $this->getObjectsByProperty('City', 'owner_id', $player_id);

	}
}
