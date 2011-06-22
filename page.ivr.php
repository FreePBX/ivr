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
				'display'		=> ''
);
foreach ($get_vars as $k => $v) {
	$var[$k] = isset($_REQUEST[$k]) ? $_REQUEST[$k] : $v;
	$$k = $var[$k];//todo: legacy support, needs to GO!
}

echo load_view(dirname(__FILE__) . '/views/rnav.php', array('ivr_results' => ivr_get_details()) + $var);

if (!$action) {
?>
<h2><?php echo _("IVR"); ?></h2>
<br/><br/>{add add button here}<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
<br/><br/><br/><br/><br/><br/><br/>

<?php
}


?>