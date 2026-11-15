RDI WEB APP — PDF FEATURE LIST IMPLEMENTATION
=============================================

This build updates the existing RDI MySQL project to follow the supplied "RDI Web App – Feature List".

IMPORTANT UPGRADE STEP
----------------------
1. Back up your current RDI project/database.
2. Replace the project files with this build.
3. Start Apache + MySQL.
4. Open once:
   http://localhost/rdi/database/install.php

The installer is upgrade-safe for the current RDI prototype: it creates/migrates the identity/RBAC/HR tables and preserves existing gallery/blog/news/task content.
5. After success, delete or rename database/install.php and database/repair-admin.php.
6. Hard refresh (Ctrl+F5).

DEFAULT LOCAL ACCOUNTS
----------------------
Super Admin
  Email: admin@rdi.local
  Password: admin123
  RDI ID: RDI-ADMIN-0001

Researcher
  Email: researcher@rdi.local
  Password: research123
  RDI ID: RDI-RES-0001

Change the passwords after first login.

CONTROLLED REGISTRATION
-----------------------
Public self-registration / old Join Applications are disabled.

Admin flow:
  Member Portal > RDI Member IDs > Generate Member ID
  - Approved email
  - Initial role
  - Department/team
  - Optional custom ID (otherwise auto-generated)

User flow:
  Website > Member Sign Up
  - Exact one-time RDI Member ID
  - Exact approved email
  - Full name
  - Password

The ID can be used only once. Admin can revoke/restore unused invitations.

RBAC / USER MANAGEMENT
----------------------
Roles:
  - Super Admin
  - Admin
  - User / Researcher

Role permissions are stored independently in roles/permissions/role_permissions.
Per-user grants/revocations are stored in user_permissions.
Backend APIs check permissions; UI hiding is only a convenience layer.

Account statuses:
  Pending, Active, Deactivated, Locked, Deleted

Deleted is a soft delete. Admins can restore users. Deactivated/Locked/Deleted accounts cannot login.
Five failed password attempts lock an active account until an admin reactivates/unlocks it.

User Management includes:
  - Search
  - Role/status filters
  - Profile editing
  - Role assignment
  - Per-user permission overrides
  - Project membership assignment
  - Activate/deactivate/lock
  - Soft delete/restore

PROFILE / PASSWORD RECOVERY
---------------------------
Profile stores:
  - RDI Member ID
  - Full name
  - Approved email
  - Role
  - Department/team
  - Profile image
  - Account status
  - Join date

RDI ID, role, permissions and account status remain admin controlled.

Members can change their own password.
Forgot Password creates a cryptographically random, single-use, hashed reset token with configurable expiry.
PHP mail() is attempted for delivery. On localhost, the UI also exposes the development reset link so the flow can be tested without SMTP.
For production, configure a real mail transport/SMTP layer.

PROJECT IDENTITY LINK
---------------------
projects and project_memberships are separate tables.
Tasks support project_id and assignee_user_id so the same user identity can be reused by Kanban/projects and future AI Buddy/workspace modules.
Researchers see their assigned tasks; task creation/all-task management is permission controlled.

HR HELP BOT / DOCUMENT REQUESTS
-------------------------------
Members can request:
  1. Salary Certificate
  2. Payslip
  3. Employment Certificate
  4. Certificate
  5. NOC
  6. NDA
  7. Joining Letter

Workflow:
  Requested > Accepted > Under Review > Approved
  or Rejected

Admin approval generates a printable document record. Known profile fields are prefilled. Salary, payroll, legal terms or other HR-only facts that are not stored are deliberately left as HR-review placeholders rather than fabricated.
Approved documents can be opened and printed / saved as PDF from the browser.
Approved NDA documents additionally support an internal typed-name acknowledgement/signature, which is stored with timestamp/IP for the prototype. For legally binding production e-signatures, connect a dedicated signature provider and legal workflow.

AUDIT LOGS
----------
Identity/admin activities such as login, profile changes, roles, statuses, permissions, Member IDs, project assignments and HR actions are logged in audit_logs and visible to authorized users.

RESEARCH NEWS
-------------
The existing arXiv/Crossref research-news automation remains included. Manual diagnostics/run require the manage_research_news permission. The Apache index.php fallback remains available.

PRODUCTION NOTES
----------------
- Use HTTPS.
- Change/remove default demo accounts/passwords.
- Delete installer/repair utilities after use.
- Configure SMTP for password-reset delivery.
- Consider CSRF tokens and stronger rate limiting before public deployment.
- Restrict DB credentials and API secrets outside the public web root where possible.
