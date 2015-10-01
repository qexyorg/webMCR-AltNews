<?php
/**
 * AlterNews for WebMCR
 *
 * General proccess
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.0.0
 *
 */

// Check webMCR constant
if(!defined('MCR')){ exit("Hacking Attempt!"); }

$indexer = "altnews";

// Load configuration
require_once(MCR_ROOT.'configs/'.$indexer.'.cfg.php');

// Set default constants
define('QEXY', true);															// Default module costant
define('MOD_VERSION', '1.0.0');													// Module version
define('MOD_STYLE', STYLE_URL.'Default/modules/qexy/'.$indexer.'/');			// Module style folder
define('MOD_STYLE_ADMIN', MOD_STYLE.'admin/');									// Module style admin folder
define('MOD_URL', BASE_URL.'?mode='.$indexer);									// Base module url
define('MOD_ADMIN_URL', MOD_URL.'&do=admin');									// Base module admin url
define('MOD_CLASS_PATH', MCR_ROOT.'instruments/modules/qexy/'.$indexer.'/');	// Root module class folder
define('MCR_URL_ROOT', 'http://'.$_SERVER['SERVER_NAME']);						// Base full site url

// Loading API
if(!file_exists(MCR_ROOT."instruments/modules/qexy/api/api.class.php")){ exit("API not found! <a href=\"https://github.com/qexyorg/webMCR-API\" target=\"_blank\">Download</a>"); }
require_once(MCR_ROOT."instruments/modules/qexy/api/api.class.php");

// Set default url for module
$api->url = "?mode=".$indexer;

// Set default style path for module
$api->style = MOD_STYLE;

// Set module cfg
$api->cfg = $cfg;

// Check access user level
if($api->user->lvl < $cfg['lvl_access']){ header('Location: '.BASE_URL.'?mode=403'); exit; }

// Load css style and javascript
$content_js .= '<link href="'.MOD_STYLE.'css/module.css" rel="stylesheet">';
$content_js .= '<script src="'.MOD_STYLE.'js/module.js"></script>';
if($api->user->lvl >= $cfg['lvl_admin']){ $content_js .= '<script src="'.MOD_STYLE.'js/admin.1x3vfg5.js"></script>'; }

// Check for installation
if($cfg['install']==true){ $install = true; }

// Set active menu
$menu->SetItemActive('main');

// Set default page
$do = isset($_GET['do']) ? $_GET['do'] : 'main';

if(isset($install) && $do!=='install'){ $api->notify("Требуется установка", "&do=install", "Внимание!", 4); }

/*
 * Load submodules
 *
 * Format:
 * 1. Include class
 * 2. Create new object for $module (For use core methods, set __construct $api)
 * 3. Set main method for $content
 * 4. Set title(from class or other) for $title
 * 5. Set BreadCrumbs(from class or other) for $bc
 *
 */

switch($do){
	// Load sub modules
	case 'admin':
	case 'main':
		require_once(MOD_CLASS_PATH.$do.'.class.php');
		$module		= new module($api);
		$content	= $module->_list();
		$title		= $module->title;
		$bc			= $module->bc;
	break;

	// Load installation
	case 'install':
		if(!isset($install) && !isset($_SESSION['install_finished'])){ $api->notify("Установка уже произведена", "", "Упс!", 4); }
		require_once(MCR_ROOT."install_".$indexer."/install.class.php");
		$module		= new module($api);
		$content	= $module->_list();
		$title		= $module->title;
		$bc			= $module->bc;
	break;

	// Load default menu
	default: $api->notify("Страница не найдена", "", "404", 3); break;
}

// Set default page title
$page = $cfg['title'].' — '.$title;

require_once(MCR_ROOT.'configs/rapi.cfg.php');

$content_data = array(
	"CONTENT" => $content,
	"BC" => $bc,
	"API_INFO" => $api->get_notify(),
	"CSRFKEY" => md5($api->getIP().@$_SERVER['HTTP_USER_AGENT'].$cfg['csrfkey']),
);

// Set returned content
$content_main = $api->sp("global.html", $content_data);


/**
 * AlterNews for WebMCR
 *
 * General proccess
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.0.0
 *
 */
?>
