<?php
require_once __DIR__.'/../includes/state.php';
require_once __DIR__.'/../includes/notifications.php';
$method=$_SERVER['REQUEST_METHOD'];
if($method==='GET'){
  $u=current_user(); json_response(load_state($u?'member':'public',$u));
}
if($method==='PUT' || $method==='POST'){
  $u=require_user(); $data=body_json(); $current=load_state('member',$u); $out=array();
  $beforeTaskMap=array();$bst=db()->prepare("SELECT id,title,project_id,assignee_user_id,status FROM tasks");$bst->execute();foreach($bst->fetchAll() as $bt)$beforeTaskMap[$bt['id']]=$bt;
  $beforeNewsMap=array();$bnst=db()->prepare("SELECT id,approved FROM research_news");$bnst->execute();foreach($bnst->fetchAll() as $bn)$beforeNewsMap[$bn['id']]=(bool)$bn['approved'];
  if(has_permission($u,'manage_content')){foreach(array('gallery','blogs','homeSections') as $k)$out[$k]=$data[$k]??$current[$k];}else{foreach(array('gallery','blogs','homeSections') as $k)$out[$k]=$current[$k];}
  if(has_permission($u,'manage_research_news'))$out['news']=$data['news']??$current['news'];else$out['news']=$current['news'];
  if(has_permission($u,'manage_tasks'))$out['tasks']=$data['tasks']??$current['tasks'];
  else{
    /* Members may only change status of tasks already assigned to their own account. */
    $incoming=array();foreach(($data['tasks']??array()) as $t)$incoming[$t['id']]=$t;
    $all=load_state('member',array_merge($u,array('permissions'=>array_merge($u['permissions']??array(),array('manage_tasks')))))['tasks'];
    foreach($all as &$t){if(($t['assigneeUserId']??null)===$u['id']&&isset($incoming[$t['id']])){$ns=$incoming[$t['id']]['status']??$t['status'];if(in_array($ns,array('To Do','In Progress','Review','Done'),true))$t['status']=$ns;}}unset($t);$out['tasks']=$all;
  }
  $out['settings']=has_permission($u,'manage_settings')?($data['settings']??$current['settings']):$current['settings'];
  save_state($out);
  if(isset($out['tasks'])&&is_array($out['tasks'])){$projectSvc=new ProjectService();foreach($out['tasks'] as $task){$uid=isset($task['assigneeUserId'])?$task['assigneeUserId']:null;$before=isset($beforeTaskMap[$task['id']])?$beforeTaskMap[$task['id']]:null;$oldUid=$before?$before['assignee_user_id']:null;if($uid&&$uid!==$oldUid){rdi_notify_safely(function($svc) use ($task,$uid){$svc->taskAssigned($task['id'],$uid,$task['title'],isset($task['project'])?$task['project']:'',isset($task['due'])?$task['due']:null);});}$pid=isset($task['projectId'])?$task['projectId']:null;if($pid){if(!$before)$projectSvc->recordTaskActivity($u,$pid,'task_added','Added task “'.$task['title'].'”.');elseif(($before['status']??'')!==($task['status']??''))$projectSvc->recordTaskActivity($u,$pid,'task_status_changed','Changed task “'.$task['title'].'” from '.($before['status']??'').' to '.($task['status']??'').'.');}}}
  if(isset($out['news'])&&is_array($out['news'])){foreach($out['news'] as $news){$was=isset($beforeNewsMap[$news['id']])?$beforeNewsMap[$news['id']]:false;$now=!empty($news['approved']);if($now&&!$was){rdi_notify_safely(function($svc) use ($news){$svc->researchUpdate($news['id'],$news['title'],isset($news['category'])?$news['category']:'Research');});}}}
  audit_log('state_updated','platform',null,'Authorized CMS/task update',$u); json_response(array('ok'=>true));
}
json_response(array('error'=>'Method not allowed'),405);
