<?php
session_start();
include 'assets/include/dbconnect.php';

// Handle CRUD operations
$message = '';
$message_type = '';

// Add new customer
if (isset($_POST['add_customer'])) {
    $customer_code = $_POST['customer_code'];
    $customer_name = $_POST['customer_name'];
    $contact = $_POST['customer_contact'];
    $address = $_POST['customer_address'];
    $revenue_auth = $_POST['revenue_auth'];
    $status = isset($_POST['status']) ? 1 : 0;
    $created_by = $_SESSION['username'] ?? 'Admin';

    $stmt = $conn->prepare("INSERT INTO customers (customer_name, customer_code, contact, address, revenue_auth, status, created_by) VALUES (?, ?, ?, ?,?, ?, ?)");
    $stmt->bind_param("sssssis", $customer_name, $customer_code, $contact, $address, $revenue_auth, $status, $created_by);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Customer added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding customer: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
    // Redirect to avoid resubmission
    header("Location: customers.php");
    exit();
}

// Update customer
if (isset($_POST['update_customer'])) {
    $customer_code = $_POST['customer_code'];
    $customer_id = $_POST['customer_id'];
    $customer_name = $_POST['customer_name'];
    $customer_address = $_POST['customer_address'];
    $customer_contact = $_POST['customer_contact'];
    $revenue_auth = $_POST['revenue_auth'];
    $status = isset($_POST['status']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE customers SET customer_name = ?, customer_code = ?, contact = ?, address = ?, revenue_auth = ?, status = ? WHERE customer_id = ?");
    $stmt->bind_param("sssssii", $customer_name, $customer_code, $customer_contact, $customer_address, $revenue_auth, $status, $customer_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Customer updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating customer: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
}

// Delete customer
if (isset($_GET['delete_id'])) {
    $customer_id = $_GET['delete_id'];

    // Check if the customer is being used in cash_disbursement_summary table
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cash_disbursement_summary WHERE customer_id = ?");
    $check_stmt->bind_param("i", $customer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    $check_stmt->close();

    if ($check_row['count'] > 0) {
        // Customer is being used in cash_disbursement_summary, cannot delete
        $_SESSION['message'] = "Cannot delete customer! This entry is being used in cash disbursement records.";
        $_SESSION['message_type'] = "error";
    } else {
        // Customer is not being used, proceed with deletion
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Customer deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting customer: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }

    // Redirect to avoid resubmission and clear URL parameters
    header("Location: customers.php");
    exit();
}

// Fetch all customers for the table
$customers_result = $conn->query("SELECT * FROM customers ORDER BY customer_id DESC");

// Check for session messages
if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];

    // Clear session messages after displaying
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Also check for URL messages (for backward compatibility)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['type'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Customers Management</title>
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
            margin-bottom: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }

        .revenue-auth-badge {
            font-size: 0.8em;
            padding: 4px 8px;
            border-radius: 12px;
            white-space: nowrap;
        }

        .revenue-auth-high {
            background-color: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }

        .revenue-auth-medium {
            background-color: #fff3e0;
            color: #ef6c00;
            border: 1px solid #ef6c00;
        }

        .revenue-auth-low {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #c62828;
        }

        .revenue-auth-premium {
            background-color: #f3e5f5;
            color: #7b1fa2;
            border: 1px solid #7b1fa2;
        }

        .alert i {
            margin-right: 10px;
        }

        .close {
            color: #000;
            opacity: 0.5;
        }

        .close:hover {
            opacity: 0.8;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .d-sm-flex {
                flex-direction: column !important;
                align-items: flex-start !important;
            }
            
            .d-sm-flex .btn {
                margin-top: 10px;
                align-self: flex-start;
            }
            
            .modal-dialog {
                margin: 10px;
            }
            
            .action-buttons {
                min-width: 150px;
            }
            
            .action-buttons .btn {
                display: block;
                width: 100%;
                margin-right: 0;
                margin-bottom: 5px;
            }
            
            .table-responsive {
                border: none;
            }
            
            #customersTable_wrapper .row:first-child {
                flex-direction: column;
            }
            
            #customersTable_wrapper .col-sm-12 {
                margin-bottom: 10px;
            }
            
            #customersTable_wrapper .dataTables_filter {
                text-align: left !important;
            }
            
            #customersTable_wrapper .dataTables_length,
            #customersTable_wrapper .dataTables_filter {
                padding: 0;
            }
            
            .revenue-auth-badge {
                font-size: 0.75em;
                padding: 3px 6px;
            }
            
            /* Hide some columns on mobile for better readability */
            .mobile-hide {
                display: none !important;
            }
        }
        
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .h3 {
                font-size: 1.5rem;
            }
            
            .modal-content {
                margin: 0 10px;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .action-buttons .btn i {
                margin-right: 5px;
            }
            
            td, th {
                padding: 0.5rem !important;
                font-size: 0.9rem;
            }
            
            .revenue-auth-badge {
                font-size: 0.7em;
                padding: 2px 4px;
            }
        }
        
        @media (max-width: 360px) {
            .h3 {
                font-size: 1.3rem;
            }
            
            .btn {
                font-size: 0.85rem;
                padding: 0.375rem 0.75rem;
            }
            
            .modal-header h5 {
                font-size: 1.1rem;
            }
            
            .form-group label {
                font-size: 0.9rem;
            }
            
            .modal-body .form-group {
                margin-bottom: 1rem;
            }
            
            .modal-body .form-control {
                padding: 0.375rem 0.75rem;
                font-size: 0.9rem;
            }
        }
        
        /* Column priority for mobile */
        @media (max-width: 768px) {
            .customer-code-col {
                min-width: 80px;
            }
            
            .customer-name-col {
                min-width: 120px;
            }
            
            .customer-contact-col {
                min-width: 100px;
                word-break: break-word;
            }
            
            .customer-address-col {
                min-width: 150px;
                word-break: break-word;
            }
            
            .revenue-auth-col {
                min-width: 90px;
            }
            
            .status-col {
                min-width: 70px;
            }
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
                <div class="container-fluid px-md-4 px-sm-3 px-2">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Customers Management</h1>
                        <button class="d-sm-inline-block btn btn-sm btn-primary shadow-sm mt-sm-0 mt-2" data-toggle="modal" data-target="#addCustomerModal">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Customer
                        </button>
                    </div>

                    <!-- Message Alert -->
                    <?php if (!empty($message)): ?>
                        <div class="alert <?php echo $message_type == 'success' ? 'alert-success' : ($message_type == 'error' ? 'alert-error' : 'alert-warning'); ?> alert-dismissible fade show" role="alert">
                            <?php if ($message_type == 'error'): ?>
                                <i class="fas fa-times-circle"></i>
                            <?php elseif ($message_type == 'success'): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle"></i>
                            <?php endif; ?>
                            <?php echo $message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- DataTable Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Customers</h6>
                        </div>
                        <div class="card-body p-md-4 p-sm-3 p-2">
                            <div class="table-responsive">
                                <table id="customersTable" class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th class="customer-code-col">Customer Code</th>
                                            <th class="customer-name-col">Customer Name</th>
                                            <th class="customer-contact-col d-none d-md-table-cell">Customer Contact</th>
                                            <th class="customer-address-col d-none d-md-table-cell">Customer Address</th>
                                            <th class="revenue-auth-col">Revenue Auth</th>
                                            <th class="status-col">Status</th>
                                            <th class="d-none d-md-table-cell">Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($customer = $customers_result->fetch_assoc()):
                                            $revenue_auth_class = '';
                                            switch (strtolower($customer['revenue_auth'])) {
                                                case 'high':
                                                    $revenue_auth_class = 'revenue-auth-high';
                                                    break;
                                                case 'medium':
                                                    $revenue_auth_class = 'revenue-auth-medium';
                                                    break;
                                                case 'low':
                                                    $revenue_auth_class = 'revenue-auth-low';
                                                    break;
                                                case 'premium':
                                                    $revenue_auth_class = 'revenue-auth-premium';
                                                    break;
                                                default:
                                                    $revenue_auth_class = 'revenue-auth-medium';
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo $customer['customer_id']; ?></td>
                                                <td class="customer-code-col"><?php echo htmlspecialchars($customer['customer_code']) ?></td>
                                                <td class="customer-name-col"><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                                <td class="customer-contact-col d-none d-md-table-cell"><?php echo htmlspecialchars($customer['contact']) ?></td>
                                                <td class="customer-address-col d-none d-md-table-cell"><?php echo htmlspecialchars($customer['address']); ?></td>
                                                <td class="revenue-auth-col">
                                                    <span class="revenue-auth-badge <?php echo $revenue_auth_class; ?>">
                                                        <?php echo htmlspecialchars($customer['revenue_auth']); ?>
                                                    </span>
                                                </td>
                                                <td class="status-col">
                                                    <span class="<?php echo $customer['status'] ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo $customer['status'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($customer['created_by']); ?></td>
                                                <td class="action-buttons">
                                                    <button class="btn btn-sm btn-primary edit-customer"
                                                        data-id="<?php echo $customer['customer_id']; ?>"
                                                        data-code="<?php echo htmlspecialchars($customer['customer_code']); ?>"
                                                        data-name="<?php echo htmlspecialchars($customer['customer_name']); ?>"
                                                        data-contact="<?php echo htmlspecialchars($customer['contact']); ?>"
                                                        data-address="<?php echo htmlspecialchars($customer['address']); ?>"
                                                        data-revenue-auth="<?php echo htmlspecialchars($customer['revenue_auth']); ?>"
                                                        data-status="<?php echo $customer['status']; ?>">
                                                        <i class="fas fa-edit"></i> <span class="d-none d-md-inline">Edit</span>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-customer"
                                                        data-id="<?php echo $customer['customer_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($customer['customer_name']); ?>">
                                                        <i class="fas fa-trash"></i> <span class="d-none d-md-inline">Delete</span>
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

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="customers.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_code">Customer Code *</label>
                                    <input type="text" class="form-control" id="customer_code" name="customer_code" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_name">Customer Name *</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_contact">Customer Contact</label>
                                    <input type="text" class="form-control" id="customer_contact" name="customer_contact">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="revenue_auth">Revenue Authorization *</label>
                                    <select class="form-control" id="revenue_auth" name="revenue_auth" required>
                                        <option value="">Select Revenue Authorization</option>
                                        <option value="Premium">Premium</option>
                                        <option value="High">High</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Low">Low</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="customer_address">Customer Address</label>
                                    <textarea class="form-control" id="customer_address" name="customer_address" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                        <label class="form-check-label" for="status">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_customer" class="btn btn-primary">Add Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="customers.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_customer_id" name="customer_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_customer_code">Customer Code *</label>
                                    <input type="text" class="form-control" id="edit_customer_code" name="customer_code" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_customer_name">Customer Name *</label>
                                    <input type="text" class="form-control" id="edit_customer_name" name="customer_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_customer_contact">Customer Contact</label>
                                    <input type="text" class="form-control" id="edit_customer_contact" name="customer_contact">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_revenue_auth">Revenue Authorization *</label>
                                    <select class="form-control" id="edit_revenue_auth" name="revenue_auth" required>
                                        <option value="">Select Revenue Authorization</option>
                                        <option value="Premium">Premium</option>
                                        <option value="High">High</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Low">Low</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="edit_customer_address">Customer Address</label>
                                    <textarea class="form-control" id="edit_customer_address" name="customer_address" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit_status" name="status">
                                        <label class="form-check-label" for="edit_status">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_customer" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCustomerModal" tabindex="-1" role="dialog" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCustomerModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete customer: <strong id="delete_customer_name"></strong>?</p>
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
            // Initialize DataTable with responsive settings
            $('#customersTable').DataTable({
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
                    "emptyTable": "No customers found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ customers",
                    "infoEmpty": "Showing 0 to 0 of 0 customers",
                    "infoFiltered": "(filtered from _MAX_ total customers)",
                    "lengthMenu": "Show _MENU_ customers",
                    "search": "Search:",
                    "zeroRecords": "No matching customers found",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                },
                "responsive": true,
                "autoWidth": false
            });

            // Edit customer button click
            $('.edit-customer').click(function() {
                var customerId = $(this).data('id');
                var customerCode = $(this).data('code');
                var customerName = $(this).data('name');
                var customerContact = $(this).data('contact');
                var customerAddress = $(this).data('address');
                var revenueAuth = $(this).data('revenue-auth');
                var status = $(this).data('status');

                $('#edit_customer_id').val(customerId);
                $('#edit_customer_code').val(customerCode);
                $('#edit_customer_name').val(customerName);
                $('#edit_customer_contact').val(customerContact);
                $('#edit_customer_address').val(customerAddress);
                $('#edit_revenue_auth').val(revenueAuth);
                $('#edit_status').prop('checked', status == 1);

                $('#editCustomerModal').modal('show');
            });

            // Delete customer button click
            $('.delete-customer').click(function() {
                var customerId = $(this).data('id');
                var customerName = $(this).data('name');

                $('#delete_customer_name').text(customerName);
                $('#confirm_delete').attr('href', 'customers.php?delete_id=' + customerId);

                $('#deleteCustomerModal').modal('show');
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);

            // Clear URL parameters to prevent resubmission on reload
            if (window.history.replaceState && (window.location.search.includes('delete_id') || window.location.search.includes('message'))) {
                var cleanURL = window.location.origin + window.location.pathname;
                window.history.replaceState({}, document.title, cleanURL);
            }
            
            // Handle window resize for better mobile experience
            $(window).resize(function() {
                $('#customersTable').DataTable().columns.adjust();
            });
        });
    </script>

</body>

</html>
<?php $conn->close(); ?>