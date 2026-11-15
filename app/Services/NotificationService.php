<?php
require_once __DIR__ . '/../Database/NotificationRepository.php';

class NotificationService {
    private $repo;
    public function __construct($repo = null) { $this->repo = $repo ?: new NotificationRepository(); }

    public function listForUser($userId,$status='all',$type='',$limit=30,$offset=0) { return $this->repo->listForUser($userId,$status,$type,$limit,$offset); }
    public function countForUser($userId,$status='all',$type='') { return $this->repo->countForUser($userId,$status,$type); }
    public function unreadCount($userId) { return $this->repo->unreadCount($userId); }
    public function markRead($notificationId,$userId) { return $this->repo->markRead($notificationId,$userId); }
    public function markAllRead($userId) { return $this->repo->markAllRead($userId); }

    public function notifyUser($userId,$type,$title,$message,$link=null,$dedupeKey=null) {
        if (!$userId) return null;
        return $this->repo->create($userId,$type,$title,$message,$link,$dedupeKey);
    }
    public function notifyUsers($userIds,$type,$title,$message,$link=null,$dedupePrefix=null) {
        $created=0;
        foreach(array_unique(array_filter($userIds)) as $uid){
            $key=$dedupePrefix ? $dedupePrefix.':'.$uid : null;
            if($this->notifyUser($uid,$type,$title,$message,$link,$key)!==null)$created++;
        }
        return $created;
    }
    public function notifyAllActive($type,$title,$message,$link=null,$dedupePrefix=null) {
        return $this->notifyUsers($this->repo->activeUserIds(),$type,$title,$message,$link,$dedupePrefix);
    }
    public function memberIdCreated($memberId,$approvedEmail,$actor) {
        $msg='Member ID '.$memberId.' was created for '.$approvedEmail.'.';
        return $this->notifyUsers($this->repo->activeAdminIds(),'member_id_created','RDI Member ID created',$msg,'#admin?tab=member-ids','member-id:'.$memberId);
    }
    public function accountStatusChanged($targetUserId,$targetName,$status,$actorName) {
        $msg='Your RDI account status was changed to '.$status.($actorName?' by '.$actorName.'.':'.');
        return $this->notifyUser($targetUserId,'account_status','Account status: '.$status,$msg,'#admin?tab=profile','account-status:'.$targetUserId.':'.$status.':'.date('YmdHis'));
    }
    public function projectAssigned($userId,$projectName,$projectId) {
        return $this->notifyUser($userId,'project_assignment','New project assignment','You were assigned to '.$projectName.'.','#admin?tab=projects&project='.$projectId,'project:'.$projectId.':user:'.$userId);
    }
    public function taskAssigned($taskId,$userId,$taskTitle,$projectName,$dueDate) {
        $msg='You were assigned “'.$taskTitle.'”'.($projectName?' in '.$projectName:'').($dueDate?' · due '.$dueDate:'').'.';
        return $this->notifyUser($userId,'task_assigned','New task assigned',$msg,'#admin?tab=tasks','task-assigned:'.$taskId.':'.$userId);
    }
    public function deadlineApproaching($taskId,$userId,$taskTitle,$dueDate,$daysLeft) {
        $when=$daysLeft<=0?'due today':('due in '.$daysLeft.' day'.($daysLeft===1?'':'s'));
        return $this->notifyUser($userId,'deadline_approaching','Task deadline approaching','“'.$taskTitle.'” is '.$when.' ('.$dueDate.').','#admin?tab=tasks','deadline:'.$taskId.':'.$dueDate.':'.$userId);
    }
    public function hrDecision($userId,$requestId,$documentType,$status,$adminNote='') {
        $verb=$status==='Approved'?'approved':'rejected';
        $msg='Your '.$documentType.' request was '.$verb.'.'.($adminNote?' Note: '.$adminNote:'');
        return $this->notifyUser($userId,'hr_'.$verb,'HR request '.ucfirst($verb),$msg,'#admin?tab=hr-help','hr:'.$requestId.':'.$status);
    }
    public function researchUpdate($newsId,$title,$category) {
        $msg='New '.($category?$category.' ':'').'research update: '.$title;
        return $this->notifyAllActive('research_update','New research update',$msg,'#research-news','research:'.$newsId);
    }
}
