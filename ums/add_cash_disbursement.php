<?php
session_start();
include 'assets/include/dbconnect.php';

// Initialize messages from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

// Clear session messages after displaying
if (isset($_SESSION['message'])) unset($_SESSION['message']);
if (isset($_SESSION['message_type'])) unset($_SESSION['message_type']);

// Initialize variables
$summary_data = null;
$details_data = [];

// Add new cash disbursement summary
if (isset($_POST['add_summary'])) {
    // Get inputs directly without validation
    $transaction_date = $_POST['transaction_date'] ?? '';
    $city_id = $_POST['city_id'] ?? 0;
    $camp_site_id = $_POST['camp_site_id'] ?? 0;
    $setup_fee_applied = $_POST['setup_fee_applied'] ?? '';
    $setup_fee_type = $_POST['setup_fee_type'] ?? '';
    $total_transactions = $_POST['total_transactions'] ?? 0;
    $total_amount = $_POST['total_amount'] ?? 0;
    $created_by = $_SESSION['username'] ?? 'Admin';
    $customer_id = $_POST['customer_id'] ?? 0;
    $authority_id = $_POST['authority_id'] ?? 0;

    // Insert new summary without validation
    $stmt = $conn->prepare("INSERT INTO cash_disbursement_summary (transaction_date, city_id, camp_site_id, setup_fee_applied, setup_fee_type, total_transactions, total_amount, customer_id, authority_id, created_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("siissdiiss", $transaction_date, $city_id, $camp_site_id, $setup_fee_applied, $setup_fee_type, $total_transactions, $total_amount, $customer_id, $authority_id, $created_by);

    if ($stmt->execute()) {
        $summary_id = (int)$stmt->insert_id;
        $_SESSION['message'] = "Cash disbursement summary added successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['current_summary_id'] = $summary_id;

        // Redirect to details tab after adding summary
        header("Location: add_cash_disbursement.php?tab=details");
        exit();
    } else {
        $message = "Error: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Check if we have a summary ID in session
$summary_id = isset($_SESSION['current_summary_id']) ? (int)$_SESSION['current_summary_id'] : 0;

// Fetch summary data if we have an ID
if ($summary_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM cash_disbursement_summary WHERE summary_id = ?");
    $stmt->bind_param("i", $summary_id);
    if ($stmt->execute()) {
        $summary_result = $stmt->get_result();
        $summary_data = $summary_result->fetch_assoc();
    }
    $stmt->close();
}

// Add new detail entry
if (isset($_POST['add_detail'])) {
    // Get inputs directly without validation
    $device_id = $_POST['device_id'] ?? 0;
    $agent_id = $_POST['agent_id'] ?? 0;
    $person_name = $_POST['person_name'] ?? '';
    $cit_shipment_ref = $_POST['cit_shipment_ref'] ?? '';
    $num_transactions = $_POST['num_transactions'] ?? 0;
    $total_trans_amount = $_POST['total_trans_amount'] ?? 0;
    $detail_transaction_date = $_POST['detail_transaction_date'] ?? '';
    $created_by = $_SESSION['username'] ?? 'Admin';

    // Insert detail without validation
    $stmt = $conn->prepare("INSERT INTO cash_disbursement_details (summary_id, device_id, agent_id, person_name, cit_shipment_ref, num_transactions, total_trans_amount, transaction_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissdiss", $summary_id, $device_id, $agent_id, $person_name, $cit_shipment_ref, $num_transactions, $total_trans_amount, $detail_transaction_date, $created_by);

    if ($stmt->execute()) {
        // Update summary totals
        $update_stmt = $conn->prepare("UPDATE cash_disbursement_summary SET 
            total_transactions = total_transactions + ?,
            total_amount = total_amount + ?
            WHERE summary_id = ?");
        $update_stmt->bind_param("ddi", $num_transactions, $total_trans_amount, $summary_id);
        $update_stmt->execute();
        $update_stmt->close();

        $_SESSION['message'] = "Transaction detail added successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: add_cash_disbursement.php?tab=details");
        exit();
    } else {
        $message = "Error adding transaction detail: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Delete detail entry
if (isset($_GET['delete_detail'])) {
    $detail_id = $_GET['delete_detail'] ?? 0;

    if ($detail_id) {
        // Get detail amount before deleting
        $stmt = $conn->prepare("SELECT num_transactions, total_trans_amount FROM cash_disbursement_details WHERE detail_id = ? AND summary_id = ?");
        $stmt->bind_param("ii", $detail_id, $summary_id);
        $stmt->execute();
        $stmt->bind_result($num_trans, $amount);
        $stmt->fetch();
        $stmt->close();

        // Delete the detail
        $stmt = $conn->prepare("DELETE FROM cash_disbursement_details WHERE detail_id = ? AND summary_id = ?");
        $stmt->bind_param("ii", $detail_id, $summary_id);

        if ($stmt->execute()) {
            // Update summary totals
            $update_stmt = $conn->prepare("UPDATE cash_disbursement_summary SET 
                total_transactions = total_transactions - ?,
                total_amount = total_amount - ?
                WHERE summary_id = ?");
            $update_stmt->bind_param("ddi", $num_trans, $amount, $summary_id);
            $update_stmt->execute();
            $update_stmt->close();

            $_SESSION['message'] = "Transaction detail deleted successfully!";
            $_SESSION['message_type'] = "success";
        }
        $stmt->close();
    }
    header("Location: add_cash_disbursement.php?tab=details");
    exit();
}

// Reset form to add new summary
if (isset($_GET['reset'])) {
    unset($_SESSION['current_summary_id']);
    $summary_id = 0;
    $summary_data = null;
    $details_data = [];
    header("Location: add_cash_disbursement.php");
    exit();
}

// Fetch all data for dropdowns
$cities_result = $conn->query("SELECT * FROM cities WHERE status = 1 ORDER BY city_name") or die($conn->error);
$customers_result = $conn->query("SELECT * FROM customers WHERE status = 1 ORDER BY customer_name") or die($conn->error);
$camp_sites_result = $conn->query("SELECT * FROM camp_sites WHERE status = 1 ORDER BY camp_site_name") or die($conn->error);
$authorities_result = $conn->query("SELECT * FROM revenue_authority WHERE is_active = 1 ORDER BY authority_name") or die($conn->error);
$devices_result = $conn->query("SELECT * FROM devices WHERE status = 1 ORDER BY device_name") or die($conn->error);
$agents_result = $conn->query("SELECT * FROM agents WHERE status = 1 ORDER BY agent_name") or die($conn->error);

// Store results in arrays to use multiple times
$cities = $cities_result->fetch_all(MYSQLI_ASSOC);
$customers = $customers_result->fetch_all(MYSQLI_ASSOC);
$camp_sites = $camp_sites_result->fetch_all(MYSQLI_ASSOC);
$authorities = $authorities_result->fetch_all(MYSQLI_ASSOC);
$devices = $devices_result->fetch_all(MYSQLI_ASSOC);
$agents = $agents_result->fetch_all(MYSQLI_ASSOC);

// Free results
$cities_result->free();
$customers_result->free();
$camp_sites_result->free();
$authorities_result->free();
$devices_result->free();
$agents_result->free();

// Fetch details if we have a summary
if ($summary_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM cash_disbursement_details WHERE summary_id = ? ORDER BY transaction_date DESC");
    $stmt->bind_param("i", $summary_id);
    if ($stmt->execute()) {
        $details_result = $stmt->get_result();
        while ($row = $details_result->fetch_assoc()) {
            $details_data[] = $row;
        }
    }
    $stmt->close();
}

// Calculate statistics if we have details
$statistics = [];
if (!empty($details_data)) {
    $total_details = count($details_data);
    $total_amount_details = array_sum(array_column($details_data, 'total_trans_amount'));
    $total_transactions_details = array_sum(array_column($details_data, 'num_transactions'));

    // Group by agent
    $agents_summary = [];
    foreach ($details_data as $detail) {
        $agent_id = $detail['agent_id'];
        if (!isset($agents_summary[$agent_id])) {
            $agents_summary[$agent_id] = [
                'count' => 0,
                'amount' => 0,
                'transactions' => 0
            ];
        }
        $agents_summary[$agent_id]['count']++;
        $agents_summary[$agent_id]['amount'] += $detail['total_trans_amount'];
        $agents_summary[$agent_id]['transactions'] += $detail['num_transactions'];
    }

    $statistics = [
        'total_details' => $total_details,
        'total_amount_details' => $total_amount_details,
        'total_transactions_details' => $total_transactions_details,
        'agents_summary' => $agents_summary
    ];
}

// Determine active tab from URL or default
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'summary';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Add Cash Disbursement - GUARDING DASHBOARD</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/dashboard.png">

    <!-- Custom fonts for this template-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css" rel="stylesheet">

    <style>
        .grad-nvb {
            background-image: linear-gradient(180deg, rgba(1, 47, 95, 1) -0.4%, rgba(56, 141, 217, 1) 106.1%);
            color: white;
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

        .form-card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: 1px solid #e3e6f0;
        }

        .nav-tabs .nav-link {
            font-weight: 600;
            color: #6e707e;
            border: 1px solid transparent;
            border-top-left-radius: .35rem;
            border-top-right-radius: .35rem;
        }

        .nav-tabs .nav-link:hover {
            border-color: #e3e6f0 #e3e6f0 #dee2e6;
            color: #4e73df;
        }

        .nav-tabs .nav-link.active {
            color: #4e73df;
            font-weight: 700;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }

        .stat-card {
            border-left: 4px solid #4e73df;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.agent {
            border-left-color: #1cc88a;
        }

        .stat-card.details {
            border-left-color: #f6c23e;
        }

        .tab-content {
            padding: 20px 0;
        }

        .detail-item {
            border-bottom: 1px solid #e3e6f0;
            padding: 10px 0;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .summary-info-card {
            background: linear-gradient(135deg, #f8f9fc 0%, #e3e6f0 100%);
            border-left: 4px solid #4e73df;
        }

        .btn-next-tab {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            font-weight: bold;
            padding: 10px 25px;
            border: none;
            transition: all 0.3s;
        }

        .btn-next-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
            color: white;
        }

        .tab-complete-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-left: 5px;
        }

        .tab-complete-indicator.complete {
            background-color: #1cc88a;
        }

        .tab-complete-indicator.incomplete {
            background-color: #f6c23e;
        }

        .readonly-field {
            background-color: #f8f9fc;
            cursor: not-allowed;
        }

        .addition-badge {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 10px;
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
                        <h1 class="h3 mb-0 text-gray-800">
                            Add Cash Disbursement
                        </h1>
                        <div>
                            <a href="add_cash_disbursement.php?reset" class="d-sm-inline-block btn btn-sm btn-warning shadow-sm" onclick="return confirm('Are you sure you want to start a new entry? Any unsaved data will be lost.')">
                                <i class="fas fa-redo fa-sm text-white-50"></i> Start New
                            </a>
                            <a href="cash_disbursement.php" class="d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
                            </a>
                        </div>
                    </div>

                    <!-- Message Alert -->
                    <?php if (!empty($message)): ?>
                        <div class="alert <?php echo $message_type == 'success' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Tabs Navigation - Always clickable -->
                    <ul class="nav nav-tabs" id="disbursementTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'summary' ? 'active' : ''; ?>" id="summary-tab" data-toggle="tab" href="#summary" role="tab" aria-controls="summary" aria-selected="<?php echo $active_tab == 'summary' ? 'true' : 'false'; ?>">
                                <i class="fas fa-file-alt"></i> Add Summary
                                <?php if ($summary_id > 0): ?>
                                    <span class="tab-complete-indicator complete" title="Summary saved"></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'details' ? 'active' : ''; ?>" id="details-tab" data-toggle="tab" href="#details" role="tab" aria-controls="details" aria-selected="<?php echo $active_tab == 'details' ? 'true' : 'false'; ?>">
                                <i class="fas fa-list"></i> Add Details
                                <span class="badge badge-primary"><?php echo count($details_data); ?></span>
                                <?php if (count($details_data) > 0): ?>
                                    <span class="tab-complete-indicator complete" title="Details added"></span>
                                <?php else: ?>
                                    <span class="tab-complete-indicator incomplete" title="No details yet"></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'statistics' ? 'active' : ''; ?>" id="statistics-tab" data-toggle="tab" href="#statistics" role="tab" aria-controls="statistics" aria-selected="<?php echo $active_tab == 'statistics' ? 'true' : 'false'; ?>">
                                <i class="fas fa-chart-bar"></i> View Statistics
                                <?php if (!empty($details_data)): ?>
                                    <span class="badge badge-success"><?php echo count($details_data); ?></span>
                                    <span class="tab-complete-indicator complete" title="Statistics available"></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="disbursementTabsContent">

                        <!-- Summary Tab -->
                        <div class="tab-pane fade <?php echo $active_tab == 'summary' ? 'show active' : ''; ?>" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                            <div class="card form-card mt-3">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Add New Summary Information</h6>
                                    <?php if ($summary_id > 0): ?>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i> Summary Saved - You can now add details
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="add_cash_disbursement.php?tab=summary" id="summaryForm">
                                        <?php if ($summary_id > 0): ?>
                                            <div class="alert alert-info mb-3">
                                                <i class="fas fa-info-circle"></i> Summary has been saved. To make changes, start a new entry.
                                            </div>
                                        <?php endif; ?>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="transaction_date">Transaction Date *</label>
                                                    <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date"
                                                        value="<?php echo date('Y-m-d\TH:i'); ?>" <?php echo $summary_id > 0 ? 'disabled' : 'required'; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="city_id">City *</label>
                                                    <select class="form-control" id="city_id" name="city_id" <?php echo $summary_id > 0 ? 'disabled' : 'required'; ?>>
                                                        <option value="">Select City</option>
                                                        <?php foreach ($cities as $city): ?>
                                                            <option value="<?php echo $city['city_id']; ?>">
                                                                <?php echo htmlspecialchars($city['city_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="camp_site_id">Camp Site *</label>
                                                    <select class="form-control" id="camp_site_id" name="camp_site_id" <?php echo $summary_id > 0 ? 'disabled' : 'required'; ?>>
                                                        <option value="">Select Camp Site</option>
                                                        <?php foreach ($camp_sites as $camp_site): ?>
                                                            <option value="<?php echo $camp_site['camp_site_id']; ?>">
                                                                <?php echo htmlspecialchars($camp_site['camp_site_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="setup_fee_applied">Setup Fee Applied *</label>
                                                    <select class="form-control" id="setup_fee_applied" name="setup_fee_applied" <?php echo $summary_id > 0 ? 'disabled' : 'required'; ?>>
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
                                                    <select class="form-control" id="setup_fee_type" name="setup_fee_type" <?php echo $summary_id > 0 ? 'disabled' : ''; ?>>
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
                                                    <input type="number" class="form-control" id="total_transactions" name="total_transactions"
                                                        value="<?php echo $summary_id > 0 && isset($summary_data['total_transactions']) ? $summary_data['total_transactions'] : '0'; ?>" min="0" <?php echo $summary_id > 0 ? 'readonly' : 'required'; ?>>
                                                    <?php if ($summary_id > 0): ?>
                                                        <small class="form-text text-muted">This field will be auto-updated when you add details</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="customer_id">Customer *</label>
                                                    <select class="form-control" id="customer_id" name="customer_id" <?php echo $summary_id > 0 ? 'disabled' : 'required'; ?>>
                                                        <option value="">Select Customer</option>
                                                        <?php foreach ($customers as $customer): ?>
                                                            <option value="<?php echo $customer['customer_id']; ?>">
                                                                <?php echo htmlspecialchars($customer['customer_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="authority_id">Revenue Authority *</label>
                                                    <select class="form-control" id="authority_id" name="authority_id" <?php echo $summary_id > 0 ? 'disabled' : 'required'; ?>>
                                                        <option value="">Select Authority</option>
                                                        <?php foreach ($authorities as $authority): ?>
                                                            <option value="<?php echo $authority['id']; ?>">
                                                                <?php echo htmlspecialchars($authority['authority_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="total_amount">Total Amount (PKR) *</label>
                                                    <input type="number" class="form-control" id="total_amount" name="total_amount"
                                                        value="<?php echo $summary_id > 0 && isset($summary_data['total_amount']) ? $summary_data['total_amount'] : '0'; ?>" min="0" step="0.01" <?php echo $summary_id > 0 ? 'readonly' : 'required'; ?>>
                                                    <?php if ($summary_id > 0): ?>
                                                        <small class="form-text text-muted">This field will be auto-updated when you add details</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <?php if ($summary_id == 0): ?>
                                                    <button type="submit" name="add_summary" class="btn btn-next-tab">
                                                        <i class="fas fa-plus"></i> Add New Summary & Go to Details
                                                        <i class="fas fa-arrow-right ml-2"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-next-tab" onclick="window.location.href='add_cash_disbursement.php?tab=details'">
                                                        <i class="fas fa-arrow-right"></i> Go to Details to Add Transactions
                                                        <i class="fas fa-arrow-right ml-2"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <a href="cash_disbursement.php" class="btn btn-secondary btn-lg">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            </div>
                                        </div>
                                    </form>

                                    <?php if ($summary_id > 0 && $summary_data): ?>
                                        <hr>
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-check-circle"></i> Summary Information Saved Successfully!</h6>
                                            <p><strong>Summary ID:</strong> #<?php echo $summary_id; ?></p>
                                            <p><strong>Transaction Date:</strong> <?php echo date('Y-m-d H:i', strtotime($summary_data['transaction_date'])); ?></p>
                                            <p><strong>Current Totals:</strong> <?php echo $summary_data['total_transactions']; ?> transactions, PKR <?php echo number_format($summary_data['total_amount'], 2); ?></p>
                                            <p class="mb-0">You can now proceed to add transaction details. Click the "Add Details" tab above or click "Go to Details" button.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Details Tab -->
                        <div class="tab-pane fade <?php echo $active_tab == 'details' ? 'show active' : ''; ?>" id="details" role="tabpanel" aria-labelledby="details-tab">
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="card form-card">
                                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                            <h6 class="m-0 font-weight-bold text-primary">Add New Transaction Detail</h6>
                                            <button type="button" class="btn btn-sm btn-info" onclick="window.location.href='add_cash_disbursement.php?tab=statistics'">
                                                <i class="fas fa-chart-bar"></i> View Stats
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="add_cash_disbursement.php?tab=details" id="detailForm">
                                                <div class="form-group">
                                                    <label for="device_id">Device *</label>
                                                    <select class="form-control" id="device_id" name="device_id" required>
                                                        <option value="">Select Device</option>
                                                        <?php foreach ($devices as $device): ?>
                                                            <option value="<?php echo $device['device_id']; ?>"><?php echo htmlspecialchars($device['device_name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="agent_id">Agent *</label>
                                                    <select class="form-control" id="agent_id" name="agent_id" required>
                                                        <option value="">Select Agent</option>
                                                        <?php foreach ($agents as $agent): ?>
                                                            <option value="<?php echo $agent['agent_id']; ?>"><?php echo htmlspecialchars($agent['agent_name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="person_name">Person Name</label>
                                                    <input type="text" class="form-control" id="person_name" name="person_name" placeholder="Enter person name">
                                                </div>

                                                <div class="form-group">
                                                    <label for="cit_shipment_ref">CIT/Shipment Reference</label>
                                                    <input type="text" class="form-control" id="cit_shipment_ref" name="cit_shipment_ref" placeholder="Enter reference">
                                                </div>

                                                <div class="form-group">
                                                    <label for="num_transactions">Number of Transactions *</label>
                                                    <input type="number" class="form-control" id="num_transactions" name="num_transactions" min="1" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="total_trans_amount">Total Amount (PKR) *</label>
                                                    <input type="number" class="form-control" id="total_trans_amount" name="total_trans_amount" min="0" step="0.01" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="detail_transaction_date">Transaction Date *</label>
                                                    <input type="datetime-local" class="form-control" id="detail_transaction_date" name="detail_transaction_date" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                                </div>

                                                <button type="submit" name="add_detail" class="btn btn-success btn-block">
                                                    <i class="fas fa-plus"></i> Add New Detail
                                                </button>
                                                <button type="button" class="btn btn-next-tab btn-block mt-2" onclick="goToStatistics()">
                                                    <i class="fas fa-chart-bar"></i> View Statistics
                                                    <i class="fas fa-arrow-right ml-2"></i>
                                                </button>
                                                <a href="add_cash_disbursement.php?tab=summary" class="btn btn-secondary btn-block mt-2">
                                                    <i class="fas fa-arrow-left"></i> Back to Summary
                                                </a>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="card form-card">
                                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                            <h6 class="m-0 font-weight-bold text-primary">Added Transaction Details (<?php echo count($details_data); ?>)</h6>
                                            <div>
                                                <span class="text-primary mr-2">
                                                    <i class="fas fa-exchange-alt"></i> Total: <?php echo array_sum(array_column($details_data, 'num_transactions')); ?> transactions
                                                </span>
                                                <span class="text-success">
                                                    <i class="fas fa-money-bill-wave"></i> PKR <?php echo number_format(array_sum(array_column($details_data, 'total_trans_amount')), 2); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($details_data)): ?>
                                                <div class="text-center py-4">
                                                    <i class="fas fa-list fa-3x text-gray-300 mb-3"></i>
                                                    <p class="text-muted">No transaction details added yet. Add your first detail above.</p>
                                                    <p class="text-info">
                                                        <i class="fas fa-lightbulb"></i> Tip: Add details and then go to Statistics tab to see analysis.
                                                    </p>
                                                </div>
                                            <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered" id="detailsTable" width="100%" cellspacing="0">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Person Name</th>
                                                                <th>Agent</th>
                                                                <th>Device</th>
                                                                <th>Reference</th>
                                                                <th>Transactions</th>
                                                                <th>Amount (PKR)</th>
                                                                <th>Date</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $agent_map = array_column($agents, 'agent_name', 'agent_id');
                                                            $device_map = array_column($devices, 'device_name', 'device_id');

                                                            foreach ($details_data as $index => $detail):
                                                                $agent_name = $agent_map[$detail['agent_id']] ?? 'N/A';
                                                                $device_name = $device_map[$detail['device_id']] ?? 'N/A';
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo $index + 1; ?></td>
                                                                    <td><?php echo htmlspecialchars($detail['person_name']); ?></td>
                                                                    <td><?php echo htmlspecialchars($agent_name); ?></td>
                                                                    <td><?php echo htmlspecialchars($device_name); ?></td>
                                                                    <td><?php echo htmlspecialchars($detail['cit_shipment_ref']); ?></td>
                                                                    <td><?php echo $detail['num_transactions']; ?></td>
                                                                    <td><?php echo number_format($detail['total_trans_amount'], 2); ?></td>
                                                                    <td><?php echo date('Y-m-d H:i', strtotime($detail['transaction_date'])); ?></td>
                                                                    <td>
                                                                        <a href="add_cash_disbursement.php?delete_detail=<?php echo $detail['detail_id']; ?>&tab=details"
                                                                            class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this detail? This cannot be undone.')">
                                                                            <i class="fas fa-trash"></i>
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="mt-3 text-center">
                                                    <button type="button" class="btn btn-next-tab" onclick="goToStatistics()">
                                                        <i class="fas fa-chart-bar"></i> Go to Statistics
                                                        <i class="fas fa-arrow-right ml-2"></i>
                                                    </button>
                                                    <a href="add_cash_disbursement.php?reset" class="btn btn-success ml-2" onclick="return confirm('Are you sure you want to complete this entry and start a new one?')">
                                                        <i class="fas fa-check"></i> Complete & Start New
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Tab -->
                        <div class="tab-pane fade <?php echo $active_tab == 'statistics' ? 'show active' : ''; ?>" id="statistics" role="tabpanel" aria-labelledby="statistics-tab">
                            <?php if (!empty($statistics) && $summary_data): ?>
                                <div class="row mt-3">
                                    <!-- Overall Statistics -->
                                    <div class="col-md-4 mb-4">
                                        <div class="card border-left-primary shadow h-100 py-2 stat-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                            Total Details Added</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $statistics['total_details']; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-list fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-4">
                                        <div class="card border-left-success shadow h-100 py-2 stat-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                            Total Detail Amount</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800">PKR <?php echo number_format($statistics['total_amount_details'], 2); ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-4">
                                        <div class="card border-left-warning shadow h-100 py-2 stat-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                            Total Detail Transactions</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $statistics['total_transactions_details']; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Agent-wise Statistics -->
                                    <div class="col-12">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Agent-wise Summary</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                                        <thead>
                                                            <tr>
                                                                <th>Agent Name</th>
                                                                <th>Number of Details</th>
                                                                <th>Total Transactions</th>
                                                                <th>Total Amount (PKR)</th>
                                                                <th>Average per Detail</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $agent_map = array_column($agents, 'agent_name', 'agent_id');
                                                            foreach ($statistics['agents_summary'] as $agent_id => $agent_data):
                                                                $agent_name = $agent_map[$agent_id] ?? "Agent #" . $agent_id;
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($agent_name); ?></td>
                                                                    <td><?php echo $agent_data['count']; ?></td>
                                                                    <td><?php echo $agent_data['transactions']; ?></td>
                                                                    <td><?php echo number_format($agent_data['amount'], 2); ?></td>
                                                                    <td>
                                                                        <?php echo $agent_data['count'] > 0 ? number_format($agent_data['amount'] / $agent_data['count'], 2) : '0.00'; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Summary vs Details Comparison -->
                                    <div class="col-12">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Summary vs Details Comparison</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="alert alert-info">
                                                            <h6><i class="fas fa-file-alt"></i> Summary Totals:</h6>
                                                            <p><strong>Transactions:</strong> <?php echo $summary_data['total_transactions']; ?></p>
                                                            <p><strong>Amount:</strong> PKR <?php echo number_format($summary_data['total_amount'], 2); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="alert alert-secondary">
                                                            <h6><i class="fas fa-list"></i> Details Totals:</h6>
                                                            <p><strong>Transactions:</strong> <?php echo $statistics['total_transactions_details']; ?></p>
                                                            <p><strong>Amount:</strong> PKR <?php echo number_format($statistics['total_amount_details'], 2); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                                $trans_diff = $summary_data['total_transactions'] - $statistics['total_transactions_details'];
                                                $amount_diff = $summary_data['total_amount'] - $statistics['total_amount_details'];
                                                ?>
                                                <div class="alert <?php echo ($trans_diff == 0 && $amount_diff == 0) ? 'alert-success' : 'alert-warning'; ?>">
                                                    <h6><i class="fas fa-balance-scale"></i> Difference Analysis:</h6>
                                                    <p><strong>Transactions Difference:</strong> <?php echo $trans_diff; ?></p>
                                                    <p><strong>Amount Difference:</strong> PKR <?php echo number_format($amount_diff, 2); ?></p>
                                                    <?php if ($trans_diff == 0 && $amount_diff == 0): ?>
                                                        <p class="mb-0"><i class="fas fa-check-circle"></i> Perfect match between summary and details!</p>
                                                    <?php else: ?>
                                                        <p class="mb-0"><i class="fas fa-exclamation-triangle"></i> There are differences between summary and details. Add more details or start a new entry.</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-center">
                                                    <a href="add_cash_disbursement.php?tab=details" class="btn btn-primary">
                                                        <i class="fas fa-arrow-left"></i> Back to Add Details
                                                    </a>
                                                    <a href="add_cash_disbursement.php?reset" class="btn btn-success ml-2" onclick="return confirm('Are you sure you want to complete this entry and start a new one?')">
                                                        <i class="fas fa-check"></i> Complete & Start New Entry
                                                    </a>
                                                    <a href="cash_disbursement.php" class="btn btn-secondary ml-2">
                                                        <i class="fas fa-list"></i> View All Entries
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> No statistics available. Add a summary and some transaction details first.
                                    <div class="mt-2">
                                        <a href="add_cash_disbursement.php?tab=summary" class="btn btn-sm btn-primary">
                                            <i class="fas fa-file-alt"></i> Go to Add Summary
                                        </a>
                                        <a href="add_cash_disbursement.php?tab=details" class="btn btn-sm btn-info">
                                            <i class="fas fa-list"></i> Go to Add Details
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
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

    <!-- DataTables JavaScript -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);

            // Initialize DataTable for details
            if ($('#detailsTable').length) {
                $('#detailsTable').DataTable({
                    "pageLength": 10,
                    "ordering": true,
                    "searching": true,
                    "info": true,
                    "responsive": true
                });
            }

            // Set current datetime as default for transaction date
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            if (!$('#transaction_date').val()) {
                $('#transaction_date').val(now.toISOString().slice(0, 16));
            }
            if (!$('#detail_transaction_date').val()) {
                $('#detail_transaction_date').val(now.toISOString().slice(0, 16));
            }

            // Handle form submissions to show loading state
            $('#summaryForm').on('submit', function() {
                if (!$('#summaryForm input:disabled, #summaryForm select:disabled').length || $('button[name="add_summary"]').length) {
                    $('button[name="add_summary"]').html('<i class="fas fa-spinner fa-spin"></i> Adding...');
                    $('button[name="add_summary"]').prop('disabled', true);
                }
            });

            $('#detailForm').on('submit', function() {
                $('button[name="add_detail"]').html('<i class="fas fa-spinner fa-spin"></i> Adding...');
                $('button[name="add_detail"]').prop('disabled', true);
            });

            // Disable summary form fields if summary already saved
            // <?php if ($summary_id > 0): ?>
            //     $('#summaryForm input, #summaryForm select').not('button').prop('disabled', true);
            //     $('#summaryForm input[readonly]').addClass('readonly-field');
            // <?php endif; ?>
        });

        // Function to navigate to statistics tab
        function goToStatistics() {
            window.location.href = 'add_cash_disbursement.php?tab=statistics';
        }
    </script>

</body>

</html>
<?php $conn->close(); ?>