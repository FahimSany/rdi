<?php
require_once __DIR__ . '/../includes/research_news.php';
$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

if ($method === 'GET' && isset($_GET['diagnostics'])) {
    require_permission('manage_research_news');
    json_response(array('ok'=>true,'diagnostics'=>rdi_research_news_diagnostics(true)));
}

if ($method === 'POST') {
    $actor=require_permission('manage_research_news');
    try {
        $result = run_research_news_automation(true);
        audit_log('research_news_automation_run','research_news',null,'Imported '.(isset($result['imported'])?$result['imported']:0).' item(s)',$actor);
        json_response($result);
    } catch (Throwable $e) {
        /* Admin-only endpoint: return actionable operational details, never DB credentials/API keys. */
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
        $result = run_research_news_automation(false);
        if(!empty($result['ran']))audit_log('research_news_automation_auto','research_news',null,'Imported '.(isset($result['imported'])?$result['imported']:0).' item(s)',$actor);
        json_response($result);
    } catch (Throwable $e) {
        json_response(array('ok'=>false,'error'=>'Research automation failed: '.$e->getMessage()),500);
    }
}

json_response(array('error'=>'Method not allowed'),405);
