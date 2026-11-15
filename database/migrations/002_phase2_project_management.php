<?php
return function ($pdo) {
    $hasColumn=function($table,$column) use ($pdo){
        $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
        $st->execute(array($table,$column));
        return (bool)$st->fetchColumn();
    };
    $addColumn=function($table,$column,$definition) use ($pdo,$hasColumn){
        if(!$hasColumn($table,$column)){
            $sql="ALTER TABLE `".$table."` ADD COLUMN `".$column."` ".$definition;
            $pdo->prepare($sql)->execute();
        }
    };
    $addIndex=function($table,$index,$columns) use ($pdo){
        $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?");
        $st->execute(array($table,$index));
        if(!$st->fetchColumn())$pdo->prepare("ALTER TABLE `".$table."` ADD INDEX `".$index."` (".$columns.")")->execute();
    };

    $addColumn('projects','leader_user_id','VARCHAR(64) NULL AFTER description');
    $addColumn('projects','start_date','DATE NULL AFTER leader_user_id');
    $addColumn('projects','end_date','DATE NULL AFTER start_date');
    $addColumn('projects','updated_at','TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
    $addIndex('projects','idx_projects_leader','leader_user_id');
    $addIndex('projects','idx_projects_status','status');

    $pdo->prepare("CREATE TABLE IF NOT EXISTS project_files (
      id VARCHAR(64) PRIMARY KEY,
      project_id VARCHAR(64) NOT NULL,
      file_name VARCHAR(255) NOT NULL,
      file_url TEXT NULL,
      description TEXT NULL,
      added_by VARCHAR(64) NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_project_files_project (project_id),
      INDEX idx_project_files_added_by (added_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();

    $pdo->prepare("CREATE TABLE IF NOT EXISTS project_papers (
      id VARCHAR(64) PRIMARY KEY,
      project_id VARCHAR(64) NOT NULL,
      title VARCHAR(255) NOT NULL,
      authors TEXT NULL,
      venue VARCHAR(255) NULL,
      doi VARCHAR(190) NULL,
      paper_url TEXT NULL,
      notes TEXT NULL,
      added_by VARCHAR(64) NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_project_papers_project (project_id),
      INDEX idx_project_papers_doi (doi)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();

    $pdo->prepare("CREATE TABLE IF NOT EXISTS project_comments (
      id VARCHAR(64) PRIMARY KEY,
      project_id VARCHAR(64) NOT NULL,
      user_id VARCHAR(64) NOT NULL,
      parent_id VARCHAR(64) NULL,
      message TEXT NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NULL DEFAULT NULL,
      INDEX idx_project_comments_project (project_id, created_at),
      INDEX idx_project_comments_user (user_id),
      INDEX idx_project_comments_parent (parent_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();

    $pdo->prepare("CREATE TABLE IF NOT EXISTS project_activity (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      project_id VARCHAR(64) NOT NULL,
      actor_user_id VARCHAR(64) NULL,
      action VARCHAR(120) NOT NULL,
      details TEXT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_project_activity_project (project_id, created_at),
      INDEX idx_project_activity_actor (actor_user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();

    $perm=$pdo->prepare("INSERT INTO permissions(permission_key,name,description) VALUES(?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description)");
    $permissions=array(
      array('view_projects','View projects','View projects the user is permitted to access.'),
      array('manage_projects','Manage projects','Create and manage project details, resources, papers and members.'),
      array('comment_projects','Comment on projects','Participate in project discussion threads.')
    );
    foreach($permissions as $row)$perm->execute($row);
    $map=$pdo->prepare("INSERT IGNORE INTO role_permissions(role_slug,permission_key) VALUES(?,?)");
    foreach(array('view_projects','manage_projects','comment_projects') as $p){$map->execute(array('super_admin',$p));$map->execute(array('admin',$p));}
    foreach(array('view_projects','comment_projects') as $p)$map->execute(array('researcher',$p));
};
