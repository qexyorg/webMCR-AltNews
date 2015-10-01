<?php

if(!defined('QEXY_API')){ exit('Hacking Attempt!'); }

if($_SERVER['REQUEST_METHOD']!='POST'){ $api->result('Bad request (#'.__LINE__.')'); }

if(!$api->user->is_auth){ $api->result('Для голосования необходима авторизация'); }

if(!isset($_POST['nid']) || !isset($_POST['value'])){ $api->result('Bad request (#'.__LINE__.')'); }

$nid = intval($_POST['nid']);
$value = (intval($_POST['value'])==1) ? 1 : 0;

$query = $api->db->query("SELECT `n`.`data`, `l`.`value`, `l`.`data` AS `data_vote`
						FROM `qx_news` AS `n`
						LEFT JOIN `qx_news_likes` AS `l`
							ON `l`.nid=`n`.id AND `l`.uid='{$api->user->id}'
						WHERE `n`.id='$nid'");

if(!$query || $api->db->num_rows($query)<=0){ $api->result('Новость не найдена'); }

$ar = $api->db->get_row($query);

$data = json_decode($ar['data'], true);

if(!$data['p_votes']){ $api->result('Голосование отключено администрацией'); }

$result = array(
	'likes' => $data['likes'],
	'dislikes' => $data['dislikes'],
);

if(!is_null($ar['value']) && $value==intval($ar['value'])){ $api->result('Ничего не обновлено', true, $result); }

if(is_null($ar['value'])){
	$data_vote = array(
		"date_create" => time(),
		"date_update" => time(),
	);
	$data_vote = $api->db->safesql(json_encode($data_vote));

	$insert = $api->db->query("INSERT INTO `qx_news_likes`
									(nid, uid, `value`, `data`)
								VALUES
									('$nid', '{$api->user->id}', '$value', '$data_vote')");
	if(!$insert){ $api->result('Bad request (#'.__LINE__.')'); }

	if($value==1){
		$data['likes']++;
	}else{
		$data['dislikes']++;
	}
}else{
	$data_vote = json_decode($ar['data_vote'], true);
	$data_vote['date_update'] = time();
	$data_vote = $api->db->safesql(json_encode($data_vote));

	$update = $api->db->query("UPDATE `qx_news_likes`
								SET `value`='$value', `data`='$data_vote'
								WHERE nid='$nid' AND uid='{$api->user->id}'");
	if(!$update){ $api->result('Bad request (#'.__LINE__.')'); }

	if($value==1){
		$data['likes']++;
		$data['dislikes']--;
	}else{
		$data['likes']--;
		$data['dislikes']++;
	}
}

$new_data = $api->db->safesql(json_encode($data));

$update = $api->db->query("UPDATE `qx_news` SET `data`='$new_data' WHERE id='$nid'");
if(!$update){ $api->result('Bad request (#'.__LINE__.')'); }

$result = array(
	'likes' => $data['likes'],
	'dislikes' => $data['dislikes'],
);

$api->result('SUCCESS', true, $result);

?>