<?php

if(!defined('QEXY_API')){ exit('Hacking Attempt!'); }

if($_SERVER['REQUEST_METHOD']!='POST'){ $api->result('Bad request (#'.__LINE__.')'); }

require_once(DIR_ROOT.'configs/altnews.cfg.php');

if(!$api->user->is_auth){ $api->result('Для добавления комментария необходима авторизация'); }

if(!isset($_POST['id']) || !isset($_POST['message'])){ $api->result('Bad request (#'.__LINE__.')'); }

$id = intval($_POST['id']);
$message = trim(@$_POST['message']);

$query = $api->db->query("SELECT `c`.uid, `c`.`data`, `n`.`data` AS `data_news`
						FROM `qx_news_comments` AS `c`
						INNER JOIN `qx_news` AS `n`
							ON `n`.id=`c`.nid
						WHERE `c`.id='$id'");

if(!$query || $api->db->num_rows($query)<=0){ $api->result('Комментарий не найден'); }

$ar = $api->db->get_row($query);

$data = json_decode($ar['data'], true);
$data_news = json_decode($ar['data_news'], true);

if(!$data_news['p_comments']){ $api->result('Комментарии отключены администрацией'); }

if(intval($ar['uid'])!=$api->user->id && $api->user->lvl<$cfg['lvl_admin']){ $api->result('Доступ запрещен'); }

if(empty($message)){ $api->result('Не заполнено поле сообщения'); }

$text_bb = $api->db->safesql($message);

$text_html = $api->bb_decode($api->db->HSC($message));

$text_strip = trim(strip_tags($text_html));

$text_html_filter = $api->db->safesql($text_html);

if(empty($text_strip)){ $api->result('Не заполнено поле сообщения'); }

$time = time();

$data['date_update'] = $time;
$data['uid_update'] = $api->user->id;

$data = $api->db->safesql(json_encode($data));

$update = $api->db->query("UPDATE `qx_news_comments`
							SET `text_bb`='$text_bb', `text_html`='$text_html_filter', `data`='$data'
							WHERE id='$id' AND uid='{$api->user->id}'");

if(!$update){ $api->result('Bad request (#'.__LINE__.')'); }

$result = array(
	"text" => $text_html,
	"time" => date("d.m.Y в H:i:s", $time),
);

$api->result('SUCCESS', true, $result);


?>