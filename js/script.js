
/* ===== Shared theme bootstrap ===== */
(function(){
  var t=localStorage.getItem('rdi-theme');
  if(t){document.documentElement.setAttribute('data-theme',t);}
  else if(window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches){document.documentElement.setAttribute('data-theme','light');}
})();

async function rdiApiJson(response){
  var text=await response.text();
  var data=null;
  try{data=text?JSON.parse(text):{};}catch(e){
    var plain=String(text||'').replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim();
    throw new Error(plain ? ('Server returned non-JSON response: '+plain.slice(0,220)) : 'Server returned an empty response.');
  }
  if(!response.ok){throw new Error((data&&data.error)||('Request failed (HTTP '+response.status+')'));}
  return data||{};
}

(function (w) {
  'use strict';

  var KEY = 'rdi-cms-v1';
  var defaults = {
    users: [],
    gallery: [
      {id:'g1', title:'AI Research Workspace', category:'Research', image:'assets/DarkDesk.png', description:'RDI collaborative research and prototyping workspace.', date:'2026-09-20'},
      {id:'g2', title:'RDI Innovation Identity', category:'Update', image:'assets/LightLogo.png', description:'Brand and innovation update from RDI Innovators.', date:'2026-09-18'},
      {id:'g3', title:'Research & Development', category:'Project', image:'assets/DarkLogo.png', description:'From ideas to experiments, prototypes and publications.', date:'2026-09-15'}
    ],
    blogs: [
      {id:'b1', title:'How RDI Turns Research Ideas into Real-World Prototypes', slug:'rdi-research-to-prototype', excerpt:'A practical look at our research-to-prototype workflow and interdisciplinary collaboration.', body:'RDI combines structured research, iterative prototyping, testing and documentation to turn promising ideas into useful outcomes. Teams work across AI, cloud, IoT and engineering disciplines while keeping the research problem and measurable impact at the center.', image:'assets/LightDesk.png', status:'published', publishAt:'2026-09-24T09:00', createdAt:'2026-09-20T12:00'},
      {id:'b2', title:'Building Stronger Research Teams Remotely', slug:'stronger-remote-research-teams', excerpt:'Processes that help distributed research teams stay aligned, accountable and productive.', body:'Clear ownership, visible milestones, concise documentation and regular research reviews make remote collaboration more effective. RDI uses task-based coordination so contributors can see what matters next.', image:'assets/DarkDesk.png', status:'published', publishAt:'2026-09-22T10:00', createdAt:'2026-09-19T10:00'}
    ],
    news: [
      {id:'n1', title:'AI-assisted research workflows continue to expand across engineering teams', source:'RDI Research Monitor', url:'#', summary:'Research teams are increasingly combining domain expertise with AI-assisted literature analysis, coding and experiment planning.', category:'Artificial Intelligence', approved:true, generated:true, publishedAt:'2026-09-26T09:00'},
      {id:'n2', title:'Interdisciplinary prototyping remains central to applied R&D', source:'RDI Research Monitor', url:'#', summary:'Applied research groups are emphasizing rapid prototypes, validation and transparent reporting across engineering domains.', category:'Engineering', approved:true, generated:true, publishedAt:'2026-09-25T12:30'}
    ],
    homeSections: [
      {id:'s1', type:'banner', title:'Research. Build. Publish. Impact.', description:'Collaborate with RDI on ambitious research and engineering projects.', media:'assets/LightDesk.png', buttonText:'Start a Project', buttonLink:'#signup', enabled:true},
      {id:'s2', type:'carousel', title:'Featured RDI Work', description:'Selected projects, updates and research initiatives.', media:'assets/DarkLogo.png,assets/LightLogo.png,assets/DarkDesk.png', buttonText:'Explore Gallery', buttonLink:'#gallery', enabled:true}
    ],
    messages: [],
    applications: [],
    memberInvites: [],
    roles: [],
    permissions: [],
    rolePermissions: {},
    hrRequests: [],
    auditLogs: [],
    projects: [],
    currentUser: null,
    tasks: [
      {id:'t1', title:'Review AI literature shortlist', project:'Research Intelligence', assignee:'RDI Researcher', due:'2026-10-02', priority:'High', status:'In Progress'},
      {id:'t2', title:'Prepare portfolio project update', project:'RDI Website', assignee:'RDI Administrator', due:'2026-09-30', priority:'Medium', status:'To Do'},
      {id:'t3', title:'Validate prototype test notes', project:'Engineering Prototype', assignee:'RDI Researcher', due:'2026-10-05', priority:'Medium', status:'Review'}
    ],
    settings: {
      contactEmail:'info.rd.innovators@gmail.com',
      contactEndpoint:'',
      researchApiEndpoint:'',
      autoApproveNews:true,
      demoAutomation:false,
      newsAutomationIntervalHours:'6',
      newsMaxItems:'5',
      newsCategories:'cs.AI,cs.LG,cs.CV,cs.CL,cs.RO',
      lastNewsAutomationAt:'',
      lastNewsAutomationStatus:'never',
      lastNewsAutomationCount:'0',
      lastNewsAutomationMessage:'Not run yet.',
      lastNewsAutomationSource:'arXiv',
      siteName:'RDI Innovators',
      rdiEmailFormat:'username.rdi@gmail.com',
      rdiEmailDomain:'gmail.com',
      passwordResetMinutes:'30'
    }
  };
  function clone(o){ return JSON.parse(JSON.stringify(o)); }
  var state = Object.assign(clone(defaults), w.RDI_INITIAL_STATE || {}, {settings:Object.assign({}, defaults.settings, (w.RDI_INITIAL_STATE||{}).settings||{})});
  function load(){ return state; }
  function save(data){ state=data; fetch('api/state.php',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify(data),credentials:'same-origin'}).catch(function(e){console.error('Database sync failed',e)}); return data; }
  function get(){ publishScheduled(state); return state; }
  function update(fn){ fn(state); return save(state); }
  async function reload(){ var r=await fetch('api/state.php',{credentials:'same-origin'}); var fresh=await rdiApiJson(r);state=Object.assign(clone(defaults),fresh,{settings:Object.assign({},defaults.settings,fresh.settings||{})}); return state; }
  function id(prefix){ return prefix + Date.now().toString(36) + Math.random().toString(36).slice(2,6); }
  function publishScheduled(data){
    var changed=false, now=Date.now();
    (data.blogs||[]).forEach(function(p){ if(p.status==='scheduled' && p.publishAt && new Date(p.publishAt).getTime()<=now){p.status='published'; changed=true;} });
    if(changed) save(data);
  }
  function session(){
    if(w.RDI_CURRENT_USER && w.RDI_CURRENT_USER.id){
      return {id:w.RDI_CURRENT_USER.id,rdiMemberId:w.RDI_CURRENT_USER.rdi_member_id||w.RDI_CURRENT_USER.rdiMemberId||'',name:w.RDI_CURRENT_USER.name,email:w.RDI_CURRENT_USER.email,role:w.RDI_CURRENT_USER.role,status:w.RDI_CURRENT_USER.status||'Active',department:w.RDI_CURRENT_USER.department||'',team:w.RDI_CURRENT_USER.team||'',profileImage:w.RDI_CURRENT_USER.profile_image||w.RDI_CURRENT_USER.profileImage||'',permissions:w.RDI_CURRENT_USER.permissions||[],loginAt:new Date().toISOString()};
    }
    try{return JSON.parse(sessionStorage.getItem('rdi-session')||'null');}catch(e){return null;}
  }
  function setSession(user){
    w.RDI_CURRENT_USER=user;
    sessionStorage.setItem('rdi-session', JSON.stringify({id:user.id,rdiMemberId:user.rdi_member_id||user.rdiMemberId||'',name:user.name,email:user.email,role:user.role,status:user.status||'Active',department:user.department||'',team:user.team||'',profileImage:user.profile_image||user.profileImage||'',permissions:user.permissions||[],loginAt:new Date().toISOString()}));
  }
  function logout(){ sessionStorage.removeItem('rdi-session'); fetch('api/auth.php',{method:'DELETE',credentials:'same-origin'}).catch(function(){}); }
  function requireAuth(){ var s=session(); if(!s){ location.hash='login'; return null;} return s; }
  w.RDIStore={get:get,save:save,update:update,reload:reload,id:id,session:session,setSession:setSession,logout:logout,requireAuth:requireAuth,defaults:clone(defaults)};
}(window));


(function(){'use strict';
function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]})}
function fmt(d){try{return new Date(d).toLocaleDateString()}catch(e){return d||''}}
function renderGallery(){var el=document.getElementById('galleryGrid');if(!el)return;var d=RDIStore.get();el.innerHTML=d.gallery.length?d.gallery.map(function(x){return'<article class="public-card"><img src="'+esc(x.image)+'" alt="'+esc(x.title)+'"><div class="public-card__body"><div class="public-card__meta">'+esc(x.category)+' · '+esc(x.date)+'</div><h3>'+esc(x.title)+'</h3><p>'+esc(x.description)+'</p></div></article>'}).join(''):'<div class="empty-state">No gallery items yet.</div>'}
function renderBlogs(){var el=document.getElementById('blogList');if(!el)return;var d=RDIStore.get(),posts=d.blogs.filter(function(x){return x.status==='published'}).sort(function(a,b){return new Date(b.publishAt)-new Date(a.publishAt)});el.innerHTML=posts.length?posts.map(function(x){return'<article class="public-card"><img src="'+esc(x.image)+'" alt="'+esc(x.title)+'"><div class="public-card__body"><div class="public-card__meta">'+fmt(x.publishAt)+'</div><h2>'+esc(x.title)+'</h2><p>'+esc(x.excerpt||x.body.slice(0,180))+'</p><details><summary class="btn btn--outline" style="cursor:pointer">Read article</summary><p>'+esc(x.body)+'</p></details></div></article>'}).join(''):'<div class="empty-state">No published blogs yet.</div>'}
function renderNews(){var el=document.getElementById('newsList');if(!el)return;var d=RDIStore.get(),items=d.news.filter(function(x){return x.approved}).sort(function(a,b){return new Date(b.publishedAt)-new Date(a.publishedAt)});el.innerHTML=items.length?items.map(function(x){var link=x.url&&x.url!=='#'?'<a class="btn btn--outline" target="_blank" rel="noopener" href="'+esc(x.url)+'">Source</a>':'';return'<article class="news-item"><div class="public-card__meta">'+esc(x.category)+' · '+fmt(x.publishedAt)+'</div><h3>'+esc(x.title)+'</h3><p>'+esc(x.summary)+'</p><small>Source: '+esc(x.source)+'</small><br>'+link+'</article>'}).join(''):'<div class="empty-state">No approved research news yet.</div>'}
function renderHome(){var mount=document.getElementById('rdiCustomSections');if(!mount)return;var d=RDIStore.get(),secs=d.homeSections.filter(function(x){return x.enabled});mount.innerHTML=secs.map(function(s){var media='';if(s.type==='carousel'){var arr=(s.media||'').split(',').map(function(x){return x.trim()}).filter(Boolean);media='<div class="mini-carousel">'+arr.map(function(src,i){return'<img class="'+(i===0?'active':'')+'" src="'+esc(src)+'" alt="">'}).join('')+'</div>'}else if(s.type==='video'){media='<video controls muted loop playsinline src="'+esc(s.media)+'"></video>'}else if(s.media){media='<img src="'+esc(s.media)+'" alt="'+esc(s.title)+'">'}var btn=s.buttonText?'<a class="btn btn--primary" href="'+esc(s.buttonLink||'#')+'">'+esc(s.buttonText)+'</a>':'';return'<section class="rdi-custom-section"><div class="container"><div class="rdi-custom-card">'+media+'<div class="rdi-custom-copy"><span class="eyebrow">RDI UPDATE</span><h2>'+esc(s.title)+'</h2><p>'+esc(s.description)+'</p>'+btn+'</div></div></div></section>'}).join('');Array.from(mount.querySelectorAll('.mini-carousel')).forEach(function(c){var imgs=Array.from(c.querySelectorAll('img')),i=0;if(imgs.length>1)setInterval(function(){imgs[i].classList.remove('active');i=(i+1)%imgs.length;imgs[i].classList.add('active')},3500)})}
function bindContact(){var f=document.getElementById('rdiContactForm');if(!f)return;f.addEventListener('submit',async function(e){e.preventDefault();var fd=new FormData(f),payload={id:RDIStore.id('m'),name:fd.get('name'),email:fd.get('email'),subject:fd.get('subject'),message:fd.get('message')},msg=document.getElementById('contactFormStatus');try{var r=await fetch('api/contact.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});var j=await rdiApiJson(r);msg.textContent='Message submitted successfully.';msg.className='form-status success';f.reset()}catch(err){msg.textContent=err.message;msg.className='form-status error'}})}
window.RDI_RENDER_PUBLIC_NEWS=renderNews;renderGallery();renderBlogs();renderNews();renderHome();bindContact();
})();


/* ============================================================
   RDI Innovators — main.js
   Vanilla JS only. No dependencies.
   ============================================================ */

(function () {
  'use strict';

  function $(selector, context) {
    return (context || document).querySelector(selector);
  }
  function $$(selector, context) {
    return Array.from((context || document).querySelectorAll(selector));
  }

  /* ── Theme ────────────────────────────────────────────── */
  var themeToggle = document.getElementById('themeToggle');
  var htmlEl      = document.documentElement;

  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      var current = htmlEl.getAttribute('data-theme');
      var next    = current === 'light' ? 'dark' : 'light';
      htmlEl.setAttribute('data-theme', next);
      localStorage.setItem('rdi-theme', next);
      themeToggle.setAttribute('aria-label',
        next === 'light' ? 'Switch to dark mode' : 'Switch to light mode'
      );
    });
  }

  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', function (e) {
      if (!localStorage.getItem('rdi-theme')) {
        htmlEl.setAttribute('data-theme', e.matches ? 'light' : 'dark');
      }
    });
  }

  /* ── Footer year ──────────────────────────────────────── */
  var yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  /* ── Mobile nav ───────────────────────────────────────── */
  var navToggle = document.getElementById('navToggle');
  var mainNav   = document.getElementById('mainNav');

  function closeNav() {
    if (!mainNav) return;
    mainNav.classList.remove('is-open');
    if (navToggle) {
      navToggle.classList.remove('is-active');
      navToggle.setAttribute('aria-expanded', 'false');
    }
  }

  if (navToggle && mainNav) {
    navToggle.addEventListener('click', function () {
      var isOpen = mainNav.classList.toggle('is-open');
      navToggle.classList.toggle('is-active', isOpen);
      navToggle.setAttribute('aria-expanded', String(isOpen));
    });
    $$('a', mainNav).forEach(function (link) {
      link.addEventListener('click', closeNav);
    });
  }

  /* ── Header shadow on scroll ──────────────────────────── */
  var header = document.getElementById('header');
  if (header) {
    window.addEventListener('scroll', function () {
      header.classList.toggle('is-scrolled', window.scrollY > 10);
    }, { passive: true });
  }

  /* ── Active nav link on scroll ────────────────────────── */
  var sections = $$('section[id]');
  var navLinks = $$('.nav__link[href^="#"]');

  if (sections.length && navLinks.length && 'IntersectionObserver' in window) {
    var activeLinkObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var id = entry.target.getAttribute('id');
          navLinks.forEach(function (link) {
            link.classList.toggle('is-active', link.getAttribute('href') === '#' + id);
          });
        }
      });
    }, { threshold: 0.4 });
    sections.forEach(function (s) { activeLinkObserver.observe(s); });
  }

  /* ── Scroll-reveal animation ──────────────────────────── */
  var revealTargets = $$(
    '.about__copy, .domain-card, .service-card, .opp-card, ' +
    '.workflow__step, .stat, .experts__copy, .experts__visual, ' +
    '.join-card, .join__copy'
  );
  revealTargets.forEach(function (el) { el.classList.add('reveal'); });

  if ('IntersectionObserver' in window) {
    var revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    $$('.reveal').forEach(function (el) { revealObserver.observe(el); });
  } else {
    $$('.reveal').forEach(function (el) { el.classList.add('is-visible'); });
  }

  /* ── Copy email on click ──────────────────────────────── */
  var copyBtn = document.getElementById('copyEmailBtn');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var email = 'info.rd.innovators@gmail.com';
      var textEl = copyBtn.querySelector('.join__email-text');
      var original = textEl ? textEl.innerHTML : '';

      function flash(msg) {
        if (!textEl) return;
        textEl.textContent = msg;
        setTimeout(function () { textEl.innerHTML = original; }, 1800);
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(email).then(
          function () { flash('Email copied to clipboard!'); },
          function () { flash(email); }
        );
      } else {
        flash(email);
      }
    });
  }

}());



/* ===== Auth helpers / controlled registration ===== */
async function rdiPost(url,payload){
  var r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',cache:'no-store',body:JSON.stringify(payload||{})});
  return rdiApiJson(r);
}

(function(){
  var f=document.getElementById('signupForm');if(!f)return;
  f.addEventListener('submit',async function(e){e.preventDefault();var fd=new FormData(f),status=document.getElementById('signupStatus'),p=String(fd.get('password')||''),c=String(fd.get('confirmPassword')||'');
    if(p!==c){status.textContent='Passwords do not match.';return;}
    try{var j=await rdiPost('api/register.php',{memberId:String(fd.get('memberId')||'').trim(),email:String(fd.get('email')||'').trim().toLowerCase(),name:String(fd.get('name')||'').trim(),password:p});status.textContent=j.message||'Account created.';status.className='full form-status success';f.reset();setTimeout(function(){location.hash='login'},900);}catch(ex){status.textContent=ex.message;status.className='full form-status error';}
  });
})();

(function(){
  var f=document.getElementById('forgotForm');if(!f)return;
  f.addEventListener('submit',async function(e){e.preventDefault();var st=document.getElementById('forgotStatus');try{var j=await rdiPost('api/forgot-password.php',{email:document.getElementById('forgotEmail').value.trim().toLowerCase()});st.className='form-status success';st.innerHTML=(j.message||'Reset request created.')+(j.devResetUrl?'<br><a href="'+j.devResetUrl+'">Open local reset link</a>':'');}catch(ex){st.className='form-status error';st.textContent=ex.message;}});
})();

(function(){
  var f=document.getElementById('resetForm');if(!f)return;
  f.addEventListener('submit',async function(e){e.preventDefault();var st=document.getElementById('resetStatus'),p=document.getElementById('resetPassword').value,c=document.getElementById('resetConfirm').value,token=new URLSearchParams(location.search).get('reset_token')||'';if(p!==c){st.textContent='Passwords do not match.';return;}try{await rdiPost('api/reset-password.php',{token:token,password:p});st.className='form-status success';st.textContent='Password reset successfully. You can now log in.';history.replaceState({},'',location.pathname+'#login');setTimeout(function(){location.hash='login';location.reload()},900);}catch(ex){st.className='form-status error';st.textContent=ex.message;}});
})();

/* ===== Login page ===== */
(function(){
  var f=document.getElementById('loginForm');if(!f)return;
  f.addEventListener('submit',async function(e){
    e.preventDefault();var email=document.getElementById('email').value.trim().toLowerCase(),pass=document.getElementById('password').value,err=document.getElementById('loginError');
    try{sessionStorage.removeItem('rdi-session');err.textContent='';var j=await rdiPost('api/auth.php',{email:email,password:pass});if(!j.user||!j.user.id)throw new Error('Login succeeded but no account session was returned.');var check=await fetch('api/auth.php',{credentials:'same-origin',cache:'no-store'}),verified=await rdiApiJson(check);if(!verified.user||verified.user.id!==j.user.id)throw new Error('Login session could not be saved. Check PHP session/cookie settings.');RDIStore.setSession(verified.user);location.hash='admin';location.reload();}catch(ex){err.textContent=ex.message;}
  });
})();

/* ===== Research News transport with Apache fallback ===== */
async function rdiResearchRequest(kind){
  var suffix=kind==='diagnostics'?'?diagnostics=1':kind==='auto'?'?auto=1':'';
  var fallbackSuffix='?rdi_api=research-news'+(kind==='diagnostics'?'&diagnostics=1':kind==='auto'?'&auto=1':'');
  var urls=['api/research.php'+suffix,'index.php'+fallbackSuffix];
  var init=kind==='run'?{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',cache:'no-store',body:'{}'}:{credentials:'same-origin',cache:'no-store'};
  var errors=[];
  for(var i=0;i<urls.length;i++){
    try{var r=await fetch(urls[i],init),ct=(r.headers.get('content-type')||'').toLowerCase();if(ct.indexOf('application/json')===-1&&(r.status===403||r.status===404||r.status===405||r.status>=500)){var html=(await r.text()).replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim().slice(0,180);errors.push(urls[i]+' → HTTP '+r.status+(html?' ('+html+')':''));continue;}return await rdiApiJson(r);}catch(e){errors.push(urls[i]+' → '+e.message);}
  }
  throw new Error('Research API could not be reached. '+errors.join(' | '));
}

/* ===== Member/Admin portal ===== */
(function(){'use strict';
  if((location.hash||'').split('?')[0]!=='#admin'||!document.querySelector('.admin-shell'))return;
  var s=RDIStore.requireAuth();if(!s)return;
  function q(x){return document.querySelector(x)} function qa(x){return Array.from(document.querySelectorAll(x))}
  function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]})}
  function fmt(d){if(!d)return'—';try{return new Date(d).toLocaleString()}catch(e){return d}}
  function toast(t){var el=q('#toast');if(!el)return;el.textContent=t;el.classList.add('show');setTimeout(function(){el.classList.remove('show')},2200)}
  function can(p){return s.role==='super_admin'||(s.permissions||[]).indexOf(p)!==-1}
  function api(url,payload){return rdiPost(url,payload)}
  function reload(){return RDIStore.reload().then(function(){renderAll();if(window.RDI_REFRESH_NOTIFICATIONS)window.RDI_REFRESH_NOTIFICATIONS();return RDIStore.get()})}

  q('#userName').textContent=s.name;q('#userRole').textContent=String(s.role||'member').replace('_',' ').toUpperCase();
  qa('[class*="perm-"]').forEach(function(el){var need=null;el.className.split(/\s+/).forEach(function(c){if(c.indexOf('perm-')===0)need=c.slice(5).replace(/-/g,'_')});if(need&&!can(need))el.style.display='none'});
  q('#logoutBtn').onclick=function(){RDIStore.logout();location.hash='login';location.reload()};
  qa('#adminNav [data-tab]').forEach(function(b){b.onclick=function(){if(b.style.display==='none')return;qa('#adminNav button').forEach(function(x){x.classList.remove('active')});b.classList.add('active');qa('.admin-panel').forEach(function(x){x.classList.remove('active')});var panel=q('#'+b.dataset.tab);if(panel)panel.classList.add('active');q('#pageTitle').textContent=b.textContent.trim();renderAll()}});
  var initialTab='';try{initialTab=new URLSearchParams((location.hash.split('?')[1]||'')).get('tab')||''}catch(e){}if(initialTab){var initialBtn=q('#adminNav [data-tab="'+initialTab.replace(/[^a-z0-9_-]/gi,'')+'"]');if(initialBtn&&initialBtn.style.display!=='none')setTimeout(function(){initialBtn.click()},0)}

  function renderAll(){var d=RDIStore.get();renderDashboard(d);renderTasks(d);renderProfile(d);renderMyHr(d);if(can('view_users'))renderUsers(d);if(can('create_member_ids'))renderMemberIds(d);if(can('manage_roles'))renderRbac(d);if(can('view_audit_logs'))renderAudit(d);if(can('manage_hr_requests'))renderHrAdmin(d);if(can('manage_content')){renderGallery(d);renderBlogs(d);renderSections(d);renderMessages(d)}if(can('manage_research_news'))renderNews(d);if(can('manage_settings'))renderSettings(d)}
  window.RDI_RENDER_ADMIN=renderAll;

  function renderDashboard(d){
    var ownHr=(d.hrRequests||[]).filter(function(x){return x.userId===s.id});var cards=[['Open tasks',(d.tasks||[]).filter(function(x){return x.status!=='Done'}).length],['My HR requests',ownHr.length],['Role',String(s.role||'').replace('_',' ')],['Account',s.status||'Active']];if(can('view_users'))cards.push(['Active users',(d.users||[]).filter(function(x){return x.status==='Active'}).length]);if(can('create_member_ids'))cards.push(['Unused IDs',(d.memberInvites||[]).filter(function(x){return x.status==='Unused'}).length]);
    q('#stats').innerHTML=cards.map(function(x){return'<div class="stat-card"><strong>'+esc(x[1])+'</strong><span>'+esc(x[0])+'</span></div>'}).join('');
    var ns=d.settings.lastNewsAutomationStatus||'never',badge=ns==='success'?'ok':ns==='error'?'danger':'warn';q('#automationStatus').innerHTML='<p><span class="badge ok">ACCOUNT: '+esc((s.status||'Active').toUpperCase())+'</span></p><p>RDI ID: <b>'+esc(s.rdiMemberId||'—')+'</b></p><p>Department: <b>'+esc(s.department||'—')+'</b></p>'+(can('manage_research_news')?'<hr><p><span class="badge '+badge+'">RESEARCH NEWS: '+esc(ns.toUpperCase())+'</span></p><p>Last run: <b>'+fmt(d.settings.lastNewsAutomationAt)+'</b></p>':'');
    var all=(d.tasks||[]).slice(-3).reverse();q('#recentActivity').innerHTML=all.length?all.map(function(t){return'<p><b>'+esc(t.title)+'</b><br><small>'+esc(t.project)+' · '+esc(t.status)+'</small></p>'}).join(''):'<p>No task activity yet.</p>';
  }

  function taskForm(d){var users=(d.users||[]).filter(function(u){return u.status==='Active'}),projects=d.projects||[];var assignee=users.length?'<select name="assigneeUserId">'+users.map(function(u){return'<option value="'+esc(u.id)+'" '+(u.id===s.id?'selected':'')+'>'+esc(u.name)+' · '+esc(u.rdi_member_id||'')+'</option>'}).join('')+'</select>':'<input name="assignee" value="'+esc(s.name)+'">';return'<div class="cardx top-gap"><form id="taskForm" class="form-grid"><div class="field"><label>Task</label><input name="title" required></div><div class="field"><label>Project</label><select name="projectId">'+projects.map(function(p){return'<option value="'+esc(p.id)+'">'+esc(p.name)+'</option>'}).join('')+'</select></div><div class="field"><label>Assignee</label>'+assignee+'</div><div class="field"><label>Due date</label><input name="due" type="date"></div><div class="field"><label>Priority</label><select name="priority"><option>Low</option><option selected>Medium</option><option>High</option></select></div><div class="field"><label>Status</label><select name="status"><option>To Do</option><option>In Progress</option><option>Review</option><option>Done</option></select></div><div class="full"><button class="btnx primary">Save Task</button> <button class="btnx" type="button" onclick="this.closest(\'.cardx\').remove()">Cancel</button></div></form></div>'}
  if(q('#addTaskBtn'))q('#addTaskBtn').onclick=function(){if(!can('manage_tasks'))return;q('#taskFormWrap').innerHTML=taskForm(RDIStore.get());q('#taskForm').onsubmit=function(e){e.preventDefault();var fd=new FormData(e.target),d=RDIStore.get(),project=(d.projects||[]).find(function(p){return p.id===fd.get('projectId')}),assignee=(d.users||[]).find(function(u){return u.id===fd.get('assigneeUserId')});RDIStore.update(function(x){x.tasks.push({id:RDIStore.id('t'),title:fd.get('title'),project:project?project.name:'',projectId:project?project.id:null,assignee:assignee?assignee.name:(fd.get('assignee')||s.name),assigneeUserId:assignee?assignee.id:s.id,due:fd.get('due'),priority:fd.get('priority'),status:fd.get('status')})});q('#taskFormWrap').innerHTML='';renderAll();toast('Task added')}};
  function renderTasks(d){var sts=['To Do','In Progress','Review','Done'];q('#taskBoard').innerHTML=sts.map(function(st){var list=(d.tasks||[]).filter(function(t){return t.status===st});return'<div class="kan-col"><h4>'+st+' ('+list.length+')</h4>'+list.map(function(t){return'<div class="task-card"><b>'+esc(t.title)+'</b><small>'+esc(t.project)+'<br>'+esc(t.assignee)+' · Due '+esc(t.due||'—')+'<br>Priority: '+esc(t.priority)+'</small><select data-task-status="'+esc(t.id)+'">'+sts.map(function(x){return'<option '+(x===t.status?'selected':'')+'>'+x+'</option>'}).join('')+'</select></div>'}).join('')+'</div>'}).join('');qa('[data-task-status]').forEach(function(sel){sel.onchange=function(){var id=sel.dataset.taskStatus;RDIStore.update(function(x){var t=(x.tasks||[]).find(function(z){return z.id===id});if(t)t.status=sel.value});toast('Task status updated')}})}

  function renderProfile(d){var u=d.currentUser||s;q('#profileMemberId').value=u.rdi_member_id||s.rdiMemberId||'';q('#profileEmail').value=u.email||s.email||'';q('#profileName').value=u.name||s.name||'';q('#profileDepartment').value=u.department||s.department||'';q('#profileTeam').value=u.team||s.team||'';q('#profileImage').value=u.profile_image||s.profileImage||'';q('#profileRole').value=String(u.role||s.role||'').replace('_',' ');q('#profileStatus').value=u.status||s.status||'Active'}
  q('#profileForm').onsubmit=async function(e){e.preventDefault();try{await api('api/profile.php',{name:q('#profileName').value.trim(),department:q('#profileDepartment').value.trim(),team:q('#profileTeam').value.trim(),profileImage:q('#profileImage').value.trim()});var auth=await fetch('api/auth.php',{credentials:'same-origin',cache:'no-store'}),j=await rdiApiJson(auth);if(j.user){RDIStore.setSession(j.user);s=RDIStore.session()}await reload();toast('Profile updated')}catch(ex){toast(ex.message)}};
  q('#changePasswordForm').onsubmit=async function(e){e.preventDefault();var st=q('#passwordChangeStatus'),n=q('#newPassword').value,c=q('#newPasswordConfirm').value;if(n!==c){st.textContent='New passwords do not match.';return;}try{await api('api/password.php',{currentPassword:q('#currentPassword').value,newPassword:n});st.className='form-status success';st.textContent='Password changed successfully.';e.target.reset()}catch(ex){st.className='form-status error';st.textContent=ex.message}};

  q('#hrRequestForm').onsubmit=async function(e){e.preventDefault();try{await api('api/hr.php',{action:'create',documentType:q('#hrDocumentType').value,note:q('#hrRequestNote').value.trim()});q('#hrRequestNote').value='';await reload();toast('HR request sent')}catch(ex){toast(ex.message)}};
  function statusBadge(x){var c=x==='Approved'||x==='Active'||x==='Used'?'ok':x==='Rejected'||x==='Deleted'||x==='Revoked'||x==='Locked'?'danger':x==='Deactivated'?'warn':'blue';return'<span class="badge '+c+'">'+esc(x)+'</span>'}
  function renderMyHr(d){
    var rows=(d.hrRequests||[]).filter(function(x){return x.userId===s.id});
    q('#myHrRows').innerHTML=rows.length?rows.map(function(x){var doc='—';if(x.status==='Approved'){doc='<a class="btnx small" target="_blank" href="api/hr-document.php?request='+encodeURIComponent(x.id)+'">Open / Print</a>';if(x.documentType==='NDA'&&!x.signedAt)doc='<button class="btnx primary small" data-sign-nda="'+x.id+'">Sign NDA</button> '+doc;else if(x.documentType==='NDA'&&x.signedAt)doc='<span class="badge ok">SIGNED</span> '+doc;}return'<tr><td><b>'+esc(x.documentType)+'</b><br><small>'+esc(x.requestNote||'')+'</small></td><td>'+statusBadge(x.status)+'</td><td>'+fmt(x.requestedAt)+'</td><td>'+esc(x.adminNote||'—')+'</td><td>'+doc+'</td></tr>'}).join(''):'<tr><td colspan="5">No HR document requests yet.</td></tr>';
    qa('[data-sign-nda]').forEach(function(b){b.onclick=async function(){var sig=prompt('Type your full name to acknowledge/sign this approved NDA:',s.name)||'';if(!sig.trim())return;try{await api('api/hr.php',{action:'sign',id:b.dataset.signNda,signatureName:sig.trim()});await reload();toast('NDA signed and stored')}catch(ex){toast(ex.message)}}});
  }

  function userAction(id,action,extra){return api('api/users.php',Object.assign({id:id,action:action},extra||{})).then(reload).then(function(){toast('User updated')}).catch(function(e){toast(e.message)})}
  function renderUsers(d){var roles=d.roles||[],roleFilter=q('#userRoleFilter');if(roleFilter.options.length<=1)roles.forEach(function(r){var o=document.createElement('option');o.value=r.slug;o.textContent=r.name;roleFilter.appendChild(o)});var search=(q('#userSearch').value||'').toLowerCase(),rf=roleFilter.value,sf=q('#userStatusFilter').value;var users=(d.users||[]).filter(function(u){var hay=(u.name+' '+u.email+' '+(u.rdi_member_id||'')).toLowerCase();return(!search||hay.indexOf(search)!==-1)&&(!rf||u.role===rf)&&(!sf||u.status===sf)});q('#userRows').innerHTML=users.length?users.map(function(u){var roleOpts=roles.map(function(r){return'<option value="'+esc(r.slug)+'" '+(r.slug===u.role?'selected':'')+'>'+esc(r.name)+'</option>'}).join('');var statusOpts=['Pending','Active','Deactivated','Locked'].map(function(x){return'<option '+(x===u.status?'selected':'')+'>'+x+'</option>'}).join('');var deleted=u.status==='Deleted';return'<tr><td><b>'+esc(u.name)+'</b><br><small>'+esc(u.email)+'</small></td><td><b>'+esc(u.rdi_member_id||'—')+'</b><br><small>'+esc((u.department||'')+(u.team?' / '+u.team:''))+'</small></td><td>'+(can('assign_roles')&&!deleted?'<select data-user-role="'+u.id+'">'+roleOpts+'</select>':esc(u.role))+'</td><td>'+(can('activate_users')&&!deleted?'<select data-user-status="'+u.id+'">'+statusOpts+'</select>':statusBadge(u.status))+'</td><td><small>'+esc((u.permissions||[]).join(', ')).slice(0,120)+'</small><br>'+(can('assign_permissions')?'<button class="btnx small" data-user-perms="'+u.id+'">Permissions</button>':'')+'</td><td><button class="btnx small" data-user-edit="'+u.id+'">Edit</button> '+(deleted?'<button class="btnx small" data-user-restore="'+u.id+'">Restore</button>':'<button class="btnx danger small" data-user-delete="'+u.id+'">Soft Delete</button>')+'</td></tr>'}).join(''):'<tr><td colspan="6">No users match the filters.</td></tr>';
    qa('[data-user-role]').forEach(function(el){el.onchange=function(){userAction(el.dataset.userRole,'role',{role:el.value})}});qa('[data-user-status]').forEach(function(el){el.onchange=function(){userAction(el.dataset.userStatus,'status',{status:el.value})}});qa('[data-user-delete]').forEach(function(el){el.onclick=function(){if(confirm('Soft-delete this user? Historical data will be kept.'))userAction(el.dataset.userDelete,'delete')}});qa('[data-user-restore]').forEach(function(el){el.onclick=function(){userAction(el.dataset.userRestore,'restore')}});qa('[data-user-edit]').forEach(function(el){el.onclick=function(){openUserEditor(d,el.dataset.userEdit,false)}});qa('[data-user-perms]').forEach(function(el){el.onclick=function(){openUserEditor(d,el.dataset.userPerms,true)}})
  }
  q('#userSearch').oninput=function(){renderUsers(RDIStore.get())};q('#userRoleFilter').onchange=function(){renderUsers(RDIStore.get())};q('#userStatusFilter').onchange=function(){renderUsers(RDIStore.get())};
  function openUserEditor(d,id,permsMode){
    var u=(d.users||[]).find(function(x){return x.id===id});if(!u)return;var box=q('#userEditor');
    if(permsMode){
      var roleDefault=d.rolePermissions[u.role]||[],effective=u.permissions||[];
      box.innerHTML='<div class="cardx top-gap"><h3>Permission overrides — '+esc(u.name)+'</h3><p>Unchecked permissions can explicitly revoke a role permission; checked permissions can grant a permission directly.</p><form id="permEditor" class="permission-grid">'+(d.permissions||[]).map(function(p){return'<label><input type="checkbox" name="p" value="'+esc(p.permission_key)+'" '+(effective.indexOf(p.permission_key)!==-1?'checked':'')+'> <span><b>'+esc(p.name)+'</b><small>'+esc(p.permission_key)+(roleDefault.indexOf(p.permission_key)!==-1?' · role default':'')+'</small></span></label>'}).join('')+'<div class="full"><button class="btnx primary">Save Permissions</button></div></form></div>';
      q('#permEditor').onsubmit=async function(e){e.preventDefault();var selected=Array.from(e.target.querySelectorAll('input[name="p"]:checked')).map(function(x){return x.value}),over={};(d.permissions||[]).forEach(function(p){var def=roleDefault.indexOf(p.permission_key)!==-1,now=selected.indexOf(p.permission_key)!==-1;if(def!==now)over[p.permission_key]=now});try{await api('api/users.php',{id:id,action:'permissions',overrides:over});box.innerHTML='';await reload();toast('Permissions updated')}catch(ex){toast(ex.message)}};
      return;
    }
    var projectChecks=can('assign_projects')?'<div class="field full"><label>Project membership</label><div class="permission-grid compact-projects">'+(d.projects||[]).map(function(pr){return'<label><input type="checkbox" name="projectIds" value="'+esc(pr.id)+'" '+((u.projectIds||[]).indexOf(pr.id)!==-1?'checked':'')+'> <span><b>'+esc(pr.name)+'</b></span></label>'}).join('')+'</div></div>':'';
    box.innerHTML='<div class="cardx top-gap"><h3>Edit User — '+esc(u.name)+'</h3><form id="userEditForm" class="form-grid"><div class="field"><label>Name</label><input name="name" value="'+esc(u.name)+'" required></div><div class="field"><label>Approved email</label><input name="email" type="email" value="'+esc(u.email)+'" required></div><div class="field"><label>Department</label><input name="department" value="'+esc(u.department||'')+'"></div><div class="field"><label>Team</label><input name="team" value="'+esc(u.team||'')+'"></div><div class="field full"><label>Profile image</label><input name="profileImage" value="'+esc(u.profile_image||'')+'"></div>'+projectChecks+'<div class="full"><button class="btnx primary">Save User</button></div></form></div>';
    q('#userEditForm').onsubmit=async function(e){e.preventDefault();var f=new FormData(e.target);try{await api('api/users.php',{id:id,action:'update',name:f.get('name'),email:f.get('email'),department:f.get('department'),team:f.get('team'),profileImage:f.get('profileImage')});if(can('assign_projects'))await api('api/users.php',{id:id,action:'projects',projectIds:f.getAll('projectIds')});box.innerHTML='';await reload();toast('User saved')}catch(ex){toast(ex.message)}};
  }
  q('#memberIdForm').onsubmit=async function(e){e.preventDefault();try{var j=await api('api/member-ids.php',{action:'create',email:q('#inviteEmail').value.trim().toLowerCase(),role:q('#inviteRole').value,memberId:q('#inviteMemberId').value.trim(),department:q('#inviteDepartment').value.trim(),team:q('#inviteTeam').value.trim()});e.target.reset();await reload();toast('Member ID created: '+j.memberId)}catch(ex){toast(ex.message)}};
  function renderMemberIds(d){var roleSel=q('#inviteRole');roleSel.innerHTML=(d.roles||[]).filter(function(r){return r.slug!=='super_admin'||s.role==='super_admin'}).map(function(r){return'<option value="'+esc(r.slug)+'">'+esc(r.name)+'</option>'}).join('');q('#emailFormatHint').textContent=d.settings.rdiEmailFormat||'username.rdi@gmail.com';q('#memberIdRows').innerHTML=(d.memberInvites||[]).length?(d.memberInvites||[]).map(function(x){return'<tr><td><b>'+esc(x.member_id)+'</b></td><td>'+esc(x.approved_email)+'</td><td>'+esc(x.initial_role)+'<br><small>'+esc((x.department||'')+(x.team?' / '+x.team:''))+'</small></td><td>'+statusBadge(x.status)+'</td><td>'+fmt(x.created_at)+'</td><td>'+(x.status==='Unused'?'<button class="btnx danger small" data-invite-revoke="'+esc(x.member_id)+'">Revoke</button>':x.status==='Revoked'?'<button class="btnx small" data-invite-restore="'+esc(x.member_id)+'">Restore</button>':esc(x.used_by_name||'Used'))+'</td></tr>'}).join(''):'<tr><td colspan="6">No Member IDs issued yet.</td></tr>';qa('[data-invite-revoke]').forEach(function(b){b.onclick=function(){api('api/member-ids.php',{action:'revoke',memberId:b.dataset.inviteRevoke}).then(reload).catch(function(e){toast(e.message)})}});qa('[data-invite-restore]').forEach(function(b){b.onclick=function(){api('api/member-ids.php',{action:'restore',memberId:b.dataset.inviteRestore}).then(reload).catch(function(e){toast(e.message)})}})}

  function renderRbac(d){var perms=d.permissions||[];q('#rbacMatrix').innerHTML=(d.roles||[]).map(function(r){var selected=d.rolePermissions[r.slug]||[],readonly=r.slug==='super_admin';return'<div class="cardx top-gap"><div class="toolbar"><div><h3>'+esc(r.name)+'</h3><small>'+esc(r.description||'')+'</small></div>'+(readonly?'<span class="badge ok">ALL PERMISSIONS</span>':'<button class="btnx primary small" data-save-role="'+esc(r.slug)+'">Save Role</button>')+'</div><div class="permission-grid" data-role-box="'+esc(r.slug)+'">'+perms.map(function(p){return'<label><input type="checkbox" value="'+esc(p.permission_key)+'" '+(readonly||selected.indexOf(p.permission_key)!==-1?'checked':'')+' '+(readonly?'disabled':'')+'> <span><b>'+esc(p.name)+'</b><small>'+esc(p.permission_key)+'</small></span></label>'}).join('')+'</div></div>'}).join('');qa('[data-save-role]').forEach(function(b){b.onclick=async function(){var box=q('[data-role-box="'+b.dataset.saveRole+'"]'),arr=Array.from(box.querySelectorAll('input:checked')).map(function(x){return x.value});try{await api('api/rbac.php',{role:b.dataset.saveRole,permissions:arr});await reload();toast('Role permissions saved')}catch(ex){toast(ex.message)}}})}

  function renderAudit(d){q('#auditRows').innerHTML=(d.auditLogs||[]).length?(d.auditLogs||[]).map(function(x){return'<tr><td>'+fmt(x.created_at)+'</td><td>'+esc(x.actor_name||'System')+'</td><td><b>'+esc(x.action)+'</b></td><td>'+esc((x.entity_type||'')+(x.entity_id?' / '+x.entity_id:''))+'</td><td><small>'+esc(x.details||'')+'</small></td></tr>'}).join(''):'<tr><td colspan="5">No audit entries yet.</td></tr>'}

  function hrAdminAction(id,action){var note=prompt('Admin note (optional):','')||'';api('api/hr.php',{id:id,action:action,adminNote:note}).then(reload).then(function(){toast('HR request '+action+'d')}).catch(function(e){toast(e.message)})}
  function renderHrAdmin(d){var rows=d.hrRequests||[];q('#hrAdminCount').textContent=rows.filter(function(x){return x.status!=='Approved'&&x.status!=='Rejected'}).length+' open';q('#hrAdminRows').innerHTML=rows.length?rows.map(function(x){var actions=x.status==='Requested'?'<button class="btnx small" data-hr-accept="'+x.id+'">Accept</button>':x.status==='Accepted'?'<button class="btnx small" data-hr-review="'+x.id+'">Review</button>':x.status==='Under Review'?'<button class="btnx primary small" data-hr-approve="'+x.id+'">Approve</button>':'';if(x.status!=='Approved'&&x.status!=='Rejected')actions+=' <button class="btnx danger small" data-hr-reject="'+x.id+'">Reject</button>';if(x.status==='Approved')actions='<a class="btnx small" target="_blank" href="api/hr-document.php?request='+encodeURIComponent(x.id)+'">Open Document</a>';return'<tr><td><b>'+esc(x.name)+'</b><br><small>'+esc(x.memberId||'')+' · '+esc(x.email)+'</small></td><td><b>'+esc(x.documentType)+'</b><br><small>'+esc(x.requestNote||'')+'</small></td><td>'+statusBadge(x.status)+'</td><td>'+fmt(x.requestedAt)+'</td><td>'+esc(x.adminNote||'—')+'</td><td>'+actions+'</td></tr>'}).join(''):'<tr><td colspan="6">No HR requests.</td></tr>';qa('[data-hr-accept]').forEach(function(b){b.onclick=function(){hrAdminAction(b.dataset.hrAccept,'accept')}});qa('[data-hr-review]').forEach(function(b){b.onclick=function(){hrAdminAction(b.dataset.hrReview,'review')}});qa('[data-hr-approve]').forEach(function(b){b.onclick=function(){hrAdminAction(b.dataset.hrApprove,'approve')}});qa('[data-hr-reject]').forEach(function(b){b.onclick=function(){hrAdminAction(b.dataset.hrReject,'reject')}})}

  function formWrap(id,html,onSubmit){q(id).innerHTML='<div class="cardx top-gap">'+html+'</div>';var f=q(id+' form');f.onsubmit=onSubmit}
  if(q('#addGalleryBtn'))q('#addGalleryBtn').onclick=function(){formWrap('#galleryFormWrap','<form class="form-grid"><div class="field"><label>Title</label><input name="title" required></div><div class="field"><label>Category</label><input name="category" value="Project"></div><div class="field full"><label>Image path / URL</label><input name="image" placeholder="assets/project.jpg" required></div><div class="field full"><label>Description</label><textarea name="description"></textarea></div><div class="field"><label>Date</label><input name="date" type="date" value="'+new Date().toISOString().slice(0,10)+'"></div><div class="full"><button class="btnx primary">Add Item</button></div></form>',function(e){e.preventDefault();var f=new FormData(e.target);RDIStore.update(function(d){d.gallery.unshift({id:RDIStore.id('g'),title:f.get('title'),category:f.get('category'),image:f.get('image'),description:f.get('description'),date:f.get('date')})});q('#galleryFormWrap').innerHTML='';renderAll();toast('Gallery item added')})};
  function renderGallery(d){q('#galleryRows').innerHTML=d.gallery.map(function(x){return'<tr><td><img class="thumb" src="'+esc(x.image)+'"></td><td><b>'+esc(x.title)+'</b><br><small>'+esc(x.description).slice(0,70)+'</small></td><td>'+esc(x.category)+'</td><td>'+esc(x.date)+'</td><td><button class="btnx danger small" data-del-gallery="'+x.id+'">Delete</button></td></tr>'}).join('');qa('[data-del-gallery]').forEach(function(b){b.onclick=function(){RDIStore.update(function(x){x.gallery=x.gallery.filter(function(z){return z.id!==b.dataset.delGallery})});renderAll()}})}
  if(q('#addBlogBtn'))q('#addBlogBtn').onclick=function(){var now=new Date(Date.now()+3600000);formWrap('#blogFormWrap','<form class="form-grid"><div class="field full"><label>Title</label><input name="title" required></div><div class="field full"><label>Excerpt</label><textarea name="excerpt"></textarea></div><div class="field full"><label>Body</label><textarea name="body" required></textarea></div><div class="field"><label>Image</label><input name="image" value="assets/DarkDesk.png"></div><div class="field"><label>Status</label><select name="status"><option>draft</option><option>scheduled</option><option>published</option></select></div><div class="field"><label>Publish at</label><input name="publishAt" type="datetime-local" value="'+now.toISOString().slice(0,16)+'"></div><div class="full"><button class="btnx primary">Save Blog</button></div></form>',function(e){e.preventDefault();var f=new FormData(e.target),title=f.get('title');RDIStore.update(function(d){d.blogs.unshift({id:RDIStore.id('b'),title:title,slug:title.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''),excerpt:f.get('excerpt'),body:f.get('body'),image:f.get('image'),status:f.get('status'),publishAt:f.get('publishAt'),createdAt:new Date().toISOString()})});q('#blogFormWrap').innerHTML='';renderAll();toast('Blog saved')})};
  function renderBlogs(d){q('#blogRows').innerHTML=d.blogs.map(function(x){var cls=x.status==='published'?'ok':x.status==='scheduled'?'warn':'blue';return'<tr><td><b>'+esc(x.title)+'</b><br><small>'+esc(x.excerpt).slice(0,80)+'</small></td><td><span class="badge '+cls+'">'+esc(x.status)+'</span></td><td>'+fmt(x.publishAt)+'</td><td>'+fmt(x.createdAt)+'</td><td><button class="btnx danger small" data-del-blog="'+x.id+'">Delete</button></td></tr>'}).join('');qa('[data-del-blog]').forEach(function(b){b.onclick=function(){RDIStore.update(function(x){x.blogs=x.blogs.filter(function(z){return z.id!==b.dataset.delBlog})});renderAll()}})}

  if(q('#diagnoseNewsBtn'))q('#diagnoseNewsBtn').onclick=async function(){var btn=q('#diagnoseNewsBtn'),notice=q('#researchNewsNotice'),old=btn.textContent;btn.disabled=true;btn.textContent='Testing…';try{var j=await rdiResearchRequest('diagnostics'),d=j.diagnostics||{};notice.textContent=['PHP '+(d.phpVersion||'?'),'DB: '+(d.database||'?'),'cURL: '+(d.curl?'ON':'OFF'),'arXiv: '+(d.arxiv||'?'),'Crossref: '+(d.crossref||'?')].join(' | ')}catch(e){notice.textContent='Diagnostics failed: '+e.message}finally{btn.disabled=false;btn.textContent=old}};
  if(q('#generateNewsBtn'))q('#generateNewsBtn').onclick=async function(){var btn=q('#generateNewsBtn'),notice=q('#researchNewsNotice'),old=btn.textContent;btn.disabled=true;btn.textContent='Running…';try{var j=await rdiResearchRequest('run');await reload();notice.textContent=j.message||('Imported '+(j.imported||0)+' item(s).');toast(notice.textContent)}catch(e){notice.textContent='Automation failed: '+e.message;toast(e.message)}finally{btn.disabled=false;btn.textContent=old}};
  function renderNews(d){q('#newsRows').innerHTML=d.news.map(function(x){return'<tr><td><b>'+esc(x.title)+'</b><br><small>'+esc(x.summary).slice(0,95)+'</small></td><td>'+esc(x.category)+'</td><td>'+esc(x.source)+'</td><td>'+statusBadge(x.approved?'Approved':'Pending')+'</td><td><button class="btnx small" data-toggle-news="'+x.id+'">'+(x.approved?'Unapprove':'Approve')+'</button> <button class="btnx danger small" data-del-news="'+x.id+'">Delete</button></td></tr>'}).join('');qa('[data-toggle-news]').forEach(function(b){b.onclick=function(){RDIStore.update(function(x){var n=x.news.find(function(z){return z.id===b.dataset.toggleNews});if(n)n.approved=!n.approved});renderAll()}});qa('[data-del-news]').forEach(function(b){b.onclick=function(){RDIStore.update(function(x){x.news=x.news.filter(function(z){return z.id!==b.dataset.delNews})});renderAll()}})}
  if(q('#addSectionBtn'))q('#addSectionBtn').onclick=function(){formWrap('#sectionFormWrap','<form class="form-grid"><div class="field"><label>Section type</label><select name="type"><option>banner</option><option>image</option><option>video</option><option>carousel</option><option>text</option></select></div><div class="field"><label>Title</label><input name="title" required></div><div class="field full"><label>Description</label><textarea name="description"></textarea></div><div class="field full"><label>Media path(s)</label><input name="media"></div><div class="field"><label>Button text</label><input name="buttonText"></div><div class="field"><label>Button link</label><input name="buttonLink"></div><label class="switch full"><input type="checkbox" name="enabled" checked> Enabled</label><div class="full"><button class="btnx primary">Add Section</button></div></form>',function(e){e.preventDefault();var f=new FormData(e.target);RDIStore.update(function(d){d.homeSections.push({id:RDIStore.id('s'),type:f.get('type'),title:f.get('title'),description:f.get('description'),media:f.get('media'),buttonText:f.get('buttonText'),buttonLink:f.get('buttonLink'),enabled:!!f.get('enabled')})});q('#sectionFormWrap').innerHTML='';renderAll();toast('Homepage section added')})};
  function renderSections(d){q('#sectionRows').innerHTML=d.homeSections.map(function(x){return'<tr><td><span class="badge blue">'+esc(x.type)+'</span></td><td><b>'+esc(x.title)+'</b><br><small>'+esc(x.description).slice(0,75)+'</small></td><td>'+esc(x.media).slice(0,55)+'</td><td>'+(x.enabled?'Yes':'No')+'</td><td><button class="btnx small" data-toggle-section="'+x.id+'">Toggle</button> <button class="btnx danger small" data-del-section="'+x.id+'">Delete</button></td></tr>'}).join('');qa('[data-toggle-section]').forEach(function(b){b.onclick=function(){RDIStore.update(function(x){var z=x.homeSections.find(function(k){return k.id===b.dataset.toggleSection});if(z)z.enabled=!z.enabled});renderAll()}});qa('[data-del-section]').forEach(function(b){b.onclick=function(){RDIStore.update(function(x){x.homeSections=x.homeSections.filter(function(z){return z.id!==b.dataset.delSection})});renderAll()}})}
  function renderMessages(d){q('#messageCount').textContent=d.messages.length+' messages';q('#messageRows').innerHTML=d.messages.length?d.messages.map(function(x){return'<tr><td><b>'+esc(x.name)+'</b><br>'+esc(x.email)+'</td><td>'+esc(x.subject)+'</td><td>'+esc(x.message).slice(0,110)+'</td><td>'+statusBadge(x.emailStatus||'stored')+'</td><td>'+fmt(x.createdAt)+'</td><td><button class="btnx danger small" data-del-message="'+x.id+'">Delete</button></td></tr>'}).join(''):'<tr><td colspan="6">No messages yet.</td></tr>';qa('[data-del-message]').forEach(function(b){b.onclick=function(){rdiPost('api/message.php',{id:b.dataset.delMessage,action:'delete'}).then(reload).catch(function(e){toast(e.message)})}})}
  function renderSettings(d){q('#contactEmail').value=d.settings.contactEmail||'';q('#contactEndpoint').value=d.settings.contactEndpoint||'';q('#rdiEmailFormat').value=d.settings.rdiEmailFormat||'username.rdi@gmail.com';q('#rdiEmailDomain').value=d.settings.rdiEmailDomain||'gmail.com';q('#passwordResetMinutes').value=d.settings.passwordResetMinutes||'30';q('#newsCategories').value=d.settings.newsCategories||'cs.AI,cs.LG,cs.CV,cs.CL,cs.RO';q('#newsAutomationIntervalHours').value=d.settings.newsAutomationIntervalHours||'6';q('#newsMaxItems').value=d.settings.newsMaxItems||'5';q('#autoApproveNews').checked=!!d.settings.autoApproveNews}
  if(q('#settingsForm'))q('#settingsForm').onsubmit=function(e){e.preventDefault();RDIStore.update(function(d){d.settings.contactEmail=q('#contactEmail').value.trim();d.settings.contactEndpoint=q('#contactEndpoint').value.trim();d.settings.rdiEmailFormat=q('#rdiEmailFormat').value.trim();d.settings.rdiEmailDomain=q('#rdiEmailDomain').value.trim();d.settings.passwordResetMinutes=String(Math.max(10,Math.min(120,parseInt(q('#passwordResetMinutes').value||'30',10))));d.settings.newsCategories=q('#newsCategories').value.trim()||'cs.AI,cs.LG,cs.CV,cs.CL,cs.RO';d.settings.newsAutomationIntervalHours=String(Math.max(1,Math.min(168,parseInt(q('#newsAutomationIntervalHours').value||'6',10))));d.settings.newsMaxItems=String(Math.max(1,Math.min(10,parseInt(q('#newsMaxItems').value||'5',10))));d.settings.autoApproveNews=q('#autoApproveNews').checked});renderAll();toast('Settings saved')};

  renderAll();
})();

/* ===== Phase 1: Notification center ===== */
(function(){'use strict';
  if((location.hash||'').split('?')[0]!=='#admin'||!document.querySelector('#notificationBell'))return;
  var session=RDIStore.session();if(!session)return;
  var bell=document.getElementById('notificationBell'),drop=document.getElementById('notificationDropdown'),recent=document.getElementById('notificationRecentList'),badge=document.getElementById('notificationUnreadBadge'),navCount=document.getElementById('notificationNavCount'),pageList=document.getElementById('notificationPageList'),statusFilter=document.getElementById('notificationStatusFilter'),typeFilter=document.getElementById('notificationTypeFilter');
  function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]})}
  function when(v){if(!v)return'';var d=new Date(String(v).replace(' ','T'));if(isNaN(d.getTime()))return String(v);var diff=Math.floor((Date.now()-d.getTime())/1000);if(diff<60)return'just now';if(diff<3600)return Math.floor(diff/60)+'m ago';if(diff<86400)return Math.floor(diff/3600)+'h ago';if(diff<604800)return Math.floor(diff/86400)+'d ago';return d.toLocaleDateString()}
  function icon(type){var m={task_assigned:'✓',deadline_approaching:'⏱',hr_approved:'HR',hr_rejected:'HR',member_id_created:'ID',account_status:'●',project_assignment:'P',research_update:'R'};return m[type]||'•'}
  async function getNotifications(opts){opts=opts||{};var qs=new URLSearchParams();qs.set('limit',String(opts.limit||30));if(opts.status)qs.set('status',opts.status);if(opts.type)qs.set('type',opts.type);var r=await fetch('api/v1/notifications.php?'+qs.toString(),{credentials:'same-origin',cache:'no-store'});return rdiApiJson(r)}
  async function action(payload){return rdiPost('api/v1/notifications.php',payload)}
  function updateCount(n){n=parseInt(n||0,10);badge.textContent=n>99?'99+':String(n);badge.hidden=n<1;navCount.textContent=n>99?'99+':String(n);navCount.hidden=n<1;bell.setAttribute('aria-label',n?('Notifications, '+n+' unread'):'Notifications')}
  function recentHtml(items){if(!items.length)return'<div class="notification-empty">No notifications yet.</div>';return items.map(function(n){return'<a class="notification-item '+(!Number(n.is_read)?'is-unread':'')+'" href="'+esc(n.link||'#admin?tab=notifications')+'" data-notification-open="'+esc(n.id)+'"><span class="notification-item__dot"></span><span class="notification-item__body"><b>'+esc(n.title)+'</b><p>'+esc(n.message)+'</p><time>'+esc(when(n.created_at))+'</time></span></a>'}).join('')}
  function pageHtml(items){if(!items.length)return'<div class="notification-empty cardx">No notifications match these filters.</div>';return items.map(function(n){var unread=!Number(n.is_read);return'<article class="notification-row '+(unread?'is-unread':'')+'"><div class="notification-row__main"><span class="notification-type-icon">'+esc(icon(n.type))+'</span><div><h3>'+esc(n.title)+'</h3><p>'+esc(n.message)+'</p><div class="notification-row__meta"><span class="badge blue">'+esc(String(n.type||'notification').replace(/_/g,' '))+'</span><small>'+esc(when(n.created_at))+'</small></div></div></div><div class="notification-row__actions">'+(unread?'<button class="btnx small" data-notification-read="'+esc(n.id)+'">Mark read</button>':'<span class="badge ok">Read</span>')+(n.link?'<a class="btnx small" data-notification-link="'+esc(n.id)+'" href="'+esc(n.link)+'">Open</a>':'')+'</div></article>'}).join('')}
  async function loadRecent(){try{var j=await getNotifications({limit:8,status:'all'});updateCount(j.unreadCount);recent.innerHTML=recentHtml(j.items||[]);bindRecent()}catch(e){recent.innerHTML='<div class="notification-empty">'+esc(e.message)+'</div>'}}
  async function loadPage(){if(!pageList)return;pageList.innerHTML='<div class="notification-empty">Loading…</div>';try{var j=await getNotifications({limit:100,status:statusFilter?statusFilter.value:'all',type:typeFilter?typeFilter.value:''});updateCount(j.unreadCount);pageList.innerHTML=pageHtml(j.items||[]);bindPage()}catch(e){pageList.innerHTML='<div class="notification-empty cardx">'+esc(e.message)+'</div>'}}
  function bindRecent(){Array.from(recent.querySelectorAll('[data-notification-open]')).forEach(function(a){a.onclick=async function(e){e.preventDefault();var href=a.getAttribute('href')||'#admin?tab=notifications';try{await action({action:'mark_read',id:a.dataset.notificationOpen})}catch(x){}location.hash=href.replace(/^#/,'');}})}
  function bindPage(){Array.from(pageList.querySelectorAll('[data-notification-read]')).forEach(function(b){b.onclick=async function(){try{await action({action:'mark_read',id:b.dataset.notificationRead});await loadPage();await loadRecent()}catch(e){alert(e.message)}}});Array.from(pageList.querySelectorAll('[data-notification-link]')).forEach(function(a){a.onclick=async function(e){e.preventDefault();var href=a.getAttribute('href');try{await action({action:'mark_read',id:a.dataset.notificationLink})}catch(x){}location.hash=href.replace(/^#/,'')}})}
  async function markAll(){try{await action({action:'mark_all_read'});await loadRecent();await loadPage()}catch(e){alert(e.message)}}
  bell.onclick=function(e){e.stopPropagation();var show=drop.hidden;drop.hidden=!show;bell.setAttribute('aria-expanded',show?'true':'false');if(show)loadRecent()};
  document.addEventListener('click',function(e){if(!drop.hidden&&!drop.contains(e.target)&&e.target!==bell){drop.hidden=true;bell.setAttribute('aria-expanded','false')}});
  var topAll=document.getElementById('notificationMarkAllTop'),pageAll=document.getElementById('notificationMarkAllBtn'),refresh=document.getElementById('notificationRefreshBtn'),viewAll=document.getElementById('notificationViewAll');
  var navBtn=document.querySelector('#adminNav [data-tab="notifications"]');if(navBtn)navBtn.addEventListener('click',loadPage);if(topAll)topAll.onclick=markAll;if(pageAll)pageAll.onclick=markAll;if(refresh)refresh.onclick=function(){loadPage();loadRecent()};if(viewAll)viewAll.onclick=function(){drop.hidden=true;var b=document.querySelector('#adminNav [data-tab="notifications"]');if(b)b.click()};if(statusFilter)statusFilter.onchange=loadPage;if(typeFilter)typeFilter.onchange=loadPage;
  window.RDI_REFRESH_NOTIFICATIONS=function(){loadRecent();if(document.querySelector('#notifications.active'))loadPage()};loadRecent();if(document.querySelector('#notifications.active'))loadPage();setInterval(loadRecent,60000);
})();

/* ===== Live research-news automatic trigger ===== */
(function(){
  var ss=RDIStore.session();if(!ss||!(ss.role==='super_admin'||(ss.permissions||[]).indexOf('manage_research_news')!==-1))return;
  setTimeout(async function(){try{var j=await rdiResearchRequest('auto');if(j.ran){await RDIStore.reload();if(window.RDI_RENDER_PUBLIC_NEWS)window.RDI_RENDER_PUBLIC_NEWS();if(window.RDI_RENDER_ADMIN)window.RDI_RENDER_ADMIN();}}catch(e){console.warn('Research news auto-run:',e.message)}},1200);
})();

/* ===== Single-page view router ===== */
(function(){
  var allowed=['home','gallery','blog','research-news','signup','forgot','reset','login','admin'];
  function route(){var raw=(location.hash||'#home').slice(1),name=raw.split('?')[0];if(allowed.indexOf(name)===-1)name='home';if(name==='admin'&&!RDIStore.session()){location.hash='login';return;}document.querySelectorAll('.app-view').forEach(function(v){v.hidden=v.getAttribute('data-view')!==name});document.body.classList.toggle('admin-body',['admin','login','signup','forgot','reset'].indexOf(name)!==-1);document.title={home:'RDI Innovators | Making Ideas Matter',gallery:'RDI Gallery',blog:'RDI Blog','research-news':'RDI Research News',signup:'RDI Member Sign Up',forgot:'RDI Password Recovery',reset:'RDI Reset Password',login:'RDI Login',admin:'RDI Member Portal'}[name]||'RDI Innovators';window.scrollTo(0,0)}
  window.addEventListener('hashchange',function(){location.reload()});route();
})();

/* ===== Phase 2: Project Management ===== */
(function(){'use strict';
  if((location.hash||'').split('?')[0]!=='#admin'||!document.getElementById('projects'))return;
  var session=RDIStore.session();if(!session)return;
  function can(p){return session.role==='super_admin'||(session.permissions||[]).indexOf(p)!==-1}
  if(!can('view_projects')&&!can('manage_projects'))return;
  var listEl=document.getElementById('projectList'),detailEl=document.getElementById('projectDetail'),searchEl=document.getElementById('projectSearch'),statusEl=document.getElementById('projectStatusFilter'),formWrap=document.getElementById('projectFormWrap');
  var projects=[],availableUsers=[],currentProject=null,currentUsers=[],detailTab='overview';
  function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]})}
  function fmtDate(v){if(!v)return'—';var d=new Date(v.length===10?v+'T00:00:00':v);return isNaN(d.getTime())?v:d.toLocaleDateString()}
  function fmtDateTime(v){if(!v)return'—';var d=new Date(String(v).replace(' ','T'));return isNaN(d.getTime())?v:d.toLocaleString()}
  function statusClass(v){return v==='Active'?'ok':v==='Completed'?'blue':v==='Cancelled'?'danger':'warn'}
  function projectApiGet(id){var url='api/v1/projects.php'+(id?'?id='+encodeURIComponent(id):'');return fetch(url,{credentials:'same-origin',cache:'no-store'}).then(rdiApiJson)}
  function post(payload){return rdiPost('api/v1/projects.php',payload)}
  function toast(t){var el=document.getElementById('toast');if(!el)return;el.textContent=t;el.classList.add('show');setTimeout(function(){el.classList.remove('show')},2300)}
  function parseHashProject(){try{return new URLSearchParams((location.hash.split('?')[1]||'')).get('project')||''}catch(e){return''}}
  async function loadProjects(openId){
    listEl.innerHTML='<div class="notification-empty">Loading projects…</div>';
    try{var j=await projectApiGet();projects=j.projects||[];availableUsers=j.availableUsers||[];renderList();var id=openId||parseHashProject();if(id&&projects.some(function(p){return p.id===id}))await openProject(id);else if(currentProject&&projects.some(function(p){return p.id===currentProject.id}))await openProject(currentProject.id);else if(projects.length)await openProject(projects[0].id);else detailEl.innerHTML='<div class="empty-state cardx">No projects are available to your account.</div>';}
    catch(e){listEl.innerHTML='<div class="notification-empty">'+esc(e.message)+'</div>';detailEl.innerHTML='<div class="empty-state cardx">Project workspace could not be loaded.</div>';}
  }
  function renderList(){var q=(searchEl.value||'').trim().toLowerCase(),st=statusEl.value||'',items=projects.filter(function(p){return(!q||(p.name+' '+(p.description||'')+' '+(p.leader_name||'')).toLowerCase().indexOf(q)!==-1)&&(!st||p.status===st)});listEl.innerHTML=items.length?items.map(function(p){var active=currentProject&&currentProject.id===p.id?' is-active':'';return'<button class="project-list-item'+active+'" type="button" data-project-open="'+esc(p.id)+'"><span class="project-list-item__top"><b>'+esc(p.name)+'</b><span class="badge '+statusClass(p.status)+'">'+esc(p.status)+'</span></span><small>'+esc(p.leader_name||'No leader')+'</small><span class="project-list-item__meta"><span>'+esc(p.member_count||0)+' members</span><span>'+esc(p.task_count||0)+' tasks</span></span></button>'}).join(''):'<div class="notification-empty">No projects match the filters.</div>';Array.from(listEl.querySelectorAll('[data-project-open]')).forEach(function(b){b.onclick=function(){openProject(b.dataset.projectOpen)}})}
  async function openProject(id){detailEl.innerHTML='<div class="notification-empty cardx">Loading project…</div>';try{var j=await projectApiGet(id);currentProject=j.project;currentUsers=j.availableUsers||[];renderList();renderDetail();}catch(e){detailEl.innerHTML='<div class="empty-state cardx">'+esc(e.message)+'</div>';}}
  function tabButton(key,label){return'<button type="button" class="project-tab '+(detailTab===key?'is-active':'')+'" data-project-tab="'+key+'">'+label+'</button>'}
  function renderDetail(){var p=currentProject;if(!p)return;var manage=!!p.canManage;detailEl.innerHTML='<div class="project-detail-head"><div><div class="project-detail-title"><h2>'+esc(p.name)+'</h2><span class="badge '+statusClass(p.status)+'">'+esc(p.status)+'</span></div><p>'+esc(p.description||'No description yet.')+'</p></div>'+(manage?'<button class="btnx small" type="button" data-project-edit>Edit Project</button>':'')+'</div><div class="project-tabs" role="tablist">'+tabButton('overview','Overview')+tabButton('tasks','Tasks')+tabButton('files','Files')+tabButton('papers','Papers')+tabButton('members','Members')+tabButton('discussion','Discussion')+tabButton('activity','Activity')+'</div><div class="project-tab-content">'+tabContent(p,manage)+'</div>';bindDetailActions();}
  function tabContent(p,manage){
    if(detailTab==='overview')return overviewHtml(p);
    if(detailTab==='tasks')return tasksHtml(p);
    if(detailTab==='files')return filesHtml(p,manage);
    if(detailTab==='papers')return papersHtml(p,manage);
    if(detailTab==='members')return membersHtml(p,manage);
    if(detailTab==='discussion')return discussionHtml(p);
    return activityHtml(p);
  }
  function overviewHtml(p){return'<div class="project-overview-grid"><div class="cardx project-metric"><small>Leader</small><strong>'+esc(p.leader_name||'Unassigned')+'</strong></div><div class="cardx project-metric"><small>Timeline</small><strong>'+esc(fmtDate(p.start_date))+' → '+esc(fmtDate(p.end_date))+'</strong></div><div class="cardx project-metric"><small>Members</small><strong>'+esc((p.members||[]).length)+'</strong></div><div class="cardx project-metric"><small>Related tasks</small><strong>'+esc((p.tasks||[]).length)+'</strong></div></div><div class="cardx top-gap"><h3>Project Description</h3><p class="project-description">'+esc(p.description||'No project description has been added.')+'</p><div class="project-meta-line"><span>Started: <b>'+esc(fmtDate(p.start_date))+'</b></span><span>Target end: <b>'+esc(fmtDate(p.end_date))+'</b></span><span>Last updated: <b>'+esc(fmtDateTime(p.updated_at))+'</b></span></div></div>'}
  function tasksHtml(p){var rows=(p.tasks||[]);return'<div class="toolbar compact"><div><h3>Related Tasks</h3><p class="pill">Tasks linked to this project</p></div><button class="btnx small" type="button" data-open-task-board>Open Task Board</button></div><div class="project-task-grid">'+(rows.length?rows.map(function(t){return'<article class="cardx project-task"><div><span class="badge '+(t.priority==='High'?'danger':t.priority==='Low'?'blue':'warn')+'">'+esc(t.priority)+'</span><span class="badge blue">'+esc(t.status)+'</span></div><h4>'+esc(t.title)+'</h4><small>'+esc(t.assignee||'Unassigned')+' · Due '+esc(fmtDate(t.due_date))+'</small></article>'}).join(''):'<div class="empty-state cardx">No tasks are linked to this project yet.</div>')+'</div>'}
  function filesHtml(p,manage){var rows=p.files||[];return (manage?'<details class="cardx project-add-box"><summary>Add file reference</summary><p class="muted">Phase 2 stores project file references. Phase 4 will replace this with protected server-side uploads.</p><form id="projectFileForm" class="form-grid"><div class="field"><label>File name</label><input name="fileName" required></div><div class="field"><label>File link</label><input name="fileUrl" required placeholder="https://... or /protected/path"></div><div class="field full"><label>Description</label><textarea name="description"></textarea></div><div class="full"><button class="btnx primary">Add File</button></div></form></details>':'')+'<div class="resource-list">'+(rows.length?rows.map(function(f){return'<article class="cardx resource-row"><div><b>'+esc(f.file_name)+'</b><p>'+esc(f.description||'No description')+'</p><small>Added by '+esc(f.added_by_name||'RDI member')+' · '+esc(fmtDateTime(f.created_at))+'</small></div><div><a class="btnx small" href="'+esc(f.file_url)+'" target="_blank" rel="noopener">Open</a>'+(manage?' <button class="btnx danger small" type="button" data-project-file-delete="'+esc(f.id)+'">Remove</button>':'')+'</div></article>'}).join(''):'<div class="empty-state cardx">No project files have been added.</div>')+'</div>'}
  function papersHtml(p,manage){var rows=p.papers||[];return (manage?'<details class="cardx project-add-box"><summary>Add research paper</summary><form id="projectPaperForm" class="form-grid"><div class="field full"><label>Paper title</label><input name="title" required></div><div class="field"><label>Authors</label><input name="authors"></div><div class="field"><label>Venue</label><input name="venue"></div><div class="field"><label>DOI</label><input name="doi"></div><div class="field"><label>Paper link</label><input name="paperUrl" placeholder="https://..."></div><div class="field full"><label>Notes</label><textarea name="notes"></textarea></div><div class="full"><button class="btnx primary">Add Paper</button></div></form></details>':'')+'<div class="resource-list">'+(rows.length?rows.map(function(x){return'<article class="cardx resource-row"><div><b>'+esc(x.title)+'</b><p>'+esc(x.authors||'')+(x.venue?' · '+esc(x.venue):'')+'</p><small>'+(x.doi?'DOI: '+esc(x.doi)+' · ':'')+'Added '+esc(fmtDateTime(x.created_at))+'</small></div><div>'+(x.paper_url?'<a class="btnx small" href="'+esc(x.paper_url)+'" target="_blank" rel="noopener">Paper</a>':'')+(manage?' <button class="btnx danger small" type="button" data-project-paper-delete="'+esc(x.id)+'">Remove</button>':'')+'</div></article>'}).join(''):'<div class="empty-state cardx">No research papers have been linked.</div>')+'</div>'}
  function membersHtml(p,manage){var members=p.members||[];var existing={};members.forEach(function(m){existing[m.user_id]=1});var choices=currentUsers.filter(function(u){return !existing[u.id]});return (manage&&choices.length?'<div class="cardx"><form id="projectMemberForm" class="project-member-form"><select name="userId" required>'+choices.map(function(u){return'<option value="'+esc(u.id)+'">'+esc(u.name)+' · '+esc(u.rdi_member_id||u.email)+'</option>'}).join('')+'</select><input name="membershipRole" value="Member" placeholder="Membership role"><button class="btnx primary">Add Member</button></form></div>':'')+'<div class="member-grid top-gap">'+(members.length?members.map(function(m){var isLeader=p.leader_user_id===m.user_id;return'<article class="cardx member-card"><div class="member-avatar">'+esc((m.name||'?').slice(0,1).toUpperCase())+'</div><div><b>'+esc(m.name)+'</b><small>'+esc(m.rdi_member_id||'')+' · '+esc(m.email)+'</small><span class="badge blue">'+esc(isLeader?'Project Lead':m.membership_role)+'</span></div>'+(manage&&!isLeader?'<button class="btnx danger small" type="button" data-project-member-remove="'+esc(m.user_id)+'">Remove</button>':'')+'</article>'}).join(''):'<div class="empty-state cardx">No project members yet.</div>')+'</div>'}
  function discussionHtml(p){var comments=p.comments||[],byParent={};comments.forEach(function(c){var k=c.parent_id||'root';(byParent[k]||(byParent[k]=[])).push(c)});function nodes(parent,depth){return(byParent[parent]||[]).map(function(c){return'<article class="project-comment" style="--comment-depth:'+Math.min(depth,4)+'"><div class="project-comment__head"><b>'+esc(c.name)+'</b><small>'+esc(fmtDateTime(c.created_at))+'</small></div><p>'+esc(c.message)+'</p>'+(p.canComment?'<button class="link-button" type="button" data-project-reply="'+esc(c.id)+'">Reply</button>':'')+nodes(c.id,depth+1)+'</article>'}).join('')}return (p.canComment?'<form id="projectCommentForm" class="cardx project-comment-form"><textarea name="message" required maxlength="5000" placeholder="Write a project discussion comment…"></textarea><button class="btnx primary">Post Comment</button></form>':'')+'<div class="project-comments">'+(comments.length?nodes('root',0):'<div class="empty-state cardx">No discussion yet. Start the project thread.</div>')+'</div>'}
  function activityHtml(p){var rows=p.activity||[];return'<div class="project-timeline">'+(rows.length?rows.map(function(a){return'<div class="project-timeline-item"><span></span><div><b>'+esc(a.actor_name||'System')+'</b> <strong>'+esc(String(a.action||'').replace(/_/g,' '))+'</strong><p>'+esc(a.details||'')+'</p><small>'+esc(fmtDateTime(a.created_at))+'</small></div></div>'}).join(''):'<div class="empty-state cardx">No project activity recorded yet.</div>')+'</div>'}
  function projectFormHtml(p){p=p||{};var users=availableUsers.length?availableUsers:currentUsers;return'<div class="cardx top-gap"><h3>'+(p.id?'Edit Project':'Create Project')+'</h3><form id="projectEditorForm" class="form-grid"><div class="field full"><label>Name</label><input name="name" required value="'+esc(p.name||'')+'"></div><div class="field full"><label>Description</label><textarea name="description">'+esc(p.description||'')+'</textarea></div><div class="field"><label>Leader</label><select name="leaderUserId"><option value="">Unassigned</option>'+users.map(function(u){return'<option value="'+esc(u.id)+'" '+(u.id===p.leader_user_id?'selected':'')+'>'+esc(u.name)+' · '+esc(u.rdi_member_id||u.email)+'</option>'}).join('')+'</select></div><div class="field"><label>Status</label><select name="status">'+['Planned','Active','On Hold','Completed','Cancelled'].map(function(st){return'<option '+((p.status||'Active')===st?'selected':'')+'>'+st+'</option>'}).join('')+'</select></div><div class="field"><label>Start date</label><input type="date" name="startDate" value="'+esc(p.start_date||'')+'"></div><div class="field"><label>End date</label><input type="date" name="endDate" value="'+esc(p.end_date||'')+'"></div><div class="full"><button class="btnx primary">'+(p.id?'Save Project':'Create Project')+'</button> <button class="btnx" type="button" data-project-form-cancel>Cancel</button></div></form></div>'}
  function openEditor(p){formWrap.innerHTML=projectFormHtml(p);var f=document.getElementById('projectEditorForm');f.onsubmit=async function(e){e.preventDefault();var fd=new FormData(f),payload={action:p&&p.id?'update':'create',name:fd.get('name'),description:fd.get('description'),leaderUserId:fd.get('leaderUserId'),status:fd.get('status'),startDate:fd.get('startDate'),endDate:fd.get('endDate')};if(p&&p.id)payload.projectId=p.id;try{var j=await post(payload);formWrap.innerHTML='';await loadProjects(j.project&&j.project.id?j.project.id:(p?p.id:''));toast(p&&p.id?'Project updated':'Project created')}catch(ex){toast(ex.message)}};var cancel=formWrap.querySelector('[data-project-form-cancel]');if(cancel)cancel.onclick=function(){formWrap.innerHTML=''};}
  function bindDetailActions(){
    Array.from(detailEl.querySelectorAll('[data-project-tab]')).forEach(function(b){b.onclick=function(){detailTab=b.dataset.projectTab;renderDetail()}});
    var edit=detailEl.querySelector('[data-project-edit]');if(edit)edit.onclick=function(){availableUsers=currentUsers;openEditor(currentProject);formWrap.scrollIntoView({behavior:'smooth',block:'start'})};
    var taskBoard=detailEl.querySelector('[data-open-task-board]');if(taskBoard)taskBoard.onclick=function(){var b=document.querySelector('#adminNav [data-tab="tasks"]');if(b)b.click()};
    var ff=document.getElementById('projectFileForm');if(ff)ff.onsubmit=async function(e){e.preventDefault();var f=new FormData(ff);try{await post({action:'file_add',projectId:currentProject.id,fileName:f.get('fileName'),fileUrl:f.get('fileUrl'),description:f.get('description')});await openProject(currentProject.id);detailTab='files';renderDetail();toast('Project file added')}catch(ex){toast(ex.message)}};
    Array.from(detailEl.querySelectorAll('[data-project-file-delete]')).forEach(function(b){b.onclick=async function(){if(!confirm('Remove this project file reference?'))return;try{await post({action:'file_delete',projectId:currentProject.id,fileId:b.dataset.projectFileDelete});await openProject(currentProject.id);detailTab='files';renderDetail();toast('File removed')}catch(ex){toast(ex.message)}}});
    var pf=document.getElementById('projectPaperForm');if(pf)pf.onsubmit=async function(e){e.preventDefault();var f=new FormData(pf);try{await post({action:'paper_add',projectId:currentProject.id,title:f.get('title'),authors:f.get('authors'),venue:f.get('venue'),doi:f.get('doi'),paperUrl:f.get('paperUrl'),notes:f.get('notes')});await openProject(currentProject.id);detailTab='papers';renderDetail();toast('Research paper added')}catch(ex){toast(ex.message)}};
    Array.from(detailEl.querySelectorAll('[data-project-paper-delete]')).forEach(function(b){b.onclick=async function(){if(!confirm('Remove this project paper?'))return;try{await post({action:'paper_delete',projectId:currentProject.id,paperId:b.dataset.projectPaperDelete});await openProject(currentProject.id);detailTab='papers';renderDetail();toast('Paper removed')}catch(ex){toast(ex.message)}}});
    var mf=document.getElementById('projectMemberForm');if(mf)mf.onsubmit=async function(e){e.preventDefault();var f=new FormData(mf);try{await post({action:'member_add',projectId:currentProject.id,userId:f.get('userId'),membershipRole:f.get('membershipRole')});await openProject(currentProject.id);detailTab='members';renderDetail();await RDIStore.reload();toast('Project member added')}catch(ex){toast(ex.message)}};
    Array.from(detailEl.querySelectorAll('[data-project-member-remove]')).forEach(function(b){b.onclick=async function(){if(!confirm('Remove this member from the project?'))return;try{await post({action:'member_remove',projectId:currentProject.id,userId:b.dataset.projectMemberRemove});await openProject(currentProject.id);detailTab='members';renderDetail();await RDIStore.reload();toast('Project member removed')}catch(ex){toast(ex.message)}}});
    var cf=document.getElementById('projectCommentForm');if(cf)cf.onsubmit=async function(e){e.preventDefault();var f=new FormData(cf);try{await post({action:'comment',projectId:currentProject.id,message:f.get('message')});await openProject(currentProject.id);detailTab='discussion';renderDetail();toast('Comment posted')}catch(ex){toast(ex.message)}};
    Array.from(detailEl.querySelectorAll('[data-project-reply]')).forEach(function(b){b.onclick=async function(){var msg=prompt('Reply to this comment:','');if(!msg)return;try{await post({action:'comment',projectId:currentProject.id,parentId:b.dataset.projectReply,message:msg});await openProject(currentProject.id);detailTab='discussion';renderDetail();toast('Reply posted')}catch(ex){toast(ex.message)}}});
  }
  if(searchEl)searchEl.oninput=renderList;if(statusEl)statusEl.onchange=renderList;
  var add=document.getElementById('addProjectBtn');if(add)add.onclick=function(){availableUsers=currentUsers.length?currentUsers:availableUsers;openEditor(null)};
  var nav=document.querySelector('#adminNav [data-tab="projects"]');if(nav)nav.addEventListener('click',function(){loadProjects(parseHashProject())});
  if(document.getElementById('projects').classList.contains('active')||parseHashProject())loadProjects(parseHashProject());
})();
