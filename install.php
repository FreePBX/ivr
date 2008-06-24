<?php
sql('CREATE TABLE IF NOT EXISTS ivr ( ivr_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, displayname VARCHAR(50), deptname VARCHAR(50), enable_directory VARCHAR(8), enable_directdial VARCHAR(8), timeout INT, announcement VARCHAR(255), dircontext VARCHAR ( 50 ) DEFAULT "default", alt_timeout VARCHAR(8), alt_invalid VARCHAR(8), `loops` TINYINT(1) NOT NULL DEFAULT 2 )');
sql('CREATE TABLE IF NOT EXISTS ivr_dests ( ivr_id INT NOT NULL, selection VARCHAR(10), dest VARCHAR(50), ivr_ret TINYINT(1) NOT NULL DEFAULT 0)');

global $db;

// Now, we need to check for upgrades. 
// V1.0, old IVR. You shouldn't see this, but check for it anyway, and assume that it's 2.0
// V2.0, Original Release
// V2.1, added 'directorycontext' to the schema
// v2.2, announcement changed to support filenames instead of ID's from recordings table
// 

$ivr_modcurrentvers = modules_getversion('ivr');

// Add the col
$sql = "SELECT dircontext FROM ivr";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
    $sql = 'ALTER TABLE ivr ADD COLUMN dircontext VARCHAR ( 50 ) DEFAULT "default"';
    $result = $db->query($sql);
    if(DB::IsError($result)) {
            die_freepbx($result->getDebugInfo());
    }
}

if (version_compare($ivr_modcurrentvers, "2.2", "<")) {
	//echo "<p>Start 2.2 upgrade</p>";
	$sql = "ALTER TABLE ivr CHANGE COLUMN announcement announcement VARCHAR ( 255 )";
    $result = $db->query($sql);
    if(DB::IsError($result)) {
            die_freepbx($result->getDebugInfo());
    } else {
    	// Change existing records
    	//echo "<p>Updating existing records</p>";
    	$existing = sql("SELECT DISTINCT announcement FROM ivr WHERE displayname <> '__install_done' AND announcement IS NOT NULL", "getAll");
    	foreach ($existing as $item) {
    		$recid = $item[0];
    		//echo "<p>processing '$recid'</p>";
    		$sql = "SELECT filename FROM recordings WHERE id = '$recid' AND displayname <> '__invalid'";
    		$recordings = sql($sql, "getRow");
    		if (is_array($recordings)) {
    			$filename = (isset($recordings[0]) ? $recordings[0] : '');
    			//echo "<p>filename: $filename";
    			if ($filename != '') {
    				$sql = "UPDATE ivr SET announcement = '".str_replace("'", "''", $filename)."' WHERE announcement = '$recid'";
				    $upcheck = $db->query($sql);
				    if(DB::IsError($upcheck))
				            die_freepbx($upcheck->getDebugInfo());    				
    			}
    		}
    	}
    }
}

// Version 2.5.7 adds auto-return to IVR
$sql = "SELECT ivr_ret FROM ivr_dests";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
    $sql = "ALTER TABLE ivr_dests ADD ivr_ret TINYINT(1) NOT NULL DEFAULT 0;";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
}

$results = array();
$sql = "SELECT ivr_id, selection, dest FROM ivr_dests";
$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
if (!DB::IsError($results)) { // error - table must not be there
	foreach ($results as $result) {
		$old_dest  = $result['dest'];
		$ivr_id    = $result['ivr_id'];
		$selection = $result['selection'];

		$new_dest = merge_ext_followme(trim($old_dest));
		if ($new_dest != $old_dest) {
			$sql = "UPDATE ivr_dests SET dest = '$new_dest' WHERE ivr_id = $ivr_id AND selection = '$selection' AND dest = '$old_dest'";
			$results = $db->query($sql);
			if(DB::IsError($results)) {
				die_freepbx($results->getMessage());
			}
		}
	}
}

// Version 2.5.17 adds improved i and t destination handling
$sql = "SELECT alt_timeout FROM ivr";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
    $sql = "ALTER TABLE ivr ADD alt_timeout VARCHAR(8);";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
}
$sql = "SELECT alt_invalid FROM ivr";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
    $sql = "ALTER TABLE ivr ADD alt_invalid VARCHAR(8);";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
}
$sql = "SELECT `loops` FROM ivr";
$check = $db->getRow($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($check)) {
	// add new field
    $sql = "ALTER TABLE ivr ADD `loops` TINYINT(1) NOT NULL DEFAULT 2;";
    $result = $db->query($sql);
    if(DB::IsError($result)) { die_freepbx($result->getDebugInfo()); }
}
?>
