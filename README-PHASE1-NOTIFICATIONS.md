# RDI Web App — Phase 1: Notifications

## Architecture
Phase 1 introduces a layered notification module:

`/api/v1/notifications.php` → `NotificationController` → `NotificationService` → `NotificationRepository` → PDO/MySQL

Existing modules do not insert notifications directly. They call `NotificationService`, which owns the event-to-notification behavior and deduplication keys.

Environment values are loaded from `.env` by `includes/env.php`. `.env` is ignored by Git and `.env.example` documents every expected variable.

## Migration

- `database/migrations/001_phase1_notifications.sql`

It creates `notifications` with user, type, title, message, link, read status, read time, created time and a per-user deduplication key.

Run on an existing database:

### XAMPP / Windows
```bat
C:\xampp\php\php.exe C:\xampp\htdocs\rdi\database\migrate.php
```

### Linux / cPanel shell
```bash
php /path/to/rdi/database/migrate.php
```

The normal installer also applies pending numbered migrations.

## Notification events implemented
- New task assigned
- Deadline approaching (scheduled job)
- HR document approved
- HR document rejected
- RDI Member ID created (admin notifications, because the invited member does not have an account yet)
- Account activated
- Account deactivated
- Project assignment
- New approved research update

## UI
- Bell in member portal header
- Unread badge
- Recent-notification dropdown
- Notifications page
- All / unread / read filter
- Type filter
- Mark one read
- Mark all read
- Responsive mobile/tablet layout

## API
### `GET /api/v1/notifications.php`
Query params: `status=all|unread|read`, `type=...`, `limit`, `offset`.
Only returns notifications for the authenticated user.

### `POST /api/v1/notifications.php`
```json
{"action":"mark_read","id":123}
```

```json
{"action":"mark_all_read"}
```

Both actions are scoped to the authenticated user and audited.

## Deadline reminder job
Run daily (or hourly; deduplication prevents duplicate reminders for the same task/due date/user):

```bash
php cron/deadline-reminders.php
```

Example Linux cron, 08:00 daily:
```cron
0 8 * * * /usr/bin/php /path/to/rdi/cron/deadline-reminders.php >> /path/to/rdi/storage/logs/deadlines.log 2>&1
```

For XAMPP use Windows Task Scheduler with:
- Program: `C:\xampp\php\php.exe`
- Argument: `C:\xampp\htdocs\rdi\cron\deadline-reminders.php`

## Phase 1 test checklist
1. Copy/verify `.env` and run `database/migrate.php`.
2. Log in as an active member; verify bell and unread count.
3. Open Notifications; verify status/type filters.
4. Mark one notification read; verify count decreases.
5. Mark all read; verify unread count becomes zero.
6. Admin creates a task assigned to another active user; log in as that user and verify `task_assigned`.
7. Admin assigns a new project; verify `project_assignment`.
8. Admin creates a Member ID; verify admins receive `member_id_created`.
9. Admin deactivates then activates a test account; after activation verify account-status notifications.
10. User requests an HR document; admin rejects/approves it; verify the user notification.
11. Approve/import a research update; verify active users receive `research_update`.
12. Create a task due within two days and run `php cron/deadline-reminders.php`; verify one deadline notification and no duplicate on a second run.
13. Test portal at desktop, tablet (~768px) and mobile (~390px); verify bell dropdown, filters and notification rows do not overflow.

## Automated checks included
```bash
php tests/phase1_notification_service_test.php
python tests/phase1_static_checks.py
node --check js/script.js
```

## Files added
- `.env.example`
- `.gitignore`
- `includes/env.php`
- `includes/notifications.php`
- `app/.htaccess`
- `app/Database/NotificationRepository.php`
- `app/Services/NotificationService.php`
- `app/Controllers/NotificationController.php`
- `api/v1/notifications.php`
- `database/migrations/001_phase1_notifications.sql`
- `database/migration_lib.php`
- `database/migrate.php`
- `cron/deadline-reminders.php`
- `tests/phase1_notification_service_test.php`
- `tests/phase1_static_checks.py`

## Existing files changed
- `config/database.php`
- `config/research-news.php`
- `includes/db.php`
- `includes/state.php`
- `includes/research_news.php`
- `api/state.php`
- `api/users.php`
- `api/member-ids.php`
- `api/hr.php`
- `api/register.php`
- `api/rbac.php`
- `api/research.php`
- `api/research-news.php`
- `cron/research-news.php`
- `database/install.php`
- `database/repair-admin.php`
- `index.php`
- `js/script.js`
- `css/style.css`

## Environment/deployment note
The archive intentionally does **not** ship a real `.env`. Copy `.env.example` to `.env`, fill the database values, and replace all seed credentials before running migrations/installers. `.gitignore` excludes `.env`, so database/SMTP/AI secrets stay outside version control.
