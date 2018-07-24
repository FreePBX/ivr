<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

/* $Id$ */
$get_vars = array(
				'action' 		=> '',
				'id'			=> '',
				'display'		=> ''
);
foreach ($get_vars as $k => $v) {
	$var[$k] = isset($_REQUEST[$k]) ? $_REQUEST[$k] : $v;
	$$k = $var[$k];//todo: legacy support, needs to GO!
}
if(!$astman){
	dbug(_("No Astman, Not loading IVR"));
	echo "<h1>"._("Connection Error")."</h1>";
	echo '<div class="well well-danger">';
	echo _("A connection to Asterisk Manager could not be made. This module requires Asterisk to be running and have proper credentials");
	echo '</div>';
}else{
	if ((!$action && !$id) || $action == "save") {
	?>
	<div class="container-fluid">
		<h1><?php echo _('IVR')?></h1>
		<div class = "display no-border">
			<div class="row">
				<div class="col-sm-12">
					<div class="fpbx-container">
						<div id="toolbar-all">
							<a href="config.php?display=ivr&amp;action=add" class = "btn btn-default"><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add IVR")?></a>
						</div>
						<table id="ivrgrid"
						data-url="ajax.php?module=ivr&amp;command=getJSON&amp;jdata=grid"
						data-cache="false"
					 	data-toggle="table"
						data-cookie="true"
      			data-cookie-id-table="ivrgrid"
      			data-toolbar="#toolbar-all"
      			data-maintain-selected="true"
      			data-pagination="true"
      			data-search="true"
						 class="table table-striped">
    						<thead>
 								<tr>
        							<th data-sortable="true" data-field="name"><?php echo _("IVR Name")?></th>
								<th data-sortable="true" data-field="description"><?php echo _("IVR Description")?></th>
        							<th class="col-md-2" data-field="link" data-formatter="actionFormatter"><?php echo _("Actions")?></th>
    							</tr>
    						</thead>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>




	<?php
	}else{
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
		$id 	= isset($_REQUEST['id']) ? $_REQUEST['id'] : null;

		echo load_view(__DIR__.'/views/form.php', array('request' => $_REQUEST));
	}
}
