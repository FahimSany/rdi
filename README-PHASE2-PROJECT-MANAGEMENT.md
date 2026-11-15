# RDI Phase 2 — Project Management

## Architecture
Phase 2 adds a permission-aware project module without replacing Phase 1 notifications.

Request flow:

`/api/v1/projects.php` → `ProjectController` → `ProjectService` → `ProjectRepository` → MySQL

The service layer owns project authorization and business rules. The repository owns prepared SQL. Project assignment notifications continue to use the single Phase 1 `NotificationService`.

## Migration
Run the normal migration runner after deploying this build:

```bat
cd C:\xampp\htdocs\rdi
C:\xampp\php\php.exe database\migrate.php
```

New migration:

- `database/migrations/002_phase2_project_management.php`

It adds:

- `projects.leader_user_id`
- `projects.start_date`
- `projects.end_date`
- `projects.updated_at`
- `project_files`
- `project_papers`
- `project_comments`
- `project_activity`
- permissions: `view_projects`, `manage_projects`, `comment_projects`

Existing project/task/content data is preserved.

## Features implemented

- Project name, description, leader, start/end dates and status.
- Permission-scoped project list: researchers only see projects they lead or belong to.
- Admins/project managers can create and edit projects.
- Project leaders can manage their own project workspace even without global `manage_projects`.
- Project members and membership roles.
- Project assignment notifications through `NotificationService`.
- Detail workspace tabs: Overview, Tasks, Files, Papers, Members, Discussion and Activity.
- Related tasks are read from the existing tasks table by `project_id`.
- Project file references and paper records are stored in MySQL.
- Threaded project comments/replies.
- Project activity history for create/update/member/file/paper/comment/task events.
- Important project actions are also recorded in the global audit log.
- Project UI is responsive for desktop, tablet and mobile.

### Phase 4 note
Phase 2 intentionally stores project file references/links so the project workspace can be complete now. Phase 4 will replace these references with real protected uploads stored outside the public web root, as required by the master plan.

## Files added

- `database/migrations/002_phase2_project_management.php`
- `app/Database/ProjectRepository.php`
- `app/Services/ProjectService.php`
- `app/Controllers/ProjectController.php`
- `api/v1/projects.php`
- `tests/phase2_project_service_test.php`
- `tests/phase2_static_checks.py`
- `README-PHASE2-PROJECT-MANAGEMENT.md`

## Main files changed

- `database/migration_lib.php`
- `database/install.php`
- `includes/state.php`
- `api/users.php`
- `api/state.php`
- `app/Services/NotificationService.php`
- `index.php`
- `js/script.js`
- `css/style.css`
- `tests/phase1_static_checks.py` (regression test updated because project assignment notification calls moved into `ProjectService`)

## Local test checklist

1. Back up your `rdi_portal` database.
2. Replace the Phase 1 code with this build but keep your real `.env`.
3. Start Apache + MySQL in XAMPP.
4. Run `C:\xampp\php\php.exe database\migrate.php`.
5. Sign out/in once so role permissions are refreshed.
6. Log in as Super Admin/Admin and open **Projects**.
7. Create a project with leader, dates and status.
8. Add at least two members. Confirm each newly assigned member receives a notification.
9. Add a file reference and a research paper.
10. Add a project discussion comment and reply.
11. Create/link a task to the project and confirm it appears in the project's Tasks tab.
12. Check the Activity tab for project/member/resource/comment/task actions.
13. Log in as an assigned researcher: confirm the project is visible.
14. Log in as a researcher who is not assigned: confirm the project is absent from the list and a direct `/api/v1/projects.php?id=...` call returns HTTP 403.
15. Test Projects at desktop width, ~768px tablet width and ~390px mobile width.
16. Re-test the Phase 1 notification bell, mark-read actions and deadline cron.

## Automated checks completed before packaging

- Phase 1 NotificationService regression tests: PASS
- Phase 1 static/security regression checks: PASS (26 checks)
- Phase 2 ProjectService permission tests: PASS
- Phase 2 static/security checks: PASS (22 checks)
- PHP syntax: PASS for 43 PHP files
- JavaScript syntax: PASS
- Duplicate HTML IDs: none
- Direct PDO `query()` / `exec()` calls: none

A live browser + MySQL integration test still needs your local XAMPP instance; the repository does not contain or connect to your local database during packaging.
