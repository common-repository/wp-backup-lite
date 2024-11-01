

<style type='text/css'>

#wrap {

	padding:5px;

}

h1 {

	color:#464646;

	font-weight:normal;

}

.menu {

	float:right;

	margin-right:10px;

}

.menu ul {

	float: left;

	margin: 0 0 3em 0;

}

.menu li {

	float:left;

	display:inline;

	margin-right:5px;

}

.form-table {

	border-collapse: collapse;

    clear: both;

    margin-bottom: -8px;

    margin-top: 0.5em;

    width: 100%;

}

.form-table td {

    font-size: 12px;

    line-height: 20px;

    margin-bottom: 9px;

    padding: 8px 10px;

}

.form-table input {

	width:80%;

}

.updated {

	padding:4px !important;

	padding-left:8px !important;

	margin:0px !important;

}
    .rm_wrap{  
        width:740px;  
    }  
    .rm_section{  
        border:1px solid #ddd;  
        border-bottom:0;  
        background:#f9f9f9;  
    }  
    .rm_opts label{  
        font-size:12px;  
        font-weight:700;  
        width:200px;  
        display:block;  
        float:left;  
    }  
    .rm_input {  
        padding:30px 10px;  
        border-bottom:1px solid #ddd;  
        border-top:1px solid #fff;  
    }  
    .rm_opts small{  
        display:block;  
        float:rightright;  
        width:500px;  
        color:#999;  
		margin-left:202px;
		margin-right:auto;
		font-size:11px;
		color:black;
    }  
    .rm_opts input[type="text"], .rm_opts select{  
        width:280px;  
        font-size:12px;  
        padding:4px;  
        color:#333;  
        line-height:1em;  
        background:#f3f3f3;  
    }  
    .rm_input input:focus, .rm_input textarea:focus{  
            background:#fff;  
    }  
    .rm_input textarea{  
        width:500px;  
        height:175px;  
        font-size:12px;  
        padding:4px;  
        color:#333;  
        line-height:1.5em;  
        background:#f3f3f3;  
    }  
    .rm_title h3 {  
        cursor:pointer;  
        font-size:1em;  
        text-transform: uppercase;  
        margin:0;  
        font-weight:bold;  
        color:#232323;  
        float:left;  
        width:80%;  
        padding:14px 4px;  
    }  
    .rm_title{  
        cursor:pointer;  
        border-bottom:1px solid #ddd;  
        background:#eee;  
        padding:0;  
    }  
    .rm_title h3 img.inactive{  
        margin:-8px 10px 0 2px;  
        width:32px;  
        height:32px;  
        background:url('<?php echo plugin_dir_url(__FILE__);?>images/pointer.png') no-repeat 0 0;  
        float:left;  
        -moz-border-radius:6px;  
        border:1px solid #ccc;  
    }  
    .rm_title h3 img.active{  
        margin:-8px 10px 0 2px;  
        width:32px;  
        height:32px;  
        background:url('<?php echo plugin_dir_url(__FILE__);?>images/pointer.png') no-repeat  0 -32px;  
        float:left;  
        -moz-border-radius:6px;  
        -webkit-border-radius:6px;  
        border:1px solid #ccc;  
    }  
    .rm_title h3:hover img{  
        border:1px solid #999;  
    }  
    .rm_title span.submit{  
        display:block;  
        float:rightright;  
        margin:0;  
        padding:0;  
 
        padding:14px 0;  
    }  
    .clearfix{  
        clear:both;  
    }  
    .rm_table th, .rm_table td{  
        border:1px solid #bbb;  
        padding:10px;  
        text-align:center;  
    }  
    .rm_table th, .rm_table td.feature{  
        border-color:#888;  
        }  
	.message {
		margin-top:10px;
		padding:15px;
		border: 1px solid blue;
	}

</style>
<?php
 
if($_POST['license'])
{
	$pp=$_POST['pp'];
	update_option('sl_license',self::check_license($pp));
}

if(get_option('sl_license')!=1 ) {
   if($_POST['license'])
		echo' <div id="message" class="updated fade"><p><strong>The email is not a valid license or you have exceeded your domain limit.</strong></p></div>';
	echo'

 <div class="wrap rm_wrap">    
<h2><img src="'.plugin_dir_url(__FILE__).'../../../images/logo.png" class="inactive" alt=""></h2><br/>
  
<div class="rm_opts">  
<form method="post">  
  <div class="rm_section">  
	<div class="rm_title"><h3>License Settings</h3><span class="submit"><input name="license" type="submit" value="Update License!" />  
	</span><div class="clearfix"></div></div>  
	<div class="rm_options">  
	
	<div class="rm_input rm_text">  
		<label for="url">License(paypal email)</label>  
		<input name="pp" type="text" size=\'30\' value="" style="border-color:red;" />  
		<small>Please enter the paypal email you\'ve bought the plugin with in order to use it.</small><div class="clearfix"></div>  
	</div> 
';


}