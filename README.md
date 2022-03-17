# sqlite_json_array_rowid_arrayid_confusion
This demonstrates how json_each() will overwrite a resulting row's 
`id` column, and how to retrieve the actual rowid.

The SQLite documentation for json_each() is here:
- https://www.sqlite.org/json1.html#jeach

It does mention that: "The "id" column is an integer that identifies a 
    specific JSON element within the complete JSON string."

However, if you skip the details and go straight to writing code, as I did,
then you'd likely miss that detail or its implications. The implication,
demonstrated below, is that it will overwrite your row result's `id` column
with the array index of your query.

For example, an array containing [2,4,6,8], searching for '4', will return
an id of '2', even if your table's id column for that row is, say `100`.

To get the original rowid, you must explicitly query for it. For example:
   SELECT $tableName.id as rowid, $tableName.*, *
...this will return the original row data's id as `rowid`, will still
returning the array's index as `id`.

This repo has code that demonstrates this problem and solution.   
