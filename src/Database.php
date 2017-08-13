<?php

declare (strict_types = 1);

use Utilities;
class Database extends Utilities
{

    public $debugMode = false;
    private $options = [
        "dbname" => "",
        "host" => "localhost",
        "user" => "root",
        "password" => "",
        "charset" => "utf8",
        "port" => ""
    ];
    private $dbType = "mysql";
    private $data;
    private $connection;
    private $PDO;
    private $errorMessage;
    private $lastErrorMessage;
    private $currentParams;
    private $lastQuery;
    private $currentQuery;
    private $keyword;
    private $util;


    const ALL = 0;
    const FIRST_ROW = 1;
    const COLUMN = 2;



    public function __construct(array $options = null)
    {

        $connectionString = $this->dbType . ":";
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
                if ($key !== "user" && $key !== "password" && !empty($value)) {
                    $connectionString .= $key . "=" . $value . ";";
                }
            }
        }
        $connectionString = substr($connectionString, 0, -1);
        $params = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false];
        try {
            $this->connection = new PDO($connectionString, $this->options["user"], $this->options["password"], $params);
        } catch (PDOException $e) {
            $this->errorMessage = $e;
            trigger_error($e);
        }
        $this->util = new Utilities();
    }

    public function __get(string $name) : mixed
    {
        switch ($name) {
            case "data" :
                return $this->data;
            case "lastId" :
                return $this->connection->lastInsertId();
            case "rows" :
                return $this->PDO->rowCount();
            case "errorMessage" :
                return $this->lastErrorMessage;
        }
    }

    public function execute(string $query = null, array $params = null)
    {
        if ($this->connection) {
            if (!empty($query)) {
                $this->currentQuery = $query;
            }
            if (empty($this->currentParams)) {
                $this->currentParams = $params;
            }
            $this->keyword = strtolower(explode(' ', $this->currentQuery)[0]);
            if ($this->keyword === "select" || $this->keyword === "show") {
                $this->executeSelect();
            }
            else {
                $this->executeUpdate();
            }
            return $this;
        }
        else {
            trigger_error("No connection to the database, can't do queries");
        }
    }

    public function getResult(self $fetchMethod = null)
    {
        $this->displayErrorMessage();
        if (empty($this->errorMessage)) {
            if ($this->keyword === "select" || $this->keyword === "show") {
                try {
                    if ($fetchMethod === "firstRow") {
                        $this->data = $this->PDO->fetch(PDO::FETCH_ASSOC);
                    }
                    else {
                        $this->data = $this->PDO->fetchAll(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) {
                    $this->errorMessage = $e;
                    trigger_error($e);
                }
                finally {
                    $this->resetParams();
                }
            }
            return $this->data;
        }
    }

    private function executeSelect()
    {
        try {
            if ($this->currentQuery !== $this->lastQuery) {
                $this->PDO = $this->connection->prepare($this->currentQuery);
            }
            if (!$this->PDO) {
                $this->errorMessage = $this->PDO->errorInfo();
            }
            else {
                $this->PDO->execute($this->currentParams);
            }
        } catch (Exception $e) {
            $this->errorMessage = $e;
            trigger_error($e);
        }
    }

    private function executeUpdate()
    {
        try {
            if ($this->currentQuery !== $this->lastQuery) {
                $this->PDO = $this->connection->prepare($this->currentQuery);
            }
            if (!$this->PDO) {
                $this->errorMessage = $this->connection->errorInfo();
            }
            else {
                $this->data = $this->PDO->execute($this->currentParams);
            }
        } catch (Exception $e) {
            $this->errorMessage = $e;
            trigger_error($e);
        }
    }

    private function displayErrorMessage()
    {
        if ($this->errorMessage && $this->debugMode) {
            print_r($this->errorMessage);
            echo "QUERY => " . $this->currentQuery;
            print_r($this->currentParams);
            trigger_error($this->errorMessage[0]);
        }
    }

    public function insertFromArray(array $params) : Database
    {
        $table = $params["table"];

        $columns = '(';
        $defaultValues = '(';
        $columnsOtherForQuery = "";
        $valuesToInsert = "";
        $multiple = 0;

        $columnInfo = $this->getTableInfo($table);
        $rotatedValues = !$this->util->isAssoc($params["values"]) ? $this->util->rotateArray($params["values"]) : $params["values"];
        //no memory leaks here boys
        unset($params);

        //Having the same order is crucial. Sorting them by the key
        $columnInfo = array_change_key_case($columnInfo, CASE_LOWER);
        $rotatedValues = array_change_key_case($rotatedValues, CASE_LOWER);
        ksort($columnInfo);
        ksort($rotatedValues);

        $columnsNameFromParams = array_keys($rotatedValues);
        //For each column of the table, check if the name fits the column of the data. Also checks for autoincremented primary key
        //Could use array_intersect_key , but that would be two loop, because i would still need to check for the primary key
        $newValues = [];
        foreach ($columnInfo as $columnKey => $columnValue) {
            if (in_array($columnKey, $columnsNameFromParams)) {
                $columnsOtherForQuery .= $this->util->escape_backsticks($columnKey) . ',';
                $newValues[$columnKey] = $rotatedValues[$columnKey];
            }
            elseif ($columnValue["primaryKey"] && $columnValue["autoIncrement"]) {
                $columns .= $this->util->escape_backsticks($columnKey) . ',';
                $defaultValues .= "null,";
            }
        }
        $columns = substr($columns . $columnsOtherForQuery, 0, -1) . ')';
        $newValues = $this->util->checkForSubArray($newValues) ? $this->util->rotateArray($newValues) : $newValues;
        $valuesToInsert .= $defaultValues;
        foreach ($newValues as $field => $value) {
            if (is_array($value)) {
                if ($multiple) {
                    $valuesToInsert .= $defaultValues;
                }
                foreach ($value as $multipleField => $multipleValue) {
                    $valuesToInsert .= $this->setString($multipleField, $multipleValue, $multiple);
                }
                $valuesToInsert = substr($valuesToInsert, 0, -1) . "),";
                $multiple++;
            }
            else {
                $valuesToInsert .= $this->setString($field, $value, 0);
            }
        }

        $valuesToInsert = substr($valuesToInsert, 0, -1);
        if (!$multiple) {
            $valuesToInsert .= ')';
        }

        $this->currentQuery = "INSERT INTO $table $columns VALUES $valuesToInsert";
        return $this;
    }

    public function updateFromArray(array $params) : Database
    {
        $query = "UPDATE " . $params["table"];
        $set = " SET ";
        $incrementation = 0;
        $where = " WHERE ";
        foreach ($params["values"] as $field => $value) {
            $set .= $this->util->escape_backsticks($field) . " = " . ":$field$incrementation,";
            $this->currentParams[":$field$incrementation"] = empty($value) ? null : $value;
            $incrementation++;
        }
        foreach ($params["where"] as $field => $value) {
            $where .= $this->util->escape_backsticks($field) . " = " . ":$field$incrementation ";
            $this->currentParams[":$field$incrementation"] = empty($value) ? null : $value;
            $incrementation++;
        }
        $set = substr($set, 0, -1);
        $query .= $set . $where;
        $this->currentQuery = $query;
        return $this;
    }

    private function resetParams()
    {
        $this->lastQuery = $this->currentQuery;
        $this->currentQuery = null;
        $this->currentParams = null;
        $this->lastErrorMessage = $this->errorMessage;
        $this->errorMessage = null;
    }

    private function getTableInfo(string $table) : array
    {
        //can't prepare statement with table name
        $info = $this->execute("SHOW COLUMNS FROM $table")->getResult();
        $columns = [];
        foreach ($info as $key => $value) {
            $name = strtolower($value["Field"]);
            $columns[$name]["type"] = explode('(', $value["Type"])[0];
            $columns[$name]["primaryKey"] = $value["Key"] === "PRI";
            $columns[$name]["autoIncrement"] = $value["Extra"] === "auto_increment";
        }
        return $columns;
    }

    private function setString(string $field, mixed $value, int $increment = null) : string
    {
        $this->currentParams[":$field" . $increment] = empty($value) ? null : $value;
        return ":$field" . "$increment,";
    }

    public function createFormatedQuery(string $query = null, array $params = null) : string
    {
        $query = empty($query) ? $this->currentQuery : $query;
        $params = empty($params) ? $this->currentParams : $params;
        if (!empty($query) && !empty($params)) {
            foreach ($params as $key => $value) {
                if (!is_numeric($value)) {
                    $value = "'$value'";
                }
                if (substr($key, 0, 1) === ':') {
                    $query = str_replace($key, $value, $query);
                }
                else {
                    $query = substr_replace($query, $value . ",", strpos($query, "?"), strlen($value));
                }
            }
            return $query;
        }
        else {
            return "Nothing to evaluate";
        }
    }


    public function delete(string $table, array $where = null)
    {
        $query = "DELETE FROM " . $this->escape_backsticks($table);
        if ($this->util->array_empty($where)) {
            $query .= " WHERE ";
            if (count($where) === 1) {

            }
        }
        return $this;
    }

    public function select($table, $fields, $condition = null)
    {
        $query = "SELECT ";
        foreach ($fields as $fName => $fValue) {
            if (strpos($fValue, '*') !== false) {
                $query .= '*, ';
            }
            else {
                $tempValue = !empty($fValue) ? $fValue : $fName;
                $tempName = !is_int($fName) ? $fName : $fValue;
                $query .= $this->escapeBackSticks($tempName) . " AS " . $this->escapeBackSticks($tempValue) . ", ";
            }
        }
        $query = substr($query, 0, -2) . " FROM " . $this->escapeBackSticks($table);
        echo $query;
    }

}


$command = [
    "ALTER" => ["DATABASE", "EVENT", "FUNCTION", "INSTANCE", "LOGFILE GROUP", "PROCEDURE", "SERVER", "TABLE", "TABLENAME", "VIEW"],
    "CREATE" => ["DATABASE", "EVENT", "FUNCTION", "INDEX", "LOGFILE GROUP", "PROCEDURE", "SERVER", "TABLE", "TABLENAME", "TRIGGER", "VIEW"],
    "DROP" => ["DATABASE", "EVENT", "FUNCTION", "INDEX", "LOGFILE GROUP", "PROCEDURE", "SERVER", "TABLE", "TABLENAME", "TRIGGER", "VIEW"],
    "RENAME" => ["TABLE"],
    "TRUNCATE" => ["TABLE"]
];

$command = [
    "CALL",
    "DELETE",
    "DO",
    "HANDLER",
    "INSERT",
    "LOAD DATA INFILE",
    "LOAD XAML",
    "REPLACE",
    "SELECT",
    "UPDATE"
];
