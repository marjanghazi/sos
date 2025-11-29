<?php
session_start();
include 'assets/include/dbconnect.php';

// Initialize messages from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

// Clear session messages after displaying
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Add new cash disbursement summary
if (isset($_POST['add_summary'])) {
    $transaction_date = $_POST['transaction_date'];
    $city_id = $_POST['city_id'];
    $camp_site_id = $_POST['camp_site_id'];
    $setup_fee_applied = $_POST['setup_fee_applied'];
    $setup_fee_type = $_POST['setup_fee_type'];
    $total_transactions = $_POST['total_transactions'];
    $total_amount = $_POST['total_amount'];
    $created_by = $_SESSION['username'] ?? 'Admin';
    
    $stmt = $conn->prepare("INSERT INTO cash_disbursement_summary (transaction_date, city_id, camp_site_id, setup_fee_applied, setup_fee_type, total_transactions, total_amount, created_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("siissiis", $transaction_date, $city_id, $camp_site_id, $setup_fee_applied, $setup_fee_type, $total_transactions, $total_amount, $created_by);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Cash disbursement summary added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding cash disbursement summary: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: cash_disbursement.php");
    exit();
}

// Update cash disbursement summary
if (isset($_POST['update_summary'])) {
    $summary_id = $_POST['summary_id'];
    $transaction_date = $_POST['transaction_date'];
    $city_id = $_POST['city_id'];
    $camp_site_id = $_POST['camp_site_id'];
    $setup_fee_applied = $_POST['setup_fee_applied'];
    $setup_fee_type = $_POST['setup_fee_type'];
    $total_transactions = $_POST['total_transactions'];
    $total_amount = $_POST['total_amount'];
    
    $stmt = $conn->prepare("UPDATE cash_disbursement_summary SET transaction_date = ?, city_id = ?, camp_site_id = ?, setup_fee_applied = ?, setup_fee_type = ?, total_transactions = ?, total_amount = ? WHERE summary_id = ?");
    $stmt->bind_param("siissiii", $transaction_date, $city_id, $camp_site_id, $setup_fee_applied, $setup_fee_type, $total_transactions, $total_amount, $summary_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Cash disbursement summary updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating cash disbursement summary: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: cash_disbursement.php");
    exit();
}

// Delete cash disbursement summary
if (isset($_GET['delete_id'])) {
    $summary_id = $_GET['delete_id'];
    
    $stmt = $conn->prepare("DELETE FROM cash_disbursement_summary WHERE summary_id = ?");
    $stmt->bind_param("i", $summary_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Cash disbursement summary deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting cash disbursement summary: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
    
    // Redirect to avoid resubmission
    header("Location: cash_disbursement.php");
    exit();
}

// Fetch all cities and camp sites for dropdowns
$cities_result = $conn->query("SELECT * FROM cities WHERE status = 1 ORDER BY city_name");
$camp_sites_result = $conn->query("SELECT * FROM camp_sites WHERE status = 1 ORDER BY camp_site_name");

// Fetch all cash disbursement summaries for the table with related data
$summaries_result = $conn->query("
    SELECT cds.*, c.city_name, cs.camp_site_name 
    FROM cash_disbursement_summary cds 
    LEFT JOIN cities c ON cds.city_id = c.city_id 
    LEFT JOIN camp_sites cs ON cds.camp_site_id = cs.camp_site_id 
    ORDER BY cds.summary_id DESC
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

    <title>Cash Disbursement Summary - GUARDING DASHBOARD</title>
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
        
        .amount-badge {
            font-size: 0.9em;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: bold;
        }
        
        .amount-positive {
            background-color: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }
        
        .setup-fee-badge {
            font-size: 0.8em;
            padding: 3px 6px;
            border-radius: 8px;
        }
        
        .setup-fee-yes {
            background-color: #fff3e0;
            color: #ef6c00;
            border: 1px solid #ef6c00;
        }
        
        .setup-fee-no {
            background-color: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }
        
        .stats-card {
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .transaction-icon {
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
                        <h1 class="h3 mb-0 text-gray-800">Cash Disbursement Summary</h1>
                        <button class="d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addSummaryModal">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Summary
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
                    <div class="row mb-4 d-none">
                        <!-- Total Amount Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Amount</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $total_amount = $conn->query("SELECT SUM(total_amount) as total FROM cash_disbursement_summary")->fetch_assoc()['total'];
                                                echo 'PKR ' . number_format($total_amount ?? 0);
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Transactions Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Transactions</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $total_transactions = $conn->query("SELECT SUM(total_transactions) as total FROM cash_disbursement_summary")->fetch_assoc()['total'];
                                                echo number_format($total_transactions ?? 0);
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Average Amount Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Avg. per Transaction</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $avg_amount = ($total_transactions > 0 && $total_amount > 0) ? round($total_amount / $total_transactions, 2) : 0;
                                                echo 'PKR ' . number_format($avg_amount);
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calculator fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Records Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total Records</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $total_records = $conn->query("SELECT COUNT(*) as total FROM cash_disbursement_summary")->fetch_assoc()['total'];
                                                echo number_format($total_records);
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-database fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DataTable Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Cash Disbursement Summaries</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="summariesTable" class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Transaction Date</th>
                                            <th>City</th>
                                            <th>Camp Site</th>
                                            <th>Setup Fee</th>
                                            <th>Fee Type</th>
                                            <th>Transactions</th>
                                            <th>Total Amount</th>
                                            <th>Created Date</th>
                                            <th class="d-none">Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($summary = $summaries_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $summary['summary_id']; ?></td>
                                            <td>
                                                <i class="fas fa-calendar transaction-icon"></i>
                                                <?php echo date('M j, Y', strtotime($summary['transaction_date'])); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($summary['city_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($summary['camp_site_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="setup-fee-badge <?php echo $summary['setup_fee_applied'] == 'Yes' ? 'setup-fee-yes' : 'setup-fee-no'; ?>">
                                                    <?php echo htmlspecialchars($summary['setup_fee_applied']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($summary['setup_fee_type']); ?></td>
                                            <td>
                                                <span class="amount-badge">
                                                    <?php echo number_format($summary['total_transactions']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="amount-badge amount-positive">
                                                    PKR <?php echo number_format($summary['total_amount']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($summary['created_date'])); ?></td>
                                            <td class="d-none"><?php echo htmlspecialchars($summary['created_by']); ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-sm btn-primary edit-summary" 
                                                        data-id="<?php echo $summary['summary_id']; ?>"
                                                        data-transaction-date="<?php echo $summary['transaction_date']; ?>"
                                                        data-city-id="<?php echo $summary['city_id']; ?>"
                                                        data-camp-site-id="<?php echo $summary['camp_site_id']; ?>"
                                                        data-setup-fee-applied="<?php echo htmlspecialchars($summary['setup_fee_applied']); ?>"
                                                        data-setup-fee-type="<?php echo htmlspecialchars($summary['setup_fee_type']); ?>"
                                                        data-total-transactions="<?php echo $summary['total_transactions']; ?>"
                                                        data-total-amount="<?php echo $summary['total_amount']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-summary" 
                                                        data-id="<?php echo $summary['summary_id']; ?>"
                                                        data-date="<?php echo date('M j, Y', strtotime($summary['transaction_date'])); ?>">
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

    <!-- Add Summary Modal -->
    <div class="modal fade" id="addSummaryModal" tabindex="-1" role="dialog" aria-labelledby="addSummaryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSummaryModalLabel">Add Cash Disbursement Summary</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="cash_disbursement.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transaction_date">Transaction Date *</label>
                                    <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="city_id">City *</label>
                                    <select class="form-control" id="city_id" name="city_id" required>
                                        <option value="">Select City</option>
                                        <?php while($city = $cities_result->fetch_assoc()): ?>
                                            <option value="<?php echo $city['city_id']; ?>"><?php echo htmlspecialchars($city['city_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="camp_site_id">Camp Site *</label>
                                    <select class="form-control" id="camp_site_id" name="camp_site_id" required>
                                        <option value="">Select Camp Site</option>
                                        <?php while($camp_site = $camp_sites_result->fetch_assoc()): ?>
                                            <option value="<?php echo $camp_site['camp_site_id']; ?>"><?php echo htmlspecialchars($camp_site['camp_site_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="setup_fee_applied">Setup Fee Applied *</label>
                                    <select class="form-control" id="setup_fee_applied" name="setup_fee_applied" required>
                                        <option value="">Select Option</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="setup_fee_type">Setup Fee Type</label>
                                    <select class="form-control" id="setup_fee_type" name="setup_fee_type">
                                        <option value="">Select Fee Type</option>
                                        <option value="Fixed">Fixed</option>
                                        <option value="Percentage">Percentage</option>
                                        <option value="Variable">Variable</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="total_transactions">Total Transactions *</label>
                                    <input type="number" class="form-control" id="total_transactions" name="total_transactions" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="total_amount">Total Amount (PKR) *</label>
                                    <input type="number" class="form-control" id="total_amount" name="total_amount" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_summary" class="btn btn-primary">Add Summary</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Summary Modal -->
    <div class="modal fade" id="editSummaryModal" tabindex="-1" role="dialog" aria-labelledby="editSummaryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSummaryModalLabel">Edit Cash Disbursement Summary</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="cash_disbursement.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_summary_id" name="summary_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_transaction_date">Transaction Date *</label>
                                    <input type="datetime-local" class="form-control" id="edit_transaction_date" name="transaction_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_city_id">City *</label>
                                    <select class="form-control" id="edit_city_id" name="city_id" required>
                                        <option value="">Select City</option>
                                        <?php 
                                        $cities_result->data_seek(0);
                                        while($city = $cities_result->fetch_assoc()): ?>
                                            <option value="<?php echo $city['city_id']; ?>"><?php echo htmlspecialchars($city['city_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_camp_site_id">Camp Site *</label>
                                    <select class="form-control" id="edit_camp_site_id" name="camp_site_id" required>
                                        <option value="">Select Camp Site</option>
                                        <?php 
                                        $camp_sites_result->data_seek(0);
                                        while($camp_site = $camp_sites_result->fetch_assoc()): ?>
                                            <option value="<?php echo $camp_site['camp_site_id']; ?>"><?php echo htmlspecialchars($camp_site['camp_site_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_setup_fee_applied">Setup Fee Applied *</label>
                                    <select class="form-control" id="edit_setup_fee_applied" name="setup_fee_applied" required>
                                        <option value="">Select Option</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_setup_fee_type">Setup Fee Type</label>
                                    <select class="form-control" id="edit_setup_fee_type" name="setup_fee_type">
                                        <option value="">Select Fee Type</option>
                                        <option value="Fixed">Fixed</option>
                                        <option value="Percentage">Percentage</option>
                                        <option value="Variable">Variable</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_total_transactions">Total Transactions *</label>
                                    <input type="number" class="form-control" id="edit_total_transactions" name="total_transactions" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="edit_total_amount">Total Amount (PKR) *</label>
                                    <input type="number" class="form-control" id="edit_total_amount" name="total_amount" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_summary" class="btn btn-primary">Update Summary</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteSummaryModal" tabindex="-1" role="dialog" aria-labelledby="deleteSummaryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSummaryModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete cash disbursement summary for date: <strong id="delete_summary_date"></strong>?</p>
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
            $('#summariesTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "order": [[0, "desc"]],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                       '<"row"<"col-sm-12"tr>>' +
                       '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "language": {
                    "emptyTable": "No cash disbursement summaries found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ summaries",
                    "infoEmpty": "Showing 0 to 0 of 0 summaries",
                    "infoFiltered": "(filtered from _MAX_ total summaries)",
                    "lengthMenu": "Show _MENU_ summaries",
                    "search": "Search:",
                    "zeroRecords": "No matching summaries found",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
            
            // Edit summary button click
            $('.edit-summary').click(function() {
                var summaryId = $(this).data('id');
                var transactionDate = $(this).data('transaction-date').replace(' ', 'T').substring(0, 16);
                var cityId = $(this).data('city-id');
                var campSiteId = $(this).data('camp-site-id');
                var setupFeeApplied = $(this).data('setup-fee-applied');
                var setupFeeType = $(this).data('setup-fee-type');
                var totalTransactions = $(this).data('total-transactions');
                var totalAmount = $(this).data('total-amount');
                
                $('#edit_summary_id').val(summaryId);
                $('#edit_transaction_date').val(transactionDate);
                $('#edit_city_id').val(cityId);
                $('#edit_camp_site_id').val(campSiteId);
                $('#edit_setup_fee_applied').val(setupFeeApplied);
                $('#edit_setup_fee_type').val(setupFeeType);
                $('#edit_total_transactions').val(totalTransactions);
                $('#edit_total_amount').val(totalAmount);
                
                $('#editSummaryModal').modal('show');
            });
            
            // Delete summary button click
            $('.delete-summary').click(function() {
                var summaryId = $(this).data('id');
                var summaryDate = $(this).data('date');
                
                $('#delete_summary_date').text(summaryDate);
                $('#confirm_delete').attr('href', 'cash_disbursement.php?delete_id=' + summaryId);
                
                $('#deleteSummaryModal').modal('show');
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>

</body>

</html>
<?php $conn->close(); ?>