<?php
 /* $Id$ */
dbug($_REQUEST);

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

function ivr_get_config($engine) {
	global $ext;

	switch($engine) {
		case "asterisk":
			$ddial_contexts = array();
			$ivrlist = ivr_list();
			if(is_array($ivrlist)) {
				foreach($ivrlist as $item) {
					$id = "ivr-".$item['ivr_id'];
					$details = ivr_get_details($item['ivr_id']);

					$announcement_id = (isset($details['announcement_id']) ? $details['announcement_id'] : '');
					$timeout_id = (isset($details['timeout_id']) ? $details['timeout_id'] : '');
					$invalid_id = (isset($details['invalid_id']) ? $details['invalid_id'] : '');
					$loops = (isset($details['loops']) ? $details['loops'] : '2');
					$retvm = (isset($details['retvm']) ? $details['retvm'] : '');

					if (!empty($details['enable_directdial'])) {
						if ($details['enable_directdial'] == 'CHECKED') {
							$ext->addInclude($id,'from-did-direct-ivr'); //generated in core module
						} else {
							$ext->addInclude($id,'from-ivr-directory-'.$details['enable_directdial']);
							$ddial_contexts[$details['enable_directdial']] = true;
						}
					}
					// I'm not sure I like the ability of people to send voicemail from the IVR.
					// Make it a config option, possibly?
                                        // $ext->addInclude($item[0],'app-messagecenter');
					if (!empty($details['enable_directory'])) {
						$ext->addInclude($id,'app-directory');
						$dir = featurecodes_getFeatureCode('infoservices', 'directory');
						$ext->add($id, '#' ,'', new ext_macro('blkvm-clr'));
						$ext->add($id, '#' ,'', new ext_setvar('__NODEST', ''));
						$ext->add($id, '#', '', new ext_goto("app-directory,$dir,1"));
					}

					$ext->add($id, 'h', '', new ext_hangup(''));
					if ($announcement_id) {
						$announcement_msg = recordings_get_file($announcement_id);
						$ext->add($id, 's', '', new ext_setvar('MSG', "$announcement_msg"));
					} else {
						$ext->add($id, 's', '', new ext_setvar('MSG', ""));
					}
					$ext->add($id, 's', '', new ext_setvar('LOOPCOUNT', 0));
					$ext->add($id, 's', '', new ext_setvar('__DIR-CONTEXT', $details['dircontext']));
					$ext->add($id, 's', '', new ext_setvar('_IVR_CONTEXT_${CONTEXT}', '${IVR_CONTEXT}'));
					$ext->add($id, 's', '', new ext_setvar('_IVR_CONTEXT', '${CONTEXT}'));
					$ext->add($id, 's', '', new ext_gotoif('$["${CDR(disposition)}" = "ANSWERED"]','begin'));
					$ext->add($id, 's', '', new ext_answer(''));
					$ext->add($id, 's', '', new ext_wait('1'));
					$ext->add($id, 's', 'begin', new ext_digittimeout(3));
					$ext->add($id, 's', '', new ext_responsetimeout($details['timeout']));

					if ($retvm) {
						$ext->add($id, 's', '', new ext_setvar('__IVR_RETVM', 'RETURN'));
					} else {
						$ext->add($id, 's', '', new ext_setvar('__IVR_RETVM', ''));
					}

					$ext->add($id, 's', '', new ext_execif('$["${MSG}" != ""]','Background','${MSG}'));
					$ext->add($id, 's', '', new ext_waitexten($details['timeout']));
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
								if ($timeout_id) {
									$timeout_msg = recordings_get_file($timeout_id);
									$ext->add($id, $dest['selection'], '', new ext_setvar('MSG',"$timeout_msg"));	
								}
								$ext->add($id, $dest['selection'], '', new ext_setvar('LOOPCOUNT','$[${LOOPCOUNT} + 1]'));	
								$ext->add($id, $dest['selection'], '', new ext_gotoif('$[${LOOPCOUNT} <= '.$loops.']','s,begin'));
							} elseif (($dest['selection'] == 'i' && !empty($details['alt_invalid']))) {
							 	$invalid=true;
								$ext->add($id, $dest['selection'], '', new ext_setvar('LOOPCOUNT','$[${LOOPCOUNT} + 1]'));	


								if ($invalid_id) {
									$invalid_msg = recordings_get_file($invalid_id);
									$ext->add($id, $dest['selection'], '', new ext_setvar('MSG',"$invalid_msg"));	
								} else {
									$ext->add($id, $dest['selection'], '', new ext_execif('$[${LOOPCOUNT} <= '.$loops.']','Playback','invalid'));
								}
								$ext->add($id, $dest['selection'], '', new ext_gotoif('$[${LOOPCOUNT} <= '.$loops.']','s,begin'));
							}
							$ext->add($id, $dest['selection'],'', new ext_macro('blkvm-clr'));
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
						if ($invalid_id) {
							$invalid_msg = recordings_get_file($invalid_id);
							$ext->add($id, 'i', '', new ext_setvar('MSG',"$invalid_msg"));	
						} else {
							$ext->add($id, 'i', '', new ext_playback('invalid'));
						}
						$ext->add($id, 'i', '', new ext_goto('loop,1'));
						$addloop=true;
					}
					if (!$timeout) {
						if ($timeout_id) {
							$timeout_msg = recordings_get_file($timeout_id);
							$ext->add($id, 't', '', new ext_setvar('MSG',"$timeout_msg"));	
						}
						$ext->add($id, 't', '', new ext_goto('loop,1'));
						$addloop=true;
					}
					if ($addloop) {
						$ext->add($id, 'loop', '', new ext_setvar('LOOPCOUNT','$[${LOOPCOUNT} + 1]'));	
						$ext->add($id, 'loop', '', new ext_gotoif('$[${LOOPCOUNT} > '.$loops.']','hang,1'));
						$ext->add($id, 'loop', '', new ext_goto($id.',s,begin'));

						// these need to be reset or inheritance problems makes them go away in some conditions and infinite inheritance creates other problems
						// reset the message including blanking it if set by a sub-ivr
						$announcement_msg = ($announcement_id) ? $announcement_msg : '';
						$ext->add($id, 'return', '', new ext_setvar('MSG', "$announcement_msg"));
						$ext->add($id, 'return', '', new ext_setvar('_IVR_CONTEXT', '${CONTEXT}'));
						$ext->add($id, 'return', '', new ext_setvar('_IVR_CONTEXT_${CONTEXT}', '${IVR_CONTEXT_${CONTEXT}}'));
						$ext->add($id, 'return', '', new ext_goto($id.',s,begin'));
					}
				}

				if (!empty($ddial_contexts)) {
					global $version;
					$ast_lt_14 = version_compare($version, '1.4', 'lt');

					foreach(array_keys($ddial_contexts) as $dir_id) {
						$context = 'from-ivr-directory-'.$dir_id;
						$entries = function_exists('directory_get_dir_entries') ? directory_get_dir_entries($dir_id) : array();
						foreach ($entries as $dstring) {
							$exten = $dstring['dial'] == '' ? $dstring['foreign_id'] : $dstring['dial'];
							if ($exten == '' || $exten == 'custom') {
								continue;
							}
					  	$ext->add($context, $exten,'', new ext_macro('blkvm-clr'));
							$ext->add($context, $exten,'', new ext_setvar('__NODEST', ''));
							$ext->add($context, $exten,'', new ext_goto('1',$exten,'from-internal'));
						}
					}
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
		sql("INSERT INTO ivr (displayname, enable_directory, enable_directdial, timeout, alt_timeout, alt_invalid, `loops`, `retvm`)  values('$name', '', '', 10, '', '', 2, '')");
		$res = $db->getRow("SELECT ivr_id from ivr where displayname='$name'");
	}
	if ($db->IsError($res)){
		die_freepbx($res->getDebugInfo());
	} else {
		return ($res[0]);
	}
	
	
}

function ivr_list() {
	global $db;

	$sql = "SELECT * FROM ivr where displayname != '__install_done' ORDER BY displayname";
	$res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
	if($db->IsError($res)) {
		return null;
	}
	return $res;
}

function ivr_get_details($id = '') {
	global $db;

	$sql = "SELECT * FROM ivr_details";
	if ($id) {
		$sql .= ' where  id = "' . $id . '"';
	}
	$res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
	if($db->IsError($res)) {
		die_freepbx($res->getDebugInfo());
	}

	return $id ? $res[0] : $res;
}

function ivr_get_entires($id) {
	global $db;
	
	//+0 to convert string to an integer
	$sql = "SELECT * FROM ivr_entries WHERE ivr_id = ? ORDER BY selection + 0";
	$res = $db->getAll($sql, array($id), DB_FETCHMODE_ASSOC);
	if ($db->IsError($res)) {
		die_freepbx($res->getDebugInfo());
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

function ivr_configpageload() {
	global $currentcomponent, $display;
	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	$id 	= isset($_REQUEST['id']) ? $_REQUEST['id'] : null;

	if ($action  == 'add' && $id != '') {
		 $currentcomponent->addguielem('_top', new gui_pageheading('title', _('Add Directory')), 0);

		$deet = array('id', 'name', 'description', 'announcement', 'directdial', 
					'invalid_loops', 'invalid_rety_recording', 
					'invalid_recording', 'invalid_destination', 
					'timeout_loops', 'timeout_time',
					'timeout_rety_recording', 'timeout_recording', 'timeout_destination',
					'retvm');
     
		foreach ($deet as $d) {
			switch ($d){
				case 'invalid_loops':
				case 'timeout_loops';
					$ivr[$d] = 2;
					break;
				case 'announcement':
				case 'repeat_recording':
				case 'invalid_recording':
					$ivr[$d] = 0;
					break;
				default:
				$ivr[$d] = '';
					break;
			}
		}
	} else {
		$ivr = ivr_get_details($id);

		$label = sprintf(_("Edit IVR: %s"), $ivr['name'] ? $ivr['name'] : 'ID '.$ivr['id']);
		$currentcomponent->addguielem('_top', new gui_pageheading('title', $label), 0);
		
		//display usage
		$usage_list			= '';//framework_display_destination_usage();
		if (!empty($usage_list)) {
			$usage_list_text	= isset($usage_list['text']) ? $usage_list['text'] : '';
			$usage_list_tooltip	= isset($usage_list['tooltip']) ? $usage_list['tooltip'] : '';
			$currentcomponent->addguielem('_top', 
				new gui_link_label('usage', $usage_list_text, $usage_list_tooltip), 0);
		}
		
		//display delete link
		$label = sprintf(_("Delete IVR: %s"), $ivr['name'] ? $ivr['name'] : 'ID '.$ivr['id']);
		$del 				= '<span><img width="16" height="16" border="0" title="' 
							. $label . '" alt="" src="images/core_delete.png"/>&nbsp;' . $label . '</span>';
		$currentcomponent->addguielem('_top', 
			new gui_link('del', $del, $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '&action=delete', 
				true, false), 0);
	}
	

	$gen_section = _('IVR General Options');
	$currentcomponent->addguielem($gen_section, 
		new gui_textbox('name', stripslashes($ivr['name']), _('IVR Name'), _('Name of this IVR.')));
	$currentcomponent->addguielem($gen_section, 
		new gui_textbox('description', stripslashes($ivr['description']), 
		_('IVR Description'), _('Description of this directory.')));


	$section = _('IVR Options (DTMF)');
	
	//build recordings select list
	$currentcomponent->addoptlistitem('recordings', 0, _('Default'));
	foreach(recordings_list() as $r){
		$currentcomponent->addoptlistitem('recordings', $r['id'], $r['displayname']);
	}
    $currentcomponent->setoptlistopts('recordings', 'sort', false);
	
	//build repeat_loops select list and defualt it to 3
	//while not 100% nesesary, declaring this is the only way to prevent sorting on the list
	$currentcomponent->addoptlist('ivr_repeat_loops', false); 
	$currentcomponent->addoptlistitem('ivr_repeat_loops', 'disabled', 'Disabled');
	for($i=0; $i <11; $i++){
		$currentcomponent->addoptlistitem('ivr_repeat_loops', $i, $i);
	}

	//generate page
	$currentcomponent->addguielem($section, 
		new gui_selectbox('announcement', $currentcomponent->getoptlist('recordings'), 
			$ivr['announcement'], _('Announcement'), _('Greeting to be played on entry to the Ivr.'), false));


	
	//direct dial
	//TODO: hook in from directory	
	$currentcomponent->addoptlistitem('direct_dial', $ivr['directdial'], _('Disabled'));
	$currentcomponent->addoptlistitem('direct_dial', $ivr['directdial'], _('Extensions'));
	$dd_help[] = _('completely disabled');
	$dd_help[] = _('enabled for all extensions on a system');
	//todo: make next line conditional on directory being present
	$dd_help[] = _('tied to a Directory allowing all entries in that directory to be dialed directly, as they appear in the directory');
	$currentcomponent->addguielem($section, 
		new gui_selectbox('directdial', $currentcomponent->getoptlist('direct_dial'), 
		$ivr['directdial'], _('Direct Dial'), _('Provides options for callers to direct dial an extension. Direct dialing can be:') . ul($dd_help), false));
	
	//invalid 
	$currentcomponent->addguielem($section, 
		new gui_selectbox('invalid_loops', $currentcomponent->getoptlist('ivr_repeat_loops'), 
		$ivr['invalid_loops'], _('Invalid Retries'), _('Number of times to retry when receiving an invalid/unmatched response from the caller'), false));
	$currentcomponent->addguielem($section, 
		new gui_selectbox('invalid_rety_recording', $currentcomponent->getoptlist('recordings'), 
		$ivr['invalid_rety_recording'], _('Invalid Retry Recording'), _('Prompt to be played when an invalid/unmatched response is received, before prompting the caller to try again'), false));
	$currentcomponent->addguielem($section, 
		new gui_selectbox('invalid_recording', $currentcomponent->getoptlist('recordings'), 
		$ivr['invalid_recording'], _('Invalid Recording'), _('Prompt to be played before sending the caller to an alternate destination due to the caller pressing 0 or receiving the maximum amount of invalid/unmatched responses (as determined by Invalid Retries)'), false));
	$currentcomponent->addguielem($section, 
		new gui_drawselects('invalid_destination', 'invalid', $ivr['invalid_destination'], _('Invalid Destination'),
		 _('Destination to send the call to after Invalid Recording is played.'), false));
	
	//timeout/invalid 
	$currentcomponent->addguielem($section, 
		new gui_selectbox('timeout_loops', $currentcomponent->getoptlist('ivr_repeat_loops'), 
		$ivr['timeout_loops'], _('Timeout Retries'), _('Number of times to retry when receiving an invalid/unmatched response from the caller'), false));
	$currentcomponent->addguielem($section, 
		new gui_textbox('timeout_time', stripslashes($ivr['timeout_time']), _('Timeout'), _('Amount of time to be concidered a timeout')));
	$currentcomponent->addguielem($section, 
		new gui_selectbox('timeout_rety_recording', $currentcomponent->getoptlist('recordings'), 
		$ivr['timeout_rety_recording'], _('Timeout Retry Recording'), _('Prompt to be played when an invalid/unmatched response is received, before prompting the caller to try again'), false));
	$currentcomponent->addguielem($section, 
		new gui_selectbox('timeout_recording', $currentcomponent->getoptlist('recordings'), 
		$ivr['timeout_recording'], _('Timeout Recording'), _('Prompt to be played before sending the caller to an alternate destination due to the caller pressing 0 or receiving the maximum amount of invalid/unmatched responses (as determined by Invalid Retries)'), false));
	$currentcomponent->addguielem($section, 
		new gui_drawselects('timeout_destination', 'timeout', 
		$ivr['timeout_destination'], _('Timeout Destination'), _('Destination to send the call to after Invalid Recording is played.'), false));
	
	//return to ivr
	$currentcomponent->addguielem($section, 
		new gui_checkbox('retvm', $ivr['retvm'], _('Return to IVR after VM'), _('If checked, upon exiting voicemail a caller will be returned to this IVR if they got a users voicemail')));
		
	/*$currentcomponent->addguielem($section, 
		new gui_checkbox('say_extension', $dir['say_extension'], _('Announce Extension'), 
		_('When checked, the extension number being transferred to will be announced prior to the transfer'),true));*/
	$currentcomponent->addguielem($section, new gui_hidden('id', $ivr['id']));
	$currentcomponent->addguielem($section, new gui_hidden('action', 'save'));


		
	$section = _('Ivr Entries');
	//draw the entries part of the table. A bit hacky perhaps, but hey - it works!
	$currentcomponent->addguielem($section, new guielement('rawhtml', ivr_draw_entries($ivr['id']), ''), 6);
}

function ivr_configpageinit($pagename) {
	global $currentcomponent;
	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	
	if($pagename == 'ivr'){
		$currentcomponent->addprocessfunc('ivr_configprocess');
		
		//dont show page if there is no action set
		if ($action && $action != 'delete') {			
			$currentcomponent->addguifunc('ivr_configpageload');
		}
		
    return true;
	}
}

//prosses received arguments
function ivr_configprocess(){
	if (isset($_REQUEST['display']) && $_REQUEST['display'] == 'ivr'){
		global $db;
		//get variables

		$get_var = array('id', 'name', 'description', 'announcement',
						'directdial', 'invalid_loops', 'invalid_rety_recording',
						'invalid_destination', 'timeout_enabled', 'invalid_recording',
						'retvm', 'invalid_enabled', 'timeout_time', 'timeout_recording',
						'timeout_rety_recording', 'timeout_destination', 'timeout_loops');
		foreach($get_var as $var){
			$vars[$var] = isset($_REQUEST[$var]) 	? $_REQUEST[$var]		: '';
		}

		$action		= isset($_REQUEST['action'])	? $_REQUEST['action']	: '';
		$entries	= isset($_REQUEST['entries'])	? $_REQUEST['entries']	: '';

		switch ($action) {
			case 'save':
			
				//get real dest
				$_REQUEST['id'] = $vars['id'] = ivr_save_details($vars);
				ivr_save_entries($vars['id'], $entries);
				needreload();
				$_REQUEST['action'] = 'edit';
				redirect_standard_continue('id', 'action');
			break;
			case 'delete':
				ivr_delete($vars['id']);
				needreload();
				redirect_standard_continue();
			break;
		}
	}
}

function ivr_save_details($vals){
	global $db, $amp_conf;

	foreach($vals as $key => $value) {
		$vals[$key] = $db->escapeSimple($value);
	}

	if ($vals['id']) {
		$sql = 'REPLACE INTO ivr_details (id, name, description, announcement,
				directdial, invalid_loops, invalid_rety_recording,
				invalid_destination, timeout_enabled, invalid_recording,
				retvm, invalid_enabled, timeout_time, timeout_recording,
				timeout_rety_recording, timeout_destination, timeout_loops)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
		$foo = $db->query($sql, $vals);
		if($db->IsError($foo)) {
			die_freepbx(print_r($vals,true).' '.$foo->getDebugInfo());
		}
	} else {
		unset($vals['id']);
		$sql = 'INSERT INTO ivr_details (name, description, announcement,
				directdial, invalid_loops, invalid_rety_recording,
				invalid_destination, timeout_enabled, invalid_recording,
				retvm, invalid_enabled, timeout_time, timeout_recording,
				timeout_rety_recording, timeout_destination, timeout_loops)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
				
		$foo = $db->query($sql, $vals);
		if($db->IsError($foo)) {
			die_freepbx(print_r($vals,true).' '.$foo->getDebugInfo());
		}
		$sql = ( ($amp_conf["AMPDBENGINE"]=="sqlite3") ? 'SELECT last_insert_rowid()' : 'SELECT LAST_INSERT_ID()');
		$vals['id'] = $db->getOne($sql);
		if ($db->IsError($foo)){
			die_freepbx($foo->getDebugInfo());
		}
	}

	return $vals['id'];
}

function ivr_save_entries($id, $entries){
	global $db;
	$id = $db->escapeSimple($id);
	sql('DELETE FROM ivr_entries WHERE ivr_id = "' . $id . '"');

	if ($entries) {
		for ($i = 0; $i < count($entries['ext']); $i++) {
			//make sure there is an extension set - otherwise SKIP IT
			if ($entries['ext'][$i]) {
				$d[] = array(
							'ivr_id'	=> $id,
							'ext' 		=> $entries['ext'][$i],
							'goto'		=> $entries['goto'][$i],
							'ivr_ret'	=> (isset($entries['ivr_ret'][$i]) ? $entries['ivr_ret'][$i] : '')
						);
			}

		}
		$sql = $db->prepare('INSERT INTO ivr_entries VALUES (?, ?, ?, ?)');
		$res = $db->executeMultiple($sql, $d);
		if ($db->IsError($res)){
			die_freepbx($res->getDebugInfo());
		}
	}
	
	return true;
}


function ivr_draw_entries_table_header_ivr() {
	return  array(_('Ext'), _('Destination'), fpbx_label(_('Return'), _('Return to IVR')), _('Delete'));
}

function ivr_draw_entries_ivr($id) {
	//TEST FUNCTION, DELETE ASAP
	return array(form_input(array('name'	=> 'entries[test1][]','value'	=> 'test1')),
				form_input(array('name'	=> 'entries[test2][]','value'	=> 'test2'))
				);
}

function ivr_draw_entries($id){
	$headers		= mod_func_iterator('draw_entries_table_header_ivr');
	$ivr_entries	= ivr_get_entires($id);
	dbug('$ivr_entries', $ivr_entries);
	if ($ivr_entries) {
		foreach ($ivr_entries as $k => $e) {
			$entries[$k]= $e;
			$entries[$k]['hooks'] = mod_func_iterator('draw_entries_ivr', array('id' => $id, 'ext' => $e['selection']));
		}
	}
	
	$entries['blank'] = array('selection' => '', 'dest' => '', 'ivr_ret' => '');
	$entries['blank']['hooks'] = mod_func_iterator('draw_entries_ivr', array('id' => '', 'ext' => ''));
	
	return load_view(dirname(__FILE__) . '/views/entries.php', 
				array(
					'headers'	=> $headers, 
					'entries'	=>  $entries
				)
			);

}

//used to add row's the entry table
function ivr_draw_entries_tr($id, $realid, $name = '',$foreign_name, $audio = '',$num = '',$e_id = '', $reuse_audio = false){

}

function ivr_delete($id) {
	global $db;
	sql('DELETE FROM ivr_details WHERE id = "' . $db->escapeSimple($id) . '"');
	sql('DELETE FROM ivr_entries WHERE ivr_id = "' . $db->escapeSimple($id) . '"');
}
//----------------------------------------------------------------------------
// Dynamic Destination Registry and Recordings Registry Functions
function ivr_check_destinations($dest=true) {
	global $active_modules;

	$destlist = array();
	if (is_array($dest) && empty($dest)) {
		return $destlist;
	}
	$sql = "SELECT dest, name, selection, a.id id FROM ivr_details a INNER JOIN ivr_entries d ON a.id = d.ivr_id  ";
	if ($dest !== true) {
		$sql .= "WHERE dest in ('".implode("','",$dest)."')";
	}
	$sql .= "ORDER BY name";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	//$type = isset($active_modules['ivr']['type'])?$active_modules['ivr']['type']:'setup';

	foreach ($results as $result) {
		$thisdest = $result['dest'];
		$thisid   = $result['ivr_id'];
		$destlist[] = array(
			'dest' => $thisdest,
			'description' => sprintf(_("IVR: %s / Option: %s"),$result['displayname'],$result['selection']),
			'edit_url' => 'config.php?display=ivr&action=edit&id='.urlencode($thisid),
		);
	}
	return $destlist;
}



function ivr_change_destination($old_dest, $new_dest) {
	global $db;
 	$sql = "UPDATE ivr_dests SET dest = '$new_dest' WHERE dest = '$old_dest'";
 	$db->query($sql);

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
			return array('description' => sprintf(_("IVR: %s"),$thisexten['displayname']),
			             'edit_url' => 'config.php?display=ivr&action=edit&id='.urlencode($exten),
								  );
		}
	} else {
		return false;
	}
}

function ivr_recordings_usage($recording_id) {
	global $active_modules;

	$results = sql("SELECT `ivr_id`, `displayname` FROM `ivr` WHERE `announcement_id` = '$recording_id' || `timeout_id` = '$recording_id' || `invalid_id` = '$recording_id'","getAll",DB_FETCHMODE_ASSOC);
	if (empty($results)) {
		return array();
	} else {
		//$type = isset($active_modules['ivr']['type'])?$active_modules['ivr']['type']:'setup';
		foreach ($results as $result) {
			$usage_arr[] = array(
				'url_query' => 'config.php?display=ivr&action=edit&id='.urlencode($result['ivr_id']),
				'description' => sprintf(_("IVR: %s"),$result['displayname']),
			);
		}
		return $usage_arr;
	}
}

?>