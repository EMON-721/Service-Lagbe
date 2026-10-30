<?php
require_once 'config.php';

// Check if user is logged in and is a provider
if (!is_logged_in() || $_SESSION['user_role'] !== 'provider') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get the service_provider id for this user
$stmt = $conn->prepare("SELECT id FROM service_providers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sp_result = $stmt->get_result();

if ($sp_result->num_rows === 0) {
    // If no service_provider entry exists, create one
    $create_stmt = $conn->prepare("INSERT INTO service_providers (user_id, bio, experience, rating, is_verified, created_at) VALUES (?, '', 0, 0.00, 0, NOW())");
    $create_stmt->bind_param("i", $user_id);
    $create_stmt->execute();
    $provider_id = $conn->insert_id;
    $create_stmt->close();
} else {
    $sp_data = $sp_result->fetch_assoc();
    $provider_id = $sp_data['id'];
}
$stmt->close();

// Fetch bookings assigned to this provider
$stmt = $conn->prepare("
    SELECT b.*, u.name as customer_name, s.name as service_name, s.description as service_description
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN services s ON b.service_id = s.id
    WHERE b.provider_id = ?
    ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = [];
while ($booking = $bookings_result->fetch_assoc()) {
    $bookings[] = $booking;
}
$stmt->close();

// Convert bookings to JavaScript format
$js_bookings = [];
foreach ($bookings as $booking) {
    $js_bookings[] = [
        'id' => '#' . $booking['id'],
        'booking_id' => $booking['id'],
        'customer' => $booking['customer_name'],
        'type' => $booking['service_name'],
        'date' => date('Y-m-d g:i A', strtotime($booking['booking_date'] . ' ' . $booking['booking_time'])),
        'status' => $booking['status'],
        'description' => $booking['service_description'] ?: $booking['service_name'],
        'address' => $booking['address'],
        'price' => '৳' . number_format($booking['price'], 2),
        'notes' => $booking['notes'] ?: ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #f60808ff;
            --primary-dark: #ed0909ff;
            --secondary-color: #4CAF50;
            --secondary-light: #66BB6A;
            --text-dark: #1a1a1a;
            --text-light: #666666;
            --bg-light: #f5f5f5;
            --bg-white: #ffffff;
            --border-color: #e0e0e0;
            --success-color: #4CAF50;
            --warning-color: #FFA500;
            --danger-color: #F44336;
            --info-color: #2196F3;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* ============ Header/Navigation ============ */
        header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            display: flex; 
            align-items: center;
            gap: 10px;
            width: 100px;
            height: auto;
        }
              




        .logo-icon {
            width: 30px;
            height: 30px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: bold;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            cursor: pointer;
            font-weight: bold;
        }

        /* ============ Main Container ============ */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 32px;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 14px;
        }

        /* ============ Statistics Cards ============ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 5px solid var(--primary-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }

        .stat-card.completed {
            border-left-color: var(--success-color);
        }

        .stat-card.pending {
            border-left-color: var(--warning-color);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .stat-card-title {
            font-size: 14px;
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-card.total .stat-card-icon {
            background: rgba(196, 30, 58, 0.1);
            color: var(--primary-color);
        }

        .stat-card.completed .stat-card-icon {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }

        .stat-card.pending .stat-card-icon {
            background: rgba(255, 165, 0, 0.1);
            color: var(--warning-color);
        }

        .stat-card-value {
            font-size: 36px;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .stat-card-change {
            font-size: 12px;
            color: var(--success-color);
        }

        .stat-card-change.negative {
            color: var(--danger-color);
        }

        /* ============ Table Section ============ */
        .table-section {
            background: var(--bg-white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-dark);
        }

        .table-controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-box {
            padding: 8px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            min-width: 250px;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(196, 30, 58, 0.1);
        }

        .filter-btn {
            padding: 8px 15px;
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* ============ Table Styling ============ */
        .services-table {
            width: 100%;
            border-collapse: collapse;
        }

        .services-table thead {
            background: var(--bg-light);
        }

        .services-table th {
            padding: 15px 25px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }

        .services-table td {
            padding: 15px 25px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        .services-table tbody tr {
            transition: background 0.3s ease;
        }

        .services-table tbody tr:hover {
            background: rgba(196, 30, 58, 0.02);
        }

        .service-id {
            font-weight: 600;
            color: var(--primary-color);
        }

        .customer-name {
            font-weight: 500;
            color: var(--text-dark);
        }

        .service-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(76, 175, 80, 0.1);
            color: var(--secondary-color);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: rgba(255, 165, 0, 0.1);
            color: var(--warning-color);
        }

        .status-completed {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }

        .status-assigned {
            background: rgba(33, 150, 243, 0.1);
            color: var(--info-color);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-view {
            background: var(--info-color);
            color: white;
        }

        .btn-view:hover {
            background: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
        }

        .btn-accept {
            background: var(--success-color);
            color: white;
        }

        .btn-accept:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
        }

        .btn-reject {
            background: var(--danger-color);
            color: white;
        }

        .btn-reject:hover {
            background: #f4f3f3e8;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
        }

        /* ============ Empty State ============ */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        /* ============ Modal ============ */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--bg-white);
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }

        .modal-title {
            font-size: 22px;
            font-weight: bold;
            color: var(--text-dark);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-light);
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: var(--primary-color);
        }

        .modal-body {
            margin-bottom: 25px;
        }

        .modal-detail {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-detail:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--text-light);
            font-size: 13px;
            text-transform: uppercase;
        }

        .detail-value {
            color: var(--text-dark);
            font-weight: 500;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-cancel {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1px solid var(--border-color);
        }

        .btn-cancel:hover {
            background: var(--border-color);
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        /* ============ Pagination ============ */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid var(--border-color);
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            background: var(--bg-white);
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .pagination button:hover:not(:disabled) {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-info {
            color: var(--text-light);
            font-size: 14px;
        }

        /* ============ Toast Notification ============ */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--success-color);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideInUp 0.3s ease;
            z-index: 2000;
        }

        .toast.error {
            background: var(--danger-color);
        }

        @keyframes slideInUp {
            from {
                transform: translateY(100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* ============ Responsive ============ */
        @media (max-width: 768px) {
            .header-container {
                padding: 0 20px;
                flex-wrap: wrap;
            }

            .page-title {
                font-size: 24px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .table-controls {
                width: 100%;
                flex-direction: column;
            }

            .search-box {
                width: 100%;
                min-width: unset;
            }

            .services-table {
                font-size: 13px;
            }

            .services-table th,
            .services-table td {
                padding: 12px 15px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .modal-content {
                width: 95%;
                margin: 20% auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
      <header class="logo-container">
        <div class="header-container">
            <div class="logo">
            <img src="ServiceLagbe.jpeg" alt="Service Lagbe Logo" class="logo">
            </div>
            <div class="user-info">
                <span id="username">Technician</span>
                <div class="user-avatar">👤</div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Service Dashboard</h1>
            <p class="page-subtitle">Manage and track all assigned services</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Total Services</h3>
                    <div class="stat-card-icon">📋</div>
                </div>
                <div class="stat-card-value" id="total-services">24</div>
                <p class="stat-card-change">All assigned services</p>
            </div>

            <div class="stat-card completed">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Completed</h3>
                    <div class="stat-card-icon">✓</div>
                </div>
                <div class="stat-card-value" id="completed-services">16</div>
                <p class="stat-card-change">+2 this week</p>
            </div>

            <div class="stat-card pending">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Pending</h3>
                    <div class="stat-card-icon">⏳</div>
                </div>
                <div class="stat-card-value" id="pending-services">8</div>
                <p class="stat-card-change negative">Awaiting action</p>
            </div>
        </div>

        <!-- Services Table -->
        <div class="table-section">
            <div class="table-header">
                <h2 class="table-title">Active Services</h2>
                <div class="table-controls">
                    <input type="text" class="search-box" id="searchBox" placeholder="Search by service ID, customer, or type...">
                    <button class="filter-btn" onclick="filterServices('pending')">Pending</button>
                    <button class="filter-btn" onclick="filterServices('all')">All</button>
                </div>
            </div>

            <table class="services-table">
                <thead>
                    <tr>
                        <th>Service ID</th>
                        <th>Customer</th>
                        <th>Service Type</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="servicesTableBody">
                    <!-- Table rows will be populated by JavaScript -->
                </tbody>
            </table>

            <div class="pagination" id="pagination">
                <!-- Pagination will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- View Service Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Service Details</h2>
                <button class="close-btn" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Details will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Accept Service Modal -->
    <div id="acceptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Accept Service</h2>
                <button class="close-btn" onclick="closeModal('acceptModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 20px; color: var(--text-dark);">Are you sure you want to accept this service?</p>
                <div class="modal-detail">
                    <span class="detail-label">Service ID:</span>
                    <span class="detail-value" id="acceptServiceId"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal('acceptModal')">Cancel</button>
                <button class="btn btn-accept" onclick="confirmAccept()">Accept Service</button>
            </div>
        </div>
    </div>

    <!-- Reject Service Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Reject Service</h2>
                <button class="close-btn" onclick="closeModal('rejectModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 20px; color: var(--text-dark);">Please provide a reason for rejecting this service:</p>
                <textarea id="rejectReason" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit; resize: vertical; min-height: 100px;" placeholder="Enter rejection reason..."></textarea>
                <div class="modal-detail" style="margin-top: 20px;">
                    <span class="detail-label">Service ID:</span>
                    <span class="detail-value" id="rejectServiceId"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal('rejectModal')">Cancel</button>
                <button class="btn btn-reject" onclick="confirmReject()">Reject Service</button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Bookings data from database
        let services = <?php echo json_encode($js_bookings); ?>;

        let currentPage = 1;
        const itemsPerPage = 5;
        let filteredServices = [...services];
        let currentServiceId = null;

        // Initialize dashboard
        function initDashboard() {
            updateStats();
            renderTable();
            setupEventListeners();
        }

        // Update statistics
        function updateStats() {
            const total = services.length;
            const completed = services.filter(s => s.status === 'completed').length;
            const pending = services.filter(s => s.status === 'pending').length;

            document.getElementById('total-services').textContent = total;
            document.getElementById('completed-services').textContent = completed;
            document.getElementById('pending-services').textContent = pending;
        }

        // Render table
        function renderTable() {
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const paginatedServices = filteredServices.slice(start, end);

            const tableBody = document.getElementById('servicesTableBody');
            tableBody.innerHTML = '';

            if (paginatedServices.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">📭</div><div class="empty-state-title">No services found</div></div></td></tr>';
                document.getElementById('pagination').innerHTML = '';
                return;
            }

            paginatedServices.forEach(service => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><span class="service-id">${service.id}</span></td>
                    <td><span class="customer-name">${service.customer}</span></td>
                    <td><span class="service-type">${service.type}</span></td>
                    <td>${service.date}</td>
                    <td><span class="status-badge status-${service.status}">${service.status.charAt(0).toUpperCase() + service.status.slice(1)}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-view" onclick="viewService('${service.id}')">View</button>
                            ${service.status === 'pending' ? `
                                <button class="btn btn-accept" onclick="acceptService('${service.id}')">Accept</button>
                                <button class="btn btn-reject" onclick="rejectService('${service.id}')">Reject</button>
                            ` : ''}
                        </div>
                    </td>
                `;
                tableBody.appendChild(row);
            });

            renderPagination();
        }

        // Render pagination
        function renderPagination() {
            const totalPages = Math.ceil(filteredServices.length / itemsPerPage);
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            const prevBtn = document.createElement('button');
            prevBtn.textContent = '← Previous';
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }
            };

            const nextBtn = document.createElement('button');
            nextBtn.textContent = 'Next →';
            nextBtn.disabled = currentPage === totalPages;
            nextBtn.onclick = () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    renderTable();
                }
            };

            const info = document.createElement('span');
            info.className = 'pagination-info';
            info.textContent = `Page ${currentPage} of ${totalPages}`;

            pagination.appendChild(prevBtn);
            pagination.appendChild(info);
            pagination.appendChild(nextBtn);
        }

        // View service details
        function viewService(serviceId) {
            const service = services.find(s => s.id === serviceId);
            if (!service) return;

            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = `
                <div class="modal-detail">
                    <span class="detail-label">Service ID:</span>
                    <span class="detail-value">${service.id}</span>
                </div>
                <div class="modal-detail">
                    <span class="detail-label">Customer:</span>
                    <span class="detail-value">${service.customer}</span>
                </div>
                <div class="modal-detail">
                    <span class="detail-label">Service Type:</span>
                    <span class="detail-value">${service.type}</span>
                </div>
                <div class="modal-detail">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">${service.date}</span>
                </div>
                <div class="modal-detail">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value">${service.address}</span>
                </div>
                <div class="modal-detail">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value">${service.description}</span>
                </div>
                <div class="modal-detail">
                    <span class="detail-label">Price:</span>
                    <span class="detail-value" style="color: var(--success-color); font-weight: bold;">${service.price}</span>
                </div>
                <div class="modal-detail">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value"><span class="status-badge status-${service.status}">${service.status.charAt(0).toUpperCase() + service.status.slice(1)}</span></span>
                </div>
            `;

            openModal('viewModal');
        }

        // Accept service
        function acceptService(serviceId) {
            currentServiceId = serviceId;
            document.getElementById('acceptServiceId').textContent = serviceId;
            openModal('acceptModal');
        }

        // Confirm accept
        function confirmAccept() {
            const service = services.find(s => s.id === currentServiceId);
            if (!service) {
                showToast('Service not found. Please refresh the page.', 'error');
                return;
            }

            // Extract booking_id from service
            let bookingId = service.booking_id;
            if (!bookingId) {
                // Fallback: extract from id (remove # if present)
                bookingId = service.id.toString().replace('#', '');
            }

            if (!bookingId || bookingId === '0') {
                showToast('Invalid booking ID. Please refresh the page.', 'error');
                return;
            }

            console.log('Accepting booking ID:', bookingId);

            // Send AJAX request to accept service
            const formData = new FormData();
            formData.append('id', bookingId);

            fetch('accept_service.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('Accept response:', data);
                const response = data.trim();
                if (response === 'success') {
                    service.status = 'confirmed';
                    updateStats();
                    renderTable();
                    closeModal('acceptModal');
                    showToast(`Service ${currentServiceId} accepted successfully!`);
                } else {
                    let errorMsg = 'Error accepting service. ';
                    if (response.includes('not_logged_in')) {
                        errorMsg = 'You are not logged in. Please refresh the page.';
                    } else if (response.includes('invalid_booking_id')) {
                        errorMsg = 'Invalid booking ID. Please refresh the page.';
                    } else if (response.includes('no_provider_profile')) {
                        errorMsg = 'Provider profile not found. Please contact administrator.';
                    } else if (response.includes('not_assigned_to_you')) {
                        errorMsg = 'This booking is not assigned to you.';
                    } else if (response.includes('invalid_status')) {
                        errorMsg = 'This booking cannot be accepted in its current status.';
                    } else {
                        errorMsg += response;
                    }
                    showToast(errorMsg, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error accepting service. Please try again.', 'error');
            });
        }

        // Reject service
        function rejectService(serviceId) {
            currentServiceId = serviceId;
            document.getElementById('rejectServiceId').textContent = serviceId;
            document.getElementById('rejectReason').value = '';
            openModal('rejectModal');
        }

        // Confirm reject
        function confirmReject() {
            const reason = document.getElementById('rejectReason').value.trim();
            if (!reason) {
                showToast('Please provide a reason for rejection', 'error');
                return;
            }

            const service = services.find(s => s.id === currentServiceId);
            if (!service) {
                showToast('Service not found. Please refresh the page.', 'error');
                return;
            }

            // Extract booking_id from service
            let bookingId = service.booking_id;
            if (!bookingId) {
                // Fallback: extract from id (remove # if present)
                bookingId = service.id.toString().replace('#', '');
            }

            if (!bookingId || bookingId === '0') {
                showToast('Invalid booking ID. Please refresh the page.', 'error');
                return;
            }

            console.log('Rejecting booking ID:', bookingId, 'Reason:', reason);

            // Send AJAX request to reject service
            const formData = new FormData();
            formData.append('id', bookingId);
            formData.append('reason', reason);

            fetch('reject_service.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('Reject response:', data);
                const response = data.trim();
                if (response === 'success') {
                    service.status = 'cancelled';
                    updateStats();
                    renderTable();
                    closeModal('rejectModal');
                    showToast(`Service ${currentServiceId} rejected successfully!`, 'error');
                } else {
                    let errorMsg = 'Error rejecting service. ';
                    if (response.includes('not_logged_in')) {
                        errorMsg = 'You are not logged in. Please refresh the page.';
                    } else if (response.includes('invalid_booking_id')) {
                        errorMsg = 'Invalid booking ID. Please refresh the page.';
                    } else if (response.includes('reason_required')) {
                        errorMsg = 'Please provide a reason for rejection.';
                    } else if (response.includes('no_provider_profile')) {
                        errorMsg = 'Provider profile not found. Please contact administrator.';
                    } else if (response.includes('not_assigned_to_you')) {
                        errorMsg = 'This booking is not assigned to you.';
                    } else if (response.includes('invalid_status')) {
                        errorMsg = 'This booking cannot be rejected in its current status.';
                    } else {
                        errorMsg += response;
                    }
                    showToast(errorMsg, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error rejecting service. Please try again.', 'error');
            });
        }

        // Filter services
        function filterServices(status) {
            if (status === 'all') {
                filteredServices = [...services];
            } else {
                filteredServices = services.filter(s => s.status === status);
            }
            currentPage = 1;
            renderTable();
        }

        // Search functionality
        function setupEventListeners() {
            document.getElementById('searchBox').addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase();
                filteredServices = services.filter(s =>
                    s.id.toLowerCase().includes(query) ||
                    s.customer.toLowerCase().includes(query) ||
                    s.type.toLowerCase().includes(query)
                );
                currentPage = 1;
                renderTable();
            });
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initDashboard);
    </script>
</body>
</html>