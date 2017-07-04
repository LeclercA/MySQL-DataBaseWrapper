<?php

class database {

    private $data;
    private $dataBaseName;
    private $dataBaseType = "mysql";
    private $host = "localhost";
    private $user = "root";
    private $password;
    private $charSet = "utf8";
    private $port = "3306";
    private $connection;
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
    private $lastInsertedID;
    private $keyword;
    private $debugMode = false;

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
            echo "success";
        } else {
            //placeholder
            echo "no success";
        }
        //placeholder
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::CASE_NATURAL);
        $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function __set($name, $value) {
        switch ($name) {
            case "dataBaseName" : $this->dataBaseName = $value;
                break;
            case "dataBaseType" : $this->dataBaseType = $value;
                break;
            case "host" : $this->host = $value;
                break;
            case "user" : $this->user = $value;
                break;
            case "password" : $this->password = $value;
                break;
            case "charSet" : $this->charSet = $value;
                break;
            case "port" : $this->port = $value;
                break;
            case "debugMode" : $this->debugBug = $value;
                break;
        }
    }

    public function __get($name) {
        switch ($name) {
            case "dataBaseName" : return $this->dataBaseName;
            case "dataBaseType" : return $this->dataBaseType;
            case "host" : return $this->host;
            case "user" : return $this->user;
            case "password" : return $this->password;
            case "charSet" : return $this->charSet;
            case "port" : return $this->port;
            case "numberOfQueries" : return $this->numberOfQueries;
            case "numberOfSuccessfulQueries" : return $this->numberOfSuccessfulQueries;
            case "numbersOfSelectQueries" : return $this->numbersOfSelectQueries;
            case "numberOfSuccessfulSelectQueries" : return $this->numberOfSuccessfulSelectQueries;
            case "numbersOfDeleteQueries" : return $this->numbersOfDeleteQueries;
            case "numberOfSuccessfulDeleteQueries" : return $this->numberOfSuccessfulDeleteQueries;
            case "numbersOfInsertQueries" : return $this->numbersOfInsertQueries;
            case "numberOfSuccessfulInsertQueries" : return $this->numberOfSuccessfulInsertQueries;
            case "numbersOfUpdateQueries" : return $this->numbersOfUpdateQueries;
            case "numberOfSuccessfulUpdateQueries" : return $this->numberOfSuccessfulUpdateQueries;
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

    public function getResult() {
        $this->displayErrorMessage();
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
        return $this->data;
    }

    private function executeSelectWithoutParams() {
        $pdoObj = $this->connection->query($this->lastQuery);
        if ($this->connection->errorCode() !== "00000") {
            $this->errorMessage = $this->connection->errorInfo();
        } else {
            $this->data = $pdoObj->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    private function executeSelectWithParams() {
        $pdoObj = $this->connection->prepare($this->lastQuery);
        if (!$pdoObj) {
            $this->errorMessage = $pdoObj->errorInfo();
        } else {
            $pdoObj->execute($this->lastParams);
            $this->data = $pdoObj->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    private function executeDeleteInsetUpdateWithoutParams() {
        $val = $this->connection->exec($this->lastQuery);
        if ($this->connection->errorCode() !== "00000") {
            $this->errorMessage = $this->connection->errorInfo();
        } else {
            $this->data = $val;
        }
    }

    private function executeDeleteInsertUpdateWithParams() {
        $pdoObj = $this->connection->prepare($this->lastQuery);
        if (!$pdoObj) {
            $this->errorMessage = $pdoObj->errorInfo();
        } else {
            $val = $pdoObj->execute($this->lastParams);
            $this->data = $val;
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

}
