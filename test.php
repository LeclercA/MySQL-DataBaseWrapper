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
$db->execute("DELETE FROM employes")->getResult();
echo $db->numberOfDeleteQueries;