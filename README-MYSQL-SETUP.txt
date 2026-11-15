RDI Innovators — MySQL Full Project
===================================

STACK
- Frontend: HTML/CSS/Vanilla JavaScript
- Backend: PHP 8+
- Database: MySQL 5.7+/8.0 or MariaDB
- Main entry: index.php

DATABASE-BACKED FEATURES
- Secure login/session authentication
- Join Us applications
- Admin approve/reject member applications
- Automatic researcher account creation after approval
- RDI Task Management
- Gallery / projects / updates
- Blog posts and scheduled status
- Research news
- Homepage custom sections
- Contact Us messages
- Admin settings

SECURITY
- Passwords use PHP password_hash()/password_verify().
- Passwords are never returned to JavaScript.
- MySQL queries use PDO prepared statements.
- Admin-only application/message operations are protected server-side.
- PHP session cookies are HttpOnly + SameSite=Lax.
- Do NOT expose database credentials publicly.

QUICK INSTALL — XAMPP
1. Copy the project folder into C:/xampp/htdocs/rdi/
2. Start Apache and MySQL in XAMPP.
3. Edit config/database.php if your MySQL username/password differs.
   Defaults:
      host: localhost
      database: rdi_portal
      username: root
      password: (empty)
4. Open http://localhost/rdi/database/install.php once.
5. After successful installation, delete or rename database/install.php.
6. Open http://localhost/rdi/

QUICK INSTALL — cPanel/shared hosting
1. Create a MySQL database and MySQL user in cPanel.
2. Grant the user ALL privileges for that database.
3. Put the credentials in config/database.php, or configure the environment
   variables RDI_DB_HOST, RDI_DB_PORT, RDI_DB_NAME, RDI_DB_USER, RDI_DB_PASS.
4. Upload the full project to public_html (or a subfolder).
5. Visit /database/install.php once.
6. Delete or rename database/install.php immediately afterward.

DEFAULT ACCOUNTS CREATED BY INSTALLER
Admin:
  Email: admin@rdi.local
  Password: admin123

Researcher:
  Email: researcher@rdi.local
  Password: research123

IMPORTANT: Change/remove these seed accounts before production use.

DATABASE FILES
- database/schema.sql       Table definitions
- database/install.php      One-time installer + starter content
- config/database.php       Database connection configuration
- includes/db.php           PDO + session/API helpers
- includes/state.php        Database state mapping

API ENDPOINTS
- api/auth.php              Login/logout/session
- api/state.php             CMS/task state read/write
- api/join.php              Public Join Us submission
- api/contact.php           Public contact submission
- api/application.php       Admin application approval/rejection/deletion
- api/message.php           Admin contact-message deletion
- api/research-news.php     Live/manual research-news automation

NOTES
- Do not open index.php using file://. PHP must run through Apache/Nginx/PHP.
- Research-news automation is now server-side and works without an AI key.
  It imports current arXiv research, deduplicates it in MySQL, and can auto-approve it.
- Automatic outbound contact email delivery also requires an SMTP/email provider;
  Contact Us submissions themselves are now stored in MySQL.

ADMIN LOGIN REPAIR (XAMPP / localhost)
--------------------------------------
If admin@rdi.local / admin123 does not work:
1. Make sure Apache + MySQL are running.
2. Open: http://localhost/rdi/database/install.php
   - Re-running the installer now safely repairs the default admin/researcher password hashes without deleting existing project data.
3. Or open: http://localhost/rdi/database/repair-admin.php
   - Click "Repair Default Admin".
   - This repair page is restricted to localhost requests.
4. Login with:
   Email: admin@rdi.local
   Password: admin123
5. Delete or rename database/install.php and database/repair-admin.php after setup/repair.


RESEARCH NEWS AUTOMATION
------------------------
The Research News feature is now a real server-side automation.

Default behavior (NO API KEY REQUIRED):
- Source: live arXiv Atom API.
- Default categories: cs.AI, cs.LG, cs.CV, cs.CL, cs.RO.
- New papers are deduplicated using a deterministic arXiv ID in MySQL.
- Paper abstracts are used as factual summaries.
- Admin can click: Research News -> Run Automation Now.
- The website also calls the automation endpoint on page load. The server only
  performs a new import when the configured interval has elapsed.
- Default interval: 6 hours.
- Default maximum new items per run: 5.
- Auto approval can be switched on/off from Automation Settings.

PHP requirements for live import:
- PHP cURL is strongly recommended.
- PHP SimpleXML/XML extension must be enabled.
- The server must allow outbound HTTP/HTTPS connections to export.arxiv.org.

OPTIONAL AI SUMMARY ENRICHMENT
------------------------------
The live feed works without AI. If you want cleaner news-style headlines and
summaries, edit config/research-news.php or set server environment variables.
The API key stays on the PHP server and is never sent to browser JavaScript.

Gemini example (free-tier availability depends on your Google account/region):
  RDI_NEWS_AI_PROVIDER=gemini
  RDI_GEMINI_API_KEY=YOUR_KEY
  RDI_GEMINI_MODEL=gemini-3.7-flash

DeepSeek example:
  RDI_NEWS_AI_PROVIDER=deepseek
  RDI_DEEPSEEK_API_KEY=YOUR_KEY
  RDI_DEEPSEEK_MODEL=deepseek-flash

TRUE SCHEDULED RUNS / CRON
--------------------------
The page-load trigger runs when somebody visits the website. For automation even
when nobody visits, schedule this CLI command on the server:

  php /full/path/to/rdi/cron/research-news.php

A 1-hour cron schedule is fine. The PHP script itself respects the interval saved
in Automation Settings, so it will not import every hour unless the interval is 1.

On cPanel Cron Jobs, an example command is:
  /usr/local/bin/php /home/USERNAME/public_html/rdi/cron/research-news.php

On Windows/XAMPP you can run the same PHP script with Windows Task Scheduler using:
  C:\xampp\php\php.exe C:\xampp\htdocs\rdi\cron\research-news.php

TROUBLESHOOTING RESEARCH NEWS
-----------------------------
If Run Automation Now fails:
1. Confirm Apache + MySQL are running.
2. Confirm you are logged in as an admin.
3. In XAMPP/PHP make sure cURL and SimpleXML are available. `php -m` should
   list curl and SimpleXML. cURL may need to be enabled in php.ini; SimpleXML
   is commonly included by default in XAMPP.
4. Restart Apache after changing php.ini.
5. Make sure the machine/server has internet access.
6. Check the Dashboard -> Automation Status for the last error message.

RESEARCH NEWS TROUBLESHOOTING (v2)
----------------------------------
Admin Panel > Research News > Run Diagnostics checks:
- PHP version
- pdo_mysql
- cURL / allow_url_fopen
- SimpleXML (optional in this build; regex parser is a fallback)
- MySQL connection and research_news table
- arXiv connectivity
- Crossref connectivity

The automation first uses arXiv. If arXiv fails, it automatically uses Crossref.
On some Windows/XAMPP installations the PHP CA bundle is missing. This build has a
last-resort TLS fallback ONLY for public research metadata feeds. For production,
configure curl.cainfo and openssl.cafile with a current CA certificate bundle.

=== APACHE 403 / RESEARCH NEWS ===
This build contains two research-news routes:
  1) api/research.php
  2) index.php?rdi_api=research-news  (automatic fallback)

The root .htaccess no longer denies API files by basename. Sensitive folders
(config/ and includes/) protect themselves with their own .htaccess files.
If XAMPP still returns an Apache HTML 403 for /api/, the JavaScript automatically
uses the index.php fallback route instead.

If even index.php returns 403, check C:\\xampp\\apache\\conf\\httpd.conf and any
VirtualHost/Directory rule for the project folder. The htdocs Directory should
normally contain "Require all granted".
