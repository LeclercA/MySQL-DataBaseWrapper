<?php
class utilities
{

    /**
     * Verify is the array provided is associative or not
     * @param array The array the verify
     * @return boolean true if array is associative, false if not.
     */
    public function isAssoc(array $arr)
    {
        if (array() === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function rotateArray($array)
    {
        $newArray = [];
        foreach ($array as $reverseKey => $reverseValue) {
            foreach ($reverseValue as $reverseSubKey => $reverseSubValue) {
                $newArray[$reverseSubKey][$reverseKey] = $reverseSubValue;
            }
        }
        return $newArray;
    }

    public function checkForSubArray($array)
    {
        return is_array(reset($array));
    }

    public function escapeBackSticks($var)
    {
        $string = explode(".",$var);
        foreach($string as $key => $singleString){
            $string[$key] = "`" . str_replace("`", "``", $singleString) . "`";
        }
        return implode('.',$string);
    }
    
    public function roundNumberWithTwoFloat($val) {
        return number_format(round($val, 2), 2, '.', '');
    }

}

class database extends utilities
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


    private $currentParams;

    private $lastQuery;
    private $currentQuery;
    private $keyword;

    public function __construct($options = null)
    {
        $connectionString = $this->contructSetter($options);
        try {
            $this->connection = new PDO($connectionString, $this->options["user"], $this->options["password"]);
        } catch (PDOException $e) {
            $this->errorMessage = $e;
            trigger_error($e);
        }
        //Cannot be removed
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function __get($name)
    {
        switch ($name) {
            case "data" :
                return $this->data;
            case "lastId" :
                return $this->connection->lastInsertId();
            case "rows" :
                return $this->PDO->rowCount();
            case "errorMessage" :
                return $this->errorMessage;
        }
    }

    public function delete($table){
        $query = "DELETE FROM" . $this->escapeBackSticks($table);
    }

    public function select($table,$fields,$condition = null){
        $query = "SELECT ";
        foreach($fields as $fName => $fValue){
            
            $tempValue = !empty($fValue) ? $fValue : $fName;
            $tempName = !is_int($fName) ? $fName : $fValue;
            $query .= $this->escapeBackSticks($tempName) . " AS " . $this->escapeBackSticks($tempValue) . ", ";
        }
        $query = substr($query,0,-2) . " FROM " . $this->escapeBackSticks($table);
        echo $query;
    }

    public function execute($query = null, $params = null)
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
            }else {
                $this->executeUpdate();
            }
            return $this;
        }
        else {
            trigger_error("No connection to the database, can't do queries");
        }
    }


    public function getResult($fetchMethod = null)
    {
        $this->displayErrorMessage();
        if ($this->keyword === "select" || $this->keyword === "show") {
            if ($fetchMethod === "firstRow") {
                $this->data = $this->PDO->fetch(PDO::FETCH_ASSOC);
            }
            else {
                $this->data = $this->PDO->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        $this->resetParams();
        return $this->data;
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
        } catch (PDOException $e) {
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
        } catch (PDOException $e) {
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
            trigger_error($this->errorMessage);
        }
    }

    public function insertFromArray($params)
    {
        $table = $this->escapeBackSticks($params["table"]);

        $columns = '(';
        $defaultValues = '(';
        $columnsOtherForQuery = "";
        $valuesToInsert = "";
        $multiple = 0;

        $columnInfo = $this->getTableInfo($table);
        $rotatedValues = !$this->isAssoc($params["values"]) ? $this->rotateArray($params["values"]) : $params["values"];
        //no memory leaks here boys
        unset($params);

        //Having the same order is crucial. Sorting them by the key
        $columnInfo = array_change_key_case($columnInfo, CASE_LOWER);
        $rotatedValues = array_change_key_case($rotatedValues, CASE_LOWER);
        ksort($columnInfo);
        ksort($rotatedValues);

        $columnsNameFromParams = array_keys($rotatedValues);
        //print_r($columnsNameFromParams);
        //print_r($columnInfo);
        //For each column of the table, check if the name fits the column of the data. Also checks for autoincremented primary key
        //Could use array_intersect_key , but that would be two loop, because i would still need to check for the primary key
        $newValues = [];
        foreach ($columnInfo as $columnKey => $columnValue) {
            if (in_array($columnKey, $columnsNameFromParams)) {
                $columnsOtherForQuery .= $this->escapeBackSticks($columnKey) . ',';
                $newValues[$columnKey] = $rotatedValues[$columnKey];
            }
            elseif ($columnValue["primaryKey"] && $columnValue["autoIncrement"]) {
                $columns .= $this->escapeBackSticks($columnKey) . ',';
                $defaultValues .= "NULL,";
            }
        }
        $columns = substr($columns . $columnsOtherForQuery, 0, -1) . ')';

        $newValues = $this->checkForSubArray($newValues) ? $this->rotateArray($newValues) : $newValues;
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

        echo $this->currentQuery = "INSERT INTO $table $columns VALUES $valuesToInsert";
        return $this;
    }

    public function updateFromArray($params)
    {
        $query = "UPDATE " . $params["table"];
        $set = " SET ";
        $incrementation = 0;
        $where = " WHERE ";
        foreach ($params["values"] as $field => $value) {
            $set .= $this->escapeBackSticks($field) . " = " . ":$field$incrementation,";
            $this->currentParams[":$field$incrementation"] = empty($value) ? NULL : $value;
            $incrementation++;
        }
        foreach ($params["where"] as $field => $value) {
            $where .= $this->escapeBackSticks($field) . " = " . ":$field$incrementation ";
            $this->currentParams[":$field$incrementation"] = empty($value) ? NULL : $value;
            $incrementation++;
        }
        $set = substr($set, 0, -1);
        $query .= $set . $where;
        $this->currentQuery = $query;
        return $this;
    }

    private function contructSetter($options)
    {
        $dataBaseString = $this->dbType . ":";
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
                if ($key !== "user" && $key !== "password" && !empty($value)) {
                    $dataBaseString .= $key . "=" . $value . ";";
                }
            }
        }
        return substr($dataBaseString, 0, -1);
    }

    private function resetParams()
    {
        $this->lastQuery = $this->currentQuery;
        $this->currentQuery = NULL;
        $this->currentParams = NULL;
        $this->errorMessage = NULL;
    }

    private function getTableInfo($table)
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

    private function setString($field, $value, $increment = NULL)
    {
        $this->currentParams[":$field" . $increment] = empty($value) ? NULL : $value;
        return ":$field" . "$increment,";
    }

    public function createFormatedQuery($query = null, $params = null)
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
    
    public function queryToCSV($query = null, $params = null) {
        if (!empty($query)) {
            $this->currentQuery = $query;
        }
        if (empty($this->currentParams)) {
            $this->currentParams = $params;
        }
        unlink("/var/lib/mysql-files/codes.csv");
        $this->currentQuery = substr_replace($this->currentQuery, " INTO OUTFILE '/var/lib/mysql-files/codes.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\\n' ", stripos($this->currentQuery, "FROM"), 0);
        $this->execute();
    }
    
}
