<a href="config.php?display=ivr&action=add" class = "list-group-item"><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add IVR")?></a>
<?php if($_REQUEST['action'] != ''){
?>
<table id="ivrnavgrid" data-url="?display=ivr&action=getJSON&jdata=grid&quietmode=1" data-cache="false" data-height="299" data-toggle="table" class="table table-striped">
	<thead>
			<tr>
			<th data-field="link" data-formatter="bnavFormatter"><?php echo _("IVR List")?></th>
		</tr>
	</thead>
</table>

<?php
}
