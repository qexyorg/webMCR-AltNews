<?php

if(!defined('QEXY_API')){ exit('Hacking Attempt!'); }

if($_SERVER['REQUEST_METHOD']!='POST'){ $api->result('Bad request (#'.__LINE__.')'); }

require_once(DIR_ROOT.'configs/altnews.cfg.php');

if(!$api->user->is_auth){ $api->result('Для удаления комментария необходима авторизация'); }

if(!isset($_POST['nid']) || !isset($_POST['id'])){ $api->result('Bad request (#'.__LINE__.')'); }

$nid = intval($_POST['nid']);
$id = intval($_POST['id']);

$query = $api->db->query("SELECT `c`.uid, `n`.`data`
						FROM `qx_news` AS `n`
						INNER JOIN `qx_news_comments` AS `c`
							ON `c`.id='$id' AND `c`.nid=`n`.id
						WHERE `n`.id='$nid'");

if(!$query || $api->db->num_rows($query)<=0){ $api->result('Новость не найдена'); }

$ar = $api->db->get_row($query);

$data_news = json_decode($ar['data'], true);

if(!$data_news['p_comments']){ $api->result('Комментарии отключены администрацией'); }

if(intval($ar['uid'])!=$api->user->id && $api->user->lvl<$cfg['lvl_admin']){ $api->result('Доступ запрещен'); }

$data_news['comments'] = intval($data_news['comments'])-1;

$delete = $api->db->query("DELETE FROM `qx_news_comments` WHERE id='$id'");

if(!$delete || $api->db->get_affected_rows()<=0){ $api->result('Ничего не удалено'); }

$data_news = $api->db->safesql(json_encode($data_news));

$update = $api->db->query("UPDATE `qx_news` SET `data`='$data_news' WHERE id='$nid'");

if(!$update){ $api->result('Bad request (#'.__LINE__.')'); }

$api->result('SUCCESS', true);


?>