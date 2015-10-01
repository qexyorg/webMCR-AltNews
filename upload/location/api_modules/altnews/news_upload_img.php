<?php

if(!defined('QEXY_API')){ exit('Hacking Attempt!'); }

if($_SERVER['REQUEST_METHOD']!='POST'){ $api->result('Bad request (#'.__LINE__.')'); }

if(!$api->user->is_auth){ $api->result('Требуется авторизация'); }

require_once(DIR_ROOT.'configs/altnews.cfg.php');

if($api->user->lvl<$cfg['lvl_admin']){ $api->result('Доступ запрещен'); }

if(!isset($_FILES['img']) || empty($_FILES['img'])){ $api->result('Файл не выбран'); }

if(empty($_FILES['img']['size'])){ $api->result('Файл не выбран'); }

$formats = array('jpg', 'png', 'jpeg', 'gif');

$file = $_FILES['img'];
			
switch($file['error']){
	case 0: break;
	case 1:
	case 2: $api->result('Превышен лимит по объему'); break;
	case 3:
	case 4: $api->result('Ошибка загрузки файла'); break;
	case 6: $api->result('Отсутствует временная папка'); break;
	case 7: $api->result('Отсутствуют права на запись'); break;
	default: $api->result('Неизвестная ошибка'); break;
}

if(!file_exists($file['tmp_name'])){ $api->result('Временный файл не существует'); }

$name = mb_strtolower($file['name'], 'UTF-8');
$ext = substr(strrchr($name, '.'), 1);
$gis = @getimagesize($file['tmp_name']);

if(!in_array($ext, $formats)){ $api->result('Разрешено загружать только форматы: '.$api->db->HSC(implode(', ', $formats))); }

if(!$gis){ $api->result('Неверный формат изображения'); }
$new_name = md5($api->gen(24)).'.'.$ext;
if(!move_uploaded_file($file['tmp_name'], DIR_ROOT.'qx_upload/altnews/'.$new_name)){
	$api->result('Не удалось загрузить файл на сервер');
}

$api->result('SUCCESS', true, '/qx_upload/altnews/'.$new_name);

?>