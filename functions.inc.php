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
}
function ivr_do_edit($id, $post) {

	$displayname = isset($post['displayname'])?$post['displayname']:'';
	$timeout = isset($post['timeout'])?$post['timeout']:'';
	$ena_directory = isset($post['ena_directory'])?$post['ena_directory']:'';
	$ena_directdial = isset($post['ena_directdial'])?$post['ena_directdial']:'';
	$annmsg_id = isset($post['annmsg_id'])?$post['annmsg_id']:'';
	$dircontext = isset($post['dircontext'])?$post['dircontext']:'';
	$timeout_id = isset($post['timeout_id'])?$post['timeout_id']:'';
	$invalid_id = isset($post['invalid_id'])?$post['invalid_id']:'';

	$loops = isset($post['loops'])?$post['loops']:'2';
	$alt_timeout = isset($post['alt_timeout'])?$post['alt_timeout']:'';
	$alt_invalid = isset($post['alt_invalid'])?$post['alt_invalid']:'';
	$retvm = isset($post['retvm'])?$post['retvm']:'';

	if (!empty($ena_directdial) && !is_numeric($ena_directdial)) {
		$ena_directdial='CHECKED';
	}
	if (!empty($alt_timeout)) {
		$alt_timeout='CHECKED';
	}
	if (!empty($alt_invalid)) {
		$alt_invalid='CHECKED';
	}
	if (!empty($retvm)) {
		$retvm='CHECKED';
	}
	
	$sql = "
	UPDATE ivr 
	SET 
		displayname='$displayname', 
		enable_directory='$ena_directory', 
		enable_directdial='$ena_directdial', 
		timeout='$timeout', 
		announcement_id='$annmsg_id', 
		timeout_id='$timeout_id', 
		invalid_id='$invalid_id', 
		dircontext='$dircontext', 
		alt_timeout='$alt_timeout', 
		alt_invalid='$alt_invalid', 
		retvm='$retvm', 
		`loops`='$loops' 
	WHERE ivr_id='$id'
	";
	sql($sql);

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
			$ivr_ret = isset($post['ivr_ret'.$match[1]]) ? $post['ivr_ret'.$match[1]] : '';
			// Debugging if it all goes pear shaped.
			// print "I think pushing $cmd does $dest<br>\n";
			if (strlen($cmd))
				ivr_add_command($id, $cmd, $dest, $ivr_ret);
		}
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

function ivr_get_details($id) {
	global $db;

	$sql = "SELECT * FROM ivr where ivr_id='$id'";
	$res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
	if($db->IsError($res)) {
		return null;
	}
	return $res[0];
}

function ivr_get_dests($id) {
	global $db;
	$sql = "SELECT selection, dest, ivr_ret FROM ivr_dests where ivr_id='$id' ORDER BY selection";
	$res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
	if($db->IsError($res)) {
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

function ivr_configpageload() {
	global $currentcomponent, $display;
	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	$id 	= isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
	if ($id == null) {
		return true;
	}
	if ($action  == 'add' && $id == '') {
		 $currentcomponent->addguielem('_top', new gui_pageheading('title', _('Add Directory')), 0);

		$deet = array('name', 'directdial', 'invalid_loops', 'invalid_rety_recording', 
					'invalid_recording', 'invalid_destination', 'timeout_loops',
					'timeout_rety_recording', 'timeout_recording', 'timeout_destination',
					'announcement_id', 'retvm', 'enable_directdial', 'description', 'id');
     
		foreach ($deet as $d) {
			switch ($d){
				case 'repeat_loops';
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
		$ivr['id'] = $ivr['ivr_id'];
		$ivr['name'] = $ivr['displayname'];
		$ivr['description'] = 'NOT IMPLEMENTED';
		$ivr['directdial'] = $ivr['invalid_loops'] = $ivr['invalid_rety_recording'] = $ivr['invalid_recording'] = '';
		$ivr['invalid_destination'] = $ivr['timeout_loops'] = $ivr['timeout_rety_recording'] = $ivr['timeout_recording'] = '';	
		$ivr['timeout_destination'] = '';
		dbug('$ivr', $ivr);

		$label = sprintf(_("Edit IVR: %s"), $ivr['name'] ? $ivr['name'] : 'ID '.$ivr['id']);

		$currentcomponent->addguielem('_top', new gui_pageheading('title', $label), 0);
		
		//display usage
		$usage_list			= framework_display_destination_usage(ivr_getdest($ivr['id']));
		if (!empty($usage_list)) {
			$usage_list_text	= isset($usage_list['text']) ? $usage_list['text'] : '';
			$usage_list_tooltip	= isset($usage_list['tooltip']) ? $usage_list['tooltip'] : '';
			$currentcomponent->addguielem('_top', 
				new gui_link_label('usage', $usage_list_text, $usage_list_tooltip), 0);
		}
		
		//display delete link
		$label 				= '<span><img width="16" height="16" border="0" title="' 
							. $label . '" alt="" src="images/core_delete.png"/>&nbsp;' . $label . '</span>';
		$currentcomponent->addguielem('_top', 
			new gui_link('del', $label, $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '&action=delete', 
				true, false), 0);
	}
	
	//delete link, dont show if we dont have an id (i.e. directory wasnt created yet)
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
	for($i=0; $i <11; $i++){
		$currentcomponent->addoptlistitem('repeat_loops', $i, $i);
	}
	
	//generate page
	$currentcomponent->addguielem($section, 
		new gui_selectbox('displayname', $currentcomponent->getoptlist('recordings'), 
			$ivr['announcement_id'], _('Announcement'), _('Greeting to be played on entry to the Ivr.'), false));


	
	//direct dial
	//TODO: hook in from directory	
	$currentcomponent->addoptlistitem('direct_dial', $ivr['enable_directdial'], _('Disabled'));
	$currentcomponent->addoptlistitem('direct_dial', $ivr['enable_directdial'], _('Extensions'));
	$dd_help[] = _('completely disabled');
	$dd_help[] = _('enabled for all extensions on a system');
	//todo: make next line conditional on directory being present
	$dd_help[] = _('tied to a Directory allowing all entried in that directory to be dialed directly, as they appear in the directory');
	$currentcomponent->addguielem($section, 
		new gui_selectbox('directdial', $currentcomponent->getoptlist('direct_dial'), 
		$ivr['directdial'], _('Direct Dial'), _('Provides options for callers to direct dial an extension. Direct dialing can be:') . ul($dd_help), false));
	
	//invalid 
	$currentcomponent->addguielem($section, 
		new gui_selectbox('invalid_loops', $currentcomponent->getoptlist('repeat_loops'), 
		$ivr['invalid_loops'], _('Invalid Retries'), _('Number of times to retry when receiving an invalid/unmatched response from the caller'), false));
	$currentcomponent->addguielem($section, 
		new gui_selectbox('invalid_rety_recording', $currentcomponent->getoptlist('recordings'), 
		$ivr['invalid_rety_recording'], _('Invalid Retry Recording'), _('Prompt to be played when an invalid/unmatched response is received, before prompting the caller to try again'), false));
	$currentcomponent->addguielem($section, 
		new gui_selectbox('invalid_recording', $currentcomponent->getoptlist('recordings'), 
		$ivr['invalid_recording'], _('Invalid Recording'), _('Prompt to be played before sending the caller to an alternate destination due to the caller pressing 0 or receiving the maximum amount of invalid/unmatched responses (as determined by Invalid Retries)'), false));
	$currentcomponent->addguielem($section, 
		new gui_drawselects('invalid_destination', 0, $ivr['invalid_destination'], _('Invalid Destination'),
		 _('Destination to send the call to after Invalid Recording is played.'), false));
	
	//timeout/invalid 
	$currentcomponent->addguielem($section, 
		new gui_selectbox('timeout_loops', $currentcomponent->getoptlist('repeat_loops'), 
		$ivr['timeout_loops'], _('Timeout Retries'), _('Number of times to retry when receiving an invalid/unmatched response from the caller'), false));
	$currentcomponent->addguielem($section, 
		new gui_selectbox('timeout_rety_recording', $currentcomponent->getoptlist('recordings'), 
		$ivr['timeout_rety_recording'], _('Timeout Retry Recording'), _('Prompt to be played when an invalid/unmatched response is received, before prompting the caller to try again'), false));
	$currentcomponent->addguielem($section, 
		new gui_selectbox('timeout_recording', $currentcomponent->getoptlist('recordings'), 
		$ivr['timeout_recording'], _('Timeout Recording'), _('Prompt to be played before sending the caller to an alternate destination due to the caller pressing 0 or receiving the maximum amount of invalid/unmatched responses (as determined by Invalid Retries)'), false));
	$currentcomponent->addguielem($section, 
		new gui_drawselects('timeout_destination', 0, 
		$ivr['timeout_destination'], _('Timeout Destination'), _('Destination to send the call to after Invalid Recording is played.'), false));
	
	//return to ivr
	$currentcomponent->addguielem($section, 
		new gui_checkbox('retvm', $ivr['retvm'], _('Return to IVR after VM'), _('If checked, upon exiting voicemail a caller will be returned to this IVR if they got a users voicemail')));
		
	/*$currentcomponent->addguielem($section, 
		new gui_checkbox('say_extension', $dir['say_extension'], _('Announce Extension'), 
		_('When checked, the extension number being transferred to will be announced prior to the transfer'),true));*/
	$currentcomponent->addguielem($section, new gui_hidden('id', $ivr['id']));
	$currentcomponent->addguielem($section, new gui_hidden('action', 'edit'));


		
	$section = _('Ivr Entries');
	//draw the entries part of the table. A bit hacky perhaps, but hey - it works!
	$currentcomponent->addguielem($section, new guielement('rawhtml', ivr_draw_entries($ivr['id']), ''), 6);
}

function ivr_configpageinit($pagename) {
	global $currentcomponent;
	if($pagename == 'ivr'){
		$currentcomponent->addprocessfunc('ivr_configprocess');
		$currentcomponent->addguifunc('ivr_configpageload');
    return true;
	}
}

//prosses received arguments
function ivr_configprocess(){
	if($_REQUEST['display'] == 'ivr'){
		return true;
		global $db,$amp_conf;
		//get variables for directory_details
		$requestvars = array('id','dirname','description','announcement',
							'callid_prefix','alert_info','repeat_loops',
							'repeat_recording','invalid_recording',
							'invalid_destination','retivr','say_extension');
		foreach($requestvars as $var){
			$vars[$var] = isset($_REQUEST[$var]) 	? $_REQUEST[$var]		: '';
		}

		$action		= isset($_REQUEST['action'])	? $_REQUEST['action']	: '';
		$entries	= isset($_REQUEST['entries'])	? $_REQUEST['entries']	: '';
		//$entries=(($entries)?array_values($entries):'');//reset keys

		switch($action){
			case 'edit':
				//get real dest
				$vars['invalid_destination'] = $_REQUEST[$_REQUEST[$_REQUEST['invalid_destination']].str_replace('goto','',$_REQUEST['invalid_destination'])];
				$vars['id'] = directory_save_dir_details($vars);
				directory_save_dir_entries($vars['id'],$entries);
				needreload();
				redirect_standard_continue('id');
			break;
			case 'delete':
				directory_delete($vars['id']);
				needreload();
				redirect_standard_continue();
			break;
		}
	}
}


function ivr_draw_entries_table_header_ivr() {
	return  array(_('Ext'), _('Destination'), fpbx_label(_('Return'), _('Return to IVR')), _('Delete'));
}

function ivr_draw_entries_ivr() {
	//TEST FUNCTION, DELETE ASAP
	return array(form_input(array('name'	=> 'test1','value'	=> 'test1')),
				form_input(array('name'	=> 'test2','value'	=> 'test2'))
				);
}

function ivr_draw_entries($id){
	$headers		= mod_func_iterator('draw_entries_table_header_ivr');
	$ivr_entires	= ivr_get_dests($id);

	foreach ($ivr_entires as $k => $e) {
		$entires[$k]= $e;
		$entires[$k]['hooks'] = mod_func_iterator('draw_entries_ivr', array('id' => $id, 'ext' => $e['selection']));
	}

	
	return load_view(dirname(__FILE__) . '/views/entries.php', 
				array(
					'headers'	=> $headers, 
					'entires'	=>  $entires
				)
			);

}

//used to add row's the entry table
function ivr_draw_entries_tr($id, $realid, $name = '',$foreign_name, $audio = '',$num = '',$e_id = '', $reuse_audio = false){

}

//----------------------------------------------------------------------------
// Dynamic Destination Registry and Recordings Registry Functions
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