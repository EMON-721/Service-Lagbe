<?php
require_once 'config.php';

// Check if user is logged in and is a provider
if (!is_logged_in() || $_SESSION['user_role'] !== 'provider') {
    echo "error:not_logged_in";
    exit;
}

$booking_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$reason = isset($_POST['reason']) ? sanitize_input($_POST['reason']) : '';
$user_id = $_SESSION['user_id'];

if ($booking_id <= 0) {
    echo "error:invalid_booking_id";
    exit;
}

if (empty($reason)) {
    echo "error:reason_required";
    exit;
}

// Get the service_provider id for this user
$stmt = $conn->prepare("SELECT id FROM service_providers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sp_result = $stmt->get_result();

if ($sp_result->num_rows === 0) {
    $stmt->close();
    echo "error:no_provider_profile";
    exit;
}

$sp_data = $sp_result->fetch_assoc();
$provider_id = $sp_data['id'];
$stmt->close();

// Verify that this booking is assigned to this provider
$stmt = $conn->prepare("SELECT id, status, provider_id FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    echo "error:booking_not_found";
    exit;
}

$booking_data = $result->fetch_assoc();
$stmt->close();

// Check if booking is assigned to this provider
if ($booking_data['provider_id'] != $provider_id) {
    echo "error:not_assigned_to_you";
    exit;
}

// Check if status is pending
if ($booking_data['status'] != 'pending') {
    echo "error:invalid_status_" . $booking_data['status'];
    exit;
}

// Update booking status to 'cancelled' (rejected) and add reason to notes
$rejection_note = "Rejected by technician. Reason: " . $reason;
$stmt = $conn->prepare("UPDATE bookings SET status='cancelled', notes=? WHERE id=? AND provider_id=?");
$stmt->bind_param("sii", $rejection_note, $booking_id, $provider_id);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "error:update_failed_" . $stmt->error;
}
$stmt->close();
?>
