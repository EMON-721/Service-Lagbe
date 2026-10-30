<?php
require_once '../config.php';

$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

$query = "SELECT b.*, s.name AS service_name, u.name AS customer_name
          FROM bookings b
          JOIN services s ON b.service_id = s.id
          JOIN users u ON b.user_id = u.id
          WHERE 1=1";

if ($search != '') {
    $query .= " AND (b.id LIKE '%$search%' 
                OR u.name LIKE '%$search%' 
                OR s.name LIKE '%$search%')";
}

if ($filter != 'all') {
    $query .= " AND b.status='$filter'";
}

$query .= " ORDER BY b.id DESC";

$result = $conn->query($query);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
