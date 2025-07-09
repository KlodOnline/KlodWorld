<?php

/*========================================================================================
    REQUEST MANAGER

    - check request legitimacy
        - filter & check post data
        - check player rights to do so
    - read/write board accordingly
        or
    - exceptionnaly request BDD with finely chisel requests to optimize BDD

    Request are POST built this way :
        'T' (type) = SCOPE / INIT / CSELECT ...)
        'M' (message) = Some data for the request

========================================================================================*/

class RequestManager
{
    private $board;
    private $log = false;

    /***************************************************************************
        Constructor
    ***************************************************************************/
    public function __construct()
    {
        $this->board = new Board(true);
    }

    /***************************************************************************
        Main functions
    ***************************************************************************/
    // Generic API Request Handler routing
    public function handleRequest(string $type, array $data): array
    {

        startTimer('>>>> Handle Request - '.$type);

        // Vérifier si c'est la première requête et logguer
        if (!SessionManager::get('last_request')) {
            logMessage('First request! Cute :)');
        } else {
            // Calculer le temps écoulé depuis la dernière requête
            $time_elapsed = round((microtime(true) - SessionManager::get('last_request')) * 1000);
            logMessage('Not first request. Time elapsed: ' . $time_elapsed . ' ms.');

            // Si la requête est plus rapide que 50ms, fermer la session et retourner une réponse
            if ($time_elapsed <= 50) {
                logMessage('OkTxsByeMotherFucka.');
                $for_javascript = ['nope' => true];
                return $for_javascript; // Fin du script
                exit();
            }
        }

        stepTimer('1stRequest check done - '.$type);

        // Mettre à jour le temps de la dernière requête dans la session
        SessionManager::set('last_request', microtime(true));

        $this->loadPlayerIfNeeded();

        stepTimer('Player loading done - '.$type);

        $handlers = [
            'UORDERS'      => 'handleGetOrder',
            'CSELECT'      => 'handleGetOrder',
            'GET_ORDER'    => 'handleGetOrder',
            'SET_ORDER'    => 'handleSetOrder',
            'CANCEL_ORDER' => 'handleCancelOrder',
            'NEWBIE'       => 'handleNewbie',
            'SCOPEG'       => 'handleScopeGroundRequest',
            'SCOPEA'       => 'handleScopeAllRequest',
            'INIT'         => 'handleInit',
            'CITY_INFO'    => 'handleDetails',
            'UNIT_INFO'    => 'handleDetails',
        ];

        if (array_key_exists($type, $handlers)) {

            stepTimer('Request handling ... - '.$type);

            $method = $handlers[$type];
            $result_array = $this->$method($data);

            stopTimer('<<<< Show Request Response - '.$type);

            return $result_array;
        }
        logForce('<CRITICAL> Unknown request type : '.serialize($type).' > '.serialize($data));

        stopTimer('Show Request Response - '.$type);

        return [];
    }


    /*******************************************************************************
    ██╗   ██╗████████╗██╗██╗     ███████╗
    ██║   ██║╚══██╔══╝██║██║     ██╔════╝
    ██║   ██║   ██║   ██║██║     ███████╗
    ██║   ██║   ██║   ██║██║     ╚════██║
    ╚██████╔╝   ██║   ██║███████╗███████║
     ╚═════╝    ╚═╝   ╚═╝╚══════╝╚══════╝
    *******************************************************************************/
    private function serverLocked($log = false)
    {

        // ATTENTION ?! Cela dépend si on est turn_manger ou GUI Client ?

        updateSessionStatus();
        $locked = SessionManager::get('status')['lock'];
        if ($locked) {
            logMessage('Server is locked ! Cannot save for now...');
        }
        if (!$locked) {
            logMessage('Server is unlocked ! Can save !');
        }
        return $locked;
    }

    private function orderNameCleaner($dubiousData)
    {
        if (isset($dubiousData) && is_string($dubiousData) && preg_match('/^[A-Z_]+$/', $dubiousData)) {
            return $dubiousData;
        }
        logMessage("Order Name Format invalid !");
        return ''; // This is a non-existent Order Name.
    }

    private function orderDataCleaner($dubiousData)
    {
        if (isset($dubiousData) && is_string($dubiousData) && preg_match('/^(\d+(-\d+)?)(,\d+(-\d+)?)*/', $dubiousData)) {
            return $dubiousData;
        }
        logMessage("Order Data Format invalid !");
        return ''; // Empty Dataset
    }

    public function loadPlayerIfNeeded()
    {
        // There is no world where the main player is loaded without his city
        // or units, and there is no world where the cache is up and the data are
        // obsolete. [TRUST THE BOARD DAMN' CORPORAT !]
        if (!$this->board->playerExists(META_ID)) {
            logMessage("Load ".META_ID." data.");
            $this->board->loadOnePlayerObjects(META_ID);
            // $this->board->loadGrounds();
        } else {
            logMessage("Not wanting to load again.");
        }
    }

    // Take Care & remember how save work ! (See board.php 'saveCollection' function)
    public function updateAndSaveBoardCollection($object)
    {
        $class = get_class($object);
        $this->board->updateCollection($object);
        return $this->saveBoardCollection($class);
    }

    public function saveBoardCollection($class)
    {
        if (!$this->serverLocked(true)) {
            $this->board->saveCollection($class);
            logMessage($class.' Saved.');
            return true;
        }
        return false;
    }

    private function itemIdCleaner($dubiousId)
    {
        $objectId = filter_var($dubiousId, FILTER_VALIDATE_INT);
        if ($objectId === false || $objectId === null) {
            logMessage('ID Format invalid or not provided!');
            exit();
        }
        if ($objectId <= 0) {
            logMessage('Object ID under 0 ?!');
            exit();
        }
        return (int) $objectId;
    }

    private function splitPostData($dubiousArray)
    {

        if (empty($dubiousArray)) {
            logForce("<CRITICAL> No 'M' parameter for setOrder.");
            exit();
        }

        $cleanArray = explode('-', $dubiousArray);

        if (count($cleanArray) < 2) {
            logForce("<CRITICAL> Invalid data format in M parameter for setOrder.");
            exit();
        }

        return $cleanArray;
    }
    private function getUnitOrCity($objectId)
    {
        $object = $this->board->getObjectByKey('Unit', [$objectId]);
        if ($object === null) {
            logMessage('Not a unit. Maybe a city ?');
            $object = $this->board->getObjectByKey('City', [$objectId]);
        }
        if ($object === null) {
            logForce("<CRITICAL> No object for the ID : ".$objectId.".");
            exit();
        }
        return $object;
    }

    private function getTokensForOrders($orders)
    {

        $tokens = [];

        // SCOPE
        $highestCol = null;
        $lowestCol = null;
        $highestRow = null;
        $lowestRow = null;

        foreach ($orders as $eachOrder) {
            $token = $this->board->tokenize($eachOrder, META_ID);

            array_push($tokens, $token);

            // SCOPE
            $order = $eachOrder->advancedOrder($this->board);
            if ($order->havePath()) {
                logMessage('Order with a path - have to prepare a SCOPE');

                $orderHighestCol = $order->highestCol();
                $orderLowestCol = $order->lowestCol();
                $orderHighestRow = $order->highestRow();
                $orderLowestRow = $order->lowestRow();

                if ($highestCol === null || $orderHighestCol > $highestCol) {
                    $highestCol = $orderHighestCol;
                }
                if ($lowestCol === null || $orderLowestCol < $lowestCol) {
                    $lowestCol = $orderLowestCol;
                }
                if ($highestRow === null || $orderHighestRow > $highestRow) {
                    $highestRow = $orderHighestRow;
                }
                if ($lowestRow === null || $orderLowestRow < $lowestRow) {
                    $lowestRow = $orderLowestRow;
                }

            }

        }

        // SCOPE
        if ($highestCol !== null) {
            $this->board->loadByScope(($what === 'Ground' ? 'Ground' : 'Locatable'), $lowestCol, $lowestRow, $orderHighestCol, $highestRow);
        }

        // ATTENTOON
        // Il reste encore à trouver comment passer PLUSEIURS ordres au GUI
        // car là j'en envoie qu'un seul mais pour les villes il en faudra sans
        // doute 2 : construire, et recruter...

        return $token;
    }

    /*******************************************************************************
    ██╗  ██╗ █████╗ ███╗   ██╗██████╗ ██╗     ███████╗██████╗ ███████╗
    ██║  ██║██╔══██╗████╗  ██║██╔══██╗██║     ██╔════╝██╔══██╗██╔════╝
    ███████║███████║██╔██╗ ██║██║  ██║██║     █████╗  ██████╔╝███████╗
    ██╔══██║██╔══██║██║╚██╗██║██║  ██║██║     ██╔══╝  ██╔══██╗╚════██║
    ██║  ██║██║  ██║██║ ╚████║██████╔╝███████╗███████╗██║  ██║███████║
    ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═════╝ ╚══════╝╚══════╝╚═╝  ╚═╝╚══════╝
    *******************************************************************************/

    // Showup Management ========================================================
    private function handleDetails($postData)
    {
        allowLogs();
        $objectId = $this->itemIdCleaner($postData['M']);
        logMessage('Needing '.$objectId.' full info ');

        // DUMMY DATA
        // Getting real infos :
        $player = $this->board->getObjectByKey('Player', [META_ID]);
        $object = $this->getUnitOrCity($objectId);

        $packet = [];

        $object_token = $this->board->tokenize($object);
        $object_token['json'] = $object->jsonData();

        $orders = $this->board->getObjectsByProperty('Order', 'owner_id', $objectId);
        $order_tokens = $this->getTokensForOrders($orders);
        $object_token['or'] = $order_tokens;


        array_push($packet, $object_token);

        disableLogs();
        return $packet;
    }

    // Order Management ========================================================

    private function handleCancelOrder($postData)
    {
        allowLogs();
        $objectId = $this->itemIdCleaner($postData['M']);
        logMessage('Actor '.$objectId.' wants to cancel order.');

        // Getting real infos :
        $player = $this->board->getObjectByKey('Player', [META_ID]);
        $object = $this->getUnitOrCity($objectId);

        // $object = $this->board->getObjectByKey('Unit', [$objectId]);

        if ($this->board->playerOwns(META_ID, $object)) {
            logMessage("Canceling order of my object.");
        } else {
            logMessage("<CRITICAL> User ".META_ID." is trying to cancel for object ".$objectId);
            return [];
        }

        $this->board->loadOrder($objectId);
        $orders = $this->board->getObjectsByProperty('Order', 'owner_id', $objectId);
        foreach ($orders as $eachOrder) {
            logMessage('Deleting order(s) in board');
            $this->board->deleteUnitOrder($eachOrder);
        }
        $this->board->saveCollection('Order');
        $packet = [];
        $object_token = $this->board->tokenize($object);
        array_push($packet, $object_token);
        disableLogs();
        return $packet;
    }

    private function handleGetOrder($postData)
    {
        allowLogs();
        $objectId = $this->itemIdCleaner($postData['M']);
        logMessage('Get order for actor '.$objectId.'.');

        // Getting real infos :
        $player = $this->board->getObjectByKey('Player', [META_ID]);
        $object = $this->getUnitOrCity($objectId);

        if ($player === null) {
            logMessage("Nope, player unknown...");
            return [];
        }
        if ($object === null) {
            logMessage("Nope, object unknown...");
            return [];
        }

        // Sadly, move orders needs map info, so we need to load player map data before any validation !
        // A FAIRE : Loader que ce qui est utile par rapport à l'ordre, et gérer ça en une requete unique BDD
        // Pour optimiser.
        // $this->board->loadGrounds();

        $this->board->loadOrder($objectId);
        $orders = $this->board->getObjectsByProperty('Order', 'owner_id', $objectId);
        logMessage('Order founds : '.serialize($orders));
        $packet = [];
        $object_token = $this->board->tokenize($object, META_ID);

        $order_tokens = $this->getTokensForOrders($orders);
        $object_token['or'] = $order_tokens;

        array_push($packet, $object_token);
        //disableLogs();
        return $packet;
    }

    /*

        Techniquement, chaque ordre venir peux avec des POST bien à lui.

        Il faudrait faire un genre de OrderFactoy::PostDigester($_POST)
        et dedans, découper doucement le $_POST en morceau, et le valider en fonction
        de l'ordre que le $_POST est sensé appeller.

        OrderFactory::PostDigester($_POST['M']) {
            ->find final order and then :
            $cleanedOrderDetails = MoveOrder::DigestDetails(POST['M'])
            return $cleanedOrderDetails
        }

        au final, request_manager doit juste s'assurer que POST['M'] contient
        chiffre, lettres majuscule, virgule, tiret, underscore
        et dans le JS on peux mettre une remarque, pour pointer vers DigestDetails
        et qu'on sache ce qu'on doit ecrire dans le frontend

    */
    private function handleSetOrder($postData)
    {
        allowLogs();
        $orderData = $this->splitPostData($postData['M']);
        $actorId = $this->itemIdCleaner($orderData[0]);
        $orderType = $this->orderNameCleaner($orderData[1]);
        $orderDetails = isset($orderData[2]) ? $this->orderDataCleaner($orderData[2]) : '';

        logMessage('Creating Order with <'.$orderType.'>, <'.$actorId.'>, <'.$orderDetails.'>.');

        // Sadly, move orders needs map info, so we need to load player map data before any validation !
        // $this->board->loadGrounds();

        // Create with order factory - but it's not that important if invalid, turn_manager will sort it out.
        $order = OrderFactory::createOrder($orderType, $actorId, $orderDetails, $this->board);

        if ($order == null) {
            logForce("<CRITICAL> Invalid order or validation failed.");
            return [];
        }

        //Just validate owner !
        if (!$this->board->playerOwns(META_ID, $order->actor())) {
            logForce("<CRITICAL> Giving order to another player Object ?!.");
            return [];
        }

        // Saving in BDD
        logMessage('Saving Order with '.serialize($order->BDDData()));
        $className = $order->BDDClass();
        $collectionOrder = new $className($order->BDDData());
        $this->updateAndSaveBoardCollection($collectionOrder);

        // And... We send a little something to the client, allowing him to refresh gui :
        // (and to confirm something happend)
        $packet = [];
        $object_token = $this->board->tokenize($order->actor());
        $object_token['or'] = $this->board->tokenize($collectionOrder);
        array_push($packet, $object_token);

        disableLogs();
        return $packet;
    }

    // NEWBIE INCOMING ? =======================================================
    private function handleNewbie()
    {

        $player_txt = PLAYER_NAME.' ('.META_ID.')';
        $for_javascript = ['retry' => false, 'is_newb' => null];

        logMessage('Is '.META_ID.' a newb ?');

        if (!$this->board->playerExists(META_ID)) {
            // if (true) {

            logMessage('First time for '.$player_txt);

            if (!$this->serverLocked(true)) {

                // PLAYER CREATION IN THE WORLD !!! --------------------------------
                // Problem is that we needs real IDs for each steps, so, we
                // save in the BDD multiple times during the process.

                // First : find a suitable place :
                logMessage('Loading Grounds...');
                $this->board->loadGrounds();

                allowLogs();

                $ground = $this->board->findSuitableStart();
                if ($ground === null) {
                    logForce('<CRITICAL> No Starting place Available ?!');
                    return [];
                }

                // Second : Create the player.
                $color = randomColor();
                if (META_ID === 1) {
                    $color  = 'Ba Ba';
                }

                $data = ['meta_id' => META_ID, 'name' => PLAYER_NAME, 'paidto' => FEE_PAID, 'color' => $color ];
                $player = new Player($data);



                // THIS MUST BE THE CREATION OF DISCOVER


                disableLogs();

                $this->updateAndSaveBoardCollection($player);

                // Third : Create starter unit.
                $this->board->newUnit(0, META_ID, $ground->col, $ground->row);
                // $this->board->newUnit(3, META_ID, $ground->col, $ground->row);

                $this->saveBoardCollection('Unit');



            } else {
                // Ask for a retry, can't save now.
                $for_javascript['retry'] = true;
            }
            $for_javascript['is_newb'] = true;

            // End of player creation. -----------------------------------------

        } else {
            logMessage(META_ID.' is maybe a newb !');
            //Player Exists ! Maybe not a newb ...
            $for_javascript['is_newb'] = false;
            // But mayber he is if too young ?
            if ($this->board->player_age(META_ID) <= 15) {
                $for_javascript['is_newb'] = true;
                logMessage(META_ID.' is too young to die !');
            }

        }

        // Anyway fin d a unit to setup start place :
        $units = $this->board->playerUnits(META_ID);
        $cities = $this->board->playerCities(META_ID);
        if (count($cities) > 0) {
            $for_javascript['start_loc'] = $cities[0]->col.','.$cities[0]->row;
        } elseif (count($units) > 0) {
            $for_javascript['start_loc'] = $units[0]->col.','.$units[0]->row;
        } else {
            // Default start place :
            $for_javascript['start_loc'] = '100,100';
        }


        return $for_javascript;
    }

    // CITY SELECTION ==========================================================
    private function handleCitySelection(array $data)
    {

        $city_id = $this->itemIdCleaner($data['M']);
        logMessage('Selecting City '.$objectId.'.');

        $bdd_io = new bdd_io();
        $request = 'SELECT '
            . 'C.name AS na, C.id AS id, C.population AS po, C.buildings AS bu, '
            . 'P.color AS co, '
            . 'U.id AS uid, U.type AS uty, U.name AS una '
            . 'FROM klodonline.cities AS C '
            . 'LEFT JOIN klodonline.players AS P ON (C.player_id = P.meta_id) '
            . 'LEFT JOIN klodonline.units AS U ON (C.col = U.col AND C.row = U.row) '
            . 'WHERE C.id=' . $city_id;

        $rows = $bdd_io->query($request);

        $city_data = [];
        $units = [];
        while (sizeof($rows) > 0) {
            $donnees = array_pop($rows);

            if ($donnees['id'] !== null) {
                foreach ($donnees as $key => $value) {
                    $city_data[$key] = $value;
                }

                $unit = ['id' => $donnees['uid'], 'ty' => $donnees['uty'], 'na' => $donnees['una']];
                $units[] = $unit;
            }
        }

        $city_data['units'] = $units;
        $city_data['t'] = 'C';

        return [0 => $city_data];
    }

    // GRAB SCOPE DETAILS ! ====================================================

    public function handleScopeGroundRequest($postData)
    {
        return $this->handleScopeRequest($postData, 'Ground');
    }

    public function handleScopeAllRequest($postData)
    {
        return $this->handleScopeRequest($postData, 'All');
    }

    public function handleScopeRequest($postData, $what)
    {
        // allowLogs();

        // Step 1: Data Validation
        if (preg_match('/^(-?\d+),(-?\d+),(-?\d+),(-?\d+)$/', $postData['M'], $matches)) {
            [$col1, $row1, $col2, $row2] = array_map('intval', array_slice($matches, 1));
            if ($col2 < $col1) {
                [$col1, $col2] = [$col2, $col1];
            }
        } else {
            logMessage("Coords Format invalid!");
            exit();
        }

        logMessage("Coords: From $col1,$row1 to $col2,$row2");

        $packets = [];
        $to_load_coords = [];

        // Step 2: Generate coordinates to load
        for ($colToLoad = $col1; $colToLoad <= $col2; $colToLoad++) {
            $finalCol = magicCylinder($colToLoad);
            for ($rowToLoad = max(0, $row1); $rowToLoad <= min($row2, MAX_ROW - 1); $rowToLoad++) {
                $to_load_coords[$finalCol."_".$rowToLoad] = [$finalCol, $rowToLoad];
            }
        }

        // Step 3: Load from cache
        $cache_results = ['Ground' => [], 'City' => [], 'Unit' => []];
        $not_in_cache = 0;
        $present_in_cache = 0;

        foreach ($to_load_coords as $key => [$col, $row]) {
            if ($what === 'Ground' || $what === 'All') {
                $cache_results['Ground'][$key] = $this->board->getGround($col, $row);
            }
            if ($what === 'City' || $what === 'All') {
                $cache_results['City'][$key] = $this->board->getObjectsByCoords('City', $col, $row);
            }
            if ($what === 'Unit' || $what === 'All') {
                $cache_results['Unit'][$key] = $this->board->getObjectsByCoords('Unit', $col, $row);
            }

            if ($cache_results['Ground'][$key] !== null) {
                $present_in_cache++;
                unset($to_load_coords[$key]);
                $packets[] = $this->objectsToPacket(
                    $cache_results['Ground'][$key],
                    $cache_results['City'][$key] ?? [],
                    $cache_results['Unit'][$key] ?? []
                );
            } else {
                $not_in_cache++;
            }
        }

        logMessage("Cache: Present = $present_in_cache, Absent = $not_in_cache");
        logMessage('Remaining to load: ' . count($to_load_coords));

        // Step 4: Load missing data from database
        if (!empty($to_load_coords)) {
            logMessage('Request Scope ...');
            $this->board->loadByScope(($what === 'Ground' ? 'Ground' : 'Locatable'), $col1, $row1, $col2, $row2);

            foreach ($to_load_coords as $key => [$col, $row]) {
                $ground = $this->board->getGround($col, $row);
                $cities = $what !== 'Ground' ? $this->board->getObjectsByCoords('City', $col, $row) : [];
                $units = $what === 'All' ? $this->board->getObjectsByCoords('Unit', $col, $row) : [];

                if ($ground !== null) {
                    $packets[] = $this->objectsToPacket($ground, $cities, $units);
                    unset($to_load_coords[$key]);
                }
            }
        } else {
            logMessage('Nothing to do!');
        }

        // disableLogs();
        return $packets;
    }

    /*******************************************************************************
        End of handlers.
    *******************************************************************************/

    private function objectsToPacket($ground, $cities = [], $units = [])
    {



        // Packet enveloppe prep :
        $packet = [];
        $packet['c'] = $ground->col;
        $packet['r'] = $ground->row;
        $packet['o'] = [];

        // Agregate Grounds Data !
        if ($ground !== null) {
            $token = $this->board->tokenize($ground);
            // logMessage('Adding ground to packet.'.serialize($token));
            array_push($packet['o'], $token);
        }

        foreach ($cities as $each_city) {
            if ($each_city !== null) {
                $token = $this->board->tokenize($each_city);
                // logMessage('Adding city to packet.'.serialize($token));
                array_push($packet['o'], $token);
            }
        }

        foreach ($units as $each_unit) {
            if ($each_unit !== null) {
                $token = $this->board->tokenize($each_unit);
                // logMessage('Adding unit to packet.'.serialize($token));
                array_push($packet['o'], $token);
            }
        }

        return $packet;
    }

}
