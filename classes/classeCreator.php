<?php
include "../database.php";
$options = [
    "dbname" => "employe",
    "host" => "localhost",
    "user" => "root",
    "charSet" => "utf8",
    "port" => "3306",
    "password" => ""
];

$db = new database($options);
$schema = 'employe';
$className = $db->execute("SELECT TABLE_NAME AS _table FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$schema'")->getResult();
$file = fopen("base.php", "w");
fwrite($file, "<?php\n\n");
fwrite($file, "class Base {\n");
fwrite($file, "\t protected " . ' $db;' . "\n");
fwrite($file, "\t" . 'public function __construct(database $db){' . "\n");
fwrite($file, "\t\t" . '$this->db = $db;' . "\n");
fwrite($file, "\t }\n\n");
fwrite($file, "\t" . 'public function update($data, $id) {' . "\n");
fwrite($file, "\t\t" . 'return $this->db->updateFromArray(["table" => $this->table, "values" => $data, "where" => ["id" => $id]])->execute()->getResult();' . "\n");
fwrite($file, "\t }\n\n");
fwrite($file, "\t" . 'public function insert($data) {' . "\n");
fwrite($file, "\t\t" . 'return $this->db->insertFromArray(["table" => $this->table, "values" => $data])->execute()->getResult();' . "\n");
fwrite($file, "\t }\n\n");
fwrite($file, "\t" . 'public function delete($id) {' . "\n");
fwrite($file, "\t\t" . 'return $this->db->execute("DELETE FROM $this->table WHERE id = :id", ["id" => $id])->getResult();' . "\n");
fwrite($file, "\t }\n\n");
fwrite($file, '}');
foreach ($className as $tableName) {
    $file = fopen($tableName["_table"] . ".php", 'w');
    fwrite($file, "<?php\n\n");
    fwrite($file, "require_once \"base.php\";\n\n");
    fwrite($file, 'class ' . ucfirst($tableName["_table"]) . " extends Base{\n");
    fwrite($file, "\t" . 'protected $table = ' . "\"" . $tableName["_table"] . "\";\n\n");
    fwrite($file, '}');
    fclose($file);
}
