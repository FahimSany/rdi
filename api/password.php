<?php
require_once __DIR__.'/../includes/db.php';
$u=require_user();if($_SERVER['REQUEST_METHOD']!=='POST')json_response(array('error'=>'Method not allowed'),405);
$d=body_json();$current=(string)($d['currentPassword']??'');$new=(string)($d['newPassword']??'');
if(strlen($new)<8)json_response(array('error'=>'New password must be at least 8 characters.'),422);
$st=db()->prepare("SELECT password_hash FROM users WHERE id=?");$st->execute(array($u['id']));$hash=$st->fetchColumn();
if(!$hash||!password_verify($current,$hash))json_response(array('error'=>'Current password is incorrect.'),401);
db()->prepare("UPDATE users SET password_hash=?,failed_login_count=0 WHERE id=?")->execute(array(password_hash($new,PASSWORD_DEFAULT),$u['id']));
audit_log('password_changed','user',$u['id'],null,$u);json_response(array('ok'=>true));
