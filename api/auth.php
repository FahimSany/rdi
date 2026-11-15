<?php
require_once __DIR__.'/../includes/db.php';
start_rdi_session();
$method=isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

if($method==='POST'){
  $d=body_json();
  $email=strtolower(trim(isset($d['email']) ? $d['email'] : ''));
  $pass=(string)(isset($d['password']) ? $d['password'] : '');
  if(!$email || !filter_var($email,FILTER_VALIDATE_EMAIL) || $pass==='') json_response(array('error'=>'Email and password are required.'),422);

  $st=db()->prepare("SELECT id,rdi_member_id,name,email,password_hash,role,department,team,profile_image,status,join_date,deleted_at,failed_login_count FROM users WHERE LOWER(email)=? LIMIT 1");
  $st->execute(array($email));
  $u=$st->fetch();
  if(!$u){ audit_log('login_failed','user',null,'Unknown email: '.$email,null); json_response(array('error'=>'Invalid email or password.'),401); }
  if(($u['status']??'Active')!=='Active' || !empty($u['deleted_at'])){
    json_response(array('error'=>'This account is '.$u['status'].'. Please contact an RDI administrator.'),403);
  }
  if(!password_verify($pass,$u['password_hash'])){
    $fails=((int)($u['failed_login_count']??0))+1;
    if($fails>=5){
      $up=db()->prepare("UPDATE users SET failed_login_count=?, status='Locked', locked_at=NOW() WHERE id=?");
      $up->execute(array($fails,$u['id']));
      audit_log('account_locked','user',$u['id'],'Locked after repeated failed logins',null);
      json_response(array('error'=>'Account locked after repeated failed login attempts. Contact an administrator.'),423);
    }
    $up=db()->prepare("UPDATE users SET failed_login_count=? WHERE id=?");$up->execute(array($fails,$u['id']));
    audit_log('login_failed','user',$u['id'],'Incorrect password',null);
    json_response(array('error'=>'Invalid email or password.'),401);
  }

  db()->prepare("UPDATE users SET failed_login_count=0, locked_at=NULL WHERE id=?")->execute(array($u['id']));
  if (function_exists('password_needs_rehash') && password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
    db()->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute(array(password_hash($pass,PASSWORD_DEFAULT),$u['id']));
  }
  unset($u['password_hash'],$u['failed_login_count'],$u['deleted_at']);
  $u['permissions']=effective_permissions($u);
  session_regenerate_id(true);
  $_SESSION['rdi_user']=$u; $_SESSION['rdi_login_at']=time();
  audit_log('login_success','user',$u['id'],'User logged in',$u);
  json_response(array('ok'=>true,'user'=>$u));
}

if($method==='DELETE'){
  $u=isset($_SESSION['rdi_user'])?$_SESSION['rdi_user']:null;
  if($u) audit_log('logout','user',$u['id'],'User logged out',$u);
  $_SESSION=array();
  if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}
  session_destroy(); json_response(array('ok'=>true));
}

if($method==='GET') json_response(array('ok'=>true,'user'=>current_user()));
json_response(array('error'=>'Method not allowed'),405);
