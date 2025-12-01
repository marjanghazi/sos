<?php
session_start();
include 'assets/include/dbconnect.php';

// Initialize messages from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

// Clear session messages after displaying
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Add new revenue authority
if (isset($_POST['add_authority'])) {
    $authority_name = $_POST['authority_name'];
    $tax_percentage = $_POST['tax_percentage'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $created_by = $_SESSION['user_id'] ?? 1; // Default to admin user

    $stmt = $conn->prepare("INSERT INTO revenue_authority (authority_name, tax_percentage, is_active, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdii", $authority_name, $tax_percentage, $is_active, $created_by);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Revenue authority added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding revenue authority: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();

    // Redirect to prevent form resubmission
    header("Location: revenue_authority.php");
    exit();
}

// Update revenue authority
if (isset($_POST['update_authority'])) {
    $id = $_POST['authority_id'];
    $authority_name = $_POST['authority_name'];
    $tax_percentage = $_POST['tax_percentage'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $updated_by = $_SESSION['user_id'] ?? 1; // Default to admin user

    $stmt = $conn->prepare("UPDATE revenue_authority SET authority_name = ?, tax_percentage = ?, is_active = ?, updated_by = ? WHERE id = ?");
    $stmt->bind_param("sdiii", $authority_name, $tax_percentage, $is_active, $updated_by, $id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Revenue authority updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating revenue authority: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();

    // Redirect to prevent form resubmission
    header("Location: revenue_authority.php");
    exit();
}

// Delete revenue authority
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $stmt = $conn->prepare("DELETE FROM revenue_authority WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Revenue authority deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting revenue authority: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();

    // Redirect to avoid resubmission
    header("Location: revenue_authority.php");
    exit();
}

// Fetch all revenue authorities for the table
$authorities_result = $conn->query("
    SELECT * FROM revenue_authority 
    ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Revenue Authority Management - GUARDING DASHBOARD</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/dashboard.png">

    <!-- Custom fonts for this template-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap4.min.css">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.css" rel="stylesheet">

    <style>
        .grad-nvb {
            background-image: linear-gradient(180deg, rgba(1, 47, 95, 1) -0.4%, rgba(56, 141, 217, 1) 106.1%);
            color: white;
        }

        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }

        .action-buttons .btn {
            margin-right: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .tax-badge {
            font-size: 0.9em;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: bold;
        }

        .tax-low {
            background-color: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }

        .tax-medium {
            background-color: #fff3e0;
            color: #ef6c00;
            border: 1px solid #ef6c00;
        }

        .tax-high {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #c62828;
        }

        .authority-icon {
            font-size: 1.2em;
            margin-right: 8px;
            color: #4e73df;
        }
    </style>
</head>

<body id="page-top sidebar-toggled" style="background-color:#F6F6F9!important;">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar included here-->
        <?php include 'assets/include/sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include 'assets/include/topbar.php'; ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Revenue Authority Management</h1>
                        <button class="d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addAuthorityModal">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Authority
                        </button>
                    </div>

                    <!-- Message Alert -->
                    <?php if (!empty($message)): ?>
                        <div class="alert <?php echo $message_type == 'success' ? 'alert-success' : 'alert-error'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Quick Stats Cards -->
                    <div class="row mb-4">
                        <!-- Total Authorities Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Authorities</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                $total_auth = $conn->query("SELECT COUNT(*) as total FROM revenue_authority")->fetch_assoc()['total'];
                                                echo $total_auth;
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-landmark fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Authorities Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Active Authorities</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                $active_auth = $conn->query("SELECT COUNT(*) as active FROM revenue_authority WHERE is_active = 1")->fetch_assoc()['active'];
                                                echo $active_auth;
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Average Tax Rate Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Avg. Tax Rate</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                $avg_tax = $conn->query("SELECT AVG(tax_percentage) as avg_tax FROM revenue_authority WHERE is_active = 1")->fetch_assoc()['avg_tax'];
                                                echo number_format($avg_tax ?? 0, 2) . '%';
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Highest Tax Rate Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Highest Tax Rate</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                $max_tax = $conn->query("SELECT MAX(tax_percentage) as max_tax FROM revenue_authority WHERE is_active = 1")->fetch_assoc()['max_tax'];
                                                echo number_format($max_tax ?? 0, 2) . '%';
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DataTable Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Revenue Authorities</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="authoritiesTable" class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Authority Name</th>
                                            <th>Tax Percentage</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Created At</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($authority = $authorities_result->fetch_assoc()):
                                            // Determine tax badge class
                                            $tax_class = 'tax-medium';
                                            if ($authority['tax_percentage'] < 10) {
                                                $tax_class = 'tax-low';
                                            } elseif ($authority['tax_percentage'] > 20) {
                                                $tax_class = 'tax-high';
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo $authority['id']; ?></td>
                                                <td>
                                                    <i class="fas fa-landmark authority-icon"></i>
                                                    <?php echo htmlspecialchars($authority['authority_name']); ?>
                                                </td>
                                                <td>
                                                    <span class="tax-badge <?php echo $tax_class; ?>">
                                                        <?php echo number_format($authority['tax_percentage'], 2); ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="<?php echo $authority['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo $authority['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($authority['created_by_name'] ?? 'System'); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($authority['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($authority['updated_at']): ?>
                                                        <?php echo date('M j, Y', strtotime($authority['updated_at'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Never</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="action-buttons">
                                                    <button class="btn btn-sm btn-primary edit-authority"
                                                        data-id="<?php echo $authority['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($authority['authority_name']); ?>"
                                                        data-tax="<?php echo $authority['tax_percentage']; ?>"
                                                        data-active="<?php echo $authority['is_active']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-authority"
                                                        data-id="<?php echo $authority['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($authority['authority_name']); ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Add Authority Modal -->
    <div class="modal fade" id="addAuthorityModal" tabindex="-1" role="dialog" aria-labelledby="addAuthorityModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAuthorityModalLabel">Add New Revenue Authority</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="revenue_authority.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="authority_name">Authority Name *</label>
                            <input type="text" class="form-control" id="authority_name" name="authority_name" placeholder="Enter authority name" required>
                        </div>
                        <div class="form-group">
                            <label for="tax_percentage">Tax Percentage *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="tax_percentage" name="tax_percentage"
                                    min="0" max="100" step="0.01" placeholder="Enter tax percentage" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">Enter percentage value (e.g., 15.50 for 15.5%)</small>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_authority" class="btn btn-primary">Add Authority</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Authority Modal -->
    <div class="modal fade" id="editAuthorityModal" tabindex="-1" role="dialog" aria-labelledby="editAuthorityModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAuthorityModalLabel">Edit Revenue Authority</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="revenue_authority.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_authority_id" name="authority_id">
                        <div class="form-group">
                            <label for="edit_authority_name">Authority Name *</label>
                            <input type="text" class="form-control" id="edit_authority_name" name="authority_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_tax_percentage">Tax Percentage *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="edit_tax_percentage" name="tax_percentage"
                                    min="0" max="100" step="0.01" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_authority" class="btn btn-primary">Update Authority</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteAuthorityModal" tabindex="-1" role="dialog" aria-labelledby="deleteAuthorityModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAuthorityModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete revenue authority: <strong id="delete_authority_name"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <a href="#" id="confirm_delete" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <?php include 'assets/include/logoutmodal.php'; ?>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- DataTables JavaScript -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#authoritiesTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                "order": [
                    [0, "desc"]
                ],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "language": {
                    "emptyTable": "No revenue authorities found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ authorities",
                    "infoEmpty": "Showing 0 to 0 of 0 authorities",
                    "infoFiltered": "(filtered from _MAX_ total authorities)",
                    "lengthMenu": "Show _MENU_ authorities",
                    "search": "Search:",
                    "zeroRecords": "No matching authorities found",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });

            // Edit authority button click
            $('.edit-authority').click(function() {
                var authorityId = $(this).data('id');
                var authorityName = $(this).data('name');
                var taxPercentage = $(this).data('tax');
                var isActive = $(this).data('active');

                $('#edit_authority_id').val(authorityId);
                $('#edit_authority_name').val(authorityName);
                $('#edit_tax_percentage').val(taxPercentage);
                $('#edit_is_active').prop('checked', isActive == 1);

                $('#editAuthorityModal').modal('show');
            });

            // Delete authority button click
            $('.delete-authority').click(function() {
                var authorityId = $(this).data('id');
                var authorityName = $(this).data('name');

                $('#delete_authority_name').text(authorityName);
                $('#confirm_delete').attr('href', 'revenue_authority.php?delete_id=' + authorityId);

                $('#deleteAuthorityModal').modal('show');
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);

            // Validate tax percentage input
            $('#tax_percentage, #edit_tax_percentage').on('input', function() {
                var value = parseFloat($(this).val());
                if (value < 0) {
                    $(this).val(0);
                } else if (value > 100) {
                    $(this).val(100);
                }
            });
        });
    </script>

</body>

</html>
<?php $conn->close(); ?>