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
    private $errorMessage;
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
        $this->connection = new PDO($connectionString, $this->user, $this->password);
        if ($this->connection) {
            //placeholder
            //echo "success";
        } else {
            //placeholder
            //echo "no success";
        }
        //placeholder
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::CASE_NATURAL);
        $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function __set($name, $value) {
        
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
            case "lastId" : return $this->PDO->lastInsertId();
            case "rows" : return $this->PDO->rowCount();
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
        if (empty($query)) {
            $this->lastQuery = $this->currentQuery;
        } else {
            $this->lastQuery = $query;
        }
        $this->errorMessage = false;
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
    }

    public function getResult($fetchMethod = null) {
        $this->displayErrorMessage();

//        if ($this->keyword === "select") {
//            if ($fetchMethod === "firstRow") {
//                $this->data = $this->PDO->fetch(PDO::FETCH_ASSOC);
//            } else {
//                $this->data = $this->PDO->fetchAll(PDO::FETCH_ASSOC);
//            }
//        }

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
        print_r($this->data);
        return $this->data;
    }

    private function executeSelectWithoutParams() {
        $this->PDO = $this->connection->query($this->lastQuery);
        if ($this->connection->errorCode() !== "00000") {
            $this->errorMessage = $this->connection->errorInfo();
        } else {
            $this->data = $this->PDO->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    private function executeSelectWithParams() {
        $this->PDO = $this->connection->prepare($this->lastQuery);
        if (!$this->PDO) {
            $this->errorMessage = $this->PDO->errorInfo();
        } else {
            $this->PDO->execute($this->lastParams);
        }
    }

    private function executeDeleteInsetUpdateWithoutParams() {
        $val = $this->connection->exec($this->lastQuery);
        if ($this->connection->errorCode() !== "00000") {
            $this->errorMessage = $this->connection->errorInfo();
        } else {
            $this->data = $val;
            $this->lastInsertedId();
        }
    }

    //TODO : error message when PDO is boolean
    private function executeDeleteInsertUpdateWithParams() {
        $this->PDO = $this->connection->prepare($this->lastQuery);
        if (!$this->PDO) {
            $this->errorMessage = $this->PDO->errorInfo();
        } else {
            $this->data = $this->PDO->execute($this->lastParams);
            $this->lastInsertedId();
        }
    }

    private function displayErrorMessage() {
        if ($this->errorMessage && $this->debugMode) {
            print_r($this->errorMessage);
            echo $this->lastQuery;
            print_r($this->lastParams);
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

    private function lastInsertedId() {
        if ($this->keyword === 'insert') {
            $this->lastId = $this->connection->lastInsertId();
        }
    }

    /**
     *  Takes an array as parameter and insert the data into the database
     * @param array $params
     *      table => the name of the table where you want to insert the data;
     *      values => an array where the keys are your databaseField and the values are the values to insert
     *      id =>  name of the id. default : id
     *      mutiple => bool. false or null if you only have one row to insert, true otherwise and if values is an array of array. It will detect array of array automaticly
     *      reverse => bool. false or null if the values are [[name => [0 => 'bob',1=> 'jacques'], [age] => [0 => 12,1=> 20]], true if they are like [["name" => "bob", "age" => 12], ["name" => "jacques", "age" => 20]]
     * @example void $db->insertFromArray(["table" => "tasks", "values" => ["name" => "bob", "age" => 12]])->execute();
     * @return database this
     *
     */
    public function insertFromArray($params) {
        $arrayValues = [];
        $realValues;
        $query = "INSERT INTO " . $params["table"];
        $columns = isset($params["id"]) && $params["id"] ? '(' . $params["id"] : "(id,";
        $defaultValues = "(NULL,";
        $values = $defaultValues;
        $multiple = isset($params["multiple"]) && $params["multiple"];
        $multipleIncrementation = 0;

        if (!isset($params["reverse"]) || !$params["reverse"]) {
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
                    $arrayValues[":$multipleField" . $multipleIncrementation] = $multipleValue;
                }
                $values = substr($values, 0, -1) . ")";
                $values .= "," . $defaultValues;
                $multipleIncrementation++;
            } else {
                $columns .= $field . ',';
                $values .= ":$field,";
                $arrayValues[":$field"] = $value;
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
        $this->currentParams = $arrayValues;
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
        
    }

}
