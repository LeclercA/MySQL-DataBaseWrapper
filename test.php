<?php

include "database.php";
$options = [
    "dataBaseName" => "employe",
    "dataBaseType" => "mysql",
    "host" => "localhost",
    "user" => "root",
    "charSet" => "utf8",
    "port" => "3306",
    "password" => ""
];

$db = new database($options);
$result = $db->execute("DELETE FROM employes")->getResult();
echo $result;
echo $db->numberOfSuccessfulDeleteQueries;

//exemple of insertFromArray();
$db->insertFromArray(["table" => "tasks", "values" => ["name" => "bob", "age" => 12]]);
