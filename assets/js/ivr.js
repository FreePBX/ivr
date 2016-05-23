var announcementRecording = null, recording = false, recordings = {}, soundBlob = null;
$(document).ready(function(){
	//on load, hide elememnts that may need to be hidden
	invalid_elements();
	timeout_elements();

	$('#add_entrie').click(function(e){
		e.preventDefault();
		// we get this each time in case a popOver has updated the array
		new_entrie = '<tr>' + $('#gotoDESTID').parents('tr').html() + '</tr>';
		id = new Date().getTime();//must be cached, as we have many replaces to do and the time can shift
		thisrow = $('#ivr_entries > tbody:last').find('tr:last').after(new_entrie.replace(/DESTID/g, id));
		$('.destdropdown2', $(thisrow).next()).addClass('hidden');
		bind_dests_double_selects();
	});

	if($('form[name=frm_ivr]').length > 0){
		//fix for popovers because jquery wont bubble up a real "submit()" correctly.
		//See http://issues.freepbx.org/browse/FREEPBX-8122 for more information
		$('form[name=frm_ivr]')[0].onsubmit = function() {
			if($("#name").val() === "") {
				return warnInvalid($("#name"),_("IVRs require a valid name"));
			}
			if($("#fileupload-container").length) {
				if(announcementRecording !== null) {
					$('#frm_ivr').append('<input type="hidden" name="announcementrecording" value="'+announcementRecording+'" />');
				} else if(announcementRecording === null && (isNaN(parseInt($("#announcement").val())) || parseInt($("#announcement").val()) == 0)) {
					if(!confirm(_("Are you sure you don't want a recording for this announcement?"))) {
						return false;
					}
				}
			}
			//remove the last blank field so that it isnt subject to validation, assuming it wasnt set
			//called from .click() as that is fired before validation
			last = $('#ivr_entries > tbody:last').find('tr:last');
			if(last.find('input[name="entries[ext][]"]').val() === '' && last.find('.destdropdown').val() === ''){
				last.remove();
			}

			var stop = false;
			$('#ivr_entries tr > td:first-child input').each(function() {
				var digit = $(this).val().trim();
				if(digit === '' || isDialpattern(digit) === false) {
					alert(_("Please enter a valid value for Digits Pressed"));
					stop = true;
					return false;
				}
			});
			if(stop) {
				return false;
			}

			//set timeout/invalid destination, removing hidden field if there is no valus being set
			if ($('#invalid_loops').val() != 'disabled') {
				invalid = $('[name=' + $('[name=gotoinvalid]').val() + 'invalid]').val();
				$('#invalid_destination').val(invalid);
			} else {
				$('#invalid_destination').remove();
			}

			if ($('#timeout_loops').val() != 'disabled') {
				timeout = $('[name=' + $('[name=gototimeout]').val() + 'timeout]').val();
				$('#timeout_destination').val(timeout);
			} else {
				$('#timeout_destination').remove();
			}


			//set goto fileds for destinations
			$('select[name^=goto][type!=hidden]').each(function(){
				num = $(this).prop('name').replace('goto', '');
				dest = $('[name=' + $(this).val() + num + ']').val();
				$(this).parent().find('input[name="entries[goto][]"]').val(dest);
				//console.log(num, dest, $(this).parent().find('input[name="entries[goto][]"]').val())
			});

			//set ret_ivr checkboxes to SOMETHING so that they get sent back
			$('[name="entries[ivr_ret][]"]').not(':checked').each(function(){
				$(this).prop('checked', true).val('uncheked');
			});

			//disable dests so that they dont get posted
			$('.destdropdown, .destdropdown2').prop("disabled", true);

			setTimeout(restore_form_elemens, 100);
		};
	}

	//delete rows on click
	$(document).on('click','.delete_entrie', function(e){
		e.preventDefault();
		if($("#ivr_entries tr").length == 2) {
			alert(_("Unable to delete the last entry"));
			return;
		}
		if($(this).closest('tr').index() === 0) {
			alert(_("Unable to delete the first entry. Please edit instead"));
			return;
		}
		$(this).closest('tr').fadeOut('normal', function(){$(this).closest('tr').remove();});
	});

	//show/hide invalid elements on change
	$('#invalid_loops').change(invalid_elements);

	//show/hide timeout elements on change
	$('#timeout_loops').change(timeout_elements);
});

function restore_form_elemens() {
	$('.destdropdown, .destdropdown2').prop('disabled',false);
	$('[name="entries[ivr_ret][]"][value=uncheked]').each(function(){
		$(this).removeAttr('checked');
	});
	invalid_elements();
	timeout_elements();
}

//always disable hidden elements so that they dont trigger validation
function invalid_elements() {
	var invalid_elements = $('#invalid_retry_recording, #invalid_recording, #invalid_append_announce, #invalid_ivr_ret, [name=gotoinvalid]');
	var invalid_element_tr = invalid_elements.parent().parent();
	switch ($('#invalid_loops').val()) {
		case 'disabled':
			invalid_elements.prop('disabled', true);
			invalid_element_tr.hide();
			break;
		case '0':
			invalid_elements.prop('disabled',false);
			invalid_element_tr.show();
			$('#invalid_retry_recording').parent().parent().hide();
			$('#invalid_append_announce').parent().parent().hide();
			break;
		default:
			invalid_elements.prop('disabled',false);
			invalid_element_tr.show();
			break;
	}
}

//always disable hidden elements so that they dont trigger validation
function timeout_elements() {
	var timeout_elements = $('#timeout_retry_recording, #timeout_recording, #timeout_append_announce, #timeout_ivr_ret, [name=gototimeout]');
	var timeout_element_tr = timeout_elements.parent().parent();
	switch ($('#timeout_loops').val()) {
		case 'disabled':
			timeout_elements.prop('disabled', true);
			timeout_element_tr.hide();
			break;
		case '0':
			timeout_elements.prop('disabled',false);
			timeout_element_tr.show();
			$('#timeout_retry_recording').parent().parent().hide();
			$('#timeout_append_announce').parent().parent().hide();
			break;
		default:
			timeout_elements.prop('disabled',false);
			timeout_element_tr.show();
			break;
	}
}

function actionFormatter(value){
	var html = '<a href="?display=ivr&action=edit&id='+value[0]+'"><i class="fa fa-pencil"></i></a>&nbsp;';
	html += '<a href="?display=ivr&action=delete&id='+value[0]+'" class="delAction"><i class="fa fa-trash"></i></a>&nbsp;';
	return html;
}
function bnavFormatter(value){
	var html = '<a href="?display=ivr&action=edit&id='+value[0]+'"><i class="fa fa-pencil"></i>&nbsp;'+_("Edit:")+'&nbsp;'+value[1]+'</a>';
	return html;
}

/**
 * Drag/Drop/Upload Files
 */
$('#dropzone').on('drop dragover', function (e) {
	e.preventDefault();
});
$('#dropzone').on('dragleave drop', function (e) {
	$(this).removeClass("activate");
});
$('#dropzone').on('dragover', function (e) {
	$(this).addClass("activate");
});
$('#fileupload').fileupload({
	dataType: 'json',
	dropZone: $("#dropzone"),
	add: function (e, data) {
		//TODO: Need to check all supported formats
		var sup = "\.("+supportedRegExp+")$",
				patt = new RegExp(sup),
				submit = true;
		$.each(data.files, function(k, v) {
			if(!patt.test(v.name.toLowerCase())) {
				submit = false;
				alert(_("Unsupported file type"));
				return false;
			}
		});
		if(submit) {
			data.submit();
		}
	},
	drop: function () {
		$("#upload-progress .progress-bar").css("width", "0%");
	},
	dragover: function (e, data) {
	},
	change: function (e, data) {
	},
	done: function (e, data) {
		if(data.result.status) {
			announcementRecording = data.result.localfilename;
			$("#jquery_jplayer_announcement").jPlayer( "clearMedia" );
			recordings[key] = announcementRecording;
		} else {
			alert(data.result.message);
		}
	},
	progressall: function (e, data) {
		var progress = parseInt(data.loaded / data.total * 100, 10);
		$("#upload-progress .progress-bar").css("width", progress+"%");
	},
	fail: function (e, data) {
	},
	always: function (e, data) {
	}
});

$(".browser-player-container").each(function() {
	var player = $(this).find(".jp-jplayer"),
			container = player.data("container"),
			recID = parseInt(player.data("recording-id")),
			key = player.data("key");
	if(!isNaN(recID) && recID > 0) {
		$(this).removeClass("hidden");
	}
	player.jPlayer({
		ready: function(event) {
			$("#"+container + " .jp-play").click(function() {
				if(!player.data("jPlayer").status.srcSet) {
					$("#"+container).addClass("jp-state-loading");
					//it is a temp file OR a system file
					if(typeof recordings[key] !== "undefined" || !isNaN(recID)) {
						var type = (typeof recordings[key] !== "undefined") ? "temp" : "system",
								id = (typeof recordings[key] !== "undefined") ? recordings[key] : player.data("recording-id");
						//get our html5 file, hope we have one
							$.ajax({
								type: 'POST',
								url: "ajax.php",
								data: {module: "recordings", command: "gethtml5byid", id: id, type: type},
								dataType: 'json',
								timeout: 30000
							}).done(function( data ) {
								if(data.status) {
									player.on($.jPlayer.event.error, function(event) {
										console.warn(event);
									});
									player.one($.jPlayer.event.canplay, function(event) {
										player.jPlayer("play");
									});
									player.jPlayer( "setMedia", data.files);
								} else {
									alert(data.message);
								}
							}).always(function() {
								$("#"+container).removeClass("jp-state-loading");

							});
					} else {
						alert(_("No file to load!"));
					}
				} else {
						//source is already set
				}
			});
		},
		//moves our ball
			timeupdate: function(event) {
				$("#jp_container_"+key).find(".jp-ball").css("left",event.jPlayer.status.currentPercentAbsolute + "%");
			},
			//puts our ball back at the start
			ended: function(event) {
				$("#jp_container_"+key).find(".jp-ball").css("left","0%");
			},
			cssSelectorAncestor: "#jp_container_"+key,
			swfPath: "http://jplayer.org/latest/dist/jplayer",
			supplied: supportedHTML5,
			wmode: "window",
			useStateClassSkin: true,
			autoBlur: false,
			keyEnabled: true,
			remainingDuration: true,
			toggleDuration: true
	});

	var acontainer = null;
		$('#jp_container_'+key+' .jp-play-bar').mousedown(function (e) {
			acontainer = $(this).parents(".jp-audio-freepbx");
			updatebar(e.pageX);
		});
		$(document).mouseup(function (e) {
			if (acontainer) {
				updatebar(e.pageX);
				acontainer = null;
			}
		});
		$(document).mousemove(function (e) {
			if (acontainer) {
				updatebar(e.pageX);
			}
		});

		//update Progress Bar control
		var updatebar = function (x) {
			var player = $("#" + acontainer.data("player")),
					progress = acontainer.find('.jp-progress'),
					maxduration = player.data("jPlayer").status.duration,
					position = x - progress.offset().left,
					percentage = 100 * position / progress.width();

			//Check within range
			if (percentage > 100) {
				percentage = 100;
			}
			if (percentage < 0) {
				percentage = 0;
			}

			player.jPlayer("playHead", percentage);

			//Update progress bar and video currenttime
			acontainer.find('.jp-ball').css('left', percentage+'%');
			acontainer.find('.jp-play-bar').css('width', percentage + '%');
			player.jPlayer.currentTime = maxduration * percentage / 100;
		};
});

//check if this browser supports WebRTC
if (Modernizr.getusermedia && window.location.protocol == "https:") {
	//show in browser recording if it does
	$("#browser-recorder-container").removeClass("hidden");
	$("#jquery_jplayer_1").jPlayer({
		ready: function(event) {

		},
		timeupdate: function(event) {
			$("#jp_container_1").find(".jp-ball").css("left",event.jPlayer.status.currentPercentAbsolute + "%");
		},
		ended: function(event) {
			$("#jp_container_1").find(".jp-ball").css("left","0%");
		},
		swfPath: "http://jplayer.org/latest/dist/jplayer",
		supplied: "wav",
		wmode: "window",
		useStateClassSkin: true,
		autoBlur: false,
		keyEnabled: true,
		remainingDuration: true,
		toggleDuration: true
	});
	var acontainer = null;
	$('.jp-play-bar').mousedown(function (e) {
		acontainer = $(this).parents(".jp-audio-freepbx");
		updatebar(e.pageX);
	});
	$(document).mouseup(function (e) {
		if (acontainer) {
			updatebar(e.pageX);
			acontainer = null;
		}
	});
	$(document).mousemove(function (e) {
		if (acontainer) {
			updatebar(e.pageX);
		}
	});

	//update Progress Bar control
	function updatebar(x) {
		var player = $("#" + acontainer.data("player")),
				progress = acontainer.find('.jp-progress'),
				maxduration = player.data("jPlayer").status.duration,
				position = x - progress.offset().left,
				percentage = 100 * position / progress.width();

		//Check within range
		if (percentage > 100) {
			percentage = 100;
		}
		if (percentage < 0) {
			percentage = 0;
		}

		player.jPlayer("playHead", percentage);

		//Update progress bar and video currenttime
		acontainer.find('.jp-ball').css('left', percentage+'%');
		acontainer.find('.jp-play-bar').css('width', percentage + '%');
		player.jPlayer.currentTime = maxduration * percentage / 100;
	};
} else {
	//hide in browser recording if it does not
	$("#browser-recorder-container").remove();
}


/**
 * Record from within WebRTC supported browser
 */
$("#record").click(function() {
	var counter = $("#jp_container_1 .jp-duration"),
			title = $("#jp_container_1 .jp-title"),
			player = $("#jquery_jplayer_1"),
			key = player.data("key"),
			controls = $(this).parents(".jp-controls"),
			recorderContainer = $("#browser-recorder"),
			saveContainer = $("#browser-recorder-save"),
			input = $("#save-recorder-input");

	controls.toggleClass("recording");
	player.jPlayer( "clearMedia" );

	//previously recording
	if (recording) {
		clearInterval(recordTimer);
		title.html('<button id="saverecording" class="btn btn-primary" type="button">'+_("Save Recording")+'</button><button id="deleterecording" class="btn btn-primary" type="button">'+_("Delete Recording")+'</button>');
		//save recording button
		$("#saverecording").one("click", function() {
			//clear media for upload
			player.jPlayer( "clearMedia" );
			var data = new FormData(),
					name = Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 15);
			data.append("file", soundBlob);
			$.ajax({
				type: "POST",
				url: "ajax.php?module=ivr&command=savebrowserrecording&filename=" + encodeURIComponent(name),
				xhr: function() {
					$("#browser-recorder-progress").removeClass("hidden").addClass("in");
					var xhr = new window.XMLHttpRequest();
					//Upload progress
					xhr.upload.addEventListener("progress", function(evt) {
						if (evt.lengthComputable) {
							var percentComplete = evt.loaded / evt.total,
							progress = Math.round(percentComplete * 100);
							$("#browser-recorder-progress .progress-bar").css("width", progress + "%");
							if(progress == 100) {
								$("#browser-recorder-progress").addClass("hidden").removeClass("in");
								$("#browser-recorder-progress .progress-bar").css("width", "0%");
							}
						}
					}, false);
					return xhr;
				},
				data: data,
				processData: false,
				contentType: false,
				success: function(data) {
					if(data.status) {
						announcementRecording = data.localfilename;
					}
					title.html(_("Hit the red record button to start recording from your browser"));
					var url = (window.URL || window.webkitURL).createObjectURL(soundBlob);
					$("#jquery_jplayer_"+key).jPlayer( "setMedia", {
						wav: url
					});
				},
				error: function() {
				}
			});
		});
		$("#deleterecording").one("click", function() {
			$("#jquery_jplayer_1").jPlayer( "clearMedia" );
			title.html(_("Hit the red record button to start recording from your browser"));
		});
		recorder.stop();
		recorder.exportWAV(function(blob) {
			soundBlob = blob;
			var url = (window.URL || window.webkitURL).createObjectURL(blob);
			player.jPlayer( "setMedia", {
				wav: url
			});
		});
		recording = false;
	} else {
		//map webkit prefix
		window.AudioContext = window.AudioContext || window.webkitAudioContext;
		var context = new AudioContext(),
		gUM = Modernizr.prefixed("getUserMedia", navigator);

		//start the recording!
		gUM({ audio: true }, function(stream) {
			var mediaStreamSource = context.createMediaStreamSource(stream);
			recorder = new Recorder(mediaStreamSource,{ workerPath: "assets/js/recorderWorker.js" });
			recorder.record();
			startTime = new Date();
			//create a normal minutes:seconds timer from micro/milli-seconds
			recordTimer = setInterval(function () {
				var mil = (new Date() - startTime),
						temp = (mil / 1000),
						min = ("0" + Math.floor((temp %= 3600) / 60)).slice(-2),
						sec = ("0" + Math.round(temp % 60)).slice(-2);
				counter.text(min + ":" + sec);
			}, 1000);
			title.text(_("Recording..."));
			recording = true;
		}, function(e) {
			controls.toggleClass("recording");
			alert(_("Your Browser Blocked The Recording, Please check your settings"));
			recording = false;
		});
	}
});
