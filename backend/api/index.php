<?php
require_once __DIR__ . '/_helpers.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$allowed = ['create', 'join', 'start', 'roll', 'keep', 'bank', 'state', 'ai_turn', 'finish_choice'];

if (!in_array($action, $allowed)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
    exit;
}

require_once __DIR__ . '/' . $action . '.php';
