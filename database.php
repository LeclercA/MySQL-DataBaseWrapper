<?php

class database {

    private $data;
    public $dataBaseName;
    public $dataBaseType = "mysql";
    public $host = "localhost";
    public $user = "root";
    public $password;
    public $charSet = "utf8";
    public $port = "3306";
    private $connection;
    private $PDO;
    private $currentErrorMessage;
    private $lastErrorMessage;
    private $currentParams;
    private $currentQuery;
    private $lastParams;
    private $lastQuery;
    private $numbersOfQueries = 0;
    private $numberOfSuccessfulQueries = 0;
    private $numberOfSelectQueries = 0;
    private $numberOfSuccessfulSelectQueries = 0;
    private $numberOfInsertQueries = 0;
    private $numberOfSuccessfulInsertQueries = 0;
    private $numberOfUpdateQueries = 0;
    private $numberOfSuccessfulUpdateQueries = 0;
    private $numberOfDeleteQueries = 0;
    private $numberOfSuccessfulDeleteQueries = 0;
    private $keyword;
    public $debugMode = false;

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
        //placeholder
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function __get($name) {
        switch ($name) {
            case "numberOfQueries" : return $this->numberOfQueries;
            case "numberOfSuccessfulQueries" : return $this->numberOfSuccessfulQueries;
            case "numberOfSelectQueries" : return $this->numberOfSelectQueries;
            case "numberOfSuccessfulSelectQueries" : return $this->numberOfSuccessfulSelectQueries;
            case "numberOfDeleteQueries" : return $this->numberOfDeleteQueries;
            case "numberOfSuccessfulDeleteQueries" : return $this->numberOfSuccessfulDeleteQueries;
            case "numberOfInsertQueries" : return $this->numberOfInsertQueries;
            case "numberOfSuccessfulInsertQueries" : return $this->numberOfSuccessfulInsertQueries;
            case "numberOfUpdateQueries" : return $this->numberOfUpdateQueries;
            case "numberOfSuccessfulUpdateQueries" : return $this->numberOfSuccessfulUpdateQueries;
            case "lastId" : return $this->connection->lastInsertId();
            case "rows" : return $this->PDO->rowCount();
            case "currentErrorMessage" : return $this->currentErrorMessage;
            case "lastErrorMessage" : return $this->lastErrorMessage;
        }
    }

    public function query($query) {
        $this->currentQuery = $query;
        return $this;
    }

    public function params($params) {
        $this->currentParams = $params;
        return $this;
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
            $this->numbersOfQueries++;
            $this->keyword = strtolower(explode(' ', $this->lastQuery)[0]);
            switch ($this->keyword) {
                case "select" :
                    $this->numberOfSelectQueries++;
                    if (empty($params) && empty($this->currentParams)) {
                        $this->executeSelectWithoutParams();
                    } else {
                        $this->lastParams = empty($this->currentParams) ? $params : $this->currentParams;
                        $this->executeSelectWithParams();
                    }
                    break;
                case "delete": $this->numberOfDeleteQueries++;
                case "insert" : $this->numberOfInsertQueries++;
                case "update":$this->numberOfUpdateQueries++;
                    if (empty($params) && empty($this->currentParams)) {
                        $this->executeDeleteInsetUpdateWithoutParams();
                    } else {
                        $this->lastParams = empty($this->currentParams) ? $params : $this->currentParams;
                        $this->executeDeleteInsertUpdateWithParams();
                    }
                    break;
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
        if ($this->keyword === "select") {
            if ($fetchMethod === "firstRow") {
                $this->data = $this->PDO->fetch(PDO::FETCH_ASSOC);
            } else {
                $this->data = $this->PDO->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        if ($this->data) {
            switch ($this->keyword) {
                case "select" :$this->numberOfSuccessfulSelectQueries++;
                    break;
                case "delete": $this->numberOfSuccessfulDeleteQueries++;
                    break;
                case "insert" : $this->numberOfSuccessfulInsertQueries++;
                    break;
                case "update":$this->numberOfSuccessfulUpdateQueries++;
                    break;
            }
            $this->numberOfSuccessfulQueries++;
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
            $this->throwError($e);
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
            $this->throwError($e);
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
            $this->throwError($e);
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
            $this->throwError($e);
        }
    }

    private function displayErrorMessage() {
        if ($this->currentErrorMessage && $this->debugMode) {
            print_r($this->currentErrorMessage);
            echo "QUERY => " . $this->lastQuery;
            print_r($this->lastParams);
            $this->throwError($this->currentErrorMessage);
        }
    }

    private function contructSetter($options) {
        $dataBaseString = "";
        if (!empty($options["dataBaseType"]) && isset($options["dataBaseType"])) {
            $this->dataBaseType = $options["dataBaseType"];
        }
        if (!empty($options["dataBaseName"]) && isset($options["dataBaseName"])) {
            $this->dataBaseName = $options["dataBaseName"];
            $dataBaseString = ";dbname=" . $this->dataBaseName;
        }
        if (!empty($options["host"]) && isset($options["host"])) {
            $this->host = $options["host"];
        }
        if (!empty($options["charSet"]) && isset($options["charSet"])) {
            $this->charSet = $options["charSet"];
        }
        if (!empty($options["port"]) && isset($options["port"])) {
            $this->port = $options["port"];
        }
        if (!empty($options["user"]) && isset($options["user"])) {
            $this->user = $options["user"];
        }
        if (!empty($options["password"]) && isset($options["password"])) {
            $this->password = $options["password"];
        }
        return $this->dataBaseType . ":host=" . $this->host . $dataBaseString . ";charset=" . $this->charSet . ";port=" . $this->password;
    }

    /**
     *  Takes an array as parameter and insert the data into the database
     * @param array $params
     *      table => the name of the table where you want to insert the data;
     *      values => an array where the keys are your databaseField and the values are the values to insert
     *      id =>  set the primary key. default : id, with value NULL. if set to false, no primary key with no value
     *      multiple => bool. false or null if you only have one row to insert, true otherwise and if values is an array of array. It will detect array of array by magic
     *      reverse => bool. false or null or not exist if the element to insert are ready (ex : [0 => ['name'=> 'bob', 'age' => 12], 1 => ['name' => 'pablo', age => 13]] AND THAT ITS AN ASSOCIATIVE ARRAY ( index starts from 0 and go up by 1 each time)
     *      If its an associative array, but the element are ready... nothing is going to work.
     *      Set reverse to true if your data comes from a form (ex : ["name" => ["bob", "pablo"], "age" => [12,13]]
     *      reverse is detected automaticly
     * @example void $db->insertFromArray(["table" => "tasks", "values" => ["name" => "bob", "age" => 12], "reverse" => true])->execute();
     * @return database this
     *
     */
    public function insertFromArray($params) {
        $parameters = [];
        $realValues = [];
        $query = "INSERT INTO " . $params["table"];
        $columns = "(id,";
        $defaultValues = "(NULL,";
        if (isset($params["id"])) {
            if (!$params["id"]) {
                $columns = '(';
                $defaultValues = '(';
            } else {
                $columns = '(' . $params["id"] . ',';
            }
        }
        $values = $defaultValues;
        $multiple = isset($params["multiple"]) && $params["multiple"];
        $multipleIncrementation = 0;
        if ((isset($params["reverse"]) && $params["reverse"]) || $this->isAssoc($params["values"])) {
            foreach ($params["values"] as $reverseKey => $reverseValue) {
                foreach ($reverseValue as $rrKey => $rrValue) {
                    $realValues[$rrKey][$reverseKey] = $rrValue;
                }
            }
        } else {
            $realValues = $params["values"];
        }
        foreach ($realValues as $field => $value) {
            if ($multiple || is_array($value)) {
                $multiple = true;
                foreach ($value as $multipleField => $multipleValue) {
                    if (!$multipleIncrementation) {
                        $columns .= $multipleField . ',';
                    }
                    $values .= ":$multipleField" . "$multipleIncrementation,";
                    $parameters[":$multipleField" . $multipleIncrementation] = empty($multipleValue) ? NULL : $multipleValue;
                }
                $values = substr($values, 0, -1) . ")";
                $values .= "," . $defaultValues;
                $multipleIncrementation++;
            } else {
                $columns .= $field . ',';
                $values .= ":$field,";
                $parameters[":$field"] = empty($value) ? NULL : $value;
            }
        }
        if ($multiple) {
            $values = substr($values, 0, -(strlen($defaultValues)) - 1);
        } else {
            $values = substr($values, 0, -1) . ")";
        }
        $columns = substr($columns, 0, -1) . ")";
        $query .= " $columns VALUES $values";
        $this->currentQuery = $query;
        $this->currentParams = $parameters;
        return $this;
    }

    /**
     *  Takes an array as parameter and update the data into the database
     * @param array $params
     *      table => the name of the table where you want to update the data;
     *      values => an array where the keys are your databaseField and the values are the values to update
     *      condition => and array where the key is the databaseField and to value is the expression to look for
     *
     * @return void No return, calls $this->execute;
     */
    public function updateFromArray($params) {
        $query = "UPDATE " . $params["table"] . " SET ";
        $set = "";
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

    private function resetParams() {
        $this->lastQuery = $this->currentQuery;
        $this->currentQuery = NULL;
        $this->lastParams = $this->currentParams;
        $this->currentParams = NULL;
        $this->lastErrorMessage = $this->currentErrorMessage;
        $this->currentErrorMessage = NULL;
    }

    private function throwError($error) {
        trigger_error($error);
    }

    private function isAssoc(array $arr) {
        if (array() === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

}
