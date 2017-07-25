<?php

include "database.php";
$options = [
    "dbname" => "employe",
    "host" => "localhost",
    "user" => "root",
    "charSet" => "utf8",
    "port" => "3306",
    "password" => ""
];

$db = new database($options);

$testArrayAssoc = ["name" => ["Bob", "NotBob"],
    "description" => [1, 2]
];
$testArray2 = ["name" => ["Bob"],
    "description" => [1]
];
$testArrayReverse = [["name" => "Bob", "description" => 6],
    ["name" => "Jacques", "description" => 7],
    ["name" => "Julien", "description" => 8],
    ["name" => "David", "description" => 9],
    ["name" => "Pablo", "description" => 10]
];

$testArrayReverse2 = ["name" => "Bob", "description" => 6];


$db->insertFromArray(["table" => "employes", "values" => $testArrayAssoc])->execute()->getResult();
$db->insertFromArray(["table" => "employes", "values" => $testArray2])->execute()->getResult();
$db->insertFromArray(["table" => "employes", "values" => $testArrayReverse])->execute()->getResult();
$db->insertFromArray(["table" => "employes", "values" => $testArrayReverse2])->execute()->getResult();