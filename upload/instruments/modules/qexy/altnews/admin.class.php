<?php
/**
 * AlterNews for WebMCR
 *
 * Admin class
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

class module{
	// Set default variables
	private $cfg			= array();
	private $user			= false;
	private $db				= false;
	private $api			= false;
	private $configs		= array();
	public	$in_header		= '';
	public	$title			= '';

	// Set counstructor values
	public function __construct($api){

		$this->cfg			= $api->cfg;
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->api			= $api;

		if($this->user->lvl < $this->cfg['lvl_admin']){ $this->api->notify("Доступ запрещен!", "&do=403", "403", 3); }

		$array = array(
			"Главная" => BASE_URL,
			$this->api->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_ADMIN_URL,
		);
		
		$this->bc		= $this->api->bc($array);
		$this->title	= "Панель управления";
	}

	private function settings(){
		$array = array(
			"Главная" => BASE_URL,
			$this->api->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_ADMIN_URL,
			"Настройки" => ""
		);
		
		$this->bc		= $this->api->bc($array);
		$this->title	= "Панель управления — Настройки";

		// CSRF Security name
		$f_security = 'mod_settings';

		// Check for post method and CSRF hacking
		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['submit']) || !$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=admin&op=settings", "403", 3); }

			$this->cfg['title']			= strip_tags(@$_POST['title']);
			$this->cfg['rop_news']		= (intval(@$_POST['rop_news'])<=0) ? 1 : intval(@$_POST['rop_news']);
			$this->cfg['rop_comments']	= (intval(@$_POST['rop_comments'])<=0) ? 1 : intval(@$_POST['rop_comments']);
			$this->cfg['lvl_access']	= intval(@$_POST['lvl_access']);
			$this->cfg['lvl_admin']		= intval(@$_POST['lvl_admin']);
			$this->cfg['use_us']		= (intval(@$_POST['use_us'])==1) ? true : false;

			if(!$this->api->savecfg($this->cfg, "configs/news.cfg.php")){ $this->api->notify("Ошибка сохранения настроек", "&do=admin&op=settings", "Ошибка!", 3); } // Check save config

			$this->api->notify("Настройки успешно сохранены", "&do=admin&op=settings", "Поздравляем!", 1);
		}

		$content = array(
			"USE_US" => ($this->cfg['use_us']) ? 'selected' : '',
			"F_SET" => $this->api->csrf_set($f_security),
			"F_SECURITY" => $f_security
		);

		return $this->api->sp("admin/settings.html", $content);
	}

	private function news_array(){

		$end		= $this->cfg['rop_news']; // Set end pagination
		$start		= $this->api->pagination($end, 0, 0); // Set start pagination

		$query = $this->db->query("SELECT id, title, `data`
								FROM `qx_news`
								ORDER BY id DESC
								LIMIT $start,$end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("admin/news-none.html"); } // Check returned result

		ob_start();

		while($ar = $this->db->get_row($query)){

			$data = json_decode($ar['data'], true);

			$result = array(
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"DATE_UPDATE" => date("d.m.Y в H:i", $data['date_update']),
			);

			echo $this->api->sp("admin/news-id.html", $result);
		}

		return ob_get_clean();
	}

	private function news_list(){

		$sql			= "SELECT COUNT(*) FROM `qx_news`"; // Set SQL query for pagination function
		$page			= "&do=admin&pid="; // Set url for pagination function
		$pagination		= $this->api->pagination($this->cfg['rop_news'], $page, $sql); // Set pagination

		$f_security = 'mod_delete'; // Set default name for csrf security variable

		$data = array(
			"PAGINATION"	=> $pagination,
			"NEWS"			=> $this->news_array(),
			"F_SECURITY"	=> $f_security,
			"F_SET"		=> $this->api->csrf_set($f_security)
		);

		return $this->api->sp('admin/news-list.html', $data);
	}

	private function get_categories($selected=1){
		$selected = intval($selected);
		$query = $this->db->query("SELECT id, title FROM `qx_news_categories`");

		if(!$query || $this->db->num_rows($query)<=0){ return '<option value="1">Без категории</option>'; }

		ob_start();

		while($ar = $this->db->get_row($query)){
			$select = (intval($ar['id'])==$selected) ? 'selected' : '';
			echo '<option value="'.intval($ar['id']).'" '.$select.'>'.$this->db->HSC($ar['title']).'</option>';
		}

		return ob_get_clean();
	}

	private function check_cid($id){
		$query = $this->db->query("SELECT COUNT(*) FROM `qx_news_categories` WHERE id='$id'");

		if(!$query){ return false; }

		$ar = $this->db->get_array($query);

		if($ar[0]<=0){ return false; }

		return true;
	}

	private function news_add(){

		$array = array(
			"Главная" => BASE_URL,
			$this->api->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_ADMIN_URL,
			"Добавление новости" => '',
		);
		
		$this->bc		= $this->api->bc($array);
		$this->title	= "Панель управления — Добавление новости";

		// CSRF Security name
		$f_security = 'news_add';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['submit']) || !$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=admin", "403", 3); }

			$title		= $this->db->safesql(@$_POST['title']);
			$cid		= intval(@$_POST['cid']);

			if(empty($title)){ $this->api->notify("Не заполнено поле \"Название\"", "&do=admin&op=add", "Ошибка!", 3); }
			if(!$this->check_cid($cid)){ $this->api->notify("Выбранная категория не существует", "&do=admin&op=add", "Ошибка!", 3); }

			$text_bb	= $this->db->safesql(@$_POST['text_bb']);
			$text_html	= $this->api->bb_decode($this->db->HSC(@$_POST['text_bb']));
			$text_strip	= trim(strip_tags($text_html));
			$text_html	= $this->db->safesql($text_html);

			if(empty($text_strip)){ $this->api->notify("Не заполнено поле \"Текст\"", "&do=admin&op=add", "Ошибка!", 3); }

			$img = $this->db->safesql(@$_POST['img-form']);

			// Set data
			$data = array(
				"date_create"	=> time(),
				"date_update"	=> time(),
				"uid_create"	=> $this->user->id,
				"uid_update"	=> $this->user->id,
				"comments"		=> 0,
				"views"			=> 0,
				"likes"			=> 0,
				"dislikes"		=> 0,
				"p_comments"	=> (intval(@$_POST['p_comments'])==1) ? true : false,
				"p_views"		=> (intval(@$_POST['p_views'])==1) ? true : false,
				"p_votes"		=> (intval(@$_POST['p_votes'])==1) ? true : false,
			);

			$data = $this->db->safesql(json_encode($data)); // Pack data to json

			$insert = $this->db->query("INSERT INTO `qx_news`
											(cid, uid, title, text_bb, text_html, img, `data`)
										VALUES
											('$cid', '{$this->user->id}', '$title', '$text_bb', '$text_html', '$img', '$data')");

			if(!$insert){ $this->api->notify("Ошибка добавления новости.", "&do=admin&op=add", "Ошибка!", 3); }

			$this->api->notify("Новость успешно добавлена", "&do=admin", "Поздравляем!", 1);
		}

		$content = array(
			"TITLE" => "",
			"CATEGORIES" => $this->get_categories(),
			"TEXT" => "",
			"IMG" => BASE_URL."qx_upload/altnews/default.png",
			"P_COMMENTS" => 'checked',
			"P_VOTES" => 'checked',
			"P_VIEWS" => 'checked',
			"SUBMIT" => "Добавить",
			"F_SET" => $this->api->csrf_set($f_security),
			"F_SECURITY" => $f_security
		);

		return $this->api->sp("admin/news-change.html", $content);
	}

	private function news_edit(){

		$array = array(
			"Главная" => BASE_URL,
			$this->api->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_ADMIN_URL,
			"Редактирование новости" => '',
		);
		
		$this->bc		= $this->api->bc($array);
		$this->title	= "Панель управления — Редактирование новости";

		$id = intval(@$_GET['nid']);

		$query = $this->db->query("SELECT cid, title, text_bb, img, `data` FROM `qx_news` WHERE id='$id'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Новость не найдена!", "&do=admin", "404", 3); }

		$ar = $this->db->get_row($query);

		$data = json_decode($ar['data'], true);

		// CSRF Security name
		$f_security = 'news_edit';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['submit']) || !$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=admin&op=edit&nid=$id", "403", 3); }

			$title		= $this->db->safesql(@$_POST['title']);
			$cid		= intval(@$_POST['cid']);

			if(empty($title)){ $this->api->notify("Не заполнено поле \"Название\"", "&do=admin&op=edit&nid=$id", "Ошибка!", 3); }
			if(!$this->check_cid($cid)){ $this->api->notify("Выбранная категория не существует", "&do=admin&op=edit&nid=$id", "Ошибка!", 3); }

			$text_bb	= $this->db->safesql(@$_POST['text_bb']);
			$text_html	= $this->api->bb_decode($this->db->HSC(@$_POST['text_bb']));
			$text_strip	= trim(strip_tags($text_html));
			$text_html	= $this->db->safesql($text_html);

			if(empty($text_strip)){ $this->api->notify("Не заполнено поле \"Текст\"", "&do=admin&op=edit&nid=$id", "Ошибка!", 3); }

			$img = $this->db->safesql(@$_POST['img-form']);

			// Set data
			$data['date_update'] = time();
			$data['uid_update'] = $this->user->id;
			$data['p_comments'] = (intval(@$_POST['p_comments'])==1) ? true : false;
			$data['p_views'] = (intval(@$_POST['p_views'])==1) ? true : false;
			$data['p_votes'] = (intval(@$_POST['p_votes'])==1) ? true : false;

			$data = $this->db->safesql(json_encode($data)); // Pack data to json

			$update = $this->db->query("UPDATE `qx_news`
										SET cid='$cid', title='$title', text_bb='$text_bb', text_html='$text_html', img='$img', `data`='$data'
										WHERE id='$id'");

			if(!$update){ $this->api->notify("Ошибка обновления новости.", "&do=admin&op=edit&nid=$id", "Ошибка!", 3); }

			$this->api->notify("Новость успешно изменена", "&do=admin&op=edit&nid=$id", "Поздравляем!", 1);
		}

		$content = array(
			"TITLE" => $this->db->HSC($ar['title']),
			"CATEGORIES" => $this->get_categories($ar['cid']),
			"TEXT" => $this->db->HSC($ar['text_bb']),
			"IMG" => $this->db->HSC($ar['img']),
			"P_COMMENTS" => ($data['p_comments']) ? 'checked' : '',
			"P_VOTES" => ($data['p_votes']) ? 'checked' : '',
			"P_VIEWS" => ($data['p_views']) ? 'checked' : '',
			"SUBMIT" => "Сохранить",
			"F_SET" => $this->api->csrf_set($f_security),
			"F_SECURITY" => $f_security
		);

		return $this->api->sp("admin/news-change.html", $content);
	}

	private function category_add(){

		$array = array(
			"Главная" => BASE_URL,
			$this->api->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_ADMIN_URL,
			"Категории" => MOD_ADMIN_URL.'&op=categories',
			"Добавление категории" => "",
		);
		
		$this->bc		= $this->api->bc($array);
		$this->title	= "Панель управления — Категории — Добавление категории";

		// CSRF Security name
		$f_security = 'category_add';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['submit']) || !$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=admin&op=categories", "403", 3); }

			$title		= $this->db->safesql(@$_POST['title']);

			if(empty($title)){ $this->api->notify("Не заполнено поле \"Название\"", "&do=admin&op=categories&act=add", "Ошибка!", 3); }

			$insert = $this->db->query("INSERT INTO `qx_news_categories`
											(title)
										VALUES
											('$title')");

			if(!$insert){ $this->api->notify("Ошибка добавления категории.", "&do=admin&op=categories&act=add", "Ошибка!", 3); }

			$this->api->notify("Категория успешно добавлена", "&do=admin&op=categories", "Поздравляем!", 1);
		}

		$content = array(
			"TITLE" => "",
			"SUBMIT" => "Добавить",
			"F_SET" => $this->api->csrf_set($f_security),
			"F_SECURITY" => $f_security
		);

		return $this->api->sp("admin/category-change.html", $content);
	}

	private function category_edit(){

		$array = array(
			"Главная" => BASE_URL,
			$this->api->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_ADMIN_URL,
			"Категории" => MOD_ADMIN_URL.'&op=categories',
			"Редактирование категории" => "",
		);
		
		$this->bc		= $this->api->bc($array);
		$this->title	= "Панель управления — Категории — Редактирование категории";

		$id = intval(@$_GET['cid']);

		$query = $this->db->query("SELECT title FROM `qx_news_categories` WHERE id='$id'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Категория не найдена!", "&do=admin&op=categories", "404", 3); }

		$ar = $this->db->get_row($query);

		// CSRF Security name
		$f_security = 'category_edit';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!isset($_POST['submit']) || !$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=admin&op=categories&act=edit&cid=$id", "403", 3); }

			$title		= $this->db->safesql(@$_POST['title']);

			if(empty($title)){ $this->api->notify("Не заполнено поле \"Название\"", "&do=admin&op=categories&act=edit&cid=$id", "Ошибка!", 3); }

			$update = $this->db->query("UPDATE `qx_news_categories`
										SET title='$title'
										WHERE id='$id'");

			if(!$update){ $this->api->notify("Ошибка обновления категории.", "&do=admin&op=categories&act=edit&cid=$id", "Ошибка!", 3); }

			$this->api->notify("Категория успешно изменена", "&do=admin&op=categories&act=edit&cid=$id", "Поздравляем!", 1);
		}

		$content = array(
			"TITLE" => $this->db->HSC($ar['title']),
			"SUBMIT" => "Сохранить",
			"F_SET" => $this->api->csrf_set($f_security),
			"F_SECURITY" => $f_security
		);

		return $this->api->sp("admin/category-change.html", $content);
	}

	private function category_array(){

		$end		= 10; // Set end pagination
		$start		= $this->api->pagination($end, 0, 0); // Set start pagination

		$query = $this->db->query("SELECT id, title
								FROM `qx_news_categories`
								ORDER BY id DESC
								LIMIT $start,$end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("admin/category-none.html"); } // Check returned result

		ob_start();

		while($ar = $this->db->get_row($query)){

			$result = array(
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
			);

			echo $this->api->sp("admin/category-id.html", $result);
		}

		return ob_get_clean();
	}

	private function category_list(){

		$sql			= "SELECT COUNT(*) FROM `qx_news_categories`"; // Set SQL query for pagination function
		$page			= "&do=admin&op=categories&pid="; // Set url for pagination function
		$pagination		= $this->api->pagination(10, $page, $sql); // Set pagination

		$f_security = 'mod_delete_cid'; // Set default name for csrf security variable

		$data = array(
			"PAGINATION"	=> $pagination,
			"CATEGORIES"	=> $this->category_array(),
			"F_SECURITY"	=> $f_security,
			"F_SET"			=> $this->api->csrf_set($f_security)
		);

		return $this->api->sp('admin/category-list.html', $data);
	}

	private function categories(){

		$array = array(
			"Главная" => BASE_URL,
			$this->api->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_ADMIN_URL,
			"Категории" => MOD_ADMIN_URL.'&op=categories',
		);
		
		$this->bc		= $this->api->bc($array);
		$this->title	= "Панель управления — Категории";

		$act = (isset($_GET['act'])) ? $_GET['act'] : 'list';

		switch($act){
			case "add": return $this->category_add(); break;
			case "edit": return $this->category_edit(); break;

			default: return $this->category_list(); break;
		}
	}

	public function _list(){

		$op = (isset($_GET['op'])) ? $_GET['op'] : 'main';

		switch($op){
			case "add": return $this->news_add(); break;
			case "edit": return $this->news_edit(); break;
			case "settings": return $this->settings(); break;
			case "categories": return $this->categories(); break;

			default: return $this->news_list(); break;
		}
	}
}

/**
 * AlterNews for WebMCR
 *
 * Admin class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.0.0
 *
 */
?>
