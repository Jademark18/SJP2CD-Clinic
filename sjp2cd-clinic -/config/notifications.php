<?php
require_once __DIR__ . '/db.php';

$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_role ENUM('admin','nurse','student') NOT NULL DEFAULT 'admin',
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('request','approved','declined','info','warning') NOT NULL DEFAULT 'info',
    status ENUM('unread','read') NOT NULL DEFAULT 'unread',
    link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_role (user_role, user_id, status)
)");

function create_notification($userId = null, $userRole = 'admin', $title = '', $message = '', $type = 'info', $link = '') {
    global $conn;

    if (!$conn || empty($title) || empty($message)) {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_role, title, message, type, link) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return false;
    }

    $bindUserId = $userId === null ? null : (int) $userId;
    $stmt->bind_param('isssss', $bindUserId, $userRole, $title, $message, $type, $link);

    return $stmt->execute();
}

function get_notifications($userRole, $userId = null, $limit = 10) {
    global $conn;

    $sql = "SELECT * FROM notifications WHERE user_role = ?";
    $params = [$userRole];
    $types = 's';

    if ($userId !== null) {
        $sql .= " AND user_id = ?";
        $params[] = (int) $userId;
        $types .= 'i';
    } else {
        $sql .= " AND user_id IS NULL";
    }

    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = (int) $limit;
    $types .= 'i';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    return $items;
}

function count_unread_notifications($userRole, $userId = null) {
    global $conn;

    $sql = "SELECT COUNT(*) AS total FROM notifications WHERE user_role = ? AND status = 'unread'";
    $params = [$userRole];
    $types = 's';

    if ($userId !== null) {
        $sql .= " AND user_id = ?";
        $params[] = (int) $userId;
        $types .= 'i';
    } else {
        $sql .= " AND user_id IS NULL";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return (int) ($row['total'] ?? 0);
}

function mark_notifications_read($userRole, $userId = null) {
    global $conn;

    $sql = "UPDATE notifications SET status = 'read' WHERE user_role = ? AND status = 'unread'";
    $params = [$userRole];
    $types = 's';

    if ($userId !== null) {
        $sql .= " AND user_id = ?";
        $params[] = (int) $userId;
        $types .= 'i';
    } else {
        $sql .= " AND user_id IS NULL";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param($types, ...$params);
    return $stmt->execute();
}
