
  <div class="shadow_overlay"></div>
  <div class="clear"></div>
  <table class="form-table" cellpadding="0" cellspacing="0" border="0">
    <tr valign="top">
      <td>
       <div class="shadow_overlay"></div>
         <?php settings_errors('server-error'); ?>
       <div class="clear"></div>
       <br/>
      <div class="content_block no_border" style="padding:0">
          <div class="left_label">Type</div>
          <div class="right_content">
            <ul class="wp-backup-plus-provider-server-no-margin">
              <li>
                <label>
                  <input <?php checked($settings['type'], 'local'); ?> class="wp-backup-plus-provider-server-type radio_btn" type="radio" name="wp-backup-plus-provider-server[type]" id="wp-backup-plus-provider-server-type-local" value="local" />
                  <span>
                  <?php _e('Local'); ?>
                  </span> </label>
              </li>
              <li>
                <label>
                  <input <?php checked($settings['type'], 'remote'); ?> class="wp-backup-plus-provider-server-type radio_btn" type="radio" name="wp-backup-plus-provider-server[type]" id="wp-backup-plus-provider-server-type-remote" value="remote" />
                  <span>
                  <?php _e('Remote'); ?>
                  </span> </label>
              </li>
            </ul>
          </div>
        </div></td>
    </tr>
    <tr valign="top" data-server-type="local">
      <td><div class="content_block no_border content_block_new">
          <div class="left_label">Path</div>
          <div class="right_content">
            <ul>
              <li>
                <input type="text" class="code large-text common_textbox" name="wp-backup-plus-provider-server[local-directory]" id="wp-backup-plus-provider-server-local-directory" value="<?php esc_attr_e($settings['local-directory']); ?>" />
              </li>
              <li class="instruction">
                <?php _e('Enter the path to the directory in which you wish to store backups. The directory should exist and be writable by the PHP process.'); ?>
              </li>
            </ul>
          </div>
        </div></td>
    </tr>
    <tr valign="top" data-server-type="remote">
      <td><div class="content_block no_border content_block_new">
          <div class="left_label">FTP Host</div>
          <div class="right_content">
            <input type="text" class="code regular-text common_textbox" name="wp-backup-plus-provider-server[host]" id="wp-backup-plus-provider-server-host" value="<?php esc_attr_e($settings['host']); ?>" />
          </div>
        </div>
        <div class="content_block no_border content_block_new">
          <div class="left_label">FTP Username</div>
          <div class="right_content">
            <input type="text" class="code regular-text common_textbox" name="wp-backup-plus-provider-server[username]" id="wp-backup-plus-provider-server-username" value="<?php esc_attr_e($settings['username']); ?>" />
          </div>
        </div>
        <div class="content_block no_border content_block_new">
          <div class="left_label">FTP Password</div>
          <div class="right_content">
            <input type="password" class="code regular-text common_textbox" name="wp-backup-plus-provider-server[password]" id="wp-backup-plus-provider-server-password" value="<?php esc_attr_e($settings['password']); ?>" />
          </div>
        </div>
        <div class="content_block no_border content_block_new">
          <div class="left_label">FTP Path</div>
          <div class="right_content">
            <ul>
              <li>
                <input type="text" class="code large-text common_textbox" name="wp-backup-plus-provider-server[remote-directory]" id="wp-backup-plus-provider-server-remote-directory" value="<?php esc_attr_e($settings['remote-directory']); ?>" />
              </li>
              <li>
                <?php _e('Enter the path to the directory in which you wish to store backups. The directory should exist and be accessible to the FTP credentials you enter below.'); ?>
              </li>
            </ul>
          </div>
        </div></td>
    </tr>
    <tr valign="top" data-server-type="remote">
      <td></td>
    </tr>
    <tr valign="top" data-server-type="remote">
      <td></td>
    </tr>
    <tr valign="top" data-server-type="remote">
      <td></td>
    </tr>
  </table>

<input type="hidden" name="wp-backup-plus-provider-server[secure]" id="wp-backup-plus-provider-server-secure" value="FTP" />
