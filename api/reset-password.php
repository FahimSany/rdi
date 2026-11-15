<?php
require_once __DIR__.'/../includes/db.php';
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(array('error'=>'Method not allowed'),405);
$d=body_json();$token=(string)($d['token']??'');$pass=(string)($d['password']??'');
if(strlen($token)<20||strlen($pass)<8)json_response(array('error'=>'Valid reset token and an 8+ character password are required.'),422);
$hash=hash('sha256',$token);$st=db()->prepare("SELECT pr.id,pr.user_id FROM password_reset_tokens pr JOIN users u ON u.id=pr.user_id WHERE pr.token_hash=? AND pr.used_at IS NULL AND pr.expires_at>NOW() AND u.status='Active' LIMIT 1");$st->execute(array($hash));$r=$st->fetch();if(!$r)json_response(array('error'=>'Reset link is invalid, expired, or already used.'),410);
$pdo=db();$pdo->beginTransaction();try{$pdo->prepare("UPDATE users SET password_hash=?,failed_login_count=0 WHERE id=?")->execute(array(password_hash($pass,PASSWORD_DEFAULT),$r['user_id']));$pdo->prepare("UPDATE password_reset_tokens SET used_at=NOW() WHERE id=?")->execute(array($r['id']));$pdo->commit();audit_log('password_reset_completed','user',$r['user_id'],null,null);json_response(array('ok'=>true));}catch(Throwable $e){$pdo->rollBack();throw $e;}
