<?php

/* =============================================================================
    Class BDDObjectManager

    This class acts as a bridge between the business logic and the database layer.
    It is responsible for generating database queries based on the structure and
    behavior of game objects (e.g., Cities, Units) and transforming raw database
    results into meaningful object instances.

    Responsibilities:
        - Defining and executing SQL queries tailored to specific game objects.
        - Mapping database rows to object instances (and vice versa).
        - Encapsulating the logic for data retrieval and persistence of game objects.
        - Ensuring the code remains aligned with the business domain, while delegating
        raw query execution to a lower-level database interface (e.g., bdd_io).

    This class abstracts the data layer for game-related entities, allowing the
    game logic to focus on gameplay without worrying about database interactions.

============================================================================= */
include_once COMMON_PATH.'/includes/bdd_io.php';

class BDDObjectManager
{
    protected $conn;
    protected $className;
    protected $table;
    private $previousState = [];

    /* -------------------------------------------------------------------------
        Constructor for the class.

        @param string $className The name of the class to manage.
        @throws Exception If the class does not exist or does not extend BDDObject.
    ------------------------------------------------------------------------- */
    public function __construct($className)
    {

        // Verify $className is a child of BDDObject!
        if (!class_exists($className) || !is_subclass_of($className, 'BDDObject')) {
            $message = "Invalid class: $className must extend BDDObject";
            throw new Exception($message);
        }

        $this->conn = new bdd_io();
        $this->className = $className;
        $this->table = $className::TABLE_NAME;
    }

    /* -------------------------------------------------------------------------
        Converts database results into an array of objects.

        @param mixed $results The result set (array or mysqli_result) to process.
        @return array An array of objects of the specified class.
    ------------------------------------------------------------------------- */
    public function resultsManager($results)
    {
        $objects = [];

        // If $results is an array, we can iterate
        if (is_array($results)) {
            foreach ($results as $row) {
                // Pour locatable on a fait du sale !!!
                if ($this->className === 'Locatable') {
                    $object = new $this->className($row);
                    $realClasse = $object->kind;
                    $objects[] = new $realClasse($row);
                } else {
                    $objects[] = new $this->className($row);
                }
            }
        } else {
            // If not, it may be a mysqli_result object, so fetch_assoc.
            while ($row = $results->fetch_assoc()) {
                // Pour locatable on a fait du sale !!!
                if ($this->className === 'Locatable') {
                    $object = new $this->className($row);
                    $realClasse = $object->kind;
                    $objects[] = new $realClasse($row);
                } else {
                    $objects[] = new $this->className($row);
                }
            }
        }
        return $objects;
    }

    /* -------------------------------------------------------------------------
        Retrieves all records from the table.

        @return array An array of objects of the specified class.
    ------------------------------------------------------------------------- */
    public function getAll()
    {

        /*		if (apcu_exists($this->table)) {
                    logForce('Loading '.$this->table.' from APCu !');
                    return apcu_fetch($this->table);
                }
        */

        // logForce('Loading '.$this->table.' from BDD !');

        $query = "SELECT * FROM $this->table";
        $results = $this->conn->executeQuery($query);
        $final_result = $this->resultsManager($results);

        //	    apcu_store($this->table, $final_result);

        return $final_result;
    }

    public function getSome($columns, $values)
    {


        $cacheKey = $this->table.'-'.serialize($columns).serialize($values);
        /*
                if (apcu_exists($cacheKey)) {
                    logForce('Loading '.$this->table.' from APCu !');
                    return apcu_fetch($cacheKey);
                }

                logForce('Loading '.$this->table.' from BDD !');
        */
        // Construire la requête avec des placeholders sécurisés
        $whereClause = [];

        foreach ($values as $eachRow) {
            $rowConditions = [];
            foreach ($columns as $index => $column) {

                $value = $eachRow[$index];

                // Special traitement !
                if ($column == 'col') {
                    $value = magicCylinder($value);
                }
                if ($column == 'row') {
                    if ($value < 0) {
                        continue;
                    }
                    if ($value > (MAX_ROW - 1)) {
                        continue;
                    }
                }

                logMessage('For Column <'.$column.'> look for <'.$value.'>.');

                $rowConditions[] = '`'.$column.'` = '.$value;
            }
            $whereClause[] = '(' . implode(' AND ', $rowConditions) . ')';
        }

        // Joindre toutes les conditions WHERE
        $whereClauseString = implode(' OR ', $whereClause);

        // Construire la requête SQL complète
        $query = "SELECT * FROM $this->table WHERE $whereClauseString";

        // Logger la requête
        logMessage('Executing query: ' . $query);

        $results = $this->conn->executeQuery($query);
        $final_result = $this->resultsManager($results);

        // apcu_store($cacheKey, $final_result);

        return $final_result;
    }
    /*
        public function getPlayerMainThings($meta_id) {
    //		$query = "SELECT p.meta_id AS player_id, p.name AS player_name, p.color "
    //			."AS player_color, l.id AS locatable_id, l.col AS locatable_col, "
            $query = "SELECT l.id AS id, l.col AS col, "
                ."l.row AS row, l.kind AS kind, o.order_type AS order_type, "
                ."o.turn AS turn FROM klodonline.players p "
                ."LEFT JOIN klodonline.locatables l ON l.owner_id = p.meta_id "
                ."LEFT JOIN klodonline.orders o ON o.owner_id = l.id "
                ."WHERE p.meta_id = ".$meta_id.";";
            $results = $this->conn->executeQuery($query);
            return $this->resultsManager($results);
        }
    */
    public function getInScope($col1, $row1, $col2, $row2, $kind = '')
    {

        // Appliquer la normalisation à col1 et col2
        $normalizedCol1 = magicCylinder($col1);
        $normalizedCol2 = magicCylinder($col2);

        // Vérifier si la normalisation a eu lieu
        if ($col1 == $normalizedCol1 && $col2 == $normalizedCol2) {
            $colCondition = "col >= $col1 AND col <= $col2";
        } else {
            $colCondition = "col >= $normalizedCol1 OR col <= $normalizedCol2";
        }

        $whereClauseString = "$colCondition AND row >= $row1 AND row <= $row2";

        // Construire la requête SQL complète
        $kindFilter = '';
        if ($kind != '') {
            $kindFilter = "kind='$kind' AND";
        }

        $query = "SELECT * FROM $this->table WHERE $kindFilter $whereClauseString";
        logMessage($query);
        $results = $this->conn->executeQuery($query);
        return $this->resultsManager($results);
    }



    public function getBiggest($column)
    {
        $query = "SELECT * FROM $this->table ORDER BY $column DESC LIMIT 1";
        $results = $this->conn->executeQuery($query);
        $results = $this->resultsManager($results);
        if (count($results) < 1) {
            return null;
        }
        return $results[0];
    }

    public function insertUnique($object)
    {

        // Préparer les données pour l'insertion
        $columns = array_keys(get_object_vars($object));
        $values = array_values(get_object_vars($object));

        // Filtrer les colonnes et valeurs, ignorer les `NULL`
        foreach ($columns as $key => $col_name) {
            if (is_null($values[$key])) {
                unset($columns[$key]);  // Supprimer la colonne
                unset($values[$key]);   // Supprimer la valeur correspondante
            }
        }

        $columns_str = $this->escapedColumns($columns);
        $placeholders = implode(", ", array_map(function ($v) { return "'" . addslashes($v) . "'"; }, $values)); // Échappe les valeurs

        // Utilisation de INSERT IGNORE pour éviter l'échec si l'objet existe déjà
        /*
        if ($log) {
            $query = "INSERT INTO $this->table ($columns_str) VALUES ($placeholders);";
        } else {
            $query = "INSERT IGNORE INTO $this->table ($columns_str) VALUES ($placeholders);";
        }
        */

        // Utilisation de INSERT INTO pour bien voir toutes les merdes et les éviter.
        $query = "INSERT INTO $this->table ($columns_str) VALUES ($placeholders);";

        logMessage('INSERT Query = > '.$query);

        // Exécution de la requête d'insertion
        $lastInsertId = $this->conn->executeQuery($query);

        logMessage(serialize($lastInsertId));

        return $lastInsertId; // Retourne l'ID inséré ou null si rien n'a été inséré
    }

    public function deleteCollection($collection, $lvl = 0)
    {
        // Refuse to delete an empty collection
        if (empty($collection)) {
            return;
        }


        // Get the key fields from the first object
        $keyFields = $collection[0]->keyFields(); // ['key'] or ['col', 'row'] ...
        // Construct the WHERE clause dynamically
        $whereClause = implode(' AND ', array_map(fn ($field) => "$field = ?", $keyFields));

        // If collection is "too big" (value to adapt)
        if (count($collection) > BATCH_SIZE) {
            // Chunk collection in batchsize packets.
            $chunk = array_slice($collection, 0, BATCH_SIZE);
            $remaining = array_slice($collection, BATCH_SIZE);
            logMessage("(" . $lvl . ") Deleting a batch of " . count($chunk) . " items...");
            $this->deleteCollection($chunk, $lvl);
            $lvl = $lvl + 1;
            // Recursively delete the remaining
            $this->deleteCollection($remaining, $lvl);


            return;
        }

        // Prepare the SQL delete query for all objects at once
        $keys = array_map(function ($object) { return $object->key(); }, $collection);

        $query = "DELETE FROM $this->table WHERE $whereClause";
        $params = [];

        foreach ($collection as $object) {
            $object_keys = $object->keyFields();
            $value_types = '';
            $values = [];
            foreach ($object_keys as $keyfield) {
                $values[] = $object->$keyfield;
                $value_types .= $this->getParamType($object->$keyfield);
            }
            $params[] = $values;
        }
        // Now call bdd_io to execute the query
        $results = $this->conn->prepareAndExecute($query, $value_types, $params);



    }


    /*

        ok j'ai trouvé la solution a mon prbleme d'iD il faut que je note lors des
        preparation les ID n'egatif, et que je fasse une table de correspondance
        avec l'id qui existe au last_insert_id

        et enfin on donnera ça au board qui fera la correspondance
        Et il faut tester le tout avec :
            - une BDD vierge,
            - une BDD pas vierge, mais un MAXCOLMAXROW qu iest plus grand que précédent
            - une BDD pas vierge, mais un MAXCOLMAXROW qu iest plus petit que précédent
            - Une BDD qui a eu un TRUNCATE sur la table Locatable.

    */

    public function saveCollection($collection, $original_mapping_table = [], $lvl = 0)
    {
        // Refuse to save an empty collection
        if (empty($collection)) {
            return [];
        }
        $return_mapping_table = $original_mapping_table;

        // If collection is "too big" (value to adapt)
        if (count($collection) > BATCH_SIZE) {
            // Chunk collection in batchsize packets.
            // CAREFULL ! Array lost keys in the process, it's not a problem
            // for now, but must be reminded in case of anything.
            $chunk = array_slice($collection, 0, BATCH_SIZE);
            $remaining = array_slice($collection, BATCH_SIZE);
            logMessage("(".$lvl.") Saving a batch of " . count($chunk) . " items...");

            $tmp_ret_mapping_table = $this->saveCollection($chunk, $return_mapping_table, $lvl);
            array_merge($tmp_ret_mapping_table, $return_mapping_table);
            $lvl = $lvl + 1;

            // Recursively save the remaining
            $tmp_ret_mapping_table = $this->saveCollection($remaining, $return_mapping_table, $lvl);
            array_merge($tmp_ret_mapping_table, $return_mapping_table);
            return $return_mapping_table;
        }

        // Prepare the SQL insert query for all objects at once
        $firstElement = reset($collection);
        $columns = array_keys(get_object_vars($firstElement));
        $columns_str = $this->escapedColumns($columns);
        $placeholders = "(".implode(", ", array_fill(0, count($columns), "?")).")";

        $query = "INSERT INTO $this->table ($columns_str) VALUES $placeholders ON DUPLICATE KEY UPDATE ";

        // Adding the update part for each column
        $update_values = [];
        foreach ($columns as $column) {
            $update_values[] = "`$column` = VALUES(`$column`)";
        }
        $query .= implode(", ", $update_values);


        // Finding data and datatype:
        list($value_types, $params) = $this->buildBindParams($collection);

        // Now call bdd_io to execute the query
        $return_mapping_table = $this->conn->prepareAndExecute($query, $value_types, $params, $return_mapping_table);


        return $return_mapping_table;
    }

    /* -------------------------------------------------------------------------
        Helper function to build parameters for the prepared query.

        @param array $collection An array of objects to process.
        @return array An array with two elements:
                      - A string of parameter types.
                      - A nested array of parameter values.
    ------------------------------------------------------------------------- */
    private function buildBindParams($collection)
    {
        $params = [];

        foreach ($collection as $object) {
            $object_vars = get_object_vars($object);
            $types = '';
            $values = [];

            foreach ($object_vars as $key => $value) {
                $values[] = $value;
                $types .= $this->getParamType($value);
            }

            $params[] = $values;
        }

        # logMessage(json_encode($values));

        return [$types, $params];
    }

    /* -------------------------------------------------------------------------
        Method to determine the type of each parameter for bind_param.

        @param mixed $value The value to analyze.
        @return string The type of the parameter ('i' for integer, 'd' for double,
                       's' for string, or '' for unknown types).
    ------------------------------------------------------------------------- */
    private function getParamType($value)
    {
        if (is_int($value) or is_bool($value)) {
            return 'i';  // Integer
        } elseif (is_double($value)) {
            return 'd';  // Double
        } elseif (is_string($value)) {
            return 's';  // String
        } elseif (is_null($value)) {
            return 's';  // For null, we can bind as string (but we can modify this logic if needed)
        }
        return '';  // If it's an unknown type, return empty (or handle it in a way you want)
    }

    private function escapedColumns($columns)
    {

        $escaped_columns = array_map(function ($column) {
            return "`$column`"; // Ajoute des backticks autour de chaque colonne
        }, $columns);
        $columns_str = implode(", ", $escaped_columns);
        return $columns_str;
    }
}
