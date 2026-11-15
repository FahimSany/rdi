<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/notifications.php';
require_once __DIR__.'/../app/Services/ProjectService.php';
$actor=require_permission('view_users');
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(array('error'=>'Method not allowed'),405);
$d=body_json();$id=trim($d['id']??'');$action=$d['action']??'';if(!$id)json_response(array('error'=>'User id required.'),422);
$pdo=db();$st=$pdo->prepare("SELECT * FROM users WHERE id=? LIMIT 1");$st->execute(array($id));$target=$st->fetch();if(!$target)json_response(array('error'=>'User not found.'),404);
if(($target['role']??'')==='super_admin'&&!is_super_admin($actor))json_response(array('error'=>'Only a Super Admin can modify a Super Admin account.'),403);

if($action==='update'){
  if(!has_permission($actor,'edit_users'))json_response(array('error'=>'Permission denied: edit_users'),403);
  $name=trim($d['name']??$target['name']);$email=strtolower(trim($d['email']??$target['email']));
  if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL))json_response(array('error'=>'Valid name and email required.'),422);
  $st=$pdo->prepare("UPDATE users SET name=?,email=?,department=?,team=?,profile_image=? WHERE id=?");
  try{$st->execute(array($name,$email,trim($d['department']??''),trim($d['team']??''),trim($d['profileImage']??''),$id));}
  catch(PDOException $e){json_response(array('error'=>'Email is already in use.'),409);}
  audit_log('user_updated','user',$id,null,$actor);json_response(array('ok'=>true));
}
if($action==='role'){
  if(!has_permission($actor,'assign_roles'))json_response(array('error'=>'Permission denied: assign_roles'),403);
  $role=trim($d['role']??'');$st=$pdo->prepare("SELECT 1 FROM roles WHERE slug=?");$st->execute(array($role));if(!$st->fetchColumn())json_response(array('error'=>'Invalid role.'),422);
  if($role==='super_admin'&&!is_super_admin($actor))json_response(array('error'=>'Only a Super Admin can assign Super Admin.'),403);
  if($id===$actor['id']&&$role!=='super_admin'&&is_super_admin($actor))json_response(array('error'=>'For safety, the current Super Admin cannot demote their own account.'),409);
  $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute(array($role,$id));audit_log('user_role_changed','user',$id,'Role: '.$role,$actor);json_response(array('ok'=>true));
}
if($action==='status'){
  if(!has_permission($actor,'activate_users'))json_response(array('error'=>'Permission denied: activate_users'),403);
  $status=$d['status']??'';if(!in_array($status,array('Pending','Active','Deactivated','Locked'),true))json_response(array('error'=>'Invalid account status.'),422);
  if($id===$actor['id']&&$status!=='Active')json_response(array('error'=>'You cannot disable or lock your own active admin session.'),409);
  $pdo->prepare("UPDATE users SET status=?,deleted_at=NULL,failed_login_count=0,locked_at=IF(?='Locked',NOW(),NULL) WHERE id=?")->execute(array($status,$status,$id));
  audit_log('user_status_changed','user',$id,'Status: '.$status,$actor);
  if(in_array($status,array('Active','Deactivated'),true)){
    rdi_notify_safely(function($svc) use ($id,$target,$status,$actor){$svc->accountStatusChanged($id,$target['name'],$status,$actor['name']);});
  }
  json_response(array('ok'=>true));
}
if($action==='delete'){
  if(!has_permission($actor,'delete_users'))json_response(array('error'=>'Permission denied: delete_users'),403);
  if($id===$actor['id'])json_response(array('error'=>'You cannot delete your own account.'),409);
  $pdo->prepare("UPDATE users SET status='Deleted',deleted_at=NOW() WHERE id=?")->execute(array($id));audit_log('user_soft_deleted','user',$id,null,$actor);json_response(array('ok'=>true));
}
if($action==='restore'){
  if(!has_permission($actor,'delete_users'))json_response(array('error'=>'Permission denied: delete_users'),403);
  $pdo->prepare("UPDATE users SET status='Active',deleted_at=NULL,failed_login_count=0,locked_at=NULL WHERE id=?")->execute(array($id));audit_log('user_restored','user',$id,null,$actor);rdi_notify_safely(function($svc) use ($id,$target,$actor){$svc->accountStatusChanged($id,$target['name'],'Active',$actor['name']);});json_response(array('ok'=>true));
}
if($action==='permissions'){
  if(!has_permission($actor,'assign_permissions'))json_response(array('error'=>'Permission denied: assign_permissions'),403);
  $overrides=isset($d['overrides'])&&is_array($d['overrides'])?$d['overrides']:array();
  $valid=array();$vst=$pdo->prepare("SELECT permission_key FROM permissions");$vst->execute();foreach($vst->fetchAll() as $r)$valid[$r['permission_key']]=true;
  $pdo->beginTransaction();try{$pdo->prepare("DELETE FROM user_permissions WHERE user_id=?")->execute(array($id));$ins=$pdo->prepare("INSERT INTO user_permissions(user_id,permission_key,allowed) VALUES(?,?,?)");foreach($overrides as $k=>$v){if(isset($valid[$k])&&($v===true||$v===false||$v===1||$v===0||$v==='1'||$v==='0'))$ins->execute(array($id,$k,($v===true||$v===1||$v==='1')?1:0));}$pdo->commit();}catch(Throwable $e){$pdo->rollBack();throw $e;}
  audit_log('user_permissions_changed','user',$id,'Custom RBAC overrides updated',$actor);json_response(array('ok'=>true));
}

if($action==='projects'){
  if(!has_permission($actor,'assign_projects'))json_response(array('error'=>'Permission denied: assign_projects'),403);
  $projectIds=isset($d['projectIds'])&&is_array($d['projectIds'])?$d['projectIds']:array();
  try{$svc=new ProjectService();$ids=$svc->setUserProjects($actor,$id,$projectIds);json_response(array('ok'=>true,'projectIds'=>$ids));}
  catch(InvalidArgumentException $e){json_response(array('error'=>$e->getMessage()),422);}
  catch(OutOfBoundsException $e){json_response(array('error'=>$e->getMessage()),404);}
  catch(DomainException $e){json_response(array('error'=>$e->getMessage()),403);}
}
json_response(array('error'=>'Invalid action.'),422);
