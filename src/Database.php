<?php

declare (strict_types = 1);
require 'Utilities.php';

class Database {

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
    private $currentParams;
    private $lastQuery;
    private $currentQuery;
    private $util;

    const ALL = 0;
    const FIRST_ROW = 1;
    const COLUMN = 2;

    public function __construct(array $options = null) {
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
        $this->connection = new PDO($connectionString, $this->options["user"], $this->options["password"], $params);

        $this->util = new Utilities();
    }

    public function __get(string $name): mixed {
        switch ($name) {
            case "data" :
                return $this->data;
            case "lastId" :
                return $this->connection->lastInsertId();
            case "rows" :
                return $this->PDO->rowCount();
        }
    }

    public function execute(string $query = null, array $params = null): self {
        if ($this->connection) {
            if (!empty($query)) {
                $this->currentQuery = $query;
            }
            if (empty($this->currentParams)) {
                $this->currentParams = $params;
            }
            if (in_array(strtolower(explode(' ', $this->currentQuery)[0]), ["select", "show"])) {
                return $this->executeSelect();
            }
            return $this->executeUpdate();
        }
    }

    public function getResult(self $fetchMethod = null, int $column = 0) {
        $this->resetParams();
        if ($fetchMethod === Database::FIRST_ROW) {
            return $this->data = $this->PDO->fetch(PDO::FETCH_ASSOC);
        } elseif ($fetchMethod === Database::COLUMN) {
            return $this->data = $this->PDO->fetchColumn($column);
        }
        return $this->data = $this->PDO->fetchAll(PDO::FETCH_ASSOC);
    }

    private function executeSelect() {
        if ($this->currentQuery !== $this->lastQuery) {
            $this->PDO = $this->connection->prepare($this->currentQuery);
        }
        if ($this->PDO) {
            $this->PDO->execute($this->currentParams);
        }
    }

    private function executeUpdate() {
        if ($this->currentQuery !== $this->lastQuery) {
            $this->PDO = $this->connection->prepare($this->currentQuery);
        }
        if ($this->PDO) {
            $params = $this->currentParams;
            $this->resetParams();
            return $this->data = $this->PDO->execute($params);
        }
    }

    public function insertFromArray(array $params): self {
        $table = $this->$util->escape_backsticks($params["table"]);
        $columns = '(';
        $defaultValues = '(';
        $columnsOtherForQuery = "";
        $valuesToInsert = "";
        $multiple = 0;
        $columnInfo = $this->getTableInfo($table);
        $rotatedValues = $this->$util->array_assoc($params["values"]) ? $params["values"] : $this->$util->array_rotate($params["values"]);
        //no memory leaks here boys
        unset($params);
        //Having the same order is crucial. Sorting them by the key
        $columnInfo = array_change_key_case($columnInfo, CASE_LOWER);
        $rotatedValues = array_change_key_case($rotatedValues, CASE_LOWER);
        ksort($columnInfo);
        ksort($rotatedValues);
        $columnsName = array_keys($rotatedValues);
        //For each column of the table, check if the name fits the column of the data. Also checks for autoincremented primary key
        //Could use array_intersect_key , but that would be two loop, because i would still need to check for the primary key
        $newValues = [];
        foreach ($columnInfo as $columnKey => $columnValue) {
            if (in_array($columnKey, $columnsName)) {
                $columnsOtherForQuery .= $this->$util->escape_backsticks($columnKey) . ',';
                $newValues[$columnKey] = $rotatedValues[$columnKey];
            } elseif ($columnValue["primaryKey"] && $columnValue["autoIncrement"]) {
                $columns .= $this->$util->escape_backsticks($columnKey) . ',';
                $defaultValues .= "null,";
            }
        }
        $columns = substr($columns . $columnsOtherForQuery, 0, -1) . ')';
        if ($this->$util->array_contains_only_array($newValues)) {
            $newValues = $this->$util->array_rotate($newValues);
        }
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
            } else {
                $valuesToInsert .= $this->setString($field, $value, 0);
            }
        }
        $valuesToInsert = substr($valuesToInsert, 0, -1);
        if ($multiple === 0) {
            $valuesToInsert .= ')';
        }
        $this->currentQuery = "INSERT INTO $table $columns VALUES $valuesToInsert";
        return $this;
    }

    public function updateFromArray(array $params): self {
        $query = "UPDATE " . $params["table"];
        $set = " SET ";
        $incrementation = 0;
        $where = " WHERE ";
        foreach ($params["values"] as $field => $value) {
            $set .= $this->$util->escape_backsticks($field) . " = " . ":$field$incrementation,";
            $this->currentParams[":$field$incrementation"] = empty($value) ? null : $value;
            $incrementation++;
        }
        foreach ($params["where"] as $field => $value) {
            $where .= $this->$util->escape_backsticks($field) . " = " . ":$field$incrementation ";
            $this->currentParams[":$field$incrementation"] = empty($value) ? null : $value;
            $incrementation++;
        }
        $set = substr($set, 0, -1);
        $query .= $set . $where;
        $this->currentQuery = $query;
        return $this;
    }

    private function resetParams() {
        $this->lastQuery = $this->currentQuery;
        $this->currentQuery = null;
        $this->currentParams = null;
        $this->errorMessage = null;
    }

    private function getTableInfo(string $table): array {
        //can't prepare statement with table name
        $info = $this->execute("SHOW COLUMNS FROM $table")->getResult();
        $columns = [];
        foreach ($info as $value) {
            $name = strtolower($value["Field"]);
            $columns[$name]["type"] = $value["Type"];
            $columns[$name]["primaryKey"] = $value["Key"] === "PRI";
            $columns[$name]["autoIncrement"] = $value["Extra"] === "auto_increment";
        }
        return $columns;
    }

    private function setString(string $field, $value, int $increment = null): string {
        $this->currentParams[":$field" . $increment] = empty($value) ? null : $value;
        return ":$field" . "$increment,";
    }

    public function createFormatedQuery(string $query = null, array $params = null): string {
        $query = empty($query) ? $this->currentQuery : $query;
        $params = empty($params) ? $this->currentParams : $params;
        if (!empty($query) && !empty($params)) {
            foreach ($params as $key => $value) {
                if (!is_numeric($value)) {
                    $value = "'$value'";
                }
                if (substr($key, 0, 1) === ':') {
                    $query = str_replace($key, $value, $query);
                } else {
                    $query = substr_replace($query, $value . ",", strpos($query, "?"), strlen($value));
                }
            }
            return $query;
        }
        return "Nothing to evaluate";
    }

    public function delete(string $table, array $where = null) {
        $query = "DELETE FROM " . $this->escape_backsticks($table);
        if ($this->util->array_empty($where)) {
            $query .= " WHERE ";
            if (count($where) === 1) {
                
            }
        }
        return $this;
    }

    public function select($table, $fields, $condition = null) {
        $query = "SELECT ";
        foreach ($fields as $fName => $fValue) {
            if (strpos($fValue, '*') !== false) {
                $query .= '*, ';
            } else {
                $tempValue = !empty($fValue) ? $fValue : $fName;
                $tempName = !is_int($fName) ? $fName : $fValue;
                $query .= $this->$util->escape_backsticks($tempName) . " AS " . $this->$util->escape_backsticks($tempValue) . ", ";
            }
        }
        $query = substr($query, 0, -2) . " FROM " . $this->$util->escape_backsticks($table);
        echo htmlentities($query);
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
