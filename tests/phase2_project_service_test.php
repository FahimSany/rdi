<?php
require_once __DIR__ . '/../app/Services/ProjectService.php';
class FakeProjectRepo {
  public $projects; public function __construct(){ $this->projects=array('p1'=>array('id'=>'p1','name'=>'Alpha','description'=>'A','leader_user_id'=>'u1','leader_name'=>'Alice','status'=>'Active','start_date'=>'2026-10-01','end_date'=>'2026-12-01','created_at'=>'2026-10-01','updated_at'=>'2026-10-01')); }
  public function listAll(){return array_values($this->projects);} public function listForUser($id){return $id==='u1'?array_values($this->projects):array();}
  public function find($id){return isset($this->projects[$id])?$this->projects[$id]:null;} public function hasAccess($pid,$uid){return $pid==='p1'&&$uid==='u1';}
  public function tasks($id){return array(array('id'=>'t1'));} public function files($id){return array();} public function papers($id){return array();} public function members($id){return array(array('user_id'=>'u1'));} public function comments($id){return array();} public function activity($id,$limit=100){return array();}
}
class FakeNotifications {}
function ok($condition,$message){if(!$condition){fwrite(STDERR,"FAIL: $message\n");exit(1);}echo "PASS: $message\n";}
$repo=new FakeProjectRepo();$svc=new ProjectService($repo,new FakeNotifications());
$member=array('id'=>'u1','role'=>'researcher','permissions'=>array('view_projects','comment_projects'));
$outsider=array('id'=>'u2','role'=>'researcher','permissions'=>array('view_projects','comment_projects'));
$admin=array('id'=>'a1','role'=>'admin','permissions'=>array('view_projects','manage_projects'));
ok(count($svc->listProjects($member))===1,'member sees assigned/led project');
ok(count($svc->listProjects($outsider))===0,'researcher does not see unassigned project');
ok(count($svc->listProjects($admin))===1,'project manager sees all projects');
$p=$svc->getProject($member,'p1');ok($p['id']==='p1'&&count($p['tasks'])===1,'project detail includes related tasks');ok($p['canManage']===true,'project leader can manage own project');
$denied=false;try{$svc->getProject($outsider,'p1');}catch(DomainException $e){$denied=true;}ok($denied,'server service rejects unauthorized project detail');
echo "Phase 2 ProjectService tests passed.\n";
