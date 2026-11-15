<?php
require_once __DIR__ . '/../includes/research_news.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "CLI only. Use api/research-news.php?auto=1 for web-triggered automation.\n";
    exit;
}

try {
    $result = run_research_news_automation(false);
    if(!empty($result['ran'])) audit_log('research_news_cron','research_news',null,'Imported '.(isset($result['imported'])?$result['imported']:0).' item(s)',null);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Research news automation failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
