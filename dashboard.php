<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Get user information
$user_name = $_SESSION['name'];
$user_erp = $_SESSION['username']; // Fixed: use username instead of user_id

// Get user's leave applications
$sql = "SELECT * FROM apply_leaves WHERE user_erp = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_erp);
$stmt->execute();
$leave_applications = $stmt->get_result();

// Get leave statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM apply_leaves WHERE user_erp = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("s", $user_erp);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

$stats_stmt->close();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOS Energy - Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-bolt me-2"></i>SOS Energy
            </a>

            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($user_name); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Welcome Card -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="card-title text-primary mb-2">
                                    Welcome back, <?php echo htmlspecialchars($user_name); ?>!
                                </h3>
                                <p class="text-muted mb-0">
                                    ERP ID: <?php echo htmlspecialchars($user_erp); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="bg-primary text-white rounded p-3 d-inline-block">
                                    <i class="fas fa-calendar-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mt-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo $stats['approved'] ?? 0; ?></h4>
                                <p class="card-text">Approved Leaves</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo $stats['pending'] ?? 0; ?></h4>
                                <p class="card-text">Pending Leaves</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo $stats['total'] ?? 0; ?></h4>
                                <p class="card-text">Total Applications</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-file-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo $stats['rejected'] ?? 0; ?></h4>
                                <p class="card-text">Rejected Leaves</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-times-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Leave Applications -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2 text-primary"></i>
                            Recent Leave Applications
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($leave_applications->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Leave Type</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Applied On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $leave_applications->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo ucfirst($row['leave_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                                                <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                                                <td>
                                                    <?php
                                                    $status = $row['status'];
                                                    $badge_class = 'bg-secondary';
                                                    if ($status == 'approved') $badge_class = 'bg-success';
                                                    if ($status == 'rejected') $badge_class = 'bg-danger';
                                                    if ($status == 'pending') $badge_class = 'bg-warning';
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($row['created_at']) {
                                                        echo date('M j, Y', strtotime($row['created_at']));
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No leave applications found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 SOS Energy Dashboard. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">ERP: <?php echo htmlspecialchars($user_erp); ?></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Simple dashboard interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.transition = 'transform 0.2s ease';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>

</html>