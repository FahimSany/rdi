<?php
require_once __DIR__ . '/../includes/env.php';
rdi_env_load(__DIR__ . '/../.env');
return array(
  'host' => rdi_env('RDI_DB_HOST'),
  'port' => rdi_env('RDI_DB_PORT'),
  'name' => rdi_env('RDI_DB_NAME'),
  'user' => rdi_env('RDI_DB_USER'),
  'pass' => rdi_env('RDI_DB_PASS', ''),
  'charset' => rdi_env('RDI_DB_CHARSET', 'utf8mb4'),
);
