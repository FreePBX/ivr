<?php 
/* $Id$ */
/*
 * Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

$get_vars = array(
				'action' 		=> '',
				'id'			=> '',
				'nbroptions'	=> '3',
				'display'		=> ''
);
foreach ($get_vars as $k => $v) {
	$var[$k] = isset($_REQUEST[$k]) ? $_REQUEST[$k] : $v;
	$$k = $var[$k];//todo: legacy support, needs to GO!
}
$dircontext = 'default';
$tabindex = 0;

echo load_view(dirname(__FILE__) . '/views/rnav.php', array('ivr_results' => ivr_list()) + $var);

switch ($action) {
	case "add":
		$id = ivr_get_ivr_id('Unnamed');
		// Set the defaults
		$def['timeout'] = 5;
		$def['ena_directdial'] = '';
		$def['ena_directory'] = '';
		break;
	case "edit":
		break;
	case "edited":
		if (isset($_REQUEST['delete'])) {
			sql("DELETE from ivr where ivr_id='$id'");
			sql("DELETE FROM ivr_dests where ivr_id='$id'");
			needreload();
		} else {
			ivr_do_edit($id, $_POST);
			if (isset($_REQUEST['increase'])) 
				$nbroptions++;
			if (isset($_REQUEST['decrease'])) {
				$nbroptions--;
			}
			if ($nbroptions < 1)
				$nbroptions = 1;
			//ivr_show_edit($id, $nbroptions, $_POST);
			$url = 'config.php?type=setup&display=ivr&action=edit&id='.$id.'&nbroptions='.$nbroptions;
			needreload();
			redirect($url);
			break;
		}
	default:

?>
<div class="content">
<h2><?php echo _("IVR"); ?></h2>
<br/><br/>{add add button here}<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
<br/><br/><br/><br/><br/><br/><br/>

<?php
}


?>