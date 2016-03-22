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
						<div class="browser-player-container hidden">
							<div id="jquery_jplayer_announcement" data-container="jp_container_announcement" class="jp-jplayer" data-key="announcement" data-recording-id="<?php echo $ivr['announcement']?>" data-recording-type="system"></div>
							<div id="jp_container_announcement" data-player="jquery_jplayer_announcement" class="jp-audio-freepbx" role="application" aria-label="media player">
								<div class="jp-type-single">
									<div class="jp-gui jp-interface">
										<div class="jp-controls">
											<i class="fa fa-play jp-play"></i>
										</div>
										<div class="jp-progress">
											<div class="jp-seek-bar progress">
												<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
												<div class="progress-bar progress-bar-striped active" style="width: 100%;"></div>
												<div class="jp-play-bar progress-bar"></div>
												<div class="jp-play-bar">
													<div class="jp-ball"></div>
												</div>
												<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
											</div>
										</div>
										<div class="jp-volume-controls">
											<i class="fa fa-volume-up jp-mute"></i>
											<i class="fa fa-volume-off jp-unmute"></i>
										</div>
									</div>
									<div class="jp-details">
										<div class="jp-title" aria-label="title"><?php echo _("Play Previous Recording")?></div>
									</div>
									<div class="jp-no-solution">
										<span><?php echo _("Update Required")?></span>
										<?php echo sprintf(_("To play the media you will need to either update your browser to a recent version or update your %s"),'<a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>')?>
									</div>
								</div>
							</div>
						</div>
						<div id="fileupload-container">
							<span class="btn btn-default btn-file">
								<?php echo _("Browse")?>
								<input id="fileupload" type="file" class="form-control" name="files[]" data-url="ajax.php?module=ivr&amp;command=upload" class="form-control" multiple>
							</span>
							<div id="upload-progress" class="progress">
								<div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
							</div>
							<div id="dropzone">
								<div class="message"><?php echo _("Drop Annoucement Recording Here")?></div>
							</div>
						</div>
						<div id="browser-recorder-container" class="hidden">
							<div id="browser-recorder">
								<div id="jquery_jplayer_1" class="jp-jplayer" data-key="announcement"></div>
								<div id="jp_container_1" data-player="jquery_jplayer_1" class="jp-audio-freepbx" role="application" aria-label="media player">
									<div class="jp-type-single">
										<div class="jp-gui jp-interface">
											<div class="jp-controls">
												<i class="fa fa-play jp-play"></i>
												<i id="record" class="fa fa-circle"></i>
											</div>
											<div class="jp-progress">
												<div class="jp-seek-bar progress">
													<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
													<div class="progress-bar progress-bar-striped active" style="width: 100%;"></div>
													<div class="jp-play-bar progress-bar"></div>
													<div class="jp-play-bar">
														<div class="jp-ball"></div>
													</div>
													<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
												</div>
											</div>
											<div class="jp-volume-controls">
												<i class="fa fa-volume-up jp-mute"></i>
												<i class="fa fa-volume-off jp-unmute"></i>
											</div>
										</div>
										<div class="jp-details">
											<div class="jp-title" aria-label="title"><?php echo _("Hit the red record button to start recording from your browser")?></div>
										</div>
										<div class="jp-no-solution">
											<span><?php echo _("Update Required")?></span>
											<?php echo sprintf(_("To play the media you will need to either update your browser to a recent version or update your %s"),'<a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>')?>
										</div>
									</div>
								</div>
							</div>
							<div id="browser-recorder-progress" class="progress fade hidden">
								<div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="announcement-help" class="help-block fpbx-help-block"><?php echo _("Greeting to be played on entry to the IVR.")?> <?php echo sprintf(_("Upload files from your local system. Supported upload formats are: %s. This includes archives (that include multiple files) and multiple files"),"<i><strong>".implode(", ",$supported['in'])."</strong></i>")?></span>
		</div>
	</div>
</div>
<!--END Announcement-->
<!--Alert Info-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="alertinfo"><?php echo _("Ring Tone") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="alertinfo"></i>
					</div>
					<div class="col-md-9">
						<?php echo FreePBX::View()->alertInfoDrawSelect("alertinfo",(($ivr['alertinfo'])?$ivr['alertinfo']:''));?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="alertinfo-help" class="help-block fpbx-help-block"><?php echo _("Select a Ring Tone from the list of options above. This will determine how your phone sounds when it is rung from this group.")?></span>
		</div>
	</div>
</div>
<!--END Alert Info-->
<?php
$restrict_mods = array(
	"ivr",
	"ringgroups",
	"directory",
	"core" => array(
		"extensions",
		"voicemail"
	)
);
echo ivr_draw_entries($ivr['id'], $restrict_mods);
?>
<input type="hidden" name="id" value="<?php echo $ivr['id']?>">
<input type="hidden" name="action" value="save">

<input type="hidden" name="invalid_destination" id="invalid_destination" value="<?php echo $ivr['invalid_destination']?>">
<input type="hidden" class="form-control" id="gotoinvalid" name="gotoinvalid" value="Terminate_Call">
<input type="hidden" class="form-control" id="Terminate_Callinvalid" name="Terminate_Callinvalid" value="<?php echo $ivr['invalid_destination']?>">

<input type="hidden" name="timeout_destination" id="timeout_destination" value="<?php echo $ivr['timeout_destination']?>">
<input type="hidden" class="form-control" id="gototimeout" name="gototimeout" value="Terminate_Call">
<input type="hidden" class="form-control" id="Terminate_Calltimeout" name="Terminate_Calltimeout" value="<?php echo $ivr['timeout_destination']?>">

<input type="hidden" class="form-control" id="announcement" name="announcement" value="<?php echo !empty($ivr['announcement']) ? $ivr['announcement'] : ""?>">
<input type="hidden" class="form-control" id="directdial" name="directdial" value="<?php echo $ivr['directdial']?>">
<input type="hidden" class="form-control" id="timeout_time" name="timeout_time" value="<?php echo $ivr['timeout_time']?>">
<input type="hidden" class="form-control" id="alertinfo" name="alertinfo" value="<?php echo $ivr['alertinfo']?>">
<input type="hidden" class="form-control" id="invalid_loops" name="invalid_loops" value="<?php echo $ivr['invalid_loops']?>">
<input type="hidden" class="form-control" id="invalid_retry_recording" name="invalid_retry_recording" value="<?php echo !empty($ivr['invalid_retry_recording']) ? $ivr['invalid_retry_recording'] : ""?>">
<input type="hidden" class="form-control" id="invalid_append_announce" name="invalid_append_announce" value="<?php echo $ivr['invalid_append_announce']?>">
<input type="hidden" class="form-control" id="invalid_ivr_ret" name="invalid_ivr_ret" value="<?php echo $ivr['invalid_ivr_ret']?>">
<input type="hidden" class="form-control" id="invalid_recording" name="invalid_recording" value="<?php echo $ivr['invalid_recording']?>">
<input type="hidden" class="form-control" id="timeout_loops" name="timeout_loops" value="<?php echo $ivr['timeout_loops']?>">
<input type="hidden" class="form-control" id="timeout_retry_recording" name="timeout_retry_recording" value="<?php echo !empty($ivr['timeout_retry_recording']) ? $ivr['timeout_retry_recording'] : ""?>">
<input type="hidden" class="form-control" id="timeout_append_announce" name="timeout_append_announce" value="<?php echo $ivr['timeout_append_announce']?>">
<input type="hidden" class="form-control" id="timeout_ivr_ret" name="timeout_ivr_ret" value="<?php echo $ivr['timeout_ivr_ret']?>">
<input type="hidden" class="form-control" id="timeout_recording" name="timeout_recording" value="<?php echo !empty($ivr['timeout_recording']) ? $ivr['timeout_recording'] : ""?>">
<input type="hidden" class="form-control" id="retvm" name="retvm" value="<?php echo $ivr['retvm']?>">
<script>var supportedRegExp = "<?php echo implode("|",array_keys($supported['in']))?>";var supportedHTML5 = "<?php echo implode(",",FreePBX::Media()->getSupportedHTML5Formats())?>"</script>
