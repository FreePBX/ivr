<?php

global $db;

// Needed for 2.1 upgrade
$sql = "SELECT dircontext FROM ivr";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
    $sql = 'ALTER TABLE ivr ADD COLUMN dircontext VARCHAR ( 50 ) DEFAULT "default"';
    $result = $db->query($sql);
    if(DB::IsError($result)) {
            die($result->getDebugInfo());
    }
}

?>

