<?php
require_once __DIR__.'/../includes/db.php';
require_admin();
$d=body_json(); $id=$d['id']??'';
if($_SERVER['REQUEST_METHOD']==='DELETE' || ($_SERVER['REQUEST_METHOD']==='POST' && ($d['action']??'')==='delete')){
  $st=db()->prepare("DELETE FROM contact_messages WHERE id=?");$st->execute([$id]);json_response(['ok'=>true]);
}
json_response(['error'=>'Method not allowed'],405);
