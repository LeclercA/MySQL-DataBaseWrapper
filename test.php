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

$testArray = ["name" => ["Bob", "Jacques", "Julien", "David", "Pablo"],
    "department_id" => [1, 2, 3, 4, 5]
];

$testArrayReverse = [["name" => "Bob", "department_id" => 1],
    ["name" => "Jacques", "department_id" => 2],
    ["name" => "Julien", "department_id" => 3],
    ["name" => "David", "department_id" => 4],
    ["name" => "Pablo", "department_id" => 5]
];

$testArrayWithCustomId = [
    "table" => "employes",
    "values" => ["name" => ["Bob", "Jacques", "Julien", "David", "Pablo"],
        "department_id" => [1, 2, 3, 4, 5]
    ],
    "id" => "idTableName", //your primary key that you want to be auto incremented
    "multiple" => true,
];

$testArrayWithNoId = [
    "table" => "employes",
    "values" => ["name" => ["Bob", "Jacques", "Julien", "David", "Pablo"],
        "department_id" => [1, 2, 3, 4, 5]
    ],
    "id" => false,
    "multiple" => true,
];

$db = new database($options);
$db->debugMode = true;
$db->execute("DELETE FROM employes")->getResult();


$db->insertFromArray(["table" => "employes", "values" => $testArray])->execute()->getResult();

$db->insertFromArray(["table" => "employes", "values" => $testArrayReverse, "reverse" => true])->execute()->getResult();

$db->insertFromArray($testArrayWithCustomId)->execute()->getResult();

$bd->insertFromArray($testArrayWithNoId)->execute()->getResult();

/*
 * RESULT
 *      QUERY 1 : INSERT INTO employes (id,name,department_id) VALUES (NULL,:name0,:department_id0),(NULL,:name1,:department_id1),(NULL,:name2,:department_id2),(NULL,:name3,:department_id3),(NULL,:name4,:department_id4)
 *      PARAMS 1 : Array(
                            [:name0] => Bob
                            [:department_id0] => 1
                            [:name1] => Jacques
                            [:department_id1] => 2
                            [:name2] => Julien
                            [:department_id2] => 3
                            [:name3] => David
                            [:department_id3] => 4
                            [:name4] => Pablo
                            [:department_id4] => 5
                        )
 *
 *      QUERY 2 : INSERT INTO employes (id,name,department_id) VALUES (NULL,:name0,:department_id0),(NULL,:name1,:department_id1),(NULL,:name2,:department_id2),(NULL,:name3,:department_id3),(NULL,:name4,:department_id4)
 *      PARAMS 2 : Array(
                            [:name0] => Bob
                            [:department_id0] => 1
                            [:name1] => Jacques
                            [:department_id1] => 2
                            [:name2] => Julien
                            [:department_id2] => 3
                            [:name3] => David
                            [:department_id3] => 4
                            [:name4] => Pablo
                            [:department_id4] => 5
                        )
 *
 *      QUERY 3 : INSERT INTO employes (idTableName,name,department_id) VALUES (NULL,:name0,:department_id0),(NULL,:name1,:department_id1),(NULL,:name2,:department_id2),(NULL,:name3,:department_id3),(NULL,:name4,:department_id4)
 *      PARAMS 3 : Array(
                            [:name0] => Bob
                            [:department_id0] => 1
                            [:name1] => Jacques
                            [:department_id1] => 2
                            [:name2] => Julien
                            [:department_id2] => 3
                            [:name3] => David
                            [:department_id3] => 4
                            [:name4] => Pablo
                            [:department_id4] => 5
                        )
 *
 *      QUERY 4 : INSERT INTO employes (name,department_id) VALUES (:name0,:department_id0),(:name1,:department_id1),(:name2,:department_id2),(:name3,:department_id3),(:name4,:department_id4)
 *      PARAMS 4 : Array(
                            [:name0] => Bob
                            [:department_id0] => 1
                            [:name1] => Jacques
                            [:department_id1] => 2
                            [:name2] => Julien
                            [:department_id2] => 3
                            [:name3] => David
                            [:department_id3] => 4
                            [:name4] => Pablo
                            [:department_id4] => 5
                        )
 *
 */


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

var_dump(is_array(reset($testArray2)));  //true
var_dump(is_array(reset($testArrayReverse)));  //true
var_dump(is_array(reset($testArrayReverse2)));  //false
var_dump(is_array(false));  //false


var_dump(test($testArray2));    //true
var_dump(test($testArrayReverse));  //true
var_dump(test($testArrayReverse2)); //false
var_dump(test(false));  //false

function test($array) {
    foreach ($array as $subArray) {
        if (is_array($subArray)) {
            return true;
        }
    }
    return false;
}
