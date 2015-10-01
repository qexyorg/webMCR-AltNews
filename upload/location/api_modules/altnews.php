<?php

if(!defined('QEXY_API')){ exit('Hacking Attempt!'); }

$op = (isset($_GET['op'])) ? $_GET['op'] : '';

if(!preg_match("/^\w+$/i", $op)){ $api->result('Bad request (#'.__LINE__.')'); }
if(!file_exists(API_MOD_DIR.'altnews/'.$op.'.php')){ $api->result('Bad request (#'.__LINE__.')'); }

require_once(API_MOD_DIR.'altnews/'.$op.'.php');

?>