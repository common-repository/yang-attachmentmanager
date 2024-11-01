<?php

/**
 * $Id: yangam.class.php 2012-05-12 12:58:45 haibor $
 */
if (!defined('ABSPATH')){
	header('HTTP/1.1 403 Forbidden', true, 403);
	die('Please do not load this page directly. Thanks!');
}

/**
 * class for front
 */
class yangam{
	const method_redirect = 0;
	const method_force = 1;
	const display_type_default = 0;
	const display_type_popup = 1;
	const textdomain = 'yang-attachment';
	const version = '0.1.0';
	private static $_instance = null;
	private static $_singular_only = TRUE;
	private static $_add_js = FALSE;
	private $_opts = array(
		'yangam_path' => '',
		'yangam_path_url' => '',
		'yangam_method' => 0,
		'yangam_template_embedded' => array('', ''),
		'yangam_options' => array(
			'yam_permalink_style' => 0,
			'yam_permalink_structure' => '/getfile/%file_id%',
			//'yam_attachment_slug' => 'getfile',//by yang：去掉 yam_attachment_slug
			//'yam_nice_permalink' => 0,//by yang：去掉 yam_nice_permalink
			'yam_time_limit' => 480,
			'yam_hash_func' => 'md5',
			'yam_check_referer' => 1
		),
		'yangam_template_custom_css' => '',
		'yangam_template_popup' => array('', ''),
		'yangam_display_type' => 0,
	);

	function __construct(){
		//attachments Table Name
		global $wpdb;
		$wpdb->attachments = $wpdb->prefix . 'attachments';//by yang 更换成 attachment
		/**
		 * the post which download attached to
		 * @since 2.2
		 */
		$wpdb->attachment_post = $wpdb->prefix . 'attachment_post';//by yang 逐步合并到 attachment
		$all_keys = self::get_opt_keys();
		foreach ((array) $all_keys as $key)
		{
			$this->_opts[$key] = get_option($key);
		}
	}

	/**
	 * singleton
	 * instanceof 用于确定一个 PHP 变量是否属于某一类 class 的实例 http://www.php.net/instanceof
	 * @return type
	 */
	public static function instance(){
		if (!(self::$_instance instanceof yangam)){
			self::$_instance = new yangam();//实例化类
		}
		return self::$_instance;
	}

	public static function init(){
		$yangam = yangam::instance();
		// Create text domain for translations
		add_action('init', 'yangam::load_textdomain');

		//installation安装启用插件时执行操作，在yang-am.php文件中执行函数
		add_action('activate_yang-attachmentmanager/yang-am.php', array($yangam, 'create_attachment_table'));

		// add plugin "Settings" action on plugin list , the plugin_basename function must get the parent __FILE__
		add_action('plugin_action_links_' . plugin_basename(YANGAM_LOADER), 'yangam::add_plugin_actions');
		//add menu
		add_action('admin_menu', array($yangam, 'attachments_menu'));
		//add admin css
		add_action('admin_print_styles', 'yangam::stylesheets_admin');
		add_action('admin_print_styles', array($yangam, 'enqueue_backend_css'));
		add_action('admin_print_footer_scripts', array($yangam, 'print_backend_js'));
		//add footer js
		add_action('admin_footer-post-new.php', 'yangam::footer_admin_js');
		add_action('admin_footer-post.php', 'yangam::footer_admin_js');
		add_action('admin_footer-page-new.php', 'yangam::footer_admin_js');
		add_action('admin_footer-page.php', 'yangam::footer_admin_js');
		// add editor button
		add_action('media_buttons', array($yangam, 'add_media_button'), 20);
		add_action('init', array($yangam, 'tinymce_addbuttons'));

		//add rewrite rule
		add_filter('query_vars', 'yangam::add_attachment_query_vars');
		add_filter('generate_rewrite_rules', array($yangam, 'attachment_rewrite_rule'));
		// do sutff
		add_action('template_redirect', array($yangam, 'download_file'), 5);

		add_filter('favorite_actions', 'yangam::favorite_actions');

		//add the shortcode
		add_shortcode('download', array($yangam, 'attachment_shortcode'));


		//register the js first
		add_action('init', 'yangam::register_front_js');
		add_action('wp_footer', array($yangam, 'print_front_js'));
		/*
		 * add popup effect css
		 * register with hook 'wp_print_styles'
		 */
		add_action('wp_print_styles', array($yangam, 'enqueue_css'), -999);
		/**
		 * add user custom css
		 * this ensure our custom css can override the default one
		 */
		add_action('wp_head', array($yangam, 'print_custom_stylesheet'), 999);
		
		//for create table attachment_post
		//add_action('admin_notices', array(__CLASS__, 'check_table'));//全局提示，插件升级提示
		
		//when post deleted,deattach the relationship
		//add_action('trash_post',array($yangam, 'deattach_post'));//文章加入回收站时，执行函数 deattach_post()
		add_action('deleted_post',array($yangam, 'deattach_post'));//删除文章后，执行函数 deattach_post()
		add_action('save_post', array($yangam, 'update_post_attachinfo'));//保存文章时，执行函数：update_post_attachinfo()
	}

	/**插件升级提示
	 * check table attachment_post exists or not
	 * @global type $wpdb 
	 */
	public static function check_table(){
		global $wpdb;
		if (!$wpdb->query("show columns from $wpdb->attachment_post")){
			echo '<div class="error"><p><strong>';
			echo sprintf(__('Yang AttachmentManager : Please click <a href="%s">here</a> to upgrade the plugin!'), WP_PLUGIN_URL . '/yang-attachmentmanager/upgrade.php');
			echo '</strong></p></div>';
		}
	}
	
	/**
	 * by yang:获取该插件在保存在数据库里的信息，以备卸载插件时使用
	 * 参数：
	 *		$t：数据库表
	 *			1-wp_options表设置信息，返回 option_name 字段
	 *			2-wp_postmeta表附加到信息，返回 meta_key 字段
	 *			3-wp_options表角色权限信息，返回 option_name 字段
	 **/
	public static function get_opt_keys( $t=1 ){
		if ( is_numeric($t) ){
			if( $t==1 ){
				$keys = array(
					'yangam_path',
					'yangam_path_url',
					'yangam_method',
					'yangam_template_embedded',
					'yangam_options',
					'yangam_template_custom_css',
					'yangam_template_popup',
					'yangam_display_type',
				);
			}
			elseif( $t==2 ){
				$keys = array(
					'yang_attached_id',
				);
			}
			elseif( $t==3 ){
				$keys = array(
					'yang_att_manage',
					'yang_att_add',
					'yang_att_del',
					'yang_att_trash',
				);
			}
		}
		return $keys;
	}

	//读取默认设置
	public static function get_default_value($key){
		$ret = null;
		switch ($key){
			case 'yangam_template_embedded':
				$ret = array('<p><img src="' . plugins_url('yang-attachmentmanager/images/ext') . '/%FILE_ICON%" alt="download icon" style="vertical-align: middle;" />&nbsp;&nbsp;<strong><a href="%FILE_ATTACHMENT_URL%">%FILE_NAME%</a></strong> (%FILE_SIZE%' . __(',', self::textdomain) . ' %FILE_HITS% ' . __('hits', self::textdomain) . ')</p>',
					'<p><img src="' . plugins_url('yang-attachmentmanager/images/ext') . '/%FILE_ICON%" alt="download icon" style="vertical-align: middle;" />&nbsp;&nbsp;<strong>%FILE_NAME%</strong> (%FILE_SIZE%' . __(',', self::textdomain) . ' %FILE_HITS% ' . __('hits', self::textdomain) . ')<br /><i>' . __('You do not have permission to download this file.', self::textdomain) . '</i></p>');
				break;
			case 'yangam_options':
				
				//by yang：去掉yam_nice_permalink、yam_attachment_slug
				$ret = array('yam_permalink_style' => 0, 'yam_permalink_structure' => '', 'yam_time_limit' => 300, 'yam_hash_func' => 'md5', 'yam_check_referer' => 1);
				
				//$ret = array('yam_permalink_style' => 0, 'yam_permalink_structure' => '', 'yam_attachment_slug' => 'getfile', 'yam_nice_permalink' => 0, 'yam_time_limit' => 300, 'yam_hash_func' => 'md5', 'yam_check_referer' => 1);
				break;
			case 'yangam_template_custom_css':
				$ret = '.yangattachment_downlinks{width:500px}.yangattachment_down_link{margin-top:10px;background:#e0e2e4;border:1px solid #330;color:#222;padding:5px 5px 5px 20px}.yangattachment_down_link a{color:#57d}.yangattachment_views{color:red}.yangattachment_box{border-bottom:1px solid #aaa;padding:10px 0}.yangattachment_box_content{line-height:18px;padding:0 0 0 10px}.yangattachment_box_content p{margin:5px 0}.yangattachment_box_content a{color:#d54e21}.yangattachment_box_content a:hover{color:#1d1d1d}.yangattachment_left{float:left;width:320px}.yangattachment_right{width:160px;float:right;margin:0 auto}.yangattachment_right img{max-width:160px}.yangattachment_notice{padding-top:10px;text-align:center}#facebox .content{width:600px;background:none repeat scroll 0 0 #e0e2e4;color:#333}#facebox .popup{width:620px;border:6px solid #444}';
				break;
			case 'yangam_template_popup':
				$ret = array(
'<div id="yang_attachment_list%FILE_ID%" style="display:none;">
	<div class="yangattachment_box">
		<strong>' . __('Download statement', self::textdomain) . '：</strong>
		<div class="yangattachment_box_content">
			' . __('statement demo', self::textdomain) . '
		</div>
	</div>	
	<div class="yangattachment_box">
		<strong>' . __('File info', self::textdomain) . '：</strong>
		<div class="yangattachment_box_content">
			<div class="yangattachment_left">
				<p>' . __('File name', self::textdomain) . '：<img src="' . plugins_url('yang-attachmentmanager/images/ext') . '/%FILE_ICON%" alt="" title="" style="vertical-align: middle;" />&nbsp;&nbsp;%FILE_NAME% </p>
				<p>' . __('File hash', self::textdomain) . '：%FILE_HASH%</p>
				<p>' . __('File size', self::textdomain) . '：%FILE_SIZE%</p>
				<p>' . __('File uploaded', self::textdomain) . '：%FILE_DATE%</p>
				<p>' . __('File updated', self::textdomain) . '：%FILE_UPDATED_DATE%</p>
				<p>' . __('File description', self::textdomain) . '：%FILE_DESCRIPTION%</p>
			</div>
			<div class="yangattachment_right">
				<strong>' . __('Download URL', self::textdomain) . '：</strong><a href="%FILE_ATTACHMENT_URL%" title="download %FILE_NAME%"><img style="vertical-align: middle;" src="' . plugins_url('yang-attachmentmanager/images') . '/download.gif" alt="download"/></a>
			</div>
		</div>
		<div style="clear:both"></div>
	</div>
	<div class="yangattachment_notice">
		<span style="">' . __('Other notice', self::textdomain) . '</span>
	</div>
</div><!-- end yang_attachment_list%FILE_ID% -->

<div class="yangattachment_down_link"><img src="' . plugins_url('yang-attachmentmanager/images/ext') . '/%FILE_ICON%" alt="download icon" style="vertical-align: middle;" />&nbsp;&nbsp;<span class="yangattachment_filename">%FILE_NAME%</span>&nbsp;&nbsp;<strong><a rel="facebox" href="#yang_attachment_list%FILE_ID%" title="download %FILE_NAME%">' . __('Download', 'yang-attachment') . '</a></strong> (%FILE_SIZE%, %FILE_HITS% ' . __('hits', self::textdomain) . ')</div>
',
'<p><img src="' . plugins_url('yang-attachmentmanager/images/ext') . '/%FILE_ICON%" alt="download icon" style="vertical-align: middle;" />&nbsp;&nbsp;<strong>%FILE_NAME%</strong> (%FILE_SIZE%' . __(',', self::textdomain) . ' %FILE_HITS% ' . __('hits', self::textdomain) . ')<br /><i>' . __('You do not have permission to download this file.', self::textdomain) . '</i></p>'
					/*'<div id="yang_attachment_list%FILE_ID%" style="display:none;">
						<div class="yangattachment_box">
							<strong>' . __('Download statement', self::textdomain) . '：</strong>
							<div class="yangattachment_box_content">
								<p>
								1. ' . __('Download statement', self::textdomain) . ' 1
								</p>
								<p>
								2. ' . __('Download statement', self::textdomain) . ' 2
								</p>
							</div>
						</div>	
						<div class="yangattachment_box">
							<strong>' . __('File info', self::textdomain) . '：</strong>
							<div class="yangattachment_box_content">
								<div class="yangattachment_left">
									<p>' . __('File name', self::textdomain) . '：<img src="' . plugins_url('yang-attachmentmanager/images/ext') . '/%FILE_ICON%" alt="" title="" style="vertical-align: middle;" />&nbsp;&nbsp;%FILE_NAME% </p>
									<p>' . __('File hash', self::textdomain) . '：%FILE_HASH%</p>
									<p>' . __('File size', self::textdomain) . '：%FILE_SIZE%</p>
									<p>' . __('File uploaded', self::textdomain) . '：%FILE_DATE%</p>
									<p>' . __('File updated', self::textdomain) . '：%FILE_UPDATED_DATE%</p>
									<p>' . __('File description', self::textdomain) . '：%FILE_DESCRIPTION%</p>
								</div>
								
								<div class="yangattachment_right">
								<strong>' . __('Download URL', self::textdomain) . '：</strong><a href="%FILE_ATTACHMENT_URL%" title="download %FILE_NAME%"><img style="vertical-align: middle;" src="' . plugins_url('yang-attachmentmanager/images') . '/download.png" alt="download"/></a>
								</div>
							</div>
							<div style="clear:both"></div>
						</div>
						<div class="yangattachment_notice">
							<span style="color:#f00;">' . __('Other notice', self::textdomain) . '</span>
						</div>
					</div><!-- end yang_attachment_list%FILE_ID% -->

					<div class="yangattachment_down_link"><img src="' . plugins_url('yang-attachmentmanager/images/ext') . '/%FILE_ICON%" alt="download icon" style="vertical-align: middle;" />&nbsp;&nbsp;<span class="yangattachment_filename">%FILE_NAME%</span>&nbsp;&nbsp;<strong><a rel="facebox" href="#yang_attachment_list%FILE_ID%" title="download %FILE_NAME%">' . __('Download', 'yang-attachment') . '</a></strong> (%FILE_SIZE%, %FILE_HITS% ' . __('hits', self::textdomain) . ')</div>
					',
					'<p><img src="' . plugins_url('yang-attachmentmanager/images/ext') . '/%FILE_ICON%" alt="download icon" style="vertical-align: middle;" />&nbsp;&nbsp;<strong>%FILE_NAME%</strong> (%FILE_SIZE%' . __(',', self::textdomain) . ' %FILE_HITS% ' . __('hits', self::textdomain) . ')<br /><i>' . __('You do not have permission to download this file.', self::textdomain) . '</i></p>'*/
				);
		}
		return $ret;
	}

	//Gets the URL (with trailing slash) for the plugin __FILE__ passed in. http://codex.wordpress.org/Function_Reference/plugin_dir_url
	public static function plugin_dir_url(){
		return plugin_dir_url(dirname(__FILE__));
	}

	//读取设置信息，可设默认值
	public function get_opt($name, $default = ''){
		$ret = null;
		$ret = !empty($this->_opts[$name]) ? $this->_opts[$name] : $default;
		return $ret;
	}

	//检查是否头部已经发送。这可能会产生其他的WordPress插件的PHP错误消息。
	//check if the header already sent.This may be PHP error messages genareated by other WordPress plugins.
	public static function check_headers_sent(){
		if (ob_get_length() > 0){
			wp_die(__('Error: Content already sent! Please contact the site administrator to solve this problem.', self::textdomain));
		}
		if (headers_sent($file, $line)){
			if (WP_DEBUG){
				wp_die('Error: header already sent in file <strong>' . $file . '</strong> line <strong>' . $line . '</strong>.Please check your server configure or contact the administrator.');
			} else {
				wp_die(__('Error: header already sent! Please contact the site administrator to solve this problem.', self::textdomain));
			}
		}
	}

	public function enqueue_css(){
		if (self::$_singular_only && !is_singular()){
			return;
		}
		$display_type = $this->get_opt('yangam_display_type', 0);
		if ($display_type){
			wp_enqueue_style('facebox', self::plugin_dir_url() . 'js/facebox/facebox.css.php');
		}
	}

	//print custom css
	public function print_custom_stylesheet(){
		if (self::$_singular_only && !is_singular())
		{
			return;
		}
		$css = $this->get_opt('yangam_template_custom_css');
		if (!empty($css))
		{
			echo '<style type="text/css">';
			echo $css;
			echo '</style>';
		}
	}

	//removed the jquery dependencies,checked in print_front_js
	public static function register_front_js(){
		wp_register_script('facebox', self::plugin_dir_url() . 'js/facebox/facebox.js.php', array(), '1.2', TRUE);
	}

	public function print_front_js(){
		if (self::$_singular_only && !is_singular())
		{
			return;
		}
		$display_type = $this->get_opt('yangam_display_type', 0);
		if (self::$_add_js && $display_type)
		{
			//if jQuery not enqueued by WP standard wp_enqueue_method yet,try to load the js ...
			global $wp_scripts;
			$handle = 'jquery';
			if (($wp_scripts instanceof WP_Scripts) && (!in_array($handle, $wp_scripts->done) || !$wp_scripts->query($handle)))
			{
				$jq_url = site_url('wp-includes/js/jquery/jquery.js');
				echo "\n<script type=\"text/javascript\">";
				echo "!window.jQuery && document.write('<script src=\"{$jq_url}\" type=\"text/javascript\"><\/script>');";
				echo "</script>\n";
			}
			wp_print_scripts('facebox');
		}
	}

	public function enqueue_backend_css(){
		wp_enqueue_style('jquery-filetree', self::plugin_dir_url() . 'js/jqueryFileTree/jqueryFileTree.css');
	}

	public function print_backend_js(){
		//SCRIPT_NAME  for upload-or-insert.php
		$current_script = isset($_GET['page']) && !empty($_GET['page']) ? $_GET['page'] : $_SERVER['SCRIPT_NAME'];
		// only load the script when needed.
		if (in_array(basename($current_script), array('yangam-add.php', 'yangam-manager.php', 'upload-or-insert.php'))){
			wp_enqueue_script('jquery');
			//wp_enqueue_script('jquery-filetree', self::plugin_dir_url() . 'js/jqueryFileTree/jqueryFileTree.js', array('jquery'), '1.0.1', TRUE);
			$connector = plugins_url('yang-attachmentmanager/js/jqueryFileTree/jqueryFileTree.php');
			$jquery_filetree_js_src = self::plugin_dir_url() . 'js/jqueryFileTree/jqueryFileTree.js';
			echo "<script type='text/javascript'>
			jQuery(function($) {
				$.getScript('$jquery_filetree_js_src',
				function()
				{
					$('#yangam-filetree').fileTree({ root: '/', script: '$connector', folderEvent: 'click', expandSpeed: 10, collapseSpeed: 100, multiFolder: true }, function(file) {
						$('#yangam-filetree-file').val(file);
						$('#yangam-filetree').slideToggle('fast');
					});
				});

				$('#yangam-filetree-button').click(function(){
					$('#yangam-filetree').slideToggle(100);
				});
			});
		</script>
			";
		}
	}

	/**
	 * Enqueue attachments Stylesheets In WP-Admin
	 * @see http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	 * @see http://codex.wordpress.org/Plugin_API/Action_Reference/admin_print_styles
	 */
	public static function stylesheets_admin(){
		wp_enqueue_style('yangam-style', plugins_url('yang-attachmentmanager/yangam-style.css'), false, '2.0.0', 'all');
	}

	/**
	 * TinyACE编辑器扩展“插入下载附件”按钮
	 * Displays Download Manager Footer  js In WP-Admin
	 * js_escape is deprecated in wp 2.8
	 * this code use the new Quicktags API function,@see quicktags.dev.js line 274
	 * TinyACE编辑器添加自定义按钮 http://www.nuodou.com/a/599.html
	 **/
	public static function footer_admin_js(){
		echo '<script type="text/javascript">' . "\n";
		echo "\t" . 'var attachmentsEdL10n = {' . "\n";
		echo "\t\t" . 'enter_attachment_id: "' . esc_js(__('Enter File ID (Separate Multiple IDs By A Comma)', self::textdomain)) . '",' . "\n";
		echo "\t\t" . 'download: "' . esc_js(__('Download', self::textdomain)) . '",' . "\n";
		echo "\t\t" . 'insert_download: "' . esc_js(__('Insert File Download', self::textdomain)) . '",' . "\n";
		echo "\t" . '};' . "\n";
		//插入下载文件
		echo "\t" . 'function insertDownload(where) {' . "\n";
		echo "\t\t" . 'var attachment_id = jQuery.trim(prompt(attachmentsEdL10n.enter_attachment_id));' . "\n";
		echo "\t\t" . 'if(attachment_id == null || attachment_id == "") {' . "\n";
		echo "\t\t\t" . 'return;' . "\n";
		echo "\t\t" . '} else {' . "\n";
		echo "\t\t\t" . 'if(where == "code") {' . "\n";
		echo "\t\t\t\t" . 'QTags.insertContent("[download id=\"" + attachment_id + "\"]");' . "\n";
		echo "\t\t\t" . '} else {' . "\n";
		echo "\t\t\t\t" . 'return "[download id=\"" + attachment_id + "\"]";' . "\n";
		echo "\t\t\t" . '}' . "\n";
		echo "\t\t" . '}' . "\n";
		echo "\t" . '}' . "\n";
		echo "\t" . 'if(document.getElementById("ed_toolbar")){' . "\n";
		echo "\t\t" . 'QTags.addButton( "ed_YangAM", attachmentsEdL10n.download ,function () { insertDownload(\'code\');},"","",attachmentsEdL10n.insert_download );' . "\n";
		echo "\t" . 'attachmentsEdL10n.insert_download' . "\n";
		echo "\t" . '}' . "\n";
		echo '</script>' . "\n";
	}

	public static function load_textdomain(){
		load_plugin_textdomain('yang-attachment', false, dirname(plugin_basename(YANGAM_LOADER)) . '/languages/');
	}

	/**
	 * Add "Settings" action on installed plugin list
	 * @param type $links
	 * @return array
	 */
	public static function add_plugin_actions($links){
		array_unshift($links, '<a href="' . admin_url('admin.php?page=yang-attachmentmanager/yangam-options.php') . '">' . __('Settings') . '</a>');
		return $links;
	}

	/**
	 * 添加后台左侧导航栏管理按钮
	 * Add Attachments Administration Menu
	 */
	public function attachments_menu(){
		if (function_exists('add_menu_page')){
			add_menu_page(__('Attachments', self::textdomain), __('Attachments', self::textdomain), 'manage_attachments', 'yang-attachmentmanager/yangam-manager.php', '', plugins_url('yang-attachmentmanager/images/att_20.png'));
		}
		//依据权限显示相应的子菜单
		if (function_exists('add_submenu_page')){
			/*add_submenu_page('yang-attachmentmanager/yangam-manager.php', __('Manage Attachments', 'yang-attachment'), __('Manage Attachments', 'yang-attachment'), 'manage_attachments', 'yang-attachmentmanager/yangam-manager.php');
			add_submenu_page('yang-attachmentmanager/yangam-manager.php', __('Add File', 'yang-attachment'), __('Add File', 'yang-attachment'), 'manage_attachments', 'yang-attachmentmanager/yangam-add.php');
			add_submenu_page('yang-attachmentmanager/yangam-manager.php', __('Attachment Options', 'yang-attachment'), __('Attachment Options', 'yang-attachment'), 'manage_attachments', 'yang-attachmentmanager/yangam-options.php');
			add_submenu_page('yang-attachmentmanager/yangam-manager.php', __('Uninstall yang-attachment', 'yang-attachment'), __('Uninstall yang-attachment', 'yang-attachment'), 'manage_attachments', 'yang-attachmentmanager/uninstall.php');*/

			//yang_att_manage yang_att_add yang_att_del yang_att_trash
			if (current_user_can('yang_att_add')){
				add_submenu_page('yang-attachmentmanager/yangam-manager.php', __('Add File', 'yang-attachment'), __('Add File', 'yang-attachment'), 'manage_attachments', 'yang-attachmentmanager/yangam-add.php');
			}
			/*
			if (current_user_can('yang_att_del')){
				add_submenu_page('yang-attachmentmanager/yangam-manager.php', __('Manage Attachments', 'yang-attachment'), __('Manage Attachments', 'yang-attachment'), 'manage_attachments', 'yang-attachmentmanager/yangam-manager.php');
			}
			*/
			if (current_user_can('yang_att_manage')){
				add_submenu_page('yang-attachmentmanager/yangam-manager.php', __('Attachment Options', 'yang-attachment'), __('Attachment Options', 'yang-attachment'), 'manage_attachments', 'yang-attachmentmanager/yangam-options.php');
				add_submenu_page('yang-attachmentmanager/yangam-manager.php', __('Uninstall yang-attachment', 'yang-attachment'), __('Uninstall yang-attachment', 'yang-attachment'), 'manage_attachments', 'yang-attachmentmanager/uninstall.php');
			}
		}
	}

	/**WordPress后台最上方有个快速进入某些功能的下拉式菜单，在这个下拉式菜单里默认列出的选项包括“编辑新文章”、“评论”、“新页面”等。
	 * Add Favourite Actions >= WordPress 2.7
	 * @param type $favorite_actions
	 * @return string 
	 */
	public static function favorite_actions($favorite_actions){
		$favorite_actions ['admin.php?page=yang-attachmentmanager/yangam-add.php'] = array(__('Add File', self::textdomain), 'manage_attachments');
		return $favorite_actions;
	}

	/**
	 * 从文章内容中提取 [download 包含的下载ID
	 * get download IDs from post content
	 * @param type $content
	 * @return type 
	 */
	public static function get_download_ids($content){
		$ids = '';
		//搜索 $content 中所有与 [download ... 匹配的内容，按顺序放在 $matches 数组中
		if (preg_match_all("@\[download(\s+)id=\"([0-9,\s]+)\"\]@", $content, $matches)){
			$ids = implode(',', $matches[2]);//将 ID 以逗号隔开
		}
		return $ids;
	}

	/**
	 * 在TinyMCE编辑器中添加快速插入下载 id 按钮
	 * Add Quick Tag For Poll In TinyMCE >= WordPress 2.5
	 * @return type 
	 */
	public function tinymce_addbuttons(){
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
		{
			return;
		}
		if (get_user_option('rich_editing') == 'true')
		{
			add_filter("mce_external_plugins", 'yangam::tinymce_addplugin');
			add_filter('mce_buttons', 'yangam::tinymce_registerbutton');
		}
	}

	/**
	 * used by tinymce_addbuttons
	 * @param type $buttons
	 * @return type 
	 */
	public static function tinymce_registerbutton($buttons){
		array_push($buttons, 'separator', 'YangAM');
		return $buttons;
	}

	/**
	 * used by tinymce_addbuttons
	 * @param array $plugin_array
	 * @return type 
	 */
	public static function tinymce_addplugin($plugin_array){
		$plugin_array ['YangAM'] = plugins_url('yang-attachmentmanager/tinymce/plugins/YangAM/editor_plugin.js');
		return $plugin_array;
	}

	//添加媒体上传按钮
	public static function add_media_button($editor_id = 'content'){
		global $post_ID;
		$url = WP_PLUGIN_URL . "/yang-attachmentmanager/upload-or-insert.php?post_id={$post_ID}&tab=upload&TB_iframe=true&width=740&height=500";
		$admin_icon = WP_PLUGIN_URL . '/yang-attachmentmanager/images/att_20.png';
		if (is_ssl())
		{
			$url = str_replace('http://', 'https://', $url);
		}
		$alt = __('Add Attachment', self::textdomain);
		$img = '<img src="' . esc_url($admin_icon) . '" width="15" height="15" alt="' . esc_attr($alt) . '" />';

		echo '<a href="' . esc_url($url) . '" class="thickbox add_attachment" id="' . esc_attr($editor_id) . '-add_attachment" title="' . esc_attr__('Add Attachment', self::textdomain) . '" onclick="return false;">' . $img . '</a>';
	}

	/**
	 * Add Attachment Query Vars
	 * @param type $public_query_vars
	 * @return string 
	 */
	public static function add_attachment_query_vars($public_query_vars){
		$public_query_vars [] = "fid";
		$public_query_vars [] = "fname";
		return $public_query_vars;
	}

	/**
	 * 读取数据库设置信息 yangam_options 中的 yam_permalink_structure，并处理为数组
	 */
	public function yam_permalink_structure(  ){
		$yangam_options = $this->get_opt('yangam_options');
		$yam_permalink_structure = trim($yangam_options['yam_permalink_structure']);
		$yam_permalink_structure = ltrim(str_replace('./', '', $yam_permalink_structure), '/');//清理 ./ 符号及左边的 / 符号
		$pmlink = explode('/', $yam_permalink_structure);
		return $pmlink;//0-getfile,1-%file_id%
	}

	/**
	 * 伪静态规则
	 * Download htaccess ReWrite Rules
	 * @param type $wp_rewrite 
	 */
	public function attachment_rewrite_rule($wp_rewrite){
		$yangam_options = $this->get_opt('yangam_options');
		//$yangam_options = get_option('yangam_options');
		/*//原伪静态规则
		$wp_rewrite->rules = array_merge(array($yangam_options ['yam_attachment_slug'] . '/([0-9]{1,})/?$' => 'index.php?fid=$matches[1]', $yangam_options ['yam_attachment_slug'] . '/(.*)$' => 'index.php?fname=$matches[1]'), $wp_rewrite->rules);
		*/

		//by yang：yam_attachment_slug 合并到 yam_permalink_structure
		$yam_permalink_style = intval($yangam_options['yam_permalink_style']);
		$yam_permalink_structure = trim($yangam_options['yam_permalink_structure']);
		if( $yam_permalink_style == 2 ){//自定义样式
			//if( $yam_permalink_structure == '/getfile/%file_id%' ){}
			$yam_permalink_structure = ltrim(str_replace('./', '', $yam_permalink_structure), '/');//清理 ./ 符号及左边的 / 符号
			$pmlink = explode('/', $yam_permalink_structure);
			//var_dump( $pmlink );exit;
			
			$wp_rewrite->rules = array_merge(array($pmlink[0] . '/([0-9]{1,})/?$' => 'index.php?fid=$matches[1]', $pmlink[0] . '/(.*)$' => 'index.php?fname=$matches[1]'), $wp_rewrite->rules);
		}
	}

	/**
	 * 前台下载文件
	 * Download File
	 * @global type $wpdb
	 * @global type $user_ID 
	 */
	public function download_file(){
		global $wpdb, $user_ID;
		$fid = (int) get_query_var('fid');
		$fname = get_query_var('fname');
		$fname = addslashes($this->attachment_file_name_decode($fname));
		//do this ONLY when fname is NOT EMPTY and is NOT remote file!
		if (!self::is_remote_file($fname) && !empty($fname) && '/' != substr($fname, 0, 1)){
			$fname = '/' . $fname;
		}
		$yangam_options = $this->get_opt('yangam_options');

		if ($fid > 0 || !empty($fname)){
			//check if the header already sent.This may be PHP error messages genareated by other WordPress plugins.
			yangam::check_headers_sent();
			if ($fid > 0 && $yangam_options ['yam_permalink_style'] == 0){
				$file = $wpdb->get_row("SELECT file_id, file, file_name, file_permission FROM $wpdb->attachments WHERE file_id = $fid AND file_permission != -2");
			}
			elseif (!empty($fname) && $yangam_options ['yam_permalink_style'] == 1){
				$file = $wpdb->get_row("SELECT file_id, file, file_name, file_permission FROM $wpdb->attachments WHERE file = '$fname' AND file_permission != -2");
			}
			//by yang: 读取伪静态样式
			elseif ($yangam_options ['yam_permalink_style'] == 2){
				$pmlink = self::yam_permalink_structure();
				if( $pmlink[1]=='%file_id%' ){
					$file = $wpdb->get_row("SELECT file_id, file, file_name, file_permission FROM $wpdb->attachments WHERE file_id = $fid AND file_permission != -2");
					//var_dump( $file );exit;
				}
				elseif( $pmlink[1]=='%file_name%' ){
					$file = $wpdb->get_row("SELECT file_id, file, file_name, file_permission FROM $wpdb->attachments WHERE file = '$fname' AND file_permission != -2");
				}
			}
			if (!$file){
				status_header(404);//return 404 status
				wp_die(__('Invalid File ID or File Name.', self::textdomain));
			}
			$file_path = stripslashes($this->get_opt('yangam_path'));
			$file_url = stripslashes($this->get_opt('yangam_path_url'));
			$yangam_method = intval($this->get_opt('yangam_method'));
			$file_id = intval($file->file_id);
			$file_name = stripslashes($file->file);
			$down_name = stripslashes($file->file_name);
			$file_permission = intval($file->file_permission);
			$current_user = wp_get_current_user();

			if( ($file_permission > 0 && intval($current_user->wp_user_level) >= $file_permission && intval($user_ID) > 0) || ($file_permission == 0 && intval($user_ID) > 0) || $file_permission == - 1 ){
				if ($yangam_options ['yam_check_referer']){
					if (!isset($_SERVER ['HTTP_REFERER']) || $_SERVER ['HTTP_REFERER'] == '')
						//print_r( $_SERVER ['HTTP_REFERER'] );
						wp_die(__('Please do not leech.', self::textdomain));
					$refererhost = parse_url($_SERVER ['HTTP_REFERER']);
					//如果本站下载也被误认为盗链，请修改下面www.your-domain.com为你的博客域名
					$validReferer = array('localhost', $_SERVER ['HTTP_HOST']);
					if (!(in_array($refererhost ['host'], $validReferer))){
						//print_r($_SERVER['HTTP_HOST']);
						wp_die(__('Please do not leech.', self::textdomain));
					}
				}
				if (!self::is_remote_file($file_name)){
					if (!is_file($file_path . $file_name)){
						status_header(404);
						wp_die(__('File does not exist.', self::textdomain));
					}
					$update_hits = $wpdb->query("UPDATE $wpdb->attachments SET file_hits = (file_hits + 1), file_last_downloaded_date = '" . current_time('timestamp') . "' WHERE file_id = $file_id AND file_permission != -2");
					if ($yangam_method == 0){
						//这里还是重新计算一下大小
						$filesize = filesize($file_path . $file_name);
						$fp = fopen($file_path . $file_name, 'rb');
						if (!$fp){
							wp_die(__('Error: can not read the file!Please contact the webmaster.', self::textdomain));
						}

						if ($filesize <= 0){
							wp_die(__('Error: filesize is zero.', self::textdomain));
						}

						header("Pragma: public");
						header("Expires: 0");
						header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
						header("Content-Type: application/force-download");
						header("Content-Type: application/octet-stream");
						header("Content-Type: application/download");
						header('Content-Disposition: attachment; ' . self::_header_filename(htmlspecialchars_decode(self::space_to_underscore($down_name))));
						header("Content-Transfer-Encoding: binary");
						header("Content-Length: " . $filesize);
						$yangam_options = $this->get_opt('yangam_options');
						// maximum execution time in seconds
						@set_time_limit($yangam_options ['yam_time_limit']);
						//memory linit 256M
						@ini_set('memory_limit', 8 * 1024 * 1024 * 256);

						$length = $filesize;
						define('CHUNK_SIZE', 4096);

						$data = '';
						while ($length > 0) {
							$to_read = $length > CHUNK_SIZE ? CHUNK_SIZE : $length;
							echo fread($fp, $to_read);
							$length -= $to_read;
						}
						fclose($fp);
						//@readfile ( $file_path . $file_name );
					} else {
						header('Location: ' . $file_url . $file_name);
					}
					exit();
				} else {
					$update_hits = $wpdb->query("UPDATE $wpdb->attachments SET file_hits = (file_hits + 1), file_last_downloaded_date = '" . current_time('timestamp') . "' WHERE file_id = $file_id AND file_permission != -2");
					header('Location: ' . $file_name);
					exit();
				}
			} else {
				wp_die(__('You do not have permission to download this file.', self::textdomain));
			}
		}
	}

	/* ###########################################################################	
	 * private functions
	 * ########################################################################## */
	
	//获取文件扩展名的图片目录
	### Function: Get File Extension Images
	private static function file_extension_images(){
		$file_ext_images = array();
		$dir = WP_PLUGIN_DIR . '/yang-attachmentmanager/images/ext';
		if (is_dir($dir)){
			if ($dh = opendir($dir)){
				while (($file = readdir($dh)) !== false) {
					if ($file != '.' && $file != '..'){
						$file_ext_images [] = $file;
					}
				}
				closedir($dh);
			}
		}
		return $file_ext_images;
	}

	/**
	 * Get a browser friendly UTF-8 encoded filename
	 */
	private static function _header_filename($file){
		$user_agent = $_SERVER ['HTTP_USER_AGENT'];
		$user_agent = (!empty($user_agent)) ? htmlspecialchars((string) $user_agent) : '';

		// There be dragons here.
		// Not many follows the RFC...
		if (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Safari') !== false || strpos($user_agent, 'Konqueror') !== false)
		{
			return "filename=" . rawurlencode($file);
		}

		// follow the RFC for extended filename for the rest
		return "filename*=UTF-8''" . rawurlencode($file);
	}

	private function attachment_file_name_encode($file_name){
		$yangam_options = get_option('yangam_options');
		if ($yangam_options['yam_check_referer'])
		{
			$file_name = base64_encode(date('Ymd') . 'xxoo_F-u-c-k_GxxoFxxooW' . stripslashes($file_name));
		}
		else
		{
			$file_name = base64_encode('xxoo_F-u-c-k_GxxoFxxooW' . stripslashes($file_name));
		}
		return $file_name;
	}

	private function attachment_file_name_decode($file_name){
		$yangam_options = $this->get_opt('yangam_options');
		if ($yangam_options['yam_check_referer'])
		{
			$file_name = str_replace(date('Ymd') . 'xxoo_F-u-c-k_GxxoFxxooW', '', base64_decode($file_name));
		}
		else
		{
			$file_name = str_replace('xxoo_F-u-c-k_GxxoFxxooW', '', base64_decode($file_name));
		}
		return $file_name;
	}

	/**
	 * 生成下载文件的URL
	 * the site_url uses get_site_url which will auto add  prefix '/' before path
	 * @param type $file_id
	 * @param type $file_name
	 * @return string 
	 */
	private function get_attachment_file_url($file_id, $file_name){
		$yangam_options = get_option('yangam_options');
		$yam_permalink_style = intval($yangam_options['yam_permalink_style']);
		$yam_permalink_structure = trim($yangam_options['yam_permalink_structure']);
		//$yam_nice_permalink = intval($yangam_options['yam_nice_permalink']);//by yang：去掉yam_nice_permalink
		$file_id = intval($file_id);
		$file_name = ltrim($file_name, '/');
		$file_name = $this->attachment_file_name_encode($file_name);
		
		if( $yam_permalink_style == 0 ){//文件ID
			$attachment_file_url = home_url('?fid=' . $file_id);
		}
		else if( $yam_permalink_style == 1 ) {//文件名
			$attachment_file_url = home_url('?fname=' . $file_name);
		}
		else if( $yam_permalink_style == 2 ){//自定义样式
			$yam_permalink_structure = ltrim(str_replace('./', '', $yam_permalink_structure), '/');//清理 ./ 符号及左边的 / 符号
			$pmlink = explode('/', $yam_permalink_structure);

			if( $pmlink[1]=='%file_id%' ){
				$attachment_file_url = home_url($pmlink[0] . '/' . $file_id . '/');
			}
			else if( $pmlink[1]=='%file_name%' ){
				$attachment_file_url = home_url($pmlink[0] . '/' . $file_name);
			}
		}
		

		/*
		if( $yam_nice_permalink == 1 ){//固定链接伪静态样式；1-是，0-否；
			if( $yam_permalink_style == 1 ){//文件ID
				$attachment_file_url = home_url($yangam_options ['yam_attachment_slug'] . '/' . $file_name);
			} else {
				$attachment_file_url = home_url($yangam_options ['yam_attachment_slug'] . '/' . $file_id . '/');
			}
		} else {
			if ($yam_permalink_style == 1){
				$attachment_file_url = home_url('?fname=' . $file_name);
			} else {
				$attachment_file_url = home_url('?fid=' . $file_id);
			}
		}
		*/
		return $attachment_file_url;
	}

	/**
	 * Download Embedded
	 * @global type $wpdb
	 * @global type $user_ID
	 * @param string $condition
	 * @param type $display
	 * @return type 
	 */
	private function attachment_embedded($id = '', $display = 'both'){
		global $wpdb, $user_ID;
		$output = '';
		$condition = '1=0';
		$id = addslashes($id);
		if (strpos($id, ',') !== false)
		{
			$condition = "file_id IN ($id)";
		}
		else
		{
			$id = (int) $id;
			$condition = "file_id = $id";
		}
		$condition .= ' AND ';

		$files = $wpdb->get_results("SELECT * FROM $wpdb->attachments WHERE $condition file_permission != -2");
		if ($files)
		{
			$current_user = wp_get_current_user();
			$file_extensions_images = self::file_extension_images();
			$yangam_display_type = $this->get_opt('yangam_display_type', 0);
			$template_attachment_embedded_temp = '';
			$template_attachment_embedded = '';
			switch ($yangam_display_type)
			{
				case 0:
					$template_attachment_embedded_temp = $this->get_opt('yangam_template_embedded');
					break;
				case 1:
					$template_attachment_embedded_temp = $this->get_opt('yangam_template_popup');
			}
			foreach ($files as $file)
			{
				$template_attachment_embedded = $template_attachment_embedded_temp;
				$file_permission = intval($file->file_permission);

				if (($file_permission > 0 && intval($current_user->wp_user_level) >= $file_permission && intval($user_ID) > 0) || ($file_permission == 0 && intval($user_ID) > 0) || $file_permission == - 1)
				{
					$template_attachment_embedded = stripslashes($template_attachment_embedded [0]);
				}
				else
				{
					$template_attachment_embedded = stripslashes($template_attachment_embedded [1]);
				}
				$template_attachment_embedded = str_replace("%FILE_ID%", $file->file_id, $template_attachment_embedded);
				$template_attachment_embedded = str_replace("%FILE%", stripslashes($file->file), $template_attachment_embedded);
				$template_attachment_embedded = str_replace("%FILE_NAME%", stripslashes($file->file_name), $template_attachment_embedded);
				$template_attachment_embedded = str_replace("%FILE_ICON%", self::file_extension_image(stripslashes($file->file), $file_extensions_images), $template_attachment_embedded);
				if ($display == 'both')
				{
					$template_attachment_embedded = str_replace("%FILE_DESCRIPTION%", nl2br(stripslashes($file->file_des)), $template_attachment_embedded);
				}
				else
				{
					$template_attachment_embedded = str_replace("%FILE_DESCRIPTION%", '', $template_attachment_embedded);
				}
				$template_attachment_embedded = str_replace("%FILE_HASH%", $file->file_hash, $template_attachment_embedded);
				$template_attachment_embedded = str_replace("%FILE_SIZE%", self::format_filesize($file->file_size), $template_attachment_embedded);
				$template_attachment_embedded = str_replace("%FILE_DATE%", mysql2date(get_option('date_format'), gmdate('Y-m-d H:i:s', $file->file_date)), $template_attachment_embedded);
				$template_attachment_embedded = str_replace("%FILE_TIME%", mysql2date(get_option('time_format'), gmdate('Y-m-d H:i:s', $file->file_date)), $template_attachment_embedded);
				$template_attachment_embedded = str_replace("%FILE_UPDATED_DATE%", mysql2date(get_option('date_format'), gmdate('Y-m-d H:i:s', $file->file_updated_date)), $template_attachment_embedded);
				$template_attachment_embedded = str_replace("%FILE_UPDATED_TIME%", mysql2date(get_option('time_format'), gmdate('Y-m-d H:i:s', $file->file_updated_date)), $template_attachment_embedded);
				$template_attachment_embedded = str_replace("%FILE_HITS%", number_format_i18n($file->file_hits), $template_attachment_embedded);
				$template_attachment_embedded = str_replace("%FILE_ATTACHMENT_URL%", $this->get_attachment_file_url($file->file_id, $file->file), $template_attachment_embedded);
				$output .= $template_attachment_embedded;
			}
			return apply_filters('attachment_embedded', $output);
		}
		else
		{
			//@todo :like this condition : [1,2,9999,1000,11111] when the last 3 are not in the DB,this will not be informed.
			$maybe_deleted = sprintf(__('<div style="color:#FF0000;"><strong>Yang AttachmentManager : </strong> The download file maybe has been deleted (File ID:%s).</div>', self::textdomain), $id);
			return $maybe_deleted;
		}
	}

	/* ###########################################################################	
	 * public functions
	 * ########################################################################## */

	/**
	 * 将原始文件名空格替换为下划线
	 * @param type $file_name
	 * @return type 
	 */
	public static function space_to_underscore($file_name){
		$file_name = self::get_basename($file_name);
		$file_name = str_replace(' ', '_', $file_name);
		return $file_name;
	}

	/**
	 * 2010 0506修正，可取得网址文件名
	 * 2011-09-21 fixed,make the function perform like  PHP origenal basename function
	 * @param string $file_name
	 * @param string $suffix
	 * @return string 
	 */
	public static function get_basename($file_name, $suffix = ''){
		//for windows servers
		$file_name = str_replace("\\", '/', $file_name);
		if (false !== strpos($file_name, '/'))
		{
			/*
			  $baseDir=dirname($file_name);
			  $basename=str_replace($baseDir,'',$file_name);
			  $basename=str_replace('/','',$basename);
			 */
			$basename = substr($file_name, strrpos($file_name, '/') + 1);
		}
		else
		{
			$basename = $file_name;
		}
		if (!empty($suffix))
		{
			$basename = substr($basename, 0, strlen($basename) - strlen($suffix));
		}
		return $basename;
	}

	/**
	 * 获取文件扩展名
	 * Get File Extension
	 * @param type $filename
	 * @return type 
	 */
	public static function file_extension($filename){
		$file_ext = explode('.', $filename);
		$file_ext = $file_ext [sizeof($file_ext) - 1];
		$file_ext = strtolower($file_ext);
		return $file_ext;
	}

	/**
	 * 打印出文件扩展名图像
	 * Print Out File Extension Image
	 * @param type $file_name
	 * @param type $file_ext_images
	 * @return string 
	 */
	private static function file_extension_image($file_name, $file_ext_images){
		$file_ext = self::file_extension($file_name);
		$file_ext .= '.gif';
		if (in_array($file_ext, $file_ext_images)){
			return $file_ext;
		} else {
			return 'unknown.gif';
		}
	}

	/**
	 * 判断是否为图片文件
	 * http://cn.php.net/manual/zh/function.getimagesize.php
	 * @return true/false
	 */
	public static function is_image($file_name){
		$types = '.gif|.jpg|.jpeg|.png|.bmp';//定义检查的图片类型
		$ext = self::file_extension($file_name);
		return stripos($types,$ext);
	}

	### Function: Format Bytes Into TiB/GiB/MiB/KiB/Bytes
	public static function format_filesize($rawSize){
		if ($rawSize / 1099511627776 > 1)
		{
			return number_format_i18n($rawSize / 1099511627776, 1) . ' ' . __('TiB', self::textdomain);
		}
		elseif ($rawSize / 1073741824 > 1)
		{
			return number_format_i18n($rawSize / 1073741824, 1) . ' ' . __('GiB', self::textdomain);
		}
		elseif ($rawSize / 1048576 > 1)
		{
			return number_format_i18n($rawSize / 1048576, 1) . ' ' . __('MiB', self::textdomain);
		}
		elseif ($rawSize / 1024 > 1)
		{
			return number_format_i18n($rawSize / 1024, 1) . ' ' . __('KiB', self::textdomain);
		}
		elseif ($rawSize > 1)
		{
			return number_format_i18n($rawSize, 0) . ' ' . __('bytes', self::textdomain);
		}
		else
		{
			return __('unknown', self::textdomain);
		}
	}

	/**
	 * Snippet Text
	 * @param type $text
	 * @param type $length
	 * @return type 
	 */
	public static function snippet_text($text, $length = 0){
		if (defined('MB_OVERLOAD_STRING'))
		{
			$text = @html_entity_decode($text, ENT_QUOTES, get_option('blog_charset'));
			if (mb_strlen($text) > $length)
			{
				return htmlentities(mb_substr($text, 0, $length), ENT_COMPAT, get_option('blog_charset')) . '...';
			}
			else
			{
				return htmlentities($text, ENT_COMPAT, get_option('blog_charset'));
			}
		}
		else
		{
			$text = @html_entity_decode($text, ENT_QUOTES, get_option('blog_charset'));
			if (strlen($text) > $length)
			{
				return htmlentities(substr($text, 0, $length), ENT_COMPAT, get_option('blog_charset')) . '...';
			}
			else
			{
				return htmlentities($text, ENT_COMPAT, get_option('blog_charset'));
			}
		}
	}

	/**
	 * 是否为远程文件
	 * check if Is Remote File
	 * @param type $file_name
	 * @return type
	 */
	public static function is_remote_file($file_name){
		$file_name = strtolower($file_name);
		if (strpos($file_name, 'http://') === false && strpos($file_name, 'https://') === false && strpos($file_name, 'ftp://') === false){
			return false;
		}
		return true;
	}

	### Function: Short Code For Inserting Files Download Into Posts
	public function attachment_shortcode($atts){
		//in last line of shortcodes : add_filter('the_content', 'do_shortcode', 11); 
		// so the shortcode is trigger before wp_footer
		self::$_add_js = TRUE;

		extract(shortcode_atts(array('id' => '0', 'display' => 'both'), $atts));
		if (!is_feed()){
			if ($id != '0'){
				return $this->attachment_embedded($id, $display);
			} else {
				return '';
			}
		} else {
			return sprintf(__('Note: There is a file embedded within this post, please visit <a href="%s">this post</a> to download the file.', self::textdomain), get_permalink());
		}
	}

	/**
	 * 创建附件表，并添加默认选项
	 * Create attachments Table and add default options
	 * @global type $wpdb
	 * @global type $blog_id 
	 */
	public function create_attachment_table(){
		global $wpdb, $blog_id;
		$this->load_textdomain();
		if (@is_file(ABSPATH . '/wp-admin/includes/upgrade.php')){
			include_once (ABSPATH . '/wp-admin/includes/upgrade.php');
		}
		elseif (@is_file(ABSPATH . '/wp-admin/upgrade-functions.php')){
			include_once (ABSPATH . '/wp-admin/upgrade-functions.php');
		} else {
			wp_die(__('We have problem finding your \'/wp-admin/upgrade-functions.php\' and \'/wp-admin/includes/upgrade.php\'', self::textdomain));
		}
		$charset_collate = '';
		if ($wpdb->supports_collation())
		{
			if (!empty($wpdb->charset))
			{
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if (!empty($wpdb->collate))
			{
				$charset_collate .= " COLLATE $wpdb->collate";
			}
		}
		// Create WP-attachments Table
		$create_table = "CREATE TABLE $wpdb->attachments (" . "file_id int(11) NOT NULL auto_increment," . "file tinytext NOT NULL," . "file_name text NOT NULL," . "file_des text NOT NULL," . "file_hash varchar(40) NOT NULL default ''," . "file_size varchar(20) NOT NULL default ''," . "file_date varchar(20) NOT NULL default ''," . "file_updated_date varchar(20) NOT NULL default ''," . "file_last_downloaded_date varchar(20) NOT NULL default ''," . "file_hits int(10) NOT NULL default '0'," . "file_status varchar(20) NOT NULL default 'open'," . "file_permission TINYINT(2) NOT NULL default '0'," . "PRIMARY KEY (file_id)) $charset_collate;";
		maybe_create_table($wpdb->attachments, $create_table);
		
		// Yangam Options
		if (function_exists('is_site_admin')){
			add_option('yangam_path', str_replace("\\", '/', WP_CONTENT_DIR) . '/blogs.dir/' . $blog_id . '/files');
			add_option('yangam_path_url', WP_CONTENT_URL . '/blogs.dir/' . $blog_id . '/files');
		} else {
			add_option('yangam_path', str_replace("\\", '/', WP_CONTENT_DIR) . '/files');
			add_option('yangam_path_url', content_url('files'));
		}
		add_option('yangam_method', 0);
		add_option('yangam_display_type', 0);
		add_option('yangam_template_embedded', self::get_default_value('yangam_template_embedded'));
		add_option('yangam_options', self::get_default_value('yangam_options'));
		add_option('yangam_template_custom_css', self::get_default_value('yangam_template_custom_css'));
		add_option('yangam_template_popup', self::get_default_value('yangam_template_popup'));

		//$wpdb->query("UPDATE $wpdb->attachments SET file_permission = -2 WHERE file_permission = -1;");
		//$wpdb->query("UPDATE $wpdb->attachments SET file_permission = -1 WHERE file_permission = 0;");
		//$wpdb->query("UPDATE $wpdb->attachments SET file_permission = 0 WHERE file_permission = 1;");
		// Create Files Folder
		if (function_exists('is_site_admin')){
			if (!is_dir(str_replace("\\", '/', WP_CONTENT_DIR) . '/blogs.dir/' . $blog_id . '/files/')){
				mkdir(str_replace("\\", '/', WP_CONTENT_DIR) . '/blogs.dir/' . $blog_id . '/files/', 0777, true);
			}
		} else {
			if (!is_dir(str_replace("\\", '/', WP_CONTENT_DIR) . '/files/')) {
				mkdir(str_replace("\\", '/', WP_CONTENT_DIR) . '/files/', 0777, true);
			}
		}
		
		
		/**增加插件使用权限 wp-admin/includes/schema.php
		 * Set 'manage_attachments' Capabilities To Administrator
		 * 
		 * 用户角色：管理员administrator > 编辑editor > 作者author > 投稿者contributor > 订阅者subscriber
		 * 
		 * 使用方法：if ( !current_user_can('upload_files') ){ wp_die(__('You do not have permission to upload files.')); }
		 * 
		 * 说明：官网WP自带权限名 “用户角色和权限” http://codex.wordpress.org/zh-cn:%E7%94%A8%E6%88%B7%E8%A7%92%E8%89%B2%E5%92%8C%E6%9D%83%E9%99%90
		 * 		add_cap(),remove_cap( $role, $cap )位于 wp-includes/capabilities.php
		 *		权限名保存在 wp_options表的 wp_user_roles 字段里
		 * 本权限暂时自动分配，考虑此后将可后台设置分配
		 **/
		//管理员administrator
		$role = get_role('administrator');
			$role->add_cap('yang_att_manage');	//新增 插件管理权限：安装、配置、卸载
			$role->add_cap('yang_att_add');		//新增 附件上传权限：上传
			$role->add_cap('yang_att_del');		//新增 附件删除权限：删除附件(数据库+本地文件)
			$role->add_cap('yang_att_trash');	//新增 附件回收权限：将附件放入回收站，待管理员确认删除
		//编辑editor
		$role = get_role('editor');
			$role->add_cap('yang_att_add');
			$role->add_cap('yang_att_trash');
		//作者author
		$role = get_role('author');
			$role->add_cap('yang_att_add');
			$role->add_cap('yang_att_trash');

		/*//批量新增权限
		$roles = array('administrator', 'editor', 'author');
		foreach ($roles as $role) {
			$role = get_role($role);
			if( !$role->has_cap('yang_attachment_del') ){
				$role->add_cap('yang_attachment_del');//增加权限名
			}
		}
		//批量删除权限
		$roles = array('administrator', 'editor', 'author');
		foreach ($roles as $role) {
			$role = get_role($role);
			$role->remove_cap( 'manage_attachments' );//manage_attachments
		}
		*/
		//var_dump(get_role('editor'));exit;

	}

	//附件 id 是否存在
	public function file_id_exists($file_id){
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SELECT file_id FROM $wpdb->attachments WHERE file_id = %d", $file_id));
	}

	//by yang：删除数据库表 postmeta 中的“附加到文章”信息var_dump();exit;
	public function deattach_post($post_id){
		global $wpdb;
		$post_id = (int) $post_id;
		if ($wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='yang_attached_id' AND post_id = %d", $post_id))){
			return $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key='yang_attached_id' AND post_id = %d", $post_id));
		}
		return true;
	}
	
	/*//原：删除数据库表 attachment_post 中的附件信息
	public function deattach_post($post_id){
		global $wpdb;
		$post_id = (int) $post_id;
		if ($wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->attachment_post WHERE post_id = %d", $post_id)))
		{
			return $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->attachment_post WHERE post_id = %d", $post_id));
		}
		return true;
	}
	*/

	/**
	 * by yang：合并数据表！将附件的“附加到文章”的信息写入数据库，表 postmeta
	 * postmeta字段设置：
	 *		meta_id		自增
	 *		post_id		插入文章 post_id
	 *		meta_key	yang_attached_id	筛选字段 AND meta_key = 'yang_attached_id'
	 *		meta_value	附加到的附件 file_id
	 **/
	public function attach_post($file_id, $post_id){
		global $wpdb;
		$post_id = (int) $post_id;
		$file_id = (int) $file_id;
		//be aware,add only the file exists in the database
		//if not attached,attach it.
		if ( !$wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='yang_attached_id' AND post_id=%d AND meta_value=%d ", $post_id, $file_id))){
			//return $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->postmeta VALUES(%d,%d)", $file_id,$post_id));
			return $wpdb->query("INSERT INTO $wpdb->postmeta VALUES (NULL, $post_id, 'yang_attached_id', '$file_id')");
		}
		return true;
	}
	

	/*//by yang修改可用：合并数据表！将附件的“附加到文章”的信息写入数据库
	public function attach_post($file_id, $post_id){
		global $wpdb;
		$post_id = (int) $post_id;
		$file_id = (int) $file_id;
		
		//be aware,add only the file exists in the database
		//if not attached,attach it.
		//prepare 准备执行的SQL语句
		$file_info = $wpdb->get_row("SELECT file_id,post_parent FROM $wpdb->attachments WHERE file_id = $file_id");
		//var_dump($file_info);exit;
		
		if( $file_info->file_id ){//如果要附加到的文章id存在
			//判断是否已经附加到文章
			$if_parent = stristr("$file_info->post_parent", "$post_id");//stristr: 返回一个从被判断字符开始到结束的字符串,如果没有返回值,则表示不包含此字符串

			if( $if_parent ){
				return true;//已经附加到文章了
			} else {
				if( $file_info->post_parent ){//原“附加到文章”不为空
					$post_id = $file_info->post_parent . "," . $post_id;
				}
				return $wpdb->query("UPDATE $wpdb->attachments SET post_parent = '".$post_id."' WHERE file_id = $file_id");
			}
		}
		return true;
	}

	//原：将附件的“附加到文章”的信息写入数据库，表 attachment_post
	public function attach_post($file_id, $post_id){
		global $wpdb;
		$post_id = (int) $post_id;
		$file_id = (int) $file_id;
		//be aware,add only the file exists in the database
		//if not attached,attach it.
		if ( !$wpdb->get_var($wpdb->prepare("SELECT file_id FROM $wpdb->attachment_post WHERE file_id = %d AND post_id= %d ", $file_id, $post_id)))
		{
			return $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->attachment_post VALUES(%d,%d)", $file_id,$post_id));
		}
		return true;
	}
	*/
	
	//保存文章时，执行函数：update_post_attachinfo()，更新数据库表 attachment_post
	public function update_post_attachinfo($post_ID){
		$post = get_post($post_ID);//通过文章id，返回文章信息
		//var_dump($post);
		$ids = $this->get_download_ids($post->post_content);//查看正文里是否包含 [download id=""] 标签
		//var_dump($ids);exit;
		if(!empty($ids)){
			$id_arr = explode(',', $ids);
			foreach($id_arr as $id){
				if( $this->file_id_exists($id)){
					$this->attach_post($id, $post_ID);
				}
			}
		}
	}
	
}

// end class
