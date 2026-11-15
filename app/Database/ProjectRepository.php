<?php
require_once __DIR__ . '/../../includes/db.php';

class ProjectRepository {
    private $pdo;
    public function __construct($pdo=null){$this->pdo=$pdo?:db();}

    public function listAll(){
        $st=$this->pdo->prepare("SELECT p.*,u.name AS leader_name,u.email AS leader_email,(SELECT COUNT(*) FROM project_memberships pm WHERE pm.project_id=p.id) AS member_count,(SELECT COUNT(*) FROM tasks t WHERE t.project_id=p.id) AS task_count FROM projects p LEFT JOIN users u ON u.id=p.leader_user_id ORDER BY COALESCE(p.start_date,'9999-12-31'),p.name");
        $st->execute();return $st->fetchAll();
    }
    public function listForUser($userId){
        $st=$this->pdo->prepare("SELECT DISTINCT p.*,u.name AS leader_name,u.email AS leader_email,(SELECT COUNT(*) FROM project_memberships pm2 WHERE pm2.project_id=p.id) AS member_count,(SELECT COUNT(*) FROM tasks t WHERE t.project_id=p.id) AS task_count FROM projects p LEFT JOIN users u ON u.id=p.leader_user_id LEFT JOIN project_memberships pm ON pm.project_id=p.id WHERE p.leader_user_id=? OR pm.user_id=? ORDER BY COALESCE(p.start_date,'9999-12-31'),p.name");
        $st->execute(array($userId,$userId));return $st->fetchAll();
    }
    public function find($id){
        $st=$this->pdo->prepare("SELECT p.*,u.name AS leader_name,u.email AS leader_email FROM projects p LEFT JOIN users u ON u.id=p.leader_user_id WHERE p.id=? LIMIT 1");$st->execute(array($id));return $st->fetch()?:null;
    }
    public function hasAccess($projectId,$userId){
        $st=$this->pdo->prepare("SELECT 1 FROM projects p LEFT JOIN project_memberships pm ON pm.project_id=p.id AND pm.user_id=? WHERE p.id=? AND (p.leader_user_id=? OR pm.user_id IS NOT NULL) LIMIT 1");$st->execute(array($userId,$projectId,$userId));return (bool)$st->fetchColumn();
    }
    public function create($data){
        $st=$this->pdo->prepare("INSERT INTO projects(id,name,description,leader_user_id,start_date,end_date,status) VALUES(?,?,?,?,?,?,?)");
        $st->execute(array($data['id'],$data['name'],$data['description'],$data['leader_user_id'],$data['start_date'],$data['end_date'],$data['status']));
        return $this->find($data['id']);
    }
    public function update($id,$data){
        $st=$this->pdo->prepare("UPDATE projects SET name=?,description=?,leader_user_id=?,start_date=?,end_date=?,status=? WHERE id=?");
        $st->execute(array($data['name'],$data['description'],$data['leader_user_id'],$data['start_date'],$data['end_date'],$data['status'],$id));return $this->find($id);
    }
    public function delete($id){$st=$this->pdo->prepare("DELETE FROM projects WHERE id=?");$st->execute(array($id));return $st->rowCount();}
    public function activeUsers(){ $st=$this->pdo->prepare("SELECT id,rdi_member_id,name,email,role,department,team,status FROM users WHERE status='Active' AND deleted_at IS NULL ORDER BY name");$st->execute();return $st->fetchAll(); }
    public function projectIdsForUser($userId){$st=$this->pdo->prepare("SELECT project_id FROM project_memberships WHERE user_id=?");$st->execute(array($userId));return $st->fetchAll(PDO::FETCH_COLUMN);}
    public function members($projectId){
        $st=$this->pdo->prepare("SELECT pm.project_id,pm.user_id,pm.membership_role,pm.joined_at,u.rdi_member_id,u.name,u.email,u.role,u.department,u.team,u.status FROM project_memberships pm JOIN users u ON u.id=pm.user_id WHERE pm.project_id=? ORDER BY FIELD(pm.membership_role,'Project Lead','Lead','Supervisor','Member','Researcher'),u.name");$st->execute(array($projectId));return $st->fetchAll();
    }
    public function memberExists($projectId,$userId){$st=$this->pdo->prepare("SELECT 1 FROM project_memberships WHERE project_id=? AND user_id=? LIMIT 1");$st->execute(array($projectId,$userId));return (bool)$st->fetchColumn();}
    public function addMember($projectId,$userId,$role){$st=$this->pdo->prepare("INSERT INTO project_memberships(project_id,user_id,membership_role) VALUES(?,?,?) ON DUPLICATE KEY UPDATE membership_role=VALUES(membership_role)");$st->execute(array($projectId,$userId,$role));}
    public function removeMember($projectId,$userId){$st=$this->pdo->prepare("DELETE FROM project_memberships WHERE project_id=? AND user_id=?");$st->execute(array($projectId,$userId));return $st->rowCount();}
    public function user($userId){$st=$this->pdo->prepare("SELECT id,rdi_member_id,name,email,role,status FROM users WHERE id=? AND deleted_at IS NULL LIMIT 1");$st->execute(array($userId));return $st->fetch()?:null;}
    public function tasks($projectId){$st=$this->pdo->prepare("SELECT id,title,project,project_id,assignee,assignee_user_id,due_date,priority,status,created_at FROM tasks WHERE project_id=? ORDER BY created_at DESC");$st->execute(array($projectId));return $st->fetchAll();}
    public function files($projectId){$st=$this->pdo->prepare("SELECT f.*,u.name AS added_by_name FROM project_files f LEFT JOIN users u ON u.id=f.added_by WHERE f.project_id=? ORDER BY f.created_at DESC");$st->execute(array($projectId));return $st->fetchAll();}
    public function addFile($data){$st=$this->pdo->prepare("INSERT INTO project_files(id,project_id,file_name,file_url,description,added_by) VALUES(?,?,?,?,?,?)");$st->execute(array($data['id'],$data['project_id'],$data['file_name'],$data['file_url'],$data['description'],$data['added_by']));return $data['id'];}
    public function file($id){$st=$this->pdo->prepare("SELECT * FROM project_files WHERE id=? LIMIT 1");$st->execute(array($id));return $st->fetch()?:null;}
    public function deleteFile($id){$st=$this->pdo->prepare("DELETE FROM project_files WHERE id=?");$st->execute(array($id));return $st->rowCount();}
    public function papers($projectId){$st=$this->pdo->prepare("SELECT p.*,u.name AS added_by_name FROM project_papers p LEFT JOIN users u ON u.id=p.added_by WHERE p.project_id=? ORDER BY p.created_at DESC");$st->execute(array($projectId));return $st->fetchAll();}
    public function addPaper($data){$st=$this->pdo->prepare("INSERT INTO project_papers(id,project_id,title,authors,venue,doi,paper_url,notes,added_by) VALUES(?,?,?,?,?,?,?,?,?)");$st->execute(array($data['id'],$data['project_id'],$data['title'],$data['authors'],$data['venue'],$data['doi'],$data['paper_url'],$data['notes'],$data['added_by']));return $data['id'];}
    public function paper($id){$st=$this->pdo->prepare("SELECT * FROM project_papers WHERE id=? LIMIT 1");$st->execute(array($id));return $st->fetch()?:null;}
    public function deletePaper($id){$st=$this->pdo->prepare("DELETE FROM project_papers WHERE id=?");$st->execute(array($id));return $st->rowCount();}
    public function comments($projectId){$st=$this->pdo->prepare("SELECT c.*,u.name,u.rdi_member_id,u.profile_image FROM project_comments c JOIN users u ON u.id=c.user_id WHERE c.project_id=? ORDER BY c.created_at ASC");$st->execute(array($projectId));return $st->fetchAll();}
    public function comment($id){$st=$this->pdo->prepare("SELECT * FROM project_comments WHERE id=? LIMIT 1");$st->execute(array($id));return $st->fetch()?:null;}
    public function addComment($data){$st=$this->pdo->prepare("INSERT INTO project_comments(id,project_id,user_id,parent_id,message) VALUES(?,?,?,?,?)");$st->execute(array($data['id'],$data['project_id'],$data['user_id'],$data['parent_id'],$data['message']));return $data['id'];}
    public function activity($projectId,$limit=100){$limit=max(1,min(200,(int)$limit));$st=$this->pdo->prepare("SELECT a.*,u.name AS actor_name FROM project_activity a LEFT JOIN users u ON u.id=a.actor_user_id WHERE a.project_id=? ORDER BY a.created_at DESC LIMIT ".$limit);$st->execute(array($projectId));return $st->fetchAll();}
    public function logActivity($projectId,$actorId,$action,$details){$st=$this->pdo->prepare("INSERT INTO project_activity(project_id,actor_user_id,action,details) VALUES(?,?,?,?)");$st->execute(array($projectId,$actorId,$action,$details));}
}
