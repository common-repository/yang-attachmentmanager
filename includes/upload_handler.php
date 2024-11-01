<?php

/**
 * $Id: upload_handler.php 440450 2011-09-19 21:35:57Z haibor $
 * @Description 执行三种上传形式，本文件被需要上传的程序调用
 */

if ( !defined( 'ABSPATH' ) ){ 
	header( 'HTTP/1.1 403 Forbidden', true, 403 );
	die ('Please do not load this page directly. Thanks!');
}

yangam_admin::check_upload_dir();
$die = 0;

### Form Processing
if (__('Add File', 'yang-attachment') == yangam_admin::post('do')){
	// Add File
	//$file_type：0-从附件库，1-上传文件，2-远程文件
	$file_type = intval(yangam_admin::post('file_type'));
	switch ($file_type){
		// files on server
		case 0:
			$data = yangam_admin::add_server_file();
			break;
		// upload local file to server
		case 1:
			$data = yangam_admin::upload_local_file(yangam_admin::post('file_upload_to'));
			break;
		// add remote file
		case 2:
			$data = yangam_admin::add_remote_file(addslashes(trim(yangam_admin::post('file_remote'))), yangam_admin::post('file_save_to'), yangam_admin::post('save_to_local'));
			break;
	} //end inner switch (add file )

	if (!$data){
		$die = 1;
	} else {
		// duplicated file check
		if (yangam_admin::check_duplicate_file(yangam_admin::post('file_type'), $data['file'], $data['file_hash'])){
			$die = 1;
		}
	}


	if (!$die){
		$do_tab = 0;
		$current_file_base_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF'];
		if (basename($current_file_base_name) == 'upload-or-insert.php'){
			$do_tab = 1;
		}
		yangam_admin::add_new_file($data, $do_tab);
	}
}
?>
<?php yangam_admin::show_message_or_error(); ?>
