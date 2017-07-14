
<?php

class utilities {

    /**
     * Verify is the array provided is associative or not
     * @param array The array the verify
     * @return boolean true if array is associative, false if not.
     */
    protected function isAssoc(array $arr) {
        if (array() === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    protected function rotateArray($array) {
        $newArray = [];
        foreach ($array as $reverseKey => $reverseValue) {
            foreach ($reverseValue as $reverseSubKey => $reverseSubValue) {
                $newArray[$reverseSubKey][$reverseKey] = $reverseSubValue;
            }
        }
        return $newArray;
    }

    protected function checkForSubArray($array) {
        return is_array(reset($array));
    }

    protected function sanitizeInput($input, $type = null) {
        if (is_string($input)) {
            if (preg_match("/^\S+@\S+[\.]\S+$/", $input)) {
                echo "huston, we got an email";
            }
        }
        return $input;
    }

    protected function cleanArray($arrayToClean, $arrayToCheckKeysFor) {
        if (!$this->isAssoc($arrayToCheckKeysFor)) {
            $arrayToCheckKeysFor = array_flip($arrayToCheckKeysFor);
        }
        foreach ($arrayToClean as $cleanKey => $cleanValue) {
            if (!array_key_exists($cleanKey, $arrayToCheckKeysFor)) {
                unset($arrayToClean[$cleanKey]);
            }
        }
        return $arrayToClean;
    }

    protected function escapeBackSticks($var) {
        return "`" . str_replace("`", "``", $var) . "`";
    }

}

class database extends utilities {

    public $dataBaseName;
    public $host = "localhost";
    public $user = "root";
    public $password;
    public $charSet = "utf8";
    public $port = "3306";
    public $debugMode = false;
    private $dataBaseType = "mysql";
    private $data;
    private $connection;
    private $PDO;
    private $currentErrorMessage;
    private $lastErrorMessage;
    private $currentParams;
    private $currentQuery;
    private $lastParams;
    private $lastQuery;
    private $keyword;

    /**
     * Constructor
     *
     *
     * @param array $options :
     *      Include the following options, in no particuliar ordor :
     *          dataBaseName : the name of the schema [no default value]
     *          dataBaseType : the type of database [default : "mysql"]
     *          host : the name of the host [default : "localhost"]
     *          user : the user that is going to use the database [default : "root"]
     *          charSet : the charset of the database [default : "utf8"];
     *          port : the port for the database [default : "3306"]
     *          password : the password to connect to the database [no default value]
     */
    public function __construct($options = null) {
        $connectionString = $this->contructSetter($options);
        try {
            $this->connection = new PDO($connectionString, $this->user, $this->password);
        } catch (Exception $e) {
            trigger_error("Impossible to connect to the databse.");
        }
        //Cannot be removed
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function __get($name) {
        switch ($name) {
            case "data" : return $this->data;
            case "lastId" : return $this->connection->lastInsertId();
            case "rows" : return $this->PDO->rowCount();
            case "currentErrorMessage" : return $this->currentErrorMessage;
            case "lastErrorMessage" : return $this->lastErrorMessage;
        }
    }

    public function execute($query = null, $params = null) {
        if ($this->connection) {
            if (empty($query)) {
                $this->lastQuery = $this->currentQuery;
            } else {
                $this->lastQuery = $query;
            }
            $this->lastErrorMessage = $this->currentErrorMessage;
            $this->currentErrorMessage = NULL;
            $this->keyword = strtolower(explode(' ', $this->lastQuery)[0]);
            if ($this->keyword === "select" || $this->keyword === "show") {
                if (empty($params) && empty($this->currentParams)) {
                    $this->executeSelectWithoutParams();
                } else {
                    $this->lastParams = empty($this->currentParams) ? $params : $this->currentParams;
                    $this->executeSelectWithParams();
                }
            } else {
                if (empty($params) && empty($this->currentParams)) {
                    $this->executeDeleteInsetUpdateWithoutParams();
                } else {
                    $this->lastParams = empty($this->currentParams) ? $params : $this->currentParams;
                    $this->executeDeleteInsertUpdateWithParams();
                }
            }
            return $this;
        } else {
            if ($this->debugMode) {
                echo "No connection to the database, can't do queries";
            } else {
                trigger_error("No connection to the database, can't do queries");
            }
        }
    }

    public function getResult($fetchMethod = null) {
        $this->displayErrorMessage();
        if ($this->keyword === "select" || $this->keyword === "show") {
            if ($fetchMethod === "firstRow") {
                $this->data = $this->PDO->fetch(PDO::FETCH_ASSOC);
            } else {
                $this->data = $this->PDO->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        $this->resetParams();
        return $this->data;
    }

    private function executeSelectWithoutParams() {
        try {
            $this->PDO = $this->connection->query($this->lastQuery);
            if ($this->connection->errorCode() !== "00000") {
                $this->currentErrorMessage = $this->connection->errorInfo();
            }
        } catch (PDOException $e) {
            $this->currentErrorMessage = $e;
            trigger_error($e);
        }
    }

    private function executeSelectWithParams() {
        try {
            $this->PDO = $this->connection->prepare($this->lastQuery);
            if (!$this->PDO) {
                $this->currentErrorMessage = $this->PDO->errorInfo();
            } else {
                $this->PDO->execute($this->lastParams);
            }
        } catch (PDOException $e) {
            $this->currentErrorMessage = $e;
            trigger_error($e);
        }
    }

    private function executeDeleteInsetUpdateWithoutParams() {
        try {
            $val = $this->connection->exec($this->lastQuery);
            if ($this->connection->errorCode() !== "00000") {
                $this->currentErrorMessage = $this->connection->errorInfo();
            } else {
                $this->data = $val;
            }
        } catch (PDOException $e) {
            $this->currentErrorMessage = $e;
            trigger_error($e);
        }
    }

    private function executeDeleteInsertUpdateWithParams() {
        try {
            $this->PDO = $this->connection->prepare($this->lastQuery);
            if (!$this->PDO) {
                $this->currentErrorMessage = $this->connection->errorInfo();
            } else {
                $this->data = $this->PDO->execute($this->lastParams);
            }
        } catch (PDOException $e) {
            $this->currentErrorMessage = $e;
            trigger_error($e);
        }
    }

    private function displayErrorMessage() {
        if ($this->currentErrorMessage && $this->debugMode) {
            print_r($this->currentErrorMessage);
            echo "QUERY => " . $this->lastQuery;
            print_r($this->lastParams);
            trigger_error($this->currentErrorMessage);
        }
    }

    /**
     *  Takes an array as parameter and insert the data into the database
     * @param array $params
     *      table => the name of the table where you want to insert the data;
     *      values => an array where the keys are your databaseField and the values are the values to insert
     *      id =>  set the primary key. default : id, with value NULL. if set to false, no primary key with no value
     *      reverse => bool. false or null or not exist if the element to insert are ready (ex : [0 => ['name'=> 'bob', 'age' => 12], 1 => ['name' => 'pablo', age => 13]] AND THAT ITS AN ASSOCIATIVE ARRAY ( index starts from 0 and go up by 1 each time)
     *      If its an associative array, but the element are ready... nothing is going to work.
     *      Set reverse to true if your data comes from a form (ex : ["name" => ["bob", "pablo"], "age" => [12,13]]
     *      reverse is detected automaticly
     * @example void $db->insertFromArray(["table" => "tasks", "values" => ["name" => "bob", "age" => 12], "reverse" => true])->execute();
     * @return database this
     *
     */
    public function insertFromArray($params) {
        $this->currentParams = [];
        $table = $this->escapeBackSticks($params["table"]);

        $columns = "(";
        $defaultValues = "(";
        $columnsOtherForQuery = "";
        $columnInfo = $this->getTableInfo($table);

        $rotatedValues = !$this->isAssoc($params["values"]) ? $this->rotateArray($params["values"]) : $params["values"];

        //no memory leaks here boys
        unset($params);

        //Having the same order is crucial. Sorting them by the key
        ksort($columnInfo);
        ksort($rotatedValues);



        $columnsNameFromParams = array_keys($rotatedValues);

        //For each column of the table, check if the name fits the column of the data. Also checks for autoincremented primary key
        //Could use array_intersect_key , but that would be two loop, because i would still need to check for the primary key
        $newValues = [];
        foreach ($columnInfo as $columnKey => $columnValue) {
            if (in_array($columnKey, $columnsNameFromParams)) {
                $columnsOtherForQuery .= $this->escapeBackSticks($columnKey) . ",";
                $newValues[$columnKey] = $rotatedValues[$columnKey];
            } elseif ($columnValue["primaryKey"] && $columnValue["autoIncrement"]) {
                $columns .= $this->escapeBackSticks($columnKey) . ',';
                $defaultValues .= "NULL,";
            }
        }
        $columns = substr($columns . $columnsOtherForQuery, 0, -1) . ")";
        $valuesToInsert = $defaultValues;
        $multipleIncrementation = 0;
        $multiple = false;
        $newValues = $this->checkForSubArray($newValues) ? $this->rotateArray($newValues) : $newValues;
        foreach ($newValues as $field => $value) {
            if (is_array($value)) {
                $multiple = true;
                foreach ($value as $multipleField => $multipleValue) {
                    //Same as ...
                    $valuesToInsert .= ":$multipleField" . "$multipleIncrementation,";
                    $this->currentParams[":$multipleField" . $multipleIncrementation] = empty($multipleValue) ? NULL : $multipleValue;
                }
                $valuesToInsert = substr($valuesToInsert, 0, -1) . ")";
                $valuesToInsert .= "," . $defaultValues;
                $multipleIncrementation++;
            } else {
                //...this
                $valuesToInsert .= ":$field,";
                $this->currentParams[":$field"] = empty($value) ? NULL : $value;
            }
        }
        if ($multiple) {
            $valuesToInsert = substr($valuesToInsert, 0, -(strlen($defaultValues)) - 1);
        } else {
            $valuesToInsert = substr($valuesToInsert, 0, -1) . ")";
        }
        $columns = substr($columns, 0, -1) . ")";
        echo $this->currentQuery = "INSERT INTO $table $columns VALUES $valuesToInsert";
        echo "<br>";
        print_r($this->currentParams);
        return $this;
    }

    /**
     *  Takes an array as parameter and update the data into the database
     * @param array $params
     *      table => the name of the table where you want to update the data;
     *      values => an array where the keys are your databaseField and the values are the values to update
     *      condition => and array where the key is the databaseField and to value is the expression to look for
     *
     * @return void No return
     */
    public function updateFromArray($params) {
        $query = "UPDATE " . $params["table"];
        $set = " SET ";
        $parameters = [];
        $incrementation = 0;
        $where = " WHERE ";
        foreach ($params["values"] as $field => $value) {
            $set .= $field . " = " . ":$field$incrementation,";
            $parameters[":$field$incrementation"] = empty($value) ? NULL : $value;
            $incrementation++;
        }
        foreach ($params["where"] as $field => $value) {
            $where .= $field . " = " . ":$field$incrementation ";
            $parameters[":$field$incrementation"] = empty($value) ? NULL : $value;
            $incrementation++;
        }
        $set = substr($set, 0, -1);
        $query .= $set . $where;
        $this->currentQuery = $query;
        $this->currentParams = $parameters;
        return $this;
    }

    private function contructSetter($options) {
        $dataBaseString = "";
        if ($options !== null) {
            if (isset($options["dataBaseName"]) && !empty($options["dataBaseName"])) {
                $this->dataBaseName = $options["dataBaseName"];
                $dataBaseString = ";dbname=" . $this->dataBaseName;
            }
            if (isset($options["host"]) && !empty($options["host"])) {
                $this->host = $options["host"];
            }
            if (isset($options["charSet"]) && !empty($options["charSet"])) {
                $this->charSet = $options["charSet"];
            }
            if (isset($options["port"]) && !empty($options["port"])) {
                $this->port = $options["port"];
            }
            if (isset($options["user"]) && !empty($options["user"])) {
                $this->user = $options["user"];
            }
            if (isset($options["password"]) && !empty($options["password"])) {
                $this->password = $options["password"];
            }
        }
        return $this->dataBaseType . ":host=" . $this->host . $dataBaseString . ";charset=" . $this->charSet . ";port=" . $this->password;
    }

    private function resetParams() {
        $this->lastQuery = $this->currentQuery;
        $this->currentQuery = NULL;
        $this->lastParams = $this->currentParams;
        $this->currentParams = NULL;
        $this->lastErrorMessage = $this->currentErrorMessage;
        $this->currentErrorMessage = NULL;
    }

    private function getTableInfo($table) {
        $this->PDO = $this->connection->query("SHOW COLUMNS FROM $table");
        $columns = [];
        $info = $this->PDO->fetchAll(PDO::FETCH_ASSOC);
        foreach ($info as $key => $value) {
            $name = $value["Field"];
            $columns[$name]["type"] = explode('(', $value["Type"])[0];
            $columns[$name]["primaryKey"] = $value["Key"] === "PRI";
            $columns[$name]["autoIncrement"] = $value["Extra"] === "auto_increment";
        }
        return $columns;
    }

}
