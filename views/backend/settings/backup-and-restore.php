<script>
function backup(value){
	
	jQuery("#backup_listing").val(7).attr('selected',true);
	show_backup(7);
	 // jQuery('#backup_listing[value=1]').attr('selected', 'selected');
	  
	 
	var backup=jQuery('.code1').val();
	if(backup=='0'){
	jQuery("#server").css("display", "none");
		jQuery("#dropbox").css("display", "none");
		jQuery("#amazon").css("display", "none");
	}
	if(backup=='1'){
		jQuery("#server").css("display", "block");
		jQuery("#dropbox").css("display", "block");
		jQuery("#amazon").css("display", "block");
		jQuery(".server").css("display", "block");
		jQuery(".dropbox").css("display", "block");
		jQuery(".amazon").css("display", "block");
		//jQuery("#all").css("display", "block");
	}
	else if(backup=="server"){
		
		jQuery("#server").css("display", "block");
		jQuery(".server").css("display", "block");
		jQuery("#dropbox").css("display", "none");
		jQuery("#amazon").css("display", "none");
		jQuery(".amazon").css("display", "none");
		jQuery(".dropbox").css("display", "none");
		//jQuery("#all").css("display", "none");
		
		}
	else if(backup=="amazon"){
		jQuery("#amazon").css("display", "block");
		jQuery(".amazon").css("display", "block");
		jQuery("#server").css("display", "none");
		jQuery("#dropbox").css("display", "none");
		jQuery(".server").css("display", "none");
		jQuery(".dropbox").css("display", "none");
		//jQuery("#all").css("display", "none");
		
		
	}
	else{
		
	jQuery("#dropbox").css("display", "block");
	jQuery(".dropbox").css("display", "block");
		jQuery("#server").css("display", "none");
		jQuery("#amazon").css("display", "none");
		jQuery(".server").css("display", "none");
		jQuery(".amazon").css("display", "none");
		//jQuery("#all").css("display", "none");
		
	}
	
}
	</script>
<script>
	function delete_amazon_file(aid){
		
		var answer = confirm("Are you sure you want to delete data from Amazon?")
	if (answer){
		fid=jQuery("#"+aid).attr('fid')
		jQuery("#tr_"+fid).css("display", "none");
		 value=jQuery("#"+aid).attr('aid')
	urlinfo=jQuery("#url").val();
	
		jQuery.ajax({
        url: urlinfo+'?action=wp_backup_plus_amazon_backup&step='+value,
		 success: function(data){
			
			 	return false;
        }
	
    });
	return false
	}
		
	else{	
	return false;	
	}
	return false;	
	}
	function delete_dropbox_file(aid){
			var answer = confirm("Are you sure you want to delete data from Dropbox?")
	if (answer){
	
		fid=jQuery("#"+aid).attr('fid')
		jQuery("#tr_"+fid).css("display", "none");
		 value=jQuery("#"+aid).attr('aid')
	urlinfo=jQuery("#url").val();

		jQuery.ajax({
        url: urlinfo+'?action=wp_backup_plus_dropbox_backup&step='+value,
		 success: function(data){
			
			 	return false;
        }
	
    });
	}else{
	return false;	
	}
	return false;	
	}
	
	function delete_server_file(aid){
		var answer = confirm("Are you sure you want to delete data from Server?")
	if (answer){
		fid=jQuery("#"+aid).attr('fid')
		jQuery("#tr_"+fid).css("display", "none");
		 value=jQuery("#"+aid).attr('aid')
	urlinfo=jQuery("#url").val();
	
		jQuery.ajax({
        url: urlinfo+'?action=wp_backup_plus_server_backup&step='+value,
		 success: function(data){
			
			 	return false;
        }
	
    });
	}
	else{
		return false;
	}
	return false;	
	}
	</script>
    <script>
	//for display defaul 7 day backup
    jQuery(document).ready(function() {
		show_backup(7);
		
		
	});
    </script>
	<script>
	function show_backup(value){
	jQuery('.shw_backup').each(function() {
			date=jQuery(this).attr('date');
			list_id=jQuery(this).attr('list')
		
    //var first=new Date((jQuery(this).attr('date')));
	first=new Date(date);
	 var second=new Date();
	  var one = new Date(first.getFullYear(), first.getMonth(), first.getDate());
    var two = new Date(second.getFullYear(), second.getMonth(), second.getDate());

    // Do the math.
    var millisecondsPerDay = 1000 * 60 * 60 * 24;
    var millisBetween = two.getTime() - one.getTime();
    var days = millisBetween / millisecondsPerDay;
	
    // Round down.
   no_of_days= Math.floor(days);
   if(value!=1){
   if(no_of_days>value){
	  jQuery("#"+list_id).hide();
	   jQuery(this).hide();
	   
   }
    if(no_of_days<value){
		 jQuery("#"+list_id).show();
	   jQuery(this).show();
	  
	   
   }
  }
  else{
	   jQuery("#"+list_id).show();
	  jQuery(this).show(); 
	   
	  
  }

});

		
		
	}
	</script>
<script type="text/javascript">
Cufon.replace('.proxima', {fontFamily:'Proxima Nova Lt'});
Cufon.replace('.myriad_pro', {fontFamily:'Myriad Pro'});
</script>

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
<div id="tabs_wrapper">
<ul class="tabs">
  <li><a href="#" class="selected">List Backup</a></li>
  <li class="no_border"><a class="active" href="<?php echo site_url().'/wp-admin/admin.php?page=wp-backup-upload';?>">Perform Backup & Restore</a></li>

</ul>

<!--Tab-1 Content Start-->
<div class="tab-content">
<div class="shadow_overlay"></div><?php //settings_errors(); ?>
  <div id="setting-error-settings_updated" style="display:none"; class="updated settings-error" ></div>
<?php

if(!isset($backups['server'])){?>
<div id="setting-error-settings_updated" class="updated settings-error"> 
<p><strong>No backup option selected, please select methods from <a href="<?php echo site_url().'/wp-admin/admin.php?page=wp-backup-plus-settings'?>">Settings</a> </strong></p></div>
	
<?php }?>
<?php

if(empty($backups['server'])){?>
<div id="setting-error-settings_updated" class="updated settings-error"> 
<p><strong>No backup found ! </strong></p></div>
	
<?php }?>

<div class="clear"></div>
 <?php 
if(($backups['server']) or $backups['dropbox'] or  $backups['amazon']){?>
<div class="filter"> <span class="label myriad_pro">Source</span> 
  <!-- //select backups-->
 
  <select  onchange="backup(this)" class="code1 selectBox inputbox custom-class1 custom-class2 selectBox-dropdown filter_input"  name="wp-backup-plus[backup-data]" id="wp-backup-plus-backup-data">
    <?php foreach($backups as $method_key => $method_backups) { if(empty($method_backups)) { continue; } ?>
    <option value="<?php echo $method_key ;?>">
    <?php esc_attr_e(self::$backup_methods[$method_key]); ?>
    </option>
    <?php  }?>
  </select>
  <?php } ?>
</div>
<?php
if(($backups['server']) or $backups['dropbox'] or  $backups['amazon']){?>
<div class="filter"> <span class="label myriad_pro">Backups</span> 
  <!-- //select backups-->
 
  <select  onchange="show_backup(this.value)"  id="backup_listing">
  <option  selected="selected" value="7">7 days backups</option>
   <option   value="30">30 days backups</option>
  <option  value="1">All days backups</option>
     
  </select>
  <?php } ?>
</div>


<div class="clear"></div>
<?php 
if(($backups['server']) or $backups['dropbox'] or  $backups['amazon']){?>

<div class="backup_res_grid_">
<div class="backup_res_grid_heading">
<ul>
<li>
<span class="name myriad_pro">Source</span>
<span class="time myriad_pro">Time</span>
<span class="size myriad_pro">Size</span>
<span class="actions_bts myriad_pro">Actions</span>
</li>
</ul>
<?php } ?>
</div>

<?php 

?>
<?php   foreach($backups as $method_key => $method_backups) { if(empty($method_backups)) { continue; }
 
 ?>
 <div class="backup_res_grid" id="<?php echo $method_key?>">
<ul>
<input type ="hidden"  value="<?php echo site_url().'/wp-admin/admin-ajax.php';?>"  id="url"/>

<li>
  <span class="source_heading myriad_pro">
    <label class="<?php echo $method_key ;?>">
      <?php esc_attr_e(self::$backup_methods[$method_key]); ?>
    </label>
    </span>
    </li>

<!--all backup-->



<?php $i=1; foreach($method_backups as $backup) { ?>

<li id="tr_<?php echo $method_key?>_<?php echo $i; ?>">

  <form  id="wp-backup-plus-restore-form-<?php echo $method_key?>-<?php echo $i; ?>"  date="<?php echo $backup->LastModified ;?>" class="<?php esc_html_e($backup->WPBP); ?> test shw_backup" enctype="multipart/form-data" method="post" action="<?php esc_url(add_query_arg(array())); ?>"  list="tr_<?php echo $method_key?>_<?php echo $i; ?>">
    <span class="myriad_pro name"><?php esc_html_e($backup->WPBP); ?></span>
    <span class="myriad_pro time"><?php echo $backup->LastModified ;?></span>
     <span class="myriad_pro size"><?php echo $backup->Size;?></span>
   <span class="actions_bts"><input class="code" name="wp-backup-plus[backup-data]" id="wp-backup-plus-backup-data" type ="hidden" value="<?php esc_attr_e(maybe_serialize($backup)); ?>">
      
      <?php wp_nonce_field('wp-backup-plus-download-or-restore-backup', 'wp-backup-plus-download-or-restore-backup-nonce'); ?>
      <?php  if($root_writable) { ?>
      <input type="submit"  attribute ="<?php echo $method_key?>-<?php echo $i; ?>" class="button button-primary restore_btn" name="wp-backup-plus-restore-backup" id="wp-backup-plus-restore-backup" value=" "/>
      <?php } ?>
      <input type="submit" class="button button-secondary dwnld_btn" name="wp-backup-plus-download-backup" id="wp-backup-plus-download-backup" value=" " />
      <a class="delete_btn"  fid="<?php echo $method_key?>_<?php echo $i; ?>" id="link_<?php echo $method_key?>_<?php echo $i; ?>" aid="<?php echo $backup->Name; ?>" onclick ="return delete_<?php echo $method_key;?>_file(this.id)" href=""></a>
      
      </span>
  </form>
</li>

<?php $i++; }?>
</ul>
</div>
<?php }?>


</div>
<!--amazon form-->
<div style="display:none;"id="amazon1">
  <form id="wp-backup-plus-restore-form"	enctype="multipart/form-data" method="post" action="<?php esc_url(add_query_arg(array())); ?>">
    <?php $i=1; foreach( $backups['amazon'] as $backup) { 
					?>
    <label>
      <?php esc_html_e($backup->WPBP)?>
    </label>
    <input  <?php if ($i==1) {?> checked <?php } ?>	nme="<?php echo $backup->Name ?>" class="code" name="wp-backup-plus[backup-data]" type="hidden" value="<?php esc_attr_e(maybe_serialize($backup)); ?>" />
    <a id="<?php echo $backup->Name; ?>" onclick ="return delete_amazon_file(this.id)" href="">Delete</a> <br/>
    <?php wp_nonce_field('wp-backup-plus-download-or-restore-backup', 'wp-backup-plus-download-or-restore-backup-nonce'); ?>
    <?php  if($root_writable) { ?>
    <input type="submit"   class="button button-primary" name="wp-backup-plus-restore-backup" id="wp-backup-plus-restore-backup" value="<?php _e('Restore Backup'); ?>"/>
    <?php } ?>
    <input type="submit" class="button button-secondary" name="wp-backup-plus-download-backup" id="wp-backup-plus-download-backup" value="<?php _e('Download Backup'); ?>" />
    <?php
					$i++;}?>
  </form>
</div>

<!--   server -->

<div style="display:none;" id="server1">
  <?php foreach($backups['server'] as $backup) { 
								?>
  <form id="wp-backup-plus-restore-form_<?php esc_html_e($backup->WPBP); ?>" class="<?php esc_html_e($backup->WPBP); ?>"	enctype="multipart/form-data" method="post" action="<?php esc_url(add_query_arg(array())); ?>">
    <?php esc_html_e($backup->WPBP); ?>
    <input class="code" name="wp-backup-plus[backup-data]" id="wp-backup-plus-backup-data" type ="hidden" value="<?php esc_attr_e(maybe_serialize($backup)); ?>">
    <a id="<?php echo $backup->Name; ?>" onclick ="return delete_server_file(this.id)" href="">Delete</a> <br/>
    <br />
    <?php wp_nonce_field('wp-backup-plus-download-or-restore-backup', 'wp-backup-plus-download-or-restore-backup-nonce'); ?>
    <?php  if($root_writable) { ?>
    <input type="submit"   attribute="<?php esc_html_e($backup->WPBP); ?>" class="button button-primary" name="wp-backup-plus-restore-backup" id="wp-backup-plus-restore-backup" value="<?php _e('Restore Backup'); ?>"/>
    <?php } ?>
    <input type="submit" class="button button-secondary" name="wp-backup-plus-download-backup" id="wp-backup-plus-download-backup" value="<?php _e('Download Backup'); ?>" />
  </form>
  <?php 
 }?>
</div>

<!--   dropbox -->
<div style="display:none;" id="dropbox1">
  <?php foreach($backups['dropbox'] as $backup){?>
  <form id="wp-backup-plus-restore-form"	enctype="multipart/form-data" method="post" action="<?php esc_url(add_query_arg(array())); ?>">
    <?php esc_html_e($backup->WPBP); ?>
    <input class="code" name="wp-backup-plus[backup-data]" id="wp-backup-plus-backup-data" type ="hidden" value="<?php esc_attr_e(maybe_serialize($backup)); ?>">
    <a id="<?php echo $backup->Name; ?>" onclick ="return delete_dropbox_file(this.id)" href="">Delete</a> <br/>
    <br />
    <?php wp_nonce_field('wp-backup-plus-download-or-restore-backup', 'wp-backup-plus-download-or-restore-backup-nonce'); ?>
    <?php  if($root_writable) { ?>
    <input type="submit"   class="button button-primary" name="wp-backup-plus-restore-backup" id="wp-backup-plus-restore-backup" value="<?php _e('Restore Backup'); ?>"/>
    <?php } ?>
    <input type="submit" class="button button-secondary" name="wp-backup-plus-download-backup" id="wp-backup-plus-download-backup" value="<?php _e('Download Backup'); ?>" />
  </form>
  <?php 
 }?>
</div>
<div class="clear"></div>
</div>
</div>

