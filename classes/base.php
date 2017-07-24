<?php

class Base {
	 protected  $db;
	public function __construct(database $db){
		$this->db = $db;
	 }

	public function update($data, $id) {
		return $this->db->updateFromArray(["table" => $this->table, "values" => $data, "where" => ["id" => $id]])->execute()->getResult();
	 }

	public function insert($data) {
		return $this->db->insertFromArray(["table" => $this->table, "values" => $data])->execute()->getResult();
	 }

	public function delete($id) {
		return $this->db->execute("DELETE FROM $this->table WHERE id = :id", ["id" => $id])->getResult();
	 }

}