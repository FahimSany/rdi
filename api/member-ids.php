<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/notifications.php';
$u=require_permission('create_member_ids');
$method=$_SERVER['REQUEST_METHOD'];
if($method==='GET'){
  $st=db()->prepare("SELECT mi.*,u.name AS used_by_name FROM member_invites mi LEFT JOIN users u ON u.id=mi.used_by ORDER BY mi.created_at DESC");$st->execute();$rows=$st->fetchAll();
  json_response(array('ok'=>true,'items'=>$rows));
}
if($method==='POST'){
  $d=body_json();$action=$d['action']??'create';$pdo=db();
  if($action==='create'){
    $email=strtolower(trim($d['email']??''));$role=trim($d['role']??'researcher');
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))json_response(array('error'=>'Valid approved email required.'),422);
    $st=$pdo->prepare("SELECT 1 FROM roles WHERE slug=?");$st->execute(array($role));if(!$st->fetchColumn())json_response(array('error'=>'Invalid initial role.'),422);
    if($role==='super_admin'&&!is_super_admin($u))json_response(array('error'=>'Only a Super Admin can issue a Super Admin Member ID.'),403);
    $id=strtoupper(trim($d['memberId']??''));
    if(!$id)$id='RDI-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));
    if(!preg_match('/^[A-Z0-9-]{6,80}$/',$id))json_response(array('error'=>'Member ID may contain only letters, numbers and hyphens.'),422);
    try{$st=$pdo->prepare("INSERT INTO member_invites(member_id,approved_email,initial_role,department,team,status,created_by) VALUES(?,?,?,?,?,'Unused',?)");$st->execute(array($id,$email,$role,trim($d['department']??''),trim($d['team']??''),$u['id']));}
    catch(PDOException $e){json_response(array('error'=>'Member ID or an unused invitation for this email already exists.'),409);}
    audit_log('member_id_created','member_invite',$id,'Approved email: '.$email,$u);
    rdi_notify_safely(function($svc) use ($id,$email,$u){$svc->memberIdCreated($id,$email,$u);});
    json_response(array('ok'=>true,'memberId'=>$id),201);
  }
  $id=strtoupper(trim($d['memberId']??''));if(!$id)json_response(array('error'=>'Member ID required.'),422);
  if($action==='revoke'){$st=$pdo->prepare("UPDATE member_invites SET status='Revoked' WHERE member_id=? AND status='Unused'");$st->execute(array($id));audit_log('member_id_revoked','member_invite',$id,null,$u);json_response(array('ok'=>true));}
  if($action==='restore'){$st=$pdo->prepare("UPDATE member_invites SET status='Unused' WHERE member_id=? AND status='Revoked'");$st->execute(array($id));audit_log('member_id_restored','member_invite',$id,null,$u);json_response(array('ok'=>true));}
  json_response(array('error'=>'Invalid action'),422);
}
json_response(array('error'=>'Method not allowed'),405);
