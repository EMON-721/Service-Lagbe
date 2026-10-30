<?php
require_once '../config.php';

$booking_id = $_GET['service_id'] ?? 0;
$search = $_GET['search'] ?? '';

// Get booking service type
$stmt = $conn->prepare("SELECT service_id FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($service_id);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("
    SELECT sp.id, u.name, s.name as service_name 
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.id
    JOIN services s ON sp.service_id = s.id
    WHERE s.id = ? AND u.name LIKE ?
");
$like = "%$search%";
$stmt->bind_param("is", $service_id, $like);
$stmt->execute();
$result = $stmt->get_result();
$providers = [];
while($row = $result->fetch_assoc()) {
    $providers[] = $row;
}
$stmt->close();

echo json_encode($providers);
