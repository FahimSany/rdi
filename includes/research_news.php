<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/notifications.php';

function rdi_news_config() {
    $file = __DIR__ . '/../config/research-news.php';
    $cfg = is_file($file) ? require $file : array();
    return array_merge(array(
        'provider' => 'none',
        'gemini_api_key' => '',
        'gemini_model' => 'gemini-2.5-flash',
        'deepseek_api_key' => '',
        'deepseek_model' => 'deepseek-v4-flash',
        'http_timeout' => 25,
        /* Last-resort TLS fallback is used ONLY for public research metadata feeds.
           It is never used for Gemini/DeepSeek requests that carry secret API keys. */
        'public_feed_allow_insecure_ssl_fallback' => true,
    ), is_array($cfg) ? $cfg : array());
}

function rdi_http_request($url, $method, $headers, $body, $timeout, $allowInsecureSslFallback = false) {
    $method = strtoupper($method ?: 'GET');
    $headers = is_array($headers) ? $headers : array();
    $timeout = max(5, (int)$timeout);

    if (function_exists('curl_init')) {
        $attempt = function ($verifySsl) use ($url, $method, $headers, $body, $timeout) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(10, $timeout));
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_USERAGENT, 'RDIResearchMonitor/2.0 (+https://rdi.local)');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl ? true : false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);
            if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $text = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno = curl_errno($ch);
            $err = curl_error($ch);
            curl_close($ch);
            return array($text, $status, $errno, $err);
        };

        list($text, $status, $errno, $err) = $attempt(true);
        if ($text === false && $allowInsecureSslFallback && stripos($url, 'https://') === 0 && in_array((int)$errno, array(35, 51, 58, 60, 77, 83), true)) {
            error_log('[RDI research feed] TLS verification failed; retrying public metadata feed without certificate verification. Fix the PHP CA bundle for production. cURL ' . $errno . ': ' . $err);
            list($text, $status, $errno, $err) = $attempt(false);
        }
        if ($text === false) {
            throw new RuntimeException('Network request failed (cURL ' . (int)$errno . '): ' . ($err ?: 'unknown network error'));
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Remote service returned HTTP ' . $status . ' for ' . parse_url($url, PHP_URL_HOST) . '.');
        }
        return $text;
    }

    if (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $request = function ($verifySsl) use ($url, $method, $headers, $body, $timeout) {
            $headerText = "User-Agent: RDIResearchMonitor/2.0\r\n";
            if ($headers) $headerText .= implode("\r\n", $headers) . "\r\n";
            $opts = array(
                'http' => array(
                    'method' => $method,
                    'timeout' => $timeout,
                    'ignore_errors' => true,
                    'header' => $headerText,
                ),
                'ssl' => array(
                    'verify_peer' => $verifySsl ? true : false,
                    'verify_peer_name' => $verifySsl ? true : false,
                ),
            );
            if ($body !== null) $opts['http']['content'] = $body;
            $ctx = stream_context_create($opts);
            $text = @file_get_contents($url, false, $ctx);
            $headersOut = isset($http_response_header) ? $http_response_header : array();
            return array($text, $headersOut);
        };

        list($text, $headersOut) = $request(true);
        if ($text === false && $allowInsecureSslFallback && stripos($url, 'https://') === 0) {
            error_log('[RDI research feed] HTTPS stream failed; retrying public metadata feed without certificate verification. Fix the PHP CA bundle for production.');
            list($text, $headersOut) = $request(false);
        }
        if ($text === false) throw new RuntimeException('Network request failed. Enable PHP cURL or allow_url_fopen and verify the PHP CA bundle.');
        $status = 200;
        if (is_array($headersOut)) {
            foreach ($headersOut as $line) {
                if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $line, $m)) $status = (int)$m[1];
            }
        }
        if ($status < 200 || $status >= 300) throw new RuntimeException('Remote service returned HTTP ' . $status . ' for ' . parse_url($url, PHP_URL_HOST) . '.');
        return $text;
    }

    throw new RuntimeException('No outbound HTTP client is available. Enable the PHP cURL extension (recommended) or allow_url_fopen.');
}

function rdi_setting_map() {
    $out = array();
    $st=db()->prepare("SELECT setting_key, setting_value FROM settings");$st->execute();foreach ($st->fetchAll() as $r) $out[$r['setting_key']] = $r['setting_value'];
    return $out;
}

function rdi_set_setting($key, $value) {
    $st = db()->prepare("INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $st->execute(array($key, (string)$value));
}

function rdi_clean_text($text) {
    $text = html_entity_decode(strip_tags((string)$text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

function rdi_truncate_text($text, $limit) {
    $text = rdi_clean_text($text);
    if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $limit) return rtrim(mb_substr($text, 0, $limit - 1, 'UTF-8')) . '…';
    if (strlen($text) > $limit) return rtrim(substr($text, 0, $limit - 1)) . '…';
    return $text;
}

function rdi_news_category_label($term) {
    $map = array(
        'cs.AI' => 'Artificial Intelligence', 'cs.LG' => 'Machine Learning', 'cs.CV' => 'Computer Vision',
        'cs.CL' => 'Natural Language Processing', 'cs.RO' => 'Robotics', 'cs.CR' => 'Cybersecurity',
        'eess.SP' => 'Signal Processing', 'stat.ML' => 'Machine Learning'
    );
    return isset($map[$term]) ? $map[$term] : ($term ?: 'Research');
}

function rdi_guess_category($text) {
    $x = strtolower((string)$text);
    if (strpos($x, 'computer vision') !== false || strpos($x, 'image ') !== false || strpos($x, 'vision') !== false) return 'Computer Vision';
    if (strpos($x, 'natural language') !== false || strpos($x, 'language model') !== false || strpos($x, 'nlp') !== false) return 'Natural Language Processing';
    if (strpos($x, 'robot') !== false) return 'Robotics';
    if (strpos($x, 'cyber') !== false || strpos($x, 'security') !== false) return 'Cybersecurity';
    if (strpos($x, 'machine learning') !== false || strpos($x, 'deep learning') !== false || strpos($x, 'neural') !== false) return 'Machine Learning';
    if (strpos($x, 'artificial intelligence') !== false || strpos($x, ' ai ') !== false) return 'Artificial Intelligence';
    return 'Research';
}

function rdi_parse_arxiv_atom_regex($xmlText) {
    $items = array();
    if (!preg_match_all('~<entry(?:\s[^>]*)?>(.*?)</entry>~si', $xmlText, $entries)) return $items;
    foreach ($entries[1] as $chunk) {
        $tag = function ($name) use ($chunk) {
            if (preg_match('~<' . preg_quote($name, '~') . '(?:\s[^>]*)?>(.*?)</' . preg_quote($name, '~') . '>~si', $chunk, $m)) return rdi_clean_text($m[1]);
            return '';
        };
        $idUrl = $tag('id');
        $title = $tag('title');
        if ($idUrl === '' || $title === '') continue;
        $abstract = $tag('summary');
        $published = $tag('published');
        $category = '';
        if (preg_match('~<arxiv:primary_category[^>]*term=["\']([^"\']+)["\']~i', $chunk, $m) || preg_match('~<category[^>]*term=["\']([^"\']+)["\']~i', $chunk, $m)) $category = $m[1];
        $authors = array();
        if (preg_match_all('~<author(?:\s[^>]*)?>.*?<name(?:\s[^>]*)?>(.*?)</name>.*?</author>~si', $chunk, $am)) {
            foreach ($am[1] as $a) { $a = rdi_clean_text($a); if ($a !== '') $authors[] = $a; if (count($authors) >= 3) break; }
        }
        $items[] = array(
            'id'=>'arxiv-' . substr(sha1($idUrl),0,24), 'title'=>$title,
            'source'=>'arXiv' . ($authors ? ' · ' . implode(', ', $authors) : ''), 'url'=>$idUrl,
            'summary'=>rdi_truncate_text($abstract,700), 'abstract'=>$abstract,
            'category'=>rdi_news_category_label($category), 'publishedAt'=>$published
        );
    }
    return $items;
}

function rdi_parse_arxiv_atom($xmlText) {
    if (function_exists('simplexml_load_string')) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlText);
        if ($xml) {
            $atom = $xml->children('http://www.w3.org/2005/Atom');
            $items = array();
            foreach ($atom->entry as $entry) {
                $entryAtom = $entry->children('http://www.w3.org/2005/Atom');
                $idUrl = trim((string)$entryAtom->id);
                if ($idUrl === '') continue;
                $title = rdi_clean_text((string)$entryAtom->title);
                $abstract = rdi_clean_text((string)$entryAtom->summary);
                $published = trim((string)$entryAtom->published);
                $category = '';
                $arxiv = $entry->children('http://arxiv.org/schemas/atom');
                if (isset($arxiv->primary_category)) $category = (string)$arxiv->primary_category['term'];
                if ($category === '' && isset($entryAtom->category[0])) $category = (string)$entryAtom->category[0]['term'];
                $authors = array();
                foreach ($entryAtom->author as $a) { $name=rdi_clean_text((string)$a->name); if($name!=='')$authors[]=$name; if(count($authors)>=3)break; }
                $items[] = array(
                    'id'=>'arxiv-' . substr(sha1($idUrl),0,24), 'title'=>$title,
                    'source'=>'arXiv' . ($authors ? ' · ' . implode(', ', $authors) : ''), 'url'=>$idUrl,
                    'summary'=>rdi_truncate_text($abstract,700), 'abstract'=>$abstract,
                    'category'=>rdi_news_category_label($category), 'publishedAt'=>$published
                );
            }
            if ($items) return $items;
        }
    }
    /* Works even when SimpleXML is not enabled on shared hosting/XAMPP. */
    return rdi_parse_arxiv_atom_regex($xmlText);
}

function rdi_fetch_arxiv_items($categories, $maxResults, $timeout, $allowInsecureSslFallback) {
    $safe = array();
    foreach ($categories as $cat) { $cat=trim($cat); if($cat!=='' && preg_match('/^[A-Za-z0-9.\-]+$/',$cat)) $safe[]=$cat; }
    if (!$safe) $safe=array('cs.AI','cs.LG','cs.CV','cs.CL','cs.RO');
    $parts=array(); foreach($safe as $cat)$parts[]='cat:'.$cat;
    $params=array('search_query'=>implode(' OR ',$parts),'start'=>0,'max_results'=>max(1,min(30,(int)$maxResults)),'sortBy'=>'submittedDate','sortOrder'=>'descending');
    $url='https://export.arxiv.org/api/query?'.http_build_query($params,'','&',PHP_QUERY_RFC3986);
    $xmlText=rdi_http_request($url,'GET',array('Accept: application/atom+xml'),null,$timeout,$allowInsecureSslFallback);
    $items=rdi_parse_arxiv_atom($xmlText);
    if (!$items) throw new RuntimeException('arXiv responded, but no readable entries were found in the feed.');
    return $items;
}

function rdi_crossref_date($row) {
    $keys=array('published-online','published-print','published','issued','created');
    foreach($keys as $k){
        if(!isset($row[$k]['date-parts'][0])||!is_array($row[$k]['date-parts'][0]))continue;
        $p=$row[$k]['date-parts'][0]; $y=isset($p[0])?(int)$p[0]:0; if(!$y)continue;
        $m=isset($p[1])?max(1,min(12,(int)$p[1])):1; $d=isset($p[2])?max(1,min(28,(int)$p[2])):1;
        return sprintf('%04d-%02d-%02dT00:00:00+00:00',$y,$m,$d);
    }
    return date('c');
}

function rdi_fetch_crossref_items($categories, $maxResults, $timeout, $allowInsecureSslFallback) {
    $labels=array(); foreach($categories as $c)$labels[]=rdi_news_category_label($c);
    $query=implode(' ',array_unique($labels));
    if(trim($query)==='')$query='artificial intelligence machine learning research';
    $from=date('Y-m-d',time()-120*86400);
    $params=array('query.bibliographic'=>$query,'filter'=>'from-pub-date:'.$from,'rows'=>max(1,min(30,(int)$maxResults)),'sort'=>'published','order'=>'desc','select'=>'DOI,title,author,published,published-online,published-print,issued,created,URL,container-title,abstract,subject');
    $url='https://api.crossref.org/works?'.http_build_query($params,'','&',PHP_QUERY_RFC3986);
    $raw=rdi_http_request($url,'GET',array('Accept: application/json'),null,$timeout,$allowInsecureSslFallback);
    $j=json_decode($raw,true);
    if(!is_array($j)||!isset($j['message']['items'])||!is_array($j['message']['items']))throw new RuntimeException('Crossref returned an unexpected JSON response.');
    $items=array();
    foreach($j['message']['items'] as $row){
        $title=isset($row['title'][0])?rdi_clean_text($row['title'][0]):''; if($title==='')continue;
        $doi=isset($row['DOI'])?trim($row['DOI']):''; $urlOut=isset($row['URL'])?trim($row['URL']):($doi!==''?'https://doi.org/'.$doi:'');
        if($urlOut==='')$urlOut='https://search.crossref.org/?q='.rawurlencode($title);
        $authors=array(); if(isset($row['author'])&&is_array($row['author']))foreach($row['author'] as $a){$n=trim((isset($a['given'])?$a['given'].' ':'').(isset($a['family'])?$a['family']:''));if($n!=='')$authors[]=$n;if(count($authors)>=3)break;}
        $journal=isset($row['container-title'][0])?rdi_clean_text($row['container-title'][0]):'Crossref';
        $abstract=isset($row['abstract'])?rdi_clean_text($row['abstract']):'';
        $summary=$abstract!==''?rdi_truncate_text($abstract,700):('Recently indexed research: “'.rdi_truncate_text($title,220).'”. Open the source link for the full abstract and publication details.');
        $subjectText=isset($row['subject'])&&is_array($row['subject'])?implode(' ',$row['subject']):'';
        $category=rdi_guess_category($title.' '.$abstract.' '.$subjectText);
        $seed=$doi!==''?$doi:$urlOut.'|'.$title;
        $items[]=array('id'=>'crossref-'.substr(sha1($seed),0,24),'title'=>$title,'source'=>$journal.($authors?' · '.implode(', ',$authors):''),'url'=>$urlOut,'summary'=>$summary,'abstract'=>$abstract!==''?$abstract:$summary,'category'=>$category,'publishedAt'=>rdi_crossref_date($row));
    }
    if(!$items)throw new RuntimeException('Crossref responded, but no usable recent research records were found.');
    return $items;
}

function rdi_news_ai_enrich($items, $cfg) {
    $provider=strtolower(trim(isset($cfg['provider'])?$cfg['provider']:'none'));
    $key=''; if($provider==='gemini')$key=trim(isset($cfg['gemini_api_key'])?$cfg['gemini_api_key']:''); if($provider==='deepseek')$key=trim(isset($cfg['deepseek_api_key'])?$cfg['deepseek_api_key']:'');
    if(!$items||$provider==='none'||$key==='')return array($items,false,'AI enrichment disabled; source abstracts/metadata used.');
    $input=array(); foreach($items as $x)$input[]=array('id'=>$x['id'],'title'=>$x['title'],'abstract'=>$x['abstract'],'category'=>$x['category']);
    $prompt="You are editing a research-news feed. For each paper below, write a factual plain-English headline and a concise 2-sentence summary (maximum 70 words total). Do not invent claims beyond the supplied metadata. Return ONLY JSON as {\"items\":[{\"id\":\"...\",\"title\":\"...\",\"summary\":\"...\",\"category\":\"...\"}]}.\n\nPAPERS:\n".json_encode($input,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    $timeout=isset($cfg['http_timeout'])?(int)$cfg['http_timeout']:25;
    try{
        $rawText='';
        if($provider==='gemini'){
            $model=trim(isset($cfg['gemini_model'])?$cfg['gemini_model']:'gemini-2.5-flash');
            $url='https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent';
            $payload=array('contents'=>array(array('parts'=>array(array('text'=>$prompt)))),'generationConfig'=>array('responseMimeType'=>'application/json','temperature'=>0.2));
            $resp=rdi_http_request($url,'POST',array('Content-Type: application/json','x-goog-api-key: '.$key),json_encode($payload),$timeout,false);
            $j=json_decode($resp,true); if(isset($j['candidates'][0]['content']['parts'][0]['text']))$rawText=$j['candidates'][0]['content']['parts'][0]['text'];
        }elseif($provider==='deepseek'){
            $model=trim(isset($cfg['deepseek_model'])?$cfg['deepseek_model']:'deepseek-v4-flash');
            $payload=array('model'=>$model,'messages'=>array(array('role'=>'system','content'=>'Return valid JSON only and preserve supplied research facts.'),array('role'=>'user','content'=>$prompt)),'response_format'=>array('type'=>'json_object'),'max_tokens'=>1800);
            $resp=rdi_http_request('https://api.deepseek.com/chat/completions','POST',array('Content-Type: application/json','Authorization: Bearer '.$key),json_encode($payload),$timeout,false);
            $j=json_decode($resp,true); if(isset($j['choices'][0]['message']['content']))$rawText=$j['choices'][0]['message']['content'];
        }
        $enriched=json_decode(trim($rawText),true); if(!is_array($enriched)||!isset($enriched['items'])||!is_array($enriched['items']))throw new RuntimeException('AI returned an unexpected format.');
        $byId=array(); foreach($enriched['items'] as $row)if(is_array($row)&&!empty($row['id']))$byId[$row['id']]=$row;
        foreach($items as $i=>$x){if(!isset($byId[$x['id']]))continue;$r=$byId[$x['id']];if(!empty($r['title']))$items[$i]['title']=rdi_truncate_text($r['title'],250);if(!empty($r['summary']))$items[$i]['summary']=rdi_truncate_text($r['summary'],700);if(!empty($r['category']))$items[$i]['category']=rdi_truncate_text($r['category'],110);}
        return array($items,true,ucfirst($provider).' enrichment applied.');
    }catch(Throwable $e){error_log('[RDI research AI] '.$e->getMessage());return array($items,false,'AI enrichment failed; source metadata used instead.');}
}

function rdi_research_news_diagnostics($networkProbe) {
    $out=array(
        'phpVersion'=>PHP_VERSION,
        'pdoMysql'=>extension_loaded('pdo_mysql'),
        'curl'=>function_exists('curl_init'),
        'allowUrlFopen'=>filter_var(ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOLEAN),
        'simpleXml'=>function_exists('simplexml_load_string'),
        'openssl'=>extension_loaded('openssl'),
        'database'=>'unknown', 'researchTable'=>'unknown'
    );
    try{
        $pdo=db(); $out['database']='ok';
        $st=$pdo->prepare("SELECT id FROM research_news LIMIT 1");$st->execute(); $out['researchTable']='ok';
    }catch(Throwable $e){$out['database']='error';$out['researchTable']='error';$out['databaseError']=$e->getMessage();}
    if($networkProbe){
        $cfg=rdi_news_config(); $timeout=min(12,max(5,(int)$cfg['http_timeout'])); $insecure=!empty($cfg['public_feed_allow_insecure_ssl_fallback']);
        try{rdi_fetch_arxiv_items(array('cs.AI'),1,$timeout,$insecure);$out['arxiv']='ok';}catch(Throwable $e){$out['arxiv']='error';$out['arxivError']=$e->getMessage();}
        try{rdi_fetch_crossref_items(array('cs.AI'),1,$timeout,$insecure);$out['crossref']='ok';}catch(Throwable $e){$out['crossref']='error';$out['crossrefError']=$e->getMessage();}
    }
    return $out;
}

function run_research_news_automation($force) {
    $pdo=db(); $settings=rdi_setting_map();
    $intervalHours=isset($settings['newsAutomationIntervalHours'])?(int)$settings['newsAutomationIntervalHours']:6; $intervalHours=max(1,min(168,$intervalHours));
    $maxItems=isset($settings['newsMaxItems'])?(int)$settings['newsMaxItems']:5; $maxItems=max(1,min(10,$maxItems));
    $lastRun=isset($settings['lastNewsAutomationAt'])?trim($settings['lastNewsAutomationAt']):'';
    if(!$force&&$lastRun!==''){$lastTs=strtotime($lastRun);if($lastTs&&(time()-$lastTs)<($intervalHours*3600))return array('ok'=>true,'ran'=>false,'imported'=>0,'skipped'=>'interval','message'=>'Automation is not due yet.','lastRun'=>$lastRun,'intervalHours'=>$intervalHours);}

    $categoriesText=isset($settings['newsCategories'])?$settings['newsCategories']:'cs.AI,cs.LG,cs.CV,cs.CL,cs.RO';
    $categories=array_filter(array_map('trim',explode(',',$categoriesText))); $cfg=rdi_news_config(); $nowIso=date('c');
    $timeout=isset($cfg['http_timeout'])?(int)$cfg['http_timeout']:25; $allowInsecure=!empty($cfg['public_feed_allow_insecure_ssl_fallback']);

    try{
        $feed=array(); $source=''; $sourceErrors=array();
        try{$feed=rdi_fetch_arxiv_items($categories,max($maxItems*3,12),$timeout,$allowInsecure);$source='arXiv';}
        catch(Throwable $e){$sourceErrors[]='arXiv: '.$e->getMessage(); error_log('[RDI research arXiv] '.$e->getMessage());}
        if(!$feed){
            try{$feed=rdi_fetch_crossref_items($categories,max($maxItems*3,12),$timeout,$allowInsecure);$source='Crossref';}
            catch(Throwable $e){$sourceErrors[]='Crossref: '.$e->getMessage(); error_log('[RDI research Crossref] '.$e->getMessage());}
        }
        if(!$feed)throw new RuntimeException('All research sources failed. '.implode(' | ',$sourceErrors));

        $fresh=array(); $exists=$pdo->prepare("SELECT 1 FROM research_news WHERE id=? LIMIT 1");
        foreach($feed as $item){$exists->execute(array($item['id']));if($exists->fetchColumn())continue;$fresh[]=$item;if(count($fresh)>=$maxItems)break;}
        list($fresh,$aiUsed,$aiMessage)=rdi_news_ai_enrich($fresh,$cfg);
        $approved=!isset($settings['autoApproveNews'])||$settings['autoApproveNews']==='1';
        $insert=$pdo->prepare("INSERT IGNORE INTO research_news(id,title,source,url,summary,category,approved,generated,published_at) VALUES(?,?,?,?,?,?,?,?,?)");
        $imported=0;
        foreach($fresh as $item){$ts=!empty($item['publishedAt'])?strtotime($item['publishedAt']):false;$pub=$ts?date('Y-m-d H:i:s',$ts):date('Y-m-d H:i:s');$insert->execute(array($item['id'],rdi_truncate_text($item['title'],250),rdi_truncate_text($item['source'],190),$item['url'],rdi_truncate_text($item['summary'],700),rdi_truncate_text($item['category'],110),$approved?1:0,1,$pub));if($insert->rowCount()>0){$imported++;if($approved)rdi_notify_safely(function($svc) use ($item){$svc->researchUpdate($item['id'],$item['title'],isset($item['category'])?$item['category']:'Research');});}}

        $statusMessage=$aiMessage.($sourceErrors?' Fallback note: '.implode(' | ',$sourceErrors):'');
        rdi_set_setting('lastNewsAutomationAt',$nowIso);rdi_set_setting('lastNewsAutomationStatus','success');rdi_set_setting('lastNewsAutomationCount',(string)$imported);rdi_set_setting('lastNewsAutomationMessage',$statusMessage);rdi_set_setting('lastNewsAutomationSource',$source);
        return array('ok'=>true,'ran'=>true,'imported'=>$imported,'approved'=>$approved,'source'=>$source,'aiUsed'=>$aiUsed,'aiMessage'=>$aiMessage,'warnings'=>$sourceErrors,'lastRun'=>$nowIso,'intervalHours'=>$intervalHours,'message'=>$imported?('Imported '.$imported.' new research item(s) from '.$source.'.'):('No new research items were found in '.$source.'.'));
    }catch(Throwable $e){
        rdi_set_setting('lastNewsAutomationAt',$nowIso);rdi_set_setting('lastNewsAutomationStatus','error');rdi_set_setting('lastNewsAutomationCount','0');rdi_set_setting('lastNewsAutomationMessage',$e->getMessage());error_log('[RDI research automation] '.$e->getMessage());throw $e;
    }
}
