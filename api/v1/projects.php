<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../app/Controllers/ProjectController.php';
$user=require_user();
$controller=new ProjectController();
$controller->handle($user);
