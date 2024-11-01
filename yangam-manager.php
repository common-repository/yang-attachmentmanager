<?php
/**
 * $Id: yangam-manager.php 2012-05-12 13:04:03 haibor $
 */
### Check Whether User Can Manage Attachments
if (!current_user_can('upload_files')){
	wp_die('Access Denied');
}

//load the admin class
require dirname(__FILE__) . '/includes/yangam_admin.class.php';

//variables 
$base_name = plugin_basename('yang-attachmentmanager/yangam-manager.php');//返回 /wp-content/plugins/
$base_page = 'admin.php?page=' . $base_name;
$mode = trim(yangam_admin::get('mode'));
$file_id = intval(yangam_admin::get('id', 0));

$die = 0;


//Form Processing
if (isset($_POST['do'])){
	switch (yangam_admin::post('do')){
		//action edit update
		case __('Edit File', yangam::textdomain):
			$file_size_sql = '';
			$file_sql = '';
			$file_id = intval(yangam_admin::post('file_id', 0));
			$file_type = intval(yangam_admin::post('file_type', -1));
			//the variable to use
			$file_name = addslashes(trim(yangam_admin::post('file_name')));
			$file_path = yangam_admin::get_opt('yangam_path');
			switch ($file_type){
				// edit orignal file
				case -1:
					$file = yangam_admin::post('old_file');
					if (yangam::is_remote_file($file))
					{
						$file_size = yangam_admin::remote_filesize($file);
						$file_hash = 'N/A';
					}
					else
					{
						$file_size = filesize($file_path . $file);
						$file_hash = yangam_admin::get_file_hash($file_path . $file);
					}
					break;
				// use server file
				case 0:
					$file = addslashes(trim(yangam_admin::post('file')));
					$file = yangam_admin::attachment_rename_file($file_path, $file);
					$file_size = filesize($file_path . $file);
					$file_hash = yangam_admin::get_file_hash($file_path . $file);
					break;
				// upload local file	
				case 1:
					//do edit upload
					$data = yangam_admin::upload_local_file(yangam_admin::post('file_upload_to'), 1);
					if (!$data)
					{
						$die = 1;
					}
					else
					{
						$file_name = $data['file_name'];
						$file = $data['file'];
						$file_size = $data['file_size'];
						$file_hash = $data['file_hash'];
					}
					break;
				// use remote file
				case 2:
					$file = addslashes(trim(yangam_admin::post('file_remote')));
					if (!yangam::is_remote_file($file))
					{
						yangam_admin::add_error(__('Error: Please give me a valid URL.', yangam::textdomain));
						$die = 1;
					}
					else
					{
						$file_name = yangam::get_basename($file);
						$file_size = yangam_admin::remote_filesize($file);
						$file_hash = 'N/A';
					}
					break;
			}
			if (!$die)
			{
				if ($file_type > -1)
				{
					$file_sql = "file = '$file',";
					if (empty($file_name) && isset($_POST['file_name']) && !empty($_POST['file_name']))
					{
						$file_name = addslashes(trim($_POST['file_name']));
					}
				}
				$file_des = addslashes(trim(yangam_admin::post('file_des')));
				$file_hits = intval(yangam_admin::post('file_hits'));
				$edit_filetimestamp = intval(yangam_admin::post('edit_filetimestamp'));
				if (intval(yangam_admin::post('auto_filesize', 0)) != 1)
				{
					$file_size = intval(yangam_admin::post('file_size'));
				}
				$file_size_sql = "file_size = '$file_size',";
				$reset_filehits = intval(yangam_admin::post('reset_filehits'));
				$hits_sql = '';
				if ($reset_filehits == 1)
				{
					$hits_sql = ', file_hits = 0';
				}
				else
				{
					$hits_sql = ", file_hits = $file_hits";
				}
				$timestamp_sql = '';
				if ($edit_filetimestamp == 1)
				{
					$file_timestamp_day = intval(yangam_admin::post('file_timestamp_day'));
					$file_timestamp_month = intval(yangam_admin::post('file_timestamp_month'));
					$file_timestamp_year = intval(yangam_admin::post('file_timestamp_year'));
					$file_timestamp_hour = intval(yangam_admin::post('file_timestamp_hour'));
					$file_timestamp_minute = intval(yangam_admin::post('file_timestamp_minute'));
					$file_timestamp_second = intval(yangam_admin::post('file_timestamp_second'));
					$timestamp_sql = ", file_date = '" . gmmktime($file_timestamp_hour, $file_timestamp_minute, $file_timestamp_second, $file_timestamp_month, $file_timestamp_day, $file_timestamp_year) . "'";
				}
				$file_permission = intval(yangam_admin::post('file_permission'));
				$file_updated_date = current_time('timestamp');
				$editfile = $wpdb->query("UPDATE $wpdb->attachments SET $file_sql file_name = '$file_name', file_des = '$file_des', file_hash = '$file_hash', $file_size_sql file_permission = $file_permission, file_updated_date = '$file_updated_date' $timestamp_sql $hits_sql WHERE file_id = $file_id;");
				if (!$editfile)
				{
					yangam_admin::add_error(sprintf(__('Error In Editing File \'%s (%s)\'', yangam::textdomain), $file_name, $file));
				}
				else
				{
					yangam_admin::add_message(sprintf(__('File \'%s (%s)\' Edited Successfully', yangam::textdomain), $file_name, $file));
				}
			}
			break;
		
		//action delete
		case __('Delete File', yangam::textdomain);
			yangam_admin::delete_file();
			break;
		
		//action trash
		case __('Trash File', yangam::textdomain);
			yangam_admin::trash_file( $file_id );
			break;
	}
}

//回收、还原 直接操作 do='open/trash'，否则使用 mode='open/trash'
if (isset($_GET['do'])){
	switch (yangam_admin::get('do')){
		//action open
		case 'open';
			yangam_admin::trash_or_open( 'open', $file_id );
			break;
		
		//action trash
		case 'trash';
			yangam_admin::trash_or_open( 'trash', $file_id );
			break;
	}
}


// Determines Which Mode It Is
switch ($mode){
	// Edit A File
	case 'edit':
		// check the  file_id to see if the file exists.
		if (!yangam_admin::id_exists($file_id)){
			yangam_admin::add_error(__('Error file_id!File id does not exists.', yangam::textdomain));
			yangam_admin::add_block_error('<p><a href="' . $_SERVER['HTTP_REFERER'] . '" >' . __('Return', yangam::textdomain) . '</a></p>');
			yangam_admin::show_message_or_error();
			exit();
		}
		$file = $wpdb->get_row("SELECT * FROM $wpdb->attachments WHERE file_id = $file_id");
		?>
		<script type="text/javascript">
			/* <![CDATA[*/
			var actual_day = "<?php echo gmdate('j', $file->file_date); ?>";
			var actual_month = "<?php echo gmdate('n', $file->file_date); ?>";
			var actual_year = "<?php echo gmdate('Y', $file->file_date); ?>";
			var actual_hour = "<?php echo gmdate('G', $file->file_date); ?>";
			var actual_minute = "<?php echo intval(gmdate('i', $file->file_date)); ?>";
			var actual_second = "<?php echo intval(gmdate('s', $file->file_date)); ?>";
			function file_usetodaydate() {
				if(jQuery('#edit_usetodaydate').is(':checked')) {
					jQuery('#edit_filetimestamp').attr('checked', true);
					jQuery('#file_timestamp_day').val("<?php echo gmdate('j', current_time('timestamp')); ?>");
					jQuery('#file_timestamp_month').val("<?php echo gmdate('n', current_time('timestamp')); ?>");
					jQuery('#file_timestamp_year').val("<?php echo gmdate('Y', current_time('timestamp')); ?>");
					jQuery('#file_timestamp_hour').val("<?php echo gmdate('G', current_time('timestamp')); ?>");
					jQuery('#file_timestamp_minute').val("<?php echo intval(gmdate('i', current_time('timestamp'))); ?>");
					jQuery('#file_timestamp_second').val("<?php echo intval(gmdate('s', current_time('timestamp'))); ?>");
				} else {
					jQuery('#edit_filetimestamp').attr('checked', false);
					jQuery('#file_timestamp_day').val(actual_day);
					jQuery('#file_timestamp_month').val(actual_month);
					jQuery('#file_timestamp_year').val(actual_year);
					jQuery('#file_timestamp_hour').val(actual_hour);
					jQuery('#file_timestamp_minute').val(actual_minute);
					jQuery('#file_timestamp_second').val(actual_second);
				}
			}
			/* ]]> */
		</script>
		<?php yangam_admin::show_message_or_error(); ?>
		
		<!-- Edit A File -->
		<?php
		//by yang：附件编辑表单；函数形式打印
		//输出测试：string(107) "http://localhost:503/wp-admin/admin.php?page=yang-attachmentmanager/yangam-manager.php&mode=edit&id=36" 
		//var_dump(admin_url('admin.php?page=' . plugin_basename(__FILE__) . '&amp;mode=edit&amp;id=' . intval($file->file_id)));exit;

		yangam_admin::print_attachment_form( admin_url('admin.php?page=' . plugin_basename(__FILE__) . '&amp;mode=edit&amp;id=' . intval($file->file_id)), 1, $file );

		break;
	
	// Delete A File
	case 'delete':
		if (!current_user_can('yang_att_del')){
			wp_die('Access Denied');
		}
		if (!yangam_admin::id_exists($file_id)){
			yangam_admin::add_error(__('Error file_id!File id does not exists.', yangam::textdomain));
			yangam_admin::add_block_error('<p><a href="' . $_SERVER['HTTP_REFERER'] . '" >' . __('Return', yangam::textdomain) . '</a></p>');
			yangam_admin::show_message_or_error();
			exit();
		}
		$file = $wpdb->get_row("SELECT * FROM $wpdb->attachments WHERE file_id = $file_id");
		?>
		<?php yangam_admin::show_message_or_error(); ?>
		
		<!-- Delete A File -->
		<?php
		//by yang：附件编辑表单；函数形式打印
		yangam_admin::print_delete_form( admin_url('admin.php?page=' . plugin_basename(__FILE__)), $file );

		break;
	
	// Main Page. List the files
	default:
		### Get Total Files
		$total_file = $wpdb->get_var("SELECT COUNT(file_id) FROM $wpdb->attachments WHERE 1=1");
		$total_bandwidth = $wpdb->get_var("SELECT SUM(file_hits*file_size) AS total_bandwidth FROM $wpdb->attachments WHERE file_size != '" . __('unknown', yangam::textdomain) . "'");
		$total_filesize = $wpdb->get_var("SELECT SUM(file_size) AS total_filesize FROM $wpdb->attachments WHERE file_size != '" . __('unknown', yangam::textdomain) . "'");
		$total_filehits = $wpdb->get_var("SELECT SUM(file_hits) AS total_filehits FROM $wpdb->attachments");
		
		yangam_admin::show_message_or_error();

		###Manage Attachments
		$attachment_list_action = admin_url('admin.php?page=' . plugin_basename(__FILE__));
		require dirname(__FILE__) . '/includes/attachment_list.php';
		?>
		<p>&nbsp;</p>

		<!-- Download Stats -->
		<div class="wrap">
			<h3><?php _e('Download Stats', yangam::textdomain); ?></h3>
			<br style="" />
			<table class="widefat">
				<tr>
					<th><?php _e('Total Files:', yangam::textdomain); ?></th>
					<td><?php echo number_format_i18n($total_file); ?></td>
				</tr>
				<tr class="alternate">
					<th><?php _e('Total Size:', yangam::textdomain); ?></th>
					<td><?php echo yangam::format_filesize($total_filesize); ?></td>
				</tr>
				<tr>
					<th><?php _e('Total Hits:', yangam::textdomain); ?></th>
					<td><?php echo number_format_i18n($total_filehits); ?></td>
				</tr>
				<tr class="alternate">
					<th><?php _e('Total Bandwidth:', yangam::textdomain); ?></th>
					<td><?php echo yangam::format_filesize($total_bandwidth); ?></td>
				</tr>
			</table>
		</div>
	<?php
} // End switch($mode)
?>
