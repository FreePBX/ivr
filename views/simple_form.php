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
								<div id="jquery_jplayer_1" class="jp-jplayer"></div>
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
<script>var supportedRegExp = "<?php echo implode("|",array_keys($supported['in']))?>";</script>
