<?php
// CORS Debug Tool - Shows exactly what headers are being sent
header('Content-Type: application/json');

// Capture all headers that would be sent
ob_start();

// Simulate a CORS request similar to what your frontend sends
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'https://tos-front-end.vercel.app';

// Set minimal response
echo json_encode(['debug' => 'cors headers test', 'origin' => $origin]);

// Get the headers that would be sent
$headers = [];
foreach (headers_list() as $header) {
    $parts = explode(':', $header, 2);
    if (count($parts) === 2) {
        $name = trim($parts[0]);
        $value = trim($parts[1]);
        if (stripos($name, 'access-control') !== false || stripos($name, 'cors') !== false) {
            $headers[$name] = $value;
        }
    }
}

// Clear output buffer and send our debug response
ob_clean();

// Send our own clean CORS headers
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

echo json_encode([
    'debug' => 'CORS Debug Information',
    'timestamp' => date('Y-m-d H:i:s T'),
    'request_origin' => $origin,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'detected_cors_headers' => $headers,
    'all_request_headers' => getallheaders(),
    'server_info' => [
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    ],
    'message' => 'This endpoint bypasses Laravel to test raw CORS behavior'
]);
?>