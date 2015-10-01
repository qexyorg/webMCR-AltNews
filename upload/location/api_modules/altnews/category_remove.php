<?php

if(!defined('QEXY_API')){ exit('Hacking Attempt!'); }

if($_SERVER['REQUEST_METHOD']!='POST'){ $api->result('Bad request (#'.__LINE__.')'); }

if(!$api->user->is_auth){ $api->result('Для удаления категории необходима авторизация'); }

require_once(DIR_ROOT.'configs/altnews.cfg.php');

if($api->user->lvl<$cfg['lvl_admin']){ $api->result('Доступ запрещен'); }

if(!isset($_POST['id'])){ $api->result('Bad request (#'.__LINE__.')'); }

$id = intval($_POST['id']);

if($id==1){ $api->result('Нельзя удалить категорию по умолчанию'); }

$delete = $api->db->query("DELETE FROM `qx_news_categories` WHERE id='$id'");

if(!$delete){ $api->result('Bad request (#'.__LINE__.')'); }

$update = $api->db->query("UPDATE `qx_news` SET `cid`='1' WHERE cid='$id'");

$api->result('SUCCESS', true);


?>