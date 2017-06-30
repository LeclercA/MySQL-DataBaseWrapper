<?php

class database {

    private $data;
    
    private $dbname;
    private $dataBaseType = "mysql";
    private $host = "localhost";
    private $user = "root";
    private $password = "4FdL,1fE";
    private $connection;
    
    private $errorMessage;
    
    private $currentParams;
    private $currentQuery;
    private $lastParams;
    private $lastQuery;
    private $lastInsertedID;
    private $debugMode = false;

    public function __construct($dbname, $dbtype = null, $host = null, $user = null, $password = null) {
        if (!empty($host)) {
            $this->host = $host;
        }
        if (!empty($user)) {
            $this->user = $user;
        }
        if (!empty($password)) {
            $this->password = $password;
        }
        if (!empty($type)) {
            $this->dataBaseType = $dbtype;
        }
        
        $this->dbname = $dbname;
        $connectionString = $this->dataBaseType . ":host=" . $this->host . ";dbname=" . $this->dbname . ";charset=utf8";
        $this->connection = new PDO($connectionString, $this->user, $this->password);
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
