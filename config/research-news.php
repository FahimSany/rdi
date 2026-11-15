<?php
require_once __DIR__ . '/../includes/env.php';
rdi_env_load(__DIR__ . '/../.env');
return array(
    'provider' => rdi_env('RDI_NEWS_AI_PROVIDER', 'none'),
    'gemini_api_key' => rdi_env('RDI_GEMINI_API_KEY', ''),
    'gemini_model' => rdi_env('RDI_GEMINI_MODEL', 'gemini-2.5-flash'),
    'deepseek_api_key' => rdi_env('RDI_DEEPSEEK_API_KEY', ''),
    'deepseek_model' => rdi_env('RDI_DEEPSEEK_MODEL', 'deepseek-v4-flash'),
    'http_timeout' => (int)rdi_env('RDI_NEWS_HTTP_TIMEOUT', '25'),
    'public_feed_allow_insecure_ssl_fallback' => rdi_env_bool('RDI_PUBLIC_FEED_INSECURE_SSL_FALLBACK', false),
);
