<?php	
	$amazon 	= 0;
	$dropbox 	= 0;
	$server 	= 0;
	$all		= 0;
	
	for($i=1; $i< count($settings['methods']); $i++){
		if($settings['methods'][$i] == 'amazon')
			$amazon = count($_SESSION['error']['amazon']);
		elseif($settings['methods'][$i] == 'dropbox')
			$dropbox = count($_SESSION['error']['dropbox']);
		elseif($settings['methods'][$i] == 'server')
			$server = count($_SESSION['error']['server']);
	}
	if($amazon)
		$tab = 'tabs4';
	elseif($dropbox)
		$tab = 'tabs5';
	elseif($server)
		$tab = 'tabs6';
	
	$all = count($_SESSION['error']['compatibilty']);	
	$all = $amazon+$dropbox+$server+$all;
	unset($_SESSION['error']);

?>
<script type="text/javascript">
var tab = "<?php echo $tab;?>";
Cufon.replace('.proxima', {fontFamily:'Proxima Nova Lt'});
Cufon.replace('.myriad_pro', {fontFamily:'Myriad Pro'});
</script>
<script type="text/javascript">
jQuery(document).ready(function() {
	
		
jQuery(".topic").click(function() {
	
	if(jQuery('#wp-backup-plus-notification').is(':checked')){
		jQuery('#keep_backup_for_one_year').show();
		jQuery('#keep_manual_backup_forever').show();
		jQuery('#1').show();
		jQuery('#2').show();
		jQuery('#keep_backup_for_one_year').attr('checked',true);
		jQuery('#keep_manual_backup_forever').attr('checked',true);
	}
	else{
		jQuery('#keep_backup_for_one_year').attr('checked',false);
		jQuery('#keep_manual_backup_forever').attr('checked',false);
		jQuery('#keep_backup_for_one_year').hide();
		jQuery('#keep_manual_backup_forever').hide();
		jQuery('#1').hide();
		jQuery('#2').hide();
		}
	});
	jQuery('.tabs a').click(function(){
		switch_tabs(jQuery(this));
	});
	switch_tabs(jQuery('.defaulttab'));
	
	if(tab == 'tabs4' ){
		jQuery('.tab-content').hide();
		jQuery('.tabs a').removeClass("selected");
		jQuery('tabs4').show();
		jQuery('#tabs4').css('display','block');
		jQuery(".tabs a[rel='tabs4']").addClass("selected");
	}else if(tab == 'tabs5' ){
		jQuery('.tab-content').hide();
		jQuery('.tabs a').removeClass("selected");
		jQuery('tabs5').show();
		jQuery('#tabs5').css('display','block');
		jQuery(".tabs a[rel='tabs5']").addClass("selected");
	}else if(tab == 'tabs6' ){
		jQuery('.tab-content').hide();
		jQuery('.tabs a').removeClass("selected");
		jQuery('tabs6').show();
		jQuery('#tabs6').css('display','block');
		jQuery(".tabs a[rel='tabs6']").addClass("selected");
	}
	
});
function switch_tabs(obj)
{
	jQuery('.tab-content').hide();
	jQuery('.tabs a').removeClass("selected");
	var id = obj.attr("rel");
	jQuery('#'+id).show();
	jQuery('.save_btn').show();
	if(id == 'tabs4' || id == 'tabs5'){
		jQuery('.save_btn').hide();
	}
	obj.addClass("selected");
}
jQuery(window).load(function(){
	
	if(document.location.href.indexOf('tabs4')>-1){
		jQuery('.tab-content').hide();
		jQuery('.tabs a').removeClass("selected");
		jQuery('tabs4').show();
		jQuery('#tabs4').css('display','block');
		jQuery(".tabs a[rel='tabs4']").addClass("selected");
	}else if(document.location.href.indexOf('tabs5')>-1){
		jQuery('.tab-content').hide();
		jQuery('.tabs a').removeClass("selected");
		jQuery('tabs5').show();
		jQuery('#tabs5').css('display','block');
		jQuery(".tabs a[rel='tabs5']").addClass("selected");
	}else if(document.location.href.indexOf('tabs6')>-1){
		jQuery('.tab-content').hide();
		jQuery('.tabs a').removeClass("selected");
		jQuery('tabs6').show();
		jQuery('#tabs6').css('display','block');
		jQuery(".tabs a[rel='tabs6']").addClass("selected");
	}		
	
});

</script>


		 <!--[if IE 8]>
<link href="<?php echo site_url().'/wp-content/plugins/wp-backup-plus/resources/css/ie_style.css'?>" rel="stylesheet" />"
<![endif]-->
<!--[if IE 7]>
<link href="<?php echo site_url().'/wp-content/plugins/wp-backup-plus/resources/css/ie7_style.css'?>" type="text/css" rel="stylesheet" />"
<![endif]-->

<!--<script>
$(document).ready(function (){

if(!$('#wp-backup-plus-methods-dropbox').attr('checked')) {
	$('#display_msg5').text("fdfdfd")
	$(".display_msg5").show();
  
  
}
if(!$('#wp-backup-plus-methods-amazon').attr('checked')) {
		$('#display_msg4').text("fdfdfd")
	$(".display_msg4"	).show();
  
}
if(!$('#wp-backup-plus-methods-server').attr('checked')) {
	$('#display_msg6').text("fdfdfd")
	$(".display_msg6").show();
	
}
	
});

-->
</script>
<?php
function getDirectorySize($path) 
{ 
  $totalsize = 0; 
  $totalcount = 0; 
  $dircount = 0; 
  if ($handle = opendir ($path)) 
  { 
    while (false !== ($file = readdir($handle))) 
    { 
      $nextpath = $path . '/' . $file; 
      if ($file != '.' && $file != '..' && !is_link ($nextpath)) 
      { 
        if (is_dir ($nextpath)) 
        { 
          $dircount++; 
          $result = getDirectorySize($nextpath); 
          $totalsize += $result['size']; 
          $totalcount += $result['count']; 
          $dircount += $result['dircount']; 
        } 
        elseif (is_file ($nextpath)) 
        { 
          $totalsize += filesize ($nextpath); 
          $totalcount++; 
        } 
      } 
    } 
  } 
  closedir ($handle); 
  $total['size'] = $totalsize; 
  $total['count'] = $totalcount; 
  $total['dircount'] = $dircount; 
  return $total; 
} 

function sizeFormat($size) 
{ 
    if($size<1024) 
    { 
        return $size; 
    } 
    else if($size<(1024*1024)) 
    { 
        $size=round($size/1024,1); 
        return $size; 
    } 
    else if($size<(1024*1024*1024)) 
    { 
        $size=round($size/(1024*1024),1); 
        return $size; 
    } 
    else 
    { 
        $size=round($size/(1024*1024*1024),1); 
        return $size; 
    } 


}  
$upload_dir = wp_upload_dir();
$dir= $upload_dir['basedir'];


$path=$dir."\wp-backup-plus/temp/"; 
$ar=getDirectorySize($path); 

$size=sizeFormat($ar['size']);
$cron=get_option('cron');
 $cron_time="";
// fetch scheduled cron time 
foreach($cron as $key=>$wordpress){
		foreach($wordpress as $cron_key=>$find_cron){
			
		if($cron_key=="wp_backup_plus_perform_backup"){
			 $cron_time=$key;
			}
	}
}
	if(!empty($cron_time)){
	 	$realtime = date("m-d-y H:i:s",$cron_time);
	}

?>

<title>Backup Lite</title>
</head>
<body>
<!--start main wrap--> 

<!--start right panel-->
<div class="right_panel">

  <div class="logo"></div>
   <!--start top links-->
   
 <div class="top_links">
    <ul>
      <li><a class="active" href="<?php echo site_url().'/wp-admin/admin.php?page=wp-backup-plus-settings'?>">Settings</a></li>
      <li><a href="<?php echo site_url().'/wp-admin/admin.php?page=wp-backup-upload';?>">Backup, Upload &amp; Restore</a></li>
    </ul>
  </div>

 <!--close top links-->
  <div class="border_gray"></div>
  <div class="main_heading proxima">WP Backup Lite - Settings</div>
   <div class="clear"></div>
	<div style="line-height:2em;"><?php settings_errors('compatibility-requirements');?></div>
    <div style="line-height:2em;"><?php settings_errors('general');?></div>
  <div id="tabs_wrapper">
    <ul class="tabs">
      <li><a href="#" class="defaulttab " rel="tabs1">General Settings</a></li>
      <li><a class="" href="#" rel="tabs2">Content Settings</a></li>
      <li><a class="" href="#" rel="tabs4">Amazon Settings <?php if($amazon){echo '<span style="color:red;">('.$amazon.')</span>';}?></a></li>
      <li><a class="" href="#" rel="tabs5">Dropbox Settings <?php if($dropbox){ echo '<span style="color:red;">('.$dropbox.')</span>';}?></a></li>
      <li><a class="" href="#" rel="tabs6">Server Settings <?php if($server){ echo '<span style="color:red;">('.$server.')</span>';}?></a></li>
      <li><a class="" href="#" rel="tabs7">Compatibility <?php if($all){ echo '<span style="color:red;">('.$all.')</span>';}?></a></li>
    </ul>
    <!--Tab-1 Content Start-->
    
    <form id="wpap_settings_form" method="post" action="<?php esc_attr_e(esc_url(add_query_arg(array()))); ?>">
      <div class="tab-content" id="tabs1">
        <div class="shadow_overlay"></div>
            <?php //settings_errors(); ?>
        <div class="clear"></div>
        <div class="content_block backup_non_justify">
          <div class="left_label">Select where you want to upload the Backup</div>
          <div class="right_content">
            <ul>
              <?php foreach(self::$backup_methods as $method_key => $method_value) { ?>
              <li>
                <input class="wp-backup-plus-method" <?php if($method_key == 'manual'||$method_key == 'amazon'||$method_key == 'dropbox') { echo 'disabled="disabled"'; } ?> <?php checked(in_array($method_key, $settings['methods']), true); ?> type="checkbox" name="wp-backup-plus[methods][]" id="wp-backup-plus-methods-<?php esc_attr_e($method_key); ?>" value="<?php esc_attr_e($method_key); ?>" />
                <input type="hidden"  id="setting_url"  value="<?php echo site_url().'/wp-admin/admin.php?page=wp-backup-plus-settings'?>">
                <span>
                <?php esc_html_e($method_value); ?>
                </span></li>
              <?php } ?>
             
                </span></li>
            </ul>
          </div>
        </div>
      </div>
      <?php
	  
   if(!WBP_USE_TREE){ ?>
   <ul>
   <li>
   <label>
      <input class="wpap_exclude_directory"  <?php checked(true, in_array('/wp-content/', $settings['exclude-directories-named'])); ?> type="checkbox" name="wp-backup-plus[exclude-directories-named][]" value="/wp-content/" />
      <code>/wp-content/</code>
      </label>
      </li>
      <li>
        <label>
          <input class="wpap_exclude_directory" <?php checked(true, in_array('/wp-admin/', $settings['exclude-directories-named'])); ?> type="checkbox" name="wp-backup-plus[exclude-directories-named][]" value="/wp-admin/" />
          <code>/wp-admin/</code> </label>
      </li>
      <li>
        <label>
          <input class="wpap_exclude_directory" <?php checked(true, in_array('/wp-includes/', $settings['exclude-directories-named'])); ?> type="checkbox" name="wp-backup-plus[exclude-directories-named][]" value="/wp-includes/" />
          <code>/wp-includes/</code> </label>
      </li>
      </ul>
      <?php
	   
}
?>
     
      <?php

   if(WBP_USE_TREE){ ?>
      
      <!--Tab-1 Content Close--> 
      <!--Tab-2 Content Start-->
      <div style="display: none;" class="tab-content" id="tabs2">
        <div class="shadow_overlay"></div>
    
        <div class="clear"></div>
        <div class="content_block">
          <div class="left_label">
            <?php _e('Exclude Directories/Files From Backups'); ?>
          </div>
          <div class="right_content">
            <div class="scroll_box">
              <div id="wpbp_excluded_directories_container">Loading...</div>
              <?php
     $settings['additional-exclusions'] = implode("\n", array_merge($settings['exclude-directories-named'], explode("\n",$settings['additional-exclusions'])));
?>
             
            </div>
             <div style="height:10px" class="clear"></div>
            <div style="height:10px" class="clear"></div>
            <div class="instruction">
              <?php _e('Click on checkbox for excluding the file or directories'); ?>
            </div>
          </div>
        </div>
        <div class="content_block">
          <div class="left_label">
            <?php _e('Excluded directories and files'); ?>
          </div>
          <div class="right_content">
            <textarea class="code large-text" rows="5" name="wp-backup-plus[additional-exclusions]" id="wp-backup-plus-additional-exclusions"><?php rtrim(esc_html_e($settings['additional-exclusions']), "\n"); ?>
</textarea>
           
          </div>
        </div>
        <input type="hidden" name="wp-backup-plus[additional-exclusions_saved]" value="<?php esc_html_e($settings['additional-exclusions']); ?>" />
        <?php
   }
   else{ ?>
        <label for="wp-backup-plus-additional-exclusions">
          <?php _e('Additional Excluded Directories'); ?>
        </label>
        <textarea class="code large-text" rows="5" name="wp-backup-plus[additional-exclusions]" id="wp-backup-plus-additional-exclusions"><?php esc_html_e($settings['additional-exclusions']);?>
</textarea>
        <input type="hidden" name="wp-backup-plus[additional-exclusions_saved]" value="<?php esc_html_e($settings['additional-exclusions']); ?>" />
        <small>
        <?php _e('Enter one directory per line that you wish to exclude (like <code>/wp-content/uploads/</code>)'); ?>
        </small>
        <?php
																					 }
?>
        <div class="content_block no_border">
          <div class="left_label">
            <?php _e('Backup size (uncompressed):'); ?>
          </div>
          <div class="right_content">
            <ul>
              <?php
		$temp = new WP_Backup_Plus();
		$backup_file_size = 0;
		$files = array();
		$temp->get_files_by_size(ABSPATH, $files, true, $backup_file_size);
					?>
              <li><?php  $total_size=round(($backup_file_size/1048576)); _e($total_size); ?>MB </li>
             
            </ul>
            <br>
          	<a href='http://wpbackupplus.com/?utm_source=plugin&utm_medium=free&utm_campaign=autobackups' target='blank'><input type='button' class='auto_upgrade' name='update_backup_plus' value=''/></a>
          </div>
        </div>
      </div>
      <?php if(defined('WP_BACKUP_PLUS_ALLOW_MYSQLDUMP') && WP_BACKUP_PLUS_ALLOW_MYSQLDUMP) { ?>
      <h3>
        <?php _e('MySQL Settings'); ?>
      </h3>
      <table class="form-table">
        <tbody>
          <tr valign="top">
            <th scope="row"><label for="wp-backup-plus-mysqldump">
                <?php _e('MySQL Dump Location'); ?>
              </label></th>
            <td><input type="text" class="code regular-text" name="wp-backup-plus[mysqldump]" id="wp-backup-plus-mysqldump" value="<?php esc_attr_e($settings['mysqldump']); ?>" />
              <br />
              <small>
              <?php _e('If you have a large database or want to speed up your database backups, you can enter the path to your server\'s <code>mysqldump</code> binary to provide a better experience.'); ?>
              </small><br />
              <small>
              <?php _e('The plugin has attempted to auto-detect an appropriate value, but if the field is empty you should be able to log in to your server and run the command <code>which mysqldump</code> from the terminal to obtain the correct value.'); ?>
              </small></td>
          </tr>
        </tbody>
      </table>
      <?php } ?>
      <?php 
	$i=3;
	foreach(self::$backup_methods as $backup_key => $backup_name) {
		if($i<=6){?>
      <div style="display: block;" class="tab-content" id="tabs<?php echo $i ?>"> 
      <div class="shadow_overlay"></div>
        <div class="display_msg_ display_msg<?php echo $i; ?>" style="display:none">
      
          <p id="display_msg<?php echo $i; ?>"></p>
        
          </div>
        <div class="wp-backup-plus-method-settings" data-method="<?php esc_attr_e($backup_key); ?>">
          <?php
do_action("wp_backup_plus_method_settings_{$backup_key}"); ?>
        </div>
      </div>
      <?php  } 
   $i++;
   ?>
      <?php } ?>
      
      <!--start compatibility--> 
      <a name="wp-backup-plus-compatibility"></a>
      <div style="display: block;" class="tab-content" id="tabs7">
        <div class="shadow_overlay"></div>
        <div class="clear"></div>
        <div class="top_link">If there are any errors, your backup will not be performed. Please ensure that no errors are indicated below before depending on WP Backup Plus.</div>
        <?php do_action('wp_backup_plus_compatibility_table'); ?>
        <div style="display: block;" class="tab-content" id="tabs3"> </div>
        <?php foreach($settings['methods'] as $method_key) {
		
		  ?>
        <?php do_action("wp_backup_plus_compatibility_table_{$method_key}"); } ?>
      </div>
      
      <!--close compatibility-->
      
      <p class="">
        <?php wp_nonce_field('save-wp-backup-plus-settings', 'save-wp-backup-plus-settings-nonce'); ?>
        <input type="submit" class="save_btn myriad_pro" name="save-wp-backup-plus-settings" value="<?php _e('Save Changes'); ?>" />
      </p>
    </form>
  </div>