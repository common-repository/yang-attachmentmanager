<?php
/*
 Plugin Name: Yang AttachmentManager
 Plugin URI: http://www.nuodou.com
 Description: Wordpress 附件管理器。A attachment manager for your WordPress blog.
 Version: 1.2
 Author: haibor
 Author URI: http://yangjunwei.com
 */

/**
 * $Id: yang-am.php 2012-05-01 12:26:29 haibor $
 * @encoding UTF-8 
 * @author haibor
 * @link http://yangjunwei.com
 */


define('YANGAM_LOADER',__FILE__);
require plugin_dir_path(__FILE__) . '/includes/yangam.class.php';

yangam::init();
