<?php 
/* $Id$ */
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

// The Digital Receptionist code is a rat's nest.  If you are planning on making significant modifications, just re-write from scratch.
// OK! You're the boss. --Rob
// Re-written from the ground up by Rob Thomas <xrobau@gmail.com> 23rd March, 2006.

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$id = isset($_REQUEST['id'])?$_REQUEST['id']:'';
$dircontext = isset($_SESSION["AMP_user"]->_deptname)?$_SESSION["AMP_user"]->_deptname:'';
$nbroptions = isset($_REQUEST['nbroptions'])?$_REQUEST['nbroptions']:'3';
$tabindex = 0;

if (empty($dircontext)) {
	$dircontext = 'default';
}

switch ($action) {
	case "add":
		$id = ivr_get_ivr_id('Unnamed');
		// Set the defaults
		$def['timeout'] = 5;
		$def['ena_directdial'] = '';
		$def['ena_directory'] = '';
		ivr_sidebar($id);
		ivr_show_edit($id, 3,  $def);
		break;
	case "edit":
		ivr_sidebar($id);
		ivr_show_edit($id, $nbroptions, $_POST);
		break;
	case "edited":
		if (isset($_REQUEST['delete'])) {
			sql("DELETE from ivr where ivr_id='$id'");
			sql("DELETE FROM ivr_dests where ivr_id='$id'");
			needreload();
		} else {
			ivr_do_edit($id, $_POST);
			ivr_sidebar($id);
			if (isset($_REQUEST['increase'])) 
				$nbroptions++;
			if (isset($_REQUEST['decrease'])) {
				$nbroptions--;
			}
			if ($nbroptions < 1)
				$nbroptions = 1;
			//ivr_show_edit($id, $nbroptions, $_POST);
			$url = 'config.php?type=setup&display=ivr&action=edit&id='.$id.'&nbroptions='.$nbroptions;
			needreload();
			redirect($url);
			break;
		}
	default:
		ivr_sidebar($id);
?>
<div class="content">
<h2><?php echo _("Digital Receptionist"); ?></h2>
<h3><?php 
echo _("Instructions")."</h3>";
echo _("You use the Digital Receptionist to make IVR's, Interactive Voice Response systems.")."<br />\n";
echo _("When creating a menu option, apart from the standard options of 0-9,* and #, you can also use 'i' and 't' destinations.")."\n";
echo _("'i' is used when the caller pushes an invalid button, and 't' is used when there is no response.")."\n";
echo _("If those options aren't supplied, the default 't' is to replay the menu three times and then hang up,")."\n";
echo _("and the default 'i' is to say 'Invalid option, please try again' and replay the menu.")."\n";
echo _("After three invalid attempts, the line is hung up.")."\n"; ?>
</div>

<?php
}


function ivr_sidebar($id)  {
?>
        <div class="rnav"><ul>
        <li><a id="<?php echo empty($id)?'current':'nul' ?>" href="config.php?display=ivr&amp;action=add"><?php echo _("Add IVR")?></a></li>
<?php

        $ivr_results = ivr_list();
        if (isset($ivr_results)){
                foreach ($ivr_results as $tresult) {
                        echo "<li><a id=\"".($id==$tresult['ivr_id'] ? 'current':'nul')."\" href=\"config.php?display=ivr";
                        echo "&amp;action=edit&amp;id={$tresult['ivr_id']}\">{$tresult['displayname']}</a></li>\n";
                }
        }
        echo "</ul></div>\n";
}

function ivr_show_edit($id, $nbroptions, $post) {
	global $db;
	global $tabindex;

	$ivr_details = ivr_get_details($id);
	$ivr_dests = ivr_get_dests($id);
?>
	<div class="content">
	<h2><?php echo _("Digital Receptionist"); ?></h2>
	<h3><?php echo _("Edit Menu")." ".$ivr_details['displayname']; ?></h3>
<?php 
?>
	<form name="prompt" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return prompt_onsubmit();">
	<input type="hidden" name="action" value="edited" />
	<input type="hidden" name="display" value="ivr" />
	<input type="hidden" name="id" value="<?php echo $id ?>" />
	<input name="Submit" type="submit" value="<?php echo _("Save")?>" tabindex="<?php echo ++$tabindex;?>" disabled>
<?php
	$usage_list = array();
	if (function_exists('queues_ivr_usage')) {
		$usage_list = queues_ivr_usage($id);
	}
	if (count($usage_list)) {
?>
		<a href="#" class="info"><?php echo _("Queue Breakout Menu Usage List");?><span><?php echo _("This IVR is being used by the following Queues, providing an ability for callers to hear this Queue's announcement periodically and giving callers an option to break out of the queue into this IVR's menu options. This queue can not be deleted when being used in this mode");?></span></a>
<?php
		$count = 0;
		foreach ($usage_list as $link) {
			$label = '<span><img width="16" height="16" border="0" title="'.$link['description'].'" alt="" src="images/queue_link.png"/>&nbsp;'.$link['description'].'</span>';
			echo "<br /><a href=".$link['url_query'].">".$label."</a>";
		}
		echo "<br />";
	} else {
?>
	<input name="delete" type="submit" value="<?php echo _("Delete")." "._("Digital Receptionist")." {$ivr_details['displayname']}"; ?>" disabled/>
<?php
	}
	if ($id) {
		$usage_list = framework_display_destination_usage(ivr_getdest($id));
		if (!empty($usage_list)) {
		?>
			<br /><a href="#" class="info"><?php echo $usage_list['text']?>:<span><?php echo $usage_list['tooltip']?></span></a>
		<?php
		}
	}
	?>
	<table>
		<tr><td colspan=2><hr /></td></tr>
		<tr>
			<td><a href="#" class="info"><?php echo _("Change Name"); ?><span><?php echo _("This changes the short name, visible on the right, of this IVR");?></span></a></td>
			<td><input type="text" name="displayname" value="<?php echo $ivr_details['displayname'] ?>" tabindex="<?php echo ++$tabindex;?>"></td>
		</tr>

<?php
	$annmsg_id = isset($ivr_details['announcement_id'])?$ivr_details['announcement_id']:'';
	if(function_exists('recordings_list')) { //only include if recordings is enabled ?>
		<tr>
			<td><a href="#" class="info"><?php echo _("Announcement")?><span><?php echo _("Message to be played to the caller. To add additional recordings please use the \"System Recordings\" MENU to the left")?></span></a></td>
			<td>
				<select name="annmsg_id" tabindex="<?php echo ++$tabindex;?>">
				<?php
					$tresults = recordings_list();
					echo '<option value="">'._("None")."</option>";
					if (isset($tresults[0])) {
						foreach ($tresults as $tresult) {
							echo '<option value="'.$tresult['id'].'"'.($tresult['id'] == $annmsg_id ? ' SELECTED' : '').'>'.$tresult['displayname']."</option>\n";
						}
					}
				?>
				</select>
			</td>
		</tr>
	
<?php
	} else {
?>
		<tr>
			<td><a href="#" class="info"><?php echo _("Announcement")?><span><?php echo _("Message to be played to the caller.<br><br>You must install and enable the \"Systems Recordings\" Module to edit this option")?></span></a></td>
			<td>
			<?php
				$default = (isset($annmsg_id) ? $annmsg_id : '');
			?>
				<input type="hidden" name="annmsg_id" value="<?php echo $default; ?>"><?php echo ($default != '' ? $default : 'None'); ?>
			</td>
		</tr>
<?php
	}
?>
		<tr>
			<td><a href="#" class="info"><?php echo _("Timeout");?><span><?php echo _("The amount of time (in seconds) before the 't' option, if specified, is used");?></span></a></td>
			<td><input type="text" name="timeout" value="<?php echo $ivr_details['timeout'] ?>" tabindex="<?php echo ++$tabindex;?>"></td>
		</tr>
		<?php if ($ivr_details['enable_directory'] && function_exists('voicemail_getVoicemail')) { ?>
		<tr>
			<td><a href="#" class="info"><?php echo _("Enable Directory");?><span><?php echo _("Let callers into the IVR dial '#' to access the directory. WARNING: this feature is deprecated and will be removed from future versions. You should install the Directory module and assign an IVR destination to use Destination functionality.");?></span></a></td>
			<td><input type="checkbox" name="ena_directory" <?php echo $ivr_details['enable_directory'] ?> tabindex="<?php echo ++$tabindex;?>"></td>
		</tr>
		<tr>
			<td><a href="#" class="info"><?php echo _("Directory Context");?><span><?php echo _("When # is selected, this is the voicemail directory context that is used");?></span></a></td>
			<td>
				<select name="dircontext" tabindex="<?php echo ++$tabindex;?>">
					<?php
					$vm_results = voicemail_getVoicemail();
					$vmcontexts = array_keys($vm_results);
					foreach ($vmcontexts as $vmc) {
						if (($vmc != 'general') && ($vmc != 'zonemessages')) {
							echo '<option value="'.$vmc.'"'.($vmc == $ivr_details['dircontext'] ? ' SELECTED' : '').'>'.$vmc."</option>\n";
            }
					}
					?>
				</select>
			</td>
		</tr>
		<?php } ?>
		<tr>
			<td><a href="#" class="info"><?php echo _("VM Return to IVR");?><span><?php echo _("If checked, upon exiting voicemail a caller will be returned to this IVR if they got a users voicemail");?></span></a></td>
			<td><input type="checkbox" name="retvm" <?php echo $ivr_details['retvm'] ?> tabindex="<?php echo ++$tabindex;?>"></td>
		</tr>
<?php
	if (!function_exists('directory_list')) {
?>
		<tr>
			<td><a href="#" class="info"><?php echo _("Enable Direct Dial");?><span><?php echo _("Let callers into the IVR dial an extension directly");?></span></a></td>
			<td><input type="checkbox" name="ena_directdial" <?php echo $ivr_details['enable_directdial'] ?> tabindex="<?php echo ++$tabindex;?>"></td>
		</tr>
<?php
	} else {
?>
		<tr>
			<td><a href="#" class="info"><?php echo _("Direct Dial Options");?><span><?php echo _("Provides options for callers to direct dial an extension. Direct dialing can be completely disabled, it can be enabled for all extensions on a system, or it can be tied to a Company Directory allowing any member listed in that directory to be dialed directly if their extension is known. If an extension in the chosen directory is overriden, only that overriden number is dialable");?></span></a></td>
			<td>
				<select name="ena_directdial" tabindex="<?php echo ++$tabindex;?>">
					<?php
					$dlist = directory_list();
					echo '<option value=""'.($ivr_details['enable_directdial'] == '' ? ' SELECTED' : '').'>'._('Disabled')."</option>\n";
					echo '<option value="CHECKED"'.(strtoupper($ivr_details['enable_directdial']) == 'CHECKED' ? ' SELECTED' : '').'>'._('All Extensions')."</option>\n";
					foreach ($dlist as $dir) {
						echo '<option value="'.$dir['id'].'"'.($ivr_details['enable_directdial'] == $dir['id'] ? ' SELECTED' : '').'>'.$dir['dirname']."</option>\n";
					}
					?>
				</select>
			</td>
		</tr>

<?php
	}
?>
		<tr>
			<td><a href="#" class="info"><?php echo _("Loop Before t-dest");?><span><?php echo _("If checked, and there is a 't' (timeout) destination defined below, the IVR will loop back to the beginning if no input is provided for the designated loop counts prior to going to the timeout (t) destination.");?></span></a></td>
			<td><input type="checkbox" name="alt_timeout" <?php echo $ivr_details['alt_timeout'] ?> tabindex="<?php echo ++$tabindex;?>"></td>
		</tr>
<?php
	$timeout_id = isset($ivr_details['timeout_id'])?$ivr_details['timeout_id']:'';
	if(function_exists('recordings_list')) { //only include if recordings is enabled ?>
		<tr>
			<td><a href="#" class="info"><?php echo _("Timeout Message")?><span><?php echo _("If a timeout occurs and a message is selected, it will be played in place of the announcement message when looping back to the top of the IVR. It will not be played if the t destination is the next target.")?></span></a></td>
			<td>
				<select name="timeout_id" tabindex="<?php echo ++$tabindex;?>">
				<?php
					//$tresults obtained above
					echo '<option value="">'._("None")."</option>";
					if (isset($tresults[0])) {
						foreach ($tresults as $tresult) {
							echo '<option value="'.$tresult['id'].'"'.($tresult['id'] == $timeout_id ? ' SELECTED' : '').'>'.$tresult['displayname']."</option>\n";
						}
					}
				?>
				</select>
			</td>
		</tr>
<?php
	}
?>
		<tr>
			<td><a href="#" class="info"><?php echo _("Loop Before i-dest");?><span><?php echo _("If checked, and there is an 'i' (invalid extension) destination defined below, the IVR will play invalid option and then loop back to the beginning for the designated loop counts prior to going to the invalid (i) destination.");?></span></a></td>
			<td><input type="checkbox" name="alt_invalid" <?php echo $ivr_details['alt_invalid'] ?> tabindex="<?php echo ++$tabindex;?>"></td>
		</tr>
<?php
	$invalid_id = isset($ivr_details['invalid_id'])?$ivr_details['invalid_id']:'';
	if(function_exists('recordings_list')) { //only include if recordings is enabled ?>
		<tr>
			<td><a href="#" class="info"><?php echo _("Invalid Message")?><span><?php echo _("If an invalid extension is pressed and a message is selected, it will be played in place of the announcement message when looping back to the top of the IVR. It will not be played if the t destination is the next target. If nothing is selected, the system will play a default invalid extension message before going back to the main announcement")?></span></a></td>
			<td>
				<select name="invalid_id" tabindex="<?php echo ++$tabindex;?>">
				<?php
					//$tresults obtained above
					echo '<option value="">'._("None")."</option>";
					if (isset($tresults[0])) {
						foreach ($tresults as $tresult) {
							echo '<option value="'.$tresult['id'].'"'.($tresult['id'] == $invalid_id ? ' SELECTED' : '').'>'.$tresult['displayname']."</option>\n";
						}
					}
				?>
				</select>
			</td>
		</tr>
<?php
	}
?>
		<tr>
			<td><a href="#" class="info"><?php echo _("Repeat Loops:")?><span><?php echo _("The number of times we should loop when invalid input or no input has been entered before going to the defined or default generated 'i' or 't' options. If the 'i' or 't' options are defined, the above check boxes must be checked in order to loop.")?></span></a></td>
			<td>
				<select name="loops" tabindex="<?php echo ++$tabindex;?>">
				<?php 
					$default = (isset($ivr_details['loops']) ? $ivr_details['loops'] : 2);
					for ($i=0; $i <= 9; $i++) {
						echo '<option value="'.$i.'" '.($i == $default ? 'SELECTED' : '').'>'.$i.'</option>';
					}
				?>		
				</select>		
			</td>
		</tr>

		<tr><td colspan=2><hr /></td></tr>
		<tr><td colspan=2>

			<input name="increase" type="submit" value="<?php echo _("Increase Options")?>" disabled>
			&nbsp;
			<input name="Submit" type="submit" value="<?php echo _("Save")?>" tabindex="<?php echo ++$tabindex;?>" disabled>
			&nbsp;
			<?php if ($nbroptions > 1) { ?>
			<input name="decrease" type="submit" value="<?php echo _("Decrease Options")?>" disabled>
			<?php } ?>
		</td>
	</tr>
	<tr><td colspan=2><hr /></td></tr></table>
	<style type="text/css">
	#ivr-dests tr:nth-child(odd){
	background-color: #FCE7CE;
	}
	</style>
	<table id="ivr-dests">
<?php
	// Draw the destinations
	$dests = ivr_get_dests($id);
	$count = 0;
	if (!empty($dests)) {
		foreach ($dests as $dest) {
			drawdestinations($count, $dest['selection'], $dest['dest'], $dest['ivr_ret']);
			$count++;
    }
	}
	while ($count < $nbroptions) {
		drawdestinations($count, null, null, 0);
		$count++;
	}
?>
	
</table>
<?php
	if ($nbroptions < $count) { 
		echo "<input type='hidden' name='nbroptions' value=$count />\n";
	} else {
		echo "<input type='hidden' name='nbroptions' value=$nbroptions />\n";
	} 

	global $module_hook;
	echo $module_hook->hookHtml;
?>
	<input name="increase" type="submit" value="<?php echo _("Increase Options")?>" disabled>
	&nbsp;
	<input name="Submit" type="submit" value="<?php echo _("Save")?>" disabled>
	&nbsp;
	<?php if ($nbroptions > 1) { ?>
	<input name="decrease" type="submit" value="<?php echo _("Decrease Options")?>" disabled>
	<?php } ?>
	
	<script language="javascript">
	<!--
$(document).ready(function() {  
	$(':submit:disabled').removeAttr('disabled'); 
});

function delEntry(e){
	$('[name=option'+e+'],[name=goto'+e+']').val('').parent().parent().fadeOut(500,function(){$(this).remove();});
}
 
var theForm = document.prompt;
theForm.displayname.focus();

	function prompt_onsubmit() {
		var msgInvalidOption = "<?php echo _("Invalid option"); ?>";
		
		defaultEmptyOK = true;

		// go thru the form looking for options
		// where the option isn't blank (as that will be removed) do the validation
	    var allelems = theForm.elements;
        if (allelems != null)
        {
        	var i, elem;
            for (i = 0; elem = allelems[i]; i++)
            {
            	if (elem.type == 'text' && elem.name.indexOf('option') == 0)
                {
                	if (elem.value != '') {
                    	if (!isIVROption(elem.value))
                        	return warnInvalid(elem, msgInvalidOption);
                        
                        var gotoNum = elem.name.charAt(6);
                        var isok = validateSingleDestination(theForm,gotoNum,true);
                        if (!isok)
                        	return false;
                    }
                 }
          	}
        }
                              	
		return true;
	}
	
	//-->

	</script>
        </form>
        </div>


<?php

echo "</div>\n";
}

function drawdestinations($count, $sel,  $dest, $ivr_ret) { 
	global $tabindex, $id;
?>
	<tr>
	<td style="text-align:right;">
  <input title="<?php echo _("Digits to press for this choice")?>" size="4" type="text" name="option<?php echo $count ?>" value="<?php echo $sel ?>" tabindex="<?php echo ++$tabindex;?>">
	</td>
	<td>
		<?php echo drawselects($dest,$count,false,false); ?>
	</td>
	<td>
		<small><a href="#" class="info"><?php echo _("Return to IVR")?><span><?php echo _("Check this box to have this option return to a parent IVR if it was called from a parent IVR. If not, it will go to the chosen destination.<br><br>The return path will be to any IVR that was in the call path prior to this IVR which could lead to strange results if there was an IVR called in the call path but not immediately before this")?></span></a></small>
		<input type="checkbox" name="ivr_ret<?php echo $count ?>" value="ivr_ret" <?php echo $ivr_ret?'CHECKED':''; ?>>
	<?php if(function_exists('ivr_dests_hook_show')){
		ivr_dests_hook_show($id, $dest);
	}
	?>
		<img src="images/trash.png" style="cursor:pointer" title="<?php echo _('Delete this entry. Dont forget to click &ldquo;Save&rdquo; to save changes!');?>" onclick="delEntry(<?php echo $count;?>)">
	</td>
	</tr>
	

<?php
}

// this can be removed in 2.2 and put back to just runModuleSQL which is in admin/functions.inc.php
// I didn't want to do it in 2.1 as there's a significant user base out there, and it will break
// them if we do it here.

function localrunModuleSQL($moddir,$type){
        global $db;
        $data='';
        if (is_file("modules/{$moddir}/{$type}.sql")) {
                // run sql script
                $fd = fopen("modules/{$moddir}/{$type}.sql","r");
                while (!feof($fd)) {
                        $data .= fread($fd, 1024);
                }
                fclose($fd);

                preg_match_all("/((SELECT|INSERT|UPDATE|DELETE|CREATE|DROP).*);\s*\n/Us", $data, $matches);

                foreach ($matches[1] as $sql) {
                                $result = $db->query($sql);
                                if(DB::IsError($result)) {
                                        return false;
                                }
                }
                return true;
        }
                return true;
}

?>
