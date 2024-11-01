<div class="content_block">
<div class="compatibility">
<table>
<tr valign="top">
	<th class="general_heading" scope="row" colspan="2"><strong><?php _e('Remote Server Compatibility'); ?></strong></th>
</tr>


<tr valign="top">
	<td  scope="row"><?php _e('FTP Extension Loaded?'); ?></td>
	<td class="label" scope="row">
		<?php if($ftp_extension_exists) { ?>
            <span class="wp-backup-plus-meets answer"><?php _e('Yes!'); ?></span>	
		<?php } else { ?>
			<span class="wp-backup-plus-lacks answer_no"><?php _e('No!'); ?></span> <?php printf(__('Please ensure that the PHP <code>ftp_connect</code> function is available and the PHP FTP extension is loaded.')); ?>
		<?php } ?>
	</td>
</tr>


<?php if($ftp_extension_exists) { ?>
	<tr valign="top">
		<td class="label" scope="row"><?php _e('FTP Credentials Correct?'); ?></td>
		<td>
			<?php if($ftp_credentials_correct) { ?>
			<span class="wp-backup-plus-meets answer"><?php _e('Yes!'); ?></span>	
			<?php } else { ?>
			<span class="wp-backup-plus-lacks answer_no"><?php _e('No!'); ?></span> <?php printf(__('Please ensure that the credentials you entered above are correct.')); ?>
			<?php } ?>
		</td>
	</tr>


	<?php if($ftp_credentials_correct) { ?>
	<tr valign="top">
		<td class="label" scope="row"><?php _e('Path Valid?'); ?></td>
		<td>
			<?php if($path_valid) { ?>
			<span class="wp-backup-plus-meets answer"><?php _e('Yes!'); ?></span>	
			<?php } else { ?>
			<span class="wp-backup-plus-lacks answer_no"><?php _e('No!'); ?></span> <?php printf(__('Please ensure the path you entered above exists and is accessible to the user you specified.'), $temporary_directory); ?>
			<?php } ?>
		</td>
	</tr>
	<?php } ?>
	

<?php } ?>
</table></div></div>