<?php
require_once __DIR__ . '/../app/Services/NotificationService.php';
class FakeNotificationRepository {
    public $rows=array();
    public function create($userId,$type,$title,$message,$link=null,$dedupeKey=null){
        foreach($this->rows as $r){if($dedupeKey && $r['user_id']===$userId && $r['dedupe_key']===$dedupeKey)return null;}
        $this->rows[]=array('user_id'=>$userId,'type'=>$type,'title'=>$title,'message'=>$message,'link'=>$link,'dedupe_key'=>$dedupeKey);return count($this->rows);
    }
    public function activeUserIds(){return array('u1','u2');}
    public function activeAdminIds(){return array('admin1','admin2');}
}
function expect_true($v,$msg){if(!$v){fwrite(STDERR,"FAIL: $msg\n");exit(1);}echo "PASS: $msg\n";}
$repo=new FakeNotificationRepository();$svc=new NotificationService($repo);
$svc->taskAssigned('t1','u1','Review paper','Research','2026-10-10');
expect_true(count($repo->rows)===1,'task assignment creates one notification');
expect_true($repo->rows[0]['type']==='task_assigned','task notification type');
$svc->taskAssigned('t1','u1','Review paper','Research','2026-10-10');
expect_true(count($repo->rows)===1,'task assignment deduplicates');
$svc->hrDecision('u1','hr1','NOC','Approved','Looks good');
expect_true($repo->rows[1]['type']==='hr_approved','HR approval notification type');
$svc->hrDecision('u1','hr2','NDA','Rejected','Needs changes');
expect_true($repo->rows[2]['type']==='hr_rejected','HR rejection notification type');
$svc->projectAssigned('u2','RDI Website','p1');
expect_true($repo->rows[3]['type']==='project_assignment','project assignment notification type');
$svc->deadlineApproaching('t2','u2','Deploy portal','2026-10-09',2);
expect_true($repo->rows[4]['type']==='deadline_approaching','deadline notification type');
$svc->researchUpdate('n1','New AI paper','AI/ML');
expect_true(count($repo->rows)===7,'research update fans out to all active users');
$svc->memberIdCreated('RDI-RES-2026-0009','member@example.com',array('id'=>'admin1'));
expect_true(count($repo->rows)===9,'member ID event notifies active admins');
echo "Phase 1 NotificationService tests passed.\n";
