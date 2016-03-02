<?php
$table = new CI_Table;
$table->set_template(array('table_open' => '<table class="table table-striped alt_table IVREntries" id="ivr_entries">'));
//build header
$h = array();
foreach($headers as $mod => $header) {
	$h += $header;
}
$table->set_heading($h);

$count = 0;
foreach ($entries as $e) {
	$count++;

	//add ext to dial
	$row[] = form_input(
				array(
					'name'			=> 'entries[ext][]',
					'value'			=> $e['selection'],
					'placeholder'	=> _('digits pressed')
				)
			);

	//add destination. The last one gets a different count so that we can manipualte it on the page
	if ($count == count($entries)) {
		$row[] = drawselects($e['dest'], 'DESTID', $restrict_mods, false) . form_hidden('entries[goto][]', '');
	} else {
		$row[] = drawselects($e['dest'], $count, $restrict_mods, false) . form_hidden('entries[goto][]', '');
	}


	//return to ivr
	$row[] = '
		<span class="radioset">
		<input type="radio" name="entries[ivr_ret][DESTID'.$count.']" id="entries'.$count.'DESTIDyes" value="1" '.($e['ivr_ret']?"CHECKED":"").'>
		<label for="entries'.$count.'DESTIDyes">'. _("Yes").'</label>
		<input type="radio" name="entries[ivr_ret][DESTID'.$count.']" id="entries'.$count.'DESTIDno" value="" '.($e['ivr_ret']?"":"CHECKED") .'>
		<label for="entries'.$count.'DESTIDno">'._("No").'</label>
		</span>
	';

	//delete buttom
	$row[] = '<a href="#" alt="'
	. _('Delete this entry. Dont forget to click Submit to save changes!')
	. '" class="delete_entrie"><i class="fa fa-trash"></i></a>';

	//add module hooks
	if (isset($e['hooks']) && $e['hooks']) {
		foreach ($e['hooks'] as $module => $hooks) {
			foreach ($hooks as $h) {
				$row[] = $h;
			}
		}

	}


	$table->add_row(array_values($row));

	unset($row);
}

$ret = '';
$ret .= $table->generate();
$ret .= '<a class="IVREntries" href="#" id="add_entrie"><i class="fa fa-plus"></i>'._('Add Another Entry').'</a>';


echo $ret;
?>
