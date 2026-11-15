<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../app/Controllers/NotificationController.php';
$user=require_user();
$controller=new NotificationController();
$controller->handle($user);
