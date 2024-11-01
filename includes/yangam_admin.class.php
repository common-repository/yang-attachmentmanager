<?php
/**
 * $Id: yangam_admin.class.php 2012-05-12 12:59:55 haibor $
 */
if (!defined('ABSPATH')){
	header('HTTP/1.1 403 Forbidden', true, 403);
	die('Please do not load this page directly. Thanks!');
}

/**
 * class for backend
 **/
class yangam_admin{
	private static $_message = '';
	private static $_error = '';
	private static $_attachment_folders = array();
	const local_server_file = 0;
	const local_pc_file = 1;
	const remote_file = 2;

	//重写 $_GET[]
	public static function get($key, $default = 0){
		return isset($_GET[$key]) ? $_GET[$key] : $default;
	}

	//重写 $_POST[]
	public static function post($key, $default = ''){
		return isset($_POST[$key]) ? $_POST[$key] : $default;
	}

	public static function add_message($msg){
		self::$_message .= '<span style="color:#4e9a06;">' . $msg . '</span><br />';
	}

	public static function add_block_message($msg){
		self::$_message .= $msg;
	}

	public static function add_error($err){
		self::$_error .= '<span style="color:#f00;">' . $err . '</span><br />';
	}

	public static function add_block_error($err){
		self::$_error .= $err;
	}

	public static function show_message_or_error($echo = 1){
		$have_msg = !empty(self::$_message) || !empty(self::$_error);
		$message = $have_msg ? '<!-- Last Action --><div id="message" class="updated fade"><p>' : '';
		if (!empty(self::$_message)){
			$message .= self::$_message;
		}

		if (!empty(self::$_error)){
			$message .= self::$_error;
		}
		$message = $have_msg ? $message . '</p></div>' : '';
		if ($echo)
			echo $message;
		else
			return $message;
	}
	
	//实例化后调用 yangam.class.php 中的函数 get_opt()
	public static function get_opt($key, $default =''){
		$yangam = yangam::instance();
		return $yangam->get_opt($key, $default);
	}
	
	//检查选项设置里的 上传路径 是否存在及可写
	public static function check_upload_dir(){
		$path = self::get_opt('yangam_path');
		if (!is_dir($path)){
			self::add_error(sprintf(__('Error: the download path %s does not exists!', yangam::textdomain), $path));
			self::show_message_or_error();
			die();
		}
		if (!is_writeable($path)){
			self::add_error(sprintf(__('Error: the download path %s is unwriteable,Please change the permission to <strong>0777</strong>!', yangam::textdomain), $path));
			self::show_message_or_error();
			die();
		}
	}

	/**检查文章是否附加有附件
	 * check a post has any attachment file or not
	 * @global type $wpdb
	 * @param type $post_id
	 * @return type 
	 **/
	public static function post_has_att($post_id){
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='yang_attached_id' AND post_id = %d", $post_id));
		
		//原
		//return $wpdb->get_var($wpdb->prepare("SELECT file_id FROM $wpdb->attachment_post WHERE post_id = %d", $post_id));
	}

	/**检查附件id是否存在
	 * check if file id exists
	 **/
	public static function id_exists($file_id){
		$file_id = (int) $file_id;
		global $wpdb;
		$result = $wpdb->query("SELECT file_id FROM $wpdb->attachments WHERE file_id=$file_id");
		if ($result > 0)
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * check the file is exists and is a normal file
	 * @param string $file the full path filename
	 * @return Boolean 
	 */
	public static function is_normal_file($path, $file){
		return file_exists($path . $file) && $file != '.' && $file != '..' && $file != '.htaccess';
	}

	### Function: Get Total Download Files

	public static function get_attachment_files($display = true){
		global $wpdb;
		$totalfiles = $wpdb->get_var("SELECT COUNT(file_id) FROM $wpdb->attachments");
		if ($display){
			echo number_format_i18n($totalfiles);
		} else {
			return number_format_i18n($totalfiles);
		}
	}

	### Function Get Total Download Size

	public static function get_attachment_size($display = true){
		global $wpdb;
		$totalsize = $wpdb->get_var("SELECT SUM(file_size) FROM $wpdb->attachments");
		if ($display){
			echo format_filesize($totalsize);
		} else {
			return format_filesize($totalsize);
		}
	}

### Function: Get Total Download Hits

	public static function get_attachment_hits($display = true){
		global $wpdb;
		$totalhits = $wpdb->get_var("SELECT SUM(file_hits) FROM $wpdb->attachments");
		if ($display){
			echo number_format_i18n($totalhits);
		} else {
			return number_format_i18n($totalhits);
		}
	}

### Function: File Permission

	public static function file_permission($file_permission){
		$file_permission_name = '';
		switch (intval($file_permission)){
			case - 2 :
				$file_permission_name = __('Hidden', 'yang-attachment');
				break;
			case - 1 :
				$file_permission_name = __('Everyone', 'yang-attachment');
				break;
			case 0 :
				$file_permission_name = __('Registered Users Only', 'yang-attachment');
				break;
			case 1 :
				$file_permission_name = __('At Least Contributor Role', 'yang-attachment');
				break;
			case 2 :
				$file_permission_name = __('At Least Author Role', 'yang-attachment');
				break;
			case 7 :
				$file_permission_name = __('At Least Editor Role', 'yang-attachment');
				break;
			case 10 :
				$file_permission_name = __('At Least Administrator Role', 'yang-attachment');
				break;
		}
		return $file_permission_name;
	}

### Function: Editable Timestamp

	public static function file_timestamp($file_timestamp){
		global $month;
		$day = gmdate('j', $file_timestamp);
		echo '<select id="file_timestamp_day" name="file_timestamp_day" size="1">' . "\n";
		for ($i = 1; $i <= 31; $i++){
			if ($day == $i){
				echo "<option value=\"$i\" selected=\"selected\">$i</option>\n";
			} else {
				echo "<option value=\"$i\">$i</option>\n";
			}
		}
		echo '</select>&nbsp;&nbsp;' . "\n";
		$month2 = gmdate('n', $file_timestamp);
		echo '<select id="file_timestamp_month" name="file_timestamp_month" size="1">' . "\n";
		for ($i = 1; $i <= 12; $i++) {
			if ($i < 10) {
				$ii = '0' . $i;
			} else {
				$ii = $i;
			}
			if ($month2 == $i)
			{
				echo "<option value=\"$i\" selected=\"selected\">$month[$ii]</option>\n";
			} else {
				echo "<option value=\"$i\">$month[$ii]</option>\n";
			}
		}
		echo '</select>&nbsp;&nbsp;' . "\n";
		$year = gmdate('Y', $file_timestamp);
		echo '<select id="file_timestamp_year" name="file_timestamp_year" size="1">' . "\n";
		for ($i = 2000; $i <= gmdate('Y'); $i++)
		{
			if ($year == $i){
				echo "<option value=\"$i\" selected=\"selected\">$i</option>\n";
			} else {
				echo "<option value=\"$i\">$i</option>\n";
			}
		}
		echo '</select>&nbsp;@' . "\n";
		echo '<span dir="ltr">' . "\n";
		$hour = gmdate('H', $file_timestamp);
		echo '<select id="file_timestamp_hour" name="file_timestamp_hour" size="1">' . "\n";
		for ($i = 0; $i < 24; $i++)
		{
			if ($hour == $i)
			{
				echo "<option value=\"$i\" selected=\"selected\">$i</option>\n";
			}
			else
			{
				echo "<option value=\"$i\">$i</option>\n";
			}
		}
		echo '</select>&nbsp;:' . "\n";
		$minute = gmdate('i', $file_timestamp);
		echo '<select id="file_timestamp_minute" name="file_timestamp_minute" size="1">' . "\n";
		for ($i = 0; $i < 60; $i++)
		{
			if ($minute == $i)
			{
				echo "<option value=\"$i\" selected=\"selected\">$i</option>\n";
			}
			else
			{
				echo "<option value=\"$i\">$i</option>\n";
			}
		}

		echo '</select>&nbsp;:' . "\n";
		$second = gmdate('s', $file_timestamp);
		echo '<select id="file_timestamp_second" name="file_timestamp_second" size="1">' . "\n";
		for ($i = 0; $i <= 60; $i++)
		{
			if ($second == $i)
			{
				echo "<option value=\"$i\" selected=\"selected\">$i</option>\n";
			}
			else
			{
				echo "<option value=\"$i\">$i</option>\n";
			}
		}
		echo '</select>' . "\n";
		echo '</span>' . "\n";
	}

	/**
	 * List Out All Files In attachments Directory
	 * @param type $dir
	 * @param type $orginal_dir  the file path prefix,the same as option yangam_path
	 */
	public static function list_attachments_folders($dir, $orginal_dir){
		if (is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				while (($file = readdir($dh)) !== false) {
					if ($file != '.' && $file != '..')
					{
						if (is_dir($dir . '/' . $file))
						{
							$folder = str_replace($orginal_dir, '', $dir . '/' . $file);
							self::$_attachment_folders[] = $folder;
							self::list_attachments_folders($dir . '/' . $file, $orginal_dir);
						}
					}
				}
				closedir($dh);
			}
		}
	}

	/**
	 * Print Listing Of Folders In Alphabetical Order
	 * 修正一bug,在files目录下面并无其它目录的情况下，即最首先安装插件时，$attachment_folders会为空的。
	 */
	public static function print_list_folders($dir, $orginal_dir){
		echo '<option value="/">/</option>' . "\n";
		self::list_attachments_folders($dir, $orginal_dir);
		if (self::$_attachment_folders)
		{
			natcasesort(self::$_attachment_folders);
			foreach (self::$_attachment_folders as $attachment_folder)
			{
				echo '<option value="' . $attachment_folder . '">' . $attachment_folder . '</option>' . "\n";
			}
		}
	}

###Function: tet basename of a file even if its name includes Chinese words

	public static function get_attachment_uniq_name($file){
		$ext = yangam::file_extension($file);
		if (strpos($file, '.'))
			$file_name = substr($file, 0, strrpos($file, '.'));
		else
			$file_name = $file;
		//$new_name = md5($file_name) . '.' . $ext;//直接将中文进行MD5处理，还是不能防止重名现象

		//by yang 改进：命名规则加入时间信息和随机数信息，防止重名现象
		$new_name = date('Ymd') . substr(md5( $file_name.microtime().rand(1, 99999999) ),8,16) . '.' . $ext;

		return $new_name;
	}

	public static function yang_rename($file_name){
		/*
		//检测文件名是否包含中文，包含汉字，就转换类似：201204258afe6f903aacca603a1d409a8250f337.jpg
		if (preg_match("/[\x7f-\xff]/", $file)){
			$file_name = self::get_attachment_uniq_name($file_name);
		}
		*/
		
		//是否重命名文件，防止上传重名文件
		$is_rename = "1";//此处可写在设置文件里，由用户自由选择是否重命名
		if( $is_rename=="1" ){//重命名
			$file_name = self::get_attachment_uniq_name($file_name);
		}

		return $file_name;
	}

	/**
	 * 重命名文件或移动文件，确保它不会覆盖的原文件
	 * rename the file or move the file ensure that it will not override the orignal file
	 * @param type $file_path
	 * @param type $file
	 * @return type 
	 */
	public static function attachment_rename_file($file_path, $file){
		$rename = false;
		$file_old = $file;
		//对中文名直接生成唯一文件名
		if (preg_match("/[\x7f-\xff]/", $file) || @preg_match("/[\x{4e00}-\x{9fa5}]+/u", $file)){
			$file = self::get_attachment_uniq_name($file);
		} else {
			$file = preg_replace("/[^A-Za-z0-9\-._\/\[\]\(\)]/", '', $file);//清除所有不为字母和数字等符号
		}
		$file = str_replace(' ', '_', $file);//将文件名中的空格替换为下划线
		if ($file != $file_old){
			if (file_exists($file_path . $file))
				rename($file_path . $file, $file_path . $file . '--' . date('Ymd-His') . '.bak');
			$rename = rename($file_path . $file_old, $file_path . $file);
		}
		if ($rename){
			return $file;
		} else {
			return $file_old;
		}
	}

	/**
	 * 将远程服务器文件下载至本地
	 * @uses download_url() http://codex.wordpress.org/Function_Reference/download_url
	 * @param type $url
	 * @param type $local_file_path
	 * @return type 
	 */
	public static function down_remote_file($url, $local_file_path){
		$yangam_options = self::get_opt('yangam_options');
		// maximum execution time in seconds
		@set_time_limit($yangam_options ['yam_time_limit']);
		$tmp_file = download_url($url, $yangam_options ['yam_time_limit']);
		//failed to fetch the file
		if (is_wp_error($tmp_file)){
			return FALSE;
		}

		if (rename($tmp_file, $local_file_path)){
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * 计算文件哈希校验值
	 * @param type $file
	 * @return type 
	 */
	public static function get_file_hash($file){
		$yangam_options = self::get_opt('yangam_options', 'md5');
		$yam_hash_func = $yangam_options ['yam_hash_func'] . '_file';
		if (file_exists($file) && function_exists($yam_hash_func))
			return $yam_hash_func($file);
		else
			return 'N/A';
	}

	/**
	 * if the php.ini size option value is not numeric
	 * @param int or string $value 
	 * @return int 
	 */
	public static function get_ini_size($value){
		if (!is_numeric($value)){
			if (strpos($value, 'M') !== false)
			{
				$value = intval($value) * 1024 * 1024;
			}
			elseif (strpos($value, 'K') !== false)
			{
				$value = intval($value) * 1024;
			}
			elseif (strpos($value, 'G') !== false)
			{
				$value = intval($value) * 1024 * 1024 * 1024;
			}
		}
		return $value;
	}

### Function: Get Max File Size That Can Be Uploaded

	public static function get_max_upload_size(){
		$upload_maxsize = self::get_ini_size(ini_get('upload_max_filesize'));
		$post_maxsize = self::get_ini_size(ini_get('post_max_size'));
		if ($upload_maxsize < $post_maxsize)
			return $upload_maxsize;
		else
			return $post_maxsize;
	}

	/**
	 * get max_execution_time
	 * @return type 
	 */
	public static function get_max_excution_time(){
		return ini_get('max_execution_time');
	}

	public static function get_max_input_time(){
		return ini_get('max_input_time');
	}

	/**
	 * Function: Get Remote File Size
	 * 增加非下载文件判断，若是网页跳转链接直接返回未知
	 */
	public static function remote_filesize($uri){
		//use wp_get_http_headers() better?
		$header_array = @get_headers($uri, 1);
		if ($header_array)
		{
			$file_size = $header_array ['Content-Length'];
			// be aware that there may be duplicated mime-type
			$mime_type = is_array($header_array['Content-Type']) ? $header_array['Content-Type'][0] : $header_array['Content-Type'];
			if (!empty($file_size) && 'text/html' != strtolower($mime_type))
			{
				return $file_size;
			}
		}
		return __('unknown', 'yang-attachment');
	}

	/**by yang
	 * 功能：附件处理表单：上传、编辑
	 * 参数：
	 *		$action-表单处理
	 *		$mode-表单模式:0-上传附件,1-编辑附件
	 *		$data-编辑附件时，从数据库读取的附件信息 $file
	 * 调用：yangam_admin::print_attachment_form( admin_url('admin.php?page=' . plugin_basename(__FILE__)), 1, $file );
	 **/
	public static function print_attachment_form( $action, $mode=0, $data='' ){
		$file_path = self::get_opt('yangam_path');
		//var_dump($data);exit;
		?>
		<form method="post" action="<?php echo $action; ?>" enctype="multipart/form-data">
			<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo self::get_max_upload_size(); ?>" />
			<?php if( $mode==1 ){ ?>
			<input type="hidden" name="file_id" value="<?php echo intval($data->file_id); ?>" />
			<input type="hidden" name="old_file" value="<?php echo stripslashes($data->file); ?>" />
			<?php } ?>
			<div class="wrap">
				<div id="icon-attachment" class="icon32"><br /></div>
				<h2>
				<?php if( $mode==0 ){
					_e('Add A File', 'yang-attachment');
				} else {
					_e('Edit A File', 'yang-attachment');
				} ?>
				</h2>
				<table class="form-table">
					<tr>
						<td valign="top"><strong><?php _e('File:', yangam::textdomain ) ?> <span style="color:#ff0000">*</span></strong></td>
						<td>
							<?php if( $mode==1 ){ ?>
							<!-- File Name -->
							<input type="radio" id="file_type_-1" name="file_type" value="-1" checked="checked" />&nbsp;&nbsp;<label	for="file_type_-1"><?php _e('Current File:', yangam::textdomain ); ?>&nbsp;<strong dir="ltr"><?php echo stripslashes($data->file); ?></strong></label>&nbsp;<br /><br />
							<?php } ?>
							
							<!-- Browse File -->
							<div style="margin-bottom:10px;">
								<input type="radio" id="file_type_0" name="file_type" value="0" />&nbsp;&nbsp;<label for="file_type_0"><?php _e('Local File:', yangam::textdomain ); ?></label>&nbsp;
								<input type="text" readonly="readonly" size="30" name="file" id="yangam-filetree-file"/>
								<input id="yangam-filetree-button" class="button" type="button" value="<?php _e('Browse Files', yangam::textdomain); ?>" onclick="document.getElementById('file_type_0').checked = true;" dir="ltr" />
								<div id="yangam-filetree" style="display:none;">	</div><br />
								<span><?php printf(__('Please upload the file to \'%s\' directory first.', yangam::textdomain), $file_path); ?></span>
							</div>
							
							<!-- Upload File -->
							<div style="margin-bottom:10px;">
								<input type="radio" id="file_type_1" name="file_type" value="1" <?php if( $mode==0 ){ echo 'checked="checked"'; } ?>  />&nbsp;&nbsp;<label for="file_type_1"><?php _e('Upload File:', yangam::textdomain ); ?></label>&nbsp;
								<input type="file" name="file_upload" size="25" onclick="document.getElementById('file_type_1').checked = true;" dir="ltr" /><!-- &nbsp;&nbsp;<?php _e('to', yangam::textdomain ); ?>&nbsp;&nbsp;
								<select name="file_upload_to" size="1" onclick="document.getElementById('file_type_1').checked = true;" dir="ltr">
									<?php self::print_list_folders($file_path, $file_path); ?>
								</select> --><br />
								<span><?php printf(__('Maximum file size is <strong>%s</strong>.', yangam::textdomain), yangam::format_filesize(self::get_max_upload_size())); ?></span>
								<span><?php printf(__('Maximum upload time is <strong>%s seconds</strong>.', yangam::textdomain), self::get_max_input_time()); ?></span><br />
							</div>
							
							<!-- Remote File -->
							<div style="">
								<input type="radio" id="file_type_2" name="file_type" value="2" />&nbsp;&nbsp;<label for="file_type_2"><?php _e('Remote File:', yangam::textdomain ); ?></label>&nbsp;
								<input type="text" name="file_remote" size="50" maxlength="255" onclick="document.getElementById('file_type_2').checked = true;" value="http://" dir="ltr" /> <br />
								<input type="checkbox" name="save_to_local" value="1" /> <?php echo __('Save to local host', yangam::textdomain); ?>&nbsp;&nbsp;<span><?php _e('The URL must contain <span style="color:red;">http://</span> or <span style="color:red;">ftp://</span> in front.', yangam::textdomain); ?></span>
								<!-- <br /><?php _e('to dir ', yangam::textdomain); ?>&nbsp;&nbsp;<input type="text" name="file_save_to" size="20" maxlength="50" value="/remote" />&nbsp;&nbsp;<span><?php _e('For directory please include <span style="color:red;">/</span> in front!', yangam::textdomain); ?></span> -->
							</div>
						</td>
					</tr>
					<tr>
						<td><strong><?php _e('File Name:', yangam::textdomain ); ?></strong></td>
						<td><input type="text" size="50" maxlength="200" name="file_name" <?php if( $mode==1 ){ ?>value="<?php echo htmlspecialchars(stripslashes($data->file_name)); ?>"<?php } ?> /></td>
					</tr>
					<tr>
						<td valign="top"><strong><?php _e('File Description:', yangam::textdomain ); ?></strong></td>
						<td><textarea rows="3" cols="47" name="file_des"><?php if( $mode==1 ){ echo htmlspecialchars(stripslashes($data->file_des)); } ?></textarea></td>
					</tr>
					<tr>
						<td valign="top"><strong><?php _e('File Size:', yangam::textdomain) ?></strong></td>
						<td><?php if( $mode==1 ){ echo yangam::format_filesize($data->file_size)."<br />"; } ?>
							<input type="text" size="10" name="file_size" <?php if( $mode==1 ){?>value="<?php echo $data->file_size; ?>"<?php }?> />&nbsp;<?php _e('bytes', yangam::textdomain ); ?><br />
							
							<?php if( $mode==1 ){?>
								<input type="checkbox" id="auto_filesize" name="auto_filesize" value="1" checked="checked" />&nbsp;<label for="auto_filesize"><?php _e('Auto Detection Of File Size', yangam::textdomain ) ?></label>
							<?php } else { ?>
								<span><?php _e('Leave blank for auto detection. Auto detection sometimes will not work for Remote File.', yangam::textdomain); ?></span>
							<?php } ?>
						</td>
					</tr>

					<?php if( $mode==1 ){?>
						<tr>
							<td valign="top"><strong><?php _e('File Hits:', yangam::textdomain ) ?></strong></td>
							<td><?php printf(_n('%s hit', '%s hits', number_format_i18n($data->file_hits),yangam::textdomain ), number_format_i18n($data->file_hits)) ?><br />
							<input type="text" size="6" maxlength="10" name="file_hits"
								value="<?php echo $data->file_hits; ?>" /><br />
							<input type="checkbox" id="reset_filehits" name="reset_filehits"
								value="1" />&nbsp;<label for="reset_filehits"><?php _e('Reset File Hits', yangam::textdomain ) ?></label></td>
						</tr>
						<tr>
							<td valign="top"><strong><?php _e('File Date:', yangam::textdomain ) ?></strong></td>
							<td><?php _e('Existing Timestamp:', yangam::textdomain ) ?> <?php echo mysql2date(sprintf('%s @ %s', get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $data->file_date)); ?><br />
							<?php self::file_timestamp($data->file_date); ?><br />
							<input type="checkbox" id="edit_filetimestamp"
								name="edit_filetimestamp" value="1" />&nbsp;<label
								for="edit_filetimestamp"><?php _e('Edit Timestamp', yangam::textdomain ) ?></label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input
								type="checkbox" id="edit_usetodaydate" value="1"
								onclick="file_usetodaydate();" />&nbsp;<label for="edit_usetodaydate"><?php _e('Use Today\'s Date', yangam::textdomain ) ?></label></td>
						</tr>
						<tr>
							<td valign="top"><strong><?php _e('File Updated Date:', yangam::textdomain ) ?></strong></td>
							<td><?php echo mysql2date(sprintf('%s @ %s', get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $data->file_updated_date)); ?></td>
						</tr>
						<tr>
							<td><strong><?php _e('File Last Downloaded Date:', yangam::textdomain ) ?></strong></td>
							<td><?php echo mysql2date(sprintf('%s @ %s', get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $data->file_last_downloaded_date)); ?></td>
						</tr>
						<tr>
							<td><strong><?php _e('Allowed To Download:', yangam::textdomain ) ?></strong></td>
							<td><select name="file_permission" size="1">
								<option value="-2" <?php selected('-2', $data->file_permission); ?>><?php _e('Hidden', yangam::textdomain ); ?></option>
								<option value="-1" <?php selected('-1', $data->file_permission); ?>><?php _e('Everyone', yangam::textdomain ); ?></option>
								<option value="0" <?php selected('0', $data->file_permission); ?>><?php _e('Registered Users Only', yangam::textdomain ); ?></option>
								<option value="1" <?php selected('1', $data->file_permission); ?>><?php _e('At Least Contributor Role', yangam::textdomain ); ?></option>
								<option value="2" <?php selected('2', $data->file_permission); ?>><?php _e('At Least Author Role', yangam::textdomain ); ?></option>
								<option value="7" <?php selected('7', $data->file_permission); ?>><?php _e('At Least Editor Role', yangam::textdomain ); ?></option>
								<option value="10" <?php selected('10', $data->file_permission); ?>><?php _e('At Least Administrator Role', yangam::textdomain ); ?></option>
							</select></td>
						</tr>
					<?php }else{ ?>
						<tr>
							<td><strong><?php _e('Starting File Hits:', yangam::textdomain) ?></strong></td>
							<td><input type="text" size="6" maxlength="10" name="file_hits" value="0" /></td>
						</tr>
						<tr>
							<td valign="top"><strong><?php _e('File Date:', yangam::textdomain) ?></strong></td>
							<td><?php self::file_timestamp(current_time('timestamp')); ?></td>
						</tr>
						<tr>
							<td><strong><?php _e('Allowed To Download:', yangam::textdomain) ?></strong></td>
							<td><select name="file_permission" size="1">
									<option value="-2"><?php _e('Hidden', yangam::textdomain); ?></option>
									<option value="-1" selected="selected"><?php _e('Everyone', yangam::textdomain); ?></option>
									<option value="0"><?php _e('Registered Users Only', yangam::textdomain); ?></option>
									<option value="1"><?php _e('At Least Contributor Role', yangam::textdomain); ?></option>
									<option value="2"><?php _e('At Least Author Role', yangam::textdomain); ?></option>
									<option value="7"><?php _e('At Least Editor Role', yangam::textdomain); ?></option>
									<option value="10"><?php _e('At Least Administrator Role', yangam::textdomain); ?></option>
								</select></td>
						</tr>
					<?php } ?>
					
					<tr>
						<td colspan="2" align="center">
							<?php if( $mode==1 ){ ?>
								<input type="submit" name="do" class="button" value="<?php _e('Edit File', yangam::textdomain ); ?>" />&nbsp;&nbsp;
							<?php }else{ ?>
								<input type="submit" name="do" class="button" value="<?php _e('Add File', yangam::textdomain ); ?>" />&nbsp;&nbsp;
							<?php } ?>
							<input type="button" name="cancel" class="button" value="<?php _e('Cancel', yangam::textdomain ); ?>" onclick="javascript:history.go(-1)" /></td>
					</tr>
				</table>
			</div>
		</form>
		<?php
	}
	
	/**by yang
	 * 功能：附件删除表单
	 * 参数：
	 *		$action-表单处理
	 *		$data-删除附件时，从数据库读取的附件信息 $file
	 * 调用：yangam_admin::print_attachment_form( admin_url('admin.php?page=' . plugin_basename(__FILE__)), 1, $file );
	 **/
	public static function print_delete_form( $action, $data='' ){
		?>
		<form method="post" action="<?php echo $action ?>">
			<input type="hidden" name="file_id" value="<?php echo intval($data->file_id); ?>" />
			<input type="hidden" name="file" value="<?php echo stripslashes($data->file); ?>" />
			<input type="hidden" name="file_name" value="<?php echo htmlspecialchars(stripslashes($data->file_name)); ?>" />
			<div class="wrap">
				<div id="icon-attachment" class="icon32"><br /></div>
				<h2><?php _e('Delete A File', yangam::textdomain ); ?></h2><br style="" />
				<table class="widefat">
					<tr>
						<td valign="top"><strong><?php _e('File:', yangam::textdomain ) ?></strong></td>
						<td><span dir="ltr"><?php echo stripslashes($data->file); ?></span></td>
					</tr>
					<tr class="alternate">
						<td><strong><?php _e('File Name:', yangam::textdomain ); ?></strong></td>
						<td><?php echo stripslashes($data->file_name); ?></td>
					</tr>
					<tr>
						<td valign="top"><strong><?php _e('File Description:', yangam::textdomain ); ?></strong></td>
						<td><?php echo stripslashes($data->file_des); ?></td>
					</tr>
					<tr>
						<td><strong><?php _e('File Size:', yangam::textdomain ); ?></strong></td>
						<td><?php echo yangam::format_filesize($data->file_size); ?></td>
					</tr>
					<tr class="alternate">
						<td><strong><?php _e('File Hits', yangam::textdomain ); ?></strong></td>
						<td><?php echo number_format_i18n($data->file_hits); ?> <?php _e('hits', yangam::textdomain ); ?></td>
					</tr>
					<tr>
						<td><strong><?php _e('File Date', yangam::textdomain ); ?></strong></td>
						<td><?php echo mysql2date(sprintf('%s @ %s', get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $data->file_date)); ?></td>
					</tr>
					<tr class="alternate">
						<td><strong><?php _e('File Updated Date:', yangam::textdomain ) ?></strong></td>
						<td><?php echo mysql2date(sprintf('%s @ %s', get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $data->file_updated_date)); ?></td>
					</tr>
					<tr>
						<td><strong><?php _e('File Last Downloaded Date:', yangam::textdomain ) ?></strong></td>
						<td><?php echo mysql2date(sprintf('%s @ %s', get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $data->file_last_downloaded_date)); ?></td>
					</tr>
					<tr class="alternate">
						<td><strong><?php _e('Allowed To Download:', yangam::textdomain ) ?></strong></td>
						<td><?php echo yangam_admin::file_permission($data->file_permission); ?></td>
					</tr>
					<?php if(!yangam::is_remote_file(stripslashes($data->file))): ?>
					<tr>
						<td colspan="2" align="center"><input type="checkbox" id="unlinkfile" name="unlinkfile" value="1" />&nbsp;<label for="unlinkfile"><?php _e('Delete File From Server?', yangam::textdomain ); ?></label></td>
					</tr>
					<?php endif; ?>
					<tr class="alternate">
						<td colspan="2" align="center">
							<input type="submit" name="do" value="<?php _e('Delete File', yangam::textdomain ); ?>" class="button" onclick="return confirm('<?php echo sprintf( __("You Are About To The Delete This File \\'%s(%s)\\'.\\nThis Action Is Not Reversible.\\n\\n Choose \\'Cancel\\' to stop, \\'OK\\' to delete.",yangam::textdomain),stripslashes(strip_tags($data->file_name)),stripslashes($data->file) ) ;?> ');" />&nbsp;&nbsp;
							<input type="button" name="cancel" value="<?php _e('Cancel', yangam::textdomain ); ?>" class="button" onclick="javascript:history.go(-1)" /></td>
					</tr>
				</table>
			</div>
		</form>
	<?php
	}
	
	/**by yang
	 * 功能：附件回收表单
	 * 参数：
	 *		$action-表单处理
	 *		$data-删除附件时，从数据库读取的附件信息 $file
	 * 调用：yangam_admin::print_attachment_form( admin_url('admin.php?page=' . plugin_basename(__FILE__)), 1, $file );
	 **/
	public static function print_trash_form( $action, $data='' ){
		?>
		<form method="post" action="<?php echo $action ?>">
			<input type="hidden" name="file_id" value="<?php echo intval($data->file_id); ?>" />
			<input type="hidden" name="file" value="<?php echo stripslashes($data->file); ?>" />
			<input type="hidden" name="file_name" value="<?php echo htmlspecialchars(stripslashes($data->file_name)); ?>" />
			<div class="wrap">
				<div id="icon-attachment" class="icon32"><br /></div>
				<h2><?php _e('Trash File', yangam::textdomain ); ?></h2><br style="" />
				<table class="widefat">
					<tr>
						<td valign="top"><strong><?php _e('File:', yangam::textdomain ) ?></strong></td>
						<td><span dir="ltr"><?php echo stripslashes($data->file); ?></span></td>
					</tr>
					<tr class="alternate">
						<td><strong><?php _e('File Name:', yangam::textdomain ); ?></strong></td>
						<td><?php echo stripslashes($data->file_name); ?></td>
					</tr>
					<tr>
						<td valign="top"><strong><?php _e('File Description:', yangam::textdomain ); ?></strong></td>
						<td><?php echo stripslashes($data->file_des); ?></td>
					</tr>
					<tr>
						<td><strong><?php _e('File Size:', yangam::textdomain ); ?></strong></td>
						<td><?php echo yangam::format_filesize($data->file_size); ?></td>
					</tr>
					<tr class="alternate">
						<td><strong><?php _e('File Hits', yangam::textdomain ); ?></strong></td>
						<td><?php echo number_format_i18n($data->file_hits); ?> <?php _e('hits', yangam::textdomain ); ?></td>
					</tr>
					<tr>
						<td><strong><?php _e('File Date', yangam::textdomain ); ?></strong></td>
						<td><?php echo mysql2date(sprintf('%s @ %s', get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $data->file_date)); ?></td>
					</tr>
					<tr class="alternate">
						<td><strong><?php _e('File Updated Date:', yangam::textdomain ) ?></strong></td>
						<td><?php echo mysql2date(sprintf('%s @ %s', get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $data->file_updated_date)); ?></td>
					</tr>
					<tr>
						<td><strong><?php _e('File Last Downloaded Date:', yangam::textdomain ) ?></strong></td>
						<td><?php echo mysql2date(sprintf('%s @ %s', get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $data->file_last_downloaded_date)); ?></td>
					</tr>
					<tr class="alternate">
						<td><strong><?php _e('Allowed To Download:', yangam::textdomain ) ?></strong></td>
						<td><?php echo yangam_admin::file_permission($data->file_permission); ?></td>
					</tr>
					<tr class="alternate">
						<td colspan="2" align="center">
							<input type="submit" name="do" value="<?php _e('Trash File', yangam::textdomain ); ?>" class="button" />&nbsp;&nbsp;
							<input type="button" name="cancel" value="<?php _e('Cancel', yangam::textdomain ); ?>" class="button" onclick="javascript:history.go(-1)" /></td>
					</tr>
				</table>
			</div>
		</form>
	<?php
	}

	/**by yang
	 * 功能：文章嵌入的附件
	 * 参数：
	 *		$post_id-当前文章id
	 * 调用：yangam_admin::print_post_attachment( $post_id );
	 **/
	public static function print_post_attachment( $post_id ){
		global $wpdb;
		$file_path = self::get_opt('yangam_path');
		
		// IDs should be integers
		//$post_id = isset( $_REQUEST['post_id'] )? intval( $_REQUEST['post_id'] ) : 0;

		$get_total_files = $wpdb->get_var("SELECT COUNT(post_id) FROM $wpdb->postmeta WHERE meta_key='yang_attached_id' AND post_id=$post_id");
			//原
			//$get_total_files = $wpdb->get_var("SELECT COUNT(file_id) FROM $wpdb->attachment_post WHERE post_id=$post_id");

		/**
		 * Get Files	pm:postmeta，am:attachments
		 * LEFT JOIN：左表会全部显示，显只接受where的条件限制；右表则接受on和where的条件限制
		 **/
		$files = $wpdb->get_results("SELECT * FROM $wpdb->postmeta pm LEFT JOIN $wpdb->attachments am ON pm.meta_value=am.file_id WHERE pm.meta_key='yang_attached_id' AND pm.post_id=$post_id ORDER BY pm.meta_value DESC");
			//原
			//$files = $wpdb->get_results("SELECT * FROM $wpdb->attachment_post dp LEFT JOIN $wpdb->attachments d ON dp.file_id=d.file_id WHERE dp.post_id=$post_id ORDER BY dp.file_id DESC");
			
		?>
		<div class="wrap">
			<div id="icon-attachment" class="icon32"><br /></div>
		<p><?php echo sprintf(__('%d Files Attached to this post', yangam::textdomain ), $get_total_files); ?></p>

		<table class="widefat yangam-tab">
			<thead>
				<tr>
					<th><?php _e('ID', yangam::textdomain ); ?></th>
					<th><?php _e('File', yangam::textdomain ); ?></th>
					<th><?php _e('Size', yangam::textdomain ); ?></th>
					<th><?php _e('Hits', yangam::textdomain ); ?></th>
					<th><?php _e('Permission', yangam::textdomain ); ?></th>
					<th><?php _e('Date/Time Added', yangam::textdomain ); ?></th>
				</tr>
			</thead>
			<?php
			if($files){
				$i = 0;
				foreach($files as $file) {
					$meta_value = intval($file->meta_value);//读取file_id
					$file_name = stripslashes($file->file);
					$file_des = stripslashes($file->file_des);
					$file_nicename = !yangam::is_remote_file(stripslashes($file->file)) && !file_exists($file_path.stripslashes($file->file))?'<span style="color:red;">'.sprintf(__('file <strong>%s</strong> does not exists!', yangam::textdomain ),$file->file_name).'</span>':stripslashes($file->file_name);
					$file_des = stripslashes($file->file_des);
					$file_size = $file->file_size;
					$file_date = mysql2date(get_option('date_format'), gmdate('Y-m-d H:i:s', $file->file_date));
					$file_time = mysql2date(get_option('time_format'), gmdate('Y-m-d H:i:s', $file->file_date));
					$file_updated_date = mysql2date(get_option('date_format'), gmdate('Y-m-d H:i:s', $file->file_updated_date));
					$file_updated_time = mysql2date(get_option('time_format'), gmdate('Y-m-d H:i:s', $file->file_updated_date));
					$file_last_downloaded_date = mysql2date(get_option('date_format'), gmdate('Y-m-d H:i:s', $file->file_last_downloaded_date));
					$file_last_downloaded_time = mysql2date(get_option('time_format'), gmdate('Y-m-d H:i:s', $file->file_last_downloaded_date));
					$file_hits = intval($file->file_hits);
					$file_permission = self::file_permission($file->file_permission);
					$file_name_actual = yangam::get_basename($file_name);
					if($i%2 == 0) {
						$style = '';
					}  else {
						$style = ' class="alternate"';
					}
					echo "<tr$style>\n";
					echo '<td valign="top">'.number_format_i18n($meta_value).'</td>'."\n";
					echo "<td>$file_nicename<br /><strong>&raquo;</strong> <i dir=\"ltr\">".yangam::snippet_text($file_name, 45)."</i><br /><br />";

					echo "</td>\n";
					echo '<td style="text-align: center;">'.yangam::format_filesize($file_size).'</td>'."\n";
					echo '<td style="text-align: center;">'.number_format_i18n($file_hits).'</td>'."\n";
					echo '<td style="text-align: center;">'.$file_permission.'</td>'."\n";
					echo "<td>$file_date , $file_time</td>\n";
					echo '</tr>';
					$i++;
				}
			} else {
				echo '<tr><td colspan="9" align="center"><strong>'.__('No Files Found', yangam::textdomain ).'</strong></td></tr>';
			}
			?>
		</table>
	<?php
	}


	//检查文件是否重复上传，针对上传文件不重命名的时候
	public static function check_duplicate_file($file_type, $file, $file_hash){
		global $wpdb;
		if (self::local_server_file === $file_type || self::local_pc_file === $file_type)
			$duplicate = $wpdb->query("SELECT file_id FROM $wpdb->attachments WHERE file_hash='$file_hash'");
		else
			$duplicate = $wpdb->query("SELECT file_id FROM $wpdb->attachments WHERE file='$file'");
		$duplicate_id = $wpdb->get_var();
		if ($duplicate_id > 0){
			self::add_error(sprintf(__('Error:File \'%s \' has already been added!The file_id is:<strong>%d</strong>', yangam::textdomain), $file, $duplicate_id));
			return 1;
		}
		return 0;
	}

	/**
	 * 功能：按日期生成目录
	 * 说明：如果没有指定目录，则按当前日期自动生成
	 * 参数：$file_upload_to 形如 /2012/05/01/
	 */
	public static function create_dir_by_date( $file_upload_to='/' ){
		//by yang: 自定义上传目录，先重置一下 $file_upload_to 参数为非 / 的目录，以方便下面的测试
		if (empty($file_upload_to)){
			$file_upload_to = gmdate('/Y/m/', time());//设定上传目录为当前日期 /uploads/2012/04/24/，可增加选择项 /uploads/2012/04/
		}

		$file_path = self::get_opt('yangam_path');

		//by yang: 生成存放目录
		$saveto_array = explode('/', str_replace('./', '', $file_upload_to));
		
		$yang_mkdir = '';//保证是在附件保存目录来创建文件夹
		foreach ($saveto_array AS $dir){
			$yang_mkdir .= $dir . '/';
			if(!is_dir($file_path.'/' . $yang_mkdir)){
				mkdir($file_path.'/' . $yang_mkdir, 0777);
				//@mkdir($file_path . $file_save_to, 0777, true);
			}
		}
	}
	
	// 三种上传形式一：files on server
	public static function add_server_file(){
		$file_path = self::get_opt('yangam_path');
		//relative path (including file name ) to upload folder
		$file = addslashes(trim(yangam_admin::post('file')));
		// file_name is the display name of the download file
		$file_name = yangam::space_to_underscore($file);
		//if the file was user uploaded via FTP client ,then the file should be renamed.
		$file = self::attachment_rename_file($file_path, $file);
		$file_size = filesize($file_path . $file);
		$file_hash = self::get_file_hash($file_path . $file);
		return array('file_name' => $file_name,
			'file' => $file,
			'file_size' => $file_size,
			'file_hash' => $file_hash,
		);
	}
	
	// 三种上传形式二：upload local file to server
	public static function upload_local_file($file_upload_to, $is_edit = 0){
		global $wpdb;
		$file_path = self::get_opt('yangam_path');//后台设置的附件保存绝对路径，如 E:/nuodou/wp-content/files

		if (empty($file_upload_to)){
			//return FALSE;
			$file_upload_to = gmdate('Y/m/', time());//设定上传目录 uploads/2012/04/24/，可增加选择项 uploads/2012/04/
		}
		self::create_dir_by_date( $file_upload_to );
		
		if ($_FILES['file_upload']['size'] > self::get_max_upload_size()){//php 被上传文件的大小
			self::add_error(sprintf(__('File Size Too Large. Maximum Size Is %s', yangam::textdomain), yangam::format_filesize(self::get_max_upload_size())));
			return FALSE;
		} else {
			if (empty($_FILES['file_upload']['name'])){//php 被上传文件的名称
				self::add_error(__('Please select a File to be upload!', yangam::textdomain));
				return FALSE;
			}

			if (is_uploaded_file($_FILES['file_upload']['tmp_name'])){//php 文件上传后得临时文件名
				if ($file_upload_to != '/'){//如果上传到的路径不是根目录
					//$file_upload_to = $file_upload_to . '/';
					$file_upload_to = '/'.$file_upload_to;//by yang
				}
				
				$file_name = yangam::space_to_underscore($_FILES['file_upload']['name']);//将原始文件名空格替换为下划线
				$uniq_name = self::yang_rename($file_name);
				
				
				//是否编辑附件
				if ($is_edit){
					$old_file_name = $wpdb->get_var("SELECT file FROM $wpdb->attachments WHERE file_id = $file_id");
					if ($old_file_name == $file_upload_to . $uniq_name) //更新文件时重命名旧文件
						@rename($file_path . $old_file_name, $file_path . $old_file_name . '--' . date('Ymd-His') . '.bak');
				}

				// E:/haibor/code/nuodou/wp-content/files + /uploads/2012/04/24/ + gcy.jpg
				$full_path = $file_path . $file_upload_to . $uniq_name;
				if(move_uploaded_file($_FILES['file_upload']['tmp_name'], $full_path)){
					$relative_file_path = $file_upload_to . $uniq_name;
					//remove invalid chars and rename non-latin chars
					$file = self::attachment_rename_file($file_path, $relative_file_path);
					$file_size = filesize($file_path . $file);
					$file_hash = self::get_file_hash($file_path . $file);
				}
				else
				{
					self::add_error(__('Error In Uploading File', yangam::textdomain));
					return FALSE;
				}
			}
			else
			{
				self::add_error(__('Error In Uploading File', yangam::textdomain));
				return FALSE;
			}
		}
		return array('file_name' => $file_name,
			'file' => $file,
			'file_size' => $file_size,
			'file_hash' => $file_hash,
		);
	}
	
	/**
	 * 功能：三种上传形式三 add remote file
	 * 参数：$file_remote 远程文件URL；$save_to_local 是否保存到本地；$file_save_to 本地目录
	 **/
	public static function add_remote_file($file_remote, $file_save_to='/', $save_to_local = 0){
		if (empty($file_remote) || !(strlen($file_remote) > 7 ) || !yangam::is_remote_file($file_remote)){
			self::add_error(__('Error: Please give me a valid URL.', yangam::textdomain));
			return FALSE;
		}
		$file_path = self::get_opt('yangam_path');
		$file = addslashes(trim($file_remote));
		$uniq_name = self::yang_rename(yangam::get_basename($file));
		$file_name = yangam::space_to_underscore($file);
		
		//try to get the remote file size
		$file_size = self::remote_filesize($file);
		$file_hash = 'N/A';

		//save to local
		if( isset($save_to_local) && (1 == $save_to_local) ){
			$file_path = self::get_opt('yangam_path');
			
			if( !empty($file_save_to) ){
				if( $file_save_to != '/' ){
					$file_save_to = $file_save_to . '/';
				}
			} else {
				$file_save_to = gmdate('/Y/m/', time());//设定上传目录为当前日期 /uploads/2012/04/24/，可增加选择项 /uploads/2012/04/
			}
			self::create_dir_by_date( $file_save_to );

			$new_file = $file_path . $file_save_to . $uniq_name;
			//exit($new_file);
			if (!yangam_admin::down_remote_file($file, $new_file)){
				self::add_error(__('Error In downloading File to local host!', yangam::textdomain));
				return FALSE;
			} else {
				self::add_message(__('Downloading File to local host successfuly!', yangam::textdomain));
				//override the variables 
				$file = $file_save_to . $uniq_name; //注意这里要加上目录
				$file = self::attachment_rename_file($file_path . $file_save_to, $file);
				$file_size = filesize($file_path . $file);
				$file_hash = self::get_file_hash($file_path . $file);
			}
		}

		return array('file_name' => $file_name,
			'file' => $file,
			'file_size' => $file_size,
			'file_hash' => $file_hash,
		);
	}

	/**
	 * 将上传文件信息插入数据库，并提供附件插入文章按钮
	 * insert the file data to DB
	 * @param type $data 
	 */
	public static function add_new_file($data, $tab = 0){
		global $wpdb;
		extract($data);
		if (!empty($_POST['file_name'])){
			$file_name = addslashes(trim($_POST['file_name']));
		}
		//var_dump($file);exit;
		if (empty($file_name))
		{
			self::add_error(sprintf(__('Error:File name REQUIRED!Please assign a file name for displaying.', yangam::textdomain), $file, $duplicate_id));
		}
		if (!empty($_POST['file_size']))
		{
			$file_size = intval($_POST['file_size']);
		}
		$file_des = addslashes(trim($_POST['file_des']));

		$file_hits = intval($_POST['file_hits']);
		$file_timestamp_day = intval($_POST['file_timestamp_day']);
		$file_timestamp_month = intval($_POST['file_timestamp_month']);
		$file_timestamp_year = intval($_POST['file_timestamp_year']);
		$file_timestamp_hour = intval($_POST['file_timestamp_hour']);
		$file_timestamp_minute = intval($_POST['file_timestamp_minute']);
		$file_timestamp_second = intval($_POST['file_timestamp_second']);
		$file_date = gmmktime($file_timestamp_hour, $file_timestamp_minute, $file_timestamp_second, $file_timestamp_month, $file_timestamp_day, $file_timestamp_year);
		$file_permission = intval($_POST['file_permission']);
		$addfile = $wpdb->query("INSERT INTO $wpdb->attachments VALUES (NULL, '$file', '$file_name', '$file_des', '$file_hash', '$file_size', '$file_date', '$file_date', '$file_date', $file_hits, 'open', $file_permission)");
		
		if (!$addfile){
			self::add_error(sprintf(__('Error In Adding File \'%s (%s)\' To Database!', yangam::textdomain), $file_name, $file));
		} else {
			self::add_message(sprintf(__('File \'%s (%s)\' Added Successfully!<br/><strong>File ID is: %s </strong>', yangam::textdomain), $file_name, $file, $wpdb->insert_id));
			if ($tab){
				//$GLOBALS['insert_shortcode_down'] = '[download id="' . $wpdb->insert_id . '"';
				//$GLOBALS['insert_shortcode_pic'] = '<img src="'.get_option('yangam_path_url').$file.'" id="yang-attachment_'.$wpdb->insert_id.'">';//by yang 新增：增加插入图片的模板
				self::add_block_message('<div style="margin:10px auto;">');
				self::add_block_message('<h3> ' . __('Insert new download into post', 'yang-attachment') . '</h3>');
				self::add_block_message('<p class="submit">');
				self::add_block_message('<input type="submit" id="insert_down" class="button button-primary" onclick="insert_into_post_down(' . $wpdb->insert_id . ');" name="insertintopost" value="' . __('Insert into post download', 'yang-attachment') . '" />');
				self::add_block_message('<input type="submit" id="insert_pic" class="button button-primary" name="insertintopost" onclick="insert_into_post_pic('.$wpdb->insert_id.', \''.ltrim(stripslashes($file),'/').'\');" value="' . __('Insert into post pic', 'yang-attachment') . '" />');//by yang 新增插入图片按钮
				self::add_block_message('</p>');
				self::add_block_message('</div>');
			}
		}
	}
	
	// 删除一个附件
	public static function delete_file(){
		global $wpdb;
		$file_path = self::get_opt('yangam_path');
		$file_id = intval(self::post('file_id'));
		$file = trim(self::post('file'));
		$file_name = trim(self::post('file_name'));
		$unlinkfile = intval(self::post('unlinkfile', 0));//从服务器物理删除文件
		if ($unlinkfile == 1){
			if (!@unlink($file_path . '/' . $file)){
				self::add_error(sprintf(__('Error In Deleting File \'%s (%s)\' From Server', yangam::textdomain), $file_name, $file));
			} else {
				self::add_message(sprintf(__('File \'%s (%s)\' Deleted From Server Successfully', yangam::textdomain), $file_name, $file));
			}
		}
		$deletefile = $wpdb->query("DELETE FROM $wpdb->attachments WHERE file_id = $file_id");
		//var_dump($deletefile);exit;
		if (!$deletefile){
			self::add_error(sprintf(__('Error In Deleting File \'%s (%s)\'', yangam::textdomain), $file_name, $file));
		} else {
			self::add_message(sprintf(__('File \'%s (%s)\' Deleted Successfully', yangam::textdomain), $file_name, $file));
		}
	}

	// 回收/还原一个附件
	public static function trash_or_open( $mode='', $file_id='' ){
		global $wpdb;
		$file = $wpdb->get_row("SELECT file_name,file_status FROM $wpdb->attachments WHERE file_id = $file_id");
		if( $file->file_status==$mode ){
			die('没有改变附件的状态！<br /><a href="admin.php?page=yang-attachmentmanager/yangam-manager.php">返回</a>');
		}

		$trashfile = $wpdb->query("UPDATE $wpdb->attachments SET file_status = '$mode' WHERE file_id = $file_id");
		//var_dump($trashfile);exit;
		if (!$trashfile){
			if( $mode=='open' ){
				self::add_error(sprintf(__('Error In Untrashing File \'%s\'', yangam::textdomain), $file->file_name));
			} else {
				self::add_error(sprintf(__('Error In Trashing File \'%s\'', yangam::textdomain), $file->file_name));
			}
		} else {
			if( $mode=='open' ){
				self::add_message(sprintf(__('File \'%s\' Untrashed Successfully', yangam::textdomain), $file->file_name));
			} else {
				self::add_message(sprintf(__('File \'%s\' Trashed Successfully', yangam::textdomain), $file->file_name));
			}
		}
	}

	public static function js_fix($text_for_js){
		$text_for_js = preg_replace('/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes($text_for_js));
		$text_for_js = str_replace("\r", '', $text_for_js);
		$text_for_js = str_replace("\n", '\\n', addslashes($text_for_js));
		return $text_for_js;
	}

}

// enc class yangam_admin
