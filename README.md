# myOwnDataBaseWrapper
My own PHP database wrapper for easy query

[x] Make everything a preapred statement

[x] Put back the lastQuery, to check if the query is the same to need rePrepare it (it is faster)

[ ] Make with so when a table is in another schema, the escapeWithBackSticks remove the dot from the string ( ex 'bob.table' => 'bob'.'table' )

[ ] Find a way to retrieve information like mysqli_info();

[ ] Accept parameters for `WHERE IN (? ? ? ?)` *array_merge*

[ ] Set alias for table in the config

[ ] check `$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);` with `true`

[ ] Add to .CSV method (or other type of file)

[ ] Re-do all

[ ] Change the `execute` function to `select`,`insert`,`update`,`delete`
