<?php
 /*

    Plugin Name: WP Backup Lite

    Plugin URI: http://wpbackupplus.com

    Description: WP Backup Lite allows you to backup, clone, and restore your wordpress website at any time for either download or to your server for safe storage. Its one of the fastest backup utilities to move your wordpress website and a quick way to better secure your websites, best of all, its completely free.

    Version: 1.0

    Author: WP Backup Lite

    Author URI: http://wpbackupplus.com/?utm_source=plugin&utm_medium=free&utm_campaign=menubar

  */
define('CURRENT_VERSION','1.0');
define('UPDATE_FILE_PATH', '');
define('WBP_USE_TREE', true);

session_start();

$max_upload = (int)(ini_get('upload_max_filesize'));
$max_post = (int)(ini_get('post_max_size'));
$memory_limit = (int)(ini_get('memory_limit'));
$upload_mb = min($max_upload, $max_post, $memory_limit);

define('MAX_ZIP_SIZE', $upload_mb*1048576);
include_once(path_join(ABSPATH, "wp-includes/pluggable.php"));

// error handler function

/**

 * Short description

 *     

 * Long description

 *

 * @param int $errno

 * @param string $errstr

 * @param string $errfile

 * @param string $errline

 *

 * @return bool

 */

function wp_backup_plus_error_handler($errno, $errstr, $errfile, $errline)

{

  if (!(error_reporting() & $errno)) {

    // This error code is not included in error_reporting

    return;

  }



  switch ($errno) {

  case E_USER_ERROR:

    echo "<b>ERROR</b> [$errno] $errstr<br />\n";

    echo "  Fatal error on line $errline in file $errfile";

    echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";

    echo "Aborting...<br />\n";

    exit(1);

    break;



  case E_USER_WARNING:

    echo "<b>WARNING</b> [$errno] $errstr<br />\n";
		break;

case E_USER_NOTICE:

    echo "<b>NOTICE</b> [$errno] $errstr<br />\n";
		break;

default:
echo "Unknown error type: [$errno] $errstr<br />\n";

    break;

  }



  /* Don't execute PHP internal error handler */

  return true;

}



// set to the user defined error handler

//$old_error_handler = set_error_handler("wp_backup_plus_error_handler");





if (!class_exists('WP_Backup_Plus')) {



  class WP_Backup_Plus {

    /// CONSTANTS

    //// VERSION



    const VERSION = '1.0.0';



    //// KEYS

    const SETTINGS_KEY = '_wp_backup_plus_settings';

    const BACKUP_ERRORS_KEY = '_wp_backup_plus_errors';

    const BACKUP_IN_PROGRESS_KEY = '_wp_backup_plus_in_progress';

    const BACKUP_IN_PROGRESS_MESSAGES_KEY = '_wp_backup_plus_in_progress_messages';



    //// SLUGS

    const SETTINGS_SLUG_MENU = 'wp-backup-plus-settings';

    const SETTINGS_SLUG_SUBMENU_SETTINGS = 'wp-backup-plus-settings';

    const SETTINGS_SLUG_SUBMENU_RESTORE = 'wp-backup-plus-restore';
	const SETTINGS_SLUG_SUBMENU_UPLOAD = 'wp-backup-upload';

    const DIAGNOSTICS_EMAIL = 'nohrn@ohrnventures.com';



    //// CACHE

    const CACHE_PERIOD = 86400;



    // 24 HOURS

    /// DATA STORAGE

    private static $admin_page_hooks = array();

    private static $default_settings = array();

    private static $request_data = null;

    // For iteration

    private static $backup_directory = null;

    private static $backup_methods = array();

    private static $backup_schedules = array();



    /**

     * Short description

     *     

     * Long description

     *

     * @return null

     */

    public static function init() {
		if(isset($_GET['var']) && $_GET['var']){
			update_option('_wp_backup_plus_in_progress','no');	
		}
		self::add_actions();
		self::add_filters();
	  	do_action('wp_backup_plus_init');
    }



    /**

     * Short description

     *     

     * Long description

     *

     * @return null

     */

    private static function add_actions() {
		if (is_admin()) {	  
			add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_resources'));
			add_action('admin_menu', array(__CLASS__, 'add_interface_items'));
		}
		add_action('init', array(__CLASS__, 'initialize_defaults'));
		add_action('wp_ajax_wp_backup_plus_progress', array(__CLASS__, 'ajax_output_progress'));
		add_action('wp_ajax_wp_backup_plus_download', array(__CLASS__, 'ajax_output_download'));
		add_action('wp_ajax_wp_backup_plus_restore', array(__CLASS__, 'ajax_output_restore'));
		add_action('wp_ajax_wp_backup_plus_get_backup_size', array(__CLASS__, 'ajax_output_get_backup_size'));	
		add_action('wp_ajax_wp_backup_plus_server_backup', array(__CLASS__, 'server_backup'));
		add_action('wp_ajax_wp_backup_plus_get_directories', array(__CLASS__, 'ajax_output_get_directories'));
		add_action('wp_backup_plus_compatibility_table', array(__CLASS__, 'display_compatibility_rows'));
		add_action('wp_backup_plus_perform_backup', array(__CLASS__, 'perform_backup'));
		add_action('wp_backup_plus_perform_scheduled_backup', array(__CLASS__, 'perform_scheduled_backup_now'));
		add_action('wp_backup_plus_perform_backup_manual', array(__CLASS__, 'perform_backup_manual'), 10, 3);
		add_action('wp_backup_plus_delete_backup', array(__CLASS__, 'delete_backup'));
		add_action('wp_backup_plus_delete_backup_if_other_backup_downloaded', array(__CLASS__, 'delete_backup_if_other_backup_downloaded'));

    }





    /**

     * Short description

     *     

     * Long description

     *

     * @return null

     */

    private static function add_filters() {

		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'add_settings_link'));
		add_filter('plugin_row_meta', array(__CLASS__, 'add_plugin_row_meta'), 11, 4);
		add_filter('wp_backup_plus_backup_methods', array(__CLASS__, 'add_manual_backup_method'));
		add_filter('wp_backup_plus_backup_schedules', array(__CLASS__, 'add_ondemand_backup_schedule'));
		add_filter('wp_backup_plus_meets_compatibility_requirements', array(__CLASS__, 'meets_compatibility_requirements'));
		add_filter('wp_backup_plus_meets_compatibility_requirements_manual', '__return_true');
		add_filter('wp_backup_plus_pre_settings_save', array(__CLASS__, 'sanitize_settings'));
		add_filter('wp_backup_plus_database_setup', array(__CLASS__, 'wpbackup_setup'));

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @return null

     */

    public static function initialize_defaults() {

      // By default make there be no scheduled backup and only manual backup

		self::$default_settings['methods'] = array('manual');
		self::$default_settings['schedule'] = 'ondemand';
		self::$default_settings['notification'] = 'yes';
		self::$default_settings['email'] = get_the_author_meta('email', get_current_user_id());
		self::$default_settings['mysqldump'] = self::get_default_mysqldump();
		self::$default_settings['exclude-directories-named'] = array();
		self::$default_settings['additional-exclusions'] = '';

	// Set up the available backup method names

      self::$backup_methods = apply_filters('wp_backup_plus_backup_methods', self::$backup_methods);

      // hack
      	self::$backup_methods['dropbox'] = "Dropbox";


      // Schedule Times

      //self::$backup_schedules = apply_filters('wp_backup_plus_backup_schedules', self::$backup_schedules);
	     apply_filters('wp_backup_plus_database_setup', array(__CLASS__, 'wpbackup_setup'));

    }



    /// PLUGIN SPECIFIC CALLBACKS



    /**

     * Short description

     *     

     * Long description

     *

     * @param array $methods

     *

     * @return array methods

     */

    public static function add_manual_backup_method($methods) {

      $methods['manual'] = __('Manual Backup');

      return $methods;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param array $schedules

     *

     * @return array schedules

     */

    public static function add_ondemand_backup_schedule($schedules) {

    $schedules['ondemand'] = array('interval' => 0, 'name' => __('On Demand Only'));
	$schedules['daily'] = array('interval' => 60 * 60 * 24, 'name' => __('Daily'));
	$schedules['weekly'] = array('interval' => 60 * 60 * 24 * 7, 'name' => __('Weekly'));
	$schedules['monthly'] = array('interval' => 60 * 60 * 24 * 30, 'name' => __('Monthly'));

	return $schedules;

    }

//create table 

    function wpbackup_setup() {
		
	global $wpdb;
    global $simple_location_version;
	$table_name= $wpdb->prefix . 'backup';
 
 
  $sql = "CREATE TABLE  IF NOT EXISTS " . $table_name . "( `backup_id` INT NOT NULL auto_increment, `name` VARCHAR(300) NULL , `location` TINYINT NULL COMMENT '1 for amazon, 2 for dropbox, 3 for localserver' , `download` TINYINT NULL COMMENT '1 for amazon, 2 for dropbox, 3 for localserver' ,`type` TINYINT NULL COMMENT '1 manual, 2 weekly, 3 monthly, 4 daily' , `created` DATETIME NULL, PRIMARY KEY (`backup_id`) )";
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	//for backup size display
	add_option( 'backup_size', '0', '', 'no' ); 
}

	
	/**

     * Performs a database backup

     *

     * Long description

     *

     * @param object $zip_archive

     * @param object $backup_errors

     * @param string $destination_path

     * @return null

     */

    public static function perform_database_backup(&$zip_archive, &$backup_errors, $destination_path) { 

      $zip_archive->addFromString('tables.sql', 'SHOW TABLES');

      self::add_meta_file($zip_archive);

      $zip_archive->close();  // write the archive for the first time

      $zip_archive = self::backup_database_to_zip($zip_archive, $backup_errors, $destination_path);

    }

/*public static function delete_backup_if_other_backup_downloaded(){
	global $wpdb;
	echo "SELECT * from ".$wpdb->prefix ."backup by name";
	$date=date("Y-m-d H:i:s");
	echo 'SELECT * from ".$wpdb->prefix ."backup   where created=".$date."group by name';
	$myrows = $wpdb->get_results( "SELECT * from ".$wpdb->prefix ."backup   where created=".$date."group by name");
print_r($myrows);
exit;	
foreach($myrows as $row){
		$filename=$row->name;
		$created= $row->created;
		$type=$row->type;	
		$id=$row->backup_id;
	}
	
	
	
	
	
	
}
*/
    /**

     * Performs a backup

     *

     * Long description

     *

     * @param boolean $download if true download the backup

     * @return null

     */


public static function delete_backup(){
	
	$settings = self::get_settings();
	
	global $wpdb;
	$myrows = $wpdb->get_results( "SELECT * from ".$wpdb->prefix ."backup" );
	
	foreach($myrows as $row){
		$filename=$row->name;
		$created= $row->created;
		$type=$row->type;	
		$id=$row->backup_id;
		
		//case 1 if delete backup checked  only
	
		
		$date1 = date("Y-m-d H:i:s");
		$date2 = $created;
		$diff = abs(strtotime($date2) - strtotime($date1));
		$years = floor($diff / (365*60*60*24));
		$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
		 $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
		
			 if($months>1){
				
			//delete backup from amazon
			if($settings['keep_manual_backup_forever']!='yes' &&$settings['keep_backup_for_one_year']!='yes'){
				
					$cls_a = new WP_Backup_Plus_Provider_Amazon();
					$cls_a->amazon_backup($filename);
				
				
			//delete backup from dropbox
				$cls_b = new WP_Backup_Plus_Provider_Dropbox();
				$cls_b->dropbox_backup($filename);
				
			//delete backup from server 
		
				$cls_c = new WP_Backup_Plus_Provider_server();
				$cls_c->server_backup($filename);
				global $wpdb;
				$table_name= $wpdb->prefix . 'backup';
				$wpdb->query('Delete from '.$table_name.' where  backup_id='.$row->backup_id);
				//$wpdb->query("DELETE FROM wp_backup WHERE backup_id =".$id);
				
				}
				if($settings['keep_manual_backup_forever']=='yes' && $settings['keep_backup_for_one_year ']!='yes'){
					
					if($type!="1" ){
				//delete those which are not manual
				$cls_a = new WP_Backup_Plus_Provider_Amazon();
				$cls_a->amazon_backup($filename);
				
				
			//delete backup from dropbox
				$cls_b = new WP_Backup_Plus_Provider_Dropbox();
				$cls_b->dropbox_backup($filename);
				
			//delete backup from server 
		
				$cls_c = new WP_Backup_Plus_Provider_server();
				$cls_c->server_backup($filename);
				//$wpdb->query("DELETE FROM wp_backup WHERE name =".$id);
				global $wpdb;
				$table_name= $wpdb->prefix . 'backup';
				$wpdb->query('Delete from '.$table_name.' where  backup_id='.$row->backup_id);
							}
			}
				 if($settings['keep_manual_backup_forever']=='yes' && $settings['keep_backup_for_one_year']=='yes'){
				
				
					
					if($type!="1" & $years>1){
				//delete those which are not manual
				$cls_a = new WP_Backup_Plus_Provider_Amazon();
				$cls_a->amazon_backup($filename);
				
				
			//delete backup from dropbox
				$cls_b = new WP_Backup_Plus_Provider_Dropbox();
				$cls_b->dropbox_backup($filename);
				
			//delete backup from server 
		
				$cls_c = new WP_Backup_Plus_Provider_server();
				$cls_c->server_backup($filename);
				//$wpdb->query("DELETE FROM wp_backup WHERE name =".$id);
				global $wpdb;
				$table_name= $wpdb->prefix . 'backup';
				$wpdb->query('Delete from '.$table_name.' where  backup_id='.$row->backup_id);
							}
				//keep a monthy backup for one year
				 if($settings['keep_manual_backup_forever']!='yes' && $settings['keep_backup_for_one_year']=='yes'){
					
				
					if($years>1){
				//delete those which are not manual
				$cls_a = new WP_Backup_Plus_Provider_Amazon();
				$cls_a->amazon_backup($filename);
				
				
			//delete backup from dropbox
				$cls_b = new WP_Backup_Plus_Provider_Dropbox();
				$cls_b->dropbox_backup($filename);
				
			//delete backup from server 
		
				$cls_c = new WP_Backup_Plus_Provider_server();
				$cls_c->server_backup($filename);
				//$wpdb->query("DELETE FROM wp_backup WHERE name =".$id);
				
				global $wpdb;
	$table_name= $wpdb->prefix . 'backup';
	$wpdb->query('Delete from '.$table_name.' where  backup_id='.$row->backup_id);
				}
			}
		}
		}
	}
	

}
    public static function perform_backup($download = false) { 
		
		set_time_limit(100000);
		self::set_backup_in_progress(true);
		self::set_backup_in_progress_messages(array());
		self::add_backup_in_progress_message(__('Starting backup...'));

	$backup_errors = new WP_Error;
	$settings = self::get_settings();

	$filename = self::get_backup_filename();
	session_start();
	$_SESSION['zipfile']=$filename;

	global $wpdb;
	$table_name= $wpdb->prefix . 'backup';

		$settings = self::get_settings();
		$schedule_type=$settings['schedule'];
		//schedule type
		if($schedule_type=="daily"){
			$stype=4;
			
		}
		else if($schedule_type=="monthly"){
			$stype=3;
			
		}
		else if($schedule_type=="weekly"){
			$stype=2;
			
		}
		
		
		$methods=$settings['methods'];
		foreach($methods as $method_name){
		if($method_name!="manual"){
			if($method_name=='server'){
				$loc='3';	
			}
			elseif($method_name=="amazon"){
				$loc='1';	
			}
			else if($method_name=="dropbox"){
				$loc='2';	
			}
				$wpdb->insert($table_name ,array('name' => $filename ,'location' => $loc,'type'=>$stype,'created' => date("Y-m-d H:i:s")));
			}
		}
		//put the backup at the temp folder
		// Backup database to zip

      $zip_file_path = path_join(self::get_temp_backup_directory(), $filename);

      //if(is_file($zip_file_path)){
		//	unlink($zip_file_path);

      //}

      $zip_archive = new ZipArchive;  
	if (true !== $zip_archive->open($zip_file_path, ZipArchive::CREATE)) {
	$backup_errors->add('wp-backup-plus-backup-database-to-zip', __('Could not backup database to the zip archive.'));

      }

      else{
	self::perform_database_backup($zip_archive, $backup_errors, $zip_file_path);

      }



      $db_errors = $backup_errors->get_error_messages("wp-backup-plus-backup-database-to-zip");

	

      if(!empty($db_errors)){

	while(list($i, $error)=each($db_errors)){

	  self::add_backup_in_progress_message(__('Warning: '.$error));

	}

	self::add_backup_in_progress_message(__('Warning: Backup terminated due to database backup errors'));

      }

      else{

	self::add_backup_in_progress_message(__('Backed up the database to archive...'));

      }



      if(empty($db_errors)){

	// Backup all files to zip

	self::add_backup_in_progress_message(__('Backing up files to zip...'));

        $zip_archive->open($zip_file_path);

	$zip_archive->addEmptyDir("files");

	$zip_archive->close();

	self::backup_files_to_zip($zip_archive, $backup_errors, $zip_file_path);

	$errors = $backup_errors->get_error_messages("wp-backup-plus-backup-files-to-zip");

	if(!empty($errors)){

	  while(list($i, $error)=each($errors)){

	    self::add_backup_in_progress_message(__('Warning: '.$error));

	  }

  	  self::add_backup_in_progress_message(__('Warning: Backup terminated due to file backup errors'));

	}

	else{

	  self::add_backup_in_progress_message(__('Backed up all appropriate files to a zip archive...'));

	}

      }



      try {
	$db_errors = $backup_errors->get_error_messages("wp-backup-plus-backup-database-to-zip");
	$file_errors = $backup_errors->get_error_messages("wp-backup-plus-backup-files-to-zip");

	$k = true;
	$last_file_size = 0;
	while($k){
	clearstatcache();
	$current_file_size = filesize($zip_file_path);

	  if($last_file_size == $current_file_size){

	    $k = false;

	  }

	  $last_file_size = $current_file_size;

	  sleep(5);

	}



	if(empty($db_errors) && empty($file_errors)){

	if ($download) {

	    sleep(3);

	    self::perform_backup_manual($backup_errors, $zip_file_path, $download);

	  } else {
		clearstatcache();
		$filesize = filesize($zip_file_path);
		$zipped_size=number_format($filesize/1048576, 1);

	    // Send to the appropriate backup method
		foreach ((array) $settings['methods'] as $method_key) {
			if($method_key!='manual'){

		self::add_backup_in_progress_message(sprintf(__('Starting backup process for method "%s"...'), $method_key));
		
		if($method_key=="amazon"||$method_key=="dropbox"){
			//check size is greater than 250
			//if($zipped_size<250){
			if($method_key=="dropbox"){
				if(self::drop_box_size_check( $zip_file_path)){
					do_action("wp_backup_plus_perform_backup_{$method_key}", $backup_errors, $zip_file_path, $download);
					self::add_backup_in_progress_message(sprintf(__('Backup process for method "%s" completed...'), self::$backup_methods[$method_key]));
				}else{
					self::add_backup_in_progress_message(sprintf(__('Can\'t upload at Dropbox as Dropbox Size is less then the backup Size.'), self::$backup_methods[$method_key]));	
				}
			}else{
				do_action("wp_backup_plus_perform_backup_{$method_key}", $backup_errors, $zip_file_path, $download);
				self::add_backup_in_progress_message(sprintf(__('Backup process for method "%s" completed...'), self::$backup_methods[$method_key]));
			}
			//}
			
			//else{
					//self::add_backup_in_progress_message(sprintf(__('Backup Zipped Size is greater than 250 mb "%s",so it could not be  completed...'), self::$backup_methods[$method_key]));
				
				
			//}
		}
		
		else{
			do_action("wp_backup_plus_perform_backup_{$method_key}", $backup_errors, $zip_file_path, $download);

		self::add_backup_in_progress_message(sprintf(__('Backup process for method "%s" completed...'), self::$backup_methods[$method_key]));
				}
		
				  }
		
				}
		
			  }   
		}


		} catch (Exception $e) {

     }



   /*   if (is_file($zip_file_path)) {

	//unlink($zip_file_path);

      }
if (is_file($database_backup_path)) {

	//	unlink($database_backup_path);

      }*/
	self::set_backup_errors($backup_errors);
	self::send_notification_mail($zip_file_path, $backup_errors, $settings, false);

	if ($download) {
	self::set_backup_in_progress(false);
	self::set_backup_in_progress_messages(array());

	exit;

      }



      $schedule_key = $settings['schedule'];

      $schedule = self::$backup_schedules[$schedule_key];



      if ($schedule && $schedule['interval'] > 0) {

	self::add_backup_in_progress_message(__('Adding the next scheduled backup to the queue...'));

	self::schedule_backup(time() + $schedule['interval']);

      }

	$current_file_size = filesize($zip_file_path);
 
      self::add_backup_in_progress_message(__('Backup has been completed!'));
	  unlink('size.txt');
		unlink('log.txt');
	  
	  self::set_backup_in_progress(false);
	  //delete size file
		

    }
	
	
	 public static function perform_scheduled_backup_now($download = false) { 



      set_time_limit(0);
		self::set_backup_in_progress(true);
		self::set_backup_in_progress_messages(array());
		self::add_backup_in_progress_message(__('Starting backup...'));


	$backup_errors = new WP_Error;
	$settings = self::get_settings();



      $filename = self::get_backup_filename();
		session_start();
	$_SESSION['zipfile']=$filename;

		global $wpdb;
	$table_name= $wpdb->prefix . 'backup';

		$settings = self::get_settings();
		$methods=$settings['methods'];
		foreach($methods as $method_name){
		if($method_name!="manual"){
			if($method_name=='server'){
				$loc='1';	
			}
				$wpdb->insert($table_name ,array('name' => $filename ,'location' => $loc,'type'=>'1' ,'created' => date("Y-m-d H:i:s")));
			}
		}
//only backup in temp folder 
$date=date("Y-m-d");


if($method_name!='server'){
	$wpdb->insert($table_name ,array('name' => $filename ,'location' => 4,'type'=>'5' ,'created' => date("Y-m-d H:i:s")));
	
	
	$myrows = $wpdb->get_results( "SELECT * from ".$wpdb->prefix ."backup   where created like '%".$date."%' and type=5 and name!='".$filename."'");
	$q="SELECT * from ".$wpdb->prefix ."backup   where created like '%".$date."%' and type=5 and name!=".$filename;
	
	
	foreach($myrows as $row){
		 $uploads = wp_upload_dir();
		$upload_file=unlink($uploads['basedir'] ."/wp-backup-plus/temp/".$row->name);
		$wpdb->query('Delete from '.$table_name.' where  backup_id='.$row->backup_id);
	}
}



      // Backup database to zip

      $zip_file_path = path_join(self::get_temp_backup_directory(), $filename);

      //if(is_file($zip_file_path)){

	//	unlink($zip_file_path);

      //}

      $zip_archive = new ZipArchive;  

      if (true !== $zip_archive->open($zip_file_path, ZipArchive::CREATE)) {

	$backup_errors->add('wp-backup-plus-backup-database-to-zip', __('Could not backup database to the zip archive.'));

      }

      else{

	self::perform_database_backup($zip_archive, $backup_errors, $zip_file_path);

      }



      $db_errors = $backup_errors->get_error_messages("wp-backup-plus-backup-database-to-zip");

	

      if(!empty($db_errors)){

	while(list($i, $error)=each($db_errors)){

	  self::add_backup_in_progress_message(__('Warning: '.$error));

	}

	self::add_backup_in_progress_message(__('Warning: Backup terminated due to database backup errors'));

      }

      else{

	self::add_backup_in_progress_message(__('Backed up the database to archive...'));

      }



      if(empty($db_errors)){

	// Backup all files to zip

	self::add_backup_in_progress_message(__('Backing up files to zip...'));

        $zip_archive->open($zip_file_path);

	$zip_archive->addEmptyDir("files");

	$zip_archive->close();

	self::backup_files_to_zip($zip_archive, $backup_errors, $zip_file_path);

	$errors = $backup_errors->get_error_messages("wp-backup-plus-backup-files-to-zip");

	if(!empty($errors)){

	  while(list($i, $error)=each($errors)){

	    self::add_backup_in_progress_message(__('Warning: '.$error));

	  }

  	  self::add_backup_in_progress_message(__('Warning: Backup terminated due to file backup errors'));

	}

	else{

	  self::add_backup_in_progress_message(__('Backed up all appropriate files to a zip archive...'));

	}

      }



      try {



	$db_errors = $backup_errors->get_error_messages("wp-backup-plus-backup-database-to-zip");

	$file_errors = $backup_errors->get_error_messages("wp-backup-plus-backup-files-to-zip");





	$k = true;

	$last_file_size = 0;

        while($k){

	  clearstatcache();

	  $current_file_size = filesize($zip_file_path);

	  if($last_file_size == $current_file_size){

	    $k = false;

	  }

	  $last_file_size = $current_file_size;

	  sleep(5);

	}



	if(empty($db_errors) && empty($file_errors)){



	  if ($download) {

	    sleep(3);

	    self::perform_backup_manual($backup_errors, $zip_file_path, $download);

	  } else {



	    clearstatcache();

	    $filesize = filesize($zip_file_path);
		$zipped_size=number_format($filesize/1048576, 1);


	    // Send to the appropriate backup method

	    foreach ((array) $settings['methods'] as $method_key) {
			if($method_key!='manual'){

		self::add_backup_in_progress_message(sprintf(__('Starting backup process for method "%s"...'), $method_key));
		
		if($method_key=="amazon"||$method_key=="dropbox"){
			//check size is greater than 250
			//if($zipped_size<250){
				
			
			if($method_key=="dropbox"){
				if(self::drop_box_size_check( $zip_file_path)){
					do_action("wp_backup_plus_perform_backup_{$method_key}", $backup_errors, $zip_file_path, $download);
					self::add_backup_in_progress_message(sprintf(__('Backup process for method "%s" completed...'), self::$backup_methods[$method_key]));
				}else{
					self::add_backup_in_progress_message(sprintf(__('Can\'t upload at Dropbox as Dropbox Size is less then the backup Size.'), self::$backup_methods[$method_key]));	
				}
			}else{
				do_action("wp_backup_plus_perform_backup_{$method_key}", $backup_errors, $zip_file_path, $download);
				self::add_backup_in_progress_message(sprintf(__('Backup process for method "%s" completed...'), self::$backup_methods[$method_key]));
			}
		
			//}
			
			//else{
					//self::add_backup_in_progress_message(sprintf(__('Backup Zipped Size is greater 250 mb "%s",so it could not be  completed...'), self::$backup_methods[$method_key]));
				
				
			//}
		}
		
		else{
			do_action("wp_backup_plus_perform_backup_{$method_key}", $backup_errors, $zip_file_path, $download);

		self::add_backup_in_progress_message(sprintf(__('Backup process for method "%s" completed...'), self::$backup_methods[$method_key]));
		}

	      }

	    }

	  }   



	}



      } catch (Exception $e) {

                

      }


	self::set_backup_errors($backup_errors);
	self::send_notification_mail($zip_file_path, $backup_errors, $settings, false);



      if ($download) {

	self::set_backup_in_progress(false);
	self::set_backup_in_progress_messages(array());

	exit;

      }
	$schedule_key = $settings['schedule'];
	$schedule = self::$backup_schedules[$schedule_key];

	if ($schedule && $schedule['interval'] > 0) {
	self::add_backup_in_progress_message(__('Adding the next scheduled backup to the queue...'));
	self::schedule_backup(time() + $schedule['interval']);

      }
$filezip=number_format($upload_file_size['size'] / 1048576, 2) ;
		if($filezip>100 && !isset($settings['manual'][1])){
			self::add_backup_in_progress_message(__('Backup size more than 100Mb so it could not save to temp folder !'));
			unlink($zip_file_path);	
		}

      self::add_backup_in_progress_message(__('Backup has been completed!'));
	 self::set_backup_in_progress(false);

    }
	



    /**

     * Adds file to zip

     *

     * Adds a file to the zip archive, and names it with the entry name parameter

     *

     * @param object $zip_archive

     * @param object $file

     * @param string $entry_name

     * @return bool

     */

    private static function add_file(&$zip_archive, $file, $entry_name){

      return $zip_archive->addFile($file, $entry_name);

    }



    /**

     * Sends a notification email

     *

     * Sends notification emails to the admin user's email address

     *

     * @param string $destination_path

     * @param object $backup_errors

     * @param array $settings

     * @param bool $on_demand, default=false

     * @return null

     */

    private static function send_notification_mail($destination_path, $backup_errors, $settings, $on_demand = false){



        include_once("../wp-includes/pluggable.php");

      $uploads = wp_upload_dir();
	$domain = sanitize_title_with_dashes(parse_url(home_url('/'), PHP_URL_HOST));
	$timestamp = date("m-d-Y-h-m-A");
	$backup_name = "backup-{$domain}-{$timestamp}.zip";
	$file=explode("temp/",$destination_path);

	$uploads_dir_path = dirname(dirname($uploads['path']));
	$uploads_path = path_join($uploads_dir_path, 'wp-backup-plus-backups');

      if(!is_dir($uploads_path)){

	mkdir($uploads_path);

      }

	  // if htaccess file comes in backkup file with any reason

	  $filename1= self::get_temp_backup_directory().'.htaccess';

		if (file_exists($filename1)) {

  			 unlink($filename1);

				} 

		$filename2= self::get_backup_directory().'.htaccess';

		if (file_exists($filename2)) {

			 unlink($filename2);

				} 
	$new_destination =  path_join($uploads_path, $filename);
	@unlink($new_destination);

      link($destination_path, $new_destination);
	$destination_url =  str_replace(ABSPATH, site_url().'/', $new_destination);
	$url=explode("uploads/",$destination_url);
	$stat = $url[0].'uploads/wp-backup-plus/temp/'.$file[1];
	$uploads = wp_upload_dir();
	$upload_file_size=stat($uploads['basedir'] ."/wp-backup-plus/temp/".$file[1]);
	$upload_file_size = number_format($upload_file_size['size'] / 1048576,2) ;
	//website name
	$site_name=get_site_url();
			
			
			if($upload_file_size<100){
		
	 $new_url='BackUpWordPress'.$sizeofemail.' has completed a backup of your site: <a href='.$site_name.'">'.$site_name.'</a><br><br><br>You can download the backup file by clicking the link below:<br><br><a href="'.$url[0].'plugins/wp-backup-plus/download_script.php?&url='.$file[1].'">download backup</a><br><br>kindly Regards,<br>The Happy BackUpWordpress Backup Emailing Robot';
			}
		else{
			
			$new_url='The backup you requested is complete for :<a href='.$site_name.'">'.$site_name.'</a><br><br>Unfortunately the backup was too large to attach the email.<br><br><br>Regards,The WP Backup+ Team';
			
			
		}

      $settings['diagnostics'] = "yes";

      if (!empty($backup_errors) && $backup_errors->get_error_code() && 'yes' === $settings['diagnostics']) {

	self::add_backup_in_progress_message(__('Sending diagnostic email because errors were encountered...'));

	$error_string = "";

	while(list($error_code, $errors)=each($backup_errors->errors)){

	  if(!empty($errors)){

	    $error_string .= __("\n\n$error_code:");

	    while(list($i, $error)=each($errors)){

	      $error_string .= __("\n$error");

	    }

	  }

	}



	wp_mail(self::DIAGNOSTICS_EMAIL, __('WP Backup Plus - Diagnostics - ' . site_url('/')), "Diagnostics Error Data: $error_string");

      }

	  $headers= "MIME-Version: 1.0\n" .

       "Content-Type: text/html; charset=\"" ;



      if ('yes' == $settings['notification'] && !empty($settings['email'])) {

	self::add_backup_in_progress_message(__('Sending notification of backup to configured email address...'));



        if($on_demand){

			

			

	  wp_mail($settings['email'], sprintf(__('Backup Performed on %s'), get_bloginfo('name')), sprintf(__("Your database and site files have been backed up using the method you requested.\n\nLink:".$new_url), get_bloginfo('name')),$headers);

	}

	else{

	  //	  echo "Sending mail to ".$settings['email']." ".sprintf(__("A scheduled backup has just been performed on your site, %s. Your database and site files have been backed up using the method you requested.\n\nLink: $destination_url"), get_bloginfo('name'));

	  wp_mail($settings['email'], sprintf(__('Backup Performed on %s'), get_bloginfo('name')), sprintf(__($new_url), get_bloginfo('name')),$headers);

	}

      }





    }







    /**

     * Short description

     *     

     * Long description

     *

     * @param string $unserialized_data

     * @param array $temp array containing remote home url and remote home path

     * @param string $site_url

     * @param int $level

     *

     * @return string unserialized data

     */

    private static function replace_unserialized_data($unserialized_data, $temp, $site_url, $level){



      if($level>2){

	return $unserialized_data;

      }



      if(!is_array($unserialized_data) && !is_object($unserialized_data)){

	if(is_string($unserialized_data)){

	  $unserialized_data = str_replace(array($temp['remote_home_url'], $temp['remote_home_path']), array($site_url, ABSPATH), $unserialized_data);

	}

      }

      elseif(is_object($unserialized_data)){

	$unserialized_data = (array)$unserialized_data;

	while(list($i,$value)=each($unserialized_data)){

	  if(!is_array($value) && !is_object($value)){

	    @$unserialized_data[$i] = str_replace(array($temp['remote_home_url'], $temp['remote_home_path']), array($site_url, ABSPATH), $value);

	  }

	  else{

	    $unserialized_data[$i] = self::replace_unserialized_data($value, $temp, $site_url, $level+1);

	  }

	}

	$unserialized_data = (object)$unserialized_data;    

      }

      else{

	while(list($i,$value)=each($unserialized_data)){

	  if(!is_array($value) && !is_object($value)){

	    $unserialized_data[$i] = str_replace(array($temp['remote_home_url'], $temp['remote_home_url']), array($site_url, ABSPATH), $value);

	  }

	  else{

	    $unserialized_data[$i] = self::replace_unserialized_data($value, $temp, $site_url, $level+1);

	  }

	}

      }

      return $unserialized_data;

    }





    /**

     * Verifies a backup

     *

     * Verifies a backup by checking the zip file is ok, and check it contains all the required files

     *

     * @param string $backup_file_name

     * @param object $errors

     * @return $errors

     */

    private static function verify_backup($backup_file_name, &$errors){



      $zip_archive = new ZipArchive;  

      $error_codes_map = array(19=>'File is not a zip file', ZIPARCHIVE::ER_INCONS=>"Zip archive inconsistent", ZIPARCHIVE::ER_INVAL=>"Invalid argument", ZIPARCHIVE::ER_MEMORY=>"Malloc failure", ZIPARCHIVE::ER_NOENT=>"No such file", ZIPARCHIVE::ER_NOZIP=>"Not a zip archive", ZIPARCHIVE::ER_OPEN=>"Can't open file", ZIPARCHIVE::ER_READ=>"Read error", ZIPARCHIVE::ER_SEEK=>"Seek error");



      // Check we have a valid backup file
	$result = $zip_archive->open($backup_file_name);



      if($result !=true || isset($error_codes_map[$result])){
	$error = "Unknown error opening zip file";
	if(isset($error_codes_map[$result])){

	  $error = $error_codes_map[$result];
	if(is_object($errors)){

	    $errors->add('wp-backup-plus-restore-zip', __($error));

	  }

	  else{

	    print_r($errors);

	  }



	}

      }

      else{



	// Check for 'files' directory

	$result = $zip_archive->statName('files');

	if(!$result){

	  $error = "Unknown error when checking files directory";

	  if(isset($error_codes_map[$result])){

	    $error = $error_codes_map;

	    $errors->add('wp-backup-plus-restore-zip', __($error));

	  }

	}

	

	// Check for 'tables.sql'

	$result = $zip_archive->statName('tables.sql');

	if(!$result){

	  $error = "Unknown error when checking files directory";

	  if(isset($error_codes_map[$result])){

	    $error = $error_codes_map;

	    $errors->add('wp-backup-plus-restore-zip', __($error));

	  }

	}

	else{

	  $fp = $zip_archive->getStream('tables.sql');

	  while (!feof($fp)) {

	    $contents .= fread($fp, 2);

	  }

	  fclose($fp);

	  $table_names = explode("\n", $contents);

	  while(list($i, $table_name)=each($table_names)){

	    $result = $zip_archive->statName(trim($table_name));

	    if(!$result){

	      $error = "Unknown error when $table_name";

	      if(isset($error_codes_map[$result])){

		$error = $error_codes_map;

		$errors->add('wp-backup-plus-restore-zip', __($error));

	      }

	    }

	  }

	}



      }



    }





    /**

     * Get file size

     *

     * Gets the size of a file

     *

     * @param string $file

     * @param int $attempt, default = 0

     * @param int $max_attempt, default = 1

     * @return int size of the file

     */

    private static function get_filesize($file, $attempts=0, $max_attempts=1){

      clearstatcache();

      @$filesize = filesize($file);      

      if(!$filesize){

        if($attempts==$max_attempts){

	  $filesize = false;

	}

	else{

	  sleep(3);

	  $filesize = self::get_filesize($file, $attempts+1);

	}

      }

      return $filesize;

    }



    /**

     * Reads a file

     *

     * Reads a file but checks if it exists first

     *

     * @param string $file

     * @param int $attempt, default = 0

     * @return null

     */

    private static function readfile_safe($file, $attempts=0){

      if(file_exists($file)){

        readfile($file);

      }

      else{

        if($attempts<4){

	  sleep(3);

	  self::readfile_safe($file, $attempts+1);

	}

      }

    }



    /**

     * Sorts two parameters

     *

     * Callback function to sort array by size

     *

     * @param number $a

     * @param number $b

     * @return bool ture if $a is greater than $b

     */

    public static function sort_by_size($a,$b) {

      return $a['size']>$b['size'];

    }



    /**

     * Performs a manual backup

     *

     * Downloads the backup zip file to the user

     *

     * @param object $backup_errors

     * @param strin $zip_file_path

     * @return null

     */

    public static function perform_backup_manual(&$backup_errors, $zip_file_path, $download) {



      $domain = sanitize_title_with_dashes(parse_url(home_url('/'), PHP_URL_HOST));

      $timestamp = date("m-d-Y-h-m A");

      $filename = "backup-{$domain}-{$timestamp}.zip";

	 // $filename = self::get_backup_filename();

	 

      $filesize = self::get_filesize($zip_file_path, 0, 10);



      if($filesize==0){

        echo "Error - zip file is 0 length in size";

      }

      else{

	header('Content-Type: application/octet-stream');

	header('Content-Length: ' . $filesize . ';');

	header("Content-Transfer-Encoding: Binary");

	header('Content-Disposition: attachment; filename="' . $filename . '"');



	$fp = @fopen($zip_file_path, "rb");

        if ($fp) {

	  while(!feof($fp)) {

	    print(fread($fp, 1024*8));

	    flush(); // this is essential for large downloads

	    if (connection_status()!=0) {

	      @fclose($file);

	      die();

	    }

	  }

	  @fclose($file);

	}



	sleep(3);



      }



    }



    private static function flush_now($s){

      echo str_pad('',1024); 

      ob_start();

      echo str_pad("$s<br>\n",8);

      ob_flush();

      flush();

      ob_end_clean();

    }



    /**

     * Get files by size

     *

     * Gets files in a directory and sorts them by size

     *

     * @param string $directory

     * @param array $files

     * @param bool $recursive

     * @param int $total_size

     * @param array $additional_directories default=null

     * @param bool $from_ajax default=false

     * @param array $excluded_paths default=null

     * @return null

     */

    public static function get_files_by_size($directory, &$files, $recursive, &$total_size, $additional_directories=null, $from_ajax=false, $excluded_paths=null){



      if(empty($excluded_paths)){

	$excluded_paths = self::get_excluded_paths();





	if($from_ajax){

			$excluded_paths = array_diff($excluded_paths, array('files/wp-content/', 'files/wp-admin/', 'files/wp-includes'));

			 $custom_excludes = explode("\n", $_POST['wp-backup-plus']['additional-exclusions_saved']);

	

	  if(!empty($custom_excludes)){

	    while(list($i,$additional_directory)=each($custom_excludes)){

	      if(empty($custom_excludes[$i])){

		unset($custom_excludes[$i]);


	      }

	      else{

		$custom_excludes[$i] = trailingslashit('files'.$custom_excludes[$i]);

	      }

	    }

	    $custom_excludes = array_values($custom_excludes);

	    $excluded_paths = array_diff($excluded_paths, $custom_excludes);

	  }

	  if(!empty($additional_directories)){

	    while(list($i,$additional_directory)=each($additional_directories)){

	      if(empty($additional_directories[$i])){

		unset($additional_directories[$i]);

	      }

	      else{

		$additional_directories[$i] = trailingslashit('files'.$additional_directories[$i]);

	      }

	    }

	    $additional_directories = array_values($additional_directories);

	  }

	}



	if(!empty($additional_directories)){

	  $excluded_paths = array_merge($excluded_paths, $additional_directories);

	}

      }



      //      print_r($excluded_paths);

      //               die();

      if(is_dir($directory) && !self::is_excluded_directory($directory, $excluded_paths)){

	$directory_iterator = new DirectoryIterator($directory);

	

	

	foreach ($directory_iterator as $directory_item) {

	 $directory_item->getPathname();

	 $dir='uploads\wp-backup-plus';

	

		

	

	  if (!$directory_item->isDot() && !self::is_excluded_directory($directory_item->getPathname(), $excluded_paths)){

	    if(is_file($directory_item->getPathname())){

			 $directory_item->getPathname();

	      $size = $directory_item->getSize();

	      $files[] = array('name'=>$directory_item->getPathname(), 'size'=>$size);

		

		  

	       $total_size+=$size;

	    }			     

	    elseif($recursive){

	      self::get_files_by_size($directory_item->getPathname(), $files, $recursive, $total_size, $additional_directories, false, $excluded_paths);

	    }

	  }

	}

      }

    }



    /// CALLBACKS



    /**

     * Short description

     *     

     * Long description

     *

     * @return null

     */

    public static function add_interface_items() {

      self::$admin_page_hooks[] = $main = add_menu_page(__('WP Backup Lite'), __('WP Backup Lite'), 'manage_options', self::SETTINGS_SLUG_MENU);
      self::$admin_page_hooks[] = $settings = add_submenu_page(self::SETTINGS_SLUG_MENU, __('WP Backup Lite - Settings'), __('Settings'), 'manage_options', self::SETTINGS_SLUG_SUBMENU_SETTINGS, array(__CLASS__, 'display_settings_page'));
	  self::$admin_page_hooks[] = $restore = add_submenu_page(self::SETTINGS_SLUG_MENU, __(''), __(''), 'manage_options', self::SETTINGS_SLUG_SUBMENU_RESTORE, array(__CLASS__, 'display_settings_page'));
	  self::$admin_page_hooks[] = $restore1 = add_submenu_page(self::SETTINGS_SLUG_MENU, __('WP Backup Lite - Backup &amp; Restore'), __('Backup Upload &amp; Restore'), 'manage_options', self::SETTINGS_SLUG_SUBMENU_UPLOAD, array(__CLASS__, 'display_settings_page'));
 	  add_action("load-{$settings}", array(__CLASS__, 'process_settings_save'));
      add_action("load-{$restore1}", array(__CLASS__, 'process_backup_and_restore'));
      add_action("load-{$restore}", array(__CLASS__, 'process_backup_and_restore'));
 
		//remove_submenu_page(self::SETTINGS_SLUG_MENU, self::SETTINGS_SLUG_SUBMENU_UPLOAD );

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param array $plugin_meta

     * @param string $plugin_file

     * @param array $plugin_data

     * @param bool $status

     *

     * @return array plugin meta

     */

    public static function add_plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status) {

      if (plugin_basename(__FILE__) === $plugin_file) {

	$plugin_meta = array_slice($plugin_meta, 0, count($plugin_meta) - 1);



	$plugin_meta[] = sprintf('<a target="_blank" href="%s">%s</a>', 'http://wpbackupplus.com/affiliates2/', __('Become an affiliate'));

      }



      return $plugin_meta;


    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param array $links

     *

     * @return array

     */

    public static function add_settings_link($links) {

      $settings_link = sprintf('<a href="%s">%s</a>', add_query_arg(array('page' => self::SETTINGS_SLUG_SUBMENU_SETTINGS), admin_url('admin.php')), __('Settings'));



      return array('settings' => $settings_link) + $links;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param array $mimes

     *

     * @return array mimes

     */

    public static function add_sql_as_accepted_mime_type($mimes) {

      $mimes['sql'] = 'text/plain';



      return $mimes;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param array $mimes

     *

     * @return array mimes

     */
	 //delete amazon backup
	 
public static function amazon_backup(){
	
	require_once ('modules/amazon/wp-backup-plus-provider-amazon.php');
	  //self::amazon_backup();
	 if(isset($_GET['step']) && ($_GET['step']!="")){
	 $filename=$_GET['step'];
	
	 $cls_a = new WP_Backup_Plus_Provider_Amazon();
		$cls_a->amazon_backup($filename);
	 }
}

//dropbox backup delete
	 public static function dropbox_backup(){
	 require_once ('modules/dropbox/wp-backup-plus-provider-dropbox.php');
	  //self::amazon_backup();
	 if(isset($_GET['step']) && ($_GET['step']!="")){
		 $filename=$_GET['step'];
	$cls_a = new WP_Backup_Plus_Provider_Dropbox();
	$cls_a->dropbox_backup($filename);
	 }
}


 public static function server_backup(){
	
	   require_once ('modules/server/wp-backup-plus-provider-server.php');
	  //self::amazon_backup();
	 if(isset($_GET['step']) && ($_GET['step']!="")){
		 $filename=$_GET['step'];
	$cls_a = new WP_Backup_Plus_Provider_Server();
	$cls_a->server_backup($filename);
	 }
}

	 
	 
	 

    public static function ajax_output_progress() {

	$excluded_paths = self::get_excluded_paths();
	$in_progress = self::get_backup_in_progress();
	$messages = self::get_backup_in_progress_messages();
	$fsize=file_get_contents('log.txt');
	$total_directory_size=file_get_contents('size.txt');
	if($fsize>$total_directory_size){
	$fsize=$total_directory_size;	
	}
	if($fsize==""){
		$fsize=0;	
	}
	$total_directory_size=$fsize.'MB /'.$total_directory_size.' MB (Uncompressed  Size)';
		
      echo json_encode(compact('in_progress', 'messages','total_directory_size'));
	  

      exit;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $line

     * @param string $log

     *

     * @return null

     */

    public static function add_report_line($line, $log){

      if($p = fopen($log, 'a')){

	fwrite($p, $line."\n");

	fclose($p);

      }

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @return null

	 

     */

	 

    function get_directory_size_full($directory, &$total_size){

      if(is_dir($directory)){

	$directory_iterator = new DirectoryIterator($directory);

	$excluded_paths = self::get_excluded_paths();

	foreach ($directory_iterator as $directory_item) {

	  //	  if (!self::is_excluded_directory($directory_item->getPathname(),$excluded_paths) && !$directory_item->isDot()){

	  if (!$directory_item->isDot()){

	    if(is_file($directory_item->getPathname())){

	      $size = $directory_item->getSize();

	      $files[] = array('name'=>$directory_item->getPathname(), 'size'=>$size);

	      $total_size+=$size;

	    }			     

	    else{

	      self::get_directory_size_full($directory_item->getPathname(), $total_size);

	    }

	  }

	}

      }

    }

	

	

	

	 public static function get_included_paths() {



      $settings = self::get_settings();

      $freeform_paths = array_filter(explode("\n", $settings['additional-inclusions']));

      if(!isset($settings['include-directories-named']) || !is_array($settings['include-directories-named'])){

	$settings['include-directories-named'] = array();

      }

      return self::format_paths(array_merge($settings['include-directories-named'], $freeform_paths));



    }

	

	 public static function format_paths($paths) {



      $settings = self::get_settings();



      $processed_paths = array();



      foreach ($paths as $path) {

	$path = trim($path,"\n");

	if(!empty($path)){

   	   $processed_paths[] = 'files/' . ltrim($path, '/');

	}

      }



      return $processed_paths;



    }

	

	

//for directory added

	   private static function is_always_included($dir){

		  

      $included_paths = self::get_included_paths();

	 

      while(list($i, $included_path)=each($included_paths)){

	if(!empty($included_path) && trim($included_path)!="files/"){

	  $included_path = is_dir(str_replace('files/', ABSPATH, $included_path))?trim(rtrim($included_path, '/')).'/':trim(rtrim($included_path, '/'));

	  if(strpos(strtolower($dir), strtolower(trim(str_replace('files/', ABSPATH, $included_path))))!==false){

	    return true;

	  }

	}

      }

      return false;

    }

	 

 private static function format_bytes($a_bytes)

    {

      if ($a_bytes < 1024) {

        return $a_bytes .' B';

      } elseif ($a_bytes < 1048576) {

        return round($a_bytes / 1024, 2) .' KB';

      } elseif ($a_bytes < 1073741824) {

        return round($a_bytes / 1048576, 2) . ' MB';

      } elseif ($a_bytes < 1099511627776) {

        return round($a_bytes / 1073741824, 2) . ' GB';

      } elseif ($a_bytes < 1125899906842624) {

        return round($a_bytes / 1099511627776, 2) .' TB';

      } elseif ($a_bytes < 1152921504606846976) {

        return round($a_bytes / 1125899906842624, 2) .' PB';

      } elseif ($a_bytes < 1180591620717411303424) {

        return round($a_bytes / 1152921504606846976, 2) .' EB';

      } elseif ($a_bytes < 1208925819614629174706176) {

        return round($a_bytes / 1180591620717411303424, 2) .' ZB';

      } else {

        return round($a_bytes / 1208925819614629174706176, 2) .' YB';

      }

    }

	

	 public static function ajax_output_get_directories(){

		$excluded_paths = self::get_excluded_paths();

	 if(empty($_GET['directory_name'])){

	$_GET['directory_name'] = ABSPATH;

      }



     $directory = $_GET['directory_name'];

	

      $directories = array();

      $files = array();



      $parent_is_excluded = $_GET['parent_directory_is_excluded'] == 'true' || self::is_excluded_directory($directory, $excluded_paths);



      if(is_dir($directory)){

	$directory_iterator = new DirectoryIterator($directory);

	

	foreach ($directory_iterator as $directory_item) {

	  //	    if(is_dir($directory_item->getPathname())){

	  //	      echo $directory_item->getPathname() ."\n";

	  //	    }

	  $size = 0;

	 

	  if (!$directory_item->isDot()){

	    if(!is_dir($directory_item->getPathname())){

		 $size = $directory_item->getSize();

	    }

	    else{

			

	      self::get_directory_size_full($directory_item->getPathname(), $size);

	    }



	    $directory_name = $directory_item->getPathname();



	   if(self::is_always_included(strtolower($directory_name))==true){

	      $is_excluded = false;

	    }

	    elseif(self::is_excluded_directory(strtolower($directory_name), $excluded_paths)){

	      //echo "$directory_name %% ";

	      $is_excluded = true;

	    }

	    elseif($parent_is_excluded){

	      $is_excluded = true;

	    }

	    else{

	      //	      echo "!!! ";

	      $is_excluded = false;

	    }



	   $item = array('path'=>$directory_name, 'short_path'=>preg_replace("~\\\\+([\"\'\\x00\\\\])~", "$1", $directory_name),  'name'=>basename($directory_name), 'type'=>is_dir($directory_name)?'directory':'file', 'excluded'=>$is_excluded , 'size'=>self::format_bytes($size));

	    if($is_excluded && is_dir($directory_name)){

	      //     print_r($item);

	    }

	    if(is_dir($directory_name)){

	      $directories[] = $item;

	    }

	    else{

	      $files[] = $item;

	    }

	  }

	  //	  }

	}

      }

	

      echo json_encode(array_merge($directories, $files));

      die();

    }

	 

	 

	 

	 

    public static function ajax_output_get_backup_size(){

	

		$upload_dir = wp_upload_dir();

		$dir= $upload_dir['basedir'];
		$path=$dir."\wp-backup-plus/temp/"; 
		$ar=self::getDirectorySize($path); 
		$size=self::sizeFormat($ar['size']);
		$additional_directories = array_merge(isset($_POST['wp-backup-plus']['exclude-directories-named'])?$_POST['wp-backup-plus']['exclude-directories-named']:array(), explode("\n",$_POST['wp-backup-plus']['additional-exclusions']));

      $backup_file_size = 0;

      $files = array();

      self::get_files_by_size(ABSPATH, $files, true, $backup_file_size, $additional_directories, true);

	

      if(round($backup_file_size/1048576)>0){
		$total_size=round($backup_file_size/1048576);
		if($size>$total_size){
			echo  $total_size;
			}

	 	 else{
			echo $total_size;  
		}

	 }

	  

      else{

		

		 $total_size=(number_format($backup_file_size/1048576, 2));

		  if($size>$total_size){

			echo $total_size;
			}

	  else{
		echo $total_size;  
		}
	}

      die();

    }

	

	

	private static function getDirectorySize($path){ 

  		$totalsize = 0; 
		$totalcount = 0; 
		$dircount = 0; 

  if(is_dir($path)){
	if ($handle = opendir ($path)){ 

    while (false !== ($file = readdir($handle))){ 
	$nextpath = $path . '/' . $file; 
	if ($file != '.' && $file != '..' && !is_link ($nextpath)) { 

        if (is_dir ($nextpath)){ 

         $dircount++; 
		$result = self::getDirectorySize($nextpath); 
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

  }

  return $total; 

} 



private static function sizeFormat($size) { 

if($size<1024) { 

        return $size; 

    } 

    else if($size<(1024*1024)) { 

        $size=round($size/1024,1); 

        return $size; 

    } 

    else if($size<(1024*1024*1024)) { 

        $size=round($size/(1024*1024),1); 
		return $size; } 

    else { 

        $size=round($size/(1024*1024*1024),1); 
		return $size; 

    } 



}  

 



	

	



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $desired_directory

     *

     * @return string full name of  meta file including path if successful, otherwise false

     */

    private static function get_meta_file($desired_directory){

      if(file_exists(path_join($desired_directory,'meta.txt'))){

	return path_join($desired_directory,'meta.txt');

      }

      return false;

    }



    /**

     * Add meta file

     *

     * Get site url and site path and add it to the zip archive, using the name 'meta.txt'

     *

     * @param object $zip

     * @return null

     */

    // CHANGE

    private static function add_meta_file($zip){

      $zip->addFromString('meta.txt', serialize(array('remote_home_url'=>get_option('siteurl'), 'remote_home_path'=>ABSPATH)));

    }



    /**

     * Get remote home url and path

     *

     * Get the home url and path of the site that was backed up

     *

     * @param string $desired_directory

     * @param object $restore_errors

     * @return array array containing the home url and home path of the site that was backed up

     */

    private static function get_remote_home_url_path($desired_directory, &$restore_errors){

      $meta_file = self::get_meta_file($desired_directory);

      if($meta_file){

	$temp = unserialize(file_get_contents($meta_file));

	$remote_home_url = trim(rtrim($temp['remote_home_url'], '/'));

	$remote_home_path = trim(rtrim($temp['remote_home_path'], '/'));

      }

      else{

	$database_files = self::get_database_files($desired_directory, $restore_errors);

	while(list($table_name, $database_file)=each($database_files)){

	  $temp = explode("_", $table_name);

	  if(isset($temp[1]) && trim(strtolower($temp[1]))=='options.sql'){

	    $options_text = file_get_contents($database_file);

	    preg_match("/\'siteurl\',\s+\'(.*?)\'/uis", $options_text, $matches);

	    $remote_home_url = $matches[1];

	    preg_match("/(\/home\/rhino\/public\_html\/WPBACKUPPLUS\.COM)/uis", $options_text, $matches);

	    $remote_home_path = $matches[1];

	  }

	}   

      }

      return array('remote_home_url'=>$remote_home_url, 'remote_home_path'=>$remote_home_path);

    }



    /**

     * Handle ajax calls during restore process

     *

     * Handles ajax calls during restore process according to the 'action' and 'step' url parameters

     *

     * @return null

     */

    public static function ajax_output_restore() {
	
	

		

      set_time_limit(0);

      $data = self::get_request_data();

      if($data['wp-backup-plus']['backup-action']=="upload"){

	$method = '';

      }

      else{

	if (!empty($data['wp-backup-plus-download-or-restore-backup-nonce']) && wp_verify_nonce($data['wp-backup-plus-download-or-restore-backup-nonce'], 'wp-backup-plus-download-or-restore-backup')) {

	  $item = maybe_unserialize($data['wp-backup-plus']['backup-data']);

	  $method = $item->WPBP_METHOD;

	}

      }



      switch($_GET['action']){

      case "wp_backup_plus_restore":

	switch($_GET['step']){

	case 'handle_zip_upload':   

	  if(empty($method)){

	    $desired_directory = $_POST['upload_directory'];

	    $desired_location = $desired_directory . 'backup.zip';

	    $current_location = false;

	    $restore_errors = new WP_Error;
		
		$restore_errors_refernce = &$restore_errors;
	    
		self::handle_zip_upload($restore_errors_refernce, $current_location);

	    $errors = $restore_errors->get_error_messages("wp-backup-plus-restore-zip");

	    if(!empty($errors)){
		 echo "Please check upload file size,'Upload_max_filesize' and  'post_max_size'  in php.ini must be greater than the  file which you are trying to upload or upload the backup through ftp or file manager";

	    }

	    elseif(is_file($current_location)){

	      self::move_zip($current_location, $desired_location);

	    }

	    else{

	      echo "Could not upload file";

	    }

	  }

	  die();

	  break;

	case 'get_backup_directory':

	  $desired_directory = trailingslashit(path_join(self::get_temp_backup_directory(), time()));

          wp_mkdir_p($desired_directory);

	  if(is_dir($desired_directory)){

	    echo json_encode($desired_directory);

	  }

	  else{

	    echo json_encode('');

	  }

          die();

	  break;

	case 'restore_backup_files':

	  $restore_errors = new WP_Error;

	  $desired_directory = $_GET['backup_directory'];

	  if(!empty($method)){

            // This will get the backup file from dropbox, amazon etc

	    $desired_location = apply_filters("wp_backup_plus_backup_file_{$item->WPBP_METHOD}", false, $item);

	  }

	  else{

	    $desired_location = $desired_directory . 'backup.zip';

	  }

	  self::restore_files_from_zip($desired_location, $desired_directory, $restore_errors);

	  $errors = $restore_errors->get_error_messages("wp-backup-plus-restore-zip"); 

	  if(empty($errors)){

	    echo json_encode('');

	  }

	  else{

	    echo json_encode($errors);

	  }

	  die();

	  break;

	case 'update_database_options':

          $desired_directory = $_GET['backup_directory'];

	  // The following  will deactivate the plugin so we must make sure 

	  // they are called in the very last ajax call         

          $desired_directory = $_GET['backup_directory'];

	  $restore_errors = new WP_Error;

	  $database_files = self::get_database_files($desired_directory, $restore_errors);

	  $errors = $restore_errors->get_error_messages("wp-backup-plus-restore-zip");

	  $remote_home_url = isset($_POST['remote_home_url'])?$_POST['remote_home_url']:null;

	  $remote_home_path = isset($_POST['remote_home_path'])?$_POST['remote_home_path']:null;

	  if(!empty($errors)){

   	    echo implode("\n", $errors);

	  }

	  else{

	    while(list($table_name, $database_file)=each($database_files)){

	      $temp = explode("_", $table_name);

	      if(isset($temp[1]) && in_array($temp[1], array('options.sql', 'users.sql'))){

		self::restore_database_table($database_file, $restore_errors, $desired_directory, $remote_home_url, $remote_home_path);

	      }

	    }

	    self::update_database_options(site_url('/'), home_url('/'), $restore_errors);

	    if (!($update_wp_config = self::update_config($desired_directory . 'files/'))) {

	      $restore_errors->add("wp-backup-plus-restore-zip", __('Config file could not be updated'));

	    } 

	    echo implode("\n", $restore_errors->get_error_messages("wp-backup-plus-restore-zip"));
		
		
	echo json_encode(site_url().'/wp-admin/?var=1');  

	  }

	  include_once(path_join(ABSPATH, "wp-admin/includes/file.php"));

	  include_once(path_join(ABSPATH, "wp-admin/includes/misc.php"));

	  include_once(path_join(ABSPATH, "wp-includes/rewrite.php"));

	  $wp_rewrite = new WP_Rewrite();

	  $wp_rewrite->flush_rules();   

	  die();

	  break;

        case 'restore_database_table':

	  $restore_errors = new WP_Error;

          $desired_directory = $_GET['backup_directory'];

	  $database_file_name = $_GET['database_file_name'];

	  $temp = explode("_", strtolower(basename($database_file_name)));

	  $remote_home_url = isset($_POST['remote_home_url'])?$_POST['remote_home_url']:null;

	  $remote_home_path = isset($_POST['remote_home_path'])?$_POST['remote_home_path']:null;

	  if(!isset($temp[1]) || ($temp[1]!='options.sql' && $temp[1]!='users.sql')){

	    echo json_encode(self::restore_database_table($database_file_name, $restore_errors, $desired_directory, $remote_home_url, $remote_home_path));



	    $errors = $restore_errors->get_error_messages("wp-backup-plus-restore-zip");

	    if(!empty($errors)){

	      echo implode("\n", $errors);

	    }

	  }

	  else{

	    echo json_encode('');

	  }

	  die();

	  break;

        case 'get_database_files':

          $desired_directory = $_GET['backup_directory'];

	  $restore_errors = new WP_Error;

	  $database_files = self::get_database_files($desired_directory, $restore_errors);

	  $errors = $restore_errors->get_error_messages("wp-backup-plus-restore-zip");

	  if(!empty($errors)){

	    echo implode("\n", $errors);

	  }

	  else{

	    while(list($table_name, $database_file)=each($database_files)){

	      $temp = explode("_", $table_name);

	      if($table_name=='tables.sql' || (isset($temp[1]) && in_array(strtolower($temp[1]), array('options.sql', 'users.sql')))){

		unset($database_files[$table_name]);

	      }

	    }

	    echo json_encode($database_files);

	  }

	  die();

	  break;

        case 'get_remote_home_url_path':

          $desired_directory = $_GET['backup_directory'];

	  $restore_errors = new WP_Error;

	  echo json_encode(self::get_remote_home_url_path($desired_directory, $restore_errors));

          die();

          break;

	case 'restore_database':

	  $restore_errors = new WP_Error;

          $desired_directory = $_POST['backup_directory'];

	  $remote_home_url = isset($_POST['remote_home_url'])?$_POST['remote_home_url']:null;

	  $remote_home_path = isset($_POST['remote_home_path'])?$_POST['remote_home_path']:null;

	  self::restore_database($desired_directory, site_url('/'), home_url('/'), $restore_errors);

	  $errors = $restore_errors->get_error_messages("wp-backup-plus-restore-zip");

	  if(!empty($errors)){

	    echo implode("\n", $restore_errors->get_error_messages("wp-backup-plus-restore-zip"));

	  }

	  die();

	  break;

	}

      }
	
    }

   

    public static function ajax_output_download() {

		

      //       $error_report = "Errors:\n";

      set_time_limit(0);

      switch($_GET['action']){

      case "wp_backup_plus_download":

	switch($_GET['step']){

	case 'get_download_link':

	  $domain = sanitize_title_with_dashes(parse_url(home_url('/'), PHP_URL_HOST));

	  $timestamp = date("m-d-Y-h-m A");

	 // $filename = "backup-{$domain}-{$timestamp}.zip";

	 $filename = self::get_backup_filename();

	  $filesize = self::get_filesize($zip_file_path, 0, 10);

	  $uploads = wp_upload_dir();

	  $uploads_dir_path = dirname(dirname($uploads['path']));

	  $uploads_path = path_join($uploads_dir_path, 'wp-backup-plus-backups');

	  if(!is_dir($uploads_path)){

	    mkdir($uploads_path);

	  }

	  $new_destination =  path_join($uploads_path, $filename);

	  @unlink($new_destination);

	  link($_GET['destination_path'], $new_destination);

	  echo json_encode(str_replace(ABSPATH, site_url().'/', $new_destination));

	  die();

	  break;

	case 'excluded_directories':

	  echo json_encode(self::get_excluded_paths());

	  die();

	  break;

	case 'check_zip_size':

	  $destination_path = $_GET['destination_path'];

	  //	  print_r($_GET);

	  echo json_encode(self::get_filesize($_GET['destination_path']));

	  die();

	  break;

	case 'download_zip':

	  $destination_path = $_GET['destination_path'];

	

	  

	  $text=file_get_contents($destination_path.'.log');

	  $zip_archive = new ZipArchive;  

          $zip_archive->open($destination_path);

	  $zip_archive->addFromString('report.txt',$text);

	  self::add_meta_file($zip_archive);

          $zip_archive->close();

	  $backup_errors = null;

	  self::perform_backup_manual($backup_errors, $destination_path, true);

          $settings = self::get_settings();

	  self::send_notification_mail($destination_path, null, $settings, true);

	  die();

	  break;

        case 'get_uploads_directories':



	  $excluded_paths = self::get_excluded_paths();



          if(in_array('files/wp-content/', array_map('strtolower', $excluded_paths))){

	    echo json_encode(array());

	  }

	  else{
$excluded_paths[] = strtolower(ABSPATH).'wp-content/uploads/wp-backup-plus';
			$excluded_paths[] = strtolower(ABSPATH).'wp-content\uploads\wp-backup-plus';
	    $backup_errors = new WP_Error;

	    $destination_path = $_GET['destination_path'];

	    $zip_archive = new ZipArchive;  



	    if (true !== $zip_archive->open($destination_path)) {

	      $backup_errors->add('wp-backup-plus-backup-files-to-zip', __('Could not backup directory' .$directory . ' to the zip archive.'));

	    }

	    else{

	      // Backup the uploads directory 'root' directory files

	      self::backup_directory_to_zip($zip_archive, $backup_errors, $destination_path, $excluded_paths, ABSPATH.'wp-content/uploads', false);

              // Get array containing all directories in the plugins directory  with full path to each directory

   	      // and store it in $directories

	      $directories = array();    


              self::get_directory_info($excluded_paths, ABSPATH.'wp-content/uploads/', $directories, false);    // $directories is passed by ref

              self::add_report_line(__("Got ".count($directories)), $destination_path.".log");

              echo json_encode($directories);

	    }



	  }

	  die();

	  break;



        case 'get_themes_directories':



	  $excluded_paths = self::get_excluded_paths();



          if(in_array('files/wp-content/', array_map('strtolower', $excluded_paths))){

	    echo json_encode(array());

	  }

	  else{



	    $backup_errors = new WP_Error;

	    $destination_path = $_GET['destination_path'];

	    $zip_archive = new ZipArchive;  



	    if (true !== $zip_archive->open($destination_path)) {

	      $backup_errors->add('wp-backup-plus-backup-files-to-zip', __('Could not backup directory' .$directory . ' to the zip archive.'));

	    }

	    else{

	      // Backup the plugin directory 'root' directory files

	      self::backup_directory_to_zip($zip_archive, $backup_errors, $destination_path, $excluded_paths, ABSPATH.'wp-content/themes', false);

              // Get array containing all directories in the plugins directory  with full path to each directory

   	      // and store it in $directories

	      $directories = array();

              self::get_directory_info($excluded_paths, ABSPATH.'wp-content/themes/', $directories, false);    // $directories is passed by ref

	      //	      $backup_errors->add('wp-backup-plus-backup-files-to-zip', __("Got ".count($directories)." themes directories"));   

              self::add_report_line(__("Got ".count($directories)." theme directories"), $destination_path.".log");

              echo json_encode($directories);

	    }



	  }

	  die();

	  break;



        case 'get_plugins_directories':



	  $excluded_paths = self::get_excluded_paths();



          if(in_array('files/wp-content/', array_map('strtolower',$excluded_paths))){

	    echo json_encode(array());

	  }

	  else{



	    $backup_errors = new WP_Error;

	    $destination_path = $_GET['destination_path'];

	    $zip_archive = new ZipArchive;  



	    if (true !== $zip_archive->open($destination_path)) {

	      $backup_errors->add('wp-backup-plus-backup-files-to-zip', __('Could not backup directory' .$directory . ' to the zip archive.'));

	    }

	    else{



	      // Backup the plugin directory 'root' directory files

	      self::backup_directory_to_zip($zip_archive, $backup_errors, $destination_path, $excluded_paths, ABSPATH.'wp-content/plugins', false);



              // Get array containing all directories in the plugins directory  with full path to each directory

   	      // and store it in $directories

	      $directories = array();



              self::get_directory_info($excluded_paths, ABSPATH.'wp-content/plugins/', $directories, false);    // $directories is passed by ref



	      //	      $backup_errors->add('wp-backup-plus-backup-files-to-zip', __("Got ".count($directories)." plugin directories"));

	      // $backup_errors->add('wp-backup-plus-backup-files-to-zip', __(implode("\n", $directories)));

              self::add_report_line(__("Got ".count($directories)." plugin directories"), $destination_path.".log");



              echo json_encode($directories);



	    }



	  }

	  die();

	  break;

        case 'plugins_directory':



	  $backup_errors = new WP_Error;

	  $destination_path = $_GET['destination_path'];

	  $zip_archive = new ZipArchive;  






	  if (true !== $zip_archive->open($destination_path)) {

	    $backup_errors->add('wp-backup-plus-backup-files-to-zip', __('Could not backup directory' .$directory . ' to the zip archive.'));

	  }

	  else{



	    $excluded_paths = self::get_excluded_paths();



	    $directories = array();



            // Get array containing all directories with full path to each directory

	    // and store it in $directories

            self::get_directory_info($excluded_paths, ABSPATH.'wp-content/plugins/', $directories, true);    // $directories is passed by ref





            // Now we backup each of the directories except those in 'excluded paths

	    while(list($i,$directory)=each($directories)){

	      self::backup_directory_to_zip($zip_archive, $backup_errors, $destination_path, $excluded_paths, $directory, false);

	      unset($directories[$i]);

	    }



	    echo json_encode(array_values($directories));

   	    $zip_archive->close();



	  }



	  die();

	  break;

	case 'directory':

	  $backup_errors = new WP_Error;

	  $directory = $_GET['directory'];

	  $destination_path = $_GET['destination_path'];

	  $zip_archive = new ZipArchive;  

	  if (true !== $zip_archive->open($destination_path)) {

	    $backup_errors->add('wp-backup-plus-backup-files-to-zip', __('Could not backup directory' .$directory . ' to the zip archive.'));

	  }

	  else{

            // Backup directory

	    $excluded_paths = self::get_excluded_paths();

	    self::backup_directory_to_zip($zip_archive, $backup_errors, $destination_path, $excluded_paths, $directory, false);

	    $zip_archive->close();

	  }

	  echo json_encode($backup_errors);

	  die();

	  break;

	case 'directory_info':

	  $backup_errors = new WP_Error;

	  $destination_path = $_GET['destination_path'];

	  $zip_archive = new ZipArchive;  

	  if (true !== $zip_archive->open($destination_path)) {

	    $backup_errors->add('wp-backup-plus-backup-files-to-zip', __('Could not backup directories to the zip archive.'));

	  }

	  else{



	    $excluded_paths = self::get_excluded_paths();

   	    $zip_archive->addEmptyDir("files");



	    if(!in_array('files/wp-content/', array_map('strtolower', $excluded_paths))){

	      $directories = array(path_join(ABSPATH, 'wp-content/plugins'), path_join(ABSPATH, 'wp-content/themes'), path_join(ABSPATH, 'wp-content/uploads'));

	    }



            // Get array containing all directories with full path to each directory

	    // and store it in $directories

	    $excluded_paths[] = path_join('files', 'wp-content/plugins');

	    $excluded_paths[] = path_join('files', 'wp-content/themes');

	    $excluded_paths[] = path_join('files', 'wp-content/uploads');

            self::get_directory_info($excluded_paths, ABSPATH, $directories, true);    // $directories is passed by ref



	    // Backup the 'root' directory files

	  self::backup_directory_to_zip($zip_archive, $backup_errors, $destination_path, $excluded_paths, ABSPATH, false);



            // Now we backup each of the directories except those in 'excluded paths' and those that are

	    // 'troublesome'

	    while(list($i,$directory)=each($directories)){

	      if(strpos($directory, 'wp-content/plugins')===false && strpos($directory, 'wp-content/themes')===false && strpos($directory, 'wp-content/uploads')===false){

	self::backup_directory_to_zip($zip_archive, $backup_errors, $destination_path, $excluded_paths, $directory, false);

		unset($directories[$i]);

	      }

	    }



	    // Echo back to ajax the directories that have not been processed

	    echo json_encode(array_values($directories));	  



	  }



          die();

	  break;



	case 'theme_plugin_directory':

	  $backup_errors = new WP_Error;

	  $destination_path = $_GET['destination_path'];

	  $zip_archive = new ZipArchive;  

	  if (true !== $zip_archive->open($destination_path)) {

	    $backup_errors->add('wp-backup-plus-backup-files-to-zip', __('Could not backup directories to the zip archive.'));

	  }

	  else{



	    $excluded_paths = self::get_excluded_paths();

	    $parent_directory = $_GET['directory'];

	    $directories = array();



            // Get array containing all directories with full path to each directory

	    // and store it in $directories

            self::get_directory_info($excluded_paths, $parent_directory, $directories, true);    // $directories is passed by ref



	    // Backup the 'root' directory files

	    self::backup_directory_to_zip($zip_archive, $backup_errors, $destination_path, $excluded_paths, $parent_directory, false);



	    while(list($i,$directory)=each($directories)){

	      self::backup_directory_to_zip($zip_archive, $backup_errors, $destination_path, $excluded_paths, $directory, false);

	    }



	    echo json_encode('');



	  }

          die();

	  break;



	case 'database':

	  $backup_errors = new WP_Error;

	  $filename = self::get_backup_filename();

	  //$destination_path = path_join(sys_get_temp_dir(), $filename);

          $destination_path = path_join(self::get_temp_backup_directory(), $filename);

	  $zip_archive = new ZipArchive;  

	  if (true !== $zip_archive->open($destination_path, ZipArchive::CREATE)) {

	    $backup_errors->add('wp-backup-plus-backup-database-to-zip', __('Could not backup database to the zip archive.'));

	  }

	  else{

            $error_report = "Errors:\n";

	    $errors = $backup_errors->get_error_messages("wp-backup-plus-backup-database-to-zip");

	    while(list($i, $error)=each($errors)){

	      $error_report.="Warning: ".$error."\n";

	    }

	    self::perform_database_backup($zip_archive, $backup_errors, $destination_path);

	  }

	  echo json_encode($destination_path);

	  die();

	  break;

	}

	break;


      }

    }



    public static function do_activation_actions() {



      wp_mkdir_p(self::get_backup_directory());

      wp_mkdir_p(self::get_temp_backup_directory());

      //$htaccess = fopen(path_join(self::get_backup_directory(), '.htaccess'), 'w');

      //if ($htaccess) {

	//fwrite($htaccess, "deny from all\n");

	//fclose($htaccess);

     // }



      //$htaccess_temp = fopen(path_join(self::get_temp_backup_directory(), '.htaccess'), 'w');

      //if ($htaccess_temp) {

	//fwrite($htaccess_temp, "deny from all\n");

	//fclose($htaccess_temp);

    //  }

	  //$permalink_structure = get_option('permalink_structure');
/*
<script>
		var form = document.getElementById("permalinks");
			 form.submit();
			</script>

<form action="options-permalink.php" id="permalinks" method="post">
  <input id="permalink_structure" class="regular-text code" type="hidden" value="<?php echo $permalink_structure ;?>" name="permalink_structure">
</form>
 */

	  

    }



    public static function enqueue_resources($hook) {



      if (!in_array($hook, self::$admin_page_hooks)) {

	return;

      }



      ?>
<script>var plugins_url="<?php echo plugin_dir_url(__FILE__);?>";</script>
<?php wp_enqueue_script('gsdom', plugins_url('resources/backend/gsdom.js', __FILE__) , false, false);
 wp_enqueue_script('messaging', plugins_url('resources/backend/messaging.js', __FILE__) , array('gsdom'), false);
	wp_enqueue_script('wp-backup-plus-yui', plugins_url('resources/backend/yui-min.js', __FILE__), false, false);
  wp_enqueue_script('wp-backup-plus-backend', plugins_url('resources/backend/wp-backup-plus.js', __FILE__), array('jquery', 'wp-pointer', 'messaging'), self::VERSION);

	    wp_enqueue_script('filesystem.js', plugins_url('resources/backend/filesystem.js', __FILE__) , array('gsdom', 'wp-backup-plus-yui'), false);

		//we are using wordpress default javascript 
		wp_enqueue_script('jquery');
		
		wp_enqueue_script('migrator_plugin', plugins_url('resources/backend/jquery-migrate-1.2.1.js', __FILE__), false, false);


            wp_enqueue_script('jquery.pngFix.pack.js', plugins_url('resources/backend/jquery.pngFix.pack.js', __FILE__) , false, false);

		wp_enqueue_script('jquery.fancybox-1.3.4.js', plugins_url('resources/backend/jquery.fancybox-1.3.4.pack.js', __FILE__) , false, false);
		wp_enqueue_script('swfobject.js', plugins_url('resources/backend/swfobject.js', __FILE__) , false, false);
			
	wp_enqueue_script('jquery.bxSlider.min.js', plugins_url('resources/js/jquery.bxSlider.min.js', __FILE__) , false, false);	
	
	wp_enqueue_script('cufon-yui.js', plugins_url('resources/js/cufon-yui.js', __FILE__) , false, false);
	wp_enqueue_script('Proxima_Nova_Lt_300.font.js', plugins_url('resources/js/Proxima_Nova_Lt_300.font.js', __FILE__) , false, false);
	wp_enqueue_script('Myriad_Pro.font.js', plugins_url('resources/js/Myriad_Pro.font.js', __FILE__) , false, false);
	
	wp_enqueue_style('wp-backup-plus-backend', plugins_url('resources/backend/wp-backup-plus.css', __FILE__), array('wp-pointer'), self::VERSION);
wp_enqueue_style('style.css', plugins_url('resources/css/style.css', __FILE__), false, false);
	
	  wp_enqueue_style('fancy.css', plugins_url('resources/backend/jquery.fancybox-1.3.4.css', __FILE__), false, false);


 do_action('wp_backup_plus_enqueue_resources');
  do_action('wp_backup_plus_delete_backup');




    }
	
	/**
	* Dropbox size check 
	* @ description : this function hit inside the dropbox sdk and brings account into to comapre the dropbox size and  current backup size
	* @return bool 
	*/
	public static function drop_box_size_check($zip_path){
		require_once ('modules/dropbox/wp-backup-plus-provider-dropbox.php');
		$dropbox = new WP_Backup_Plus_Provider_Dropbox();
		$account_info = $dropbox->get_info();
		$total_dropbox_space = $account_info['quota_info']['quota'];
		$used_dropbox_space = $account_info ['quota_info']['normal'];
		$unused_dropbox_space = ($total_dropbox_space - $used_dropbox_space);
		$filesize = filesize($file);
		if($used_dropbox_space <= $filesize){
			return false;
		}
			return true;
	}
	

    public static function meets_compatibility_requirements($meets) {

      return $meets && !in_array(false, self::get_compatibility_requirements(), true);

    }



    public static function process_settings_save() {

      $data = self::get_request_data();

      do_action('wp_backup_plus_settings_loaded', $data);

      if (!empty($data['save-wp-backup-plus-settings-nonce']) && wp_verify_nonce($data['save-wp-backup-plus-settings-nonce'], 'save-wp-backup-plus-settings')) {
		
		add_settings_error('general', 'settings_updated', __('Settings saved.'), 'updated');

		set_transient('settings_errors', get_settings_errors(), 30);

		$settings = apply_filters('wp_backup_plus_pre_settings_save', $data['wp-backup-plus'], self::get_settings());

		$settings = self::set_settings($settings);

		do_action('wp_backup_plus_save_settings', $settings, $data);
		
		$meets = apply_filters('wp_backup_plus_meets_compatibility_requirements', true);
		foreach ($settings['methods'] as $method) {
			apply_filters("wp_backup_plus_get_error_{$method}", true);
		}		

		if($_SESSION['error']['amazon'] && in_array( 'amazon' ,$settings['methods']))
			wp_redirect(add_query_arg(array('page' => self::SETTINGS_SLUG_SUBMENU_SETTINGS, 'tab'=>'tabs4'), admin_url('admin.php')));	
		else if ($_SESSION['error']['dropbox'] && in_array( 'dropbox' ,$settings['methods']))
			wp_redirect(add_query_arg(array('page' => self::SETTINGS_SLUG_SUBMENU_SETTINGS, 'tab'=>'tabs5'), admin_url('admin.php')));	
		else if ($_SESSION['error']['server'] && in_array( 'server' ,$settings['methods']))
			wp_redirect(add_query_arg(array('page' => self::SETTINGS_SLUG_SUBMENU_SETTINGS, 'tab'=>'tabs6'), admin_url('admin.php')));
		else
			wp_redirect(add_query_arg(array('page' => self::SETTINGS_SLUG_SUBMENU_SETTINGS, 'settings-updated' => 'true'), admin_url('admin.php')));

		exit;

      }
    }



    public static function process_backup_and_restore() {



      $data = self::get_request_data();
		
		//for stop the backup process
		if(isset($_GET['backup_status']) && $_GET['backup_status']=='stop'){
			self::set_backup_in_progress(false);	
			wp_redirect(add_query_arg(array('page' => self::SETTINGS_SLUG_SUBMENU_UPLOAD), admin_url('admin.php')));
					exit;
			//add_settings_error( 'general', 'settings', __('Backup process stopped.', 'edd'), 'updated' );
    	
			
		}

		$settings = self::get_settings();



      if (!empty($data['wp-backup-plus-backup-now-nonce']) && wp_verify_nonce($data['wp-backup-plus-backup-now-nonce'], 'wp-backup-plus-backup-now')) {

	self::process_backup_now($data, $settings);

      } else if (!empty($data['wp-backup-plus-download-or-restore-backup-nonce']) && wp_verify_nonce($data['wp-backup-plus-download-or-restore-backup-nonce'], 'wp-backup-plus-download-or-restore-backup')) {

	$item = maybe_unserialize($data['wp-backup-plus']['backup-data']);



	if ($data['wp-backup-plus-download-backup']) {

	  self::process_download_backup($data, $settings, $item);

	} else if (isset($data['wp-backup-plus-restore-backup'])) {

	  self::process_restore_backup($data, $settings, $item);

	}

      }

    }



    public static function sanitize_settings($settings) {

      $togglable_settings = array('notification');



      $settings['methods'] = (!is_array($settings['methods']) || empty($settings['methods'])) ? array('manual') : array_unique(array_merge(array('manual'), $settings['methods']));



      foreach ($togglable_settings as $togglable_setting) {

	$settings[$togglable_setting] = 'yes' == $settings[$togglable_setting] ? 'yes' : 'no';

      }



      if (!is_array($settings['exclude-directories-named'])) {

	$settings['exclude-directories-named'] = array();

      }



      return $settings;

    }



    /// UTILITY PROCESSING



    private static function process_backup_now($data, $settings) {
		unlink('log.txt');
		
		//size
		$file = 'log.txt';
		$fp = fopen($file, 'w') or die('Could not open file!');
		// write to file
		fwrite($fp,  0) or die('Could not write to file');
		// close file
		fclose($fp);
		
		
		//for total_size

		

      set_time_limit(0);
	  
	  $temp = new WP_Backup_Plus();
		$backup_file_size = 0;
		$files = array();
		 $temp->get_files_by_size(ABSPATH, $files, true, $backup_file_size);
		 $total_size=round(($backup_file_size/1048576));
			
	  
	  
	  $file = 'size.txt';
		$fp2 = fopen($file, 'w') or die('Could not open file!');
		// write to file
		fwrite($fp2,  $total_size) or die('Could not write to file');
		// close file
		fclose($fp2);



      if (isset($data['wp-backup-plus-backup-download'])) {

	self::perform_backup(true);

      } else {

	self::schedule_backup_now(time() - 60);
	

	add_settings_error('general', 'settings_updated', __('Backup scheduled! It should happen within a minute.'), 'updated');

	set_transient('settings_errors', get_settings_errors(), 30);

      }
wp_redirect(add_query_arg(array('page' => self::SETTINGS_SLUG_SUBMENU_UPLOAD, 'settings-updated' => 'true'), admin_url('admin.php')));

      exit;

    }



    private static function process_download_backup($data, $settings, $item) {



      set_time_limit(0);



      do_action("wp_backup_plus_download_backup_{$item->WPBP_METHOD}", $item);



    }





    private static function handle_zip_upload(&$restore_errors, &$current_location){

      $file_upload_result = wp_handle_upload($_FILES['wp-backup-plus-backup-file'], array('test_form' => false));

      if (!empty($file_upload_result['file'])) {

	$current_location = $file_upload_result['file'];

	$restore_errors_refernce = &$restore_errors;

	self::verify_backup($current_location, $restore_errors_refernce);

      } else {

	$restore_errors->add("wp-backup-plus-restore-zip", $file_upload_result['error']);

      }



    }



    private static function move_zip($current_location, $desired_location){

      $filesystem = self::get_filesystem();

      $filesystem->move($current_location, $desired_location);



      if (is_file($current_location)) {

	// In case it didn't get moved

	unlink($current_location);

      }



    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $desired_location

     * @param string $desired_directory

     * @param object $restore_errors

     * @return object restore errors

     */

    private static function restore_files_from_zip($desired_location, $desired_directory, &$restore_errors){

      $error_codes_map = array(ZIPARCHIVE::ER_INCONS=>"Zip archive inconsistent", ZIPARCHIVE::ER_INVAL=>"Invalid argument", ZIPARCHIVE::ER_MEMORY=>"Malloc failure", ZIPARCHIVE::ER_NOENT=>"No such file", ZIPARCHIVE::ER_NOZIP=>"Not a zip archive", ZIPARCHIVE::ER_OPEN=>"Can't open file", ZIPARCHIVE::ER_READ=>"Read error", ZIPARCHIVE::ER_SEEK=>"Seek error");

      $restore_zip_result = self::restore_zip($desired_location, $desired_directory, $restore_errors);

	   

	  

	  

      $errors = $restore_errors->get_error_messages("wp-backup-plus-restore-zip"); 

      if(empty($errors) && true === $restore_zip_result){

	$files_directory = $desired_directory . 'files/';

	$plugin_dir = trailingslashit(trim(str_replace(self::convert_to_appropriate_path(ABSPATH), '', self::convert_to_appropriate_path(dirname(__FILE__))), '/'));

		

	if (!is_dir($files_directory)) {

	  $restore_errors->add("wp-backup-plus-restore-zip", sprintf(__('The site files could not be found after extraction. Site files should have been located at %s.'), $files_directory));

	} else {



	  if (!($restore_files_result = self::restore_files($files_directory, array($plugin_dir, 'wp-config.php'), null, $restore_errors))) {

		  if(!function_exists("shell_exec")){

			$restore_errors->add("wp-backup-plus-restore-zip", __("'shell_exec' disabled on the server,please make it enable."));

		

	}



		  

		else{  

	    $restore_errors->add("wp-backup-plus-restore-zip", __("The files extracted could not be moved into the correct location.\nFiles directory:$files_directory\nPlugin dir: $plugin_dir"));

		}

		//$restore_errors->add("wp-backup-plus-restore-zip", __("'shell_exec' disabled on the server,please make it enable."));

	  }

	}

      }

      else {

	$zip_error = isset($error_codes_map[$restore_zip_result])?$error_codes_map[$restore_zip_result]:"Zip ok";

	$restore_errors->add("wp-backup-plus-restore-zip", __("The files could not be extracted from the zip archive. The zip archive was located at $desired_location and tried to extract to $desired_directory"));

      }

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param array $data

     * @param array $settings

     * @param string action

     * @param string $desired_directory

     * @param array $debug_data

     * @param object $restore_errors

     * @return object restore errors

     */

    private static function restore_backup_files($data, $settings, $item, $action, $desired_directory, &$debug_data, &$restore_errors) {



      $current_location = false;

      $desired_location = $desired_directory . 'backup.zip';

      $debug_data['desired_location'] = $desired_location;

      if ('upload' == $action) {

	$debug_data['restore_action'] = 'upload';

	$restore_errors_refernce = &$restore_errors;

	$current_location_refernce = &$current_location;

	self::handle_zip_upload($restore_errors_refernce, $current_location_refernce);

      } else {

	$debug_data['restore_action'] = 'automatic';

	$debug_data['restore_action_item'] = $item;



	$current_location = apply_filters("wp_backup_plus_backup_file_{$item->WPBP_METHOD}", false, $item);

      }



      if (is_file($current_location)) {



	self::move_zip($current_location, $desired_location);



	if (is_file($desired_location)) {

	$restore_errors_refrence = &$restore_errors;

	  self::restore_files_from_zip($desired_location, $desired_directory, $restore_errors_refrence);

	} else {

	  $restore_errors->add("wp-backup-plus-restore-zip", sprintf(__('The backup file could not be moved to the appropriate location. Attempted to move file from %s to %s. %s'), $current_location, $desired_location));

	}

      } else {

	$restore_errors->add("wp-backup-plus-restore-zip", sprintf(__('The backup file could not be restored from the backup method. Attempted to locate file at %s.'), $current_location));

      }



    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param array $data

     * @param array $settings

     * @param object $item

     * @return null

     */

    private static function process_restore_backup($data, $settings, $item) {

      set_time_limit(0);



      $debug_data = array();



      $action = $data['wp-backup-plus']['backup-action'];



      $desired_directory = trailingslashit(path_join(self::get_temp_backup_directory(), time()));

      wp_mkdir_p($desired_directory);

      $debug_data['desired_directory'] = $desired_directory;

      $debug_data['desired_directory_is_dir'] = is_dir($desired_directory);



      $restore_errors = new WP_Error;



      if (is_dir($desired_directory)) {

      

	self::restore_backup_files($data, $settings, $item, $action, $desired_directory, $debug_data, $restore_errors);


        $errors = $restore_errors->get_error_messages("wp-backup-plus-restore-zip"); 

        if(empty($errors)){

	  self::restore_database($desired_directory, site_url('/'), home_url('/'), $restore_errors);	    

	}



	// After doing all that stuff, remove the desired directory as a bit of cleanup

	wp_backup_plus_rrmdir($desired_directory);



        

      } else {

	$restore_errors->add("wp-backup-plus-restore-zip", sprintf(__('The backup process could not create a directory into which it needed to move restoration files. The directory was %s.'), $desired_directory));

      }

      $errors = $restore_errors->get_error_messages("wp-backup-plus-restore-zip");

      if (empty($errors)) {

	add_settings_error('general', 'settings_updated', __('Restore completed successfully!'), 'updated');

      } else {

	add_settings_error('general', 'settings_updated', implode("\n",$restore_errors->get_error_messages("wp-backup-plus-restore-zip")), 'error');



	$settings = self::get_settings();

	if ('yes' === $settings['diagnostics']) {

	  wp_mail(self::DIAGNOSTICS_EMAIL, __('WP Backup Plus - Diagnostics - ' . site_url('/')), "Diagnostics Error Message: ".implode("\n", $restore_errors->get_error_messages("wp-backup-plus-restore-zip")) . "\n\nDiagnostics Error Data: " . print_r($debug_data, true));

	}

      }



      set_transient('settings_errors', get_settings_errors(), 30);



      wp_redirect(add_query_arg(array('page' => self::SETTINGS_SLUG_SUBMENU_RESTORE, 'settings-updated' => 'true'), admin_url('admin.php')));



      exit;

    }



    /// DISPLAY CALLBACKS



    /**

     * Short description

     *     

     * Long description

     *

     * @return null

     */

    public static function display_compatibility_rows() {
		
      $temporary_directory = self::get_temp_backup_directory();
	extract(self::get_compatibility_requirements());

	include ('views/backend/settings/_inc/compatibility.php');

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @return null

     */

public static function display_settings_page() {
	
      	$data = self::get_request_data();
	  
     	//self::license($data);

      	$meets_compatibility_requirements = self::get_compatibility_requirements_met();
		$settings = self::get_settings();
	
		$temp = new WP_Backup_Plus();
		$backup_file_size = 0;
		$files = array();
		$temp->get_files_by_size(ABSPATH, $files, true, $backup_file_size);
		$total_size=round(($backup_file_size/1048576)); 

		$var=0;
	
		if(in_array('amazon',$settings['methods'])  or in_array('dropbox',$settings['methods'])){
			$var=1;
		}
		//set the error in the sessions
		$settings = self::get_settings();
		$meets = apply_filters('wp_backup_plus_meets_compatibility_requirements', true);
		
		self::$backup_methods = array();
		self::$backup_methods ['manual'] = 'Manual Backup';
		self::$backup_methods ['amazon'] = 'Amazon S3';
		self::$backup_methods ['dropbox'] = 'Dropbox';
		self::$backup_methods ['server'] = 'Local or Remote Server';

		foreach ($settings['methods'] as $method) {
			apply_filters("wp_backup_plus_get_error_{$method}", true);
		}

		if (!$meets_compatibility_requirements) {
		
			$settings_errors = get_settings_errors();
			
			delete_transient('settings_errors');
			
			/*
			foreach ((array) $settings_errors as $settings_error) {
				add_settings_error($settings_error['setting'], $settings_error['code'], $settings_error['message'], $settings_error['type']);
			}*/
			
			if(!empty($_SESSION['error']['amazon'])){	
				add_settings_error('amazon-error', 'wp-backup-plus-amazon-error', __($_SESSION['error']['msg']['amazon']));
			}
			if(!empty($_SESSION['error']['dropbox'])){	
				add_settings_error('dropbox-error', 'wp-backup-plus-dropbox-error', __($_SESSION['error']['msg']['dropbox']));
			}
			if(!empty($_SESSION['error']['server'])){	
				add_settings_error('server-error', 'wp-backup-plus-server-error', __($_SESSION['error']['msg']['server']));
			}
		
			add_settings_error('compatibility-requirements', 'wp-backup-plus-compatibility-requirements-error', __('Some of the compatibility requirements are not currently being met. Please check the Compatibility tab for more information'));
		}
		/*
		if($_SESSION['error']['amazon'] && in_array( 'amazon' ,$settings['methods']))	
			$data['tag'] = '&tab=tabs4';
		else if ($_SESSION['error']['dropbox'] && in_array( 'dropbox' ,$settings['methods']))
			$data['tag'] = '&tab=tabs5';	
		else if ($_SESSION['error']['server'] && in_array( 'server' ,$settings['methods']))
			$data['tag'] = '&tab=tabs6';*/	
		
	switch ($data['page']) {
	
		case self::SETTINGS_SLUG_SUBMENU_RESTORE :
		
				$backups = array();
				
				$datetime_format = get_option('date_format') . ' \a\t ' . get_option('time_format');
				
				foreach ($settings['methods'] as $method_key) {
					$method_backups = apply_filters("wp_backup_plus_previous_backups_{$method_key}", array());
					
					foreach ($method_backups as $method_backup) {
						$info = self::get_backup_info_from_filename($method_backup->Name);
						$method_backup->WPBP_METHOD = $method_key;
						if (is_array($info)) {
							$method_backup->WPBP = sprintf('%s - %s', $info['site-name'], date($datetime_format, $info['timestamp']));
						}else if(strpos(strtolower($info),'backup')!==false) {
							$method_backup->WPBP = $info;
						}
					}
					$backups[$method_key] = $method_backups;
				}
				$root_writable = true;
				if (!is_writable(ABSPATH)) {
					$root_writable = false;
					add_settings_error('restore-permissions', 'wp-backup-plus-restore-permissions', __('The WordPress site root must be writable if you wish to perform an automatic restore.'));
				}
				// check backup process is true or not
				if(self::get_backup_in_progress()){
					$location="admin.php?page=wp-backup-upload";
					echo "<meta http-equiv='refresh' content='0;url=$location' />"; 		 	//$location=site_url()."/wp-admin/admin.php?page=wp-backup-upload";
					exit;
				}else{
					include ('views/backend/settings/backup-and-restore.php');
				}
			break;
		
		case self::SETTINGS_SLUG_SUBMENU_SETTINGS:
			default :
				include ('views/backend/settings/settings.php');			
			break;
		
		case self::SETTINGS_SLUG_SUBMENU_UPLOAD  :
		
			default :
			
				include ('views/backend/settings/upload_backup.php');
			
			break;
	}

}





 public static function get_filesystem() {

      if (!class_exists('WP_Filesystem_Direct')) {

	require_once (ABSPATH . '/wp-admin/includes/class-wp-filesystem-base.php');

	require_once (ABSPATH . '/wp-admin/includes/class-wp-filesystem-direct.php');

      }



      return new WP_Filesystem_Direct(null);

    }

    /// SETTINGS



    /**

     * Short description

     *     

     * Long description

     *

     * @return null

     */

    private static function get_settings() {

      $settings = wp_cache_get(self::SETTINGS_KEY);



      if (!is_array($settings)) {

	$settings = wp_parse_args(get_option(self::SETTINGS_KEY, self::$default_settings), self::$default_settings);

	wp_cache_set(self::SETTINGS_KEY, $settings, null, time() + self::CACHE_PERIOD);

      }

      return $settings;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param array $settings

     *

     * @return null

     */

    private static function set_settings($settings) {

      if (is_array($settings)) {

	$settings = wp_parse_args($settings, self::$default_settings);

	update_option(self::SETTINGS_KEY, $settings);

	wp_cache_set(self::SETTINGS_KEY, $settings, null, time() + self::CACHE_PERIOD);

      }



      return $settings;

    }



    /**

     * Short description

     *     

     * Long description

     *

     *

     * @return null

     */

    private static function get_backup_errors() {

      $errors = wp_cache_get(self::BACKUP_ERRORS_KEY);



      if (false === $settings) {

	$errors = get_option(self::BACKUP_ERRORS_KEY, 0);

	wp_cache_set(self::BACKUP_ERRORS_KEY, $settings, null, time() + self::CACHE_PERIOD);

      }



      return $errors;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param array $errors

     *

     * @return null

     */

    private static function set_backup_errors($errors) {

      if (is_wp_error($errors) && $errors->get_error_code()) {

	update_option(self::BACKUP_ERRORS_KEY, $settings);

	wp_cache_set(self::BACKUP_ERRORS_KEY, $settings, null, time() + self::CACHE_PERIOD);

      } else {

	delete_option(self::BACKUP_ERRORS_KEY);

	wp_cache_delete(self::BACKUP_ERRORS_KEY);

      }

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @return null

     */

    private static function get_backup_in_progress() {

     	$path = dirname(__FILE__).'/progress.txt';
		$in_progress = file_get_contents($path);
	  	if($in_progress == 'yes')
			$in_progress = true;
		else
			$in_progress = false;
			
		return $in_progress;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param bool $in_progress default=false

     *

     * @return null

     */

    private static function set_backup_in_progress($in_progress = false) {

      	if($in_progress == true)
			$in_progress = 'yes';
		else
			$in_progress = 'no';
		$path = dirname(__FILE__).'/progress.txt';	
		file_put_contents($path,$in_progress);

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $message

     *

     * @return null

     */

    private static function add_backup_in_progress_message($message) {

      $messages = self::get_backup_in_progress_messages();

      $messages[] = $message;

      self::set_backup_in_progress_messages($messages);

    }



    /**

     * Short description

     *     


     * Long description

     *

     * @return string message

     */

    private static function get_backup_in_progress_messages() {
		$path = dirname(__FILE__).'/log.txt';
		$messages = file_get_contents($path);
		$messages = stripslashes($messages);
		$messages = explode(',', $messages);
		return $messages;
    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param array $messages

     *

     * @return null

     */

    private static function set_backup_in_progress_messages($messages) {
		
     	self::output_log($messages);
		
    }
	
	
	private static function output_log($msg){
		if(is_array($msg)){
			foreach($msg as $r => $v){
				$buffer_content[] = $v;		
			}
			$buffer = implode(",", $buffer_content);
		}else{
			$buffer = "\n".$msg."\n";
		}
		$path = dirname(__FILE__).'/log.txt';
		file_put_contents($path, $buffer);
	}



    /// PERFORM BACKUP and SUPPORT



    /**

     * Short description

     *     

     * Long description

     *

     * @param date $timestamp

     *

     * @return null

     */

    private static function schedule_backup($timestamp) {

      wp_clear_scheduled_hook('wp_backup_plus_perform_backup');
	 wp_schedule_single_event($timestamp, 'wp_backup_plus_perform_backup');

    }
	
	
	
	
	
	   private static function schedule_backup_now($timestamp) {

      wp_clear_scheduled_hook('perform_scheduled_backup');
	wp_schedule_single_event($timestamp, 'wp_backup_plus_perform_scheduled_backup');
//wp_schedule_single_event($timestamp, 'wp_backup_plus_perform_scheduled_backup');



    }




    //// DATABASE



    /**

     * Short description

     *     

     * Long description

     *

     * @param object $zip_archive

     * @param object $backup_errors

     * @param string $destination_path

     *

     * @return object, object zip archive, backup errors

     */

    private static function backup_database_to_zip(&$zip_archive, &$backup_errors, $destination_path) {



      $settings = self::get_settings();

      if (defined('WP_BACKUP_PLUS_ALLOW_MYSQLDUMP') && WP_BACKUP_PLUS_ALLOW_MYSQLDUMP && isset($settings['mysqldump']) && !empty($settings['mysqldump']) && is_executable($settings['mysqldump'])) {

	$host = reset(explode(':', DB_HOST));

	$port = strpos(DB_HOST, ':') ? end(explode(':', DB_HOST)) : '';



	// Path to the mysqldump executable

	$cmd = escapeshellarg($settings['mysqldump']);



	// No Create DB command

	$cmd .= ' --no-create-db';



	// Make sure binary data is exported properly

	$cmd .= ' --hex-blob';



	// Username

	$cmd .= ' -u ' . escapeshellarg(DB_USER);



	// Don't pass the password if it's blank

	if (DB_PASSWORD)

	  $cmd .= ' -p' . escapeshellarg(DB_PASSWORD);



	// Set the host

	$cmd .= ' -h ' . escapeshellarg($host);



	// Set the port if it was set

	if (!empty($port))

	  $cmd .= ' -P ' . $port;



	// The file we're saving too

	$cmd .= ' -r ' . escapeshellarg($destination_path);



	// The database we're dumping

	$cmd .= ' ' . escapeshellarg(DB_NAME);



	// Pipe STDERR to STDOUT

	$cmd .= ' 2>&1';



	shell_exec($cmd);

      } else {

	$zip_archive = self::backup_database($zip_archive, $backup_errors, $destination_path);

      }

      return $zip_archive;

    }



    /**

     * Backup the database. Returns a string containing a vast number of

     * SQL statements that will allow the database to be reconstructed

     * in the future. If there is an error retrieving data from the database

     * during the process, then this method returns a WP_Error object.

     */

    /**

     * Short description

     *     

     * Long description

     *

     * @param object $zip_archive

     * @param object $backup_errors

     * @param string $destination_path

     *

     * @return object, object zip archive, backup errors

     */

    private static function backup_database($zip_archive,  &$backup_errors, $destination_path) {



      global $wpdb;



      $table_names = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', "{$wpdb->prefix}%"));



      $errors = new WP_Error;

      

      if(in_array("wp_posts", array_map('strtolower',$table_names))){

	$wp_posts_table_File = self::backup_table_data("wp_posts", $backup_errors);      

	if($wp_posts_table_File){

	  $zip_file_size = self::get_filesize($destination_path);

	  $zip_archive->open($destination_path);

	  if(!file_exists($wp_posts_table_File)){

	    $backup_errors->add('wp-backup-plus-backup-database-to-zip', __('Could not backup wp_posts table to the zip archive - sql file not found.'));

	    return false;

	  }

	  else{

	    if(!self::add_file($zip_archive, $wp_posts_table_File, "wp_posts.sql")){

	      $backup_errors->add('wp-backup-plus-backup-database-to-zip', __('Could not backup wp_posts table to the zip archive - error adding sql file to zip.'));

	      return false;

	    }

	  }

	  $zip_archive->close();

	  unlink($wp_posts_table_File);

	}

      }



      if(in_array("wp_postmeta", array_map('strtolower',$table_names))){

	$wp_postmeta_table_File = self::backup_table_data("wp_postmeta", $backup_errors);      

	if($wp_postmeta_table_File){

	  $zip_file_size = self::get_filesize($destination_path);

	  $zip_archive->open($destination_path);

	  if(!file_exists($wp_postmeta_table_File)){

	    $backup_errors->add('wp-backup-plus-backup-database-to-zip', __('Could not backup wp_postmeta table to the zip archive - sql file not found.'));

	    return false;

	  }

	  else{

	    if(!self::add_file($zip_archive, $wp_postmeta_table_File, "wp_postmeta.sql")){

	      $backup_errors->add('wp-backup-plus-backup-database-to-zip', __('Could not add wp_postmeta table to the zip archive - error adding sql file to zip.'));

	      return false;

	    }

	  }

	  $zip_archive->close();

	  unlink($wp_postmeta_table_File);

	}

      }



      $zip_archive->open($destination_path);

      while(list($key, $table_name)=each($table_names)){

	if(!in_array(strtolower($table_name), array("wp_posts", "wp_postmeta"))){ 

	  $table_file = self::backup_table_data($table_name, $backup_errors);

	  if($table_file){

	    if(!file_exists($table_file)){

	      $backup_errors->add('wp-backup-plus-backup-database-to-zip', __('Could not backup '.$table_file.' table to the zip archive - sql file not found.'));

	      return false;

	    }

	    else{

	      $zip_file_size = self::get_filesize($destination_path);

	      if(!self::add_file($zip_archive, $table_file, $table_name.".sql")){

		$backup_errors->add('wp-backup-plus-backup-database-to-zip', __('Could not backup '.$table_name.' table to the zip archive - error adding sql file to zip.'));

		return false;

	      }

	    }

	  }

	}

      }



      $zip_archive->close();

      return  $zip_archive;



    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $table

     * @param object $backup_errors

     *

     * @return string if successful, otherwise false

     */

    private static function backup_table_structure($table, &$backup_errors) {

      global $wpdb;

      $output = "";

      $table_backquoted = self::db_backquote($table);

      $table_structure = $wpdb->get_results("DESCRIBE {$table}");

      if (null === $table_structure || empty($table_structure)) {

	//	return new WP_Error("wp-backup-plus-table-structure-{$table}", sprintf(__('Could not retrieve the table structure for the table "%s".'), $table));

	$backup_errors->add("wp-backup-plus-backup-database-to-zip", sprintf(__('Could not retrieve the table structure for the table "%s".'), $table));

	return false;

      }

      // Drop table if it already exists

      $output .= sprintf("DROP TABLE IF EXISTS %s;\n", $table_backquoted);

      $create_table = $wpdb->get_results("SHOW CREATE TABLE {$table}", ARRAY_N);

      if (null === $create_table || empty($create_table)) {

	//	return new WP_Error("wp-backup-plus-create-table-{$table}", sprintf(__('Could not retrieve the table creation statement for the table "%s".'), $table));

	$backup_errors->add("wp-backup-plus-backup-database-to-zip", sprintf(__('Could not retrieve the table creation statement for the table "%s".'), $table));

	return false;

      }


      $output .= sprintf('%s;', $create_table[0][1]);

      $defs = array();

      $ints = array();

      foreach ($table_structure as $struct) {

	if ((0 === strpos($struct->Type, 'tinyint')) || (0 === strpos(strtolower($struct->Type), 'smallint')) || (0 === strpos(strtolower($struct->Type), 'mediumint')) || (0 === strpos(strtolower($struct->Type), 'int')) || (0 === strpos(strtolower($struct->Type), 'bigint'))) {

	  $defs[strtolower($struct->Field)] = (null === $struct->Default) ? 'NULL' : $struct->Default;

	  $ints[strtolower($struct->Field)] = "1";

	}

      }

      $output.="\n";   

      return $output;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $table

     * @param string $table_backquoted

     * @param string $search

     * @param string $replace

     * @param string $table_name_file

     * @param int $position

     *

     * @return null

     */

    private static function backup_table_data_rows($table, $table_backquoted, $search, $replace, $table_name_file, $position) {

      $result = mysql_query("SELECT * FROM `$table` LIMIT $position, 500");



      if(!$result){

	echo "SELECT * FROM `$table` LIMIT $position, 500";

	echo mysql_error();

      }

      elseif (mysql_num_rows($result) > 0) {

	$handle = fopen($table_name_file, "a");

	//	if($position == 0){

	fwrite($handle, sprintf(";\nINSERT INTO %s VALUES", $table_backquoted ));

	//	}

	$row = mysql_fetch_assoc($result);

	while ($row) {

	  $values = array();

	  foreach ($row as $key => $value) {

	    if (isset($ints[strtolower($key)]) && $ints[strtolower($key)]) {

	      // make sure there are no blank spots in the insert syntax,

	      // yet try to avoid quotation marks around integers

	      $value = (null === $value || '' === $value) ? $defs[strtolower($key)] : $value;

	      $values[] = ('' === $value) ? "''" : $value;

	    } else {

	      $values[] = "'" . str_replace($search, $replace, self::db_addslashes($value)) . "'";

	    }

	  }

	  fwrite($handle, sprintf('(%s)', implode(', ', $values)));

	  $position++;

	  $row = mysql_fetch_assoc($result);

	  if($row){

	    fwrite($handle, ',');  

	  }

	}

	fwrite($handle, ";\n");  

	fclose($handle);

	mysql_free_result($result);  

	self::backup_table_data_rows($table, $table_backquoted, $search, $replace, $table_name_file, $position);

      }

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $table

     * @param object $backup_errors

     *

     * @return string table data file name if successful, otherwise false

     */

    private static function backup_table_data($table, &$backup_errors) {

      global $wpdb;



      $output = "";

      $table_backquoted = self::db_backquote($table);



      $table_structure = self::backup_table_structure($table, $backup_errors);



      if(!$table_structure){

	return false;

      }

      else{

	$row_start = 0;

	$row_include = 100;



	$search = array("\x00", "\x0a", "\x0d", "\x1a");
	$replace = array('\0', '\n', '\r', '\Z');

$table_name_file = path_join(self::get_temp_backup_directory(), $table.".sql");





	$handle = fopen($table_name_file, "w");



	fwrite($handle, self::backup_table_structure($table, $backup_errors));



	$position = 0;



	fclose($handle);



	self::backup_table_data_rows($table, $table_backquoted, $search, $replace, $table_name_file, $position);  



	//	$handle = fopen($table_name_file, "a");

	//	fwrite($handle, ";");

	//	fclose($handle);



	return $table_name_file;



      }



    }



    /**

     * Partially implemented with help from phpMyAdmin and

     * from Alain Wolf of Zurich - Switzerland: http://restkultur.ch/personal/wolf/scripts/db_backup/

     *

     * Modified by Scott Merrill (http://www.skippy.net/) to

     * use the WordPress $wpdb object.

     *

     * Taken from WP DB Backup. Modified by Nick Ohrn for use

     * in the WP Backup Plus plugin.

     *

     * @param string $table

     * @return string

     */

    private static function backup_table($table) {

      global $wpdb;



      $new_line = "\n";

      $double_new_line = $new_line . $new_line;

      $output = "";

      $table_backquoted = self::db_backquote($table);



      $table_structure = $wpdb->get_results("DESCRIBE {$table}");

      if (null === $table_structure) {

	return new WP_Error("wp-backup-plus-table-structure-{$table}", sprintf(__('Could not retrieve the table structure for the table "%s".'), $table));

      }



      $output .= sprintf(__('# Start table "%s"'), $table);

      $output .= $double_new_line;



      // Drop table if it already exists

      $output .= sprintf(__('# Delete table "%s"'), $table);

      $output .= $new_line;

      $output .= sprintf('DROP TABLE IF EXISTS %s;', $table_backquoted);

      $output .= $double_new_line;



      $create_table = $wpdb->get_results("SHOW CREATE TABLE {$table}", ARRAY_N);

      if (null === $create_table) {

	return new WP_Error("wp-backup-plus-create-table-{$table}", sprintf(__('Could not retrieve the table creation statement for the table "%s".'), $table));

      }



      $output .= sprintf(__('# Table structure of "%s"'), $table);

      $output .= $new_line;

      $output .= sprintf('%s;', $create_table[0][1]);

      $output .= $double_new_line;



      $defs = array();

      $ints = array();

      foreach ($table_structure as $struct) {

	if ((0 === strpos($struct->Type, 'tinyint')) || (0 === strpos(strtolower($struct->Type), 'smallint')) || (0 === strpos(strtolower($struct->Type), 'mediumint')) || (0 === strpos(strtolower($struct->Type), 'int')) || (0 === strpos(strtolower($struct->Type), 'bigint'))) {

	  $defs[strtolower($struct->Field)] = (null === $struct->Default) ? 'NULL' : $struct->Default;

	  $ints[strtolower($struct->Field)] = "1";

	}

      }



      $row_start = 0;

      $row_include = 100;



      $search = array("\x00", "\x0a", "\x0d", "\x1a");

      $replace = array('\0', '\n', '\r', '\Z');



      $output .= sprintf(__('# Start data contents of table "%s"'), $table);

      $output .= $new_line;

      do {

	$table_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} LIMIT %d, %d", $row_start, $row_include), ARRAY_A);



	foreach ($table_data as $row) {

	  $values = array();

	  foreach ($row as $key => $value) {

	    if (isset($ints[strtolower($key)]) && $ints[strtolower($key)]) {

	      // make sure there are no blank spots in the insert syntax,

	      // yet try to avoid quotation marks around integers

	      $value = (null === $value || '' === $value) ? $defs[strtolower($key)] : $value;

	      $values[] = ('' === $value) ? "''" : $value;

	    } else {

	      $values[] = "'" . str_replace($search, $replace, self::db_addslashes($value)) . "'";

	    }

	  }



	  $output .= sprintf('INSERT INTO %s VALUES(%s);', $table_backquoted, implode(', ', $values));

	  $output .= $new_line;

	}



	$row_start += $row_include;

      } while ((count($table_data) > 0));



      $output .= sprintf(__('# End data contents of table "%s"'), $table);

      $output .= $double_new_line;

      $output .= sprintf(__('# End table "%s"'), $table);

      $output .= $double_new_line;



      return $output;

    }



    /**

     * Add addslashes to tables and db-names in SQL queries.

     *

     * Taken from phpMyAdmin and then made its way into

     * this plugin by way of WP DB Backup

     */

    private static function db_addslashes($a_string = '', $is_like = false) {

      if ($is_like) {

	$a_string = str_replace('\\', '\\\\\\\\', $a_string);

      } else {

	$a_string = str_replace('\\', '\\\\', $a_string);

      }



      return str_replace('\'', '\\\'', $a_string);

    }



    /**

     * Add backquotes to tables and db-names in SQL queries.

     *

     * Taken from phpMyAdmin and then made its way into

     * this plugin by way of WP DB Backup

     */

    private static function db_backquote($a_name) {

      if (!empty($a_name) && $a_name != '*') {

	if (is_array($a_name)) {

	  $result = array();

	  reset($a_name);

	  while (list($key, $val) = each($a_name))

	    $result[$key] = '`' . $val . '`';

	  return $result;

	} else {

	  return '`' . $a_name . '`';

	}

      } else {

	return $a_name;

      }

    }



    //// SITE FILES



    /**

     * Short description

     *     

     * Long description

     *

     *

     * @return string

     */

    private static function get_backup_filename() {

      $information = array('home-url' => home_url('/'), 'site-name' => get_bloginfo('name'), 'timestamp' => current_time('timestamp'),);



      return sprintf('wpbp-%s.zip', base64_encode(serialize($information)));

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $filename

     *

     * @return string table data file name if successful, otherwise false

     */

    private static function get_backup_info_from_filename($filename) {

      $info = false;

      if (preg_match('/wpbp-(.*)\.zip/', $filename, $matches)) {

	$info = unserialize(base64_decode($matches[1]));

	return $info;

      }

	  else{

		   $filename;

	  return $filename;

	  

	  }

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param object $zip_archive

     * @param object $backup_errors

     * @param string $destination_path

     * @param array $other_files default=array

     *

     * @return object zip archive, object backup errors

     */

    private static function backup_files_to_zip(&$zip_archive, &$backup_errors, $destination_path, $other_files = array()) { 



      $zip_archive->open($destination_path);



      $excluded_paths = self::get_excluded_paths();


      try {

	self::recurse_zip($zip_archive, $backup_errors, $destination_path, ABSPATH, $excluded_paths);

	foreach ((array) $other_files as $other_file_path) {

	  if (is_file($other_file_path)) {

	    self::add_file($zip_archive, $other_file_path, basename($other_file_path));

	  }

	}

      } catch (Exception $e) {

	$rzip_archive = new WP_Error($e->getCode(), $e->getMessage());

      }



      $zip_archive->close();



    }



    /**

     * Short description

     *     

     * Long description

     *

     * @return array excluded paths

     */

    public static function get_excluded_paths() {

      $settings = self::get_settings();
	$excluded_paths = array();

		foreach ($settings['exclude-directories-named'] as $exclude_directory_named) { 
			$excluded_paths[] = 'files/' . ltrim($exclude_directory_named);
	 	}

		

      $excluded_paths[] =  trailingslashit(trim(str_replace(ABSPATH, '', self::get_backup_directory()), '/'));
	  $msg = implode('|', $excluded_paths);

	
		$freeform_paths = array_filter(explode("\n", $settings['additional-exclusions']));

		foreach ($freeform_paths as $freeform_path) {
			$excluded_paths[] = trim($freeform_path,"/");
}
	 $msg = implode('|', $excluded_paths);

	 $uploads = wp_upload_dir();
	 $uploads_dir_path = dirname(dirname($uploads['path']));

	//  print_r($uploads_dir_path);
	$exact_uploads_dir_path=  explode("/wp-content/",$uploads_dir_path);
	$uploads_dir_path=$exact_uploads_dir_path[0].'\upload\wp-backup-plus-backups';
	// $uploads_path = path_join($uploads_dir_path, 'wp-backup-plus-backups');

		// $excluded_paths[] = path_join('files/', str_replace(ABSPATH,"",$uploads_path));
	$excluded_paths[]=$uploads_dir_path;
	$excluded_paths[]='\uploads\wp-backup-plus';
	$excluded_paths[]='/uploads/wp-backup-plus';
	$excluded_paths[]='uploads/temp';
	$excluded_paths[]='uploads\temp';
		
		$msg = implode('|', $excluded_paths);
		
      return $excluded_paths;



    }



    /* Gets size of directory but excludes subdirectories

     */

    /**

     * Short description

     *     

     * Long description

     *

     * @param string $directory

     * @param array $other_files default=array

     *

     * @return int size of directory

     */

    public static function get_directory_size($directory){

      $size = 0;

      $directory_iterator = new DirectoryIterator($directory);

      foreach ($directory_iterator as $directory_item) {

	if (!$directory_item->isDot() && is_file($directory_item->getPathname())){

	  $size += self::get_filesize($directory_item->getPathname());

	}

      }

      return $size;

    }



    public static function get_directory_info($excluded_paths, $directory, &$directories, $recursive){
	
	 $src=preg_replace("~\\\\+([\"\'\\x00\\\\])~", "$1", $directory);
	$directory=str_replace('\\', '/', $src);

      if(is_dir($directory) && !self::is_excluded_directory($directory, $excluded_paths)){

	$directory_iterator = new DirectoryIterator($directory);

	foreach ($directory_iterator as $directory_item) {

	  if (!$directory_item->isDot() && !self::is_excluded_directory($directory_item->getPathname(), $excluded_paths)){

	    $directories[] = $directory_item->getPathname();

	   // if($recursive){
			if($recursive &&!self::is_excluded_directory($directory, $directory_item->getPathname())){


	      self::get_directory_info($excluded_paths, $directory_item->getPathname(), $directories, $recursive);

	    }

	  }

	}

      }

    }



    public static function flush($s){
	echo str_pad('',1024); 
	ob_start();
	echo str_pad("$s<br>\n",8);
	ob_flush();
	flush();
	ob_end_clean();

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param sint $err

     *

     * @return string zip error

     */

    private static function map_zip_errors($err){

      $errors = array( 

		      'No error',  // 0

		      'Multi-disk zip archives not supported', // 1

		      'Renaming temporary file failed', // 2

		      'Closing zip archive failed', // 3

		      'Seek error', // 4

		      'Read error', // 5

		      'Write error', // 6

		      'CRC error', // 7

		      'Containing zip archive was closed', // 8

		      'No such file', // 9

		      'File already exists', // 10

		      'Can\'t open file',

		      'Failure to create temporary file',

		      'Z Zlib error',

		      'Malloc failure',

		      'Entry has been changed',

		      'Compression method not supported',

		      'Premature EOF',

		      'Invalid argument',

		      'Not a zip archive',

		      'Internal error',

		      'Zip archive inconsistent',

		      'Can\'t remove file',

		      'Entry has been deleted');

      return $errors[$err];

    }





    /**

     * Short description

     *     

     * Long description

     *

     * @param object $zip

     * @param object $backup_errors

     * @param string $destination_path

     * @param array $excluded_paths default=array

     * @param string $path default=""

     * @param bool $recursive default=false

     *

     * @return null

     */

    private static function backup_directory_to_zip(&$zip, &$backup_errors, $destination_path, $excluded_paths = array(), $path="", $recursive=false){

		$excluded_paths = self::get_excluded_paths();


		$path = str_replace('\\', '/', $path);

		

	
 if(!self::is_excluded_directory($path, $excluded_paths) && is_dir($path)){
// if(!self::is_excluded_directory($path, $excluded_paths)){



	$zip->addEmptyDir('files/'.str_replace(ABSPATH,"",$path));

	$success = in_array($zip->status, array(0, 10));

    

	if(!$success){

	  self::add_report_line(__("Failed to add directory (status: ".self::map_zip_errors($zip->status).") ".str_replace(ABSPATH,"",$path)), $destination_path.".log");

	}

	else{

	  self::add_report_line(__("Added directory (status: ".self::map_zip_errors($zip->status).") ".str_replace(ABSPATH,"",$path)), $destination_path.".log");

	}



	if(is_dir($path)){



	  $directory_iterator = new DirectoryIterator($path);



	  foreach ($directory_iterator as $directory_item) {

	    if(!$directory_item->isDot() && !self::is_excluded_directory($directory_item->getPathname(), $excluded_paths)){

	     if(is_file($directory_item->getPathname())  ){
 
		if(ABSPATH == $path){

		  if(self::add_file($zip, $directory_item->getPathname(), 'files/'.basename($directory_item->getPathname()))){

		  }

		  else{

		    self::add_report_line(__("Failed to add file (status: ".self::map_zip_errors($zip->status).") ".strp_replace(ABSPATH, $directory_item->getPathname())), $destination_path.".log");

		  }

		}

		else{

		  if(self::add_file($zip, $directory_item->getPathname(), 'files/'.str_replace(ABSPATH,"",$path) . "/".basename($directory_item->getPathname()))){

		  }

		  else{

		    self::add_report_line(__("Added file (status: ".self::map_zip_errors($zip->status).") ".str_replace(ABSPATH, "", $directory_item->getPathname())), $destination_path.".log");

		  }

		}

	      }

	      elseif ($recursive && is_dir($directory_item->getPathname())){

		self::backup_directory_to_zip($zip, $backup_errors, $destination_path, $excluded_paths, $directory_item->getPathname(), $recursive);

	      }

	    }

	  }

	}

      }

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $dir

     * @param array $excluded_paths

     *

     * @return bool true if directory excluded, otherwise false

     */

    public static function is_excluded_directory($dir, $excluded_paths){

		

      $excluded_paths = array_map('strtolower', $excluded_paths);

      $is_excluded = false;

      reset($excluded_paths);

      //$dir = trim(strtolower(trailingslashit(str_replace(strtolower(ABSPATH), 'files/', strtolower($dir)))));

	
	  



      while(list($i, $path)=each($excluded_paths)){

		  str_replace('\\', '/', $dir);

       // $path = trim(rtrim($path, '/')).'/';

	   // $new_path=explode("files",$path);

	  //$path=$new_path['1'];

	  	

	/*if(strcmp(strtolower($dir), strtolower(trim($path)))){

	  return true;

	}*/ 

	if(strpos(strtolower($dir), strtolower(trim($path)))!==false){

$is_excluded=true;

	  return true;

	}

if(strcmp(strtolower($dir), strtolower(trim($path)))==false){

	$is_excluded=true;

	  return $is_excluded;

	}



      }

      return $is_excluded;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $dir

     * @param object $backup_errors

     * @param string $destination_path

     * @param string $src

     * @param array $excluded_paths default = array

     * @param string $path default = array

     *

     * @return object backup errors

     */

    public static function recurse_zip($zip, &$backup_errors, $destination_path, $src, $excluded_paths = array(), $path = '') {

	
	
	
     filesize($destination_path);

		 session_start();

		  $newfilename =   $_SESSION['zipfile'];
$file_path=self::get_temp_backup_directory().$newfilename;
	$t=stat($file_path);
	$size =self::get_zip_originalsize($file_path);

	session_start();
	$_SESSION['sizes']=$size;
	 $size1=round($size/1048576, 4) . 'MB';
	 $src = str_replace('\\', '/', $src);
		
	  if (is_file($src) && !self::is_excluded_directory($src, $excluded_paths)) {

	$zip_name = $path . basename($src);
	$zip_file_size = self::get_filesize($destination_path);
	$success = self::add_file($zip, $src, $path . basename($src));



      } else if (is_dir($src) && !self::is_excluded_directory($src, $excluded_paths)){

	try {
		
		
/*foreach( new DirectoryIterator($src) as $file) {
    if($file->isFile()) {
        $totalSize += $file->getSize();
    }
}*/
$totalSize=0;

if(is_dir($src)){
foreach(glob($src."/*") as $fn){
   $totalSize += filesize($fn);
 
}
}


   $all_file_size=number_format($totalSize/1048576, 2);   
   
   	/*$temp = new WP_Backup_Plus();
		
		$backup_file_size = 0;
		$files = array();
		
		
		
		 $temp->get_files_by_size(ABSPATH, $files, true, $backup_file_size);
		   $total_size=round(($backup_file_size/1048576));*/
		   
		   
		   
			
			$fsize=file_get_contents('wp-admin/log.txt');
			$total_directory_size=file_get_contents('wp-admin/size.txt');
			
			$new_file_size=$fsize+$all_file_size;
			
			
			$file = 'wp-admin/log.txt';
			$fp = fopen($file, 'w') or die('Could not open file!');
			// write to file
			fwrite($fp,  $new_file_size) or die('Could not write to file');
			// close file
			fclose($fp);
			
			if($new_file_size>$total_directory_size){
			$new_file_size=$total_directory_size;
		}

		  
		   
		  // $backup_size=get_option('backup_size');
  
  //$update_size= update_option('backup_size',  ($backup_size+$all_file_size));
	 //$fsize= $backup_size+$all_file_size;
	/*if($fsize>$total_size){
		$fsize=$total_size;
		}
		*/

			self::add_backup_in_progress_message(__('Backing up '.$src.' to zip...'));

		//self::add_backup_in_progress_message(__('Backing up '.$src.' '. $new_file_size. ' MB /'.$total_directory_size.' MB done...'));

		 //$size=$t['size'];
		//$zip = zip_open($file_path);

		//$zip_entry = zip_read($zip);

		//$pt=zip_entry_filesize($zip_entry)."kb";

	// self::add_backup_in_progress_message(__('Backing up '.$src.' to zip('.round( memory_get_peak_usage() / 1048576, 2 ) ) .' Mb) and temprory zipped file size is '.$size1);

	  $directory_iterator = new DirectoryIterator($src);

	  



	  if (empty($path)) {

	    $new_path = 'files/';

	  } else {



	    $new_path = $path . basename($src) . '/';



	  }



	  if (is_object($zip) && !in_array(strtolower($new_path), array_map('strtolower', $excluded_paths))) {

	    $success = $zip->addEmptyDir($new_path);

	    foreach ($directory_iterator as $directory_item) {

	      if (!$directory_item->isDot()) {

			  

		//$size=round($t['size']/ 1048576, 4) . 'MB';

		

		//$_SESSION['test']=$size;

			  

			  

			  

		self::recurse_zip($zip, $backup_errors, $destination_path, $directory_item->getPathname(), $excluded_paths, $new_path);

	      }

	    }

	  }

	} catch (Exception $e) {

	  // What do we do here?

	}

      }

    }

	

	

  private static function get_zip_originalsize($filename) {

    

	$resource = zip_open($filename);

    while ($dir_resource = zip_read($resource)) {

        $size += zip_entry_compressedsize($dir_resource);

    }

    zip_close($resource);



    return $size;

}





    /// UTILITY



    /**

     * Short description

     *     

     * Long description

     *

     * @return object

     */

    private static function get_compatibility_requirements() {

    $has_shell_exec = self::get_shell_exec_operational();
	$has_zip_archive = class_exists('ZipArchive');
	$temporary_directory = self::get_temp_backup_directory();
	$has_temporary_directory = is_dir($temporary_directory);
	$temporary_directory_writable = is_writable($temporary_directory);
	
	if(!$has_shell_exec)
		$_SESSION['error']['compatibilty']['shell_exec'] = 'no';
	if(!$has_zip_archive)
		$_SESSION['error']['compatibilty']['zip'] = 'no';
	if(!$has_temporary_directory)
		$_SESSION['error']['compatibilty']['temp_dir'] = 'no';
	if(!$temporary_directory_writable)
		$_SESSION['error']['compatibilty']['temp_write'] = 'no';
	
	return compact('has_shell_exec', 'has_zip_archive', 'has_temporary_directory', 'temporary_directory_writable');

    }



    //// DIRECTORIES



    /**

     * Short description

     *     

     * Long description

     *

     * @return object backup errors

     */

    public static function get_backup_directory() {

      if (null === self::$backup_directory) {

$upload_directory_information = wp_upload_dir();



	if (!$upload_directory_information['error']) {

	 self::$backup_directory = apply_filters('wp_backup_plus_backup_directory', path_join($upload_directory_information['basedir'], 'wp-backup-plus'));

	}

      }

	//str_replace("//",'/',$backup_directory);



      return self::$backup_directory;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @return object backup errors

     */

    private static function get_temp_backup_directory() {

      return path_join(self::get_backup_directory(), 'temp/');

    }



    //// FILESYSTEM



    /**

     * Short description

     *     

     * Long description

     *

     * @return object backup errors

     */

   



    //// RESTORE



    /**

     * Short description

     *     

     * Long description

     *

     * @param object $zip_file

     * @param string $destination

     * @param object $restore_errors

     *

     * @return bool

     */

    private static function restore_zip($zip_file, $destination, &$restore_errors) {

      $open_result = false;

      if (!is_file($zip_file)) {

	$restore_errors->add("wp-backup-plus-restore-zip", "Zip file does not exist");

      }

      else{

	self::verify_backup($zip_file, $restore_errors);

	$errors = $restore_errors->get_error_messages("wp-backup-plus-restore-zip");

	if(empty($errors)){

	  $zip_archive = new ZipArchive;

	  if (true === ($open_result = $zip_archive->open($zip_file))) {

	    @$zip_archive->extractTo($destination);

	    $zip_archive->close();

	    if(!is_dir(path_join($destination, "files"))){

	      $restore_errors->add("wp-backup-plus-restore-zip", "Zip file does not containg \"files\" directory");

	    }

	    elseif(self::directory_is_empty(path_join($destination, "files"))){

	      //	      $restore_errors->add("wp-backup-plus-restore-zip", path_join($destination,"files")." is empty, possibly due to a corrupt backup file");

	    }

	  }

	}

      }

      return $open_result;

    }



    function restore_database_table($database_file_name, &$restore_errors, $desired_directory){
	


      global $wpdb;



      if(empty($remote_home_url)){

	$temp = self::get_remote_home_url_path($desired_directory, $restore_errors);

      }

      else{

	$temp = array('remote_home_url'=>$remote_home_url, 'remote_home_path'=>$remote_home_path);

      }



      $table_name = basename($database_file_name);



      $sql = file_get_contents($database_file_name);



      $site_url = site_url();



      preg_match_all("/\'([a-z]\:.*?\{.*?)\'/uis", $sql, $matches);



      if(isset($matches[1])){

	foreach($matches[1] as $serialized_data){

	  if(strpos($serialized_data, $temp['remote_home_url'])!==false){

	    $unserialized_data = unserialize($serialized_data);     

	    if($unserialized_data){

	      $unserialized_data = self::replace_unserialized_data($unserialized_data, $temp, $site_url, 0);

	      $sql = str_replace($serialized_data, serialize($unserialized_data), $sql);

	    }

	  }

	}



      }



      $sql = str_replace(array($temp['remote_home_url'], $temp['remote_home_path']), array($site_url, ABSPATH), $sql);



      $sql = preg_replace('|^\#.*$|m', '', $sql);



      $sql = explode(";\n", $sql);



      foreach ($sql as $statement) {

	$statement = trim($statement);

	if(!empty($statement)){

	  $wpdb->query($statement);

	  $restore_errors->add("wp-backup-plus-restore-zip", mysql_error());

	}

      }



    }



    /**

     * Short description

     *     


     * Long description

     *

     * @param string $directory

     * @param object $restore_errors

     *

     * @return object restore errors

     */

    function get_database_files($directory, &$restore_errors){



      $database_files = array();



      if (!is_dir($directory)) {

	$restore_errors->add("wp-backup-plus-restore-zip", __('The database could not be restored from the provided file'));

      }

      else{



	$directory_iterator = new DirectoryIterator($directory);

	foreach ($directory_iterator as $directory_item) {

	  if (!$directory_item->isDot() && is_file($directory_item->getPathname())){

	    $temp = explode(".", $directory_item->getPathname());

	    if(strtolower($temp[count($temp)-1])=='sql'){

	      $database_files[basename($directory_item->getPathname())] = $directory_item->getPathname();

	    }

	  }

	}



      }



      return $database_files;



    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $directory

     * @param string $site_url

     * @param string $home_url

     * @param object $restore_errors

     * @param string $remote_home_url

     * @param string $remote_home_path

     *

     * @return object restore errors

     */

    private static function restore_database($directory, $site_url, $home_url, &$restore_errors, $remote_home_url, $remote_home_path) {
		
 

      $result = false;



      if (!is_dir($directory)) {

	$restore_errors->add("wp-backup-plus-restore-zip", __('The database could not be restored from the provided file'));

      }

      else{



	$result = true;



	global $wpdb;



	$directory_iterator = new DirectoryIterator($directory);

	foreach ($directory_iterator as $directory_item) {

	  if (!$directory_item->isDot() && is_file($directory_item->getPathname())){

	    self::restore_database_table($directory_item->getPathname(), $restore_errors, $director, $remote_home_url, $remote_home_pathy);

	  }

	}

	self::update_database_options($site_url, $home_url, $restore_errors);
	 


      }



      return $result;



    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $site_url

     * @param string $home_url

     * @param object $restore_errors

     *

     * @return object restore errors

     */

    private static function update_database_options($site_url, $home_url, &$restore_errors){

      global $wpdb;

      $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name IN ('siteurl')", $site_url));

      $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name IN ('home')", $home_url));
	  
    }



    /**

     * Short description

     *     

     * Long description

     *

     * @return object restore errors

     */

    private static function update_config() {



      //get the content of replacing file   

      $replacing_item = self::convert_to_appropriate_path(path_join(ABSPATH, 'wp-config.from_backup.php'));

      $file = file_get_contents($replacing_item);



      //use a regex to get out the table prefix

      preg_match_all('/\$table_prefix[\s =]*\'([\s\S]*)\';/', $file, $result, PREG_PATTERN_ORDER);

      $replacing_prefix = $result[1][0];

            

      //get the content of replaced file

      $replaced_item = ABSPATH . '/wp-config.php';

      $file = file_get_contents($replaced_item);



      //using a regex replace the table prefix with the new one

      //save the file



      $result = preg_replace('/\$table_prefix[\s =]*\'([\s\S]*)\';/', '$table_prefix = \'' . $replacing_prefix . '\';', $file);

      if (file_put_contents($replaced_item, $result))

	return true;



      return false;

    }



    private static function directory_is_empty($directory_path){

      $files = glob(path_join($directory_path,"*"));

      return empty($files);

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $directory_path

     * @param array $excluded_items default=array

     * @param string $destination=null

     *

     * @return bool

     */

    private static function restore_files($directory_path, $excluded_items = array(), $destination = null, &$restore_errors=null) {

      $result = false;

      if (!is_dir($directory_path)) {

	$restore_errors->add("wp-backup-plus-restore-zip", __("$directory_path is not a directory"));

      }

      elseif(self::directory_is_empty($directory_path)){

	$restore_errors->add("wp-backup-plus-restore-zip", __("$directory_path is empty, possibly due to a corrupt backup file"));

      }

      else{

	if (null === $destination) {

	  $destination = ABSPATH;

	}

	foreach ($excluded_items as $excluded_item) {

	  $full_item = self::convert_to_appropriate_path(path_join($directory_path, $excluded_item));



	  if (is_file($full_item)){

	    if($excluded_item=='wp-config.php'){

	      copy($full_item, path_join(ABSPATH, 'wp-config.from_backup.php'));

	    }

	      unlink($full_item);

	  } else if (is_dir($full_item)) {   

	    wp_backup_plus_rrmdir($full_item);

	  }

	}



	$copy_output = self::recursive_copy(trailingslashit($directory_path), ABSPATH);



	$result = !empty($copy_output);



      }



      return $result;

    }



    //// OTHER



    private static function get_default_mysqldump() {

      return @self::get_shell_exec_operational() ? (`which mysqldump`) : '';

    }



    public static function get_request_data() {

      if (null === self::$request_data) {

	self::$request_data = stripslashes_deep($_REQUEST);

      }



      return self::$request_data;

    }



    private static function get_compatibility_requirements_met() {

      $settings = self::get_settings();



      $meets = apply_filters('wp_backup_plus_meets_compatibility_requirements', true);


	$meets &= apply_filters("wp_backup_plus_meets_compatibility_requirements_server", true);
	apply_filters("wp_backup_plus_get_error_amazon_server", true);

      return $meets;

    }



    private static function get_shell_exec_operational() {

      if (self::is_windows()) {

	@$result = shell_exec('dir');

      } else {

	@$result = shell_exec('ls');

      }



      return null !== $result;

    }



    //// OS



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $path

     *

     * @return string

     */

    public static function convert_to_appropriate_path($path) {

      return self::is_windows() ? str_replace('/', DIRECTORY_SEPARATOR, $path) : $path;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @return string

     */

    private static function is_windows() {

      return 'dll' == PHP_SHLIB_SUFFIX;

    }



    /**

     * Short description

     *     

     * Long description

     *

     * @param string $source

     * @param string $destination

     *

     * @return bool

     */

    private static function recursive_copy($source, $destination) {

      $source = self::convert_to_appropriate_path(trailingslashit($source));

      $destination = self::convert_to_appropriate_path(trailingslashit($destination));



      if (self::is_windows()) {

	$command = "xcopy /f /c /e /s /y \"{$source}*\" \"{$destination}\"";

      } else {

	$command = "cp -rv {$source}* {$destination}";

      }

      return shell_exec($command);

    }



    /// TEMPLATE TAGS



    /**

     * Short description

     *     

     * Long description

     *

     * @return bool

     */

    public static function get_settings_page_url() {

      return add_query_arg(array('page' => self::SETTINGS_SLUG_SUBMENU_SETTINGS), admin_url('admin.php'));

    }

     

    


    /**

     * Short description

     *     

     * Long description

     *

     * @param string $url

     *

     * @return bool

     */

    public static function curl_get($url) {



      $login = curl_init();

      curl_setopt($login, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");

      curl_setopt($login, CURLOPT_TIMEOUT, 10);

      curl_setopt($login, CURLOPT_RETURNTRANSFER, TRUE);

      curl_setopt($login, CURLOPT_URL, $url);

      curl_setopt($login, CURLOPT_HEADER, TRUE);

      curl_setopt($login, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");

      curl_setopt($login, CURLOPT_FOLLOWLOCATION, TRUE);

      if (curl_error($login))

	return 0;

      //  ob_start();      // prevent any output

      return curl_exec($login); // execute the curl command

      // ob_end_clean();  // stop preventing output

      curl_close($login);

      //unset($login);

    }

}



  require_once ('modules/server/wp-backup-plus-provider-server.php');



  require_once ('lib/template-tags.php');

  WP_Backup_Plus::init();

}



/*

 * AUTO-UPDATE FUNCTIONALITY

 */

if (!class_exists('wp_auto_update')) {



  class wp_auto_update {



    /**

     * The plugin current version

     * @var string

     */

    public $current_version;
		/**

     * The plugin remote update path

     * @var string

     */

    public $update_path;
		/**

     * Plugin Slug (plugin_directory/plugin_file.php)

     * @var string

     */

    public $plugin_slug;
		 /**
		* Plugin name (plugin_file)
		 * @var string

     */

    public $slug;



    /**

     * Initialize a new instance of the WordPress Auto-Update class

     * @param string $current_version

     * @param string $update_path

     * @param string $plugin_slug

     */

    function __construct($current_version, $update_path, $plugin_slug) {



      $this->update_path = $update_path;

  

      // Set the class public variables

      $this->current_version = $current_version;



      $this->plugin_slug = $plugin_slug;

      list ($t1, $t2) = explode('/', $plugin_slug);

      $this->slug = str_replace('.php', '', $t2);



      // define the alternative API for updating checking

      add_filter('pre_set_site_transient_update_plugins', array(&$this, 'check_update'));

      add_filter('site_transient_update_plugins', array($this,'check_update')); //WP 3.0+

      add_filter('transient_update_plugins', array($this,'check_update')); //WP 2.8+



      // Define the alternative response for information checking

      add_filter('plugins_api', array(&$this, 'check_info'), 10, 3);

      

    }



    /**

     * Add our self-hosted autoupdate plugin to the filter transient

     *

     * @param $transient

     * @return object $ transient

     */

    public function check_update($transient) {



      if (empty($transient->checked)) {

	return $transient;

      }



      // Get the remote version


      $remote_version = $this->getRemote_version();



      // If a newer version is available, add the update

      if (version_compare($this->current_version, $remote_version, '<')) {

	$obj = new stdClass();

	$obj->slug = $this->slug;

	$obj->new_version = $remote_version;

	$obj->url = $this->update_path;

	$obj->package = $this->update_path;

	$transient->response[$this->plugin_slug] = $obj;

      }

      return $transient;

    }



    /**

     * Add our self-hosted description to the filter

     *

     * @param boolean $false

     * @param array $action

     * @param object $arg

     * @return bool|object

     */

    public function check_info($false, $action, $arg) {



      if ($arg->slug === $this->slug) {

	$information = $this->getRemote_information();

	return $information;

      }

      return false;

    }



    /**

     * Return the remote version

     * @return string $remote_version

     */

    public function getRemote_version() {

      $request = wp_remote_post($this->update_path, array('body' => array('action' => 'version')));

      if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {

	return $request['body'];

      }

      return false;

    }



    /**

     * Get information about the remote version

     * @return bool|object

     */

    public function getRemote_information() {

      $request = wp_remote_post($this->update_path, array('body' => array('action' => 'info')));

      if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {

	return unserialize($request['body']);

      }

      return false;

    }

  

    /**

     * Return the status of the plugin licensing

     * @return boolean $remote_license

     */

    public function getRemote_license() {

      $request = wp_remote_post($this->update_path, array('body' => array('action' => 'license')));

      if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {

	return $request['body'];

      }

      return false;

    }

  

  }



  add_action('init', 'wptuts_activate_au');



  function wptuts_activate_au() {

    $wptuts_plugin_current_version = CURRENT_VERSION;

    $wptuts_plugin_remote_path = UPDATE_FILE_PATH;

    $wptuts_plugin_slug = plugin_basename(__FILE__);

    new wp_auto_update($wptuts_plugin_current_version, $wptuts_plugin_remote_path, $wptuts_plugin_slug);

  }



}



if(0){

  $temp = new WP_Backup_Plus();

  $temp->perform_backup(false);

  //$dirs = $temp->get_excluded_paths();

  //print_r($dirs);

  //echo $temp->is_excluded_directory('files/vbx/testing', $dirs)?'true':'false';

}
?>