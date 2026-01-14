<?php
// Log khi form submit được trigger
$log_file = __DIR__ . '/../logs/payment_debug.log';
$log_entry = date('Y-m-d H:i:s') . " - FORM SUBMIT TRIGGERED (from JavaScript)\n";
$log_entry .= "POST data: " . print_r($_POST, true) . "\n";
$log_entry .= "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>

