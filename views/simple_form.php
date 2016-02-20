<!--IVR Name-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="name"><?php echo _("IVR Name") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="name"></i>
					</div>
					<div class="col-md-9">
						<input type="text" class="form-control" id="name" name="name" value="<?php echo $ivr['name']?>">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="name-help" class="help-block fpbx-help-block"><?php echo _("Name of this IVR")?></span>
		</div>
	</div>
</div>
<!--END IVR Name-->
<input type="hidden" class="form-control" id="description" name="description" value="<?php echo $ivr['description'] ?>">
<!--Announcement-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="announcement"><?php echo _("Announcement") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="announcement"></i>
					</div>
					<div class="col-md-9">
						<select class="form-control" id="announcement" name="announcement">
							<?php echo $annopts?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="announcement-help" class="help-block fpbx-help-block"><?php echo _("Greeting to be played on entry to the IVR.")?></span>
		</div>
	</div>
</div>
<!--END Announcement-->
<?php echo ivr_draw_entries($ivr['id'], array("core" => array("extensions","voicemail")))?>
<input type="hidden" name="id" value="<?php echo $ivr['id']?>">
<input type="hidden" name="invalid_destination" id="invalid_destination" value="">
<input type="hidden" name="timeout_destination" id="timeout_destination" value="">
<input type="hidden" name="action" value="save">
<input type="hidden" class="form-control" id="directdial" name="directdial" value="ext-local">
<input type="hidden" class="form-control" id="timeout_time" name="timeout_time" value="5">
<input type="hidden" class="form-control" id="invalid_loops" name="invalid_loops" value="3">
<input type="hidden" class="form-control" id="invalid_retry_recording" name="invalid_retry_recording" value="default">
<input type="hidden" class="form-control" id="invalid_append_announce" name="invalid_append_announce" value="">
<input type="hidden" class="form-control" id="invalid_ivr_ret" name="invalid_ivr_ret" value="">
<input type="hidden" class="form-control" id="invalid_recording" name="invalid_recording" value="default">
<input type="hidden" class="form-control" id="gotoinvalid" name="gotoinvalid" value="Terminate_Call">
<input type="hidden" class="form-control" id="Terminate_Callinvalid" name="Terminate_Callinvalid" value="app-blackhole,hangup,1">
<input type="hidden" class="form-control" id="timeout_loops" name="timeout_loops" value="3">
<input type="hidden" class="form-control" id="timeout_retry_recording" name="timeout_retry_recording" value="default">
<input type="hidden" class="form-control" id="timeout_append_announce" name="timeout_append_announce" value="">
<input type="hidden" class="form-control" id="timeout_ivr_ret" name="timeout_ivr_ret" value="">
<input type="hidden" class="form-control" id="timeout_recording" name="timeout_recording" value="default">
<input type="hidden" class="form-control" id="gototimeout" name="gototimeout" value="Terminate_Call">
<input type="hidden" class="form-control" id="Terminate_Calltimeout" name="Terminate_Calltimeout" value="app-blackhole,hangup,1">
<input type="hidden" class="form-control" id="retvm" name="retvm" value="">
