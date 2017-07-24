<?php

require '../includes/connexionBD.php'; //include the databaseWrapper
$schema = 'outillage';
$className = $db->execute("SELECT TABLE_NAME AS _table FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$schema'")->getResult();
foreach ($className as $tableName) {
    $file = fopen($tableName["_table"].".php", 'w');
    fwrite($file, "<?php\n");
    fwrite($file, 'class ' . ucfirst($tableName["_table"]) . "{\n");
    fwrite($file, "\t" . 'private $db;' . "\n");
    fwrite($file, "\t" . 'private $table = ' . "'".$tableName["_table"]."';\n\n");
    fwrite($file, "\t" . 'public function __construct(database $db){' . "\n");
    fwrite($file, "\t\t" . '$this->db = $db;' . "\n");
    fwrite($file, "\t }\n\n");

    fwrite($file, "\t" . 'public function update($data, $id) {' . "\n");
    fwrite($file, "\t\t" . 'return $this->db->updateFromArray([\'table\' => $this->table, \'values\' => $data, \'where\' => [\'id\' => $id]])->execute()->getResult();' . "\n");
    fwrite($file, "\t }\n\n");

    fwrite($file, "\t" . 'public function insert($data) {' . "\n");
    fwrite($file, "\t\t" . 'return $this->db->insertFromArray([\'table\' => $this->table, \'values\' => $data])->execute()->getResult();' . "\n");
    fwrite($file, "\t }\n\n");

    fwrite($file, "\t" . 'public function delete($id) {' . "\n");
    fwrite($file, "\t\t" . 'return $this->db->execute(\'DELETE FROM $table WHERE id = :id\', [\'id\' => $id])->getResult();' . "\n");
    fwrite($file, "\t }\n\n");

    fwrite($file, '}');
    fclose($file);
}
