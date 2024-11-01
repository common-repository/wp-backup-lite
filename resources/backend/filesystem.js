

WPBPFileSystem = function(){
       
    var $ = function(id){
	return document.getElementById(id);
    }

    var exclude = function(Y, directory, directory_element, icon, directories_container, directory_link, directory_link_container, toggle_directory_checkbox, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_checkbox){
	
	return function(e){
	    //            e.preventDefault();
	    //            e.stopPropagation;
	    if(directory.type=='file'){
		Y.one(directory_link_container).replaceClass('included', 'excluded');
	    }
	    else{
		Y.one(directory_element).replaceClass('included', 'excluded');
	    }
	    var callback = include(Y, directory, directory_element, icon, directories_container, directory_link,  directory_link_container, toggle_directory_checkbox, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_checkbox);
           // Y.one(toggle_directory_checkbox).detach('click', callback);
            //Y.one(toggle_directory_checkbox).on('click', callback);
	    //	    alert('exclude');
	    //      	    Y.one(toggle_directory_checkbox).on('click', close(Y, directory, directory_element, icon, directories_container, directory_link, add_directory_callback, remove_directory_callback, toggle_directory_checkbox, add_include_directory_callback, remove_include_directory_callback));
	    //      	    close(Y, directory, directory_element, icon, directories_container, directory_link, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox)(null);  
	    add_directory_callback(directory.short_path, parent_directory_checkbox==null?null:parent_directory_checkbox.checked);
	    remove_include_directory_callback(directory.short_path);
	    showBackupFileSize(Y, 0)(null);
	}
    }

    var include = function(Y, directory, directory_element, icon, directories_container, directory_link, directory_link_container, toggle_directory_checkbox, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_checkbox){
	return function(e){
	    //	    alert('include');
	    //            e.preventDefault();
	    //            e.stopPropagation;
	    if(directory.type=='file'){
		Y.one(directory_link_container).replaceClass('excluded', 'included');
	    }
	    else{
		Y.one(directory_element).replaceClass('excluded', 'included');
	    }
	    //	    alert(toggle_directory_checkbox);
	    var callback = exclude(Y, directory, directory_element, icon, directories_container, directory_link, directory_link_container, toggle_directory_checkbox, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_checkbox);
            Y.one(toggle_directory_checkbox).detach('click');
            Y.one(toggle_directory_checkbox).on('click', callback);
	    //	    alert('include');
	    //      	    Y.one(toggle_directory_checkbox).on('click', close(Y, directory, directory_element, icon, directories_container, directory_link, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox));
	    //      	    close(Y, directory, directory_element, icon, directories_container, directory_link, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox)(null);
	    remove_directory_callback(directory.short_path);
	    add_include_directory_callback(directory.short_path, parent_directory_checkbox==null?null:parent_directory_checkbox.checked);
	    showBackupFileSize(Y, 1)(null);
	}
    }

    var expand_cached = function(Y, directory, directory_element, icon, directories_container, directory_link, toggle_directory_checkbox, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback){
        return function(e){
            e.preventDefault();
            e.stopPropagation;
	    //            icon.src = plugins_url+'resources/backend/directory_icon_open.png';
		    Y.one(icon).setStyle('background', 'url('+plugins_url+'resources/backend/directory_icon_open.png) left no-repeat');
	    //	    Y.one(icon).setStyle('background',plugins_url+'resources/backend/directory_icon_open.png');
            Y.one(directories_container).setStyle('display', 'block');
            Y.one(directory_link).detach('click');
      	    Y.one(directory_link).on('click', close(Y, directory, directory_element, icon, directories_container, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox));
	}
    }

    var close = function(Y, directory, directory_element, icon, directories_container, directory_link, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox, checkbox){
        return function(e){
    //	    alert('close()'+toggle_directory_checkbox);
	    if(e && !checkbox){
		e.preventDefault();
		e.stopPropagation;
	    }

	    //            icon.src = plugins_url+'resources/backend/directory_icon_closed.png';
	    //	    Y.one(icon).setStyle('background',plugins_url+'resources/backend/directory_icon_closed.png');
		    Y.one(icon).setStyle('background', 'url('+plugins_url+'resources/backend/directory_icon_closed.png) left no-repeat');
            Y.one(directories_container).setStyle('display', 'none');
            Y.one(directory_link).detach('click');
	    //      	    Y.one(directory_link).on('click', expand_cached(Y, directory, directory_element, icon, directories_container, directory_link, toggle_directory_checkbox, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback));
      	    Y.one(directory_link).on('click', expand(Y, directory_element, icon, directory, directory_link, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox));
	    //      	    Y.one(toggle_directory_checkbox).on('click', close(Y, directory, directory_element, icon, directories_container, directory_link, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox));

	}
    }

    var expand = function(Y, directory_element, icon, directory, directory_link, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox){
	return function(e){
	    e.preventDefault();
	    e.stopPropagation;
	    //	    icon.src = plugins_url+'resources/backend/directory_icon_open.png';
	    //	    Y.one(icon).setStyle('background',plugins_url+'resources/backend/directory_icon_open.png');
		    Y.one(icon).setStyle('background', 'url('+plugins_url+'resources/backend/directory_icon_open.png) left no-repeat');
	    var directories_container = $ul();
	    /*
    var get_directories = function(Y, directory_name, directory_element, directories_container, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_is_excluded){
	     */
	    get_directories(Y, directory.path, directory_element, directories_container, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox)(e);
	    Y.one(directory_link).detach('click');
      	    Y.one(directory_link).on('click', close(Y, directory, directory_element, icon, directories_container, directory_link, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox));
	    Y.one(toggle_directory_checkbox).on('click', close(Y, directory, directory_element, icon, directories_container, directory_link, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox, true));
	}
    }

    var render_directories = function(Y, parent_directory_element, directories, directories_container, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_checkbox){
	return function(e){
	    var directory_element = null;
	    var icon = null;
	    var directory = null;
	    var directory_link = null;
	    var i = null;
	    var directory_class = null;
	    var toggle_directory_checkbox = null;
	    for(i in directories){
		directory = directories[i];
		directory_class = directory.excluded?'excluded':'included';
	        directory_element = $li({});
		//		Y.one(directory_element).setStyle('width', '450px');
		directory_link = $a({'href':'#'}, directory.name+'');
		
		directory_link_container = $div({'_class':directory_class, 'className':directory_class}, directory_link)
		icon = $div({'style':' height:16px; width:16px; display:block; float:left' , '_class':'folder_image'});
		toggle_directory_checkbox = $input({'type':'checkbox'});
		toggle_directory_checkbox.checked = directory.excluded;
		Y.one(toggle_directory_checkbox).on('click', directory.excluded?include(Y, directory, directory_element, icon, directories_container, directory_link, directory_link_container, toggle_directory_checkbox, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_checkbox):exclude(Y, directory, directory_element, icon, directories_container, directory_link, directory_link_container, toggle_directory_checkbox, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_checkbox));
		if(directory.type=='file'){
		    Y.one(icon).setStyle('background', 'url('+plugins_url+'resources/backend/file_icon.png) left no-repeat');
		    //		    icon = $img({'src':plugins_url+'resources/backend/file_icon.png'});
		}
		else{
		    Y.one(icon).setStyle('background', 'url('+plugins_url+'resources/backend/directory_icon_closed.png) left no-repeat');
		    //	    Y.one(icon).setStyle('background',plugins_url+'resources/backend/directory_icon_closed.png');
		    //		    icon = $a({'href':'#'}, $img({'src':plugins_url+'resources/backend/directory_icon_closed.png'}));
		    //      	            Y.one(icon).on('click', expand(Y, directory_element, icon, directory));
      	            Y.one(directory_link).on('click', expand(Y, directory_element, icon, directory, directory_link, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, toggle_directory_checkbox));
		}
		//		toggle_directory_checkbox = $a({'href':'#'}, $img({'src':plugins_url+'resources/backend/toggle_directory_icon.png'}));
		directory_element.appendChild(icon);
		directory_element.appendChild(directory_link_container);
		directory_element.appendChild($div({}, directory.size+''));
		directory_element.appendChild($div({}, toggle_directory_checkbox));
		directories_container.appendChild(directory_element);
		parent_directory_element.appendChild(directories_container);
	    }
	    //	    return directories_container;
	}
    }

    var get_directoriesSuccess = function(Y, directory_element, directories_container, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_checkbox){
	return function(id,o){
	    //	    alert(o.responseText);
	    var directories = Y.JSON.parse(o.responseText);
	    render_directories(Y, directory_element, directories, directories_container, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_checkbox)(null);
	}
    }

    var get_directoriesFailure = function(Y){
	return function(id,o){
	   // alert('Connection error');
	}
    }

    var get_directories = function(Y, directory_name, directory_element, directories_container, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_checkbox){
	return function(e){
	    var parent_directory_is_excluded = parent_directory_checkbox == null?null:parent_directory_checkbox.checked;
	    if(e){
	   	    e.preventDefault();
	   	    e.stopPropagation;
	    }
	    var cfg = {
		method: 'GET',
		data: 'directory_name='+encodeURIComponent(directory_name)+'&parent_directory_is_excluded='+parent_directory_is_excluded,
	        on : {
		    success : get_directoriesSuccess(Y, directory_element, directories_container, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, parent_directory_checkbox),
 	            failure : get_directoriesFailure(Y)
		}
	    }
	    Y.io(ajaxurl+"?action=wp_backup_plus_get_directories", cfg); 
	}
    }

    return{

        init : function(container_id, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback){

	    return function(e){

		// All components: http://yuilibrary.com/yui/docs/guides/
		YUI().use("io", "dump", "json-parse", 'node', 'event', 'transition', 'node-load', 'anim',  function (Y) {
			Y.on('available', function(){
				$(container_id).innerHTML = "";
				var directories_container = $ul();
				get_directories(Y, "", $(container_id), directories_container, add_directory_callback, remove_directory_callback, add_include_directory_callback, remove_include_directory_callback, null)(e);
			    }, '#'+container_id);


		    });

	    }

	}

                   
    };

}();

var add_directory = function(){
    return function(directory_path, parent_directory_is_excluded){
	if(!parent_directory_is_excluded){
	 var directories_box = document.getElementById('wp-backup-plus-additional-exclusions');
	    directories_box.value = directories_box.value.replace(directory_path, "\n");
	    directories_box.value = directories_box.value.replace(directory_path, "\n");
	    directories_box.value = directories_box.value.replace("\n\n", "");
	    directories_box.value=directories_box.value+directory_path+"\n";
	}
    }
}

var remove_directory = function(){
    return function(directory_path){
	//	alert('Removing '+directory_path+' from list of directories and files to exclude');
	var directories_box = document.getElementById('wp-backup-plus-additional-exclusions');
	directories_box.value = directories_box.value.replace(directory_path, "\n");
	directories_box.value = directories_box.value.replace(directory_path, "\n");
	directories_box.value = directories_box.value.replace("\n\n", "");
    }
}

var add_include_directory = function(){
    return function(directory_path, parent_directory_is_excluded){
	if(parent_directory_is_excluded!=null){

	    //	    alert('Adding '+directory_path+' to list of directories and files to always include');
	
	    var directories_box = document.getElementById('wp-backup-plus-additional-inclusions');
	    directories_box.value = directories_box.value.replace(directory_path, "\n");
	    directories_box.value = directories_box.value.replace(directory_path, "\n");
	    directories_box.value=directories_box.value+directory_path+"\n";
	    directories_box.value = directories_box.value.replace("\n\n", "");
	}
    }
}

var remove_include_directory = function(){
    return function(directory_path){
		//	alert('removing '+directory_path+' from list of directories and files to always include');
	var directories_box = document.getElementById('wp-backup-plus-additional-inclusions');
	directories_box.value = directories_box.value.replace(directory_path,"\n");
	directories_box.value = directories_box.value.replace(directory_path,"\n");
	directories_box.value = directories_box.value.replace("\n\n", "");
    }
}


    WPBPFileSystem.init('wpbp_excluded_directories_container', add_directory(), remove_directory(), add_include_directory(), remove_include_directory())(null);
