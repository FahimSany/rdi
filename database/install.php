<?php
header('Content-Type: text/html; charset=utf-8');
$config = require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/migration_lib.php';

function rdi_has_column($pdo,$table,$column){
  $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $st->execute(array($table,$column)); return (bool)$st->fetchColumn();
}
function rdi_add_column($pdo,$table,$column,$definition){
  if(!rdi_has_column($pdo,$table,$column)) $pdo->prepare("ALTER TABLE `$table` ADD COLUMN `$column` $definition")->execute();
}

try {
  $rootDsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
  $pdo = new PDO($rootDsn, $config['user'], $config['pass'], array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC));
  $dbName = str_replace('`','',$config['name']);
  $pdo->prepare("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")->execute();
  $pdo->prepare("USE `{$dbName}`")->execute();
  $schema=file_get_contents(__DIR__.'/schema.sql'); if($schema===false)throw new RuntimeException('Could not read database/schema.sql'); foreach(rdi_sql_statements($schema) as $statement)$pdo->prepare($statement)->execute();
  $appliedMigrations = rdi_run_migrations($pdo, __DIR__.'/migrations');
  try{$pdo->prepare("ALTER TABLE member_invites DROP INDEX uq_member_invite_email_status")->execute();}catch(Throwable $e){}

  /* Upgrade users/tasks created by older RDI builds without deleting data. */
  try{$pdo->prepare("ALTER TABLE users MODIFY role VARCHAR(64) NOT NULL DEFAULT 'researcher'")->execute();}catch(Throwable $e){}
  rdi_add_column($pdo,'users','rdi_member_id','VARCHAR(80) NULL UNIQUE AFTER id');
  rdi_add_column($pdo,'users','department','VARCHAR(160) NULL AFTER role');
  rdi_add_column($pdo,'users','team','VARCHAR(160) NULL AFTER department');
  rdi_add_column($pdo,'users','profile_image','TEXT NULL AFTER team');
  rdi_add_column($pdo,'users','status',"ENUM('Pending','Active','Deactivated','Locked','Deleted') NOT NULL DEFAULT 'Active' AFTER profile_image");
  rdi_add_column($pdo,'users','join_date','DATE NULL AFTER status');
  rdi_add_column($pdo,'users','deleted_at','DATETIME NULL AFTER join_date');
  rdi_add_column($pdo,'users','failed_login_count','INT NOT NULL DEFAULT 0 AFTER deleted_at');
  rdi_add_column($pdo,'users','locked_at','DATETIME NULL AFTER failed_login_count');
  rdi_add_column($pdo,'users','updated_at','TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
  rdi_add_column($pdo,'tasks','project_id','VARCHAR(64) NULL AFTER project');
  rdi_add_column($pdo,'tasks','assignee_user_id','VARCHAR(64) NULL AFTER assignee');
  rdi_add_column($pdo,'hr_documents','signed_by_name','VARCHAR(160) NULL AFTER content');
  rdi_add_column($pdo,'hr_documents','signed_at','DATETIME NULL AFTER signed_by_name');
  rdi_add_column($pdo,'hr_documents','signature_ip','VARCHAR(64) NULL AFTER signed_at');

  $roles=array(
    array('super_admin','Super Admin','Full platform administration and RBAC control.'),
    array('admin','Admin','RDI administration, user operations, content and task management.'),
    array('researcher','User / Researcher','Standard RDI member account for projects, tasks, HR requests and future AI Buddy access.')
  );
  $st=$pdo->prepare("INSERT INTO roles(slug,name,description,is_system) VALUES(?,?,?,1) ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description)");
  foreach($roles as $r)$st->execute($r);

  $permissions=array(
    'view_users'=>'View users','edit_users'=>'Edit user profiles','activate_users'=>'Activate/deactivate/unlock users','delete_users'=>'Soft delete/restore users',
    'assign_roles'=>'Assign roles','assign_permissions'=>'Assign user permissions','create_member_ids'=>'Create/revoke RDI Member IDs','manage_roles'=>'Manage role permission templates',
    'assign_projects'=>'Assign projects','view_projects'=>'View projects','manage_projects'=>'Manage projects','comment_projects'=>'Comment on projects','manage_tasks'=>'Create and manage tasks','update_own_tasks'=>'Update assigned task status','use_ai_buddy'=>'Use AI Buddy','manage_ai_buddy'=>'Manage AI Buddy',
    'view_audit_logs'=>'View audit logs','manage_content'=>'Manage gallery, blogs and homepage content','manage_research_news'=>'Manage research-news automation',
    'manage_hr_requests'=>'Review and approve HR document requests','request_hr_documents'=>'Request HR documents','manage_settings'=>'Manage automation/system settings'
  );
  $st=$pdo->prepare("INSERT INTO permissions(permission_key,name,description) VALUES(?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description)");
  foreach($permissions as $k=>$n)$st->execute(array($k,$n,$n));

  $pdo->prepare("DELETE FROM role_permissions WHERE role_slug IN ('super_admin','admin','researcher')")->execute();
  $ins=$pdo->prepare("INSERT INTO role_permissions(role_slug,permission_key) VALUES(?,?)");
  foreach(array_keys($permissions) as $p)$ins->execute(array('super_admin',$p));
  $adminPerms=array('view_users','edit_users','activate_users','delete_users','assign_roles','assign_permissions','create_member_ids','assign_projects','view_projects','manage_projects','comment_projects','manage_tasks','update_own_tasks','use_ai_buddy','manage_ai_buddy','view_audit_logs','manage_content','manage_research_news','manage_hr_requests','request_hr_documents','manage_settings');
  foreach($adminPerms as $p)$ins->execute(array('admin',$p));
  foreach(array('view_projects','comment_projects','update_own_tasks','use_ai_buddy','request_hr_documents') as $p)$ins->execute(array('researcher',$p));

  $seedAdminEmail=rdi_env('RDI_SEED_ADMIN_EMAIL');$seedAdminPass=rdi_env('RDI_SEED_ADMIN_PASSWORD');$seedResearcherEmail=rdi_env('RDI_SEED_RESEARCHER_EMAIL');$seedResearcherPass=rdi_env('RDI_SEED_RESEARCHER_PASSWORD');
  if(!$seedAdminEmail||!$seedAdminPass||!$seedResearcherEmail||!$seedResearcherPass)throw new RuntimeException('Seed account credentials are missing from .env.');
  $stmt=$pdo->prepare("INSERT INTO users(id,rdi_member_id,name,email,password_hash,role,department,team,status,join_date) VALUES(?,?,?,?,?,?,?,?, 'Active', CURDATE()) ON DUPLICATE KEY UPDATE rdi_member_id=VALUES(rdi_member_id), name=VALUES(name), email=VALUES(email), password_hash=VALUES(password_hash), role=VALUES(role), status='Active', deleted_at=NULL");
  $stmt->execute(array('u-admin','RDI-ADMIN-0001','RDI Super Admin',$seedAdminEmail,password_hash($seedAdminPass,PASSWORD_DEFAULT),'super_admin','Administration','Core'));
  $stmt->execute(array('u-researcher','RDI-RES-0001','RDI Researcher',$seedResearcherEmail,password_hash($seedResearcherPass,PASSWORD_DEFAULT),'researcher','Research','General'));

  $settings=array(
    'contactEmail'=>'info.rd.innovators@gmail.com','contactEndpoint'=>'','researchApiEndpoint'=>'','autoApproveNews'=>'1','demoAutomation'=>'0',
    'newsAutomationIntervalHours'=>'6','newsMaxItems'=>'5','newsCategories'=>'cs.AI,cs.LG,cs.CV,cs.CL,cs.RO','lastNewsAutomationAt'=>'','lastNewsAutomationStatus'=>'never',
    'lastNewsAutomationCount'=>'0','lastNewsAutomationMessage'=>'Not run yet.','lastNewsAutomationSource'=>'arXiv','rdiEmailFormat'=>'username.rdi@gmail.com','rdiEmailDomain'=>'gmail.com',
    'passwordResetMinutes'=>'30','siteName'=>'RDI Innovators'
  );
  $s=$pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=setting_value");
  foreach($settings as $k=>$v)$s->execute(array($k,$v));

  $pdo->prepare("INSERT IGNORE INTO projects(id,name,description,status) VALUES
    ('p-research-intelligence','Research Intelligence','Research monitoring and literature intelligence.','Active'),
    ('p-rdi-web','RDI Website','RDI web platform and internal systems.','Active'),
    ('p-engineering','Engineering Prototype','Applied engineering prototype validation.','Active')")->execute();
  $pdo->prepare("INSERT IGNORE INTO project_memberships(project_id,user_id,membership_role) VALUES ('p-research-intelligence','u-researcher','Researcher'),('p-rdi-web','u-admin','Project Lead')")->execute();
  $projectSeed=$pdo->prepare("UPDATE projects SET leader_user_id=?,start_date=?,end_date=? WHERE id=?");
  $projectSeed->execute(array('u-researcher','2026-09-01','2026-12-31','p-research-intelligence'));
  $projectSeed->execute(array('u-admin','2026-08-01','2026-12-31','p-rdi-web'));
  $projectSeed->execute(array('u-admin','2026-09-10',NULL,'p-engineering'));

  $pdo->prepare("INSERT IGNORE INTO gallery(id,title,category,image,description,item_date) VALUES
    ('g1','AI Research Workspace','Research','assets/DarkDesk.png','RDI collaborative research and prototyping workspace.','2026-09-20'),
    ('g2','RDI Innovation Identity','Update','assets/LightLogo.png','Brand and innovation update from RDI Innovators.','2026-09-18'),
    ('g3','Research & Development','Project','assets/DarkLogo.png','From ideas to experiments, prototypes and publications.','2026-09-15')")->execute();
  $pdo->prepare("INSERT IGNORE INTO blogs(id,title,slug,excerpt,body,image,status,publish_at,created_at) VALUES
    ('b1','How RDI Turns Research Ideas into Real-World Prototypes','rdi-research-to-prototype','A practical look at our research-to-prototype workflow and interdisciplinary collaboration.','RDI combines structured research, iterative prototyping, testing and documentation to turn promising ideas into useful outcomes.','assets/LightDesk.png','published','2026-09-24 09:00:00','2026-09-20 12:00:00'),
    ('b2','Building Stronger Research Teams Remotely','stronger-remote-research-teams','Processes that help distributed research teams stay aligned, accountable and productive.','Clear ownership, visible milestones, concise documentation and regular research reviews make remote collaboration more effective.','assets/DarkDesk.png','published','2026-09-22 10:00:00','2026-09-19 10:00:00')")->execute();
  $pdo->prepare("INSERT IGNORE INTO research_news(id,title,source,url,summary,category,approved,generated,published_at) VALUES
    ('n1','AI-assisted research workflows continue to expand across engineering teams','RDI Research Monitor','#','Research teams are increasingly combining domain expertise with AI-assisted literature analysis, coding and experiment planning.','Artificial Intelligence',1,1,'2026-09-26 09:00:00'),
    ('n2','Interdisciplinary prototyping remains central to applied R&D','RDI Research Monitor','#','Applied research groups are emphasizing rapid prototypes, validation and transparent reporting across engineering domains.','Engineering',1,1,'2026-09-25 12:30:00')")->execute();
  $pdo->prepare("INSERT IGNORE INTO home_sections(id,type,title,description,media,button_text,button_link,enabled,sort_order) VALUES
    ('s1','banner','Research. Build. Publish. Impact.','Collaborate with RDI on ambitious research and engineering projects.','assets/LightDesk.png','Member Sign Up','#signup',1,0),
    ('s2','carousel','Featured RDI Work','Selected projects, updates and research initiatives.','assets/DarkLogo.png,assets/LightLogo.png,assets/DarkDesk.png','Explore Gallery','#gallery',1,1)")->execute();
  $pdo->prepare("INSERT IGNORE INTO tasks(id,title,project,project_id,assignee,assignee_user_id,due_date,priority,status) VALUES
    ('t1','Review AI literature shortlist','Research Intelligence','p-research-intelligence','RDI Researcher','u-researcher','2026-10-02','High','In Progress'),
    ('t2','Prepare portfolio project update','RDI Website','p-rdi-web','RDI Super Admin','u-admin','2026-09-30','Medium','To Do'),
    ('t3','Validate prototype test notes','Engineering Prototype','p-engineering','RDI Researcher','u-researcher','2026-10-05','Medium','Review')")->execute();

  echo '<h2>RDI database installed / upgraded successfully.</h2>';
  echo '<p>This installer preserves existing content while adding the PDF-defined User Management, RBAC and HR tables.</p>';
  echo '<p>Phase migrations applied: <b>'.htmlspecialchars($appliedMigrations ? implode(', ', $appliedMigrations) : 'none pending').'</b></p>';
  echo '<p>Delete or rename <code>database/install.php</code> after installation.</p>';
  echo '<p>Seed accounts were created from <code>.env</code>. For security, credentials are not printed here.</p>';
} catch(Throwable $e){ http_response_code(500); echo '<h2>Installation failed</h2><pre>'.htmlspecialchars($e->getMessage()).'</pre>'; }
