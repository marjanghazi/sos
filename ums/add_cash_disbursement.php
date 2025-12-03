<?php
session_start();
include 'assets/include/dbconnect.php';

// Initialize messages from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

// Clear session messages after displaying
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Initialize summary_id
$summary_id = isset($_SESSION['current_summary_id']) ? $_SESSION['current_summary_id'] : 0;
$summary_data = null;
$details_data = [];

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
    $customer_id = $_POST['customer_id'];
    $authority_id = $_POST['authority_id'];

    // Insert new summary
    $stmt = $conn->prepare("INSERT INTO cash_disbursement_summary (transaction_date, city_id, camp_site_id, setup_fee_applied, setup_fee_type, total_transactions, total_amount, customer_id, authority_id, created_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("siissiiiss", $transaction_date, $city_id, $camp_site_id, $setup_fee_applied, $setup_fee_type, $total_transactions, $total_amount, $customer_id, $authority_id, $created_by);
    
    if ($stmt->execute()) {
        $summary_id = $stmt->insert_id;
        $_SESSION['message'] = "Cash disbursement summary added successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['current_summary_id'] = $summary_id;
        header("Location: cash_disbursement_add.php");
        exit();
    } else {
        $message = "Error: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// If we have a summary_id, fetch the data
if ($summary_id > 0) {
    // Fetch summary data
    $stmt = $conn->prepare("SELECT * FROM cash_disbursement_summary WHERE summary_id = ?");
    $stmt->bind_param("i", $summary_id);
    $stmt->execute();
    $summary_result = $stmt->get_result();
    $summary_data = $summary_result->fetch_assoc();
    $stmt->close();
    
    // Fetch related details if summary exists
    if ($summary_data) {
        $stmt = $conn->prepare("SELECT * FROM cash_disbursement_details WHERE summary_id = ? ORDER BY transaction_date DESC");
        $stmt->bind_param("i", $summary_id);
        $stmt->execute();
        $details_result = $stmt->get_result();
        while ($row = $details_result->fetch_assoc()) {
            $details_data[] = $row;
        }
        $stmt->close();
    }
}

// Add new detail entry
if (isset($_POST['add_detail']) && $summary_id > 0) {
    $device_id = $_POST['device_id'];
    $agent_id = $_POST['agent_id'];
    $person_name = $_POST['person_name'];
    $cit_shipment_ref = $_POST['cit_shipment_ref'];
    $num_transactions = $_POST['num_transactions'];
    $total_trans_amount = $_POST['total_trans_amount'];
    $detail_transaction_date = $_POST['detail_transaction_date'];
    $created_by = $_SESSION['username'] ?? 'Admin';

    $stmt = $conn->prepare("INSERT INTO cash_disbursement_details (summary_id, device_id, agent_id, person_name, cit_shipment_ref, num_transactions, total_trans_amount, transaction_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissiiss", $summary_id, $device_id, $agent_id, $person_name, $cit_shipment_ref, $num_transactions, $total_trans_amount, $detail_transaction_date, $created_by);
    
    if ($stmt->execute()) {
        // Update summary totals
        $update_stmt = $conn->prepare("UPDATE cash_disbursement_summary SET 
            total_transactions = total_transactions + ?,
            total_amount = total_amount + ?
            WHERE summary_id = ?");
        $update_stmt->bind_param("iii", $num_transactions, $total_trans_amount, $summary_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        $_SESSION['message'] = "Transaction detail added successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: cash_disbursement_add.php");
        exit();
    } else {
        $message = "Error adding transaction detail: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Delete detail entry
if (isset($_GET['delete_detail']) && $summary_id > 0) {
    $detail_id = intval($_GET['delete_detail']);
    
    // Get detail amount before deleting
    $stmt = $conn->prepare("SELECT num_transactions, total_trans_amount FROM cash_disbursement_details WHERE detail_id = ?");
    $stmt->bind_param("i", $detail_id);
    $stmt->execute();
    $stmt->bind_result($num_trans, $amount);
    $stmt->fetch();
    $stmt->close();
    
    // Delete the detail
    $stmt = $conn->prepare("DELETE FROM cash_disbursement_details WHERE detail_id = ?");
    $stmt->bind_param("i", $detail_id);
    
    if ($stmt->execute()) {
        // Update summary totals
        $update_stmt = $conn->prepare("UPDATE cash_disbursement_summary SET 
            total_transactions = total_transactions - ?,
            total_amount = total_amount - ?
            WHERE summary_id = ?");
        $update_stmt->bind_param("iii", $num_trans, $amount, $summary_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        $_SESSION['message'] = "Transaction detail deleted successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: cash_disbursement_add.php");
        exit();
    }
    $stmt->close();
}

// Reset form to add new summary
if (isset($_GET['reset'])) {
    unset($_SESSION['current_summary_id']);
    $summary_id = 0;
    $summary_data = null;
    $details_data = [];
    header("Location: cash_disbursement_add.php");
    exit();
}

// Fetch all cities and camp sites for dropdowns
$cities_result = $conn->query("SELECT * FROM cities WHERE status = 1 ORDER BY city_name");
$customers_result = $conn->query("SELECT * FROM customers WHERE status = 1 ORDER BY customer_name");
$camp_sites_result = $conn->query("SELECT * FROM camp_sites WHERE status = 1 ORDER BY camp_site_name");
$authorities_result = $conn->query("SELECT * FROM revenue_authority WHERE is_active = 1 ORDER BY authority_name");
$devices_result = $conn->query("SELECT * FROM devices WHERE status = 1 ORDER BY device_name");
$agents_result = $conn->query("SELECT * FROM agents WHERE status = 1 ORDER BY agent_name");

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
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : ($summary_id > 0 ? 'details' : 'summary');
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
                        <h1 class="h3 mb-0 text-gray-800">Add Cash Disbursement</h1>
                        <div>
                            <a href="cash_disbursement_add.php?reset" class="d-sm-inline-block btn btn-sm btn-warning shadow-sm">
                                <i class="fas fa-redo fa-sm text-white-50"></i> Start New
                            </a>
                            <a href="cash_disbursement.php" class="d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
                            </a>
                        </div>
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

                    <!-- Tabs Navigation - Always clickable -->
                    <ul class="nav nav-tabs" id="disbursementTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'summary' ? 'active' : ''; ?>" id="summary-tab" data-toggle="tab" href="#summary" role="tab" aria-controls="summary" aria-selected="<?php echo $active_tab == 'summary' ? 'true' : 'false'; ?>">
                                <i class="fas fa-file-alt"></i> Summary
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'details' ? 'active' : ''; ?>" id="details-tab" data-toggle="tab" href="#details" role="tab" aria-controls="details" aria-selected="<?php echo $active_tab == 'details' ? 'true' : 'false'; ?>">
                                <i class="fas fa-list"></i> Transaction Details
                                <?php if ($summary_id > 0): ?>
                                    <span class="badge badge-primary"><?php echo count($details_data); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'statistics' ? 'active' : ''; ?>" id="statistics-tab" data-toggle="tab" href="#statistics" role="tab" aria-controls="statistics" aria-selected="<?php echo $active_tab == 'statistics' ? 'true' : 'false'; ?>">
                                <i class="fas fa-chart-bar"></i> Statistics
                                <?php if (!empty($details_data)): ?>
                                    <span class="badge badge-success"><?php echo count($details_data); ?></span>
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
                                    <h6 class="m-0 font-weight-bold text-primary">Add Summary Information</h6>
                                    <?php if ($summary_id > 0): ?>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i> Summary Saved
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="cash_disbursement_add.php?tab=summary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="transaction_date">Transaction Date *</label>
                                                    <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" 
                                                           value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="city_id">City *</label>
                                                    <select class="form-control" id="city_id" name="city_id" required>
                                                        <option value="">Select City</option>
                                                        <?php 
                                                        $cities_result->data_seek(0);
                                                        while ($city = $cities_result->fetch_assoc()): 
                                                        ?>
                                                            <option value="<?php echo $city['city_id']; ?>">
                                                                <?php echo htmlspecialchars($city['city_name']); ?>
                                                            </option>
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
                                                        <?php 
                                                        $camp_sites_result->data_seek(0);
                                                        while ($camp_site = $camp_sites_result->fetch_assoc()): 
                                                        ?>
                                                            <option value="<?php echo $camp_site['camp_site_id']; ?>">
                                                                <?php echo htmlspecialchars($camp_site['camp_site_name']); ?>
                                                            </option>
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
                                                    <input type="number" class="form-control" id="total_transactions" name="total_transactions" 
                                                           value="0" min="0" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="customer_id">Customer *</label>
                                                    <select class="form-control" id="customer_id" name="customer_id" required>
                                                        <option value="">Select Customer</option>
                                                        <?php 
                                                        $customers_result->data_seek(0);
                                                        while ($customer = $customers_result->fetch_assoc()): 
                                                        ?>
                                                            <option value="<?php echo $customer['customer_id']; ?>">
                                                                <?php echo htmlspecialchars($customer['customer_name']); ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="authority_id">Revenue Authority *</label>
                                                    <select class="form-control" id="authority_id" name="authority_id" required>
                                                        <option value="">Select Authority</option>
                                                        <?php 
                                                        $authorities_result->data_seek(0);
                                                        while ($authority = $authorities_result->fetch_assoc()): 
                                                        ?>
                                                            <option value="<?php echo $authority['id']; ?>">
                                                                <?php echo htmlspecialchars($authority['authority_name']); ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="total_amount">Total Amount (PKR) *</label>
                                                    <input type="number" class="form-control" id="total_amount" name="total_amount" 
                                                           value="0" min="0" step="0.01" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="submit" name="add_summary" class="btn btn-primary btn-lg">
                                                    <i class="fas fa-save"></i> <?php echo $summary_id > 0 ? 'Update Summary' : 'Add Summary'; ?>
                                                </button>
                                                <?php if ($summary_id > 0): ?>
                                                    <a href="cash_disbursement_add.php?tab=details" class="btn btn-success btn-lg">
                                                        <i class="fas fa-arrow-right"></i> Go to Details
                                                    </a>
                                                <?php endif; ?>
                                                <a href="cash_disbursement.php" class="btn btn-secondary btn-lg">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Details Tab -->
                        <div class="tab-pane fade <?php echo $active_tab == 'details' ? 'show active' : ''; ?>" id="details" role="tabpanel" aria-labelledby="details-tab">
                            <?php if ($summary_id > 0): ?>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="card form-card">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Add Transaction Detail</h6>
                                            </div>
                                            <div class="card-body">
                                                <form method="POST" action="cash_disbursement_add.php?tab=details">
                                                    <div class="form-group">
                                                        <label for="device_id">Device *</label>
                                                        <select class="form-control" id="device_id" name="device_id" required>
                                                            <option value="">Select Device</option>
                                                            <?php 
                                                            $devices_result->data_seek(0);
                                                            while ($device = $devices_result->fetch_assoc()): 
                                                            ?>
                                                                <option value="<?php echo $device['device_id']; ?>"><?php echo htmlspecialchars($device['device_name']); ?></option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="agent_id">Agent *</label>
                                                        <select class="form-control" id="agent_id" name="agent_id" required>
                                                            <option value="">Select Agent</option>
                                                            <?php 
                                                            $agents_result->data_seek(0);
                                                            while ($agent = $agents_result->fetch_assoc()): 
                                                            ?>
                                                                <option value="<?php echo $agent['agent_id']; ?>"><?php echo htmlspecialchars($agent['agent_name']); ?></option>
                                                            <?php endwhile; ?>
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
                                                        <i class="fas fa-plus"></i> Add Detail
                                                    </button>
                                                    <a href="cash_disbursement_add.php?tab=statistics" class="btn btn-info btn-block mt-2">
                                                        <i class="fas fa-chart-bar"></i> View Statistics
                                                    </a>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <div class="card form-card">
                                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Transaction Details (<?php echo count($details_data); ?>)</h6>
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
                                                        <p class="text-muted">No transaction details found. Add your first detail above.</p>
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
                                                                <?php foreach ($details_data as $index => $detail): 
                                                                    // Get agent name
                                                                    $agent_name = "N/A";
                                                                    $agents_result->data_seek(0);
                                                                    while ($agent = $agents_result->fetch_assoc()) {
                                                                        if ($agent['agent_id'] == $detail['agent_id']) {
                                                                            $agent_name = htmlspecialchars($agent['agent_name']);
                                                                            break;
                                                                        }
                                                                    }
                                                                    
                                                                    // Get device name
                                                                    $device_name = "N/A";
                                                                    $devices_result->data_seek(0);
                                                                    while ($device = $devices_result->fetch_assoc()) {
                                                                        if ($device['device_id'] == $detail['device_id']) {
                                                                            $device_name = htmlspecialchars($device['device_name']);
                                                                            break;
                                                                        }
                                                                    }
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $index + 1; ?></td>
                                                                        <td><?php echo htmlspecialchars($detail['person_name']); ?></td>
                                                                        <td><?php echo $agent_name; ?></td>
                                                                        <td><?php echo $device_name; ?></td>
                                                                        <td><?php echo htmlspecialchars($detail['cit_shipment_ref']); ?></td>
                                                                        <td><?php echo $detail['num_transactions']; ?></td>
                                                                        <td><?php echo number_format($detail['total_trans_amount'], 2); ?></td>
                                                                        <td><?php echo date('Y-m-d H:i', strtotime($detail['transaction_date'])); ?></td>
                                                                        <td>
                                                                            <a href="cash_disbursement_add.php?delete_detail=<?php echo $detail['detail_id']; ?>&tab=details" 
                                                                               class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this detail?')">
                                                                                <i class="fas fa-trash"></i>
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> Please save the summary first to add transaction details.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Statistics Tab -->
                        <div class="tab-pane fade <?php echo $active_tab == 'statistics' ? 'show active' : ''; ?>" id="statistics" role="tabpanel" aria-labelledby="statistics-tab">
                            <?php if (!empty($statistics) && $summary_id > 0): ?>
                                <div class="row mt-3">
                                    <!-- Overall Statistics -->
                                    <div class="col-md-4 mb-4">
                                        <div class="card border-left-primary shadow h-100 py-2 stat-card">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                            Total Details</div>
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
                                                            <?php foreach ($statistics['agents_summary'] as $agent_id => $agent_data): 
                                                                // Get agent name
                                                                $agent_name = "Agent #" . $agent_id;
                                                                $agents_result->data_seek(0);
                                                                while ($agent = $agents_result->fetch_assoc()) {
                                                                    if ($agent['agent_id'] == $agent_id) {
                                                                        $agent_name = htmlspecialchars($agent['agent_name']);
                                                                        break;
                                                                    }
                                                                }
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo $agent_name; ?></td>
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
                                                            <h6>Summary Totals:</h6>
                                                            <p>Transactions: <?php echo $summary_data['total_transactions']; ?></p>
                                                            <p>Amount: PKR <?php echo number_format($summary_data['total_amount'], 2); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="alert alert-secondary">
                                                            <h6>Details Totals:</h6>
                                                            <p>Transactions: <?php echo $statistics['total_transactions_details']; ?></p>
                                                            <p>Amount: PKR <?php echo number_format($statistics['total_amount_details'], 2); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php 
                                                $trans_diff = $summary_data['total_transactions'] - $statistics['total_transactions_details'];
                                                $amount_diff = $summary_data['total_amount'] - $statistics['total_amount_details'];
                                                ?>
                                                <div class="alert <?php echo ($trans_diff == 0 && $amount_diff == 0) ? 'alert-success' : 'alert-warning'; ?>">
                                                    <h6>Difference:</h6>
                                                    <p>Transactions Difference: <?php echo $trans_diff; ?></p>
                                                    <p>Amount Difference: PKR <?php echo number_format($amount_diff, 2); ?></p>
                                                    <?php if ($trans_diff == 0 && $amount_diff == 0): ?>
                                                        <p><i class="fas fa-check-circle"></i> Perfect match between summary and details!</p>
                                                    <?php else: ?>
                                                        <p><i class="fas fa-exclamation-triangle"></i> There are differences between summary and details.</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-center">
                                                    <a href="cash_disbursement_add.php?tab=details" class="btn btn-primary">
                                                        <i class="fas fa-arrow-left"></i> Back to Details
                                                    </a>
                                                    <a href="cash_disbursement_add.php?reset" class="btn btn-success ml-2">
                                                        <i class="fas fa-check"></i> Complete & Start New
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> No statistics available. Add a summary and some transaction details first.
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
            $('#detailsTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "searching": true,
                "info": true,
                "responsive": true
            });

            // Set current datetime as default for transaction date
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            if (!$('#transaction_date').val()) {
                $('#transaction_date').val(now.toISOString().slice(0, 16));
            }

            // Tab switching - Bootstrap handles this automatically
            
            // Update summary totals when detail form values change
            $('#num_transactions, #total_trans_amount').on('input', function() {
                updateTotalSummary();
            });

            function updateTotalSummary() {
                var currentTrans = <?php echo $summary_data ? $summary_data['total_transactions'] : 0; ?>;
                var currentAmount = <?php echo $summary_data ? $summary_data['total_amount'] : 0; ?>;
                
                var newTrans = parseInt($('#num_transactions').val()) || 0;
                var newAmount = parseFloat($('#total_trans_amount').val()) || 0;
                
                // Only update if we're in add mode
                if (<?php echo $summary_id > 0 ? 'true' : 'false'; ?>) {
                    $('#total_transactions').val(currentTrans + newTrans);
                    $('#total_amount').val(currentAmount + newAmount);
                }
            }

            // Calculate amount based on number of transactions (optional feature)
            $('#num_transactions').on('input', function() {
                var transactions = $(this).val();
                if (transactions && transactions > 0 && $('#total_trans_amount').val() == '') {
                    // You can set a default rate here if needed
                    // var rate = 100; // PKR 100 per transaction
                    // $('#total_trans_amount').val(transactions * rate);
                }
            });
            
            // Handle tab clicks to update URL
            $('.nav-tabs .nav-link').on('click', function(e) {
                var tabId = $(this).attr('href').substring(1);
                // You can update the URL here if needed
            });
        });
    </script>

</body>

</html>
<?php $conn->close(); ?>