<?php
/**
 * $Id: yangam-add.php 2012-05-12 13:04:27 haibor $
 * @Description �ϴ�����¸���
 */

### Check Whether User Can Manage Attachments
if (!current_user_can('yang_att_add')){
	//wp_die('Access Denied');
	wp_die(__('You do not have permission to upload files.', yangam::textdomain));
}

require dirname(__FILE__) . '/includes/yangam_admin.class.php';

require dirname(__FILE__) . '/includes/upload_handler.php';

//by yang�������ϴ������ϲ��������༭�������Ӳ��� $mode=0
yangam_admin::print_attachment_form( admin_url('admin.php?page=' . plugin_basename(__FILE__)), 0 );

?>