<?php
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['image'])) {
    http_response_code(400);
    exit("No image provided");
}

$image_data = $data['image'];
$image_data = str_replace('data:image/jpeg;base64,', '', $image_data);
$image_data = str_replace(' ', '+', $image_data);
$image_binary = base64_decode($image_data);

// Sauvegarder l’image temporairement
$file = 'captured.jpg';
file_put_contents($file, $image_binary);

// Appeler le script Python pour la détection
shell_exec("python3 face_check.py " . escapeshellarg($file));
