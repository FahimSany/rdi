<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/Services/NotificationService.php';
$pdo=db();$service=new NotificationService();
$st=$pdo->prepare("SELECT id,title,due_date,assignee_user_id,status FROM tasks WHERE assignee_user_id IS NOT NULL AND due_date IS NOT NULL AND status NOT IN ('Done','Completed','Cancelled') AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)");
$st->execute();$sent=0;
foreach($st->fetchAll() as $task){
    $days=(int)floor((strtotime($task['due_date'].' 00:00:00')-strtotime(date('Y-m-d').' 00:00:00'))/86400);
    if($service->deadlineApproaching($task['id'],$task['assignee_user_id'],$task['title'],$task['due_date'],$days)!==null)$sent++;
}
audit_log('deadline_reminder_cron','notification',null,'Created '.$sent.' reminder notification(s)',null);
echo '[RDI] deadline reminders created: '.$sent.PHP_EOL;
