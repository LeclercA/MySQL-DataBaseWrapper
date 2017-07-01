<?php

class database {

    private $data;
    
    private $dataBaseName;
    private $dataBaseType = "mysql";
    private $host = "localhost";
    private $user = "root";
    private $password;
    private $connection;
    private $charSet = "utf8";
    private $port = "3306";
    
    private $errorMessage;
    private $currentParams;
    private $currentQuery;
    private $lastParams;
    private $lastQuery;
    private $lastInsertedID;
    private $debugMode = false;

    /**
     * Constructor
     * 
     * 
     * @param array $options :
     *      Include the following options, in no particuliar ordor : 
     *          dataBaseName : the name of the schema 
     *          dataBaseType : the type of database [default : "mysql"]
     *          host : the name of the host [default : "localhost"]
     *          user : the user that is going to use the database [default : "root"]
     *          charSet : the charset of the database [default : "utf8"];
     *          port : the port for the database [default : "3306"]
     *          password : the password to connect to the database [no default value]
     */
    public function __construct($options = null) {
        if (!empty($options["dataBaseName"]) && isset($options["dataBaseName"])) {
            $this->dataBaseName = $options["dataBaseName"];
        }
        if (!empty($options["dataBaseType"]) && isset($options["dataBaseType"])) {
            $this->dataBaseType = $options["dataBaseType"];
        }
        if (!empty($options["host"]) && isset($options["host"])) {
            $this->host = $options["host"];
        }
        if (!empty($options["user"]) && isset($options["user"])) {
            $this->user = $options["user"];
        }
        if (!empty($options["charSet"]) && isset($options["charSet"])) {
            $this->charSet = $options["charSet"];
        }
        if (!empty($options["port"]) && isset($options["port"])) {
            $this->port = $options["port"];
        }
        if (!empty($options["password"]) && isset($options["password"])) {
            $this->password = $options["password"];
        }

        $this->dataBaseName;
        $connectionString = $this->dataBaseType . ":host=" . $this->host . ";dbname=" . $this->dataBaseName . ";charset=" . $this->charSet . ";port=" . $this->password;
        $this->connection = new PDO($connectionString, $this->user, $this->password);
        if($this->connection){
            echo "success";
        }else{
            echo "no success";
        }
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::CASE_NATURAL);
        $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
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
        $firstWord = explode(' ', $this->lastQuery)[0];
        if ("select" === strtolower($firstWord)) {
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
    }

    public function getFirstResult() {
        return array_slice($this->data, 0, 1);
    }

    public function getLastResult() {
        return array_slice($this->data, -1);
    }

    public function getResult() {
        if ($this->errorMessage && $this->debugMode) {
            print_r($this->errorMessage);
            echo $this->lastQuery;
            print_r($this->lastParams);
        }
        return $this->data;
    }

    public function executeSelectWithoutParams() {
        $pdoObj = $this->connection->query($this->lastQuery);
        if ($this->connection->errorCode() !== "00000") {
            $this->errorMessage = $this->connection->errorInfo();
        } else {
            $this->data = $pdoObj->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function executeSelectWithParams() {
        $pdoObj = $this->connection->prepare($this->lastQuery);
        if (!$pdoObj) {
            $this->errorMessage = $pdoObj->errorInfo();
        } else {
            $pdoObj->execute($this->lastParams);
            $this->data = $pdoObj->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function executeDeleteInsetUpdateWithoutParams() {
        $val = $this->connection->exec($this->lastQuery);
        if ($this->connection->errorCode() !== "00000") {
            $this->errorMessage = $this->connection->errorInfo();
        } else {
            $this->data = $val;
        }
    }

    public function executeDeleteInsertUpdateWithParams() {
        $pdoObj = $this->connection->prepare($this->lastQuery);
        if (!$pdoObj) {
            $this->errorMessage = $pdoObj->errorInfo();
        } else {
            $val = $pdoObj->execute($this->lastParams);
            $this->data = $val;
        }
    }

    /**
     *
     * @param Bool $var. TRUE if want want error message, FALSE if you don't want error message */
    public function setDebugMode($var) {
        $this->debugMode = $var;
    }

}
