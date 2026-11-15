<?php
require_once __DIR__.'/../includes/db.php';
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(array('error'=>'Method not allowed'),405);
$d=body_json();$email=strtolower(trim($d['email']??''));
$response=array('ok'=>true,'message'=>'If an active RDI account exists for that email, a reset link has been created.');
if(!filter_var($email,FILTER_VALIDATE_EMAIL))json_response($response);
$st=db()->prepare("SELECT id,name,email,status,deleted_at FROM users WHERE LOWER(email)=? LIMIT 1");$st->execute(array($email));$u=$st->fetch();
if(!$u||$u['status']!=='Active'||!empty($u['deleted_at']))json_response($response);
$token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);$id='pr'.bin2hex(random_bytes(8));$minutes=max(10,min(120,(int)get_setting('passwordResetMinutes','30')));
db()->prepare("UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL")->execute(array($u['id']));
db()->prepare("INSERT INTO password_reset_tokens(id,user_id,token_hash,expires_at) VALUES(?,?,?,DATE_ADD(NOW(), INTERVAL ? MINUTE))")->execute(array($id,$u['id'],$hash,$minutes));
$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$host=$_SERVER['HTTP_HOST']??'localhost';$base=rtrim(dirname($_SERVER['SCRIPT_NAME']??'/api/forgot-password.php'),'/\\');$base=preg_replace('#/api$#','',$base);$url=$scheme.'://'.$host.$base.'/index.php?reset_token='.urlencode($token).'#reset';
$subject='RDI password reset';$body="Hello {$u['name']},\n\nUse this single-use link within {$minutes} minutes to reset your RDI password:\n{$url}\n\nIf you did not request this, ignore this email.";
@mail($u['email'],$subject,$body,"From: ".get_setting('contactEmail','info.rd.innovators@gmail.com'));
if(in_array($host,array('localhost','127.0.0.1','localhost:80'),true)||strpos($host,'localhost:')===0)$response['devResetUrl']=$url;
audit_log('password_reset_requested','user',$u['id'],'Reset token issued',null);json_response($response);
