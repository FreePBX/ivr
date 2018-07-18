<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//
$freepbx = FreePBX::Create();
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

$hooks = $freepbx->Ivr->pageHook($_REQUEST);
$hookhtml = '';
foreach ($hooks as $key => $value) {
	$hookhtml .= $value;
}

$display_mode = "advanced";
$mode = $freepbx->Config->get("FPBXOPMODE");
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
										$ivr['rvolume'] = '';
									} else {

									}
									$supported = $freepbx->Media->getSupportedFormats();
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
