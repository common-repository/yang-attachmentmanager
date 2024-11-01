<?php
/**
 * $Id: uninstall.php 2012-05-09 17:01:14 haibor $
 * description: 卸载插件，清理数据库信息
 **/


### Check Whether User Can Manage Attachments
if (!current_user_can('yang_att_manage')){
	//die('Access Denied');
	wp_die(__('Oh NO! You do not have permission to access this page.', yangam::textdomain));
}

//插件列表“删除”按钮，直接调用本卸载程序
if( !class_exists('yangam') ){
	require dirname(__FILE__). '/includes/yangam.class.php';
}

//load the admin class
require dirname(__FILE__). '/includes/yangam_admin.class.php';

### Variables Variables Variables
$base_name = plugin_basename('yang-attachmentmanager/yangam-manager.php');
$base_page = 'admin.php?page='.$base_name;
$mode = trim( yangam_admin::get('mode'));

$attachments_tables = array($wpdb->attachments);//获取附件数据库表
$attachments_settings1 = yangam::get_opt_keys(1);//获取数据库表 wp_options 里的插件设置信息
$attachments_settings2 = yangam::get_opt_keys(2);//获取数据库表 wp_postmeta 里的文件附加到信息
$attachments_settings3 = yangam::get_opt_keys(3);//获取数据库表 wp_options 里的角色权限设置信息



### Form Processing
if(isset($_POST['do'])) {
	// Decide What To Do
	switch( yangam_admin::post('do')) 
			{
		//  Uninstall yang-attachment
		case __('Uninstall yang-attachment', yangam::textdomain) :
			if(trim(yangam_admin::post('uninstall_attachment_yes')) == 'yes') {
				echo '<div id="message" class="updated fade">';
				
				//清理配置信息：删除附件保存的数据表
				echo '<p>';
				foreach($attachments_tables as $table) {
					$wpdb->query("DROP TABLE {$table}");
					echo '<span style="color: green;">';
					printf(__('Table \'%s\' has been deleted.', yangam::textdomain), "<strong><em>{$table}</em></strong>");
					echo '</span><br />';
				}
				echo '</p>';
				
				//清理配置信息：清理数据库表 wp_options 里的插件设置信息
				echo '<p>';
				foreach($attachments_settings1 as $setting) {
					$delete_setting = delete_option($setting);
					if($delete_setting) {
						echo '<span style="color:green;">';
						printf(__('Setting Key \'%s\' has been deleted.', yangam::textdomain), "<strong><em>{$setting}</em></strong>");
						echo '</span><br />';
					} else {
						echo '<span style="color:red;">';
						printf(__('Error deleting Setting Key \'%s\'.', yangam::textdomain), "<strong><em>{$setting}</em></strong>");
						echo '</span><br />';
					}
				}
				echo '</p>';
				
				//清理配置信息：清理数据库表 wp_options 里的角色权限信息
				echo '<p>';
				foreach($attachments_settings1 as $setting) {
					$roles = array('administrator', 'editor', 'author');
					foreach ($roles as $role) {
						$role = get_role($role);
						$role->remove_cap( $setting );
					}
					echo '<span style="color: green;">';
					printf(__('Setting Key \'%s\' has been deleted.', yangam::textdomain), "<strong><em>{$setting}</em></strong>");
					echo '</span><br />';
				}
				echo '</p>';
				
				//清理配置信息：清理数据库表 wp_postmeta 里的“附加到文章的附件”信息
				echo '<p>';
				foreach($attachments_settings2 as $setting) {
					$wpdb->query("DELETE FROM ".$wpdb->prefix.'postmeta'." WHERE meta_key = '$setting';");
					echo '<span style="color: green;">';
					printf(__('Setting Key \'%s\' has been deleted.', yangam::textdomain), "<strong><em>{$setting}</em></strong>");
					echo '</span><br />';
				}
				echo '</p>';

				//保留插件上传的附件，可手动删除
				echo '<p style="color: blue;">';
				_e('The files uploaded by yang-attachment <strong>WILL NOT</strong> be deleted. You will have to delete it manually.',  yangam::textdomain);
				echo '<br />';
				printf(__('The path to the attachments folder is <strong>\'%s\'</strong>.',  yangam::textdomain), yangam_admin::get_opt('yangam_path') );
				echo '</p>';
				echo '</div>';
				$mode = 'end-UNINSTALL';//Deactivating Plugins 停用插件
			}
			break;
	}
}


### Determines Which Mode It Is
switch($mode) {
	//Deactivating Plugins 停用插件
	case 'end-UNINSTALL':
		flush_rewrite_rules();
		$deactivate_url = 'plugins.php?action=deactivate&amp;plugin=yang-attachmentmanager/yang-am.php';
		if(function_exists('wp_nonce_url')) {
			$deactivate_url = wp_nonce_url($deactivate_url, 'deactivate-plugin_yang-attachmentmanager/yang-am.php');
		}
		echo '<div class="wrap">';
		echo '<div id="icon-attachment" class="icon32"><br /></div>';
		echo '<h2>'.__('Uninstall yang-attachment', yangam::textdomain).'</h2>';
		echo '<p><strong>'.sprintf(__('Uninstall successfully! <a href="%s">Click Here</a> To Finish The Uninstallation And yang-attachment Will Be Deactivated Automatically.', yangam::textdomain), $deactivate_url).'</strong></p>';
		echo '</div>';
		break;
		// Main Page
	default:
		?>
<!-- Uninstall yang-attachment -->
<form method="post" action="<?php echo admin_url('admin.php?page='.plugin_basename(__FILE__)); ?>">
	<div class="wrap">
		<div id="icon-attachment" class="icon32"><br /></div>
		<h2><?php _e('Uninstall yang-attachment',  yangam::textdomain); ?></h2>
		<p><?php _e('Deactivating yang-attachment plugin does not remove any data that may have been created, such as the attachment options and the download data. To completely remove this plugin, you can uninstall it here.',  yangam::textdomain); ?>
		</p>	
		<p style="color: red"><strong><?php _e('NOTE:',  yangam::textdomain); ?></strong><br />
				<?php _e('The download files uploaded by yang-attachment <strong>WILL NOT</strong> be deleted. You will have to delete it manually.',  yangam::textdomain); ?><br />
				<?php printf(__('The path to the attachments folder is <strong>\'%s\'</strong>.', yangam::textdomain), yangam_admin::get_opt('yangam_path') ); ?>
		</p>
		<p style="color: red"><strong><?php _e('WARNING:', 'yang-attachment'); ?></strong><br />
			<?php _e('Once uninstalled, this cannot be undone. You should use a Database Backup plugin of WordPress to back up all the data first.',  yangam::textdomain); ?></p>
		<p style="color: red"><strong><?php _e('The following WordPress Options/Tables will be DELETED:',  yangam::textdomain); ?></strong><br /></p>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php _e('WordPress Options', yangam::textdomain); ?></th>
					<th><strong><?php _e('WordPress Tables',  yangam::textdomain); ?></strong></th>
					<th><strong><?php _e('File attached to',  yangam::textdomain); ?></strong></th>
				</tr>
			</thead>
			<tr>
				<td valign="top">
					<ol>
						<?php
						foreach($attachments_settings1 as $settings) {
							echo '<li>'.$settings.'</li>'."\n";
						}
						?>
					</ol>
				</td>
				<td valign="top" class="alternate">
					<ol>
						<?php
						foreach($attachments_tables as $tables) {
							echo '<li>'.$tables.'</li>'."\n";
						}
						?>
					</ol>
				</td>
				<td valign="top">
					<ol>
						<?php
						foreach($attachments_settings2 as $settings) {
							echo '<li>'.$settings.'</li>'."\n";
						}
						?>
					</ol>
				</td>
			</tr>
		</table>
		<p>&nbsp;</p>
		<p style="text-align: center;">
			<input type="checkbox" name="uninstall_attachment_yes" value="yes" />&nbsp;<?php _e('Yes', yangam::textdomain); ?><br /><br />
			<input type="submit" name="do"
				value="<?php _e('Uninstall yang-attachment',  yangam::textdomain); ?>"
				class="button"
				onclick="return confirm('<?php _e('You Are About To Uninstall yang-attachment From WordPress.\nThis Action Is Not Reversible.\n\n Choose [Cancel] To Stop, [OK] To Uninstall.',  yangam::textdomain); ?>')" />
		</p>
	</div>
</form>
		<?php
} // End switch($mode)
?>
