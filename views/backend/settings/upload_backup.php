<script type="text/javascript">
        jQuery(document).ready(function() {
			jQuery("#tip4").click(function() {
				jQuery.fancybox({
						'padding'		: 0,
						'autoScale'		: false,
						'transitionIn'	: 'none',
						'transitionOut'	: 'none',
						'title'			: this.title,
						'width'			: 680,
						'height'		: 495,
						'href'			: this.href.replace(new RegExp("watch\\?v=", "i"), 'v/'),
						'type'			: 'swf',
						'swf'			: {
							 'wmode'		: 'transparent',
							'allowfullscreen'	: 'true'
						}
					});

				return false;
			});
		});
    </script>
<script type="text/javascript">
Cufon.replace('.proxima', {fontFamily:'Proxima Nova Lt'});
Cufon.replace('.myriad_pro', {fontFamily:'Myriad Pro'});
</script>
   <script type="text/javascript">
        function backup_stop(){
				var answer = confirm("Are you sure you want to stop backup process?")
	if (answer){
	jQuery('#form_stop_backup').submit();	
	}
		else{
		return false;
		}
		return false;
	}
    </script>
<?php  $settings = self::get_settings();?>
<?php if(count($settings['methods']) < 2){ add_settings_error('no_method_error', 'wp_no_method_error','Select where you want to upload the backup under <a href="'.site_url().'/wp-admin/admin.php?page=wp-backup-plus-settings">Settings</a>'); }?>

    
  

<div class="wrap">
<div id="wpap_download_backup_link_container"></div>
<title>Backup And Restore</title>
</head>

<body>

<!--start right panel-->
<div class="right_panel">
<div class="logo"></div>
<!--start top links-->
<div class="top_links">
  <ul>
    <li><a href="<?php echo site_url().'/wp-admin/admin.php?page=wp-backup-plus-settings'?>">Settings</a></li>
    <li><a class="active" href="<?php echo site_url().'/wp-admin/admin.php?page=wp-backup-upload';?>">Backup, Upload &amp; Restore</a></li>
  </ul>
</div>
<!--close top links-->
<div class="border_gray"></div>
<div class="main_heading proxima">WP Backup Lite - Backup & Restore</div>
<div class="clear"></div>
<div style="line-height:2em;"><?php settings_errors('compatibility-requirements');?></div>
<div style="line-height:2em;"><?php settings_errors('general');?></div>
<div style="line-height:2em;"><?php settings_errors('no_method_error');?></div>
<div id="tabs_wrapper">
<ul class="tabs">
  <li><a href="<?php echo site_url().'/wp-admin/admin.php?page=wp-backup-plus-restore';?>">List Backup</a></li>
  <li><a class="selected" href="<?php echo site_url().'/wp-admin/admin.php?page=wp-backup-upload';?>">Perform Backup &amp; Restore</a></li>
</ul>
<div class="tab-content">
  <div class="shadow_overlay"></div>
  <?php //settings_errors(); ?>
  <div id="setting-error-settings_updated" style="display:none"; class="updated settings-error" ></div>
   <div id="setting-error-settings_updated_loader" style=display:none; class="updated settings-error loading_bg" > </div>
  <div class="content_block">
    <div class="dwnload_block">
      <h3 class="myriad_pro">Restore <a id="tip4" class="blue" href="https://www.youtube.com/watch?v=N9tJFFv5Du0">(?)</a></h3>
      <div class="left_label">Upload Backup</div>
      <div class="right_content">
        <form id="wp-backup-plus-restore-form" enctype="multipart/form-data" method="post" action="<?php esc_url(add_query_arg(array())); ?>">
          <table class="form-table form-table_1" cellpadding="0" cellspacing="0" border="0">
            <tbody>
              <tr valign="top" data-backup-action="existing">
                <td><?php if(!empty($backups)) { ?>
                  <select class="code" name="wp-backup-plus[backup-data]" id="wp-backup-plus-backup-data">
                    <?php foreach($backups as $method_key => $method_backups) { if(empty($method_backups)) { continue; } ?>
                    <optgroup label	="<?php esc_attr_e(self::$backup_methods[$method_key]); ?>">
                    <?php foreach($method_backups as $backup) { ?>
                    <option value="<?php esc_attr_e(maybe_serialize($backup)); ?>">
                    <?php esc_html_e($backup->WPBP); ?>
                    </option>
                    <?php } ?>
                    </optgroup>
                    <?php } ?>
                  </select>
                  <br />
                  <small>
                  <?php _e('<a class="wp-backup-plus-backup-toggle" href="#">Upload a backup file</a>'); ?>
                  </small>
                  <?php } ?></td>
              </tr>
              <tr valign="top" data-backup-action="upload">
                <td style="padding:0 !important"><input type="file" name="wp-backup-plus-backup-file" id="wp-backup-plus-backup-file" value="" />
                  <?php if(!empty($backups)) { ?>
                  <br />
                  <small>
                  <?php _e('<a class="wp-backup-plus-backup-toggle" href="#">Select an existing backup</a>'); ?>
                  </small>
                  <?php } ?></td>
              </tr>
            </tbody>
          </table>
          <input type="hidden" name="wp-backup-plus[backup-action]" id="wp-backup-plus-backup-action" value="<?php echo (empty($backups) ? 'upload' : 'existing'); ?>" />
          <p class="submit res_bckp">
            <?php wp_nonce_field('wp-backup-plus-download-or-restore-backup', 'wp-backup-plus-download-or-restore-backup-nonce'); 
		
			?>
            <input type="submit" class="button <?php if(!self::get_backup_in_progress()){ ?>button-primary <?php } ?> restore_btn_  <?php if(self::get_backup_in_progress()){?> restore_btn_disable <?php }?>" name="wp-backup-plus-restore-backup" 	<?php if(self::get_backup_in_progress()){?> disabled <?php } ?> id="wp-backup-plus-restore-backup" value=" " />
            
            <!--<input type="submit" class="button button-secondary" name="wp-backup-plus-download-backup" id="wp-backup-plus-download-backup" value="<?php //_e('Download Backup'); ?>" />--> 
            <span id="wp-backup-plus-ajax-restore-log"></span> </p>
        </form>
      </div>
      
    </div>
    <div class="content_block no_border" style="padding:25px 0 0 0">
      <div class="left_label">Backup Status</div>
      <div class="right_content">
        <ul>
           <li>Your Maximum Backup Size on your Current Server is <?php echo MAX_ZIP_SIZE/1048576; ?> MB.</li>
        </ul>
      </div>
    </div>
  </div>
  
  <!--start buttons-->
  <div class="content_block no_border">
    <div class="dwnload_block">
      <h3 class="myriad_pro">On Demand Backup</h3>
      <div class="buttons">
        <form class="left" method="post" action="<?php esc_url(add_query_arg(array())); ?>">
          <p class="submit padding_0">
            <input type="submit" class="button backup_now  <?php if(self::get_backup_in_progress()){?> backup_now_disable <?php }?> " name="wp-backup-plus-backup-now"  id="now_backup"  	<?php if(self::get_backup_in_progress()){ ?>disabled <?php } ?> value=" " />
            <input type="submit" class="button button-secondary backup_dwnld <?php if(self::get_backup_in_progress()){?> backup_dwnld_disable <?php }?>  " name="wp-backup-plus-backup-download" 	<?php if(self::get_backup_in_progress()){ ?>disabled <?php } ?> id="wp-backup-plus-backup-download" value=" " />
            <span id="wp-backup-plus-ajax-log"></span> </p>
          <?php wp_nonce_field('wp-backup-plus-backup-now', 'wp-backup-plus-backup-now-nonce'); ?>
        </form>
      </div>
      
       <div class="buttons">
        <form class="left" method="post"  id="form_stop_backup" onSubmit="return backup_stop()"  action="admin.php?page=wp-backup-upload&backup_status=stop">
          <p class="submit padding_0">
            <input type="submit" class="button backup_now stop_bckp_bt"   name="wp-backup-plus-backup-now" id="stop_backup"   style="display:none" value=" " />
            
        </form>
      </div>
      
      <!--close buttons-->
      
      <div class="content_block no_border" style="padding:25px 0 0 0">
        <div class="left_label">
          <?php if(isset($_GET['settings-updated'])){ _e('Backup Status');} ?>
        </div>
        <div class="right_content">
          <ul>
            <li>
              <p>
                <?php if(isset($_GET['settings-updated'])){_e('A backup is currently in progress. Please see below for information about the status.');}?>
              </p>
              <div id="backup_process" class="text_style" <?php if(!isset($_GET['settings-updated'])){?> style="display:none"  <?php } ?>></div>
            </li>
            <li>
              <div id="wp-backup-plus-backup-status">
                <textarea rows="7" class="code large-text" id="wp-backup-plus-backup-status-field"></textarea>
              </div>
            </li>
          </ul>
          <div class="clear"></div>
        </div>
      </div>
     
    </div>
  </div>
</div>

</div></div>

<!--close right panel--> 

