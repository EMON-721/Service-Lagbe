<?php
require_once '../config.php';

$id = $_GET['id'];

$query = "SELECT b.*, s.name AS service_name, u.name AS customer_name, u.phone AS customer_phone
          FROM bookings b
          JOIN services s ON b.service_id = s.id
          JOIN users u ON b.user_id = u.id
          WHERE b.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

echo json_encode($data);
?>
