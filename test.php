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

$testArrayAssoc = [
    "name" => ["Bob", "NotBob"],
    "description" => [1, 2]
];
$testArray2 = [
    "name" => ["Bob"],
    "description" => [1]
];
$testArrayReverse = [
    ["name" => "Bob", "description" => 6],
    ["name" => "Jacques", "description" => 7],
    ["name" => "Julien", "description" => 8],
    ["name" => "David", "description" => 9],
    ["name" => "Pablo", "description" => 10]
];

$testArrayReverse2 = ["name" => "Bob", "description" => 6];


$bd = new PDO("mysql:dbname=employe;host=localhost", "root", "");
$time = microtime(true);
$query = "UPDATE employes SET name='Bob' WHERE id = 91";
for ($i = 0; $i < 2500; $i++) {
    $pdo = $bd->exec($query);
}

echo "End#1 = " . (microtime(true) - $time);

$time = microtime(true);
for ($i = 0; $i < 2500; $i++) {
    $db->execute("UPDATE employes SET name= ? WHERE id = ?", ["Bob", 91])->getResult();
}

echo "End#2 = " . (microtime(true) - $time);
//$db->insertFromArray(["table" => "employes", "values" => $testArrayAssoc])->execute()->getResult();
//$db->insertFromArray(["table" => "employes", "values" => $testArray2])->execute()->getResult();
//$db->insertFromArray(["table" => "employes", "values" => $testArrayReverse])->execute()->getResult();
//$db->insertFromArray(["table" => "employes", "values" => $testArrayReverse2])->execute()->getResult();