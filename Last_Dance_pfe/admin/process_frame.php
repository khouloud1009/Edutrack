<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['frame'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$session_id = $_POST['session_id'] ?? '';
$upload_dir = '../uploads/frames/';
$filename = uniqid('frame_') . '.jpg';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (move_uploaded_file($_FILES['frame']['tmp_name'], $upload_dir . $filename)) {
    // Here you would typically process the image with your face recognition system
    // For now, we'll just return a success message
    echo json_encode(['success' => true, 'filename' => $filename]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save frame']);
}