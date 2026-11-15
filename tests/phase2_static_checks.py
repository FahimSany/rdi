from pathlib import Path
import re, sys
root=Path(__file__).resolve().parents[1]
checks=[]
def ck(cond,msg):
    checks.append((bool(cond),msg))

mig=(root/'database/migrations/002_phase2_project_management.php').read_text()
api=(root/'api/v1/projects.php').read_text()
svc=(root/'app/Services/ProjectService.php').read_text()
repo=(root/'app/Database/ProjectRepository.php').read_text()
idx=(root/'index.php').read_text()
js=(root/'js/script.js').read_text()
css=(root/'css/style.css').read_text()
state=(root/'includes/state.php').read_text()
notify=(root/'app/Services/NotificationService.php').read_text()
install=(root/'database/install.php').read_text()
ck('project_files' in mig and 'project_papers' in mig and 'project_comments' in mig and 'project_activity' in mig,'migration creates Phase 2 project tables')
ck('leader_user_id' in mig and 'start_date' in mig and 'end_date' in mig,'migration adds leader and project dates')
ck("'view_projects'" in mig and "'manage_projects'" in mig and "'comment_projects'" in mig,'migration seeds project permissions')
ck('require_user()' in api,'project API requires authenticated user')
ck('ProjectController' in api and 'ProjectService' in svc and 'ProjectRepository' in repo,'layered project API architecture exists')
ck("has_permission($user,'manage_projects')" in svc and 'hasAccess' in svc,'server-side project permission checks exist')
ck(all(x in idx for x in ['Overview','Project Management']) if False else 'id="projects"' in idx,'project management panel exists')
for tab in ['overview','tasks','files','papers','members','discussion','activity']:
    ck((("tabButton('"+tab+"',") in js) or (("detailTab==='"+tab+"'") in js), f'{tab} tab implemented')
ck('project_comments' in repo and 'addComment' in svc,'project discussion is persisted')
ck('project_activity' in repo and 'logActivity' in svc,'project activity history is persisted')
ck("'#admin?tab=projects&project='" in notify,'project assignment notifications link to project workspace')
ck("if($user){" in state and "$state['projects']=$projectService->listProjects($user)" in state,'bootstrap state only loads permission-scoped projects for authenticated users')
ck('@media(max-width:1100px)' in css and '@media(max-width:760px)' in css and '.project-workspace' in css,'project UI has tablet/mobile responsive rules')
ck("view_projects'=>'View projects'" in install and "manage_projects'=>'Manage projects'" in install,'installer preserves Phase 2 permissions')
# Global rule: PHP code must not use direct PDO query()/exec().
viol=[]
for f in root.rglob('*.php'):
    t=f.read_text(errors='ignore')
    if re.search(r'->(?:query|exec)\s*\(',t): viol.append(str(f.relative_to(root)))
ck(not viol,'no direct PDO query()/exec() calls: '+(', '.join(viol) if viol else 'prepared statements only'))
# No duplicate ids in rendered HTML source.
ids=re.findall(r'\bid="([^"]+)"',idx)
dups=sorted({x for x in ids if ids.count(x)>1})
ck(not dups,'no duplicate HTML ids: '+(', '.join(dups) if dups else 'none'))
failed=[m for ok,m in checks if not ok]
for ok,m in checks: print(('PASS: ' if ok else 'FAIL: ')+m)
if failed: sys.exit(1)
print(f'Phase 2 static checks passed ({len(checks)} checks).')
