CREATE TABLE IF NOT EXISTS roles (
  slug VARCHAR(64) PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
  permission_key VARCHAR(100) PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_slug VARCHAR(64) NOT NULL,
  permission_key VARCHAR(100) NOT NULL,
  PRIMARY KEY(role_slug, permission_key),
  INDEX(permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id VARCHAR(64) PRIMARY KEY,
  rdi_member_id VARCHAR(80) NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(64) NOT NULL DEFAULT 'researcher',
  department VARCHAR(160) NULL,
  team VARCHAR(160) NULL,
  profile_image TEXT NULL,
  status ENUM('Pending','Active','Deactivated','Locked','Deleted') NOT NULL DEFAULT 'Active',
  join_date DATE NULL,
  deleted_at DATETIME NULL,
  failed_login_count INT NOT NULL DEFAULT 0,
  locked_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX(role), INDEX(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_permissions (
  user_id VARCHAR(64) NOT NULL,
  permission_key VARCHAR(100) NOT NULL,
  allowed TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY(user_id, permission_key),
  INDEX(permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_invites (
  member_id VARCHAR(80) PRIMARY KEY,
  approved_email VARCHAR(190) NOT NULL,
  initial_role VARCHAR(64) NOT NULL DEFAULT 'researcher',
  department VARCHAR(160) NULL,
  team VARCHAR(160) NULL,
  status ENUM('Unused','Used','Revoked') NOT NULL DEFAULT 'Unused',
  created_by VARCHAR(64) NULL,
  used_by VARCHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  used_at DATETIME NULL,
  INDEX(approved_email),
  INDEX(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id VARCHAR(64) PRIMARY KEY,
  user_id VARCHAR(64) NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id), INDEX(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(220) NOT NULL,
  description TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_memberships (
  project_id VARCHAR(64) NOT NULL,
  user_id VARCHAR(64) NOT NULL,
  membership_role VARCHAR(100) NOT NULL DEFAULT 'Member',
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(project_id,user_id), INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery (
  id VARCHAR(64) PRIMARY KEY, title VARCHAR(220) NOT NULL, category VARCHAR(100) NOT NULL,
  image TEXT NOT NULL, description TEXT, item_date DATE NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blogs (
  id VARCHAR(64) PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255), excerpt TEXT, body LONGTEXT,
  image TEXT, status ENUM('draft','scheduled','published') DEFAULT 'draft', publish_at DATETIME NULL, created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS research_news (
  id VARCHAR(64) PRIMARY KEY, title VARCHAR(255) NOT NULL, source VARCHAR(200), url TEXT, summary TEXT,
  category VARCHAR(120), approved TINYINT(1) DEFAULT 0, generated TINYINT(1) DEFAULT 0, published_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS home_sections (
  id VARCHAR(64) PRIMARY KEY, type VARCHAR(40) NOT NULL, title VARCHAR(255), description TEXT, media LONGTEXT,
  button_text VARCHAR(120), button_link TEXT, enabled TINYINT(1) DEFAULT 1, sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tasks (
  id VARCHAR(64) PRIMARY KEY, title VARCHAR(255) NOT NULL, project VARCHAR(200), project_id VARCHAR(64) NULL,
  assignee VARCHAR(160), assignee_user_id VARCHAR(64) NULL,
  due_date DATE NULL, priority ENUM('Low','Medium','High') DEFAULT 'Medium',
  status ENUM('To Do','In Progress','Review','Done') DEFAULT 'To Do', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(assignee_user_id), INDEX(project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
  id VARCHAR(64) PRIMARY KEY, name VARCHAR(160) NOT NULL, email VARCHAR(190) NOT NULL, subject VARCHAR(255),
  message TEXT NOT NULL, email_status VARCHAR(30) DEFAULT 'stored', created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Kept only for backward compatibility with older RDI builds. New registration uses member_invites. */
CREATE TABLE IF NOT EXISTS join_applications (
  id VARCHAR(64) PRIMARY KEY, name VARCHAR(160) NOT NULL, email VARCHAR(190) NOT NULL, phone VARCHAR(80),
  desired_role VARCHAR(120), interests TEXT, resume TEXT, message TEXT, password_hash VARCHAR(255) NOT NULL,
  status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending', created_at DATETIME NOT NULL, reviewed_at DATETIME NULL,
  INDEX(email), INDEX(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hr_requests (
  id VARCHAR(64) PRIMARY KEY,
  user_id VARCHAR(64) NOT NULL,
  document_type ENUM('Salary Certificate','Payslip','Employment Certificate','Certificate','NOC','NDA','Joining Letter') NOT NULL,
  request_note TEXT NULL,
  status ENUM('Requested','Accepted','Under Review','Approved','Rejected') NOT NULL DEFAULT 'Requested',
  admin_note TEXT NULL,
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME NULL,
  approved_at DATETIME NULL,
  INDEX(user_id), INDEX(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hr_documents (
  id VARCHAR(64) PRIMARY KEY,
  request_id VARCHAR(64) NOT NULL UNIQUE,
  user_id VARCHAR(64) NOT NULL,
  document_type VARCHAR(80) NOT NULL,
  document_number VARCHAR(100) NOT NULL,
  content LONGTEXT NOT NULL,
  signed_by_name VARCHAR(160) NULL,
  signed_at DATETIME NULL,
  signature_ip VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  actor_user_id VARCHAR(64) NULL,
  action VARCHAR(120) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id VARCHAR(100) NULL,
  details TEXT NULL,
  ip_address VARCHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(actor_user_id), INDEX(action), INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) PRIMARY KEY, setting_value LONGTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
