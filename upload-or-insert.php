<?php
/**
 * $Id: upload-or-insert.php 2012-05-12 13:04:35 haibor $
 */

/** Load WordPress Administration Bootstrap */
define( 'IFRAME_REQUEST' , true );
$bootstrap_file = dirname(dirname(dirname(dirname(__FILE__)))). '/wp-admin/admin.php' ;
if (file_exists( $bootstrap_file )){
	require $bootstrap_file;
} else {
	echo '<p>Failed to load bootstrap.</p>';
	exit;
}


/*Check Whether User Can upload_files*/
if (!current_user_can('upload_files')){
	wp_die(__('You do not have permission to upload files.'));
}

//enqueue the needed media stylesheet
wp_enqueue_style('media');

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

require dirname(__FILE__) . '/includes/yangam_admin.class.php';
$GLOBALS['body_id'] = 'media-upload';
iframe_header( __('Yang AttachmentManager',yangam::textdomain), false );
?>


<script type="text/javascript">
	/* <![CDATA[ */
	//by yang:插入下载文件
	function insert_into_post_down(file_id){
		var win = window.dialogArguments || opener || parent || top;
		win.send_to_editor('[download id="'+file_id+'"]'+"\n\r");//jquery方法
	};
	//by yang:插入显示图片
	function insert_into_post_pic(file_id,file_name){
		var win = window.dialogArguments || opener || parent || top;
		win.send_to_editor('<img src="<?=get_option('yangam_path_url')?>/'+file_name+'" id="yang-attachment_'+file_id+'">'+"\n\r");//jquery方法
	};
	/* ]]> */
</script>

<?php
// IDs should be integers
$post_id = isset( $_REQUEST['post_id'] )? intval( $_REQUEST['post_id'] ) : 0;
?>

<div id="media-upload-header">
	<ul id='sidemenu'>
		<li id='tab-type'><a href='upload-or-insert.php?post_id=<?php echo $post_id;?>&tab=upload' <?php if ($_GET['tab'] == 'upload')
			echo "class='current'"; ?>><?php _e('upload a New File',  yangam::textdomain); ?></a></li>
		<li id='tab-library'><a href='upload-or-insert.php?post_id=<?php echo $post_id;?>&tab=attachments' <?php if ($_GET['tab'] == 'attachments')
			echo "class='current'"; ?>><?php _e('View Attachment', yangam::textdomain); ?></a></li>
		
		<?php if( yangam_admin::post_has_att($post_id) ):?>
		<li id='tab-download'><a href='upload-or-insert.php?post_id=<?php echo $post_id;?>&tab=post-attachments' <?php if ($_GET['tab'] == 'post-attachments')
			echo "class='current'"; ?>><?php _e('Post Attachment',  yangam::textdomain); ?></a></li>
		<?php endif;?>
	</ul>
</div>

	<?php
		// Get the Tab
		$tab = yangam_admin::get('tab');
		switch ($tab)
		{
			//上传新文件
			case 'upload' :
				//Form Processing		
				require dirname(__FILE__) . '/includes/upload_handler.php';
				
				//by yang：附件上传表单，合并到附件编辑表单，增加参数 $mode=0
				yangam_admin::print_attachment_form('upload-or-insert.php?tab=upload&post_id='. $post_id);
				?>

				<script type="text/javascript">
					/** JQuery插入，需要定义$GLOBALS['insert_shortcode_down'] 和 $GLOBALS['insert_shortcode_pic']
					  * 如：$GLOBALS['insert_shortcode_down'] = '[download id="' . $wpdb->insert_id . '"';
					  * $GLOBALS['insert_shortcode_pic'] = '<img src="'.get_option('yangam_path_url').$file.'" id="yang-attachment_'.$wpdb->insert_id.'">';
					  * <input>标签需要定义id：id="insert_down" 和 id="insert_pic"
					*/
					/* <![CDATA[ */
					/* //by yang 上传后，点击插入文件
					jQuery('#insert_down').click(function(){
						var win = window.dialogArguments || opener || parent || top;
						win.send_to_editor('<?php echo isset($GLOBALS['insert_shortcode_down']) ? $GLOBALS['insert_shortcode_down'] : ''; ?>]'+"\n\r");
					});
					//by yang 上传后，插入图片
					jQuery('#insert_pic').click(function(){
						var win = window.dialogArguments || opener || parent || top;
						win.send_to_editor('<?php echo isset($GLOBALS['insert_shortcode_pic']) ? $GLOBALS['insert_shortcode_pic'] : ''; ?>'+"\n\r");
					});*/
					/* ]]> */
				</script>

				<?php
				break;

			//查看附件库列表
			case 'attachments' :
				// Show table of attachments	
				$base_page = 'upload-or-insert.php?tab=attachments&post_id='. $post_id;
				
				require dirname(__FILE__). '/includes/attachment_list.php';
				break;
			
			//文章嵌入的附件
			case 'post-attachments' :
				// Show table of attachments	
				$base_page = 'upload-or-insert.php?tab=post-attachments&post_id='. $post_id;
				
				//by yang：文章嵌入的附件，写成函数形式
				$post_id = isset( $_REQUEST['post_id'] )? intval( $_REQUEST['post_id'] ) : 0;
				yangam_admin::print_post_attachment($post_id);
				
				break;
		}
	?>
				
<?php
iframe_footer();
?>