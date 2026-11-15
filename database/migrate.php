<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Run migrations from the command line.\n"); }
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/migration_lib.php';
try{
    $applied=rdi_run_migrations(db(),__DIR__.'/migrations');
    if(!$applied){echo "No pending migrations.\n";exit(0);}
    foreach($applied as $m)echo "Applied: ".$m."\n";
}catch(Throwable $e){fwrite(STDERR,"Migration failed: ".$e->getMessage()."\n");exit(1);}
