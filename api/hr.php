<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/notifications.php';
$u=require_user();$pdo=db();$method=$_SERVER['REQUEST_METHOD'];
$types=array('Salary Certificate','Payslip','Employment Certificate','Certificate','NOC','NDA','Joining Letter');
function hr_content($type,$user,$requestNote,$docNo){
  $join=$user['join_date']?:'[Join date to be verified by HR]';$dept=$user['department']?:'[Department to be verified by HR]';$team=$user['team']?:'[Team to be verified by HR]';
  $base="RDI INNOVATORS\nDocument No: {$docNo}\nDocument Type: {$type}\n\nMember: {$user['name']}\nRDI Member ID: ".($user['rdi_member_id']?:'[Not assigned]')."\nApproved Email: {$user['email']}\nDepartment: {$dept}\nTeam: {$team}\nJoin Date: {$join}\n\n";
  $specific=array(
    'Salary Certificate'=>"This certifies the employment/membership details above. Salary amount and payroll details must be verified and completed by authorized HR before issue.\nSalary: [HR verification required]",
    'Payslip'=>"Pay period: [HR verification required]\nBasic/Gross salary: [HR verification required]\nDeductions: [HR verification required]\nNet pay: [HR verification required]",
    'Employment Certificate'=>"This is to certify that the above-named member is associated with RDI. Role, service period and employment wording must be reviewed by authorized HR before final issue.",
    'Certificate'=>"This certificate is generated for the purpose stated in the request. Certificate title/purpose: ".($requestNote?:'[HR review required]'),
    'NOC'=>"RDI has received a request for a No Objection Certificate. The exact purpose and approval conditions must be reviewed by authorized HR. Purpose: ".($requestNote?:'[Purpose required]'),
    'NDA'=>"NON-DISCLOSURE AGREEMENT DRAFT\nThis document requires review and signature by the member and authorized RDI representative. Confidential information, obligations, term, governing terms and signature blocks must be finalized by HR/legal review.\nRequest note: ".($requestNote?:'[None]'),
    'Joining Letter'=>"This joining letter records the approved RDI identity above. Appointment/joining terms, effective date, reporting line and any compensation terms must be reviewed and completed by authorized HR."
  );
  return $base.($specific[$type]??'')."\n\nStatus: APPROVED AFTER ADMIN REVIEW\nAuthorized approval: RDI Administration";
}
if($method==='GET'){
  if(has_permission($u,'manage_hr_requests')){$st=$pdo->prepare("SELECT h.*,u.name,u.email,u.rdi_member_id FROM hr_requests h JOIN users u ON u.id=h.user_id ORDER BY h.requested_at DESC");$st->execute();}
  else{$st=$pdo->prepare("SELECT h.*,u.name,u.email,u.rdi_member_id FROM hr_requests h JOIN users u ON u.id=h.user_id WHERE h.user_id=? ORDER BY h.requested_at DESC");$st->execute(array($u['id']));}
  json_response(array('ok'=>true,'requests'=>$st->fetchAll()));
}
if($method!=='POST')json_response(array('error'=>'Method not allowed'),405);
$d=body_json();$action=$d['action']??'create';
if($action==='create'){
  if(!has_permission($u,'request_hr_documents'))json_response(array('error'=>'Permission denied: request_hr_documents'),403);
  $type=$d['documentType']??'';if(!in_array($type,$types,true))json_response(array('error'=>'Invalid HR document type.'),422);
  $id='hr'.bin2hex(random_bytes(8));$pdo->prepare("INSERT INTO hr_requests(id,user_id,document_type,request_note,status) VALUES(?,?,?,?,'Requested')")->execute(array($id,$u['id'],$type,trim($d['note']??'')));
  audit_log('hr_document_requested','hr_request',$id,$type,$u);json_response(array('ok'=>true,'id'=>$id),201);
}
if($action==='sign'){
  $id=$d['id']??'';$signature=trim($d['signatureName']??'');if(!$id||!$signature)json_response(array('error'=>'Request id and signature name are required.'),422);
  $st=$pdo->prepare("SELECT h.id,h.user_id,h.document_type,h.status,d.id AS document_id,d.signed_at FROM hr_requests h JOIN hr_documents d ON d.request_id=h.id WHERE h.id=? LIMIT 1");$st->execute(array($id));$r=$st->fetch();
  if(!$r||$r['user_id']!==$u['id']||$r['document_type']!=='NDA'||$r['status']!=='Approved')json_response(array('error'=>'Only your own approved NDA can be signed.'),403);
  if(!empty($r['signed_at']))json_response(array('error'=>'This NDA has already been signed.'),409);
  $ip=isset($_SERVER['REMOTE_ADDR'])?substr($_SERVER['REMOTE_ADDR'],0,64):null;$pdo->prepare("UPDATE hr_documents SET signed_by_name=?,signed_at=NOW(),signature_ip=? WHERE id=?")->execute(array($signature,$ip,$r['document_id']));
  audit_log('nda_signed','hr_request',$id,'Internal typed-signature acknowledgement',$u);json_response(array('ok'=>true));
}
if(!has_permission($u,'manage_hr_requests'))json_response(array('error'=>'Permission denied: manage_hr_requests'),403);
$id=$d['id']??'';if(!$id)json_response(array('error'=>'Request id required.'),422);
$st=$pdo->prepare("SELECT h.*,u.name,u.email,u.rdi_member_id,u.department,u.team,u.join_date FROM hr_requests h JOIN users u ON u.id=h.user_id WHERE h.id=? LIMIT 1");$st->execute(array($id));$r=$st->fetch();if(!$r)json_response(array('error'=>'HR request not found.'),404);
$note=trim($d['adminNote']??'');
if($action==='accept'){$pdo->prepare("UPDATE hr_requests SET status='Accepted',admin_note=?,reviewed_at=NOW() WHERE id=?")->execute(array($note,$id));}
elseif($action==='review'){$pdo->prepare("UPDATE hr_requests SET status='Under Review',admin_note=?,reviewed_at=NOW() WHERE id=?")->execute(array($note,$id));}
elseif($action==='reject'){$pdo->prepare("UPDATE hr_requests SET status='Rejected',admin_note=?,reviewed_at=NOW() WHERE id=?")->execute(array($note,$id));}
elseif($action==='approve'){
  $docNo='RDI-HR-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8));$docId='doc'.bin2hex(random_bytes(8));$content=hr_content($r['document_type'],$r,$r['request_note'],$docNo);
  $pdo->beginTransaction();try{$pdo->prepare("UPDATE hr_requests SET status='Approved',admin_note=?,reviewed_at=NOW(),approved_at=NOW() WHERE id=?")->execute(array($note,$id));$pdo->prepare("INSERT INTO hr_documents(id,request_id,user_id,document_type,document_number,content) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE document_number=VALUES(document_number),content=VALUES(content),created_at=NOW()")->execute(array($docId,$id,$r['user_id'],$r['document_type'],$docNo,$content));$pdo->commit();}catch(Throwable $e){$pdo->rollBack();throw $e;}
}else json_response(array('error'=>'Invalid HR action.'),422);
audit_log('hr_request_'.$action,'hr_request',$id,$note,$u);
if(in_array($action,array('approve','reject'),true)){
  $finalStatus=$action==='approve'?'Approved':'Rejected';
  rdi_notify_safely(function($svc) use ($r,$id,$finalStatus,$note){$svc->hrDecision($r['user_id'],$id,$r['document_type'],$finalStatus,$note);});
}
json_response(array('ok'=>true));
