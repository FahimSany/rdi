RDI Innovators — Single-file Frontend Structure
================================================

The website is now consolidated into exactly:
- index.html
- css/style.css
- js/script.js

All public and admin views share the same stylesheet and JavaScript bundle.

Hash routes:
- #home
- #gallery
- #blog
- #research-news
- #login
- #admin

Demo login:
Admin: admin@rdi.local / admin123
Researcher: researcher@rdi.local / research123

Notes:
- Content, users, tasks and admin data use browser localStorage/sessionStorage in this plain HTML/CSS/JS version.
- Real automatic email and DeepSeek/equivalent research-news automation should use a secure backend/API proxy. Do not expose private API keys in frontend JavaScript.
