<?php
require_once __DIR__.'/../includes/db.php';
$u=require_user();
if($_SERVER['REQUEST_METHOD']==='GET')json_response(array('ok'=>true,'user'=>$u));
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(array('error'=>'Method not allowed'),405);
$d=body_json();
$name=trim($d['name']??'');if(!$name)json_response(array('error'=>'Full name is required.'),422);
$department=trim($d['department']??'');$team=trim($d['team']??'');$image=trim($d['profileImage']??'');
$st=db()->prepare("UPDATE users SET name=?,department=?,team=?,profile_image=? WHERE id=?");$st->execute(array($name,$department,$team,$image,$u['id']));
audit_log('profile_updated','user',$u['id'],null,$u);
json_response(array('ok'=>true,'user'=>user_row_by_id($u['id'])));
