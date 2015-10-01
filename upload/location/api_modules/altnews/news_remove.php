<?php

if(!defined('QEXY_API')){ exit('Hacking Attempt!'); }

if($_SERVER['REQUEST_METHOD']!='POST'){ $api->result('Bad request (#'.__LINE__.')'); }

if(!$api->user->is_auth){ $api->result('Для удаления новости необходима авторизация'); }

require_once(DIR_ROOT.'configs/altnews.cfg.php');

if($api->user->lvl<$cfg['lvl_admin']){ $api->result('Доступ запрещен'); }

if(!isset($_POST['id'])){ $api->result('Bad request (#'.__LINE__.')'); }

$id = intval($_POST['id']);

$delete = $api->db->query("DELETE FROM `qx_news` WHERE id='$id'");

if(!$delete){ $api->result('Bad request (#'.__LINE__.')'); }

$delete = $api->db->query("DELETE FROM `qx_news_comments` WHERE nid='$id'");

if(!$delete){ $api->result('Bad request (#'.__LINE__.')'); }

$delete = $api->db->query("DELETE FROM `qx_news_views` WHERE nid='$id'");

if(!$delete){ $api->result('Bad request (#'.__LINE__.')'); }

$delete = $api->db->query("DELETE FROM `qx_news_likes` WHERE nid='$id'");

if(!$delete){ $api->result('Bad request (#'.__LINE__.')'); }

$api->result('SUCCESS', true);


?>