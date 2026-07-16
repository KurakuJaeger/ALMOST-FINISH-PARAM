<?php

require_once __DIR__ . '/../src/models/philippine-location.php';
require_once __DIR__ . '/../src/models/postal-code.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

try {
    if (isset($_GET['postal_locality_id'])) {
        $result = PostalCode::forAddress(
            max(0, (int) $_GET['postal_locality_id']),
            max(0, (int) ($_GET['barangay_id'] ?? 0))
        );
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    } elseif (isset($_GET['locality_id'])) {
        $items = PhilippineLocation::barangays(max(0, (int) $_GET['locality_id']));
    } elseif (isset($_GET['province_id'])) {
        $items = PhilippineLocation::localities(max(0, (int) $_GET['province_id']));
    } elseif (isset($_GET['region_code'])) {
        $items = PhilippineLocation::provinces(trim((string) $_GET['region_code']));
    } else {
        $items = PhilippineLocation::regions();
    }
    echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Location options are temporarily unavailable.']);
}
