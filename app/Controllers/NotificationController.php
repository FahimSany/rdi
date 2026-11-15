<?php
require_once __DIR__ . '/../Services/NotificationService.php';

class NotificationController {
    private $service;
    public function __construct($service = null) { $this->service = $service ?: new NotificationService(); }

    public function handle($user) {
        $method=isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'GET';
        if($method==='GET') return $this->index($user);
        if($method==='POST') return $this->mutate($user);
        json_response(array('error'=>'Method not allowed'),405);
    }

    private function index($user) {
        $status=isset($_GET['status'])?strtolower(trim($_GET['status'])):'all';
        if(!in_array($status,array('all','read','unread'),true))$status='all';
        $type=isset($_GET['type'])?trim($_GET['type']):'';
        if($type!==''&&!preg_match('/^[a-z0-9_\-]{1,80}$/i',$type))json_response(array('error'=>'Invalid notification type.'),422);
        $limit=isset($_GET['limit'])?(int)$_GET['limit']:30;
        $offset=isset($_GET['offset'])?(int)$_GET['offset']:0;
        $items=$this->service->listForUser($user['id'],$status,$type,$limit,$offset);
        json_response(array('ok'=>true,'items'=>$items,'unreadCount'=>$this->service->unreadCount($user['id']),'total'=>$this->service->countForUser($user['id'],$status,$type)));
    }

    private function mutate($user) {
        $d=body_json();$action=isset($d['action'])?$d['action']:'';
        if($action==='mark_read'){
            $id=isset($d['id'])?(int)$d['id']:0;if($id<1)json_response(array('error'=>'Notification id required.'),422);
            $changed=$this->service->markRead($id,$user['id']);
            audit_log('notification_marked_read','notification',(string)$id,null,$user);
            json_response(array('ok'=>true,'changed'=>$changed,'unreadCount'=>$this->service->unreadCount($user['id'])));
        }
        if($action==='mark_all_read'){
            $changed=$this->service->markAllRead($user['id']);
            audit_log('notifications_marked_all_read','notification',null,'Marked '.$changed.' notification(s) read',$user);
            json_response(array('ok'=>true,'changed'=>$changed,'unreadCount'=>0));
        }
        json_response(array('error'=>'Invalid notification action.'),422);
    }
}
