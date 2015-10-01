<?php
/**
 * AlterNews for WebMCR
 *
 * Main class
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
	public	$bc				= '';

	// Set constructor vars
	public function __construct($api){

		$this->cfg			= $api->cfg;
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->api			= $api;
		$this->mcfg			= $api->getMcrConfig();

	}

	private function news_array(){

		$bd_names = $this->mcfg['bd_names'];
		$bd_users = $this->mcfg['bd_users'];

		$end = $this->cfg['rop_news'];
		$start = $this->api->pagination($end, 0, 0);

		$where = "";

		if(isset($_GET['cid'])){
			$cid = intval($_GET['cid']);
			$where = "WHERE `n`.cid='$cid'";
		}

		$query = $this->db->query("SELECT `n`.id, `n`.uid, `n`.cid, `n`.title, `n`.`text_html`, `n`.img, `n`.`data`,
											`c`.title AS `category`,
											`u`.`{$bd_users['login']}`
									FROM `qx_news` AS `n`
									LEFT JOIN `qx_news_categories` AS `c`
										ON `c`.id=`n`.cid
									LEFT JOIN `{$bd_names['users']}` AS `u`
										ON `u`.`{$bd_users['id']}`=`n`.uid
									$where
									ORDER BY `n`.id DESC
									LIMIT $start,$end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("news/news-none.html"); }

		ob_start();

		while($ar = $this->db->get_row($query)){

			$id = intval($ar['id']);

			$data = json_decode($ar['data'], true);

			$text = $ar['text_html'];
			$pos = mb_strpos($text, '{READMORE}', 0, 'UTF-8');
			if($pos!==false){ $text = mb_substr($text, 0, $pos, "UTF-8"); }

			$category = (is_null($ar['category'])) ? 'Без категории' : $this->db->HSC($ar['category']);

			/*
			Комментарии, голосования и просмотры должны выводится через отдельный шаблон при условии их активации в $data
			*/

			$data_votes = array(
				"NID" => $id,
				"LIKES" => intval($data['likes']),
				"DISLIKES" => intval($data['dislikes']),
			);

			$comments = ($data['p_comments']) ? $this->api->sp("news/news-comments.html", intval($data['comments'])) : "";
			$views = ($data['p_views']) ? $this->api->sp("news/news-views.html", intval($data['views'])) : "";
			$votes = ($data['p_votes']) ? $this->api->sp("news/news-votes.html", $data_votes) : "";

			$result = array(
				"ID"					=> $id,
				"UID"					=> intval($ar['uid']),
				"CID"					=> intval($ar['cid']),
				"LOGIN"					=> $this->db->HSC($ar[$bd_users['login']]),
				"TITLE"					=> $this->db->HSC($ar['title']),
				"TEXT"					=> $text,
				"IMG"					=> $this->db->HSC($ar['img']),
				"CATEGORY"				=> $category,
				"LOGIN"					=> $this->db->HSC($ar[$bd_users['login']]),
				"DATE_CREATE"			=> date("d.m.Y в H:i", $data['date_create']),
				"DATE_UPDATE"			=> date("d.m.Y в H:i", $data['date_update']),
				"VIEWS"					=> $views,
				"COMMENTS"				=> $comments,
				"VOTES"					=> $votes,
			);

			echo $this->api->sp("news/news-id.html", $result);
		}

		return ob_get_clean();
	}

	private function news_list(){

		$array = array(
			"Главная" => BASE_URL,
			$this->api->cfg['title'] => "",
		);
		
		$this->bc = $this->api->bc($array);
		$this->title = 'Список новостей';

		$where = "";
		$page = '&pid=';

		if(isset($_GET['cid'])){
			$cid = intval($_GET['cid']);
			$where = "WHERE cid='$cid'";
			$page = '&cid='.$cid.'&pid=';
		}

		$sql = "SELECT COUNT(*) FROM `qx_news` $where";

		$data = array(
			"NEWS" => $this->news_array(),
			"PAGINATION" => $this->api->pagination($this->cfg['rop_news'], $page, $sql),
		);

		return $this->api->sp("news/news-list.html", $data);
	}

	private function comments_array($nid){


		$bd_names = $this->mcfg['bd_names'];
		$bd_users = $this->mcfg['bd_users'];
		$site_ways = $this->mcfg['site_ways'];

		$end = $this->cfg['rop_comments'];
		$start = $this->api->pagination($end, 0, 0);

		$query = $this->db->query("SELECT `c`.id, `c`.uid, `c`.`text_html`, `c`.`data`,
										`u`.`{$bd_users['login']}`, `u`.`{$bd_users['female']}`, `u`.`default_skin`
									FROM `qx_news_comments` AS `c`
									LEFT JOIN `{$bd_names['users']}` AS `u`
										ON `u`.`{$bd_users['id']}`=`c`.uid
									WHERE `c`.nid='$nid'
									ORDER BY `c`.id DESC
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return; }

		ob_start();

		while($ar = $this->db->get_row($query)){

			$data = json_decode($ar['data'], true);
			$uid = intval($ar['uid']);

			$login = $this->db->HSC($ar[$bd_users['login']]);

			$avatar_url = BASE_URL.$site_ways['mcraft'].'tmp/skin_buffer/';

			if(intval($ar['default_skin'])==1){
				$avatar_img = (intval($bd_users['female'])==1) ? 'default/Char_Mini_female.png' : 'default/Char_Mini.png';
			}else{
				$avatar_img = $login.'_Mini.png';
			}

			$avatar = $avatar_url.$avatar_img;

			$result = array(
				"ID" => intval($ar['id']),
				"UID" => $uid,
				"TEXT" => $ar['text_html'],
				"AVATAR" => $avatar,
				"LOGIN" => $login,
				"DATE_CREATE" => date("d.m.Y в H:i:s", $data['date_create']),
				"DATE_UPDATE" => date("d.m.Y в H:i:s", $data['date_update']),
				"CONTROL" => ($uid!=$this->user->id && $this->user->lvl<$this->cfg['lvl_admin']) ? '' : $this->api->sp("comments/com-control.html"),
				"USER_URL" => ($this->cfg['use_us']) ? BASE_URL.'?mode=users&uid='.$login : '#',
			);

			echo $this->api->sp("comments/com-id.html", $result);
		}

		return ob_get_clean();
	}

	private function comments_list($nid, $data){

		if(!$data['p_comments']){ return $this->api->sp("comments/com-close.html"); }

		$sql = "SELECT COUNT(*) FROM `qx_news_comments` WHERE nid='$nid'";

		$data = array(
			"COMMENTS" => $this->comments_array($nid),
			"PAGINATION" => $this->api->pagination($this->cfg['rop_comments'], '&nid='.$nid.'&pid=', $sql),
			"COMMENT_FORM" => (!$this->user->isOnline) ? '' : $this->api->sp("comments/com-form.html"),
		);

		return $this->api->sp("comments/com-list.html", $data);
	}

	private function news_full(){
		$id = intval($_GET['nid']);

		$time = time();

		$ip = $this->db->safesql($this->api->getIP());
		$uid = ($this->user->id === false) ? -1 : $this->user->id;

		$bd_names = $this->mcfg['bd_names'];
		$bd_users = $this->mcfg['bd_users'];

		$query = $this->db->query("SELECT `n`.id, `n`.uid, `n`.cid, `n`.title, `n`.`text_html`, `n`.img, `n`.`data`,
											`c`.title AS `category`,
											`u`.`{$bd_users['login']}`,
											`v`.id AS `vid`
									FROM `qx_news` AS `n`
									LEFT JOIN `qx_news_categories` AS `c`
										ON `c`.id=`n`.cid
									LEFT JOIN `{$bd_names['users']}` AS `u`
										ON `u`.`{$bd_users['id']}`=`n`.uid
									LEFT JOIN `qx_news_views` AS `v`
										ON `v`.nid='$id' AND (`v`.uid='$uid' OR `v`.ip='$ip')
									WHERE `n`.id='$id'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Новость не найдена", "", "404", 3); }

		$ar = $this->db->get_row($query);

		$data = json_decode($ar['data'], true);

		// Проверка на существование записи о уникальном просмотре и добавлении соответствующей записи

		if(is_null($ar['vid']) && $data['p_views']){
			$data['views'] = intval($data['views'])+1;

			$new_data = $this->db->safesql(json_encode($data));

			$uid = ($this->user->id===false) ? 0 : $this->user->id;
			$insert = $this->db->query("INSERT INTO `qx_news_views`
											(nid, uid, ip, `date`)
										VALUES
											('$id', '$uid', '$ip', '$time')");
			if(!$insert){ $this->api->notify('Bad request (#'.__LINE__.')', '', 'Ошибка!', 3); }
			$update = $this->db->query("UPDATE `qx_news` SET `data`='$new_data' WHERE id='$id'");
			if(!$update){ $this->api->notify('Bad request (#'.__LINE__.')', '', 'Ошибка!', 3); }
		}

		$data_votes = array(
			"NID" => $id,
			"LIKES" => intval($data['likes']),
			"DISLIKES" => intval($data['dislikes']),
		);

		$comments = ($data['p_comments']) ? $this->api->sp("news/news-comments.html", intval($data['comments'])) : "";
		$views = ($data['p_views']) ? $this->api->sp("news/news-views.html", intval($data['views'])) : "";
		$votes = ($data['p_votes']) ? $this->api->sp("news/news-votes.html", $data_votes) : "";
		$title = $this->db->HSC($ar['title']);

		$array = array(
			"Главная" => BASE_URL,
			$this->api->cfg['title'] => MOD_URL,
			$title => ""
		);
		
		$this->bc = $this->api->bc($array);
		$this->title = $title;

		$result = array(
			"TITLE" => $title,
			"UID" => intval($ar['uid']),
			"CID" => intval($ar['cid']),
			"TEXT" => str_replace("{READMORE}", "", $ar['text_html']),
			"IMG" => $this->db->HSC($ar['img']),
			"CATEGORY" => $this->db->HSC($ar['category']),
			"LOGIN" => $this->db->HSC($ar[$bd_users['login']]),
			"DATE_UPDATE" => date("d.m.Y в H:i", $data['date_update']),
			"DATE_CREATE" => date("d.m.Y в H:i", $data['date_create']),
			"VOTES" => $votes,
			"COMMENTS" => $comments,
			"VIEWS" => $views,
			"COMMENTS_LIST" => $this->comments_list($id, $data),
		);

		return $this->api->sp("news/news-full.html", $result);
	}

	public function _list(){
		return (isset($_GET['nid'])) ? $this->news_full() : $this->news_list();
	}
}

/**
 * AlterNews for WebMCR
 *
 * Main class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.0.0
 *
 */
?>