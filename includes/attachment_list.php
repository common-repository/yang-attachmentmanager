<?php

/**
 * $Id: attachment_list.php 2012-05-12 13:04:11 haibor $
 * @Description:被 yangam-manager.php 和 upload-or-insert.php 文件包含调用
 **/
if ( !defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 403 Forbidden', true, 403 );
	die ('Please do not load this page directly. Thanks!');
}

/**
 * $yang_tab 的作用是判断调用附件列表的页面
 * 0-wordpress左侧管理面板进入的附件库列表
 * 1-编辑文章时插入附件的弹窗列表，即 upload-or-insert.php 页面
 **/
$yang_tab = 0;

//SCRIPT_NAME：包含当前脚本的路径。__FILE__ 常量包含当前脚本(例如包含文件)的完整路径和文件名。
//SCRIPT_NAME 输出：/wp-admin/admin.php
$current_file_base_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF'];
if( basename( $current_file_base_name ) == 'upload-or-insert.php' ){
	$yang_tab = 1;
}

global $wpdb;
// IDs should be integers
$post_id = isset( $_REQUEST['post_id'] )? intval( $_REQUEST['post_id'] ) : 0;

$file_path = yangam_admin::get_opt('yangam_path');//附件设置里的文件保存绝对路径
$file_path_url = yangam_admin::get_opt('yangam_path_url');//附件设置里的文件下载URL
$file_page = intval( yangam_admin::get('filepage',1));
$file_sortby = trim(yangam_admin::get('by'));
$file_sortby_text = '';
$file_sortorder = trim( yangam_admin::get('order') );
$file_sortorder_text = '';
$file_perpage = intval( yangam_admin::get('perpage',10));
$file_sort_url = '';
$file_search = addslashes( yangam_admin::get('search', '') );
$file_search_query = '';

### Form Sorting URL
if(!empty($file_sortby)) {
	$file_sort_url .= '&amp;by='.$file_sortby;
}
if(!empty($file_sortorder)) {
	$file_sort_url .= '&amp;order='.$file_sortorder;
}
if(!empty($file_perpage)) {
	$file_sort_url .= '&amp;perpage='.$file_perpage;
}


### Searching
if(!empty($file_search)) {
	$file_search_query = "AND (file LIKE ('%$file_search%') OR file_name LIKE('%$file_search%') OR file_des LIKE ('%$file_search%'))";
	$file_sort_url .= '&amp;search='.stripslashes($file_search);
}


### Get Order By
switch($file_sortby) {
	case 'id':
		$file_sortby = 'file_id';
		$file_sortby_text = __('File ID', yangam::textdomain );
		break;
	case 'file':
		$file_sortby = 'file';
		$file_sortby_text = __('File', yangam::textdomain );
		break;
	case 'size':
		$file_sortby = '(file_size+0.00)';
		$file_sortby_text = __('File Size', yangam::textdomain );
		break;
	case 'hits':
		$file_sortby = 'file_hits';
		$file_sortby_text = __('File Hits', yangam::textdomain);
		break;
	case 'permission':
		$file_sortby = 'file_permission';
		$file_sortby_text = __('File Permission', yangam::textdomain);
		break;
	case 'date':
		$file_sortby = 'file_date';
		$file_sortby_text = __('File Date', yangam::textdomain);
		break;
	case 'updated_date':
		$file_sortby = 'file_updated_date';
		$file_sortby_text = __('File Updated Date', yangam::textdomain);
		break;
	case 'last_downloaded_date':
		$file_sortby = 'file_last_downloaded_date';
		$file_sortby_text = __('File Last Downloaded Date', yangam::textdomain);
		break;
	case 'name':
	default:
		//还是默认为ID方便一点
		$file_sortby = 'file_id';
		$file_sortby_text = __('File ID', yangam::textdomain);
}


### Get Sort Order
switch($file_sortorder) {
	case 'asc':
		$file_sortorder = 'ASC';
		$file_sortorder_text = __('Ascending', yangam::textdomain);
		break;
	case 'desc':
	default:
		//默认为ID的 DESC方便些
		$file_sortorder = 'DESC';
		$file_sortorder_text = __('Descending', yangam::textdomain );
}


$get_total_files = $wpdb->get_var("SELECT COUNT(file_id) FROM $wpdb->attachments WHERE 1=1 $file_search_query");
			
### Checking $file_page and $offset
	if(empty($file_page) || $file_page == 0) { $file_page = 1; }
	if(empty($offset)) { $offset = 0; }
	if(empty($file_perpage) || $file_perpage == 0) { $file_perpage = 20; }

### Determin $offset
	$offset = ($file_page-1) * $file_perpage;

### Determine Max Number Of Polls To Display On Page
	if(($offset + $file_perpage) > $get_total_files) {
		$max_on_page = $get_total_files;
	} else {
		$max_on_page = ($offset + $file_perpage);
	}

### Determine Number Of Polls To Display On Page
	if (($offset + 1) > ($get_total_files)) {
		$display_on_page = $get_total_files;
	} else {
		$display_on_page = ($offset + 1);
	}

### Determing Total Amount Of Pages
	$total_pages = ceil($get_total_files / $file_perpage);

/**
 * 功能：Get Files 获取附件文件并读取附加到的文章信息
 * 说明：left join 中左表的全部记录将全部被查询显示，on 后面的条件对它不起作用，除非在后面再加上where来进行筛选；d.为attachments表，dp.为attachment_post表
 * SQL语句注解：
 * dp.file_id as dp_file_id,dp.post_id 表示读取attachment_post表中的 file_id,dp.post_id 字段
 * d.* 表示读取attachments表中的 所有 字段
 * 
 **/
	//原
	//$files = $wpdb->get_results("SELECT dp.file_id as dp_file_id,dp.post_id,d.* FROM $wpdb->attachments d LEFT JOIN $wpdb->attachment_post dp ON d.file_id = dp.file_id WHERE 1=1 $file_search_query ORDER BY $file_sortby $file_sortorder LIMIT $offset, $file_perpage");

	//by yang修改可用: 将 attachment_post 表合并至 attachments 后，无需查询前表
	//$files = $wpdb->get_results("SELECT * FROM $wpdb->attachments WHERE 1=1 $file_search_query ORDER BY $file_sortby $file_sortorder LIMIT $offset, $file_perpage");
	
	//am:attachemts表，pm:postmeta表
	$files = $wpdb->get_results("SELECT pm.meta_value as pm_meta_value,pm.post_id,am.* FROM $wpdb->attachments am LEFT JOIN $wpdb->postmeta pm ON pm.meta_key='yang_attached_id' AND am.file_id = pm.meta_value WHERE 1=1 $file_search_query ORDER BY $file_sortby $file_sortorder LIMIT $offset, $file_perpage");
	//var_dump($files);exit;
	//WHERE meta_key='yang_attached_id' AND post_id=%d AND meta_value=%d
	
?>
<div class="wrap">
	<div id="icon-attachment" class="icon32"><br /></div>
	<?php if( !$yang_tab ):?>
		<h2><?php _e('Manage Attachments', yangam::textdomain ); ?> <a href="<?php echo 'admin.php?page=' . plugin_basename('yang-attachmentmanager/yangam-add.php')?>" class="add-new-h2"><?php echo esc_html_x('Add New', 'file'); ?></a></h2>
		<!-- <h3><?php _e('Attachments', yangam::textdomain ); ?></h3> -->
	<?php endif;?>

	<p><?php printf(__('Displaying <strong>%s</strong> To <strong>%s</strong> Of <strong>%s</strong> Files', yangam::textdomain ), number_format_i18n($display_on_page), number_format_i18n($max_on_page), number_format_i18n($get_total_files)); ?> / <?php printf(__('Sorted By <strong>%s</strong> In <strong>%s</strong> Order', yangam::textdomain ), $file_sortby_text, $file_sortorder_text); ?></p>

	<?php if( !$yang_tab ):?>
		<p>
			<span style="text-align:center;padding:0 400px;"> <?php _e('File id:', yangam::textdomain ); ?>
				<input type="text" name="edit_id" id="edit_id" /> <input type="button" value="<?php _e('Edit it!', yangam::textdomain ); ?>" class="button" onclick="window.location.href='<?php echo "$base_page&mode=edit&id="?>'+document.getElementById('edit_id').value;return false;" /></span></p>
	<?php endif;?>

<table class="widefat <?php if( $yang_tab ):?> yangam-tab<?php endif;?>">
	<thead>
		<tr>
			<th><?php _e('ID', yangam::textdomain ); ?></th>
			<th class="manage-column column-icon" id="icon" scope="col"></th>
			<th><?php _e('File', yangam::textdomain ); ?></th>
			<th><?php _e('Size', yangam::textdomain ); ?></th>
			<?php if( !$yang_tab ):?><th><?php _e('Hits', yangam::textdomain ); ?></th><?php endif;?>
			<th><?php _e('Permission', yangam::textdomain ); ?></th>
			<th><?php _e('File attached to', yangam::textdomain ); ?></th>
			<th><?php _e('Action', yangam::textdomain ); ?></th>
		</tr>
	</thead>
	<?php
	if($files) {;
		$i = 0;
		foreach($files as $file) {
			$file_id = intval($file->file_id);
			$file_name = stripslashes($file->file);//获取文件保存的名字，去除斜杠stripslashes($text);
			$file_dir_name = ltrim(stripslashes($file->file), '/');//获取文件存储的名字
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
			$file_permission = yangam_admin::file_permission($file->file_permission);
			$file_name_actual = yangam::get_basename($file_name);
			$post_id = $file->post_id;
			
			//是否显示小图片
			if( yangam::is_image($file_nicename) ){
				if( yangam::is_remote_file($file_name) ){
					$show_url = $file_name;
				} else {
					$show_url = $file_path_url. yangam::snippet_text($file_name, 45);
				}
			} else {
				$show_url = WP_PLUGIN_URL . '/yang-attachmentmanager/images/other_file.png';
			}

			$file_status = $file->file_status;
			if( $file_status=='trash' ){
				$status = __('Trashed', yangam::textdomain );
				$status_do = __('To Open', yangam::textdomain );
				$trash = 'open';
			} else {
				$status = __('Open', yangam::textdomain );
				$status_do = __('Trash', yangam::textdomain );
				$trash = 'trash';
			}

			
			//隔行换色
			if($i%2 == 0) {
				$style = '';
			} else {
				$style = 'class="alternate"';
			}

			echo "<tr $style>\n";
			
			//ID
			echo '<td valign="top">'.number_format_i18n($file_id).'</td>'."\n";
			
			//小图片
			echo "<td>";
			echo "<a title='".__('Edit', yangam::textdomain )." $file_nicename' href=\"$base_page&amp;mode=edit&amp;id=$file_id\" class=\"edit\">";
			echo "<img width='60' height='60' title='' alt='' class='attachment-80x60' src='$show_url' />";
			echo "</a></td>\n";

			//附件信息
			echo "<td>[$status] $file_nicename<br /><strong>&raquo;</strong> <i dir=\"ltr\">".yangam::snippet_text($file_name, 45)."</i><br /><br />";
			if( !$yang_tab ){
				echo "<i>".sprintf(__('Last Updated: %s, %s', yangam::textdomain ), $file_updated_time, $file_updated_date)."</i><br /><i>".sprintf(__('Last Downloaded: %s, %s', yangam::textdomain ), $file_last_downloaded_time, $file_last_downloaded_date)."</i>";
			}
			echo "</td>\n";

			//大小
			echo '<td style="text-align: left;">'.yangam::format_filesize($file_size).'</td>'."\n";

			//下载量（次）
			echo !$yang_tab ? '<td style="text-align: left;">'.number_format_i18n($file_hits).'</td>'."\n" : '';

			//权限
			echo '<td style="text-align: left;">'.$file_permission.'</td>'."\n";
			
			//文件附加到
			//原
			$post_title = '';
			if( $post_id > 0 ){
				$post = get_post($post_id);//通过文章id，返回文章信息
				$post_title = $post->post_title;
				$permalink = get_permalink($post_id);//文章链接
			}
			echo "<td><a href=\"$permalink\" title=\"$post_title\" target=\"_blank\">$post_title</a></td>\n";

			/*//原：文件附加到
			$post_title = '';
			if( $post_id > 0 ){
				$post = get_post($post_id);//通过文章id，返回文章信息
				$post_title = $post->post_title;
				$permalink = get_permalink($post_id);//文章链接
			}
			echo "<td><a href=\"$permalink\" title=\"$post_title\" target=\"_blank\">$post_title</a></td>\n";
			
			
			//by yang修改可用：文件附加到，attachments表 增加了字段 post_parent
			$post_parent = $file->post_parent;//附加到的文章id，以英文逗号分割
			$post_parent_arr = explode(',', $post_parent);
			echo "<td>";
			foreach($post_parent_arr as $pid)//post_id
			{
				$post_title = '';
				if( $pid > 0 ){
					$post = get_post($pid);//通过文章id，返回文章信息
					$post_title = $post->post_title;
					$permalink = get_permalink($pid);//文章链接
				}
				echo "<a href=\"$permalink\" title=\"$post_title\" target=\"_blank\">$post_title</a><br />";
			}
			echo "</td>\n";
			*/

			//操作
			if(!$yang_tab){
				echo "<td style=\"text-align: left;\">";
					echo "<a href=\"$base_page&amp;mode=edit&amp;id=$file_id\" class=\"edit\">".__('Edit', yangam::textdomain )."</a>";
					
					if( current_user_can('yang_att_del') ){
						echo "　<a href=\"$base_page&amp;mode=delete&amp;id=$file_id\" class=\"delete\">".__('Delete', yangam::textdomain )."</a>";
					}
					echo "　<a href=\"$base_page&amp;do=$trash&amp;id=$file_id\" class=\"trash\">$status_do</a>";
				echo "</td>\n";
			} else {
				echo "<td style=\"text-align: left;\"><input type=\"button\" id=\"insert_down\" onclick=\"insert_into_post_down($file_id);\" class=\"button button-primary\" name=\"insertintopost\" value=\"" . __('Insert into post download', 'yang-attachment') . "\" /> <input type=\"button\" id=\"insert_pic\" onclick=\"insert_into_post_pic($file_id,'$file_dir_name');\" class=\"button button-primary\" name=\"insertintopost_pic\" value=\"" . __('Insert into post pic', 'yang-attachment') . "\" /></td>\n";	
			}
			echo '</tr>';
			$i++;
		}
	} else {
		echo '<tr><td colspan="9" align="center"><strong>'.__('No Files Found', yangam::textdomain ).'</strong></td></tr>';
	}
	?>
</table>

<?php
if($total_pages > 1) {
?> <br />
	<table class="widefat<?php if( $yang_tab ):?> yangam-tab<?php endif;?>">
		<tr>
			<td
				align="<?php echo ('rtl' == $text_direction) ? 'right' : 'left'; ?>"
				width="50%"><?php
				if($file_page > 1 && ((($file_page*$file_perpage)-($file_perpage-1)) <= $get_total_files)) {
					echo '<strong>&laquo;</strong> <a class="attachment-page-item" href="'.$base_page.'&amp;filepage='.($file_page-1).$file_sort_url.'" title="&laquo; '.__('Previous Page', yangam::textdomain ).'">'.__('Previous Page', yangam::textdomain ).'</a>';
				} else {
					echo '&nbsp;';
				}
				?></td>
			<td
				align="<?php echo ('rtl' == $text_direction) ? 'left' : 'right'; ?>"
				width="50%"><?php
				if($file_page >= 1 && ((($file_page*$file_perpage)+1) <= $get_total_files)) {
					echo '<a class="attachment-page-item" href="'.$base_page.'&amp;filepage='.($file_page+1).$file_sort_url.'" title="'.__('Next Page', yangam::textdomain ).' &raquo;">'.__('Next Page', yangam::textdomain ).'</a> <strong>&raquo;</strong>';
				} else {
					echo '&nbsp;';
				}
				?></td>
		</tr>
		<tr class="alternate">
			<td colspan="2" align="center"><?php _e('Total Pages', yangam::textdomain ); ?>
			(<?php echo number_format_i18n($total_pages); ?>): <?php
			if ($file_page >= 4) {
				echo '<strong><a class="attachment-page-item" href="'.$base_page.'&amp;filepage=1'.$file_sort_url.'" title="'.__('Go to First Page', yangam::textdomain ).'">&laquo; '.__('First', yangam::textdomain ).'</a></strong> ... ';
			}
			if($file_page > 1) {
				echo ' <strong><a class="attachment-page-item" href="'.$base_page.'&amp;filepage='.($file_page-1).$file_sort_url.'" title="&laquo; '.__('Go to Page', yangam::textdomain ).' '.number_format_i18n($file_page-1).'">&laquo;</a></strong> ';
			}
			for($i = $file_page - 2 ; $i  <= $file_page +2; $i++) {
				if ($i >= 1 && $i <= $total_pages) {
					if($i == $file_page) {
						echo '<strong class="attachment-page-item-current" >['.number_format_i18n($i).']</strong> ';
					} else {
						echo '<a class="attachment-page-item" href="'.$base_page.'&amp;filepage='.($i).$file_sort_url.'" title="'.__('Page', yangam::textdomain ).' '.number_format_i18n($i).'">'.number_format_i18n($i).'</a> ';
					}
				}
			}
			if($file_page < $total_pages) {
				echo ' <strong><a class="attachment-page-item" href="'.$base_page.'&amp;filepage='.($file_page+1).$file_sort_url.'" title="'.__('Go to Page', yangam::textdomain ).' '.number_format_i18n($file_page+1).' &raquo;">&raquo;</a></strong> ';
			}
			if (($file_page+2) < $total_pages) {
				echo ' ... <strong><a class="attachment-page-item" href="'.$base_page.'&amp;filepage='.($total_pages).$file_sort_url.'" title="'.__('Go to Last Page', yangam::textdomain ), 'yang-attachment'.'">'.__('Last', yangam::textdomain ).' &raquo;</a></strong>';
			}
			?></td>
		</tr>
	</table>
<?php
}
?> <br />
<form action="<?php echo !$yang_tab ? $attachment_list_action: 'upload-or-insert.php?tab=attachments' ; ?>" method="get">
<table class="widefat">
	<tr>
		<th><?php _e('Filter Options: ', yangam::textdomain ); ?></th>
		<td><?php _e('Keywords:', yangam::textdomain ); ?><input
			type="text" name="search" size="30" maxlength="200"
			value="<?php echo stripslashes($file_search); ?>" /></td>
	</tr>
	<tr>
		<th><?php _e('Sort Options:', yangam::textdomain ); ?></th>
		<!-- if page variable is null, wp will give you a error: you do not have permission to access this page.
		so ,this var MUST be set OR delete this hidden input totally!
		-->
		<?php if( !$yang_tab ):?>
			<input type="hidden" name="page" value="<?php echo $base_name;?>" />
		<?php endif;?>
		<td>
		<select name="by" size="1">
			<option value="id"
			<?php if($file_sortby == 'file_id') { echo ' selected="selected"'; }?>><?php _e('File ID', yangam::textdomain ); ?></option>
			<option value="file"
			<?php if($file_sortby == 'file') { echo ' selected="selected"'; }?>><?php _e('File', yangam::textdomain ); ?></option>
			<option value="name"
			<?php if($file_sortby == 'file_name') { echo ' selected="selected"'; }?>><?php _e('File Name', yangam::textdomain ); ?></option>
			<option value="date"
			<?php if($file_sortby == 'file_date') { echo ' selected="selected"'; }?>><?php _e('File Date', yangam::textdomain ); ?></option>
			<option value="updated_date"
			<?php if($file_sortby == 'updated_date') { echo ' selected="selected"'; }?>><?php _e('File Updated Date', yangam::textdomain ); ?></option>
			<option value="last_downloaded_date"
			<?php if($file_sortby == 'last_downloaded_date') { echo ' selected="selected"'; }?>><?php _e('File Last Downloaded Date', yangam::textdomain ); ?></option>
			<option value="size"
			<?php if($file_sortby == '(file_size+0.00)') { echo ' selected="selected"'; }?>><?php _e('File Size', yangam::textdomain ); ?></option>
			<option value="hits"
			<?php if($file_sortby == 'file_hits') { echo ' selected="selected"'; }?>><?php _e('File Hits', yangam::textdomain ); ?></option>
			<option value="permission"
			<?php if($file_sortby == 'file_timestamp') { echo ' selected="selected"'; }?>><?php _e('File Permission', yangam::textdomain ); ?></option>
		</select> &nbsp;&nbsp;&nbsp; <select name="order" size="1">
			<option value="asc"
			<?php if($file_sortorder == 'ASC') { echo ' selected="selected"'; }?>><?php _e('Ascending', yangam::textdomain ); ?></option>
			<option value="desc"
			<?php if($file_sortorder == 'DESC') { echo ' selected="selected"'; } ?>><?php _e('Descending', yangam::textdomain ); ?></option>
		</select> &nbsp;&nbsp;&nbsp; <select name="perpage" size="1">
		<?php
		for($k=10; $k <= 100; $k+=10) {
			if($file_perpage == $k) {
				echo "<option value=\"$k\" selected=\"selected\">".__('Per Page', yangam::textdomain ).": ".number_format_i18n($k)."</option>\n";
			} else {
				echo "<option value=\"$k\">".__('Per Page', yangam::textdomain ).": ".number_format_i18n($k)."</option>\n";
			}
		}
		?>
		</select></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<input type="hidden" name="post_id" value="<?php echo $post_id;?>"/>
			<input type="submit" value="<?php _e('Go', yangam::textdomain ); ?>" class="button" /></td>
	</tr>
</table>
	<?php if( $yang_tab ):?>
	<input type="hidden" name="tab" value="attachments" />
	<?php endif;?>
</form>
</div>
