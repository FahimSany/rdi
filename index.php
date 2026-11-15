<?php
/* Research-news fallback API route.
   This intentionally lives on index.php so it can work even when an Apache
   rule/security module blocks a file under /api/. */
if (isset($_GET['rdi_api']) && $_GET['rdi_api'] === 'research-news') {
    require_once __DIR__ . '/includes/research_news.php';
    $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

    if ($method === 'GET' && isset($_GET['diagnostics'])) {
        require_permission('manage_research_news');
        json_response(array('ok'=>true,'diagnostics'=>rdi_research_news_diagnostics(true)));
    }

    if ($method === 'POST') {
        $actor=require_permission('manage_research_news');
        try {
            $result=run_research_news_automation(true);
            audit_log('research_news_automation_run','research_news',null,'Imported '.(isset($result['imported'])?$result['imported']:0).' item(s)',$actor);
            json_response($result);
        } catch (Throwable $e) {
            json_response(array(
                'ok'=>false,
                'error'=>'Research automation failed: '.$e->getMessage(),
                'diagnostics'=>rdi_research_news_diagnostics(false)
            ),500);
        }
    }

    if ($method === 'GET' && isset($_GET['auto'])) {
        $actor=require_permission('manage_research_news');
        try {
            $result=run_research_news_automation(false);
            if(!empty($result['ran']))audit_log('research_news_automation_auto','research_news',null,'Imported '.(isset($result['imported'])?$result['imported']:0).' item(s)',$actor);
            json_response($result);
        } catch (Throwable $e) {
            json_response(array('ok'=>false,'error'=>'Research automation failed: '.$e->getMessage()),500);
        }
    }

    json_response(array('error'=>'Method not allowed'),405);
}

require_once __DIR__.'/includes/state.php';
start_rdi_session();
try {
  $u = current_user();
  $mode = !$u ? 'public' : 'member';
  $initialState = load_state($mode,$u);
} catch (Throwable $e) {
  $u = null;
  $initialState = ['users'=>[],'gallery'=>[],'blogs'=>[],'news'=>[],'homeSections'=>[],'messages'=>[],'applications'=>[],'tasks'=>[],'settings'=>[],'memberInvites'=>[],'roles'=>[],'permissions'=>[],'rolePermissions'=>[],'hrRequests'=>[],'auditLogs'=>[],'projects'=>[],'currentUser'=>null];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RDI Innovators | Making Ideas Matter</title>
  <meta name="description" content="Research and Development Innovators (RDI) — research, innovation, projects, blog, research news and team management." />
  <meta name="theme-color" content="#ff5e1a" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
  <div class="app-view" data-view="home">
 ========== HEADER ========== 
<header class="header" id="header">
<div class="container header__inner">
<a aria-label="RDI Innovators — back to top" class="header__brand" href="#home">
<span aria-hidden="true" class="brand-mark">
<svg fill="none" height="28" viewbox="0 0 32 32" width="28">
<path d="M16 3a9 9 0 00-5 16.5V23a2 2 0 002 2h6a2 2 0 002-2v-3.5A9 9 0 0016 3z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
<path d="M13 28h6M14 25.5h4" stroke="currentColor" stroke-linecap="round" stroke-width="2"></path>
</svg>
</span>
<span class="brand-text">
<strong>RDI</strong>
<small>INNOVATORS</small>
</span>
</a>
<button aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation" class="header__toggle" id="navToggle">
<span></span>
<span></span>
<span></span>
</button>
<nav aria-label="Main navigation" class="nav" id="mainNav" role="navigation">
<a class="nav__link" href="#home">Home</a>
<a class="nav__link" href="#about">About</a>
<a class="nav__link" href="#services">Services</a>
<a class="nav__link" href="#opportunities">Opportunities</a>
<a class="nav__link" href="#workflow">Workflow</a>
<a class="nav__link" href="#experts">For Experts</a>
<a class="nav__link" href="#contact">Contact</a>
<a class="nav__link" href="#gallery">Gallery</a>
<a class="nav__link" href="#blog">Blog</a>
<a class="nav__link" href="#research-news">Research News</a>
<a class="nav__link login-link" href="#login">Login</a>
<a class="btn btn--primary btn--sm nav__cta" href="#signup">Member Sign Up</a>
<button aria-label="Toggle light/dark mode" class="theme-toggle" id="themeToggle">
<svg aria-hidden="true" class="icon-moon" fill="none" height="18" viewbox="0 0 24 24" width="18">
<path d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
</svg>
<svg aria-hidden="true" class="icon-sun" fill="none" height="18" viewbox="0 0 24 24" width="18">
<circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.8"></circle>
<path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"></path>
</svg>
</button>
</nav>
</div>
</header>
<main>
<!-- ========== HERO ========== -->
<section class="hero" id="home">
<div aria-hidden="true" class="hero__stars"></div>
<div class="container hero__grid">
<div class="hero__copy">
<h1 class="hero__title">Research and Development Innovators (RDI)</h1>
<p class="hero__tagline">Making Ideas Matter</p>
<p class="hero__sub">
            A global, fully remote research and innovation team transforming
            ideas into impactful solutions through AI, engineering, and
            interdisciplinary R&amp;D.
          </p>
<div class="hero__actions">
<a class="btn btn--primary" href="#contact-form">
              Start a Project
              <svg aria-hidden="true" fill="none" height="14" viewbox="0 0 16 16" width="14"><path d="M2 8l12-6-4 14-2-6-6-2z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path></svg>
</a>
<a class="btn btn--outline" href="#signup">
<svg aria-hidden="true" fill="none" height="14" viewbox="0 0 16 16" width="14"><circle cx="8" cy="6" r="3" stroke="currentColor" stroke-width="1.5"></circle><path d="M2 14a6 6 0 0112 0" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"></path></svg>
              Join as Researcher
            </a>
</div>
</div>
<div class="hero__visual">
<img alt="RDI Innovators concept visual" class="hero__illustration hero__illustration--dark" src="assets/DarkLogo.png"/>
<img alt="RDI Innovators concept visual" class="hero__illustration hero__illustration--light" src="assets/LightLogo.png"/>
</div>
</div>
</section>
<!-- ========== ABOUT ========== -->
<section class="about" id="about">
<div class="container about__grid">
<div class="about__copy">
<span class="eyebrow">ABOUT RDI</span>
<h2>Who We Are</h2>
<p>
            Research and Development Innovators (RDI) is a fully remote,
            project-based research organization dedicated to solving
            real-world problems through innovation and interdisciplinary
            collaboration.
          </p>
<p>
            We specialize in cutting-edge domains including Artificial
            Intelligence, Machine Learning, Cloud Computing, IoT, Biotechnology,
            Renewable Energy, Quantum Technologies, NLP, Computer Vision,
            and core Engineering (EEE &amp; CSE).
          </p>
<p>
            Our mission is to bridge the gap between academic research and
            real-world applications by transforming ideas into impactful
            prototypes, products, and high-quality publications.
          </p>
<a class="btn btn--primary" href="#services">Learn More</a>
</div>
<div class="about__domains">
<article class="domain-card">
<span class="domain-card__icon">
<svg aria-hidden="true" fill="none" height="40" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="40">
<path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 1.98-3A2.5 2.5 0 0 1 9.5 2Z" fill="currentColor" fill-opacity="0.15"></path>
<path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-1.98-3A2.5 2.5 0 0 0 14.5 2Z" fill="currentColor" fill-opacity="0.08"></path>
<circle cx="6.5" cy="9" fill="currentColor" r="0.9"></circle>
<circle cx="17.5" cy="9" fill="currentColor" r="0.9"></circle>
<circle cx="7" cy="14.5" fill="currentColor" r="0.9"></circle>
<circle cx="17" cy="14.5" fill="currentColor" r="0.9"></circle>
</svg>
</span>
<h4>AI / ML</h4>
</article>
<article class="domain-card">
<span class="domain-card__icon">
<svg aria-hidden="true" fill="none" height="40" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="40">
<path d="M17.5 19a4.5 4.5 0 1 0-1.41-8.775 5.5 5.5 0 0 0-10.85-1.018A4.5 4.5 0 0 0 6.5 19h11Z" fill="currentColor" fill-opacity="0.15"></path>
<path d="M8 22v-2M12 22v-3M16 22v-2" stroke-width="1.9"></path>
</svg>
</span>
<h4>Cloud Computing</h4>
</article>
<article class="domain-card">
<span class="domain-card__icon">
<svg aria-hidden="true" fill="none" height="40" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="40">
<circle cx="12" cy="12" fill="currentColor" r="3"></circle>
<circle cx="4" cy="4" fill="currentColor" fill-opacity="0.2" r="2.3"></circle>
<circle cx="20" cy="4" fill="currentColor" fill-opacity="0.2" r="2.3"></circle>
<circle cx="4" cy="20" fill="currentColor" fill-opacity="0.2" r="2.3"></circle>
<circle cx="20" cy="20" fill="currentColor" fill-opacity="0.2" r="2.3"></circle>
<path d="m5.6 5.6 4.5 4.5M18.4 5.6l-4.5 4.5M5.6 18.4l4.5-4.5M18.4 18.4l-4.5-4.5"></path>
</svg>
</span>
<h4>IoT</h4>
</article>
<article class="domain-card">
<span class="domain-card__icon">
<svg aria-hidden="true" fill="none" height="40" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="40">
<path d="M7 2c0 4 10 4 10 8s-10 4-10 8c0 2 0 2 0 4"></path>
<path d="M17 2c0 4-10 4-10 8s10 4 10 8c0 2 0 2 0 4"></path>
<path d="M9 5h6M8.5 9h7M8.5 15h7M9 19h6" stroke-width="1.9"></path>
<circle cx="9" cy="5" fill="currentColor" r="0.7"></circle>
<circle cx="15" cy="5" fill="currentColor" r="0.7"></circle>
<circle cx="9" cy="19" fill="currentColor" r="0.7"></circle>
<circle cx="15" cy="19" fill="currentColor" r="0.7"></circle>
</svg>
</span>
<h4>Biotechnology</h4>
</article>
<article class="domain-card">
<span class="domain-card__icon">
<svg aria-hidden="true" fill="none" height="40" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="40">
<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19.8 2c1 5 0 11.5-4.6 14.5" fill="currentColor" fill-opacity="0.15"></path>
<path d="M2 22 17 7" stroke-width="1.9"></path>
<path d="M9 14c2 0 4-1 5-3M14 9c0 2-1 4-3 5"></path>
</svg>
</span>
<h4>Renewable Energy</h4>
</article>
<article class="domain-card">
<span class="domain-card__icon">
<svg aria-hidden="true" fill="none" height="40" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="40">
<circle cx="12" cy="12" fill="currentColor" r="2"></circle>
<path d="M20.2 20.2c2.04-2.03.02-7.36-4.5-11.9-4.54-4.52-9.87-6.54-11.9-4.5-2.04 2.03-.02 7.36 4.5 11.9 4.54 4.52 9.87 6.54 11.9 4.5Z" fill="currentColor" fill-opacity="0.1"></path>
<path d="M15.7 15.7c4.52-4.54 6.54-9.87 4.5-11.9-2.03-2.04-7.36-.02-11.9 4.5-4.52 4.54-6.54 9.87-4.5 11.9 2.03 2.04 7.36.02 11.9-4.5Z" fill="currentColor" fill-opacity="0.1"></path>
</svg>
</span>
<h4>Quantum Technologies</h4>
</article>
<article class="domain-card">
<span class="domain-card__icon">
<svg aria-hidden="true" fill="none" height="40" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="40">
<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10Z" fill="currentColor" fill-opacity="0.15"></path>
<path d="M7 9h10M7 13h6" stroke-width="1.9"></path>
<circle cx="8" cy="9" fill="currentColor" r="0.6"></circle>
<circle cx="16" cy="9" fill="currentColor" r="0.6"></circle>
</svg>
</span>
<h4>NLP</h4>
</article>
<article class="domain-card">
<span class="domain-card__icon">
<svg aria-hidden="true" fill="none" height="40" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="40">
<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="currentColor" fill-opacity="0.10"></path>
<circle cx="12" cy="12" fill="currentColor" fill-opacity="0.25" r="3.2"></circle>
<circle cx="12" cy="12" r="3.2"></circle>
<circle cx="12" cy="12" fill="currentColor" r="1.2"></circle>
<circle cx="13.4" cy="10.6" fill="#ffffff" fill-opacity="0.9" r="0.5"></circle>
</svg>
</span>
<h4>Computer Vision</h4>
</article>
</div>
</div>
</section>
<!-- ========== SERVICES / CORE ACTIVITIES ========== -->
<section class="services" id="services">
<div class="container">
<div class="section-head section-head--center">
<span class="eyebrow">WHAT WE DO</span>
<h2>Our Core Activities</h2>
</div>
<div class="card-grid card-grid--4">
<article class="service-card">
<span class="service-card__icon">
<svg fill="none" height="36" viewbox="0 0 24 24" width="36"><path d="M9 2v6L4 18a3 3 0 003 4h10a3 3 0 003-4L15 8V2M9 2h6M11 14h2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path></svg>
</span>
<h3>Research &amp; Innovation</h3>
<p>We conduct advanced research across multiple domains and publish in high-impact journals such as IEEE and Springer.</p>
</article>
<article class="service-card">
<span class="service-card__icon">
<svg fill="none" height="36" viewbox="0 0 24 24" width="36"><rect height="14" rx="2" stroke="currentColor" stroke-width="1.5" width="20" x="2" y="4"></rect><path d="M9 9l-2 2 2 2M15 9l2 2-2 2M8 22h8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path></svg>
</span>
<h3>Prototype Development</h3>
<p>We convert research outcomes into real-world applications, industrial prototypes, and scalable solutions.</p>
</article>
<article class="service-card">
<span class="service-card__icon">
<svg fill="none" height="36" viewbox="0 0 24 24" width="36"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"></circle><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"></path></svg>
</span>
<h3>Engineering Solutions</h3>
<p>We provide innovative R&amp;D-based solutions for complex real-life engineering and industrial problems.</p>
</article>
<article class="service-card">
<span class="service-card__icon">
<svg fill="none" height="36" viewbox="0 0 24 24" width="36"><path d="M2 9l10-5 10 5-10 5z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.5"></path><path d="M6 11v5a6 6 0 0012 0v-5M22 9v6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path></svg>
</span>
<h3>Research Training</h3>
<p>We equip undergrad, master's and PhD students with proper research methodologies, writing standards and publication skills.</p>
</article>
</div>
</div>
</section>
<!-- ========== OPPORTUNITIES ========== -->
<section class="opportunities" id="opportunities">
<div class="container">
<div class="section-head section-head--center section-head--light">
<span class="eyebrow">OPPORTUNITIES FOR</span>
<h2>Students &amp; Researchers</h2>
</div>
<div class="opp-row">
<article class="opp-card">
<span class="opp-card__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><path d="M9 18h6M10 22h4M12 2a7 7 0 00-4 12.7V17h8v-2.3A7 7 0 0012 2z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path></svg>
</span>
<h4>Pitch Your Idea</h4>
<p>Pitch your own research idea and collaborate with expert resources.</p>
</article>
<article class="opp-card">
<span class="opp-card__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"></circle><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"></path></svg>
</span>
<h4>Join Projects</h4>
<p>Join ongoing projects through selection and interview process.</p>
</article>
<article class="opp-card">
<span class="opp-card__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.5"></path><path d="M14 2v6h6M9 13l2 2 4-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path></svg>
</span>
<h4>Authorship</h4>
<p>Gain authorship in research papers based on your contribution impact.</p>
</article>
<article class="opp-card">
<span class="opp-card__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"></circle><path d="M4 21a8 8 0 0116 0" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"></path><path d="M16 11l2 2 4-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path></svg>
</span>
<h4>Research Assistant</h4>
<p>Work as Research Assistant (RA) or supervise teams in projects.</p>
</article>
<article class="opp-card">
<span class="opp-card__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.5"></path><path d="M8 10h8M8 13h5" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"></path></svg>
</span>
<h4>Peer Review</h4>
<p>Get peer review and improvement support for your academic research.</p>
</article>
</div>
<div class="opp-row opp-row--4">
<article class="opp-card">
<span class="opp-card__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.5"></path><path d="M14 2v6h6M8 13h8M8 17h6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path></svg>
</span>
<h4>Paper Validation</h4>
<p>Research paper checking via Turnitin and expert review.</p>
</article>
<article class="opp-card">
<span class="opp-card__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><path d="M3 21h18M5 21V10l7-7 7 7v11M9 21v-6h6v6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path></svg>
</span>
<h4>Study Abroad Support</h4>
<p>Guidance for Master's / PhD applications to top universities abroad.</p>
</article>
<article class="opp-card">
<span class="opp-card__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><path d="M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.5"></path><circle cx="9" cy="10" r="2" stroke="currentColor" stroke-width="1.5"></circle><path d="M14 9h4M14 13h4M5 17l3-2 3 2" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"></path></svg>
</span>
<h4>Visa &amp; Application</h4>
<p>Visa processing and application support through our partner network.</p>
</article>
<article class="opp-card">
<span class="opp-card__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><path d="M20 7l-8-4-8 4 8 4 8-4z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.5"></path><path d="M4 12l8 4 8-4M4 17l8 4 8-4" stroke="currentColor" stroke-linejoin="round" stroke-width="1.5"></path></svg>
</span>
<h4>Custom Packages</h4>
<p>Custom packages including research, publication and study abroad support.</p>
</article>
</div>
</div>
</section>
<!-- ========== WORKFLOW ========== -->
<section class="workflow" id="workflow">
<div class="container">
<div class="section-head section-head--center">
<span class="eyebrow">OUR WORKFLOW</span>
<h2>How We Work</h2>
</div>
<ol class="workflow__steps">
<li class="workflow__step">
<span class="workflow__num">1</span>
<span class="workflow__icon">
<svg fill="none" viewbox="0 0 24 24"><path d="M9 18h6M10 22h4M12 2a7 7 0 00-4 12.7V17h8v-2.3A7 7 0 0012 2z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9"></path></svg>
</span>
<h4>Identify Problem</h4>
<p>Identify research problem &amp; define scope.</p>
</li>
<li class="workflow__step">
<span class="workflow__num">2</span>
<span class="workflow__icon">
<svg fill="none" viewbox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.9"></path><path d="M14 2v6h6M8 13h8M8 17h5" stroke="currentColor" stroke-linecap="round" stroke-width="1.9"></path></svg>
</span>
<h4>Proposal</h4>
<p>Prepare research proposal.</p>
</li>
<li class="workflow__step">
<span class="workflow__num">3</span>
<span class="workflow__icon">
<svg fill="none" viewbox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.9"></circle><path d="M4 21a8 8 0 0116 0" stroke="currentColor" stroke-linecap="round" stroke-width="1.9"></path></svg>
</span>
<h4>Pitch for Funding</h4>
<p>Pitch to professors, institutions and academic networks.</p>
</li>
<li class="workflow__step">
<span class="workflow__num">4</span>
<span class="workflow__icon">
<svg fill="none" viewbox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.9"></circle><circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.9"></circle><circle cx="12" cy="12" fill="currentColor" r="2"></circle></svg>
</span>
<h4>Target Journals</h4>
<p>Target high-impact journals (IEEE, Q1/Q2).</p>
</li>
<li class="workflow__step">
<span class="workflow__num">5</span>
<span class="workflow__icon">
<svg fill="none" viewbox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.9"></circle><path d="M12 7v10M9 10h4.5a2 2 0 010 4H9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.1"></path></svg>
</span>
<h4>Funding Model</h4>
<p>Choose funding model (sponsored / self-funded).</p>
</li>
<li class="workflow__step">
<span class="workflow__num">6</span>
<span class="workflow__icon">
<svg fill="none" viewbox="0 0 24 24"><path d="M14.7 6.3a4 4 0 105.4 5.4L17 15l4 4-3 3-4-4-3.3 3.3a4 4 0 11-5.4-5.4L8 13 5 10l3-3 3 3 3.7-3.7z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9"></path></svg>
</span>
<h4>Prototype</h4>
<p>Develop prototype and validate results.</p>
</li>
<li class="workflow__step">
<span class="workflow__num">7</span>
<span class="workflow__icon">
<svg fill="none" viewbox="0 0 24 24"><path d="M3 21h18M6 18V10M11 18V6M16 18v-8M21 18v-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4"></path></svg>
</span>
<h4>Scale &amp; Impact</h4>
<p>Scale into product and create real-world impact.</p>
</li>
</ol>
<p class="workflow__note">
<strong>Note:</strong> For self-funded research, RDI provides ~20% subsidy, the rest is shared by the team.
        </p>
</div>
</section>
<!-- ========== STATS ========== -->
<section class="stats">
<div class="container stats__grid">
<div class="stat">
<span class="stat__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><path d="M9 18h6M10 22h4M12 2a7 7 0 00-4 12.7V17h8v-2.3A7 7 0 0012 2z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path></svg>
</span>
<strong>50+</strong>
<span>Research Projects</span>
</div>
<div class="stat">
<span class="stat__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.5"></path><path d="M14 2v6h6M8 13h8M8 17h5" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"></path></svg>
</span>
<strong>100+</strong>
<span>Research Papers</span>
</div>
<div class="stat">
<span class="stat__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"></path><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"></circle><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"></path></svg>
</span>
<strong>200+</strong>
<span>Researchers</span>
</div>
<div class="stat">
<span class="stat__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"></circle><circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.5"></circle><circle cx="12" cy="12" fill="currentColor" r="1.5"></circle></svg>
</span>
<strong>15+</strong>
<span>Domain Expertise</span>
</div>
<div class="stat">
<span class="stat__icon">
<svg fill="none" height="28" viewbox="0 0 24 24" width="28"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"></circle><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"></path></svg>
</span>
<strong>5+</strong>
<span>Academies Reached</span>
</div>
</div>
</section>
<!-- ========== FOR EXPERTS ========== -->
<section class="experts" id="experts">
<div class="container experts__grid">
<div aria-hidden="true" class="experts__visual">
<img alt="" class="experts__img experts__img--dark" src="assets/DarkDesk.png"/>
<img alt="" class="experts__img experts__img--light" src="assets/LightDesk.png"/>
</div>
<div class="experts__copy">
<span class="eyebrow">FOR EXPERTS &amp; PROFESSIONALS</span>
<h2>Share Your Knowledge. <span class="text-accent">Create Impact.</span></h2>
<ul class="experts__list">
<li><span class="check"><svg fill="none" height="16" viewbox="0 0 18 18" width="16"><path d="M3 9l4 4 8-8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"></path></svg></span>Create your own courses under RDI</li>
<li><span class="check"><svg fill="none" height="16" viewbox="0 0 18 18" width="16"><path d="M3 9l4 4 8-8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"></path></svg></span>Earn royalties from enrolled students</li>
<li><span class="check"><svg fill="none" height="16" viewbox="0 0 18 18" width="16"><path d="M3 9l4 4 8-8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"></path></svg></span>Lead or supervise research teams</li>
<li><span class="check"><svg fill="none" height="16" viewbox="0 0 18 18" width="16"><path d="M3 9l4 4 8-8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"></path></svg></span>Collaborate on funded or independent research projects</li>
<li><span class="check"><svg fill="none" height="16" viewbox="0 0 18 18" width="16"><path d="M3 9l4 4 8-8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"></path></svg></span>Provide peer review services for academic research</li>
</ul>
<a class="btn btn--primary" href="#signup">Learn More</a>
</div>
</div>
</section>
<!-- ========== JOIN RDI ========== -->
<section class="join" id="join">
<div class="container join__grid">
<div class="join__copy">
<span class="eyebrow eyebrow--light">JOIN RDI</span>
<h2>Let's Build the Future Together</h2>
<p>If you are passionate about research and innovation, join us and turn ideas into impactful solutions.</p>
</div>
<div class="join__cards" id="contact">
<div class="join-card">
<span class="join-card__icon">
<svg fill="none" height="22" viewbox="0 0 24 24" width="22"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.6"></circle><path d="M4 21a8 8 0 0116 0" stroke="currentColor" stroke-linecap="round" stroke-width="1.6"></path></svg>
</span>
<strong>Join as<br/>Research Assistant</strong>
</div>
<div class="join-card">
<span class="join-card__icon">
<svg fill="none" height="22" viewbox="0 0 24 24" width="22"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.6"></path><path d="M14 2v6h6M9 13l2 2 4-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"></path></svg>
</span>
<strong>Work on<br/>Exciting Projects</strong>
</div>
<div class="join-card">
<span class="join-card__icon">
<svg fill="none" height="22" viewbox="0 0 24 24" width="22"><path d="M2 9l10-5 10 5-10 5z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.6"></path><path d="M6 11v5a6 6 0 0012 0v-5" stroke="currentColor" stroke-linecap="round" stroke-width="1.6"></path></svg>
</span>
<strong>Build Your<br/>Academic Profile</strong>
</div>
<button aria-label="Copy email address" class="join__email" id="copyEmailBtn" type="button">
<span class="join__email-icon">
<svg fill="none" height="18" viewbox="0 0 24 24" width="18"><path d="M2 6a2 2 0 012-2h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.6"></path><path d="M2 6l10 7 10-7" stroke="currentColor" stroke-linecap="round" stroke-width="1.6"></path></svg>
</span>
<span class="join__email-text">Send your resume: <strong>info.rd.innovators@gmail.com</strong></span>
</button>
</div>

<div class="join-apply-card controlled-join-card">
  <div class="join-apply-head"><span class="eyebrow eyebrow--light">CONTROLLED RDI MEMBERSHIP</span><h3>Already recruited by RDI?</h3><p>Public self-registration is disabled. An RDI Admin first creates a unique one-time Member ID linked to your approved email and initial role.</p></div>
  <div class="controlled-steps"><div><b>1</b><span>Receive your RDI Member ID and approved email from an administrator.</span></div><div><b>2</b><span>Use the exact Member ID + approved email to create your account.</span></div><div><b>3</b><span>Log in to your profile, projects/tasks and HR services.</span></div></div>
  <a class="btn btn--primary" href="#signup">Use RDI Member ID to Sign Up</a>
  <a class="btn btn--outline" href="#login">Already have an account? Log in</a>
</div>
</div>
</section>
<!-- ========== ADMIN-CONTROLLED CUSTOM SECTIONS ========== -->
<div id="rdiCustomSections"></div>
<!-- ========== CONTACT FORM ========== -->
<section class="contact-form-wrap" id="contact-form">
<div class="container">
<div class="contact-form-card">
<span class="eyebrow">CONTACT RDI</span>
<h2>Send us a message</h2>
<p>Messages appear in the admin Contact Inbox. When a server email endpoint is configured, the same submission can also be emailed automatically.</p>
<form class="contact-form-grid" id="rdiContactForm">
<div><input name="name" placeholder="Your name" required=""/></div>
<div><input name="email" placeholder="Email address" required="" type="email"/></div>
<div class="full"><input name="subject" placeholder="Subject" required=""/></div>
<div class="full"><textarea name="message" placeholder="Tell us about your project or question" required=""></textarea></div>
<div class="full"><button class="btn btn--primary" type="submit">Send Message</button> <span class="inline-gap-left" id="contactFormStatus"></span></div>
</form>
</div>
</div>
</section>
</main>
 ========== FOOTER ========== 
<footer class="footer">
<div class="container footer__grid">
<div class="footer__brand">
<a class="header__brand" href="#home">
<span aria-hidden="true" class="brand-mark">
<svg fill="none" height="28" viewbox="0 0 32 32" width="28">
<path d="M16 3a9 9 0 00-5 16.5V23a2 2 0 002 2h6a2 2 0 002-2v-3.5A9 9 0 0016 3z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
<path d="M13 28h6M14 25.5h4" stroke="currentColor" stroke-linecap="round" stroke-width="2"></path>
</svg>
</span>
<span class="brand-text">
<strong>RDI</strong>
<small>INNOVATORS</small>
</span>
</a>
<p>Research and Development Innovators (RDI) is a global remote research organization dedicated to innovation, research and real-world impact.</p>
<div class="footer__socials">
<a aria-label="LinkedIn" class="social-icon" href="#"><svg fill="none" height="18" viewbox="0 0 24 24" width="18"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"></path><circle cx="4" cy="4" r="2" stroke="currentColor" stroke-width="1.6"></circle></svg></a>
<a aria-label="Twitter" class="social-icon" href="#"><svg fill="none" height="18" viewbox="0 0 24 24" width="18"><path d="M22 4s-2 2-4 2.5C16.5 5 14 5 13 7v2C8 9 4 5 4 5s-2 8 6 12c-2 2-5 2-7 2 8 4 18 0 18-12 0-.5 2-3 1-3z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.6"></path></svg></a>
<a aria-label="YouTube" class="social-icon" href="#"><svg fill="none" height="18" viewbox="0 0 24 24" width="18"><path d="M22.5 6.4a2.8 2.8 0 00-2-2C18.9 4 12 4 12 4s-6.9 0-8.5.4a2.8 2.8 0 00-2 2A29 29 0 001 12a29 29 0 00.5 5.6 2.8 2.8 0 002 2C5.1 20 12 20 12 20s6.9 0 8.5-.4a2.8 2.8 0 002-2A29 29 0 0023 12a29 29 0 00-.5-5.6z" stroke="currentColor" stroke-width="1.6"></path><polygon points="9.75 15 15.5 12 9.75 9 9.75 15" stroke="currentColor" stroke-linejoin="round" stroke-width="1.6"></polygon></svg></a>
<a aria-label="GitHub" class="social-icon" href="#"><svg fill="none" height="18" viewbox="0 0 24 24" width="18"><path d="M9 19c-4 1.5-4-2-6-2.5M15 22v-3.9a3.4 3.4 0 00-1-2.6c3.4-.4 6-2 6-7a5.5 5.5 0 00-1.5-3.8 5 5 0 00-.1-3.8s-1.2-.4-4 1.5a13.3 13.3 0 00-7 0c-2.8-1.9-4-1.5-4-1.5a5 5 0 00-.1 3.8A5.5 5.5 0 003 11.5c0 4.9 2.6 6.6 6 7a3.4 3.4 0 00-1 2.6V22" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"></path></svg></a>
</div>
</div>
<div class="footer__col">
<h5>Quick Links</h5>
<ul>
<li><a href="#home">Home</a></li>
<li><a href="#about">About</a></li>
<li><a href="#services">Services</a></li>
<li><a href="#opportunities">Opportunities</a></li>
<li><a href="#workflow">Workflow</a></li>
</ul>
</div>
<div class="footer__col">
<h5>Resources</h5>
<ul>
<li><a href="#experts">For Experts</a></li>
<li><a href="#">Publications</a></li>
<li><a href="#">Peer Review</a></li>
<li><a href="#">Careers</a></li>
<li><a href="#contact">Contact</a></li>
</ul>
</div>
<div class="footer__col">
<h5>Contact Us</h5>
<ul class="footer__contact">
<li>
<svg fill="none" height="16" viewbox="0 0 24 24" width="16"><path d="M2 6a2 2 0 012-2h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.6"></path><path d="M2 6l10 7 10-7" stroke="currentColor" stroke-linecap="round" stroke-width="1.6"></path></svg>
            info.rd.innovators@gmail.com
          </li>
<li>
<svg fill="none" height="16" viewbox="0 0 24 24" width="16"><path d="M12 22s8-7 8-13a8 8 0 10-16 0c0 6 8 13 8 13z" stroke="currentColor" stroke-linejoin="round" stroke-width="1.6"></path><circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.6"></circle></svg>
            We are global &amp; remote
          </li>
<li>
<svg fill="none" height="16" viewbox="0 0 24 24" width="16"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"></circle><path d="M12 7v5l3 2" stroke="currentColor" stroke-linecap="round" stroke-width="1.6"></path></svg>
            Available 24/7
          </li>
</ul>
</div>
</div>
<div class="footer__bottom container">
<p>© <span id="year"></span> Research and Development Innovators (RDI). All rights reserved.</p>
<p>Making Ideas Matter <span aria-hidden="true" class="heart">❤</span></p>
</div>
</footer>

</div>
  <div class="app-view" data-view="gallery" hidden><header class="header is-scrolled"><div class="container header__inner"><a class="header__brand" href="#home"><span class="brand-mark">R</span><span class="brand-text"><strong>RDI</strong><small>INNOVATORS</small></span></a><nav class="nav is-open public-nav-static"><a class="nav__link" href="#home">Home</a><a class="nav__link is-active" href="#gallery">Gallery</a><a class="nav__link" href="#blog">Blog</a><a class="nav__link" href="#research-news">Research News</a><a class="login-link" href="#login">Login</a></nav></div></header><main><section class="public-hero"><div class="container"><span class="eyebrow">RDI PROJECTS &amp; UPDATES</span><h1>Gallery</h1><p>Explore project highlights, research activity, prototypes, events and RDI updates managed from the admin panel.</p></div></section><section><div class="container public-grid" id="galleryGrid"></div></section></main></div>
  <div class="app-view" data-view="blog" hidden><header class="header is-scrolled"><div class="container header__inner"><a class="header__brand" href="#home"><span class="brand-mark">R</span><span class="brand-text"><strong>RDI</strong><small>INNOVATORS</small></span></a><nav class="nav is-open public-nav-static"><a class="nav__link" href="#home">Home</a><a class="nav__link" href="#gallery">Gallery</a><a class="nav__link is-active" href="#blog">Blog</a><a class="nav__link" href="#research-news">Research News</a><a class="login-link" href="#login">Login</a></nav></div></header><main><section class="public-hero"><div class="container"><span class="eyebrow">RDI KNOWLEDGE</span><h1>Blog</h1><p>Published and automatically scheduled articles from the RDI admin panel.</p></div></section><section><div class="container blog-layout"><div class="blog-list" id="blogList"></div><aside class="news-list"><div class="news-item"><span class="eyebrow">AUTOMATION</span><h3>Scheduled publishing</h3><p>Posts marked “scheduled” become public automatically after their publish time when the site loads.</p></div></aside></div></section></main></div>
  <div class="app-view" data-view="research-news" hidden><header class="header is-scrolled"><div class="container header__inner"><a class="header__brand" href="#home"><span class="brand-mark">R</span><span class="brand-text"><strong>RDI</strong><small>INNOVATORS</small></span></a><nav class="nav is-open public-nav-static"><a class="nav__link" href="#home">Home</a><a class="nav__link" href="#gallery">Gallery</a><a class="nav__link" href="#blog">Blog</a><a class="nav__link is-active" href="#research-news">Research News</a><a class="login-link" href="#login">Login</a></nav></div></header><main><section class="public-hero"><div class="container"><span class="eyebrow">RDI RESEARCH MONITOR</span><h1>Research News</h1><p>Approved research-news items generated/imported through the admin automation workflow.</p></div></section><section><div class="container news-list news-page-list" id="newsList"></div></section></main></div>
  <div class="app-view admin-route" data-view="signup" hidden><main class="login-page"><section class="login-card auth-card-wide"><a class="admin-brand" href="#home"><i>R</i><div><b>RDI</b><span>CONTROLLED REGISTRATION</span></div></a><h1>RDI Member Sign Up</h1><p>Use the one-time Member ID and exact approved email issued by an RDI administrator.</p><div class="notice">You cannot register freely. The Member ID and approved email must match the admin-issued invitation.</div><form id="signupForm" class="form-grid"><div class="field"><label>RDI Member ID</label><input name="memberId" required placeholder="RDI-2026-XXXXXX" autocomplete="off"></div><div class="field"><label>Approved RDI email</label><input name="email" type="email" required placeholder="username.rdi@gmail.com"></div><div class="field full"><label>Full name</label><input name="name" required></div><div class="field"><label>Password</label><input name="password" type="password" minlength="8" required></div><div class="field"><label>Confirm password</label><input name="confirmPassword" type="password" minlength="8" required></div><div class="full error" id="signupStatus"></div><div class="full"><button class="btnx primary" type="submit">Create RDI Account</button> <a class="btnx" href="#login">Back to Login</a></div></form></section></main></div>

  <div class="app-view admin-route" data-view="forgot" hidden><main class="login-page"><section class="login-card"><a class="admin-brand" href="#home"><i>R</i><div><b>RDI</b><span>ACCOUNT RECOVERY</span></div></a><h1>Forgot Password</h1><p>Enter your approved RDI email. A single-use expiring reset link will be created and emailed when server mail is configured.</p><form id="forgotForm"><div class="field"><label>Email</label><input id="forgotEmail" required type="email"></div><div class="error" id="forgotStatus"></div><button class="btnx primary" type="submit">Request Reset Link</button><a class="btnx ghost back-link-center" href="#login">Back to Login</a></form></section></main></div>

  <div class="app-view admin-route" data-view="reset" hidden><main class="login-page"><section class="login-card"><a class="admin-brand" href="#home"><i>R</i><div><b>RDI</b><span>ACCOUNT RECOVERY</span></div></a><h1>Reset Password</h1><p>Reset links are single-use and expire automatically.</p><form id="resetForm"><div class="field"><label>New password</label><input id="resetPassword" required minlength="8" type="password"></div><div class="field"><label>Confirm password</label><input id="resetConfirm" required minlength="8" type="password"></div><div class="error" id="resetStatus"></div><button class="btnx primary" type="submit">Reset Password</button><a class="btnx ghost back-link-center" href="#login">Back to Login</a></form></section></main></div>

  <div class="app-view admin-route" data-view="login" hidden><main class="login-page"><section class="login-card"><a class="admin-brand" href="#home"><i>R</i><div><b>RDI</b><span>INNOVATORS</span></div></a><h1>Team Login</h1><p>Sign in with your approved RDI account.</p><div class="demo"><b>RDI controlled access</b><br/>Use the approved account created from your RDI Member ID. Local seed credentials are configured in <code>.env</code>, not in the page source.</div><form id="loginForm"><div class="field"><label>Email</label><input id="email" required type="email" autocomplete="username"/></div><div class="field"><label>Password</label><input id="password" required type="password" autocomplete="current-password"/></div><div class="error" id="loginError"></div><button class="btnx primary" type="submit">Log In</button><div class="auth-links"><a href="#forgot">Forgot password?</a><a href="#signup">Have an RDI Member ID?</a></div><a class="btnx ghost back-link-center" href="#home">Back to Website</a></form></section></main></div>

  <div class="app-view admin-route" data-view="admin" hidden><div class="admin-shell"><aside class="admin-sidebar"><a class="admin-brand" href="#home"><i>R</i><div><b>RDI</b><span>MEMBER PORTAL</span></div></a><nav class="admin-nav" id="adminNav"><div class="nav-sep">Workspace</div><button class="active" data-tab="dashboard">Dashboard</button><button data-tab="notifications">Notifications <span class="nav-count" id="notificationNavCount" hidden></span></button><button class="perm-view-projects" data-tab="projects">Projects</button><button data-tab="tasks">Task Management</button><button data-tab="profile">My Profile</button><button data-tab="hr-help">HR Help Bot</button><div class="nav-sep perm-view-users">Identity &amp; Access</div><button class="perm-view-users" data-tab="users">User Management</button><button class="perm-create-member-ids" data-tab="member-ids">RDI Member IDs</button><button class="perm-manage-roles" data-tab="rbac">Roles &amp; Permissions</button><button class="perm-view-audit-logs" data-tab="audit">Audit Logs</button><button class="perm-manage-hr-requests" data-tab="hr-admin">HR Requests</button><div class="nav-sep perm-manage-content">Content</div><button class="perm-manage-content" data-tab="gallery">Gallery</button><button class="perm-manage-content" data-tab="blogs">Automatic Blog</button><button class="perm-manage-research-news" data-tab="news">Research News</button><button class="perm-manage-content" data-tab="homepage">Homepage Customize</button><button class="perm-manage-content" data-tab="messages">Contact Inbox</button><div class="nav-sep perm-manage-settings">System</div><button class="perm-manage-settings" data-tab="settings">System Settings</button><a href="#home" target="_blank">View Website ↗</a></nav></aside>
<main class="admin-main"><header class="admin-topbar"><div><h1 id="pageTitle">Dashboard</h1><p id="pageSub">RDI identity, projects, tasks and services workspace.</p></div><div class="admin-user"><div class="notification-center"><button class="notification-bell" id="notificationBell" type="button" aria-label="Notifications" aria-expanded="false"><svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20" fill="none"><path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="notification-badge" id="notificationUnreadBadge" hidden>0</span></button><div class="notification-dropdown" id="notificationDropdown" hidden><div class="notification-dropdown__head"><b>Notifications</b><button class="link-button" id="notificationMarkAllTop" type="button">Mark all read</button></div><div class="notification-dropdown__list" id="notificationRecentList"><div class="notification-empty">Loading…</div></div><button class="notification-view-all" id="notificationViewAll" type="button">View all notifications</button></div></div><span class="pill" id="userRole"></span><b id="userName"></b><button class="btnx small" id="logoutBtn">Logout</button></div></header>
<section class="admin-panel active" id="dashboard"><div class="stats-grid" id="stats"></div><div class="grid-2"><div class="cardx"><h3>Account / Automation Status</h3><div id="automationStatus"></div></div><div class="cardx"><h3>Recent Activity</h3><div id="recentActivity"></div></div></div></section>
<section class="admin-panel" id="notifications"><div class="toolbar"><div><h2>Notifications</h2><p class="pill">Your RDI activity alerts</p></div><div class="notification-actions"><button class="btnx" id="notificationRefreshBtn" type="button">Refresh</button><button class="btnx primary" id="notificationMarkAllBtn" type="button">Mark all read</button></div></div><div class="notification-filters"><select id="notificationStatusFilter" aria-label="Notification status"><option value="all">All notifications</option><option value="unread">Unread</option><option value="read">Read</option></select><select id="notificationTypeFilter" aria-label="Notification type"><option value="">All types</option><option value="task_assigned">Task assigned</option><option value="deadline_approaching">Deadline reminder</option><option value="hr_approved">HR approved</option><option value="hr_rejected">HR rejected</option><option value="member_id_created">Member ID created</option><option value="account_status">Account status</option><option value="project_assignment">Project assignment</option><option value="research_update">Research update</option></select></div><div class="notification-page-list" id="notificationPageList"><div class="notification-empty">Loading notifications…</div></div></section><section class="admin-panel perm-view-projects" id="projects"><div class="toolbar"><div><h2>Project Management</h2><p class="pill">Permission-scoped projects, members, tasks, resources and discussion</p></div><button class="btnx primary perm-manage-projects" id="addProjectBtn" type="button">+ New Project</button></div><div id="projectFormWrap"></div><div class="project-workspace"><aside class="project-list-panel"><div class="project-list-head"><input id="projectSearch" placeholder="Search projects" aria-label="Search projects"><select id="projectStatusFilter" aria-label="Project status"><option value="">All statuses</option><option>Planned</option><option>Active</option><option>On Hold</option><option>Completed</option><option>Cancelled</option></select></div><div id="projectList" class="project-list"><div class="notification-empty">Loading projects…</div></div></aside><div id="projectDetail" class="project-detail"><div class="empty-state cardx">Select a project to view its workspace.</div></div></div></section>
<section class="admin-panel" id="tasks"><div class="toolbar"><div><h2>RDI Task Management</h2><p class="pill">Project/account-linked Kanban</p></div><button class="btnx primary perm-manage-tasks" id="addTaskBtn">+ Add Task</button></div><div id="taskFormWrap"></div><div class="kanban" id="taskBoard"></div></section>
<section class="admin-panel" id="profile"><div class="grid-2"><div class="cardx"><h2>My RDI Profile</h2><form class="form-grid" id="profileForm"><div class="field"><label>RDI Member ID</label><input id="profileMemberId" disabled></div><div class="field"><label>Approved email</label><input id="profileEmail" disabled></div><div class="field full"><label>Full name</label><input id="profileName" required></div><div class="field"><label>Department</label><input id="profileDepartment"></div><div class="field"><label>Team</label><input id="profileTeam"></div><div class="field full"><label>Profile image URL / path</label><input id="profileImage"></div><div class="field"><label>Role</label><input id="profileRole" disabled></div><div class="field"><label>Status</label><input id="profileStatus" disabled></div><div class="full"><button class="btnx primary">Save Profile</button></div></form></div><div class="cardx"><h2>Change Password</h2><form id="changePasswordForm"><div class="field"><label>Current password</label><input id="currentPassword" type="password" required></div><div class="field"><label>New password</label><input id="newPassword" type="password" minlength="8" required></div><div class="field"><label>Confirm new password</label><input id="newPasswordConfirm" type="password" minlength="8" required></div><div class="error" id="passwordChangeStatus"></div><button class="btnx primary">Change Password</button></form></div></div></section>
<section class="admin-panel" id="hr-help"><div class="grid-2"><div class="cardx"><span class="eyebrow">HR HELP BOT</span><h2>Request an HR Document</h2><p>Select the document you need. The request goes to RDI administration for acceptance, review and approval.</p><form id="hrRequestForm"><div class="field"><label>Document</label><select id="hrDocumentType"><option>Salary Certificate</option><option>Payslip</option><option>Employment Certificate</option><option>Certificate</option><option>NOC</option><option>NDA</option><option>Joining Letter</option></select></div><div class="field"><label>Purpose / details for HR review</label><textarea id="hrRequestNote" placeholder="Purpose, month, institution, special wording, etc."></textarea></div><button class="btnx primary">Send HR Request</button></form></div><div class="cardx"><h3>How it works</h3><div class="controlled-steps light"><div><b>1</b><span>You request a document.</span></div><div><b>2</b><span>Admin accepts and reviews the request.</span></div><div><b>3</b><span>After approval, the document becomes available to open/print.</span></div></div></div></div><div class="table-wrap top-gap"><table class="tablex"><thead><tr><th>Document</th><th>Status</th><th>Requested</th><th>Admin note</th><th>Document</th></tr></thead><tbody id="myHrRows"></tbody></table></div></section>
<section class="admin-panel perm-view-users" id="users"><div class="toolbar"><div><h2>User Management</h2><span class="pill">RBAC · status · soft delete / restore</span></div></div><div class="filter-row"><input id="userSearch" placeholder="Search name, email or RDI ID"><select id="userRoleFilter"><option value="">All roles</option></select><select id="userStatusFilter"><option value="">All statuses</option><option>Pending</option><option>Active</option><option>Deactivated</option><option>Locked</option><option>Deleted</option></select></div><div class="table-wrap"><table class="tablex"><thead><tr><th>User</th><th>RDI ID / Team</th><th>Role</th><th>Status</th><th>Permissions</th><th>Actions</th></tr></thead><tbody id="userRows"></tbody></table></div><div id="userEditor"></div></section>
<section class="admin-panel perm-create-member-ids" id="member-ids"><div class="grid-2"><div class="cardx"><h2>Generate RDI Member ID</h2><p>Each Member ID is linked to one approved email and can be used once.</p><form class="form-grid" id="memberIdForm"><div class="field full"><label>Approved RDI email</label><input id="inviteEmail" type="email" required placeholder="username.rdi@gmail.com"></div><div class="field"><label>Initial role</label><select id="inviteRole"></select></div><div class="field"><label>Custom Member ID (optional)</label><input id="inviteMemberId" placeholder="Auto-generate if blank"></div><div class="field"><label>Department</label><input id="inviteDepartment"></div><div class="field"><label>Team</label><input id="inviteTeam"></div><div class="full"><button class="btnx primary">Generate Member ID</button></div></form></div><div class="cardx"><h3>Registration rule</h3><p>Signup succeeds only when the submitted Member ID and email exactly match an unused invitation. Used IDs cannot be reused.</p><p>Current email format: <b id="emailFormatHint"></b></p></div></div><div class="table-wrap top-gap"><table class="tablex"><thead><tr><th>Member ID</th><th>Approved email</th><th>Role / Team</th><th>Status</th><th>Created</th><th>Action</th></tr></thead><tbody id="memberIdRows"></tbody></table></div></section>
<section class="admin-panel perm-manage-roles" id="rbac"><div class="toolbar"><div><h2>Roles &amp; Permissions</h2><span class="pill">Independent RBAC permission templates</span></div></div><div id="rbacMatrix"></div></section>
<section class="admin-panel perm-view-audit-logs" id="audit"><div class="toolbar"><h2>Audit Logs</h2><span class="pill">Latest 200 identity/admin activities</span></div><div class="table-wrap"><table class="tablex"><thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Entity</th><th>Details</th></tr></thead><tbody id="auditRows"></tbody></table></div></section>
<section class="admin-panel perm-manage-hr-requests" id="hr-admin"><div class="toolbar"><h2>HR Requests</h2><span class="pill" id="hrAdminCount"></span></div><div class="table-wrap"><table class="tablex"><thead><tr><th>Member</th><th>Document / Note</th><th>Status</th><th>Requested</th><th>Admin note</th><th>Actions</th></tr></thead><tbody id="hrAdminRows"></tbody></table></div></section>
<section class="admin-panel perm-manage-content" id="gallery"><div class="toolbar"><h2>Gallery — Projects &amp; Updates</h2><button class="btnx primary" id="addGalleryBtn">+ Add Gallery Item</button></div><div id="galleryFormWrap"></div><div class="table-wrap"><table class="tablex"><thead><tr><th>Preview</th><th>Title</th><th>Category</th><th>Date</th><th>Action</th></tr></thead><tbody id="galleryRows"></tbody></table></div></section>
<section class="admin-panel perm-manage-content" id="blogs"><div class="toolbar"><div><h2>Automatic Blog</h2><span class="pill">Scheduled publishing</span></div><button class="btnx primary" id="addBlogBtn">+ Create Blog</button></div><div id="blogFormWrap"></div><div class="table-wrap"><table class="tablex"><thead><tr><th>Title</th><th>Status</th><th>Publish time</th><th>Created</th><th>Action</th></tr></thead><tbody id="blogRows"></tbody></table></div></section>
<section class="admin-panel perm-manage-research-news" id="news"><div class="toolbar"><div><h2>Automatic Research News</h2><span class="pill">arXiv primary · Crossref fallback · MySQL deduplication</span></div><div><button class="btnx" id="diagnoseNewsBtn" type="button">Run Diagnostics</button> <button class="btnx primary" id="generateNewsBtn" type="button">Run Automation Now</button></div></div><div class="notice" id="researchNewsNotice">Use diagnostics to test PHP, database and research sources.</div><div class="table-wrap"><table class="tablex"><thead><tr><th>News</th><th>Category</th><th>Source</th><th>Approval</th><th>Action</th></tr></thead><tbody id="newsRows"></tbody></table></div></section>
<section class="admin-panel perm-manage-content" id="homepage"><div class="toolbar"><div><h2>Homepage Customize</h2><span class="pill">Banner / title / description / image / video / carousel</span></div><button class="btnx primary" id="addSectionBtn">+ Add Section</button></div><div id="sectionFormWrap"></div><div class="table-wrap"><table class="tablex"><thead><tr><th>Type</th><th>Title</th><th>Media</th><th>Enabled</th><th>Action</th></tr></thead><tbody id="sectionRows"></tbody></table></div></section>
<section class="admin-panel perm-manage-content" id="messages"><div class="toolbar"><h2>Contact Us Inbox</h2><span class="pill" id="messageCount"></span></div><div class="table-wrap"><table class="tablex"><thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Email status</th><th>Date</th><th>Action</th></tr></thead><tbody id="messageRows"></tbody></table></div></section>
<section class="admin-panel perm-manage-settings" id="settings"><div class="cardx"><h2>System &amp; Automation Settings</h2><form class="form-grid" id="settingsForm"><div class="field"><label>Contact destination email</label><input id="contactEmail" type="email"/></div><div class="field"><label>Contact form endpoint (optional)</label><input id="contactEndpoint" placeholder="https://your-server/api/contact"/></div><div class="field"><label>RDI email format example</label><input id="rdiEmailFormat" placeholder="username.rdi@gmail.com"/></div><div class="field"><label>RDI email domain</label><input id="rdiEmailDomain" placeholder="gmail.com"/></div><div class="field"><label>Password reset expiry (minutes)</label><input id="passwordResetMinutes" type="number" min="10" max="120" value="30"/></div><div class="field full"><label>Research categories</label><input id="newsCategories" placeholder="cs.AI,cs.LG,cs.CV,cs.CL,cs.RO"/></div><div class="field"><label>Research automation interval (hours)</label><input id="newsAutomationIntervalHours" type="number" min="1" max="168" value="6"/></div><div class="field"><label>Maximum new items per run</label><input id="newsMaxItems" type="number" min="1" max="10" value="5"/></div><label class="switch full"><input id="autoApproveNews" type="checkbox"/> Auto-approve imported research news</label><div class="notice full">RDI registration verifies the exact admin-approved email stored against the one-time Member ID. The email format/domain setting is configurable for a future custom RDI domain.</div><div class="full"><button class="btnx primary" type="submit">Save Settings</button></div></form></div></section>
</main></div><div class="toast" id="toast"></div></div>
  <script>window.RDI_INITIAL_STATE = <?= json_encode($initialState, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>; window.RDI_CURRENT_USER = <?= json_encode($u ?? null, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>;</script>
  <script src="js/script.js"></script>
</body>
</html>