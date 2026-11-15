<?php
require_once __DIR__.'/../includes/db.php';
$actor=require_user();$method=$_SERVER['REQUEST_METHOD'];
if($method==='GET'){
  if(!has_permission($actor,'view_users'))json_response(array('error'=>'Permission denied.'),403);
  $st=db()->prepare("SELECT slug,name,description FROM roles ORDER BY FIELD(slug,'super_admin','admin','researcher'),name");$st->execute();$roles=$st->fetchAll();
  $st=db()->prepare("SELECT permission_key,name,description FROM permissions ORDER BY name");$st->execute();$perms=$st->fetchAll();
  $rp=array();$st=db()->prepare("SELECT role_slug,permission_key FROM role_permissions");$st->execute();foreach($st->fetchAll() as $r)$rp[$r['role_slug']][]=$r['permission_key'];
  json_response(array('ok'=>true,'roles'=>$roles,'permissions'=>$perms,'rolePermissions'=>$rp));
}
if($method==='POST'){
  if(!has_permission($actor,'manage_roles'))json_response(array('error'=>'Only authorized role managers can change role templates.'),403);
  $d=body_json();$role=$d['role']??'';$permissions=$d['permissions']??array();if(!is_array($permissions))$permissions=array();
  if($role==='super_admin')json_response(array('error'=>'Super Admin always retains all permissions.'),409);
  $st=db()->prepare("SELECT 1 FROM roles WHERE slug=?");$st->execute(array($role));if(!$st->fetchColumn())json_response(array('error'=>'Invalid role.'),422);
  $valid=array();$st=db()->prepare("SELECT permission_key FROM permissions");$st->execute();foreach($st->fetchAll() as $r)$valid[$r['permission_key']]=true;
  $pdo=db();$pdo->beginTransaction();try{$pdo->prepare("DELETE FROM role_permissions WHERE role_slug=?")->execute(array($role));$ins=$pdo->prepare("INSERT INTO role_permissions(role_slug,permission_key) VALUES(?,?)");foreach(array_unique($permissions) as $p)if(isset($valid[$p]))$ins->execute(array($role,$p));$pdo->commit();}catch(Throwable $e){$pdo->rollBack();throw $e;}
  audit_log('role_permissions_changed','role',$role,null,$actor);json_response(array('ok'=>true));
}
json_response(array('error'=>'Method not allowed'),405);
