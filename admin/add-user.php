<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $role = sanitize_input($_POST['role']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($role) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!in_array($role, ['user', 'admin', 'provider'])) {
        $error = "Invalid role selected.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            // Check if phone already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Phone number already exists.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // If role is provider, create service_provider entry
                    if ($role === 'provider') {
                        $sp_stmt = $conn->prepare("INSERT INTO service_providers (user_id, bio, experience, rating, is_verified, created_at) VALUES (?, '', 0, 0.00, 0, NOW())");
                        $sp_stmt->bind_param("i", $user_id);
                        $sp_stmt->execute();
                        $sp_stmt->close();
                    }
                    
                    $success = "User added successfully!";
                    header("refresh:2;url=users.php");
                } else {
                    $error = "Error adding user: " . $stmt->error;
                }
                $stmt->close();
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - Service Lagbe Admin</title>
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
            max-width: 800px;
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

        .form-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
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
                <a href="users.php" class="menu-item active">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="services.php" class="menu-item">
                    <i class="fas fa-tools"></i> Services
                </a>
                <a href="bookings.php" class="menu-item">
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
                <a href="users.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>

                <h1 class="page-title">Add New User</h1>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="form-card">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Full Name <span style="color: red;">*</span></label>
                            <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span style="color: red;">*</span></label>
                            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone <span style="color: red;">*</span></label>
                            <input type="tel" id="phone" name="phone" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="role">Role <span style="color: red;">*</span></label>
                            <select id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="user" <?php echo (isset($_POST['role']) && $_POST['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="provider" <?php echo (isset($_POST['role']) && $_POST['role'] === 'provider') ? 'selected' : ''; ?>>Provider (Technician)</option>
                                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="password">Password <span style="color: red;">*</span></label>
                            <input type="password" id="password" name="password" required minlength="6">
                            <small>Password must be at least 6 characters long</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password <span style="color: red;">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>

                        <div class="form-actions">
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add User
                            </button>
                        </div>
                    </form>
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

            // Password confirmation validation
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity("Passwords do not match");
                } else {
                    confirmPassword.setCustomValidity("");
                }
            }
            
            password.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        });
    </script>
</body>

</html>


