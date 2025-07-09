<?php

/* =============================================================================
    Class bdd_io

    This class provides a generic interface for interacting with the database.
    It handles connection management, query execution, result fetching, and
    prepared statements with support for transactions.

    Responsibilities:
        - Establishing and maintaining the database connection.
        - Executing raw SQL queries securely and efficiently.
        - Fetching results in a structured format (e.g., associative arrays).
        - Managing transactions for atomic operations.
        - Logging query execution time and errors for debugging purposes.

    This class is intentionally kept generic to support a wide range of use
    cases and should not contain logic specific to any business domain.
============================================================================= */
class bdd_io
{
    private $connection;

    /* -------------------------------------------------------------------------
        Constructor: Establishes a database connection.

        @param string $host Database host.
        @param string $username Database username.
        @param string $password Database password.
        @param string $database Database name.
        @throws Exception if the connection fails.
    ------------------------------------------------------------------------- */
    public function __construct()
    {
        // --
    }

    /* -------------------------------------------------------------------------
        Executes a raw SQL query.

        @param string $query The SQL query to execute.
        @return mixed Query result or true for non-SELECT queries.
        @throws Exception if the query fails.
    ------------------------------------------------------------------------- */
    public function query($query)
    {
        $results = $this->executeQuery($query);
        return $results;
    }

    /* -------------------------------------------------------------------------
        Executes a query and measures its execution time.

        @param string $query The SQL query to execute.
        @return mixed Query result or true for non-SELECT queries.
        @throws Exception if the query fails.
    ------------------------------------------------------------------------- */
    public function executeQuery($query)
    {

        // allowlogs();

        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            throw new Exception("Connection failed: " . $this->connection->connect_error);
        }

        logMessage("SQL Query : ".$query);
        $startTime = microtime(true);

        $result = $this->connection->query($query);

        if ($result === false) {
            logMessage("Query error: " . $this->connection->error);
            $this->connection->close();
            // disableLogs();
            return;
        }

        $executionTime = round((microtime(true) - $startTime) * 1000);
        logMessage("SQL Query done in " . $executionTime . " ms.");

        // Si c'est une requête INSERT, retourne le dernier ID inséré
        if ($this->connection->insert_id) {
            logMessage('INSERT_ID=' . $this->connection->insert_id);
            $inserted_id = $this->connection->insert_id;
            $this->connection->close();
            // disableLogs();
            return $inserted_id;
        }

        // Si c'est une requête SELECT, retourne les résultats
        if ($result instanceof mysqli_result) {
            $this->connection->close();
            // disableLogs();
            return $this->fetchAll($result);
        }

        // Pour les autres types de requêtes (UPDATE, DELETE), retourne true
        $this->connection->close();


        // disableLogs();
        return true;
    }



    /* -------------------------------------------------------------------------
        Fetches all rows from a result set as an associative array.

        @param mysqli_result $result The result set to process.
        @return array An array of rows.
    ------------------------------------------------------------------------- */
    private function fetchAll($result)
    {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /* -------------------------------------------------------------------------
        Prepares and executes a parameterized query using transactions.

        @param string $query The SQL query to prepare.
        @param string $value_type A string defining parameter types (e.g., "s" for string).
        @param array $params An array of parameter sets.
        @return bool True on success.
        @throws Exception if the query fails or the transaction is rolled back.
    ------------------------------------------------------------------------- */
    public function prepareAndExecute($query, $value_type, $params = [], $mapping_table = [])
    {

        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            throw new Exception("Connection failed: " . $this->connection->connect_error);
        }


        // logMessage("SQL Query : ".$query);

        // $startTime = microtime(true);


        $this->connection->begin_transaction();
        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("Query preparation error: " . $this->connection->error);
            }

            foreach ($params as $param) {

                $id_to_map = $param[0];
                if ($param[0] < 0) {
                    $param[0] = 0;
                }

                $stmt->bind_param($value_type, ...$param);
                $stmt->execute();

                // SAUVEGARDER DANS UNE VARIABLE GLOBAL LE DERNIER ID A SUCCES ?
                // ET LE DEFINIR LORS DE L'INIT DU CODE ?
                $last_id = $stmt->insert_id;

                $mapping_table[$id_to_map] = $last_id;

            }

            $this->connection->commit();
            $this->connection->close();

            return $mapping_table;

        } catch (Exception $e) {
            $this->connection->rollback();
            $this->connection->close();
            throw $e;
        }
    }

}
