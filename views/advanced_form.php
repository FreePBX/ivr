<input type="hidden" name="id" value="<?php echo $ivr['id']?>">
<input type="hidden" name="invalid_destination" id="invalid_destination" value="">
<input type="hidden" name="timeout_destination" id="timeout_destination" value="">
<input type="hidden" name="action" value="save">

<div class="section-title" data-for="ivrgeneral">
	<h3><i class="fa fa-minus"></i> <?php echo _('IVR General Options')?></h3>
</div>
<div class="section" data-id="ivrgeneral">
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
	<!--IVR Description-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="description"><?php echo _("IVR Description") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="description"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="description" name="description" value="<?php echo $ivr['description'] ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="description-help" class="help-block fpbx-help-block"><?php echo _("Description of this IVR")?></span>
			</div>
		</div>
	</div>
	<!--END IVR Description-->
</div>
<div class="section-title" data-for="ivrdtmf">
	<h3><i class="fa fa-minus"></i> <?php echo _('IVR DTMF Options')?></h3>
</div>
<div class="section" data-id="ivrdtmf">
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
				<span id="announcement-help" class="help-block fpbx-help-block"><?php echo _("Select a 'System Recording' here that will be played on entry to the IVR.")?></span>
			</div>
		</div>
	</div>
	<!--END Announcement-->
	<!--Direct Dial-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="directdial"><?php echo _("Enable Direct Dial") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="directdial"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="directdial" id="directdialyes" value="ext-local" <?php echo ($ivr['directdial'] == "ext-local"?"CHECKED":"") ?>>
							<label for="directdialyes"><?php echo _("Yes");?></label>
							<input type="radio" name="directdial" id="directdialno" value="" <?php echo ($ivr['directdial'] == ""?"CHECKED":"") ?>>
							<label for="directdialno"><?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="directdial-help" class="help-block fpbx-help-block"><?php echo _("Provides options for callers to direct dial an extension.")?></span>
			</div>
		</div>
	</div>
	<!--END Direct Dial-->
	<!--Timeout-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="timeout_time"><?php echo _("Timeout") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="timeout_time"></i>
						</div>
						<div class="col-md-9">
							<input type="number" min="0" class="form-control" id="timeout_time" name="timeout_time" value="<?php echo stripslashes($ivr['timeout_time'])?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="timeout_time-help" class="help-block fpbx-help-block"><?php echo _("Amount of time to be considered a timeout.").'<br/><strong>'._("A value of 0 disables the timeout").'</strong>'?></span>
			</div>
		</div>
	</div>
	<!--END Timeout-->
	<!--Alert Info-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="alertinfo"><?php echo _("Alert Info") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="alertinfo"></i>
						</div>
						<div class="col-md-9">
							<?php echo FreePBX::View()->alertInfoDrawSelect("alertinfo",(!empty($ivr['alertinfo'])?$ivr['alertinfo']:''));?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="alertinfo-help" class="help-block fpbx-help-block"><?php echo _("ALERT_INFO can be used for distinctive ring with SIP devices.")?></span>
			</div>
		</div>
	</div>
	<!--END Alert Info-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="rvolume"><?php echo _("Ringer Volume Override") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="rvolume"></i>
						</div>
						<div class="col-md-9">
							<select class="form-control" id="rvolume" name="rvolume">
								<option value="0"><?php echo _("None")?></option>
								<?php for($i = 1; $i <= 14; $i++) { ?>
									<option value="<?php echo $i?>" <?php echo ($ivr['rvolume'] == $i) ? 'selected' : ''?>><?php echo $i?></option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="rvolume-help" class="help-block fpbx-help-block"><?php echo sprintf(_("Override the ringer volume. Note: This is only valid for %s phones at this time"),"Sangoma")?></span>
			</div>
		</div>
	</div>
	<!--Invalid Retries-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="invalid_loops"><?php echo _("Invalid Retries") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="invalid_loops"></i>
						</div>
						<div class="col-md-9">
							<input type="number" min="0" max="10" class="form-control" id="invalid_loops" name="invalid_loops" value="<?php echo $ivr['invalid_loops']?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="invalid_loops-help" class="help-block fpbx-help-block"><?php echo _("Number of times to retry when receiving an invalid/unmatched response from the caller")?></span>
			</div>
		</div>
	</div>
	<!--END Invalid Retries-->
	<!--Invalid Retry Recording-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="invalid_retry_recording"><?php echo _("Invalid Retry Recording") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="invalid_retry_recording"></i>
						</div>
						<div class="col-md-9">
							<select class="form-control" id="invalid_retry_recording" name="invalid_retry_recording">
								<?php echo $invalidretryopts?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="invalid_retry_recording-help" class="help-block fpbx-help-block"><?php echo _("Prompt to be played when an invalid/unmatched response is received, before prompting the caller to try again")?></span>
			</div>
		</div>
	</div>
	<!--END Invalid Retry Recording-->
	<!--Append Announcement to Invalid-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="invalid_append_announce"><?php echo _("Append Announcement to Invalid") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="invalid_append_announce"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="invalid_append_announce" id="invalid_append_announceyes" value="on" <?php echo (!empty($ivr['invalid_append_announce'])?"CHECKED":"") ?>>
							<label for="invalid_append_announceyes"><?php echo _("Yes");?></label>
							<input type="radio" name="invalid_append_announce" id="invalid_append_announceno" value="" <?php echo (!empty($ivr['invalid_append_announce'])?"":"CHECKED") ?>>
							<label for="invalid_append_announceno"><?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="invalid_append_announce-help" class="help-block fpbx-help-block"><?php echo _("After playing the Invalid Retry Recording the system will replay the main IVR Announcement")?></span>
			</div>
		</div>
	</div>
	<!--END Append Announcement to Invalid-->
	<!--Return on Invalid-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="invalid_ivr_ret"><?php echo _("Return on Invalid") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="invalid_ivr_ret"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="invalid_ivr_ret" id="invalid_ivr_retyes" value="on" <?php echo (!empty($ivr['invalid_ivr_ret'])?"CHECKED":"") ?>>
							<label for="invalid_ivr_retyes"><?php echo _("Yes");?></label>
							<input type="radio" name="invalid_ivr_ret" id="invalid_ivr_retno" value="" <?php echo (!empty($ivr['invalid_ivr_ret'])?"":"CHECKED") ?>>
							<label for="invalid_ivr_retno"><?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="invalid_ivr_ret-help" class="help-block fpbx-help-block"><?php echo _('Choose yes to have this option return to a parent IVR if it was called '
																								. 'from a parent IVR. If not, it will go to the chosen destination.<br><br>'
																								. 'The return path will be to any IVR that was in the call path prior to this '
																								. 'IVR which could lead to strange results if there was an IVR called in the '
																								. 'call path but not immediately before this')?></span>
			</div>
		</div>
	</div>
	<!--END Return on Invalid-->
	<!--Invalid Recording-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="invalid_recording"><?php echo _("Invalid Recording") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="invalid_recording"></i>
						</div>
						<div class="col-md-9">
							<select class="form-control" id="invalid_recording" name="invalid_recording">
								<?php echo $invalidopts?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="invalid_recording-help" class="help-block fpbx-help-block"><?php echo _("Prompt to be played before sending the caller to an alternate destination due to the caller pressing 0 or receiving the maximum amount of invalid/unmatched responses (as determined by Invalid Retries)")?></span>
			</div>
		</div>
	</div>
	<!--END Invalid Recording-->
	<!--Invalid Destination-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="gotoinvalid"><?php echo _("Invalid Destination") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="gotoinvalid"></i>
						</div>
						<div class="col-md-9">
							<?php echo drawselects($ivr['invalid_destination'], 'invalid')?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="gotoinvalid-help" class="help-block fpbx-help-block"><?php echo _("Destination to send the call to after Invalid Recording is played")?></span>
			</div>
		</div>
	</div>
	<!--END Invalid Destination-->
	<!--Timeout Retries-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="timeout_loops"><?php echo _("Timeout Retries") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="timeout_loops"></i>
						</div>
						<div class="col-md-9">
							<input type="number" min="0" max="10" class="form-control" id="timeout_loops" name="timeout_loops" value="<?php echo $ivr['timeout_loops']?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="timeout_loops-help" class="help-block fpbx-help-block"><?php echo _("Number of times to retry when no DTMF is heard and the IVR choice times out.")?></span>
			</div>
		</div>
	</div>
	<!--END Timeout Retries-->
	<!--Timeout Retry Recording-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="timeout_retry_recording"><?php echo _("Timeout Retry Recording") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="timeout_retry_recording"></i>
						</div>
						<div class="col-md-9">
							<select class="form-control" id="timeout_retry_recording" name="timeout_retry_recording">
								<?php echo $timeoutretryopts?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="timeout_retry_recording-help" class="help-block fpbx-help-block"><?php echo _("Prompt to be played when a timeout occurs, before prompting the caller to try again")?></span>
			</div>
		</div>
	</div>
	<!--END Timeout Retry Recording-->
	<!--Append Announcement on Timeout-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="timeout_append_announce"><?php echo _("Append Announcement on Timeout") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="timeout_append_announce"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="timeout_append_announce" id="timeout_append_announceyes" value="on" <?php echo (!empty($ivr['timeout_append_announce'])?"CHECKED":"") ?>>
							<label for="timeout_append_announceyes"><?php echo _("Yes");?></label>
							<input type="radio" name="timeout_append_announce" id="timeout_append_announceno" value="" <?php echo (!empty($ivr['timeout_append_announce'])?"":"CHECKED") ?>>
							<label for="timeout_append_announceno"><?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="timeout_append_announce-help" class="help-block fpbx-help-block"><?php echo _("After playing the Timeout Retry Recording the system will replay the main IVR Announcement")?></span>
			</div>
		</div>
	</div>
	<!--END Append Announcement on Timeout-->
	<!--Return on Timeout-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="timeout_ivr_ret"><?php echo _("Return on Timeout") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="timeout_ivr_ret"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="timeout_ivr_ret" id="timeout_ivr_retyes" value="on" <?php echo (!empty($ivr['timeout_ivr_ret'])?"CHECKED":"") ?>>
							<label for="timeout_ivr_retyes"><?php echo _("Yes");?></label>
							<input type="radio" name="timeout_ivr_ret" id="timeout_ivr_retno" value="" <?php echo (!empty($ivr['timeout_ivr_ret'])?"":"CHECKED") ?>>
							<label for="timeout_ivr_retno"><?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="timeout_ivr_ret-help" class="help-block fpbx-help-block"><?php echo _('Check this box to have this option return to a parent IVR if it was called '
																								. 'from a parent IVR. If not, it will go to the chosen destination.<br><br>'
																								. 'The return path will be to any IVR that was in the call path prior to this '
																								. 'IVR which could lead to strange results if there was an IVR called in the '
																								. 'call path but not immediately before this')?></span>
			</div>
		</div>
	</div>
	<!--END Return on Timeout-->
	<!--Timeout Recording-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="timeout_recording"><?php echo _("Timeout Recording") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="timeout_recording"></i>
						</div>
						<div class="col-md-9">
							<select class="form-control" id="timeout_recording" name="timeout_recording">
								<?php echo $timeoutopts?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="timeout_recording-help" class="help-block fpbx-help-block"><?php echo _("Prompt to be played before sending the caller to an alternate destination due to the caller pressing 0 or receiving the maximum amount of invalid/unmatched responses (as determined by Invalid Retries)")?></span>
			</div>
		</div>
	</div>
	<!--END Timeout Recording-->
	<!--Timeout Destination-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="gototimeout"><?php echo _("Timeout Destination") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="gototimeout"></i>
						</div>
						<div class="col-md-9">
							<?php echo drawselects($ivr['timeout_destination'],'timeout')?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="gototimeout-help" class="help-block fpbx-help-block"><?php echo _("Destination to send the call to after Timeout Recording is played.")?></span>
			</div>
		</div>
	</div>
	<!--END Timeout Destination-->
	<!--Return to IVR after VM-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="retvm"><?php echo _("Return to IVR after VM") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="retvm"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="retvm" id="retvmyes" value="on" <?php echo (!empty($ivr['retvm'])?"CHECKED":"") ?>>
							<label for="retvmyes"><?php echo _("Yes");?></label>
							<input type="radio" name="retvm" id="retvmno" value="" <?php echo (!empty($ivr['retvm'])?"":"CHECKED") ?>>
							<label for="retvmno"><?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span id="retvm-help" class="help-block fpbx-help-block"><?php echo _("If Yes, upon exiting voicemail a caller will be returned to this IVR if they got a users voicemail")?></span>
			</div>
		</div>
	</div>
	<!--END Return to IVR after VM-->
</div>
<div class="section-title" data-for="ivrentries">
	<h3><i class="fa fa-minus"></i> <?php echo _('IVR Entries')?></h3>
</div>
<div class="section" data-id="ivrentries">
	<?php echo ivr_draw_entries($ivr['id'])?>
</div>
<?php echo $hookhtml?>
