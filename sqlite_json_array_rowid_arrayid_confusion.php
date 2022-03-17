<?php

//
// This demonstrates how json_each() will overwrite a resulting row's 
// `id` column, and how to retrieve the actual rowid.
// 
// The SQLite documentation for json_each() is here:
// - https://www.sqlite.org/json1.html#jeach
// 
// It does mention that: "The "id" column is an integer that identifies a 
//     specific JSON element within the complete JSON string."
// 
// However, if you skip the details and go straight to writing code, as I did,
// then you'd likely miss that detail or its implications. The implication,
// demonstrated below, is that it will overwrite your row result's `id` column
// with the array index of your query.
// 
// For example, an array containing [2,4,6,8], searching for '4', will return
// an id of '2', even if your table's id column for that row is, say `100`.
// 
// To get the original rowid, you must explicitly query for it. For example:
//    SELECT $tableName.id as rowid, $tableName.*, *
// ...this will return the original row data's id as `rowid`, will still
// returning the array's index as `id`.
// 
// Below is code that demonstrates this problem and solution.   
// 


////////////////////////////////////////////////////////////////////////////////
$dbFileName = __FILE__.'.db';
$tableName = "test_sqlite_json_array";

$numRows = 10;

$qCreateTable = '';
if (!file_exists($dbFileName)) {
	$qCreateTable = "CREATE TABLE $tableName (id INTEGER PRIMARY KEY,
	                                          entries JSON);";
	$db = new \PDO("sqlite:" . $dbFileName);
	$db->exec($qCreateTable);

	for ($i = 1; $i <= $numRows; $i++) {
		$randEntries = rand(1,5);
		$entries = [];
		for ($e = 1; $e <= $randEntries; $e++) {
			$entries[] = rand(1,10);
		}
		sort($entries);
		//
		// leave array_unique() commented to see how "duplicate" rows are still left
		// in when using SELECT DISTINCT. This is detailed more at the end of this
		// file.
		//
		// Delete the database file, then uncomment it to regenerate the db and
		// see that unique rows are returned, as you'd expect.
		//
		// The lesson here is that duplicate array values can return duplicate rows.
		//
		// array_unique($entries);
		//
		$row = json_encode($entries);
		while (1) {
			$db->exec("INSERT INTO $tableName(entries) VALUES ('$row')");
			if ('00000' === $db->errorCode()) {
				echo ".";
				break;
			} else {
				echo "\nerr: [".$db->errorCode()."]: ".print_r($db->errorInfo(),1)."\n";
				exit;
			}
		}
	}
	$db = null;
	unset($db);
}


////////////////////////////////////////////////////////////////////////////////
$db = new \PDO("sqlite:" . $dbFileName);


//------------------------------------------------------------------------------
//
// this shows all rows with the proper ids.
// you get the "proper" id with $row['id'] because there is no json_each() or
// json_tree() query.
//
echo "\n\n---------------------------------------------------\n";
echo "All rows in the table.\n";
for ($i = 1; $i <= $numRows; $i++)
{
	$res = $db->query("SELECT * FROM $tableName WHERE id=$i;");
	if (false === $res) { echo "not found: $i\n"; }
	while ($row = $res->fetch(\PDO::FETCH_ASSOC)) {
		echo "\t{$row['id']} : {$row['entries']}\n";
	}
}


//------------------------------------------------------------------------------
//
// now, we'll query and see that the `id` is actuall the index in the array
// of where our queried value is located as the array index.
//
// NOTICE THAT THE `id` VALUE IS NOT THE ROW'S `id`
//
echo "\n\n---------------------------------------------------\n";
echo "All rows in the table: 'WRONG' id. Displays array index, not row id.\n";
for ($i = 1; $i <= $numRows; $i++)
{
	$res = $db->query("SELECT *
										 FROM   $tableName, json_each($tableName.entries)
										 WHERE  json_each.value LIKE '$i'
										 ORDER BY id ASC;");
	if (false === $res) { echo "not found: $i\n"; }
	while ($row = $res->fetch(\PDO::FETCH_ASSOC)) {
		echo "\t{$row['id']} : {$row['entries']}\n";
		print_r($row);
	}
	echo "\n";
}


//------------------------------------------------------------------------------
//
// now we'll adjust the query to extract our row's `id` as `rowid`
// ALSO IMPORTANT: if any of your table's columsn are in the raw array output
// (key, value, type, atom, parent, fullkey, path), you'll need to also
// extract those using `as` values as well.
//
echo "\n\n---------------------------------------------------\n";
echo "All rows in the table: Use's `rowid` for the row's ID, with array data.\n";
for ($i = 1; $i <= $numRows; $i++)
{
	$res = $db->query("SELECT $tableName.id as rowid, $tableName.*, *
										 FROM   $tableName, json_each($tableName.entries)
										 WHERE  json_each.value LIKE '$i'
										 ORDER BY $tableName.rowid ASC;");
	if (false === $res) { echo "not found: $i\n"; }
	while ($row = $res->fetch(\PDO::FETCH_ASSOC)) {
		echo "\t{$row['rowid']} : {$row['entries']}\n";
		print_r($row);
	}
	echo "\n";
}


//------------------------------------------------------------------------------
//
// IMPORTANT: NOTICE THAT SELECT DISTINCT DOES NOT WORK WHEN
//            ARRAYS HAVE DUPLICATE ENTRIES.
//
// I haven't looked hard at this, but I'm guessing it's because, to be
// considered a duplicate row, all resulting data must be the same.
// Since the array indexes are different, the results are "technically"
// different, even though the rowid is the same.
//
// It appears the solution is to make sure you don't have duplicates in your
// array. Or some other way of filtering this that I don't know about and
// haven't looked to figure out.
//
echo "\n\n---------------------------------------------------\n";
echo "All rows in the table: Use's `rowid` for the row's ID, with array data.\n";
for ($i = 1; $i <= $numRows; $i++)
{
	$res = $db->query("SELECT DISTINCT $tableName.id as rowid, $tableName.*, *
										 FROM   $tableName, json_each($tableName.entries)
										 WHERE  json_each.value LIKE '$i'
										 ORDER BY $tableName.rowid ASC;");
	if (false === $res) { echo "not found: $i\n"; }
	echo "\n---------------------------\n";
	while ($row = $res->fetch(\PDO::FETCH_ASSOC)) {
		echo "\t{$row['rowid']} : {$row['entries']}\n";
		print_r($row);
	}
	echo "\n";
}


////////////////////////////////////////////////////////////////////////////////
$db = null;
unset($db);
