<div class="content_block">
<div class="compatibility">
<table class="compatibility_grid">
<tr valign="top">
	<th class="general_heading " scope="row" colspan="2"><strong><?php _e('General Compatibility'); ?></strong></th>
</tr>


<tr valign="top">
	<td class="label" scope="row"><?php _e('Shell Exec Operational?'); ?></td>
	<td>
		<?php if($has_shell_exec) { ?>
		<span class="wp-backup-plus-meets answer"><?php _e('Yes!'); ?></span>	
		<?php } else { ?>
		<span class="wp-backup-plus-lacks answer_no"><?php _e('No!'); ?></span> <?php printf(__('Please ensure that your PHP process can use the <code>shell_exec</code> command.')); ?>
		<?php } ?>
	</td>
</tr>
<tr valign="top">
	<td class="label" scope="row"><?php _e('Temporary Directory Exists?'); ?></td>
	<td>
		<?php if($has_temporary_directory) { ?>
		<span class="wp-backup-plus-meets answer"><?php _e('Yes!'); ?></span>	
		<?php } else { ?>
		<span class="wp-backup-plus-lacks answer_no"><?php _e('No!'); ?></span> <?php printf(__('Please ensure that the WP Backup Plus temporary directory (<code>%s</code>) has been created.'), $temporary_directory); ?>
		<?php } ?>
	</td>
</tr>
<tr valign="top">
	<td class="label" scope="row"><?php _e('Temporary Directory Writeable?'); ?></td>
	<td>
		<?php if($temporary_directory_writable) { ?>
		<span class="wp-backup-plus-meets answer"><?php _e('Yes!'); ?></span>	
		<?php } else { ?>
		<span class="wp-backup-plus-lacks answer_no"><?php _e('No!'); ?></span> <?php printf(__('Please ensure that the WP Backup Plus temporary directory (<code>%s</code>) is writable by the web server.'), $temporary_directory); ?>
		<?php } ?>
	</td>
</tr>
<tr valign="top">
	<td class="label" scope="row"><?php _e('Zip Archive Installed?'); ?></td>
	<td>
		<?php if($has_zip_archive) { ?>
		<span class="wp-backup-plus-meets answer"><?php _e('Yes!'); ?></span>	
		<?php } else { ?>
		<span class="wp-backup-plus-lacks answer_no"><?php _e('No!'); ?></span> <?php _e('Please install the <a href="http://www.php.net/manual/en/intro.zip.php">PHP ZipArchive</a> extension so that WP Backup Plus can archive your database and files appropriately.'); ?>
		<?php } ?>
	</td>
</tr>
</table>
</div>
</div>