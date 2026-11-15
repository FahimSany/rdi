<?php
require_once __DIR__.'/../includes/db.php';
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['error'=>'Method not allowed'],405);
$d=body_json();$id=$d['id']??('m'.bin2hex(random_bytes(8)));
$st=db()->prepare("INSERT INTO contact_messages(id,name,email,subject,message,email_status,created_at) VALUES(?,?,?,?,?,'stored',NOW())");
$st->execute([$id,trim($d['name']??''),trim($d['email']??''),trim($d['subject']??''),trim($d['message']??'')]);
json_response(['ok'=>true,'id'=>$id],201);
