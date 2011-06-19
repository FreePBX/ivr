<?php
$table = new CI_Table;
$table->set_template(array('table_open' => '<table class="alt_table">'));
//build header
$h = array();
foreach($headers as $mod => $header) {
	$h += $header;
}
$table->set_heading($h);

$count = 0;
foreach ($entires as $e) {
	$count++;

	//add ext to dial
	$row[] = fpbx_label(
		form_input(
			array(
				'name'	=> 'entires[ext][]',
				'value'	=> $e['selection']
				)
			), 
		'Digits to press for this choice');
	
	//add destination
	$row[] = drawselects($e['dest'], $count, false, false) . form_hidden('entires[goto][]', '');
	
	//return to ivr
	$row[] = fpbx_label(form_checkbox('entires[ivr_ret][]', 'ivr_ret', $e['ivr_ret']), 
			'Check this box to have this option return to a parent IVR if it was called'
			. ' from a parent IVR. If not, it will go to the chosen destination.<br><br>'
			. 'The return path will be to any IVR that was in the call path prior to this '
			. 'IVR which could lead to strange results if there was an IVR called in the '
			. 'call path but not immediately before this');
	
	//add module hooks	
	if ($e['hooks']) {
		foreach ($e['hooks'] as $module => $hooks) {
			foreach ($hooks as $h) {
				$row[] = $h;
			}
		}
		
	}
	
	//delete buttom
	$row[] = '<img src="images/trash.png" style="cursor:pointer" title="' 
	. _('Delete this entry. Dont forget to click Save to save changes!') 
	. '" onclick="delEntry(' . $e['selection'] . ')""';

	$table->add_row(array_values($row));	
	
	unset($row);
}


echo $table->generate();
?>