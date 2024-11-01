<div class="content_block">
<div class="compatibility">
<table>
<tr valign="top">
	<th class="general_heading" scope="row" colspan="2"><strong><?php _e('Local Server Compatibility'); ?></strong></th>
</tr>


<tr valign="top">
	<td class="label" scope="row"><?php _e('Directory Exists?'); ?></td>
	<td>
		<?php if($directory_exists) { ?>
		<span class="wp-backup-plus-meets answer"><?php _e('Yes!'); ?></span>	
		<?php } else { ?>
		<span class="wp-backup-plus-lacks answer_no"><?php _e('No!'); ?></span> <?php printf(__('Please ensure that the directory you specified exists.')); ?>
		<?php } ?>
	</td>
</tr>
<tr valign="top">
	<td class="label" scope="row"><?php _e('Directory Writeable?'); ?></td>
	<td>
		<?php if($directory_writable) { ?>
		<span class="wp-backup-plus-meets answer"><?php _e('Yes!'); ?></span>	
		<?php } else { ?>
		<span class="wp-backup-plus-lacks answer_no"><?php _e('No!'); ?></span> <?php printf(__('Please ensure that the directory you specified is writable by the PHP process.')); ?>
		<?php } ?>
	</td>
</tr>
</table></div></div>