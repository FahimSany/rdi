<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/../app/Services/ProjectService.php';
function dt_out($v){ return $v ? str_replace(' ','T',$v) : null; }
function sql_dt($v){ if(!$v)return null; return str_replace('T',' ',substr($v,0,19)); }

function load_state($mode='public',$user=null) {
  $pdo=db();
  $st=$pdo->prepare("UPDATE blogs SET status='published' WHERE status='scheduled' AND publish_at IS NOT NULL AND publish_at <= NOW()");$st->execute();
  if($user===null && $mode!=='public') $user=current_user();
  $state=array(
    'users'=>array(),'gallery'=>array(),'blogs'=>array(),'news'=>array(),'homeSections'=>array(),'messages'=>array(),'applications'=>array(),'tasks'=>array(),'settings'=>array(),
    'memberInvites'=>array(),'roles'=>array(),'permissions'=>array(),'rolePermissions'=>array(),'hrRequests'=>array(),'auditLogs'=>array(),'projects'=>array(),'currentUser'=>$user
  );

  $st=$pdo->prepare("SELECT * FROM gallery ORDER BY item_date DESC, created_at DESC");$st->execute();foreach($st->fetchAll() as $r) $state['gallery'][]=array('id'=>$r['id'],'title'=>$r['title'],'category'=>$r['category'],'image'=>$r['image'],'description'=>$r['description'],'date'=>$r['item_date']);
  $st=$pdo->prepare("SELECT * FROM blogs ORDER BY COALESCE(publish_at,created_at) DESC");$st->execute();foreach($st->fetchAll() as $r) $state['blogs'][]=array('id'=>$r['id'],'title'=>$r['title'],'slug'=>$r['slug'],'excerpt'=>$r['excerpt'],'body'=>$r['body'],'image'=>$r['image'],'status'=>$r['status'],'publishAt'=>dt_out($r['publish_at']),'createdAt'=>dt_out($r['created_at']));
  $st=$pdo->prepare("SELECT * FROM research_news ORDER BY published_at DESC");$st->execute();foreach($st->fetchAll() as $r) $state['news'][]=array('id'=>$r['id'],'title'=>$r['title'],'source'=>$r['source'],'url'=>$r['url'],'summary'=>$r['summary'],'category'=>$r['category'],'approved'=>(bool)$r['approved'],'generated'=>(bool)$r['generated'],'publishedAt'=>dt_out($r['published_at']));
  $st=$pdo->prepare("SELECT * FROM home_sections ORDER BY sort_order,id");$st->execute();foreach($st->fetchAll() as $r) $state['homeSections'][]=array('id'=>$r['id'],'type'=>$r['type'],'title'=>$r['title'],'description'=>$r['description'],'media'=>$r['media'],'buttonText'=>$r['button_text'],'buttonLink'=>$r['button_link'],'enabled'=>(bool)$r['enabled']);

  if($user){
    if(has_permission($user,'view_projects')||has_permission($user,'manage_projects')){
      try{$projectService=new ProjectService();$state['projects']=$projectService->listProjects($user);}catch(Throwable $e){error_log('[RDI projects state] '.$e->getMessage());}
    }
    if(has_permission($user,'manage_tasks')) {$taskSt=$pdo->prepare("SELECT * FROM tasks ORDER BY created_at");$taskSt->execute();}
    else { $taskSt=$pdo->prepare("SELECT * FROM tasks WHERE assignee_user_id=? OR (assignee_user_id IS NULL AND assignee=?) ORDER BY created_at");$taskSt->execute(array($user['id'],$user['name'])); }
    foreach($taskSt as $r)$state['tasks'][]=array('id'=>$r['id'],'title'=>$r['title'],'project'=>$r['project'],'projectId'=>$r['project_id']??null,'assignee'=>$r['assignee'],'assigneeUserId'=>$r['assignee_user_id']??null,'due'=>$r['due_date'],'priority'=>$r['priority'],'status'=>$r['status']);

    if(has_permission($user,'view_users')){
      $ust=$pdo->prepare("SELECT id,rdi_member_id,name,email,role,department,team,profile_image,status,join_date,deleted_at,created_at FROM users ORDER BY created_at DESC");$ust->execute();$users=$ust->fetchAll();
      $ost=$pdo->prepare("SELECT user_id,permission_key,allowed FROM user_permissions");$ost->execute();$ov=$ost->fetchAll();$ovMap=array();foreach($ov as $x)$ovMap[$x['user_id']][$x['permission_key']]=(bool)$x['allowed'];
      $pmMap=array();$pst=$pdo->prepare("SELECT user_id,project_id FROM project_memberships");$pst->execute();foreach($pst->fetchAll() as $m)$pmMap[$m['user_id']][]=$m['project_id'];
      foreach($users as $x){$x['permissions']=effective_permissions($x);$x['permissionOverrides']=$ovMap[$x['id']]??array();$x['projectIds']=$pmMap[$x['id']]??array();$state['users'][]=$x;}
      $rst=$pdo->prepare("SELECT slug,name,description FROM roles ORDER BY FIELD(slug,'super_admin','admin','researcher'),name");$rst->execute();foreach($rst->fetchAll() as $r)$state['roles'][]=$r;
      $pst2=$pdo->prepare("SELECT permission_key,name,description FROM permissions ORDER BY name");$pst2->execute();foreach($pst2->fetchAll() as $r)$state['permissions'][]=$r;
      $rpst=$pdo->prepare("SELECT role_slug,permission_key FROM role_permissions");$rpst->execute();foreach($rpst->fetchAll() as $r)$state['rolePermissions'][$r['role_slug']][]=$r['permission_key'];
    }
    if(has_permission($user,'create_member_ids')) {$mist=$pdo->prepare("SELECT mi.*,u.name AS used_by_name FROM member_invites mi LEFT JOIN users u ON u.id=mi.used_by ORDER BY mi.created_at DESC");$mist->execute();foreach($mist->fetchAll() as $r)$state['memberInvites'][]=$r;}
    if(has_permission($user,'manage_content')) {$cmst=$pdo->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC");$cmst->execute();foreach($cmst->fetchAll() as $r)$state['messages'][]=array('id'=>$r['id'],'name'=>$r['name'],'email'=>$r['email'],'subject'=>$r['subject'],'message'=>$r['message'],'createdAt'=>dt_out($r['created_at']),'emailStatus'=>$r['email_status']);}
    if(has_permission($user,'manage_hr_requests')) {$hr=$pdo->prepare("SELECT h.*,u.name,u.email,u.rdi_member_id,d.signed_by_name,d.signed_at FROM hr_requests h JOIN users u ON u.id=h.user_id LEFT JOIN hr_documents d ON d.request_id=h.id ORDER BY h.requested_at DESC");$hr->execute();}
    else {$hr=$pdo->prepare("SELECT h.*,u.name,u.email,u.rdi_member_id,d.signed_by_name,d.signed_at FROM hr_requests h JOIN users u ON u.id=h.user_id LEFT JOIN hr_documents d ON d.request_id=h.id WHERE h.user_id=? ORDER BY h.requested_at DESC");$hr->execute(array($user['id']));}
    foreach($hr as $r)$state['hrRequests'][]=array('id'=>$r['id'],'userId'=>$r['user_id'],'name'=>$r['name'],'email'=>$r['email'],'memberId'=>$r['rdi_member_id'],'documentType'=>$r['document_type'],'requestNote'=>$r['request_note'],'status'=>$r['status'],'adminNote'=>$r['admin_note'],'requestedAt'=>dt_out($r['requested_at']),'reviewedAt'=>dt_out($r['reviewed_at']),'approvedAt'=>dt_out($r['approved_at']),'signedByName'=>$r['signed_by_name']??null,'signedAt'=>dt_out($r['signed_at']??null));
    if(has_permission($user,'view_audit_logs')) {$ast=$pdo->prepare("SELECT a.*,u.name AS actor_name FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_user_id ORDER BY a.created_at DESC LIMIT 200");$ast->execute();foreach($ast->fetchAll() as $r)$state['auditLogs'][]=$r;}
  }

  $sst=$pdo->prepare("SELECT setting_key,setting_value FROM settings");$sst->execute();foreach($sst->fetchAll() as $r){
    $v=$r['setting_value']; if(in_array($r['setting_key'],array('autoApproveNews','demoAutomation'),true)) $v=$v==='1';
    $state['settings'][$r['setting_key']]=$v;
  }
  $state['settings'] += array('contactEmail'=>'info.rd.innovators@gmail.com','contactEndpoint'=>'','researchApiEndpoint'=>'','autoApproveNews'=>true,'demoAutomation'=>false,'newsAutomationIntervalHours'=>'6','newsMaxItems'=>'5','newsCategories'=>'cs.AI,cs.LG,cs.CV,cs.CL,cs.RO','lastNewsAutomationAt'=>'','lastNewsAutomationStatus'=>'never','lastNewsAutomationCount'=>'0','lastNewsAutomationMessage'=>'Not run yet.','lastNewsAutomationSource'=>'arXiv','siteName'=>'RDI Innovators','rdiEmailFormat'=>'username.rdi@gmail.com','rdiEmailDomain'=>'gmail.com','passwordResetMinutes'=>'30');
  if(!$user){
    /* Public visitors do not need internal automation endpoints/settings. */
    $state['settings']=array('siteName'=>$state['settings']['siteName'],'contactEmail'=>$state['settings']['contactEmail'],'rdiEmailFormat'=>$state['settings']['rdiEmailFormat']);
  }
  return $state;
}

function save_state($s) {
  $pdo=db(); $pdo->beginTransaction();
  try{
    $maps=array(
      'gallery'=>array('table'=>'gallery','cols'=>array('id','title','category','image','description','item_date'),'vals'=>function($x){return array($x['id'],$x['title'],isset($x['category'])?$x['category']:'Project',isset($x['image'])?$x['image']:'',isset($x['description'])?$x['description']:'',!empty($x['date'])?$x['date']:null);}),
      'blogs'=>array('table'=>'blogs','cols'=>array('id','title','slug','excerpt','body','image','status','publish_at','created_at'),'vals'=>function($x){return array($x['id'],$x['title'],isset($x['slug'])?$x['slug']:'',isset($x['excerpt'])?$x['excerpt']:'',isset($x['body'])?$x['body']:'',isset($x['image'])?$x['image']:'',isset($x['status'])?$x['status']:'draft',sql_dt(isset($x['publishAt'])?$x['publishAt']:null),sql_dt(isset($x['createdAt'])?$x['createdAt']:date('c')));}),
      'news'=>array('table'=>'research_news','cols'=>array('id','title','source','url','summary','category','approved','generated','published_at'),'vals'=>function($x){return array($x['id'],$x['title'],isset($x['source'])?$x['source']:'',isset($x['url'])?$x['url']:'',isset($x['summary'])?$x['summary']:'',isset($x['category'])?$x['category']:'',!empty($x['approved'])?1:0,!empty($x['generated'])?1:0,sql_dt(isset($x['publishedAt'])?$x['publishedAt']:date('c')));}),
      'homeSections'=>array('table'=>'home_sections','cols'=>array('id','type','title','description','media','button_text','button_link','enabled','sort_order'),'vals'=>function($x,$i){return array($x['id'],isset($x['type'])?$x['type']:'banner',isset($x['title'])?$x['title']:'',isset($x['description'])?$x['description']:'',isset($x['media'])?$x['media']:'',isset($x['buttonText'])?$x['buttonText']:'',isset($x['buttonLink'])?$x['buttonLink']:'',!empty($x['enabled'])?1:0,$i);}),
      'tasks'=>array('table'=>'tasks','cols'=>array('id','title','project','project_id','assignee','assignee_user_id','due_date','priority','status'),'vals'=>function($x){return array($x['id'],$x['title'],isset($x['project'])?$x['project']:'',isset($x['projectId'])?$x['projectId']:null,isset($x['assignee'])?$x['assignee']:'',isset($x['assigneeUserId'])?$x['assigneeUserId']:null,!empty($x['due'])?$x['due']:null,isset($x['priority'])?$x['priority']:'Medium',isset($x['status'])?$x['status']:'To Do');})
    );
    foreach($maps as $key=>$m){
      if(!array_key_exists($key,$s))continue;
      $del=$pdo->prepare("DELETE FROM {$m['table']}");$del->execute(); $cols=implode(',',$m['cols']); $ph=implode(',',array_fill(0,count($m['cols']),'?')); $st=$pdo->prepare("INSERT INTO {$m['table']} ($cols) VALUES ($ph)");
      $items=is_array($s[$key])?$s[$key]:array(); foreach($items as $i=>$x){$vals=$key==='homeSections'?call_user_func($m['vals'],$x,$i):call_user_func($m['vals'],$x);$st->execute($vals);}
    }
    if(isset($s['settings']) && is_array($s['settings'])){$st=$pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");foreach($s['settings'] as $k=>$v){if(is_bool($v))$v=$v?'1':'0';$st->execute(array($k,(string)$v));}}
    $pdo->commit();
  }catch(Throwable $e){$pdo->rollBack();throw $e;}
}
