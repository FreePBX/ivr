<?php
 /* $Id$ */


function ivr_init() {
    global $db;

    // Check to make sure that install.sql has been run
    $sql = "SELECT deptname from ivr where displayname='__install_done' LIMIT 1";

    $results = $db->getAll($sql, DB_FETCHMODE_ASSOC);

    if (DB::IsError($results)) {
            // It couldn't locate the table. This is bad. Lets try to re-create it, just
            // in case the user has had the brilliant idea to delete it.
            // runModuleSQL taken from page.module.php. It's inclusion here is probably
            // A bad thing. It should be, I think, globally available.
            localrunModuleSQL('ivr', 'uninstall');
            if (localrunModuleSQL('ivr', 'install')==false) {
                    echo _("There is a problem with install.sql, cannot re-create databases. Contact support\n");
                    die;
            } else {
                    $results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
            }
    }
    
    if (!isset($results[0])) {
        // Note: There's an invalid entry created, __invalid, after this is run,
        // so as long as this has been run _once_, there will always be a result.

		// Read old IVR format, part of xtns..
		$sql = "SELECT context,descr FROM extensions WHERE extension = 's' AND application LIKE 'DigitTimeout' AND context LIKE '".$dept."aa_%' ORDER BY context,priority";
		$unique_aas = $db->getAll($sql);
		if (isset($unique_aas)) {
			foreach($unique_aas as $aa){
				// This gets all the menu options
				$id = ivr_get_ivr_id($aa[1]);
				// Save the old name, with a link to the new name, for upgrading
				$ivr_newname[$aa[0]] = "ivr-$id";
				// Get the old config
				$sql = "SELECT extension,args from extensions where application='Goto' and context='{$aa[0]}'";
				$cmds = $db->getAll($sql, DB_FETCHMODE_ASSOC);
				if (isset($cmds)) {
					// There were some actions, so loop through them
					foreach ($cmds as $cmd) {
						$arr=explode(',', $cmd['args']);
						// s == old stuff. We don't care.
						if ($arr[0] != 's') 
							ivr_add_command($id,$cmd['extension'],$cmd['args'],0);
					}
				}
			}
			// Now. Upgrade all the links inside the old IVR's
			if (isset($ivr_newname)) {
				// Some IVR's were upgraded
				$sql = "SELECT * FROM ivr_dests WHERE dest LIKE '%aa_%'";
				$dests = $db->getAll($sql, DB_FETCHMODE_ASSOC);
				if (isset($dests)) {
					foreach ($dests as $dest) {
						$arr=explode(',', $dest['dest']);
						sql("UPDATE ivr_dests set dest='".$ivr_newname[$arr[0]].",s,1' where ivr_id='".$dest['ivr_id']."' and selection='".$dest['selection']."'");
					}
				}
			}
	
			// Upgrade everything using IVR as a destination. Ick.
	
			// Are queue's using an ivr failover?
			// ***FIXME*** if upgrading queues away from legacy cruft.
			$queues = $db->getAll("select extensions,args from extensions where args LIKE '%aa_%' and context='ext-queues' and priority='6'"); 
			if (count($res) != 0) {
				foreach ($queues as $q) {
					$arr=explode(',', $q['args']);
					sql("UPDATE extensions set args='".$ivr_newname[$arr[0]].",s,1' where context='ext-queues' and priority='6' and extension='".$q['extension']."'");
	                            }
			}
	
			// Now process everything else - if there's anything to process.
			if (isset($ivr_newname) && is_array($ivr_newname)) {
				foreach (array_keys($ivr_newname) as $old) {
					// Timeconditions
					sql("UPDATE timeconditions set truegoto='".$ivr_newname[$arr[0]].",s,1' where truegoto='$old,s,1'");
					sql("UPDATE timeconditions set falsegoto='".$ivr_newname[$arr[0]].",s,1' where falsegoto='$old,s,1'");
					// Inbound Routes
					sql("UPDATE incoming set destination='".$ivr_newname[$arr[0]].",s,1' where destination='$old,s,1'");
					// Ring Groups
					sql("UPDATE ringgroups set postdest='".$ivr_newname[$arr[0]].",s,1' where postdest='$old,s,1'");
				}
			}
		} 
		// Note, the __install_done line is for internal version checking - the second field
		// should be incremented and checked if the database ever changes.
		$result = sql("INSERT INTO ivr (displayname, deptname) VALUES ('__install_done', '1')");
		needreload();
    }
}

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function ivr_destinations() {
	//get the list of IVR's
	$results = ivr_list();

	// return an associative array with destination and description
	if (isset($results)) {
		foreach($results as $result){
			$extens[] = array('destination' => 'ivr-'.$result['ivr_id'].',s,1', 'description' => $result['displayname']);
		}
	}
	if (isset($extens)) 
		return $extens;
	else
		return null;
}

function ivr_getdest($exten) {
	return array('ivr-'.$exten.',s,1');
}

function ivr_getdestinfo($dest) {
	global $active_modules;

	if (substr(trim($dest),0,4) == 'ivr-') {
		$exten = explode(',',$dest);
		$exten = substr($exten[0],4);

		$thisexten = ivr_get_details($exten);
		if (empty($thisexten)) {
			return array();
		} else {
			//$type = isset($active_modules['ivr']['type'])?$active_modules['ivr']['type']:'setup';
			return array('description' => 'IVR : '.$thisexten['displayname'],
			             'edit_url' => 'config.php?display=ivr&action=edit&id='.urlencode($exten),
								  );
		}
	} else {
		return false;
	}
}

function ivr_recordings_usage($recording_id) {
	global $active_modules;

	$results = sql("SELECT `ivr_id`, `displayname` FROM `ivr` WHERE `announcement_id` = '$recording_id'","getAll",DB_FETCHMODE_ASSOC);
	if (empty($results)) {
		return array();
	} else {
		//$type = isset($active_modules['ivr']['type'])?$active_modules['ivr']['type']:'setup';
		foreach ($results as $result) {
			$usage_arr[] = array(
				'url_query' => 'config.php?display=ivr&action=edit&id='.urlencode($result['ivr_id']),
				'description' => "IVR: ".$result['displayname'],
			);
		}
		return $usage_arr;
	}
}

function ivr_get_config($engine) {
        global $ext;
        global $conferences_conf;

	switch($engine) {
		case "asterisk":
			$ivrlist = ivr_list();
			if(is_array($ivrlist)) {
				foreach($ivrlist as $item) {
					$id = "ivr-".$item['ivr_id'];
					$details = ivr_get_details($item['ivr_id']);

					$announcement_id = (isset($details['announcement_id']) ? $details['announcement_id'] : '');
					$loops = (isset($details['loops']) ? $details['loops'] : '2');

					if (!empty($details['enable_directdial'])) {
						// MODIFIED (PL)
						// always include ext-findmefollow whether or not the module is currenlty
						// enabled since subsequent activations should work without regenerating the
						// ivr. (and no harm done if context does not exist.
						//
						$ext->addInclude($id,'from-did-direct-ivr');
					}
					// I'm not sure I like the ability of people to send voicemail from the IVR.
					// Make it a config option, possibly?
                                        // $ext->addInclude($item[0],'app-messagecenter');
					if (!empty($details['enable_directory'])) {
						$ext->addInclude($id,'app-directory');
						$dir = featurecodes_getFeatureCode('infoservices', 'directory');
						$ext->add($id, '#' ,'', new ext_dbdel('${BLKVM_OVERRIDE}'));
						$ext->add($id, '#' ,'', new ext_setvar('__NODEST', ''));
						$ext->add($id, '#', '', new ext_goto("app-directory,$dir,1"));
					}

					$ext->add($id, 'h', '', new ext_hangup(''));
					$ext->add($id, 's', '', new ext_setvar('LOOPCOUNT', 0));
					$ext->add($id, 's', '', new ext_setvar('__DIR-CONTEXT', $details['dircontext']));
					$ext->add($id, 's', '', new ext_setvar('_IVR_CONTEXT_${CONTEXT}', '${IVR_CONTEXT}'));
					$ext->add($id, 's', '', new ext_setvar('_IVR_CONTEXT', '${CONTEXT}'));
					$ext->add($id, 's', '', new ext_gotoif('$["${CDR(disposition)}" = "ANSWERED"]','begin'));
					$ext->add($id, 's', '', new ext_answer(''));
					$ext->add($id, 's', '', new ext_wait('1'));
					$ext->add($id, 's', 'begin', new ext_digittimeout(3));
					$ext->add($id, 's', '', new ext_responsetimeout($details['timeout']));
					if ($announcement_id != '') {
						$announcement_msg = recordings_get_file($announcement_id);
						$ext->add($id, 's', '', new ext_background($announcement_msg));
					}
					$ext->add($id, 's', '', new ext_waitexten());
					$ext->add($id, 'hang', '', new ext_playback('vm-goodbye'));
					$ext->add($id, 'hang', '', new ext_hangup(''));

					$default_t=true;

					// Actually add the IVR commands now.
					$dests = ivr_get_dests($item['ivr_id']);
					$timeout=false;
					$invalid=false;
					$addloop=false;
					if (!empty($dests)) {
						foreach($dests as $dest) {
							if ($dest['selection'] == 't' && empty($details['alt_timeout'])) {
							 	$timeout=true;
							} elseif ($dest['selection'] == 'i' && empty($details['alt_invalid'])) {
							 	$invalid=true;
							} elseif (($dest['selection'] == 't' && !empty($details['alt_timeout']))) {
							 	$timeout=true;
								$ext->add($id, $dest['selection'], '', new ext_setvar('LOOPCOUNT','$[${LOOPCOUNT} + 1]'));	
								$ext->add($id, $dest['selection'], '', new ext_gotoif('$[${LOOPCOUNT} <= '.$loops.']','s,begin'));
							} elseif (($dest['selection'] == 'i' && !empty($details['alt_invalid']))) {
							 	$invalid=true;
								$ext->add($id, $dest['selection'], '', new ext_setvar('LOOPCOUNT','$[${LOOPCOUNT} + 1]'));	
								//$ext->add($id, $dest['selection'], '', new ext_playback('invalid'));
								$ext->add($id, $dest['selection'], '', new ext_execif('$[${LOOPCOUNT} <= '.$loops.']','Playback','invalid'));
								$ext->add($id, $dest['selection'], '', new ext_gotoif('$[${LOOPCOUNT} <= '.$loops.']','s,begin'));
							}
							$ext->add($id, $dest['selection'],'', new ext_dbdel('${BLKVM_OVERRIDE}'));
							$ext->add($id, $dest['selection'],'', new ext_setvar('__NODEST', ''));

							// if the goto goes loops back to this ivr, then don't go to the begining or it will break the return to previous ivr info
							//
							$dest_context = trim(strtok($dest['dest'],",|"));
							if ($dest_context == $id) {
								$dest['dest'] = $id.',s,begin';
							}

							if ($dest['ivr_ret']) {
								$ext->add($id, $dest['selection'],'', new ext_gotoif('$["x${IVR_CONTEXT_${CONTEXT}}" = "x"]', $dest['dest'].':${IVR_CONTEXT_${CONTEXT}},return,1'));
							} else {
								$ext->add($id, $dest['selection'],'', new ext_goto($dest['dest']));
							}
						}
					}
					// Apply invalid if required
					if (!$invalid) {
						//$ext->add($id, 'i', '', new ext_noop('default i dest'));
						$ext->add($id, 'i', '', new ext_playback('invalid'));
						$ext->add($id, 'i', '', new ext_goto('loop,1'));
						$addloop=true;
					}
					if (!$timeout) {
						//$ext->add($id, 't', '', new ext_noop('default t dest'));
						$ext->add($id, 't', '', new ext_goto('loop,1'));
						$addloop=true;
					}
					if ($addloop) {
						$ext->add($id, 'loop', '', new ext_setvar('LOOPCOUNT','$[${LOOPCOUNT} + 1]'));	
						$ext->add($id, 'loop', '', new ext_gotoif('$[${LOOPCOUNT} > '.$loops.']','hang,1'));
						$ext->add($id, 'loop', '', new ext_goto($id.',s,begin'));

						// these need to be reset or inheritance problems makes them go away in some conditions and infinite inheritance creates other problems
						//
						$ext->add($id, 'return', '', new ext_setvar('_IVR_CONTEXT', '${CONTEXT}'));
						$ext->add($id, 'return', '', new ext_setvar('_IVR_CONTEXT_${CONTEXT}', '${IVR_CONTEXT_${CONTEXT}}'));
						$ext->add($id, 'return', '', new ext_goto($id.',s,begin'));
					}
					$ext->add($id, 'fax', '', new ext_goto('ext-fax,in_fax,1'));
				}
			}
		break;
	}
}



function ivr_get_ivr_id($name) {
	global $db;
	$res = $db->getRow("SELECT ivr_id from ivr where displayname='$name'");
	if (count($res) == 0) {
		// It's not there. Create it and return the ID
		sql("INSERT INTO ivr (displayname, enable_directory, enable_directdial, timeout, alt_timeout, alt_invalid, `loops`)  values('$name', 'CHECKED', 'CHECKED', 10, '', '', 2)");
		$res = $db->getRow("SELECT ivr_id from ivr where displayname='$name'");
		needreload();
	}
	return ($res[0]);
	
}

function ivr_add_command($id, $cmd, $dest, $ivr_ret) {
	global $db;
	// Does it already exist?
	$res = $db->getRow("SELECT * from ivr_dests where ivr_id='$id' and selection='$cmd'");
	$ivr_ret = $ivr_ret ? 1 : 0;
	if (count($res) == 0) {
		// Just add it.
		sql("INSERT INTO ivr_dests VALUES('$id', '$cmd', '$dest', '$ivr_ret')");
	} else {
		// Update it.
		sql("UPDATE ivr_dests SET dest='$dest', ivr_ret='$ivr_ret' where ivr_id='$id' and selection='$cmd'");
	}
	needreload();
}
function ivr_do_edit($id, $post) {

	$displayname = isset($post['displayname'])?$post['displayname']:'';
	$timeout = isset($post['timeout'])?$post['timeout']:'';
	$ena_directory = isset($post['ena_directory'])?$post['ena_directory']:'';
	$ena_directdial = isset($post['ena_directdial'])?$post['ena_directdial']:'';
	$annmsg_id = isset($post['annmsg_id'])?$post['annmsg_id']:'';
	$dircontext = isset($post['dircontext'])?$post['dircontext']:'';

	$loops = isset($post['loops'])?$post['loops']:'2';
	$alt_timeout = isset($post['alt_timeout'])?$post['alt_timeout']:'';
	$alt_invalid = isset($post['alt_invalid'])?$post['alt_invalid']:'';

	if (!empty($ena_directory)) {
		$ena_directory='CHECKED';
	}
	if (!empty($ena_directdial)) {
		$ena_directdial='CHECKED';
	}
	if (!empty($alt_timeout)) {
		$alt_timeout='CHECKED';
	}
	if (!empty($alt_invalid)) {
		$alt_invalid='CHECKED';
	}
	
	sql("UPDATE ivr SET displayname='$displayname', enable_directory='$ena_directory', enable_directdial='$ena_directdial', timeout='$timeout', announcement_id='$annmsg_id', dircontext='$dircontext', alt_timeout='$alt_timeout', alt_invalid='$alt_invalid', `loops`='$loops' WHERE ivr_id='$id'");

	// Delete all the old dests
	sql("DELETE FROM ivr_dests where ivr_id='$id'");
	// Now, lets find all the goto's in the post. Destinations return gotoN => foo and get fooN for the dest.
	// Is that right, or am I missing something?
	foreach(array_keys($post) as $var) {
		if (preg_match('/goto(\d+)/', $var, $match)) {
			// This is a really horrible line of code. take N, and get value of fooN. See above. Note we
			// get match[1] from the preg_match above
			$dest = $post[$post[$var].$match[1]];
			$cmd = $post['option'.$match[1]];
			$ivr_ret = $post['ivr_ret'.$match[1]];
			// Debugging if it all goes pear shaped.
			// print "I think pushing $cmd does $dest<br>\n";
			if (strlen($cmd))
				ivr_add_command($id, $cmd, $dest, $ivr_ret);
		}
	}
	needreload();
}


function ivr_list() {
	global $db;

	$sql = "SELECT * FROM ivr where displayname <> '__install_done' ORDER BY displayname";
        $res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
        if(DB::IsError($res)) {
		return null;
        }
        return $res;
}

function ivr_get_details($id) {
	global $db;

	$sql = "SELECT * FROM ivr where ivr_id='$id'";
        $res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
        if(DB::IsError($res)) {
		return null;
        }
        return $res[0];
}

function ivr_get_dests($id) {
	global $db;

	$sql = "SELECT selection, dest, ivr_ret FROM ivr_dests where ivr_id='$id' ORDER BY selection";
        $res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
        if(DB::IsError($res)) {
                return null;
        }
        return $res;
}
	
function ivr_get_name($id) {
	$res = ivr_get_details($id);
	if (isset($res['displayname'])) {
		return $res['displayname'];
	} else {
		return null;
	}
}

function ivr_check_destinations($dest=true) {
	global $active_modules;

	$destlist = array();
	if (is_array($dest) && empty($dest)) {
		return $destlist;
	}
	$sql = "SELECT dest, displayname, selection, a.ivr_id ivr_id FROM ivr a INNER JOIN ivr_dests d ON a.ivr_id = d.ivr_id  ";
	if ($dest !== true) {
		$sql .= "WHERE dest in ('".implode("','",$dest)."')";
	}
	$sql .= "ORDER BY displayname";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	//$type = isset($active_modules['ivr']['type'])?$active_modules['ivr']['type']:'setup';

	foreach ($results as $result) {
		$thisdest = $result['dest'];
		$thisid   = $result['ivr_id'];
		$destlist[] = array(
			'dest' => $thisdest,
			'description' => 'IVR: '.$result['displayname'].' / Option: '.$result['selection'],
			'edit_url' => 'config.php?display=ivr&action=edit&id='.urlencode($thisid),
		);
	}
	return $destlist;
}
?>
