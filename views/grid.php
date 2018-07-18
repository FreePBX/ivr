<div class="container-fluid">
	<h1>
		<?php echo _('IVR') ?>
	</h1>
	<div class="display no-border">
		<div class="row">
			<div class="col-sm-12">
				<div class="fpbx-container">
					<div id="toolbar-all">
						<a href="config.php?display=ivr&amp;action=add" class="btn btn-default">
							<i class="fa fa-plus"></i>&nbsp;
							<?php echo _("Add IVR") ?>
						</a>
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
								<th data-sortable="true" data-field="name">
									<?php echo _("IVR Name") ?>
								</th>
								<th class="col-md-2" data-field="link" data-formatter="actionFormatter">
									<?php echo _("Actions") ?>
								</th>
							</tr>
						</thead>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>