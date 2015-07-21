<a href="config.php?display=ivr" class = "list-group-item <?php echo ($_REQUEST['id'] == ''?'hidden':'')?>"><i class="fa fa-list"></i>&nbsp;<?php echo _("List IVRs")?></a>
<a href="config.php?display=ivr&action=add" class = "list-group-item"><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add IVR")?></a>
<?php if($_REQUEST['action'] != ''){
?>
<table id="ivrnavgrid" data-url="ajax.php?module=ivr&command=getJSON&jdata=grid" data-cache="false" data-height="299" data-toggle="table" class="table table-striped">
	<thead>
			<tr>
			<th data-field="link" data-formatter="bnavFormatter"><?php echo _("IVR List")?></th>
		</tr>
	</thead>
</table>

<?php
}
