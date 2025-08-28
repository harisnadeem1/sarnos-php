<?php
header('Content-Type: application/json');

// Basis beveiligingscheck
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Upload directory maken indien niet bestaat
$uploadDir = '../uploads/descriptions/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Controleer of er een bestand is geüpload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Geen geldig bestand geüpload']);
    exit;
}

$file = $_FILES['file'];

// Bestandstype validatie
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Alleen afbeeldingsbestanden zijn toegestaan (JPG, PNG, GIF, WebP)']);
    exit;
}

// Bestandsgrootte check (max 5MB)
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'Bestand is te groot. Maximum grootte is 5MB']);
    exit;
}

// Veilige bestandsnaam genereren
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = 'desc_' . uniqid() . '_' . time() . '.' . strtolower($extension);
$targetPath = $uploadDir . $fileName;

// Bestand verplaatsen
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Relatief pad voor de database/web
    $relativeUrl = 'uploads/descriptions/' . $fileName;
    
    echo json_encode([
        'success' => true,
        'location' => $relativeUrl,
        'url' => '../' . $relativeUrl
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Fout bij het uploaden van het bestand']);
}
?> 