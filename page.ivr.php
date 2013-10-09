<?php 
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

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
				'display'		=> ''
);
foreach ($get_vars as $k => $v) {
	$var[$k] = isset($_REQUEST[$k]) ? $_REQUEST[$k] : $v;
	$$k = $var[$k];//todo: legacy support, needs to GO!
}

echo load_view(dirname(__FILE__) . '/views/rnav.php', array('ivr_results' => ivr_get_details()) + $var);

if (!$action && !$id) {
?>
<h2><?php echo _("IVR"); ?></h2>
<br/><br/>
<a href="config.php?type=setup&display=ivr&action=add">
	<input type="button" value="<?php echo _("Add a new IVR")?>" id="new_dir">
</a>
<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
<br/><br/><br/><br/><br/><br/><br/>

<?php
}


?>
