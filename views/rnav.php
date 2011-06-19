<?php

$li[] = '<a href="config.php?display='. urlencode($display) . '&action=add">' . _("Add IVR") . '</a>';


if (isset($ivr_results)){
	foreach ($ivr_results as $r) {
		$li[] = '<a id="' . ( $id == $r['ivr_id'] ? 'current' : '') 
			. '" href="config.php?display=ivr&amp;action=edit&amp;id=' 
			. $r['ivr_id'] . '">' 
			. $r['displayname'] .'</a>';
	}
}	

echo '<div class="rnav">' . ul($li) . '</div>';
?>