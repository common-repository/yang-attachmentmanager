<?php
/**
 * $Id: YangAM-options.php 2012-05-12 13:04:47 haibor $
 */
### Check Whether User Can Manage Attachments
if (!current_user_can('yang_att_manage')){
	//die('Access Denied');
	wp_die(__('Oh NO! You do not have permission to access this page.', yangam::textdomain));
}

//load the admin class
require dirname(__FILE__) . '/includes/yangam_admin.class.php';

### Variables Variables Variables
$base_name = plugin_basename('yang-attachmentmanager/YangAM-manager.php');
$base_page = 'admin.php?page=' . $base_name;

global $wp_rewrite;
### If Form Is Submitted
if (isset($_POST['Submit'])){
	$yangam_path = trim(yangam_admin::post('yangam_path'));
	$yangam_path_url = trim(yangam_admin::post('yangam_path_url'));
	//$yam_nice_permalink = intval(yangam_admin::post('yam_nice_permalink'));//by yang：去掉yam_nice_permalink
	$yam_permalink_style = intval(yangam_admin::post('yam_permalink_style'));
	$yam_permalink_structure = trim(yangam_admin::post('yam_permalink_structure'));
	//$yam_attachment_slug = trim(yangam_admin::post('yam_attachment_slug'));//by yang：去掉 yam_attachment_slug
	$yam_time_limit = trim(yangam_admin::post('yam_time_limit')) * 60;
	$yam_hash_func = trim(yangam_admin::post('yam_hash_func'));
	$yam_check_referer = trim(yangam_admin::post('yam_check_referer'));
	$yangam_method = intval(yangam_admin::post('yangam_method'));
	$yangam_display_type = intval($_POST['yangam_display_type']);
	$yangam_template_custom_css = trim( yangam_admin::post('yangam_template_custom_css') );
	$yangam_template_popup [] = trim(yangam_admin::post('yangam_template_popup'));
	$yangam_template_popup [] = trim(yangam_admin::post('yangam_template_popup_2'));
	$yangam_template_embedded [] = trim(yangam_admin::post('yangam_template_embedded'));
	$yangam_template_embedded [] = trim(yangam_admin::post('yangam_template_embedded_2'));

	$yangam_options = array(
		'yam_permalink_style' => $yam_permalink_style,
		'yam_permalink_structure' => $yam_permalink_structure,
		//'yam_attachment_slug' => $yam_attachment_slug,//by yang：去掉 yam_attachment_slug
		//'yam_nice_permalink' => $yam_nice_permalink,//by yang：去掉yam_nice_permalink
		'yam_time_limit' => $yam_time_limit,
		'yam_hash_func' => $yam_hash_func,
		'yam_check_referer' => $yam_check_referer
	);
	$update_attachment_queries = array();
	$update_attachment_text = array();

	if (is_dir($yangam_path)) {
		$update_attachment_queries [] = update_option('yangam_path', untrailingslashit($yangam_path));
	} else {// if the site has moved to another SERVER and the dir is not exists anymore ... 
		if (function_exists('is_site_admin')){
			global $blog_id;
			$update_attachment_queries [] = update_option('yangam_path', str_replace("\\", '/', WP_CONTENT_DIR) . '/blogs.dir/' . $blog_id . '/files');
		} else {
			$update_attachment_queries [] = update_option('yangam_path', str_replace("\\", '/', WP_CONTENT_DIR) . '/files');
		}
	}
	$update_attachment_queries [] = update_option('yangam_path_url', untrailingslashit($yangam_path_url));
	$update_attachment_queries [] = update_option('yangam_options', $yangam_options);
	
	//by yang改写
	//定义一个新的重写规则可以分两步：（1）“清理”缓存的重写规则，迫使WordPress重新计算重写规则，（2）计算重写规则时，用generate_rewrite_rules动作还属来添加新规则：attachment_rewrite_rule()
	if (2 == $yangam_options ['yam_permalink_style']){
		$permalink_structure = get_option('permalink_structure');
		if ($permalink_structure)
		{
			$wp_rewrite->flush_rules(false);
		}
	}

	/*//by yang：去掉yam_nice_permalink
	if (1 == $yangam_options ['yam_nice_permalink'])
	{
		$permalink_structure = get_option('permalink_structure');
		if ($permalink_structure)
		{
			$wp_rewrite->flush_rules(false);
		}
	}
	*/
	$update_attachment_queries [] = update_option('yangam_method', $yangam_method);
	//flush the rewrite rules
	if (intval(yangam_admin::get_opt('yangam_method')) != intval($yangam_method))
	{
		flush_rewrite_rules();
	}
	$update_attachment_queries [] = update_option('yangam_display_type', $yangam_display_type);
	$update_attachment_queries [] = update_option('yangam_template_custom_css', $yangam_template_custom_css);

	$update_attachment_queries [] = update_option('yangam_template_popup', $yangam_template_popup);
	$update_attachment_queries [] = update_option('yangam_template_embedded', $yangam_template_embedded);

	$update_attachment_text [] = __('Download Path', yangam::textdomain);
	$update_attachment_text [] = __('Download Path URL', yangam::textdomain);
	$update_attachment_text [] = __('Attachment Options(Use filename or Not、Download slug、Use Nice Permalink or Not、Time Limit、Hash Function、Check HTTP Referer)', yangam::textdomain);
	$update_attachment_text [] = __('Download Method', yangam::textdomain);
	$update_attachment_text [] = __('Download Display Type', yangam::textdomain);
	$update_attachment_text [] = __('Download Custom CSS', yangam::textdomain);
	$update_attachment_text [] = __('Download Popup Template', yangam::textdomain);
	$update_attachment_text [] = __('Download Embedded Template', yangam::textdomain);
	$i = 0;
	foreach ($update_attachment_queries as $update_attachment_query)
	{
		if ($update_attachment_query)
		{
			yangam_admin::add_message($update_attachment_text [$i] . ' ' . __('Updated', yangam::textdomain));
		}
		$i++;
	}
}


### Get Attachment Options
$yangam_options = yangam_admin::get_opt('yangam_options');

$yangam_template_custom_css = yangam_admin::get_opt('yangam_template_custom_css');
### Get File Download Method
$yangam_method = intval(yangam_admin::get_opt('yangam_method'));
//display style 0 :embedded 1: popup
$yangam_display_type = intval(yangam_admin::get_opt('yangam_display_type'));

$yangam_template_popup = yangam_admin::get_opt('yangam_template_popup');
$yangam_template_embedded = yangam_admin::get_opt('yangam_template_embedded');
?>
<?php
yangam_admin::show_message_or_error();
?>

<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
	jQuery('input:radio.tog').change(function() {
		if( '2' == this.value ){//选中了自定义样式
			jQuery('#yam_permalink_structure').val( '/getfile/%file_id%' );//当选中“自定义样式时”，设置指定值
			return;
		}
		//jQuery('#yam_permalink_structure').val( this.value );在输入框获取当前radio的值
		jQuery('#yam_permalink_structure').val( '' );
	});
	jQuery('#yam_permalink_structure').focus(function() {
		jQuery("#yam_permalink_style-2").attr('checked', 'checked');
	});
});
//]]>
</script>

<form method="post" action="<?php echo $_SERVER ['PHP_SELF']; ?>?page=<?php echo plugin_basename(__FILE__); ?>">
	<div class="wrap">
		<div id="icon-attachment" class="icon32"><br /></div>
		<h2><?php _e('Attachment Options', yangam::textdomain); ?></h2>
		<h3><?php _e('Attachment Options', yangam::textdomain); ?></h3>
		<table class="form-table">
			<tr valign="top">
				<th><?php _e('Upload Path:', yangam::textdomain); ?></th>
				<td>
					<input type="text" name="yangam_path" value="<?php echo stripslashes(yangam_admin::get_opt('yangam_path')); ?>" size="50" dir="ltr" /><br />
					<?php _e('The absolute path to the directory where all the files are stored (without trailing slash).', yangam::textdomain); ?></td></tr>
			<tr valign="top">
				<th><?php _e('Upload Path URL:', yangam::textdomain); ?></th>
				<td><input type="text" name="yangam_path_url" value="<?php echo stripslashes(yangam_admin::get_opt('yangam_path_url')); ?>" size="50" dir="ltr" /><br />
					<?php _e('The url to the directory where all the files are stored (without trailing slash).', yangam::textdomain); ?></td></tr>
			<tr valign="top">
				<th><?php _e('Permalink Settings', yangam::textdomain); ?></th>
				<td>
					<input type="radio" class="tog" id="yam_permalink_style-0" name="yam_permalink_style" value="0" <?php checked('0', $yangam_options ['yam_permalink_style']); ?>>&nbsp;
					<label for="yam_permalink_style-0"><?php _e('File ID', yangam::textdomain); ?><br />
						<span dir="ltr">- <?php echo get_option('home'); ?>/?fid=1</span></label><br />
						<!-- <span dir="ltr">- <?php echo get_option('home'); ?>/<?php echo stripslashes($yangam_options ['yam_attachment_slug']); ?>/1/</span> --><br />
					<input type="radio" class="tog" id="yam_permalink_style-1" name="yam_permalink_style" value="1" <?php checked('1', $yangam_options ['yam_permalink_style']); ?>>&nbsp;
					<label for="yam_permalink_style-1"><?php _e('File Name', yangam::textdomain); ?><br />
						<span dir="ltr">- <?php echo get_option('home'); ?>/?fname=filename.ext</span></label><br />
						<!-- <span dir="ltr">- <?php echo get_option('home'); ?>/<?php echo stripslashes($yangam_options ['yam_attachment_slug']); ?>/filename.ext</span> --><br />


					<input type="radio" class="tog" id="yam_permalink_style-2" name="yam_permalink_style" value="2" <?php checked('2', $yangam_options ['yam_permalink_style']); ?>>&nbsp;
					<label><?php _e('Custom Style', yangam::textdomain); ?><br />
						<input type="text" class="regular-text code" value="<?php echo stripslashes($yangam_options ['yam_permalink_structure']); ?>" id="yam_permalink_structure" name="yam_permalink_structure"><br />
						<!-- <span dir="ltr">- <?php echo get_option('home'); ?>/<?php echo stripslashes($yangam_options ['yam_attachment_slug']); ?>/1/</span><br />
						<span dir="ltr">- <?php echo get_option('home'); ?>/<?php echo stripslashes($yangam_options ['yam_attachment_slug']); ?>/filename.ext</span></label><br /> -->
						填写示例：/getfile/%file_id%<br />
						可使用的参数：<b>getfile</b>为防盗使用；<b>%file_id%</b> 表示文件id；<b>%file_name%</b> 表示文件名<br />
						<!-- <span dir="ltr">- <?php echo get_option('home'); ?>/<?php echo stripslashes($yangam_options ['yam_attachment_slug']); ?>/filename.ext</span> --><br />
						- http://localhost:503/1/<br />
						- http://localhost:503/filename.ext<br />
					<?php _e('Change it to <strong>File ID</strong> when you encounter 404 error.', yangam::textdomain); ?></td></tr>
			
			<!-- 
			<tr valign="top">
				<th><?php _e('Permalink Style', yangam::textdomain); ?></th>
				<td>
					<input type="radio" class="tog" id="yam_nice_permalink-1" name="yam_nice_permalink" value="1" <?php checked('1', $yangam_options ['yam_nice_permalink']); ?>>&nbsp;
					<label for="yam_nice_permalink-1"><?php _e('Yes', yangam::textdomain); ?><br />
						<span dir="ltr">- <?php echo get_option('home'); ?>/<?php echo stripslashes($yangam_options ['yam_attachment_slug']); ?>/1/</span><br />
						<span dir="ltr">- <?php echo get_option('home'); ?>/<?php echo stripslashes($yangam_options ['yam_attachment_slug']); ?>/filename.ext</span></label><br />
					<input type="radio" class="tog" id="yam_nice_permalink-0" name="yam_nice_permalink" value="0" <?php checked('0', $yangam_options ['yam_nice_permalink']); ?>>&nbsp;
					<label for="yam_nice_permalink-0"><?php _e('No', yangam::textdomain); ?><br />
						<span dir="ltr">- <?php echo get_option('home'); ?>/?fid=1</span><br />
						<span dir="ltr">- <?php echo get_option('home'); ?>/?fname=filename.ext</span></label><br />
					<?php _e('Change it to <strong>No</strong> when you encounter 404 error.', yangam::textdomain); ?></td></tr>
			<tr valign="top">
				<th><?php _e('Download Slug:', yangam::textdomain); ?></th>
				<td>
					<input type="text" name="yam_attachment_slug" size="30" maxlength="50" value="<?php echo stripslashes($yangam_options ['yam_attachment_slug']); ?>"><br />
					<?php _e('This only affects when you have enabled <strong>Permalink Style</strong> .', yangam::textdomain); ?></td></tr>
			-->
			<tr valign="top">
				<th><?php _e('Remote Download Time Limit:', yangam::textdomain); ?></th>
				<td>
					<input type="text" name="yam_time_limit" size="30" maxlength="50" value="<?php echo stripslashes($yangam_options ['yam_time_limit']) / 60; ?>"><?php _e('Minutes', yangam::textdomain); ?><br />
					<?php _e('This only affects when the plugin are downloading <strong>Remote File</strong> to your local server.', yangam::textdomain); ?></td></tr>
			<tr valign="top">
				<th><?php _e('Hash Function:', yangam::textdomain); ?></th>
				<td>
					<select name="yam_hash_func" size="1">
						<option value="md5" <?php selected('md5', $yangam_options ['yam_hash_func']); ?>>MD5</option>
						<option value="sha1" <?php selected('sha1', $yangam_options ['yam_hash_func']); ?>>SHA1</option>
					</select></td></tr>
			<tr valign="top">
				<th><?php _e('Download Method:', yangam::textdomain); ?></th>
				<td>
					<select name="yangam_method" size="1">
						<option value="0" <?php selected('0', $yangam_method); ?>><?php _e('Output File', yangam::textdomain); ?></option>
						<option value="1" <?php selected('1', $yangam_method); ?>><?php _e('Redirect To File', yangam::textdomain); ?></option>
					</select> <br />
					<?php _e('Change it to <strong>Redirect To File</strong> when you have problem with large files.', yangam::textdomain); ?>
				</td>
			</tr>

			<tr valign="top">
				<th><?php _e('Check HTTP referer:', yangam::textdomain); ?></th>
				<td><select name="yam_check_referer" size="1">
						<option value="0" <?php selected('0', $yangam_options ['yam_check_referer']); ?>><?php _e('Not enabled', yangam::textdomain); ?></option>
						<option value="1" <?php selected('1', $yangam_options ['yam_check_referer']); ?>><?php _e('Enabled', yangam::textdomain); ?></option>
					</select> <br />
					<?php _e('<strong>Enable this could save your a lot of bandwidth.</strong>', yangam::textdomain); ?>
				</td>
			</tr>
			<tr valign="top">
				<th><?php _e('Download display type:', yangam::textdomain); ?></th>
				<td><select name="yangam_display_type" size="1" id="yangam_display_type" onchange="set_template_table(this.value);">
						<option value="0" <?php selected('0', $yangam_display_type); ?>><?php _e('Embeded', yangam::textdomain); ?></option>
						<option value="1" <?php selected('1', $yangam_display_type); ?>><?php _e('Popup', yangam::textdomain); ?></option>
					</select> <br />
				</td>
			</tr>
			<tr valign="top">
				<th><?php _e('Download Templates Custom CSS', yangam::textdomain); ?>
					<input type="button" name="RestoreDefault" value="<?php _e('Restore Default CSS', yangam::textdomain); ?>" onclick="attachment_default_templates('custom_css');" class="button" />
				</th>
				<td>	
					<textarea name="yangam_template_custom_css" id="yangam_template_custom_css" cols="80" rows="10">
						<?php echo htmlspecialchars(stripslashes($yangam_template_custom_css)); ?>
					</textarea>
				</td>
			</tr>

		</table>

		<h3><?php
			_e('Download Templates (With Permission)', yangam::textdomain);
			?></h3>
		<table class="form-table" id="table_yangam_template_embedded">
			<tr valign="top">
				<td width="30%"><strong><?php
			_e('Download Template', yangam::textdomain);
			?></strong><br />
<?php
_e('Displayed when you embedded a file within a post or a page and users have permission to download the file.', yangam::textdomain);
?><br />
					<br />
<?php
_e('Allowed Variables:', yangam::textdomain);
?><br />
					- %FILE_ID%<br />
					- %FILE%<br />
					- %FILE_ICON%<br />
					- %FILE_NAME%<br />
					- %FILE_DESCRIPTION%<br />
					- %FILE_HASH%<br />
					- %FILE_SIZE%<br />
					- %FILE_DATE%<br />
					- %FILE_TIME%<br />
					- %FILE_UPDATED_DATE%<br />
					- %FILE_UPDATED_TIME%<br />
					- %FILE_HITS%<br />
					- %FILE_ATTACHMENT_URL%<br />
					<br />
					<input type="button" name="RestoreDefault"
						   value="<?php
_e('Restore Default Template', yangam::textdomain);
?>"
						   onclick="attachment_default_templates('embedded');" class="button" /></td>
				<td><textarea cols="80" rows="20" id="yangam_template_embedded"
							  name="yangam_template_embedded"><?php
echo htmlspecialchars(stripslashes($yangam_template_embedded [0]));
?></textarea></td>
			</tr>
		</table>


		<table class="form-table" id="table_yangam_template_popup" style="display:none;">
			<tr valign="top">
				<td width="30%"><strong><?php
						_e('Download popup div template', yangam::textdomain);
?></strong><br />
<?php
_e('Displayed the download info in a popup window when you embedded a file within a post or a page and users have permission to download the file.', yangam::textdomain);
?><br />
					<br />
<?php
_e('Allowed Variables:', yangam::textdomain);
?><br />
					- %FILE_ID%<br />
					- %FILE%<br />
					- %FILE_ICON%<br />
					- %FILE_NAME%<br />
					- %FILE_DESCRIPTION%<br />
					- %FILE_HASH%<br />
					- %FILE_SIZE%<br />
					- %FILE_DATE%<br />
					- %FILE_TIME%<br />
					- %FILE_UPDATED_DATE%<br />
					- %FILE_UPDATED_TIME%<br />
					- %FILE_HITS%<br />
					- %FILE_ATTACHMENT_URL%<br />
					<br />
					<input type="button" name="RestoreDefault"
						   value="<?php
_e('Restore Default Template', yangam::textdomain);
?>"
						   onclick="attachment_default_templates('popup');" class="button" /></td>
				<td><textarea cols="80" rows="20" id="yangam_template_popup"
							  name="yangam_template_popup"><?php
echo htmlspecialchars(stripslashes($yangam_template_popup [0]));
?></textarea></td>
			</tr>
		</table>





		<h3><?php
			_e('Download Templates (Without Permission)', yangam::textdomain);
?></h3>
		<table class="form-table" id="table_yangam_template_embedded_2">

			<tr valign="top">
				<td width="30%"><strong><?php
						_e('Download Template', yangam::textdomain);
?></strong><br />
<?php
_e('Displayed when you embedded a file within a post or a page and users <strong>DO NOT</strong> have permission to download the file.', yangam::textdomain);
?><br />
					<br />
<?php
_e('Allowed Variables:', yangam::textdomain);
?><br />
					- %FILE_ID%<br />
					- %FILE%<br />
					- %FILE_ICON%<br />
					- %FILE_NAME%<br />
					- %FILE_DESCRIPTION%<br />
					- %FILE_HASH%<br />
					- %FILE_SIZE%<br />
					- %FILE_DATE%<br />
					- %FILE_TIME%<br />
					- %FILE_UPDATED_DATE%<br />
					- %FILE_UPDATED_TIME%<br />
					- %FILE_HITS%<br />
					- %FILE_ATTACHMENT_URL%<br />
					<br />
					<input type="button" name="RestoreDefault"
						   value="<?php
_e('Restore Default Template', yangam::textdomain);
?>"
						   onclick="attachment_default_templates('embedded_2');" class="button" />
				</td>
				<td><textarea cols="80" rows="20" id="yangam_template_embedded_2"
							  name="yangam_template_embedded_2"><?php
echo htmlspecialchars(stripslashes($yangam_template_embedded [1]));
?></textarea></td>
			</tr>

		</table>



		<table class="form-table" id="table_yangam_template_popup_2"  style="display:none;">

			<tr valign="top">
				<td width="30%"><strong><?php
_e('Download popup div template', yangam::textdomain);
?></strong><br />
<?php
_e('Displayed when you embedded a file within a post or a page and users <strong>DO NOT</strong> have permission to download the file.', yangam::textdomain);
?><br />
					<br />
<?php
_e('Allowed Variables:', yangam::textdomain);
?><br />
					- %FILE_ID%<br />
					- %FILE%<br />
					- %FILE_ICON%<br />
					- %FILE_NAME%<br />
					- %FILE_DESCRIPTION%<br />
					- %FILE_HASH%<br />
					- %FILE_SIZE%<br />
					- %FILE_DATE%<br />
					- %FILE_TIME%<br />
					- %FILE_UPDATED_DATE%<br />
					- %FILE_UPDATED_TIME%<br />
					- %FILE_HITS%<br />
					- %FILE_ATTACHMENT_URL%<br />
					<br />
					<input type="button" name="RestoreDefault"
						   value="<?php
_e('Restore Default Template', yangam::textdomain);
?>"
						   onclick="attachment_default_templates('popup_2');" class="button" />
				</td>
				<td><textarea cols="80" rows="20" id="yangam_template_popup_2"
							  name="yangam_template_popup_2"><?php
echo htmlspecialchars(stripslashes($yangam_template_popup [1]));
?></textarea></td>
			</tr>

		</table>


		<p class="submit" align="center"><input type="submit" name="Submit"
												class="button"
												value="<?php
												_e('Save Changes', yangam::textdomain);
?>" /></p>
	</div>
</form>
<?php
$yangam_template_embedded_default = yangam::get_default_value('yangam_template_embedded');
$yangam_template_custom_css_default = yangam::get_default_value('yangam_template_custom_css');
$yangam_template_popup_default = yangam::get_default_value('yangam_template_popup');
?>
<script type="text/javascript">
	/* <![CDATA[*/
	function attachment_default_templates(template) {
		var default_template;
		switch(template) {
			case "embedded":
				default_template = "<?php echo yangam_admin::js_fix($yangam_template_embedded_default[0]); ?>";
				break;
			case "embedded_2":
				default_template = "<?php echo yangam_admin::js_fix($yangam_template_embedded_default[1]); ?>";
				case "popup_2":
					default_template = "<?php echo yangam_admin::js_fix($yangam_template_popup_default[1]); ?>";
					break;
				case 'custom_css':
					default_template = "<?php echo yangam_admin::js_fix($yangam_template_custom_css_default); ?>";
					break;
				case "popup":
					default_template ="<?php echo yangam_admin::js_fix($yangam_template_popup_default[0]); ?>";
				}
				jQuery("#attachment_template_" + template).val(default_template);
			}
			/* ]]> */

			function set_template_table(type)
			{
				type = parseInt(type);
				switch(type)
				{
					case 0:
						jQuery('#table_yangam_template_embedded').show();
						jQuery('#table_yangam_template_embedded_2').show();
						jQuery('#table_yangam_template_popup').hide();
						jQuery('#table_yangam_template_popup_2').hide();
						break;
			
					case 1:
						jQuery('#table_yangam_template_embedded').hide();
						jQuery('#table_yangam_template_embedded_2').hide();	
						jQuery('#table_yangam_template_popup').show();
						jQuery('#table_yangam_template_popup_2').show();	
						break;
				}
			}
			jQuery(function($){
				set_template_table($('#yangam_display_type').val() );
			});
</script>
