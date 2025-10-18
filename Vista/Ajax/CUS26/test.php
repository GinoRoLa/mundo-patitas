<?php
header('Content-Type: application/json');

// Simulamos que recibimos datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => true, 'message' => 'Fetch funciona', 'recibido' => null]);
} else {
    echo json_encode(['success' => true, 'message' => 'Fetch funciona', 'recibido' => $input]);
}
