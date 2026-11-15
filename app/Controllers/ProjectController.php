<?php
require_once __DIR__ . '/../Services/ProjectService.php';

class ProjectController {
    private $service;
    public function __construct($service=null){$this->service=$service?:new ProjectService();}
    public function handle($user){
        $method=$_SERVER['REQUEST_METHOD']??'GET';
        try{
            if($method==='GET')$this->get($user);
            if($method==='POST')$this->post($user);
            json_response(array('error'=>'Method not allowed.'),405);
        }catch(InvalidArgumentException $e){json_response(array('error'=>$e->getMessage()),422);}
         catch(OutOfBoundsException $e){json_response(array('error'=>$e->getMessage()),404);}
         catch(DomainException $e){json_response(array('error'=>$e->getMessage()),403);}
    }
    private function get($user){
        $id=trim($_GET['id']??'');
        if($id!==''){$project=$this->service->getProject($user,$id);json_response(array('ok'=>true,'project'=>$project,'availableUsers'=>$this->service->activeUsersForProject($user,$id)));}
        json_response(array('ok'=>true,'projects'=>$this->service->listProjects($user),'availableUsers'=>$this->service->activeUsers($user)));
    }
    private function post($user){
        $d=body_json();$action=$d['action']??'';
        if($action==='create')json_response(array('ok'=>true,'project'=>$this->service->createProject($user,$d)),201);
        $projectId=trim($d['projectId']??'');if($projectId==='')json_response(array('error'=>'projectId is required.'),422);
        if($action==='update')json_response(array('ok'=>true,'project'=>$this->service->updateProject($user,$projectId,$d)));
        if($action==='member_add')json_response(array('ok'=>true,'members'=>$this->service->addMember($user,$projectId,trim($d['userId']??''),trim($d['membershipRole']??'Member'))));
        if($action==='member_remove')json_response(array('ok'=>true,'members'=>$this->service->removeMember($user,$projectId,trim($d['userId']??''))));
        if($action==='comment')json_response(array('ok'=>true,'id'=>$this->service->addComment($user,$projectId,$d['message']??'',isset($d['parentId'])?$d['parentId']:null)),201);
        if($action==='file_add')json_response(array('ok'=>true,'id'=>$this->service->addFile($user,$projectId,$d)),201);
        if($action==='file_delete'){$this->service->deleteFile($user,$projectId,trim($d['fileId']??''));json_response(array('ok'=>true));}
        if($action==='paper_add')json_response(array('ok'=>true,'id'=>$this->service->addPaper($user,$projectId,$d)),201);
        if($action==='paper_delete'){$this->service->deletePaper($user,$projectId,trim($d['paperId']??''));json_response(array('ok'=>true));}
        json_response(array('error'=>'Invalid project action.'),422);
    }
}
