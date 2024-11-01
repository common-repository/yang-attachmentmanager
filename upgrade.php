<?php
/**
 * $Id: upgrade.php 2012-05-09 17:02:39 haibor $
 * description: 插件数据库升级操作
 */


require( dirname(__FILE__) . '/../../../wp-load.php' );
if (! current_user_can('manage_options') )
	wp_die(__('Permission denied!', yangam::textdomain));

header('Content-type: text/html;charget=UTF-8');

function yang_maybe_add_my_table(){
	global $wpdb, $blog_id;
	$yangam = yangam::instance();
	$yangam->load_textdomain();
	if (@is_file(ABSPATH . '/wp-admin/includes/upgrade.php')){
		include_once (ABSPATH . '/wp-admin/includes/upgrade.php');
	}
	elseif (@is_file(ABSPATH . '/wp-admin/upgrade-functions.php')){
		include_once (ABSPATH . '/wp-admin/upgrade-functions.php');
	} else {
		wp_die(__('We have problem finding your \'/wp-admin/upgrade-functions.php\' and \'/wp-admin/includes/upgrade.php\'', self::textdomain));
	}
	$charset_collate = '';
	if ($wpdb->supports_collation()){
		if (!empty($wpdb->charset)){
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if (!empty($wpdb->collate)){
			$charset_collate .= " COLLATE $wpdb->collate";
		}
	}
	// Create Table
	$create_table = "CREATE TABLE IF NOT EXISTS $wpdb->attachment_post (" . "`file_id` int(11) NOT NULL,`post_id` int(11) NOT NULL DEFAULT 0,KEY `file_id` (`file_id`)) $charset_collate;";
	return maybe_create_table($wpdb->attachment_post, $create_table);
}


if(yang_maybe_add_my_table() )
	echo '<div style="margin:7em auto auto 10em;color:green;">Upgrade Successfully</div>';
else
	die('<div style="margin:7em auto auto 10em;color:red;">Upgrade Failed</div>');

if(isset($_SERVER['HTTP_REFERER'])){
	echo '<div style="margin:7em auto auto 10em;color:green;">Click <a href="'. $_SERVER['HTTP_REFERER'] . '">here</a> go back.</div>';
}
