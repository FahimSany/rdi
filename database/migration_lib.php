<?php
function rdi_sql_statements($sql) {
    $parts=preg_split('/;\s*(?:\r?\n|$)/',trim((string)$sql));
    $out=array();
    foreach($parts as $part){$part=trim($part);if($part!==''&&!preg_match('/^--/', $part))$out[]=$part;}
    return $out;
}

function rdi_run_migrations($pdo, $dir) {
    $st=$pdo->prepare("CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $st->execute();
    $st=$pdo->prepare("SELECT migration FROM schema_migrations");$st->execute();
    $done=array();foreach($st->fetchAll(PDO::FETCH_COLUMN) as $m)$done[$m]=true;
    $files=array_merge(glob(rtrim($dir,'/\\').'/*.sql')?:array(),glob(rtrim($dir,'/\\').'/*.php')?:array());sort($files,SORT_STRING);$applied=array();
    foreach($files as $file){
        $name=basename($file);if(isset($done[$name]))continue;
        try{
            if(substr($file,-4)==='.php'){
                $migration=require $file;
                if(!is_callable($migration))throw new RuntimeException('PHP migration must return a callable: '.$name);
                call_user_func($migration,$pdo);
            }else{
                $sql=file_get_contents($file);if($sql===false)throw new RuntimeException('Could not read migration '.$name);
                foreach(rdi_sql_statements($sql) as $statement)$pdo->prepare($statement)->execute();
            }
            $ins=$pdo->prepare("INSERT INTO schema_migrations(migration) VALUES(?)");$ins->execute(array($name));
            $applied[]=$name;
        }catch(Throwable $e){throw new RuntimeException('Migration '.$name.' failed: '.$e->getMessage(),0,$e);}
    }
    return $applied;
}
