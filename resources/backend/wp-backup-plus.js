
jQuery(document).ready(function($) {
	
	var $status_field = $('#wp-backup-plus-backup-status-field')
	    , $status_field_container = $('#wp-backup-plus-backup-status');

	var backup_running = false;
	var saved_directories_count = 0;
	var saved_plugin_directories_count = 0;
	var number_of_plugin_directories = null;
	var saved_theme_directories_count = 0;
	var number_of_theme_directories = null;
	var saved_uploads_directories_count = 0;
	var number_of_uploads_directories = null;
	var last_file_size = -1;

	var excluded_directories_checkboxes = document.getElementsByName('wp-backup-plus[exclude-directories-named');
	YUI().use('io', function (Y) {

		var showBackupFileSizeSuccess = function(Y){
		    return function(id,o){
			//alert(o.responseText);
			document.getElementById('wp-backup-plus-backup-size').innerHTML = o.responseText;
		    }
		}

		var showBackupFileSizeFailure = function(Y){
		    return function(id,o){
		    }
		}

		var showBackupFileSize = function(Y){
		    return function(e){
			var cfg = {
			    method: 'POST',
			    data:'add='+(e.target._node.checked?'1':'0'),
			    form: {
				id: 'wpap_settings_form'
			    },
			    on : {
				success : showBackupFileSizeSuccess(Y),
				failure : showBackupFileSizeFailure(Y)
			    }
			}
			Y.io(ajaxurl+'?action=wp_backup_plus_get_backup_size', cfg); 
		    }
		}


		Y.all('.wpap_exclude_directory').on('click', showBackupFileSize(Y));

		if(document.getElementById('wp-backup-plus-additional-exclusions')){
		    Y.one('#wp-backup-plus-additional-exclusions').on('mouseout', showBackupFileSize(Y));
		}

	    });



	if($('#wp-backup-plus-restore-backup')){
YUI().use('io', 'json-parse', 'async-queue', 'io-upload-iframe', function (Y) {

		    var restoreStart = function(Y){
			return function(id, args){
			}
		    }

		    var restoreComplete = function(Y){
			return function(id, o, args){
			}
		    }

		    var restoreSuccess = function(Y, step, url, backup_directory, table_name, ajaxurl, remote_home_url, remote_home_path){

			return function(id,o){

			    try{
				var data = Y.JSON.parse(o.responseText)
				    switch(step){
				    case 'restore_database_table':
				    if(data!='' && data!='null' && data!=null){
					//alert(data);
				$("#setting-error-settings_updated").html("<p><strong>"+data+"</strong></p>");
				$("#setting-error-settings_updated").show();
				    }
				    break;
				    case 'get_remote_home_url_path':
					
		     		    do_restore(Y, 'get_database_files', '?action=wp_backup_plus_restore&step=get_database_files&backup_directory='+encodeURIComponent(backup_directory), backup_directory, null, data['remote_home_url'], data['remote_home_path'])(null);
				    break;

				    case 'update_database_options':
				    MESSAGING.show('Restore completed<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
				    document.location.href = data;
				    break;  
				    case 'get_database_files':

				    var database_files = data;

				    var database_tables_queue = new Y.AsyncQueue();
				    database_tables_queue.defaults.timeout = 5000; 
				    for(table_name in database_files){
					database_tables_queue.add(do_restore(Y, 'restore_database_table', '?action=wp_backup_plus_restore&step=restore_database_table&database_file_name='+encodeURIComponent(database_files[table_name])+'&backup_directory='+encodeURIComponent(backup_directory), backup_directory, table_name, remote_home_url, remote_home_path));       
				    }

				    database_tables_queue.add(do_restore(Y, 'update_database_options', '?action=wp_backup_plus_restore&step=update_database_options&backup_directory='+encodeURIComponent(backup_directory), backup_directory, null, remote_home_url, remote_home_path));

                                    database_tables_queue.run();

				    break;
				    case 'restore_backup_files':
				    if(data!=''){
					MESSAGING.destroy('wp-backup-plus');
					$("#setting-error-settings_updated").html("<p><strong>"+data+"</strong></p>");
					$("#setting-error-settings_updated").show();
					//alert(data);
				    }
				    else{
					do_restore(Y, 'get_remote_home_url_path', '?action=wp_backup_plus_restore&step=get_remote_home_url_path&backup_directory='+encodeURIComponent(backup_directory), backup_directory, null, null, null)(null);
				    }
				    break; 
				    case 'get_backup_directory':

				    var upload_directory = data;
					
				    var upload_cfg = {
					method: 'POST',
					data: 'upload_directory='+upload_directory,
					form: {
					    id: formid,
					    upload: true
					}
				    }

				    // Define a function to handle the start of a transaction
				    function start(id, args) {

					MESSAGING.show('Uploading<br/><div class="message_small">Please do not refresh or close the page till the restore completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
					var id = id; // Transaction ID.
					var args = args.backup_directory;
				    }
 // Define a function to handle the response data.
				    function complete(id, o, args) {
					var id = id; // Transaction ID.
					var data = o.responseText; // Response data.
					var backup_directory = args[0]; 
					if(data==''){
					    // zip file successfully uploaded
					    do_restore(Y, 'restore_backup_files', '?action=wp_backup_plus_restore&step=restore_backup_files&backup_directory='+encodeURIComponent(backup_directory), backup_directory, table_name, null, null)(null);

					}
					else{
					    MESSAGING.destroy('wp-backup-plus');
				$("#setting-error-settings_updated").html("<p><strong>"+data+"</strong></p>");
				$("#setting-error-settings_updated").show();
							
					
						<!--else{
					   	 //alert(data);
						//}-->
					}
				    };

				    // Subscribe to event "io:start", and pass an object
				    // as an argument to the event handler "start".
				    Y.on('io:start', start, Y, { 'backup_directory': upload_directory });

				    // Subscribe to event "io:complete", and pass an array
				    // as an argument to the event handler "complete".
				    Y.on('io:complete', complete, Y, [upload_directory]);

				    // Start the transaction.
				    var request = Y.io(ajaxurl+'?action=wp_backup_plus_restore&step=handle_zip_upload', upload_cfg); 


				    break;

				    }
			    }
			    catch(err){
					MESSAGING.destroy('wp-backup-plus');
				
				//alert('There was an error. Restoration cannot continues.\n\nERROR:'+err+'\n\nRESPONSE:'+o.responseText);
				$("#setting-error-settings_updated").html("<p><strong>There was an error. Restoration cannot continue,check your 'max_execution_time' and 'max_input_time' in php.ini</strong></p>");
				$("#setting-error-settings_updated").show();
			
			    }
			}
		    }

		    var restoreFailure = function(Y, step, url, backup_directory, table_name, ajaxurl){
			return function(id,o){
			    MESSAGING.destroy('wp-backup-plus');
			}
		    }

		    $('.button-primary').click(function(event) {
				
				var answer = confirm("Are you sure you want to restore the website?")
	if (answer){
				
				t=$(this).attr("attribute");
			
				
			
				if(t=="" || typeof(t)=="undefined"){
				formid='wp-backup-plus-restore-form'
				}
				else{
					formid='wp-backup-plus-restore-form-'+t
					
				}
				
	document.getElementById('wp-backup-plus-restore-backup').enabled = false;
			    event.preventDefault();
			    var backup_directory = null;
			    var table_name = null;
			    backup_running = true;
			    do_restore(Y, 'get_backup_directory', '?action=wp_backup_plus_restore&step=get_backup_directory',backup_directory, table_name, null, null)(null);

			    return false;
	}
	else{
	return false	
	}
			});

		    var do_restore = function(Y, step, url, backup_directory, table_name, remote_home_url, remote_home_path){
			return function(e){

                            Y.detach('io:start');
                            Y.detach('io:complete');

			    switch(step){

			    case 'get_remote_home_url_path':
				MESSAGING.show('Retrieving remote information<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
				break;

			    case 'update_database_options':
			    MESSAGING.show('Updating database options<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
			    break;
			    case 'restore_database_table':
			    MESSAGING.show('Restoring '+table_name.replace(".sql", "")+'<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
			    break;
			    case 'get_backup_directory':
				$("#setting-error-settings_updated").hide();
			    MESSAGING.show('Getting backup directory<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
							    break;
			    case 'restore_backup_files':
			    MESSAGING.show('Restoring backup files<br/><div class="message_small">Please do not refresh or close the page till the restore is  completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
			    break;
			    case 'handle_zip_upload':
						
			    MESSAGING.show('Uploading<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
			    break;  
			    case 'get_database_files':
			    MESSAGING.show('Fetching database files<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
			    break;
			    }

			    var cfg = {
				method: 'GET',
				form: {
				    id: formid
				}
				
				,
				on : {
					
				    start : restoreStart(Y),
				    complete : restoreComplete(Y),
				    success : restoreSuccess(Y, step, url, backup_directory, table_name, ajaxurl, remote_home_url, remote_home_path),
				    failure : restoreFailure(Y, step, url, backup_directory, table_name, ajaxurl)
				}
			    }
			    Y.io(ajaxurl+url, cfg); 

			}

		    } // do_restore()

		})

		}



	if($('#wp-backup-plus-backup-download')){
	    $('#wp-backup-plus-backup-download').click(function(event) {
		    event.preventDefault();
		    //			MESSAGING.show('bye world', 'wp-backup-plus', plugins_url+'resources/backend/');
		    //			MESSAGING.show('hello world this is long long long long long long line', 'wp-backup-plus', plugins_url+'resources/backend/');
		    //			return 1;
		    document.getElementById('wp-backup-plus-backup-download').enabled = false;
		    saved_directories_count=0;
		    backup_running = true;
		    download_backup('excluded_directories', '?action=wp_backup_plus_download&step=excluded_directories', null, null, 0, null, null)();

		    return false;
		});
	}

	var get_zip_filesize = function(destination_path, is_complete_poll){

	    $.getJSON(
		      ajaxurl+'?action=wp_backup_plus_download&step=check_zip_size&destination_path='+encodeURIComponent(destination_path),
		      function(data){

			  if(data*1 == last_file_size*1){

			      backup_running = false;

			      document.getElementById('wp-backup-plus-backup-download').enabled = true;
			      //				  document.getElementById('wp-backup-plus-ajax-log').innerHTML = "";
			      //			      MESSAGING.destroy('wp-backup-plus');


			      last_file_size = 0;

			      saved_directories_count = 0;
			      saved_plugin_directories_count = 0;
			      number_of_plugin_directories = null;
			      saved_theme_directories_count = 0;
			      number_of_theme_directories = null;
			      saved_uploads_directories_count = 0;
			      number_of_uploads_directories = null

			      is_complete_poll = null;
			      check_zip_filesize = null;

			      MESSAGING.destroy('wp-backup-plus');

			      document.location.href = ajaxurl+'?action=wp_backup_plus_download&step=download_zip&destination_path='+encodeURIComponent(destination_path);



			      //			      download_backup('get_download_link', '?action=wp_backup_plus_download&step=get_download_link&destination_path='+encodeURIComponent(destination_path), null, null, 0, destination_path, null)();

			  }
			  else if(data*1>0 && last_file_size!=0){
			      last_file_size = data;
			      //				  document.getElementById('wp-backup-plus-ajax-log').innerHTML = "";
			      //document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Waiting for zip to finish...";
			      MESSAGING.show('Waiting for zip to finish', 'wp-backup-plus', plugins_url+'resources/backend/');
			  }
		      }		
		      )
	}

	var check_zip_filesize = function(destination_path, is_complete_poll){
	    return function(e){
		get_zip_filesize(destination_path, is_complete_poll);
	    }
	}

	function backup_is_complete(destination_path, is_complete_poll){
	    return function(e){
		//		    alert("DEBUG: testing to see if backup is complete  - " + saved_plugin_directories_count+ ' '+  number_of_plugin_directories + ' ' + saved_plugin_directories_count + ' ' + number_of_theme_directories + ' '+  saved_theme_directories_count + ' ' + number_of_uploads_directories + ' ' + saved_uploads_directories_count);

		if(0){
		    //		    if(saved_plugin_directories_count > 0 && number_of_plugin_directories >= saved_plugin_directories_count && 
		    //       number_of_theme_directories >= saved_theme_directories_count &&
		    //number_of_uploads_directories >= saved_uploads_directories_count){
		    is_complete_poll = null;
		    saved_plugin_directories_count = 0;
		    document.getElementById('wp-backup-plus-backup-download').enabled = true;
		    //			document.getElementById('wp-backup-plus-ajax-log').innerHTML = "";
		    MESSAGING.destroy('wp-backup-plus');
		    document.location.href = ajaxurl+'?action=wp_backup_plus_download&step=download_zip&destination_path='+encodeURIComponent(destination_path);
		}
	    }
	}

	function download_backup(step, url, directories, excluded_directories, directory_index, destination_path, is_complete_poll){
	    return function(){
		switch(step){
		case 'get_download_link':
		    MESSAGING.destroy('wp-backup-plus');
		    break;
		case 'excluded_directories':
		    //document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Preparing...";
		    MESSAGING.show('Preparing...<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
			
		    break;

		case 'database':
		    //			document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Backing up database ...";
		    MESSAGING.show('Backing up database<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
		    break;
		case 'directory_info':
		    //			document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Backing up standard files...";
		    MESSAGING.show('Backing up standard files<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
		    break;
		case 'plugins_directory':
		    if(jQuery.inArray('files/wp-content/', excluded_directories)==-1){
			//			    document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Backing up plugins directory ...";
			MESSAGING.show('Backing up plugins directory<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
		    }
		    break;
		case 'get_plugins_directories':
		    if(jQuery.inArray('files/wp-content/', excluded_directories)==-1){
			//			    document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Fetching plugins directories ...";
			MESSAGING.show('Fetching plugins directories<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
		    }
		    break;
		case 'themes_directory':
		    if(jQuery.inArray('files/wp-content/', excluded_directories)==-1){
			//			    document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Backing up themes directory ...";
			MESSAGING.show('Backing up themes directory<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
		    }
		    break;
		case 'get_themes_directories':
		    if(jQuery.inArray('files/wp-content/', excluded_directories)==-1){
			//			    document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Fetching themes directories ...";
			MESSAGING.show('Fetching themes directories<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
		    }
		    break;
		case 'uploads_directory':
		    if(jQuery.inArray('files/wp-content/', excluded_directories)==-1){
			//			    document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Backing up uploads directory ...";
			MESSAGING.show('Backing up uploads directory<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
		    }
		    break;
		case 'get_uploads_directories':
		    if(jQuery.inArray('files/wp-content/', excluded_directories)==-1){
			//			    document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Fetching uploads directories ...";
			MESSAGING.show('Fetching uploads directories<br/><div class="message_small">Please do not refresh or close the page till the restore is  completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
		    }
		    break;
		case 'directory':
		    if(directories[directory_index]){
			//			    document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Preparing...";
			MESSAGING.show('Preparing<br/><div class="message_small">Please do not refresh or close the page till the restore is completed</div>', 'wp-backup-plus', plugins_url+'resources/backend/');
		    }
		    else{
			return 1;
		    }
		    break;
		}
		
		$.getJSON(
			  ajaxurl+url,
			  function(data){
			      switch(step){
			      case 'get_download_link':
				  document.location.href=data;
				  //document.getElementById('wpap_download_backup_link_container').innerHTML = '<a href="'+data+'">Download backup</a>';
				  break;
			      case 'excluded_directories':
				  excluded_directories = data;
				  download_backup('database', '?action=wp_backup_plus_download&step=database', null, excluded_directories, 0, null, null)();
				  break;
			      case 'database':
				  destination_path = data;
				  download_backup('directory_info', '?action=wp_backup_plus_download&step=directory_info&destination_path='+encodeURIComponent(destination_path),directories, excluded_directories, directory_index, destination_path, is_complete_poll)();
				  return false;
				  break;
			      case 'directory_info':
				  if(jQuery.inArray('files/wp-content/', excluded_directories)==-1){
				      download_backup('get_plugins_directories', '?action=wp_backup_plus_download&step=get_plugins_directories&destination_path='+encodeURIComponent(destination_path),directories, excluded_directories, directory_index, destination_path, is_complete_poll)();
				  }
				  else{
				      if(is_complete_poll == null && destination_path!=null){
					  if(check_zip_filesize==null){
					      MESSAGING.destroy('wp-backup-plus');
					      document.location.href = ajaxurl+'?action=wp_backup_plus_download&step=download_zip&destination_path='+encodeURIComponent(destination_path);

					      //					      download_backup('get_download_link', '?action=wp_backup_plus_download&step=get_download_link&destination_path='+encodeURIComponent(destination_path), null, null, 0, destination_path, null)();

					  }
					  else{
					      var cb = check_zip_filesize(destination_path, is_complete_poll);
					      is_complete_poll = setInterval(cb, 10000);
					  }
				      }
				  }
				  break;
			      case 'get_plugins_directories':
				  directories = data;
				  number_of_plugin_directories = directories.length;
				  download_backup('plugin_directory', '?action=wp_backup_plus_download&step=theme_plugin_directory'+'&directory='+encodeURIComponent(directories[0])+'&destination_path='+encodeURIComponent(destination_path), directories, excluded_directories, 0, destination_path, is_complete_poll)();
				  break;
			      case 'get_themes_directories':
				  directories = data;
				  //alert('Got theme directories:'+data);
				  number_of_theme_directories = directories.length;
				  download_backup('theme_directory', '?action=wp_backup_plus_download&step=theme_plugin_directory'+'&directory='+encodeURIComponent(directories[0])+'&destination_path='+encodeURIComponent(destination_path), directories, excluded_directories, 0, destination_path, is_complete_poll)();
				  break;
			      case 'get_uploads_directories':
				  directories = data;
				  number_of_uploads_directories = directories.length;
				  download_backup('uploads_directory', '?action=wp_backup_plus_download&step=theme_plugin_directory'+'&directory='+encodeURIComponent(directories[0])+'&destination_path='+encodeURIComponent(destination_path), directories, excluded_directories, 0, destination_path, is_complete_poll)();
				  break;
			      case 'plugin_directory':
				  saved_plugin_directories_count++;
				  //				      document.getElementById('wp-backup-plus-ajax-log').innerHTML = 'Saving plugin directory '+(saved_plugin_directories_count+"")+' of '+number_of_plugin_directories+''; 
				  //				      document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Saving plugins "+(directory_index*1+1)+' of '+directories.length;
				  MESSAGING.show("Saving plugins "+(directory_index*1+1)+' of '+directories.length+"<br/><div class='message_small'>Please do not refresh or close the page till the restore is completed</div>", 'wp-backup-plus', plugins_url+'resources/backend/');
				  if(directories[directory_index*1+1]){
				      download_backup('plugin_directory', '?action=wp_backup_plus_download&step=theme_plugin_directory'+'&directory='+encodeURIComponent(directories[directory_index*1+1])+'&destination_path='+encodeURIComponent(destination_path), directories, excluded_directories, directory_index*1+1, destination_path, is_complete_poll)();
				  }
				  else{
				      download_backup('get_themes_directories', '?action=wp_backup_plus_download&step=get_themes_directories&destination_path='+encodeURIComponent(destination_path),directories, excluded_directories, 0, destination_path, is_complete_poll)();
				  }
				  break;
			      case 'theme_directory':
				  saved_theme_directories_count++;
				  //		      document.getElementById('wp-backup-plus-ajax-log').innerHTML = 'Saving theme directory '+(saved_theme_directories_count+"")+' of '+number_of_theme_directories+'';                          
				  //				      document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Saving themes "+(directory_index*1+1)+' of '+directories.length;
				  MESSAGING.show("Saving themes"+(directory_index*1+1)+' of '+directories.length+" <br/><div class='message_small'>Please do not refresh or close the page till the restore is completed</div>", 'wp-backup-plus', plugins_url+'resources/backend/');
				  if(directories[directory_index*1+1]){
				      download_backup('theme_directory', '?action=wp_backup_plus_download&step=theme_plugin_directory'+'&directory='+encodeURIComponent(directories[directory_index*1+1])+'&destination_path='+encodeURIComponent(destination_path), directories, excluded_directories, directory_index*1+1, destination_path, is_complete_poll)();
				  }
				  else{
				      download_backup('get_uploads_directories', '?action=wp_backup_plus_download&step=get_uploads_directories&destination_path='+encodeURIComponent(destination_path),directories, excluded_directories, 0, destination_path, is_complete_poll)();
				  }
				  break;
			      case 'uploads_directory':
				  saved_uploads_directories_count++;
				  //document.getElementById('wp-backup-plus-ajax-log').innerHTML = 'Saving uploads directory '+(saved_uploads_directories_count+"")+' of '+number_of_uploads_directories+''; 
				  //				      document.getElementById('wp-backup-plus-ajax-log').innerHTML = "Saving uploads "+(directory_index*1+1)+' of '+directories.length;
				  MESSAGING.show("Saving uploads "+(directory_index*1+1)+' of '+directories.length, 'wp-backup-plus', plugins_url+'resources/backend/');
				  if(directories[directory_index*1+1]){
				      download_backup('uploads_directory', '?action=wp_backup_plus_download&step=theme_plugin_directory'+'&directory='+encodeURIComponent(directories[directory_index*1+1])+'&destination_path='+encodeURIComponent(destination_path), directories, excluded_directories, directory_index*1+1, destination_path, is_complete_poll)();
				  }
				  else{
				      if(is_complete_poll == null && destination_path!=null){
					  if(check_zip_filesize==null){

					      MESSAGING.destroy('wp-backup-plus');

					     document.location.href = ajaxurl+'?action=wp_backup_plus_download&step=download_zip&destination_path='+encodeURIComponent(destination_path);

					     //  download_backup('get_download_link', '?action=wp_backup_plus_download&step=get_download_link&destination_path='+encodeURIComponent(destination_path), null, null, 0, destination_path, null)();


					  }
					  else{
					      var cb = check_zip_filesize(destination_path, is_complete_poll);
					      is_complete_poll = setInterval(cb, 10000);
					  }
				      }
				  }
				  break;
			      case 'directory':
				  saved_directories_count++;
				  //				      document.getElementById('wp-backup-plus-ajax-log').innerHTML = 'Saving directory '+(saved_directories_count+"")+' of '+directories.length+''; 
				  MESSAGING.show('Saving directory '+(saved_directories_count+"")+' of '+directories.length+'', 'wp-backup-plus', plugins_url+'resources/backend/');
				  if(saved_directories_count==directories.length){
				      document.getElementById('wp-backup-plus-backup-download').enabled = true;
				      //					  document.getElementById('wp-backup-plus-ajax-log').innerHTML = "";
				      MESSAGING.destroy('wp-backup-plus');

				      document.location.href = ajaxurl+'?action=wp_backup_plus_download&step=download_zip&destination_path='+encodeURIComponent(destination_path);

				      //			      download_backup('get_download_link', '?action=wp_backup_plus_download&step=get_download_link&destination_path='+encodeURIComponent(destination_path), null, null, 0, destination_path, null)();

				  }
			      }
			  }
			  )
		    }

	}

	if($status_field.size() > 0) {  
	    setInterval(function() {
		    if(!backup_running){ // backup_running is global
			
			$.get(
			      ajaxurl,
			      { action: 'wp_backup_plus_progress' },
			      function(data, status) {
					  
					
				
				  if(data.in_progress) {
					
					  	$('#wp-backup-plus-backup-download').attr("disabled", true);
						$("#wp-backup-plus-backup-download").addClass("backup_dwnld_disable");
							$('#now_backup').attr("disabled", true);
							$("#now_backup").addClass("backup_now_disable");
							
						 $("#wp-backup-plus-restore-backup").addClass("restore_btn_disable");
						 
						
						   $("#wp-backup-plus-restore-backup").removeClass("button-primary");
						    $("#wp-backup-plus-restore-backup").removeClass("button");
						   $('#wp-backup-plus-restore-backup').attr("disabled", true);
						 
						 
						  $("#setting-error-settings_updated_loader").show();
						  $("#setting-error-settings_updated").hide();
						   $("#setting-error-settings_updated_loader").html('<p><strong><img class=" left" src="'+plugins_url+'resources/backend/ajaxspinner.gif"/><span class="loader_text">Please wait! Back up is still processing.</span></strong></p>');

				
				      $status_field_container.show();
					    
				  }
				else{
					
					$("#wp-backup-plus-restore-backup").addClass("button-primary");
					$("#wp-backup-plus-restore-backup").addClass("button");
					$('#wp-backup-plus-backup-download').attr("disabled", false);
					$('#backup_now').attr("disabled", false);
					$('#now_backup').attr("disabled", false);
					$('#wp-backup-plus-restore-backup').attr("disabled", false);
					$("#setting-error-settings_updated").html("");
					$("#setting-error-settings_updated_loader").hide();
					$("#wp-backup-plus-backup-download").removeClass("backup_dwnld_disable");
					$("#wp-backup-plus-restore-backup").removeClass("restore_btn_disable");
					$("#now_backup").removeClass("backup_now_disable");
			 
				}
					
				  if(data.messages.length > 0) {
					
					  $("#setting-error-settings").hide();
				      var new_messages = data.messages.slice(parseInt($status_field.attr('data-last-message-index')));
				
					 if(new_messages.length > 0) {
						
						 if(data.in_progress==true){
						 $('#backup_process').text(data.total_directory_size);
						$('#stop_backup').show();
						 }
						 else{
							 $('#stop_backup').hide();
							 
						 }
						 
						  var message_text = $status_field.val() + new_messages.join("\n") + "\n";
						$status_field.val(message_text).attr('data-last-message-index', data.messages.length).scrollTop($status_field.get(0).scrollHeight);
					
					
				      }
				  }
			      },
			      'json'
			      );
		    }
		}, 5000);
	}
		
	$('input.wp-backup-plus-method').bind('click change', function(event) {
		var $dependents = $('.wp-backup-plus-method-settings').hide();
		ul=$('#setting_url').val();
		
		var $checked_methods = $('input.wp-backup-plus-method:checked').each(function(index, element) {
			$dependents.filter('[data-method="' + $(element).attr('value') + '"]').show();
			if(!$('#wp-backup-plus-methods-dropbox').attr('checked')) {
				if(!$('#wp-backup-plus-methods-dropbox').attr('checked')) {
	$('.display_msg5').replaceWith("<a href='http://wpbackupplus.com/?utm_source=plugin&utm_medium=free&utm_campaign=upgrade' target='blank'><input type='button' class='upgrade_btn' name='update_amazon' value=''/></a>");
	$(".display_msg5").show();
  
  
}
			}
			else{
			$(".display_msg5").hide();
			
			}
			if(!$('#wp-backup-plus-methods-amazon').attr('checked')) {
				$('.display_msg4').replaceWith("<a href='http://wpbackupplus.com/?utm_source=plugin&utm_medium=free&utm_campaign=upgrade' target='blank'><input type='button' class='upgrade_btn' name='update_amazon' value=''/></a>");
	$(".display_msg4").show();
  
		
			
			}
			else{
			$(".display_msg4").hide();
			
			}

			if(!$('#wp-backup-plus-methods-server').attr('checked')) {
				$('.display_msg6').replaceWith("To enable this setting, please select 'Local or Remote Server' setting from <a style='color:#0088CC;' href='"+location.href+"'>General Settings</a> tab.");
				$(".display_msg6").show();
			}
			else{
				$(".display_msg6").hide();
			
			}
				//$(".display_msg6").hide();
		    });
	    }).filter(':first').change();
		    
	$('.wp-backup-plus-help').each(function(index, element) {
		var $link = $(element);
				
		$link.pointer({
			content: '<h3>' + $link.attr('title') + '</h3><p>' + $link.attr('data-content') + '</p>',
			    position: 'top'
			    }).click(function(event) {
				    event.preventDefault();
							
				    $link.pointer('toggle');
				});
	    });
			
	$('#wp-backup-plus-notification').bind('click change', function(event) {
		var $this = $(this);
		var $dependency = $('#wp-backup-plus-email').parents('tr');
					
		if($this.is(':checked')) {
		    $dependency.show();
		} else {
		    $dependency.hide();
		}
	    }).change();
			    
	$('.wp-backup-plus-backup-toggle').click(function(event) {
		event.preventDefault();
					
		var $backup_action = $('#wp-backup-plus-backup-action');
		var backup_action = $backup_action.val() == 'upload' ? 'existing' : 'upload';
		$backup_action.val(backup_action).change();
	    });
				
	$('#wp-backup-plus-backup-action').change(function(event) {
		var backup_action = $(this).val() == 'upload' ? 'upload' : 'existing';
					    
		$('[data-backup-action]').hide().filter('[data-backup-action="' + backup_action + '"]').show();
						
		$download = $('#wp-backup-plus-download-backup');
		if('upload' == backup_action) {
		    $download.hide();
		} else {
		    $download.show();
		}
	    }).change();
    });





