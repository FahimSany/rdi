from pathlib import Path
import re, sys
root=Path(__file__).resolve().parents[1]
checks=[]
def ok(cond,msg):
    checks.append((bool(cond),msg))

migration=(root/'database/migrations/001_phase1_notifications.sql').read_text()
for col in ['user_id','type','title','message','link','is_read','created_at']:
    ok(re.search(r'\b'+re.escape(col)+r'\b',migration,re.I),f'migration contains {col}')
api=(root/'api/v1/notifications.php').read_text()
ok('require_user()' in api,'notifications API requires authenticated user')
ok((root/'.env.example').exists(),'.env.example exists')
ok('.env' in (root/'.gitignore').read_text(),'.env is gitignored')
raw=[]
for folder in ['api','includes','app','cron']:
    for f in (root/folder).rglob('*.php'):
        text=f.read_text(errors='ignore')
        if '->query(' in text or '->exec(' in text:
            raw.append(str(f.relative_to(root)))
ok(not raw,'runtime PHP uses prepared statements only; raw='+','.join(raw))
index=(root/'index.php').read_text()
ids=re.findall(r'\bid=["\']([^"\']+)["\']',index)
ok(len(ids)==len(set(ids)),'index has no duplicate HTML ids')
for needed in ['notificationBell','notificationUnreadBadge','notificationPageList','notificationStatusFilter','notificationTypeFilter']:
    ok(needed in ids,f'UI contains {needed}')
css=(root/'css/style.css').read_text()
ok('.notification-dropdown' in css and '@media(max-width:760px)' in css,'notification UI has mobile responsive rules')
js=(root/'js/script.js').read_text()
ok('api/v1/notifications.php' in js,'frontend uses /api/v1/ notifications endpoint')
for event in ['taskAssigned','hrDecision','memberIdCreated','accountStatusChanged','projectAssigned','researchUpdate','deadlineApproaching']:
    found=any(event in f.read_text(errors='ignore') for f in list((root/'api').rglob('*.php'))+list((root/'includes').rglob('*.php'))+list((root/'cron').rglob('*.php'))+list((root/'app').rglob('*.php')))
    ok(found,f'event hook present: {event}')
failed=[m for p,m in checks if not p]
for passed,msg in checks: print(('PASS' if passed else 'FAIL')+': '+msg)
if failed:
    sys.exit(1)
print(f'Phase 1 static checks passed: {len(checks)} checks.')
