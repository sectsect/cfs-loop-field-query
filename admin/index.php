<div class="wrap">
	<h1>CFS Loop Field Query<span style="font-size: 10px; padding-left: 12px;">- For Events -</span></h1>

	<?php if(isset($_GET['settings-updated'])): ?>
		<span style="background: #fff; border: 2px solid #5bd535; border-radius: 5px; color: #888; padding: 3px 10px; display: inline-block; margin: 10px 0 0;">Save Settings.</span>
	<?php endif; ?>

	<section>
		<form method="post" action="options.php">
			<h3>General Settings</h3>
	        <?php
	            settings_fields( 'cfs_lfq-settings-group' );
	            do_settings_sections( 'cfs_lfq-settings-group' );
	        ?>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="cfs_lfq_posttype">Post Type Name</label>
						</th>
						<td>
							<input type="text" id="cfs_lfq_posttype" class="regular-text" name="cfs_lfq_posttype" value="<?php echo get_option('cfs_lfq_posttype'); ?>" style="width: 150px;">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="cfs_lfq_taxonomy">Taxonomy Name <span style="font-size: 11px; font-weight: normal;">(Optional)</span></label>
						</th>
						<td>
							<input type="text" id="cfs_lfq_taxonomy" class="regular-text" name="cfs_lfq_taxonomy" value="<?php echo get_option('cfs_lfq_taxonomy'); ?>" style="width: 150px;">
						</td>
					</tr>
				</tbody>
			</table>
			<h3>Field Settings</h3>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="cfs_lfq_dategroup">Loop Field Name</label>
						</th>
						<td>
							<input type="text" id="cfs_lfq_dategroup" class="regular-text" name="cfs_lfq_dategroup" value="<?php echo get_option('cfs_lfq_dategroup'); ?>" style="width: 150px;">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="cfs_lfq_datefield">Field Name in Loop Feld</label>
						</th>
						<td>
							<input type="text" id="cfs_lfq_datefield" class="regular-text" name="cfs_lfq_datefield" value="<?php echo get_option('cfs_lfq_datefield'); ?>" style="width: 150px;">
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</form>
	</section>
</div>
