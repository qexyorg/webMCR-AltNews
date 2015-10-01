<?php

if(!defined('QEXY_API')){ exit('Hacking Attempt!'); }

if($_SERVER['REQUEST_METHOD']!='POST'){ $api->result('Bad request (#'.__LINE__.')'); }

require_once(DIR_ROOT.'configs/altnews.cfg.php');

if(!$api->user->is_auth){ $api->result('Для редактирования комментария необходима авторизация'); }

if(!isset($_POST['id'])){ $api->result('Bad request (#'.__LINE__.')'); }

$id = intval($_POST['id']);

$query = $api->db->query("SELECT `c`.uid, `c`.`text_bb`, `c`.`text_html`, `c`.`data`, `n`.`data` AS `data_news`
						FROM `qx_news_comments` AS `c`
						INNER JOIN `qx_news` AS `n`
							ON `n`.id=`c`.nid
						WHERE `c`.id='$id'");

if(!$query || $api->db->num_rows($query)<=0){ $api->result('Доступ запрещен'); }

$ar = $api->db->get_row($query);

$data_news = json_decode($ar['data_news'], true);

if(!$data_news['p_comments']){ $api->result('Комментарии отключены администрацией'); }

if(intval($ar['uid'])!=$api->user->id && $api->user->lvl<$cfg['lvl_admin']){ $api->result('Доступ запрещен'); }

$data = json_decode($ar['data'], true);

$result = array(
	"bb_panel" => $api->bb_panel('.bb-comment', 'panel-target-comment'),
	"text_html" => $ar['text_html'],
	"text_bb" => $api->db->HSC($ar['text_bb']),
);

$api->result('SUCCESS', true, $result);


?>