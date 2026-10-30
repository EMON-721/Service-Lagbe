<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$error = '';
$success = '';
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// Validate booking_id
if ($booking_id <= 0) {
    $error = "Invalid booking ID.";
    redirect('bookings.php');
}

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, u.name as user_name, s.name as service_name, s.description as service_description
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN services s ON b.service_id = s.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking_result = $stmt->get_result();

if ($booking_result->num_rows === 0) {
    $error = "Booking not found.";
    redirect('bookings.php');
}

$booking = $booking_result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_provider'])) {
    $provider_id = isset($_POST['provider_id']) ? intval($_POST['provider_id']) : 0;
    
    if ($provider_id <= 0) {
        $error = "Please select a technician.";
    } else {
        // Check if booking already has a provider assigned with active status
        // Allow assignment if: 
        // - No provider assigned (provider_id = 0 or NULL)
        // - Status is 'cancelled' (technician rejected, can reassign)
        // Block assignment if: 
        // - Status is 'pending' AND provider is assigned (waiting for technician response)
        // - Status is 'confirmed' (technician accepted)
        $current_status = $booking['status'];
        $has_provider = !empty($booking['provider_id']) && $booking['provider_id'] > 0;
        
        // Allow assignment if no provider OR status is cancelled
        if ($has_provider && $current_status === 'pending') {
            $error = "This booking is already assigned to a technician and is waiting for their response. Please wait for the technician to accept or reject the job.";
        } elseif ($has_provider && $current_status === 'confirmed') {
            $error = "This booking has been accepted by the technician. You cannot reassign it.";
        } else {
            // Can assign: no provider OR status is cancelled
            // Verify that the provider_id exists in service_providers table
            $check_stmt = $conn->prepare("SELECT id FROM service_providers WHERE id = ?");
            $check_stmt->bind_param("i", $provider_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $error = "Invalid technician selected. Please try again.";
            } else {
                // Update booking with provider_id and set status to 'pending' (waiting for technician response)
                $stmt = $conn->prepare("UPDATE bookings SET provider_id = ?, status = 'pending' WHERE id = ?");
                $stmt->bind_param("ii", $provider_id, $booking_id);
                
                if ($stmt->execute()) {
                    $success = "Technician assigned successfully! The technician will see this job and can accept or reject it.";
                    // Redirect after 2 seconds
                    header("refresh:2;url=bookings.php");
                } else {
                    $error = "Error assigning technician: " . $stmt->error;
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
    }
}

// Get all registered technicians (users with role='provider')
// Use LEFT JOIN to include providers even if they don't have service_providers entry
$stmt = $conn->prepare("
    SELECT u.id as user_id, u.name, u.email, u.phone, 
           COALESCE(sp.id, 0) as sp_id,
           COALESCE(sp.bio, '') as bio, 
           COALESCE(sp.experience, 0) as experience, 
           COALESCE(sp.rating, 0.00) as rating, 
           COALESCE(sp.is_verified, 0) as is_verified
    FROM users u
    LEFT JOIN service_providers sp ON u.id = sp.user_id
    WHERE u.role = 'provider'
    ORDER BY sp.rating DESC, sp.experience DESC, u.name ASC
");
$stmt->execute();
$technicians_result = $stmt->get_result();
$technicians = [];
while ($technician = $technicians_result->fetch_assoc()) {
    // If provider doesn't have service_providers entry, create one
    if (empty($technician['sp_id']) || $technician['sp_id'] == 0) {
        // Create service_provider entry for this user
        $create_stmt = $conn->prepare("INSERT INTO service_providers (user_id, bio, experience, rating, is_verified, created_at) VALUES (?, '', 0, 0.00, 0, NOW())");
        $create_stmt->bind_param("i", $technician['user_id']);
        if ($create_stmt->execute()) {
            $technician['sp_id'] = $conn->insert_id;
        } else {
            // If creation failed, skip this technician
            $create_stmt->close();
            continue;
        }
        $create_stmt->close();
    }
    // Use service_provider id for assignment (this is what bookings.provider_id references)
    // Only add if we have a valid ID
    if (!empty($technician['sp_id']) && $technician['sp_id'] > 0) {
        $technician['id'] = $technician['sp_id'];
        $technicians[] = $technician;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Technician - Service Lagbe Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #1b5e20;
            --secondary-color: #d21f50;
            --light-bg: #f5f5f5;
            --card-bg: #ffffff;
            --text-color: #333333;
            --text-light: #666666;
            --border-color: #e0e0e0;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --info-color: #2196f3;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo {
            max-width: 150px;
            height: auto;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }

        .menu-item:hover,
        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
        }

        .header {
            height: var(--header-height);
            background-color: var(--card-bg);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-color);
        }

        .user-menu {
            display: flex;
            align-items: center;
        }

        .user-info {
            margin-right: 15px;
            text-align: right;
        }

        .user-name {
            font-weight: 500;
        }

        .user-role {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            margin-bottom: 20px;
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(76, 175, 80, 0.2);
        }

        .alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(244, 67, 54, 0.2);
        }

        .booking-info-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .booking-info-card h3 {
            margin-top: 0;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .technicians-section {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .technicians-section h3 {
            margin-top: 0;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .technicians-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .technician-card {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .technician-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .technician-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(27, 94, 32, 0.05);
        }

        .technician-card input[type="radio"] {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .technician-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .technician-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 15px;
        }

        .technician-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .technician-verified {
            display: inline-block;
            color: var(--success-color);
            font-size: 0.85rem;
            margin-left: 5px;
        }

        .technician-rating {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .stars {
            color: #ffc107;
            margin-right: 8px;
        }

        .rating-value {
            font-weight: 500;
            color: var(--text-color);
        }

        .technician-details {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .technician-experience {
            display: inline-block;
            background-color: rgba(33, 150, 243, 0.1);
            color: var(--info-color);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-top: 10px;
        }

        .technician-bio {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--text-light);
            font-style: italic;
        }

        .submit-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #155016;
        }

        .btn-secondary {
            background-color: var(--text-light);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #555;
        }

        .no-technicians {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }

        .no-technicians i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--border-color);
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.pushed {
                margin-left: var(--sidebar-width);
            }

            .technicians-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="../ServiceLagbe.jpeg" alt="Service Lagbe Logo" class="sidebar-logo">
            </div>
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="users.php" class="menu-item">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="services.php" class="menu-item">
                    <i class="fas fa-tools"></i> Services
                </a>
                <a href="bookings.php" class="menu-item active">
                    <i class="fas fa-calendar-check"></i> Bookings
                </a>
                <a href="reviews.php" class="menu-item">
                    <i class="fas fa-star"></i> Reviews
                </a>
                <a href="../index.php" class="menu-item">
                    <i class="fas fa-home"></i> Back to Website
                </a>
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <!-- Header -->
            <header class="header">
                <button class="toggle-sidebar" id="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?php echo $_SESSION['user_name']; ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <a href="bookings.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Bookings
                </a>

                <h1 class="page-title">Assign Technician</h1>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Booking Information -->
                <div class="booking-info-card">
                    <h3><i class="fas fa-info-circle"></i> Booking Details</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Booking ID</span>
                            <span class="info-value">#<?php echo $booking['id']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Customer</span>
                            <span class="info-value"><?php echo htmlspecialchars($booking['user_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Service</span>
                            <span class="info-value"><?php echo htmlspecialchars($booking['service_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date & Time</span>
                            <span class="info-value">
                                <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> 
                                at <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Price</span>
                            <span class="info-value">৳<?php echo number_format($booking['price'], 2); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value"><?php echo ucfirst($booking['status']); ?></span>
                        </div>
                    </div>
                    <?php if (!empty($booking['address'])): ?>
                        <div class="info-item" style="margin-top: 15px;">
                            <span class="info-label">Address</span>
                            <span class="info-value"><?php echo htmlspecialchars($booking['address']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Technicians Selection -->
                <div class="technicians-section">
                    <h3><i class="fas fa-users"></i> Select a Technician</h3>
                    
                    <?php if (count($technicians) > 0): ?>
                        <form method="POST" action="assign_provider.php?booking_id=<?php echo $booking_id; ?>">
                            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                            <div class="technicians-grid">
                                <?php foreach ($technicians as $technician): ?>
                                    <div class="technician-card">
                                        <input type="radio" name="provider_id" value="<?php echo $technician['id']; ?>" 
                                               id="tech_<?php echo $technician['id']; ?>" required>
                                        <div class="technician-header">
                                            <div class="technician-avatar">
                                                <?php echo strtoupper(substr($technician['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="technician-name">
                                                    <?php echo htmlspecialchars($technician['name']); ?>
                                                    <?php if ($technician['is_verified']): ?>
                                                        <i class="fas fa-check-circle technician-verified" title="Verified"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="technician-rating">
                                                    <div class="stars">
                                                        <?php
                                                        $rating = floatval($technician['rating']);
                                                        $full_stars = floor($rating);
                                                        $half_star = ($rating - $full_stars) >= 0.5;
                                                        
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            if ($i <= $full_stars) {
                                                                echo '<i class="fas fa-star"></i>';
                                                            } elseif ($i == $full_stars + 1 && $half_star) {
                                                                echo '<i class="fas fa-star-half-alt"></i>';
                                                            } else {
                                                                echo '<i class="far fa-star"></i>';
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                    <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="technician-details">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($technician['email']); ?>
                                        </div>
                                        <div class="technician-details">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($technician['phone']); ?>
                                        </div>
                                        <?php if ($technician['experience']): ?>
                                            <div class="technician-experience">
                                                <i class="fas fa-briefcase"></i> <?php echo $technician['experience']; ?> years experience
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($technician['bio'])): ?>
                                            <div class="technician-bio">
                                                "<?php echo htmlspecialchars($technician['bio']); ?>"
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="submit-section">
                                <a href="bookings.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" name="assign_provider" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Assign Technician
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="no-technicians">
                            <i class="fas fa-user-slash"></i>
                            <h3>No Technicians Available</h3>
                            <p>There are no registered technicians in the system.</p>
                            <a href="users.php" class="btn btn-primary" style="margin-top: 20px;">
                                <i class="fas fa-user-plus"></i> Add Technician
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleSidebar = document.getElementById('toggle-sidebar');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');

            toggleSidebar.addEventListener('click', function () {
                sidebar.classList.toggle('active');
                mainContent.classList.toggle('pushed');
            });

            // Handle responsive behavior
            function handleResize() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('pushed');
                } else {
                    sidebar.classList.add('active');
                    mainContent.classList.add('pushed');
                }
            }

            handleResize();
            window.addEventListener('resize', handleResize);
        });

        function selectTechnician(techId, cardElement) {
            // Unselect all cards
            document.querySelectorAll('.technician-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Select the clicked card
            if (cardElement) {
                cardElement.classList.add('selected');
            }

            // Check the radio button
            const radio = document.getElementById('tech_' + techId);
            if (radio) {
                radio.checked = true;
                // Trigger change event to ensure form validation works
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Handle card clicks
        document.querySelectorAll('.technician-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on the radio button itself
                if (e.target.type === 'radio') {
                    return;
                }
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    const techId = radio.value;
                    selectTechnician(techId, this);
                }
            });
        });

        // Handle radio button clicks
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('click', function(e) {
                e.stopPropagation();
                const techId = this.value;
                const card = this.closest('.technician-card');
                selectTechnician(techId, card);
            });
        });

        // Form validation before submit
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const selectedRadio = document.querySelector('input[name="provider_id"]:checked');
                if (!selectedRadio || !selectedRadio.value || selectedRadio.value == '0') {
                    e.preventDefault();
                    alert('Please select a technician before submitting.');
                    return false;
                }
                // Log for debugging
                console.log('Form submitting with provider_id:', selectedRadio.value);
            });
        }

        // Debug: Log when radio buttons are checked
        document.querySelectorAll('input[name="provider_id"]').forEach(radio => {
            radio.addEventListener('change', function() {
                console.log('Technician selected:', this.value);
            });
        });
    </script>
</body>

</html>


