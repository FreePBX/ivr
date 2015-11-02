<div id="toolbar-ivrbnav">
<a href="config.php?display=ivr" class = "btn btn-default"><i class="fa fa-list"></i>&nbsp;<?php echo _("List IVRs")?></a>
<a href="config.php?display=ivr&action=add" class = "btn btn-default"><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add IVR")?></a>
</div>
<table id="ivrnavgrid"
 		data-search="true"
		data-toolbar="#toolbar-ivrbnav"
		data-url="ajax.php?module=ivr&command=getJSON&jdata=grid"
		data-cache="false"
		data-toggle="table" 
		class="table">
	<thead>
			<tr>
			<th data-field="name" data-sortable="true"><?php echo _("IVR List")?></th>
		</tr>
	</thead>
</table>
<script type="text/javascript">
	$("#ivrnavgrid").on('click-row.bs.table',function(e,row,elem){
		window.location = '?display=ivr&action=edit&id='+row['id'];
	})
</script>
