<?php
require_once __DIR__ . '/../../includes/db.php';

class NotificationRepository {
    private $pdo;
    public function __construct($pdo = null) { $this->pdo = $pdo ?: db(); }

    public function create($userId, $type, $title, $message, $link = null, $dedupeKey = null) {
        $dedupeHash = ($dedupeKey !== null && $dedupeKey !== '') ? hash('sha256', (string)$dedupeKey) : null;
        $sql = "INSERT INTO notifications(user_id,type,title,message,link,is_read,dedupe_key) VALUES(?,?,?,?,?,0,?)";
        if ($dedupeHash !== null) {
            $sql .= " ON DUPLICATE KEY UPDATE id=id";
        }
        $st = $this->pdo->prepare($sql);
        $st->execute(array($userId, $type, $title, $message, $link, $dedupeHash));
        return $st->rowCount() > 0 ? $this->pdo->lastInsertId() : null;
    }

    public function listForUser($userId, $status, $type, $limit, $offset) {
        $where = array('user_id=?'); $params = array($userId);
        if ($status === 'unread') { $where[] = 'is_read=0'; }
        elseif ($status === 'read') { $where[] = 'is_read=1'; }
        if ($type !== '') { $where[] = 'type=?'; $params[] = $type; }
        $limit = max(1, min(100, (int)$limit));
        $offset = max(0, (int)$offset);
        $sql = "SELECT id,user_id,type,title,message,link,is_read,read_at,created_at FROM notifications WHERE ".implode(' AND ', $where)." ORDER BY created_at DESC,id DESC LIMIT ".$limit." OFFSET ".$offset;
        $st = $this->pdo->prepare($sql); $st->execute($params); return $st->fetchAll();
    }

    public function countForUser($userId, $status = 'all', $type = '') {
        $where = array('user_id=?'); $params = array($userId);
        if ($status === 'unread') $where[] = 'is_read=0';
        elseif ($status === 'read') $where[] = 'is_read=1';
        if ($type !== '') { $where[]='type=?'; $params[]=$type; }
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE ".implode(' AND ', $where));
        $st->execute($params); return (int)$st->fetchColumn();
    }

    public function unreadCount($userId) { return $this->countForUser($userId, 'unread', ''); }

    public function markRead($notificationId, $userId) {
        $st=$this->pdo->prepare("UPDATE notifications SET is_read=1,read_at=COALESCE(read_at,NOW()) WHERE id=? AND user_id=?");
        $st->execute(array($notificationId,$userId)); return $st->rowCount();
    }

    public function markAllRead($userId) {
        $st=$this->pdo->prepare("UPDATE notifications SET is_read=1,read_at=COALESCE(read_at,NOW()) WHERE user_id=? AND is_read=0");
        $st->execute(array($userId)); return $st->rowCount();
    }

    public function activeUserIds() {
        $st=$this->pdo->prepare("SELECT id FROM users WHERE status='Active' AND deleted_at IS NULL"); $st->execute();
        return array_map(function($r){return $r['id'];}, $st->fetchAll());
    }

    public function activeAdminIds() {
        $st=$this->pdo->prepare("SELECT id FROM users WHERE status='Active' AND deleted_at IS NULL AND role IN ('super_admin','admin')"); $st->execute();
        return array_map(function($r){return $r['id'];}, $st->fetchAll());
    }

    public function activeUserExists($userId) {
        $st=$this->pdo->prepare("SELECT 1 FROM users WHERE id=? AND status='Active' AND deleted_at IS NULL LIMIT 1");
        $st->execute(array($userId)); return (bool)$st->fetchColumn();
    }
}
