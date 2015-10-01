<?php
/**
 * AlterNews for WebMCR
 *
 * Install class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.0.0
 *
 */

// Check Qexy constant
if (!defined('QEXY')){ exit("Hacking Attempt!"); }

$content_js .= '<link href="'.BASE_URL.'install_altnews/styles/css/install.css" rel="stylesheet">';

class module{
	// Set default variables
	private $cfg			= array();
	private $user			= false;
	private $db				= false;
	private $api			= false;
	private $configs		= array();
	public	$in_header		= '';
	public	$title			= '';
	private $mcfg			= array();

	// Set counstructor values
	public function __construct($api){

		$this->cfg			= $api->cfg;
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->api			= $api;
		$this->mcfg			= $this->api->getMcrConfig();
		
		if($this->user->lvl < $this->cfg['lvl_admin']){ $this->api->url = ''; $this->api->notify(); }
	}
	
	private function resaveMcfg(){
		$mcfg = $this->mcfg;

		$mcfg['config']['s_dpage'] = 'altnews';

		$txt  = '<?php'.PHP_EOL;
		$txt .= '$config = '.var_export($mcfg['config'], true).';'.PHP_EOL;
		$txt .= '$bd_names = '.var_export($mcfg['bd_names'], true).';'.PHP_EOL;
		$txt .= '$bd_users = '.var_export($mcfg['bd_users'], true).';'.PHP_EOL;
		$txt .= '$bd_money = '.var_export($mcfg['bd_money'], true).';'.PHP_EOL;
		$txt .= '$site_ways = '.var_export($mcfg['site_ways'], true).';'.PHP_EOL;
		$txt .= '?>';

		$result = file_put_contents(MCR_ROOT."config.php", $txt);

		if (is_bool($result) and $result == false){ return false; }

		return true;
	}

	private function step_1(){

		if(!$this->cfg['install']){ $this->api->notify("Установка уже произведена", "", "Ошибка!", 3); }
		if(isset($_SESSION['step_2'])){ $this->api->notify("", "&do=install&op=2", "", 3); }

		$write_menu = $write_cfg = $write_configs = '';

		if(!is_writable(MCR_ROOT.'instruments/menu_items.php')){
			$write_menu = '<div class="alert alert-error"><b>Внимание!</b> Выставите права 777 на файл <b>instruments/menu_items.php</b></div>';
		}

		if(!is_writable(MCR_ROOT.'configs')){
			$write_configs = '<div class="alert alert-error"><b>Внимание!</b> Выставите права 777 на папку <b>configs</b></div>';
		}

		if(!is_writable(MCR_ROOT.'configs/altnews.cfg.php')){
			$write_cfg = '<div class="alert alert-error"><b>Внимание!</b> Выставите права 777 на файл <b>configs/altnews.cfg.php</b></div>';
		}

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['submit'])){ $this->api->notify("Hacking Attempt!", "&do=install", "403", 3); }

			if(!empty($write_menu) || !empty($write_cfg) || !empty($write_configs)){ $this->api->notify("Требуется выставить необходимые права на запись", "&do=install", "Ошибка!", 3); }

			$this->cfg['title']			= $this->db->HSC(strip_tags(@$_POST['title']));
			$this->cfg['lvl_access']	= intval(@$_POST['lvl_access']);
			$this->cfg['lvl_admin']		= intval(@$_POST['lvl_admin']);
			$this->cfg['use_us']		= (intval(@$_POST['use_us'])==1) ? true : false;

			// Check save config
			if(!$this->api->savecfg($this->cfg, "configs/altnews.cfg.php")){ $this->api->notify("Ошибка сохранения настроек", "&do=install", "Ошибка!", 3); }

			if(intval(@$_POST['use_default'])==1){
				if(!$this->resaveMcfg()){ $this->api->notify("Ошибка сохранения настроек", "&do=install", "Ошибка!", 3); }
			}

			$create1 = $this->db->query("CREATE TABLE IF NOT EXISTS `qx_news` (
										  `id` int(10) NOT NULL AUTO_INCREMENT,
										  `cid` int(10) NOT NULL DEFAULT '1',
										  `uid` int(10) NOT NULL DEFAULT '0',
										  `title` varchar(255) NOT NULL,
										  `text_bb` text NOT NULL,
										  `text_html` text NOT NULL,
										  `img` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT 'default.png',
										  `data` text NOT NULL,
										  PRIMARY KEY (`id`)
										) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

			if(!$create1){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$create2 = $this->db->query("CREATE TABLE IF NOT EXISTS `qx_news_categories` (
										  `id` int(10) NOT NULL AUTO_INCREMENT,
										  `title` varchar(32) NOT NULL,
										  PRIMARY KEY (`id`)
										) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2;");

			if(!$create2){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$create3 = $this->db->query("CREATE TABLE IF NOT EXISTS `qx_news_comments` (
										  `id` int(10) NOT NULL AUTO_INCREMENT,
										  `nid` int(10) NOT NULL,
										  `uid` int(10) NOT NULL,
										  `text_bb` text NOT NULL,
										  `text_html` text NOT NULL,
										  `data` text NOT NULL,
										  PRIMARY KEY (`id`)
										) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

			if(!$create3){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$create4 = $this->db->query("CREATE TABLE IF NOT EXISTS `qx_news_likes` (
										  `id` int(10) NOT NULL AUTO_INCREMENT,
										  `nid` int(10) NOT NULL,
										  `uid` int(10) NOT NULL,
										  `value` tinyint(1) NOT NULL DEFAULT '0',
										  `data` text NOT NULL,
										  PRIMARY KEY (`id`)
										) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

			if(!$create4){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$create5 = $this->db->query("CREATE TABLE IF NOT EXISTS `qx_news_views` (
										  `id` int(10) NOT NULL AUTO_INCREMENT,
										  `nid` int(10) NOT NULL,
										  `uid` int(10) NOT NULL DEFAULT '0',
										  `ip` varchar(16) CHARACTER SET latin1 NOT NULL DEFAULT '0',
										  `date` int(11) NOT NULL,
										  PRIMARY KEY (`id`)
										) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

			if(!$create5){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$insert = $this->db->query("INSERT INTO `qx_news_categories`
										(`id`, `title`)
											VALUES
										(1, 'Без категории')");

			if(!$insert){ $this->api->notify("Ошибка установки #".__LINE__, "&do=install", "Ошибка!", 3); }

			$_SESSION['step_2'] = true;

			$this->api->notify("Шаг 2", "&do=install&op=2", "Продолжение установки", 2);
		}

		$content = array(
			"WRITE_MENU" => $write_menu,
			"WRITE_CFG" => $write_cfg,
			"WRITE_CONFIGS" => $write_configs,
		);

		return $this->api->sp(MCR_ROOT.'install_altnews/styles/step-1.html', $content, true);
	}

	private function saveMenu($menu) {
	
		$txt  = "<?php if (!defined('MCR')) exit;".PHP_EOL;
		$txt .= '$menu_items = '.var_export($menu, true).';'.PHP_EOL;

		$result = file_put_contents(MCR_ROOT."instruments/menu_items.php", $txt);

		return (is_bool($result) and $result == false)? false : true;	
	}

	private function step_2(){

		if(!isset($_SESSION['step_2'])){ $this->api->notify("", "&do=install", "", 3); }
		if(isset($_SESSION['step_3'])){ $this->api->notify("", "&do=install&op=3", "", 3); }

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['submit'])){ $this->api->notify("Hacking Attempt!", "&do=install&op=2", "403", 3); }

			require(MCR_ROOT."instruments/menu_items.php");

			if(!isset($menu_items[0]['qx_news'])){
				$menu_items[1]['qx_news'] = array (
				  'name' => 'Новости',
				  'url' => '',
				  'parent_id' => -1,
				  'lvl' => 15,
				  'permission' => -1,
				  'active' => false,
				  'inner_html' => '',
				);
				
				$menu_items[1]['qx_news_list'] = array (
				  'name' => 'Управление новостями',
				  'url' => '?mode=altnews&do=admin',
				  'parent_id' => 'qx_news',
				  'lvl' => 15,
				  'permission' => -1,
				  'active' => false,
				  'inner_html' => '',
				);
				
				$menu_items[1]['qx_news_categories'] = array (
				  'name' => 'Управление категориями',
				  'url' => '?mode=altnews&do=admin&op=categories',
				  'parent_id' => 'qx_news',
				  'lvl' => 15,
				  'permission' => -1,
				  'active' => false,
				  'inner_html' => '',
				);
				
				$menu_items[1]['qx_news_settings'] = array (
				  'name' => 'Настройки',
				  'url' => '?mode=altnews&do=admin&op=settings',
				  'parent_id' => 'qx_news',
				  'lvl' => 15,
				  'permission' => -1,
				  'active' => false,
				  'inner_html' => '',
				);
			}

			if(!$this->saveMenu($menu_items)){ $this->api->notify("Ошибка установки", "&do=install&op=2", "Ошибка!", 3); }

			$_SESSION['step_3'] = true;

			$this->api->notify("", "&do=install&op=3", "", 2);
		}

		return $this->api->sp(MCR_ROOT.'install_altnews/styles/step-2.html', array(), true);
	}

	private function step_3(){

		if(!isset($_SESSION['step_3'])){ $this->api->notify("", "&do=install&op=2", "", 3); }
		if(isset($_SESSION['step_finish'])){ $this->api->notify("", "&do=install&op=finish", "", 3); }

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['submit'])){ $this->api->notify("Hacking Attempt!", "&do=install", "403", 3); }

			$this->cfg['install'] = false;

			if(!$this->api->savecfg($this->cfg, "configs/altnews.cfg.php")){ $this->api->notify("Ошибка установки", "&do=install", "Ошибка!", 3); }

			$_SESSION['step_finish'] = true;

			$this->api->notify("", "&do=install&op=finish", "", 2);
		}

		return $this->api->sp(MCR_ROOT.'install_altnews/styles/step-3.html', array(), true);
	}

	private function finish(){

		if(!isset($_SESSION['step_finish'])){ $this->api->notify("", "&do=install&op=3", "", 3); }

		$content = $this->api->sp(MCR_ROOT.'install_altnews/styles/finish.html', array(), true);

		unset($_SESSION['step_finish'], $_SESSION['step_3'], $_SESSION['step_2']);

		return $content;
	}

	public function _list(){

		$op = (isset($_GET['op'])) ? $_GET['op'] : 'main';

		switch($op){
			case "2":
				$this->title	= "Установка — Шаг 2"; // Set page title (In tag <title></title>)
				$array = array(
					"Главная" => BASE_URL,
					$this->cfg['title'] => MOD_URL,
					"Установка" => MOD_URL."&do=install",
					"Шаг 2" => ""
				);
				$this->bc		= $this->api->bc($array);

				return $this->step_2(); // Set content
			break;

			case "3":
				$this->title	= "Установка — Шаг 3"; // Set page title (In tag <title></title>)
				$array = array(
					"Главная" => BASE_URL,
					$this->cfg['title'] => MOD_URL,
					"Установка" => MOD_URL."&do=install",
					"Шаг 3" => ""
				);
				$this->bc		= $this->api->bc($array);

				return $this->step_3(); // Set content
			break;

			case "finish":
				$this->title	= "Установка — Конец установки"; // Set page title (In tag <title></title>)
				$array = array(
					"Главная" => BASE_URL,
					$this->cfg['title'] => MOD_URL,
					"Установка" => MOD_URL."&do=install",
					"Конец установки" => ""
				);
				$this->bc		= $this->api->bc($array);

				return $this->finish(); // Set content
			break;

			default:
				$array = array(
					"Главная" => BASE_URL,
					$this->cfg['title'] => MOD_URL,
					"Установка" => MOD_URL."&do=install",
					"Шаг 1" => ""
				);
				$this->bc		= $this->api->bc($array);

				$this->title	= "Установка — Шаг 1";
				return $this->step_1();
			break;
		}

		return '';
	}
}

/**
 * AlterNews for WebMCR
 *
 * Install class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.0.0
 *
 */
?>
