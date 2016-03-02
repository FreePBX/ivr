<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//
extract($request, EXTR_SKIP);
$infohtml = '';
if($action == 'add'){
	$ivr = array();
	$heading = _("Add IVR");
	$deet = array('id', 'name', 'description', 'announcement', 'directdial',
				'invalid_loops', 'invalid_retry_recording',
				'invalid_recording', 'invalid_destination', 'invalid_ivr_ret',
				'timeout_loops', 'timeout_time', 'timeout_retry_recording',
				'timeout_recording', 'timeout_destination', 'timeout_ivr_ret',
				'retvm');

	//keep vairables set on new ivr's
	foreach ($deet as $d) {
		switch ($d){
			case 'invalid_loops':
			case 'timeout_loops';
				$ivr[$d] = 3;
				break;
			case 'announcement':
				$ivr[$d] = '';
				break;
			case 'invalid_recording':
			case 'invalid_retry_recording':
			case 'timeout_retry_recording':
			case 'timeout_recording':
				$ivr[$d] = 'default';
				break;
			case 'timeout_time':
				$ivr[$d] = 10;
				break;
			default:
			$ivr[$d] = '';
				break;
		}
	}
}else{
	$ivr = ivr_get_details($id);
	$heading = _('Edit IVR: ');
	$heading .= ($ivr['name'] ? $ivr['name'] : 'ID '.$ivr['id']);
	$usage_list	= framework_display_destination_usage(ivr_getdest($ivr['id']));
	if(!empty($usage_list)){
		$infohtml = '
		<div class="panel panel-default">
			<div class="panel-heading">
				'.$usage_list['text'].'
			</div>
			<div class="panel-body">
    			'.$usage_list['tooltip'].'
			</div>
		</div>
		';
	}
	$delURL = '?display=ivr&action=delete&id='.$id;
}
$recordingList = recordings_list();
$annopts = '<option>'._('None').'</option>';
foreach($recordingList as $r){
	$checked = ($r['id'] == $ivr['announcement']?' SELECTED':'');
	$annopts .= '<option value="'.$r['id'].'" '.$checked.'>'.$r['displayname'].'</option>';
}
$invalidretryopts = '<option value="default">'._('Default').'</option>';
$invalidretryopts .= '<option value="" '.($ivr['invalid_retry_recording'] == ''?'SELECTED':'').'>'._('None').'</option>';
foreach($recordingList as $r){
	$checked = ($r['id'] == $ivr['invalid_retry_recording']?' SELECTED':'');
	$invalidretryopts .= '<option value="'.$r['id'].'" '.$checked.'>'.$r['displayname'].'</option>';
}
$invalidopts = '<option value="default">'._('Default').'</option>';
$invalidopts .= '<option value="" '.($ivr['invalid_recording'] == ''?'SELECTED':'').'>'._('None').'</option>';
foreach($recordingList as $r){
	$checked = ($r['id'] == $ivr['invalid_recording']?' SELECTED':'');
	$invalidopts .= '<option value="'.$r['id'].'" '.$checked.'>'.$r['displayname'].'</option>';
}
$timeoutretryopts = '<option value="default">'._('Default').'</option>';
$timeoutretryopts .= '<option value="" '.($ivr['timeout_retry_recording'] == ''?'SELECTED':'').'>'._('None').'</option>';
foreach($recordingList as $r){
	$checked = ($r['id'] == $ivr['timeout_retry_recording']?' SELECTED':'');
	$timeoutretryopts .= '<option value="'.$r['id'].'" '.$checked.'>'.$r['displayname'].'</option>';
}
$timeoutopts = '<option value="default">'._('Default').'</option>';
$timeoutopts .= '<option value="" '.($ivr['timeout_recording'] == ''?'SELECTED':'').'>'._('None').'</option>';
foreach($recordingList as $r){
	$checked = ($r['id'] == $ivr['timeout_recording']?' SELECTED':'');
	$timeoutopts .= '<option value="'.$r['id'].'" '.$checked.'>'.$r['displayname'].'</option>';
}

$hooks = \FreePBX::Ivr()->pageHook($_REQUEST);
$hookhtml = '';
foreach ($hooks as $key => $value) {
	$hookhtml .= $value;
}

$display_mode = "advanced";
$mode = \FreePBX::Config()->get("FPBXOPMODE");
if(!empty($mode)) {
	$display_mode = $mode;
}

?>
<div class="container-fluid">
	<h1><?php echo $heading?></h1>
	<?php echo $infohtml?>
	<div class = "display full-border">
		<div class="row">
			<div class="col-sm-12">
				<div class="fpbx-container">
					<div class="display full-border">
						<form class='fpbx-submit' name="frm_ivr" id="frm_ivr" method="POST" action="config.php?display=ivr" data-fpbx-delete="<?php echo $delURL?>">
							<?php
								if($display_mode == "basic") {
									if($action == 'add') {
										$ivr['invalid_destination'] = 'app-blackhole,hangup,1';
										$ivr['timeout_destination'] = 'app-blackhole,hangup,1';
										$ivr['directdial'] = 'ext-local';
										$ivr['timeout_time'] = '5';
										$ivr['alertinfo'] = '';
										$ivr['invalid_loops'] = '3';
										$ivr['invalid_retry_recording'] = 'default';
										$ivr['invalid_append_announce'] = '';
										$ivr['invalid_ivr_ret'] = '';
										$ivr['invalid_recording'] = '';
										$ivr['timeout_loops'] = '3';
										$ivr['timeout_retry_recording'] = 'default';
										$ivr['timeout_append_announce'] = '';
										$ivr['timeout_ivr_ret'] = '';
										$ivr['timeout_recording'] = 'default';
										$ivr['retvm'] = '';
										$ivr['announcement'] = '';
									} else {

									}
									$supported = FreePBX::Media()->getSupportedFormats();
									include(__DIR__."/simple_form.php");
								} else {
									include(__DIR__."/advanced_form.php");
								}
							?>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
