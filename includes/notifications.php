<?php
require_once __DIR__ . '/../app/Services/NotificationService.php';

function rdi_notification_service() {
    static $service = null;
    if ($service === null) $service = new NotificationService();
    return $service;
}

function rdi_notify_safely($callback) {
    try { return call_user_func($callback, rdi_notification_service()); }
    catch (Throwable $e) { error_log('[RDI notifications] '.$e->getMessage()); return null; }
}
