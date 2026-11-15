<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/notifications.php';
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(array('error'=>'Method not allowed'),405);
$d=body_json();
$memberId=strtoupper(trim($d['memberId']??''));
$email=strtolower(trim($d['email']??''));
$name=trim($d['name']??'');
$pass=(string)($d['password']??'');
if(!$memberId||!$email||!$name||strlen($pass)<8) json_response(array('error'=>'Member ID, approved email, full name and an 8+ character password are required.'),422);
if(!filter_var($email,FILTER_VALIDATE_EMAIL)) json_response(array('error'=>'Enter a valid email address.'),422);
$pdo=db();
$st=$pdo->prepare("SELECT * FROM member_invites WHERE member_id=? LIMIT 1");$st->execute(array($memberId));$inv=$st->fetch();
if(!$inv || $inv['status']!=='Unused') json_response(array('error'=>'This RDI Member ID is invalid, already used, or revoked.'),409);
if(strtolower($inv['approved_email'])!==$email) json_response(array('error'=>'The email does not match the RDI-approved email linked to this Member ID.'),403);
$st=$pdo->prepare("SELECT 1 FROM users WHERE LOWER(email)=? OR rdi_member_id=? LIMIT 1");$st->execute(array($email,$memberId));if($st->fetchColumn())json_response(array('error'=>'An account already exists for this email or Member ID.'),409);
$uid='u'.bin2hex(random_bytes(8));
$pdo->beginTransaction();
try{
  $st=$pdo->prepare("INSERT INTO users(id,rdi_member_id,name,email,password_hash,role,department,team,status,join_date) VALUES(?,?,?,?,?,?,?,?, 'Active', CURDATE())");
  $st->execute(array($uid,$memberId,$name,$email,password_hash($pass,PASSWORD_DEFAULT),$inv['initial_role'],$inv['department'],$inv['team']));
  $st=$pdo->prepare("UPDATE member_invites SET status='Used',used_by=?,used_at=NOW() WHERE member_id=? AND status='Unused'");$st->execute(array($uid,$memberId));
  $pdo->commit();
  audit_log('member_registered','user',$uid,'Registered with '.$memberId,null);
  rdi_notify_safely(function($svc) use ($uid,$name){$svc->accountStatusChanged($uid,$name,'Active','RDI registration');});
  json_response(array('ok'=>true,'message'=>'Account created. You can now log in.')); 
}catch(Throwable $e){$pdo->rollBack();throw $e;}
