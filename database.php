<?php

class utilities {

    /**
     * Verify is the array provided is associative or not
     * @param array $array The array to verify
     * @return boolean true if array is associative, false if not.
     */
    public function is_assoc(array $array) {
        return is_array($array) && array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Rotate the array. Change the position of the keys and values
     * @param array $array The array to rotate
     * @return array The rotated array
     */
    public function rotate_array(array $array) {
        $newArray = [];
        foreach ($array as $reverseKey => $reverseValue) {
            foreach ($reverseValue as $reverseSubKey => $reverseSubValue) {
                $newArray[$reverseSubKey][$reverseKey] = $reverseSubValue;
            }
        }
        return $newArray;
    }

    /**
     * Check if the values of the provided array are all array
     * @param array $array The array to check
     * @return bool true if the array contains only array, false otherwise
     */
    public function contains_only_array(array $array) {
        $i = 0;
        $notFound = true;
        while (isset($array[$i]) && $i < count($array) && $notFound) {
            if (!is_array($array[$i])) {
                $notFound = false;
            }
            $i++;
        }
        return $notFound;
    }

    /**
     * Take a string and espace it with backstick '`'
     * @param string $var
     * @return string The escaped string
     */
    public function escape_backsticks($var) {
        $string = explode(".", $var);
        foreach ($string as $key => $singleString) {
            $string[$key] = "`" . str_replace("`", "``", $singleString) . "`";
        }
        return implode('.', $string);
    }

    public function round_number_two_decimal($val) {
        return number_format(round($val, 2), 2, '.', '');
    }
    /**
     * Create a pseudo-random string
     * @param int $length The length of the random word. Default : 10
     * @return string The random word generated 
     */
    public function random_string($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    /**
     * Create a string containing interrogation marks to be used in preparedStatement
     * @param array $data The data to create marks from
     * @return string The string containing the interrogation marks
     */
    public function create_marks($data) {
        return str_repeat('?,', count($data) - 1) . '?';
    }

    /**
     * Takes a string and change the special characters to the latin alphabet
     * @param string $var The string to remove the special characters from
     * @return string The string after removing the special characters
     */
    public function remove_special_chars($var) {
        $specialChars = [
            '&amp;' => 'and', '@' => 'at', '©' => 'c', '®' => 'r', 'À' => 'a',
            'Á' => 'a', 'Â' => 'a', 'Ä' => 'a', 'Å' => 'a', 'Æ' => 'ae', 'Ç' => 'c',
            'È' => 'e', 'É' => 'e', 'Ë' => 'e', 'Ì' => 'i', 'Í' => 'i', 'Î' => 'i',
            'Ï' => 'i', 'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
            'Ø' => 'o', 'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ý' => 'y',
            'ß' => 'ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'å' => 'a',
            'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u',
            'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'p', 'ÿ' => 'y', 'Ā' => 'a',
            'ā' => 'a', 'Ă' => 'a', 'ă' => 'a', 'Ą' => 'a', 'ą' => 'a', 'Ć' => 'c',
            'ć' => 'c', 'Ĉ' => 'c', 'ĉ' => 'c', 'Ċ' => 'c', 'ċ' => 'c', 'Č' => 'c',
            'č' => 'c', 'Ď' => 'd', 'ď' => 'd', 'Đ' => 'd', 'đ' => 'd', 'Ē' => 'e',
            'ē' => 'e', 'Ĕ' => 'e', 'ĕ' => 'e', 'Ė' => 'e', 'ė' => 'e', 'Ę' => 'e',
            'ę' => 'e', 'Ě' => 'e', 'ě' => 'e', 'Ĝ' => 'g', 'ĝ' => 'g', 'Ğ' => 'g',
            'ğ' => 'g', 'Ġ' => 'g', 'ġ' => 'g', 'Ģ' => 'g', 'ģ' => 'g', 'Ĥ' => 'h',
            'ĥ' => 'h', 'Ħ' => 'h', 'ħ' => 'h', 'Ĩ' => 'i', 'ĩ' => 'i', 'Ī' => 'i',
            'ī' => 'i', 'Ĭ' => 'i', 'ĭ' => 'i', 'Į' => 'i', 'į' => 'i', 'İ' => 'i',
            'ı' => 'i', 'Ĳ' => 'ij', 'ĳ' => 'ij', 'Ĵ' => 'j', 'ĵ' => 'j', 'Ķ' => 'k',
            'ķ' => 'k', 'ĸ' => 'k', 'Ĺ' => 'l', 'ĺ' => 'l', 'Ļ' => 'l', 'ļ' => 'l',
            'Ľ' => 'l', 'ľ' => 'l', 'Ŀ' => 'l', 'ŀ' => 'l', 'Ł' => 'l', 'ł' => 'l',
            'Ń' => 'n', 'ń' => 'n', 'Ņ' => 'n', 'ņ' => 'n', 'Ň' => 'n', 'ň' => 'n',
            'ŉ' => 'n', 'Ŋ' => 'n', 'ŋ' => 'n', 'Ō' => 'o', 'ō' => 'o', 'Ŏ' => 'o',
            'ŏ' => 'o', 'Ő' => 'o', 'ő' => 'o', 'Œ' => 'oe', 'œ' => 'oe', 'Ŕ' => 'r',
            'ŕ' => 'r', 'Ŗ' => 'r', 'ŗ' => 'r', 'Ř' => 'r', 'ř' => 'r', 'Ś' => 's',
            'ś' => 's', 'Ŝ' => 's', 'ŝ' => 's', 'Ş' => 's', 'ş' => 's', 'Š' => 's',
            'š' => 's', 'Ţ' => 't', 'ţ' => 't', 'Ť' => 't', 'ť' => 't', 'Ŧ' => 't',
            'ŧ' => 't', 'Ũ' => 'u', 'ũ' => 'u', 'Ū' => 'u', 'ū' => 'u', 'Ŭ' => 'u',
            'ŭ' => 'u', 'Ů' => 'u', 'ů' => 'u', 'Ű' => 'u', 'ű' => 'u', 'Ų' => 'u',
            'ų' => 'u', 'Ŵ' => 'w', 'ŵ' => 'w', 'Ŷ' => 'y', 'ŷ' => 'y', 'Ÿ' => 'y',
            'Ź' => 'z', 'ź' => 'z', 'Ż' => 'z', 'ż' => 'z', 'Ž' => 'z', 'ž' => 'z',
            'ſ' => 'z', 'Ə' => 'e', 'ƒ' => 'f', 'Ơ' => 'o', 'ơ' => 'o', 'Ư' => 'u',
            'ư' => 'u', 'Ǎ' => 'a', 'ǎ' => 'a', 'Ǐ' => 'i', 'ǐ' => 'i', 'Ǒ' => 'o',
            'ǒ' => 'o', 'Ǔ' => 'u', 'ǔ' => 'u', 'Ǖ' => 'u', 'ǖ' => 'u', 'Ǘ' => 'u',
            'ǘ' => 'u', 'Ǚ' => 'u', 'ǚ' => 'u', 'Ǜ' => 'u', 'ǜ' => 'u', 'Ǻ' => 'a',
            'ǻ' => 'a', 'Ǽ' => 'ae', 'ǽ' => 'ae', 'Ǿ' => 'o', 'ǿ' => 'o', 'ə' => 'e',
            'Ё' => 'jo', 'Є' => 'e', 'І' => 'i', 'Ї' => 'i', 'А' => 'a', 'Б' => 'b',
            'В' => 'v', 'Г' => 'g', 'Д' => 'd', 'Е' => 'e', 'Ж' => 'zh', 'З' => 'z',
            'И' => 'i', 'Й' => 'j', 'К' => 'k', 'Л' => 'l', 'М' => 'm', 'Н' => 'n',
            'О' => 'o', 'П' => 'p', 'Р' => 'r', 'С' => 's', 'Т' => 't', 'У' => 'u',
            'Ф' => 'f', 'Х' => 'h', 'Ц' => 'c', 'Ч' => 'ch', 'Ш' => 'sh', 'Щ' => 'sch',
            'Ъ' => '-', 'Ы' => 'y', 'Ь' => '-', 'Э' => 'je', 'Ю' => 'ju', 'Я' => 'ja',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l',
            'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
            'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '-', 'ы' => 'y', 'ь' => '-', 'э' => 'je',
            'ю' => 'ju', 'я' => 'ja', 'ё' => 'jo', 'є' => 'e', 'і' => 'i', 'ї' => 'i',
            'Ґ' => 'g', 'ґ' => 'g', 'א' => 'a', 'ב' => 'b', 'ג' => 'g', 'ד' => 'd',
            'ה' => 'h', 'ו' => 'v', 'ז' => 'z', 'ח' => 'h', 'ט' => 't', 'י' => 'i',
            'ך' => 'k', 'כ' => 'k', 'ל' => 'l', 'ם' => 'm', 'מ' => 'm', 'ן' => 'n',
            'נ' => 'n', 'ס' => 's', 'ע' => 'e', 'ף' => 'p', 'פ' => 'p', 'ץ' => 'C',
            'צ' => 'c', 'ק' => 'q', 'ר' => 'r', 'ש' => 'w', 'ת' => 't', '™' => 'tm',
        ];
        return strtr($var,$specialChars);
    }
    
    /**
     * Check if all the value of an array an empty or not.
     * @param array $array To array to evaluate
     * @return bool Return true if the $array is empty, return true if not 
     */
    public function array_empty($array){
        return strlen(implode($array)) === 0;
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
    private $lastErrorMessage;
    private $currentParams;
    private $lastQuery;
    private $currentQuery;
    private $keyword;
    private $util;

    public function __construct($options = null) {

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
        $this->util = new utilities();
    }

    public function __get($name) {
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

    public function execute($query = null, $params = null) {
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
            } else {
                $this->executeUpdate();
            }
            return $this;
        } else {
            trigger_error("No connection to the database, can't do queries");
        }
    }

    public function getResult($fetchMethod = null) {
        $this->displayErrorMessage();
        if (empty($this->errorMessage)) {
            if ($this->keyword === "select" || $this->keyword === "show") {
                try {
                    if ($fetchMethod === "firstRow") {
                        $this->data = $this->PDO->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $this->data = $this->PDO->fetchAll(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) {
                    $this->errorMessage = $e;
                    trigger_error($e);
                } finally {
                    $this->resetParams();
                }
            }
            return $this->data;
        }
    }

    private function executeSelect() {
        try {
            if ($this->currentQuery !== $this->lastQuery) {
                $this->PDO = $this->connection->prepare($this->currentQuery);
            }
            if (!$this->PDO) {
                $this->errorMessage = $this->PDO->errorInfo();
            } else {
                $this->PDO->execute($this->currentParams);
            }
        } catch (Exception $e) {
            $this->errorMessage = $e;
            trigger_error($e);
        }
    }

    private function executeUpdate() {
        try {
            if ($this->currentQuery !== $this->lastQuery) {
                $this->PDO = $this->connection->prepare($this->currentQuery);
            }
            if (!$this->PDO) {
                $this->errorMessage = $this->connection->errorInfo();
            } else {
                $this->data = $this->PDO->execute($this->currentParams);
            }
        } catch (Exception $e) {
            $this->errorMessage = $e;
            trigger_error($e);
        }
    }

    private function displayErrorMessage() {
        if ($this->errorMessage && $this->debugMode) {
            print_r($this->errorMessage);
            echo "QUERY => " . $this->currentQuery;
            print_r($this->currentParams);
            trigger_error($this->errorMessage[0]);
        }
    }

    public function insertFromArray($params) {
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
//print_r($columnsNameFromParams);
//print_r($columnInfo);
//For each column of the table, check if the name fits the column of the data. Also checks for autoincremented primary key
//Could use array_intersect_key , but that would be two loop, because i would still need to check for the primary key
        $newValues = [];
        foreach ($columnInfo as $columnKey => $columnValue) {
            if (in_array($columnKey, $columnsNameFromParams)) {
                $columnsOtherForQuery .= $this->util->escapeBackSticks($columnKey) . ',';
                $newValues[$columnKey] = $rotatedValues[$columnKey];
            } elseif ($columnValue["primaryKey"] && $columnValue["autoIncrement"]) {
                $columns .= $this->util->escapeBackSticks($columnKey) . ',';
                $defaultValues .= "NULL,";
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
            } else {
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

    public function updateFromArray($params) {
        $query = "UPDATE " . $params["table"];
        $set = " SET ";
        $incrementation = 0;
        $where = " WHERE ";
        foreach ($params["values"] as $field => $value) {
            $set .= $this->util->escapeBackSticks($field) . " = " . ":$field$incrementation,";
            $this->currentParams[":$field$incrementation"] = empty($value) ? NULL : $value;
            $incrementation++;
        }
        foreach ($params["where"] as $field => $value) {
            $where .= $this->util->escapeBackSticks($field) . " = " . ":$field$incrementation ";
            $this->currentParams[":$field$incrementation"] = empty($value) ? NULL : $value;
            $incrementation++;
        }
        $set = substr($set, 0, -1);
        $query .= $set . $where;
        $this->currentQuery = $query;
        return $this;
    }

    private function resetParams() {
        $this->lastQuery = $this->currentQuery;
        $this->currentQuery = NULL;
        $this->currentParams = NULL;
        $this->lastErrorMessage = $this->errorMessage;
        $this->errorMessage = NULL;
    }

    private function getTableInfo($table) {
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

    private function setString($field, $value, $increment = NULL) {
        $this->currentParams[":$field" . $increment] = empty($value) ? NULL : $value;
        return ":$field" . "$increment,";
    }

    public function createFormatedQuery($query = null, $params = null) {
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
        } else {
            return "Nothing to evaluate";
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


    
}


$command = [
    "ALTER" => ["DATABASE","EVENT","FUNCTION","INSTANCE","LOGFILE GROUP","PROCEDURE","SERVER","TABLE","TABLENAME","VIEW"],
    "CREATE" => ["DATABASE","EVENT","FUNCTION","INDEX","LOGFILE GROUP","PROCEDURE","SERVER","TABLE","TABLENAME","TRIGGER","VIEW"],
    "DROP" => ["DATABASE","EVENT","FUNCTION","INDEX","LOGFILE GROUP","PROCEDURE","SERVER","TABLE","TABLENAME","TRIGGER","VIEW"],
    "RENAME" => ["TABLE"],
    "TRUNCATE" =>["TABLE"]
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
