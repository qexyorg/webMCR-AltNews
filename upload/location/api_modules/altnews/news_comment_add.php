<?php

if(!defined('QEXY_API')){ exit('Hacking Attempt!'); }

if($_SERVER['REQUEST_METHOD']!='POST'){ $api->result('Bad request (#'.__LINE__.')'); }

if(!$api->user->is_auth){ $api->result('Для добавления комментария необходима авторизация'); }

if(!isset($_POST['nid']) || !isset($_POST['message'])){ $api->result('Bad request (#'.__LINE__.')'); }

$nid = intval($_POST['nid']);
$message = trim(@$_POST['message']);

$query = $api->db->query("SELECT `data` FROM `qx_news` WHERE id='$nid'");

if(!$query || $api->db->num_rows($query)<=0){ $api->result('Новость не найдена'); }

$ar = $api->db->get_row($query);

$data_news = json_decode($ar['data'], true);

if(!$data_news['p_comments']){ $api->result('Комментарии отключены администрацией'); }

if(empty($message)){ $api->result('Не заполнено поле сообщения'); }

$text_bb = $api->db->safesql($message);

$text_html = $api->bb_decode($api->db->HSC($message));

$text_strip = trim(strip_tags($text_html));

$text_html_filter = $api->db->safesql($text_html);

if(empty($text_strip)){ $api->result('Не заполнено поле сообщения'); }

$time = time();

$data = array(
	"date_create" => $time,
	"date_update" => $time,
	"uid_create" => $api->user->id,
	"uid_update" => $api->user->id,
);

$data = $api->db->safesql(json_encode($data));

$insert = $api->db->query("INSERT INTO `qx_news_comments`
								(nid, uid, `text_bb`, `text_html`, `data`)
							VALUES
								('$nid', '{$api->user->id}', '$text_bb', '$text_html_filter', '$data')");

if(!$insert){ $api->result('Bad request (#'.__LINE__.')'); }

$id = $api->db->insert_id();

$data_news['comments'] = intval($data_news['comments'])+1;

$data_news = $api->db->safesql(json_encode($data_news));

$update = $api->db->query("UPDATE `qx_news` SET `data`='$data_news' WHERE id='$nid'");

if(!$update){ $api->result('Bad request (#'.__LINE__.')'); }

$site_ways = $api->mcfg['site_ways'];
$config = $api->mcfg['config'];

$avatar_url = $config['s_root'].$site_ways['mcraft'].'tmp/skin_buffer/';

if($api->user->default_skin==1){
	$avatar_img = ($api->user->female==1) ? 'default/Char.png' : 'default/Char_Mini_female.png';
}else{
	$avatar_img = $api->user->login.'_Mini.png';
}

$path = DIR_ROOT.$site_ways['style'].'Default/modules/qexy/altnews/';

$data_comment = array(
	"ID" => $id,
	"AVATAR" => $avatar_url.$avatar_img,
	"LOGIN" => $api->user->login,
	"TEXT" => $text_html,
	"DATE_UPDATE" => date("d.m.Y в H:i:s", $time),
	"CONTROL" => $api->sp($path."comments/com-control.html"),
);

$result = array(
	"comment" => $api->sp($path.'comments/com-id.html', $data_comment),
);

$api->result('SUCCESS', true, $result);


?>