<?php
/**
 * $Id: jqueryFileTree.php 441920 2011-09-22 05:24:02Z haibor $
 * $Revision: 441920 $
 * $Date: 2011-09-22 05:24:02 +0000 (Thu, 22 Sep 2011) $
 * @package Yang-AttachmentManager
 * @encoding UTF-8 
 * @author haibor
 * @link http://yangjunwei.com
 * @license http://www.gnu.org/licenses/
 * Output a list of files for jQuery File Tree
 * jQuery File Tree PHP Connector
 *  orig file by Cory S.N. LaViska on 24 March 2008
 * A Beautiful Site (http://abeautifulsite.net/)
 */


if (file_exists('../../../../../wp-load.php'))
{
	require_once("../../../../../wp-load.php");
}
else if (file_exists('../../../../wp-load.php'))
{
	require_once("../../../../wp-load.php");
}
else
{
	die('<p>Failed to load bootstrap.</p>');
}

require dirname(__FILE__). '/../../includes/yangam_admin.class.php';

//Check Whether User Can Manage Attachments
if (!current_user_can('upload_files'))
{
	wp_die('Access Denied');
}

//init the variables for safe reasons.
$root = '';
$dir = '';

$root = yangam_admin::get_opt('yangam_path');
$dir = urldecode( yangam_admin::post('dir'));

if( file_exists($root . $dir) ) {
	$files = scandir($root .$dir);
	natcasesort($files);
	if( count($files) > 2 ) { /* The 2 accounts for . and .. */
		echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
		// All dirs
		foreach( $files as $file ) {
			if( yangam_admin::is_normal_file($root . $dir, $file) && is_dir($root . $dir . $file) ) {
				echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlspecialchars($dir . $file) . "/\">" . htmlspecialchars($file) . "</a></li>";
			}
		}
		// All files
		foreach( $files as $file ) {
			if( yangam_admin::is_normal_file($root . $dir, $file)  && !is_dir($root . $dir . $file) ) {
				$ext = preg_replace('/^.*\./', '', $file);
				echo "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlspecialchars($dir . $file) . "\">" . htmlspecialchars($file) . "</a></li>";
			}
		}
		echo "</ul>";	
	}
}

?>
