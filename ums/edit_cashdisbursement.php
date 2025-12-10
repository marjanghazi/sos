<?php
session_start();
include 'assets/include/dbconnect.php';

// Initialize messages from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

// Clear session messages after displaying
if (isset($_SESSION['message'])) unset($_SESSION['message']);
if (isset($_SESSION['message_type'])) unset($_SESSION['message_type']);

// Get summary ID from URL
$summary_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($summary_id == 0) {
    header("Location: cash_disbursement.php");
    exit();
}

// Initialize variables
$summary_data = null;
$details_data = [];

// Fetch summary data
$stmt = $conn->prepare("SELECT * FROM cash_disbursement_summary WHERE summary_id = ?");
$stmt->bind_param("i", $summary_id);
if ($stmt->execute()) {
    $summary_result = $stmt->get_result();
    $summary_data = $summary_result->fetch_assoc();
    
    if (!$summary_data) {
        $_SESSION['message'] = "Cash disbursement summary not found!";
        $_SESSION['message_type'] = "error";
        header("Location: cash_disbursement.php");
        exit();
    }
} else {
    $_SESSION['message'] = "Error fetching summary: " . $stmt->error;
    $_SESSION['message_type'] = "error";
    header("Location: cash_disbursement.php");
    exit();
}
$stmt->close();

// Update cash disbursement summary
if (isset($_POST['update_summary'])) {
    // Get inputs
    $transaction_date = $_POST['transaction_date'] ?? '';
    $city_id = $_POST['city_id'] ?? 0;
    $camp_site_id = $_POST['camp_site_id'] ?? 0;
    $setup_fee_applied = $_POST['setup_fee_applied'] ?? '';
    $setup_fee_type = $_POST['setup_fee_type'] ?? '';
    $customer_id = $_POST['customer_id'] ?? 0;
    $authority_id = $_POST['authority_id'] ?? 0;
    $updated_by = $_SESSION['username'] ?? 'Admin';

    // Update summary without updating totals (they're auto-calculated from details)
    $stmt = $conn->prepare("UPDATE cash_disbursement_summary SET 
        transaction_date = ?, 
        city_id = ?, 
        camp_site_id = ?, 
        setup_fee_applied = ?, 
        setup_fee_type = ?, 
        customer_id = ?, 
        authority_id = ?,
        updated_date = NOW(),
        updated_by = ?
        WHERE summary_id = ?");
    
    $stmt->bind_param("siissiiisi", $transaction_date, $city_id, $camp_site_id, $setup_fee_applied, $setup_fee_type, $customer_id, $authority_id, $updated_by, $summary_id);

    if ($stmt->execute()) {
        // Update local summary data
        $summary_data['transaction_date'] = $transaction_date;
        $summary_data['city_id'] = $city_id;
        $summary_data['camp_site_id'] = $camp_site_id;
        $summary_data['setup_fee_applied'] = $setup_fee_applied;
        $summary_data['setup_fee_type'] = $setup_fee_type;
        $summary_data['customer_id'] = $customer_id;
        $summary_data['authority_id'] = $authority_id;
        
        $_SESSION['message'] = "Cash disbursement summary updated successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: cash_disbursement_edit.php?id=" . $summary_id . "&tab=summary");
        exit();
    } else {
        $message = "Error updating summary: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Add new detail entry
if (isset($_POST['add_detail'])) {
    // Get inputs
    $device_id = $_POST['device_id'] ?? 0;
    $agent_id = $_POST['agent_id'] ?? 0;
    $person_name = $_POST['person_name'] ?? '';
    $cit_shipment_ref = $_POST['cit_shipment_ref'] ?? '';
    $num_transactions = $_POST['num_transactions'] ?? 0;
    $total_trans_amount = $_POST['total_trans_amount'] ?? 0;
    $detail_transaction_date = $_POST['detail_transaction_date'] ?? '';
    $created_by = $_SESSION['username'] ?? 'Admin';

    // Insert detail
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

        // Update local summary totals
        $summary_data['total_transactions'] += $num_transactions;
        $summary_data['total_amount'] += $total_trans_amount;
        
        $_SESSION['message'] = "Transaction detail added successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: cash_disbursement_edit.php?id=" . $summary_id . "&tab=details");
        exit();
    } else {
        $message = "Error adding transaction detail: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Update detail entry
if (isset($_POST['update_detail'])) {
    $detail_id = $_POST['detail_id'] ?? 0;
    
    if ($detail_id > 0) {
        // Get old detail values first
        $stmt = $conn->prepare("SELECT num_transactions, total_trans_amount FROM cash_disbursement_details WHERE detail_id = ? AND summary_id = ?");
        $stmt->bind_param("ii", $detail_id, $summary_id);
        $stmt->execute();
        $stmt->bind_result($old_num_trans, $old_amount);
        $stmt->fetch();
        $stmt->close();

        // Get new inputs
        $device_id = $_POST['device_id'] ?? 0;
        $agent_id = $_POST['agent_id'] ?? 0;
        $person_name = $_POST['person_name'] ?? '';
        $cit_shipment_ref = $_POST['cit_shipment_ref'] ?? '';
        $num_transactions = $_POST['num_transactions'] ?? 0;
        $total_trans_amount = $_POST['total_trans_amount'] ?? 0;
        $detail_transaction_date = $_POST['detail_transaction_date'] ?? '';
        $updated_by = $_SESSION['username'] ?? 'Admin';

        // Update the detail
        $stmt = $conn->prepare("UPDATE cash_disbursement_details SET 
            device_id = ?, 
            agent_id = ?, 
            person_name = ?, 
            cit_shipment_ref = ?, 
            num_transactions = ?, 
            total_trans_amount = ?, 
            transaction_date = ?,
            updated_by = ?,
            updated_date = NOW()
            WHERE detail_id = ? AND summary_id = ?");
        
        $stmt->bind_param("iissdissiii", $device_id, $agent_id, $person_name, $cit_shipment_ref, $num_transactions, $total_trans_amount, $detail_transaction_date, $updated_by, $detail_id, $summary_id);

        if ($stmt->execute()) {
            // Update summary totals with difference
            $trans_diff = $num_transactions - $old_num_trans;
            $amount_diff = $total_trans_amount - $old_amount;
            
            $update_stmt = $conn->prepare("UPDATE cash_disbursement_summary SET 
                total_transactions = total_transactions + ?,
                total_amount = total_amount + ?
                WHERE summary_id = ?");
            $update_stmt->bind_param("ddi", $trans_diff, $amount_diff, $summary_id);
            $update_stmt->execute();
            $update_stmt->close();

            // Update local summary totals
            $summary_data['total_transactions'] += $trans_diff;
            $summary_data['total_amount'] += $amount_diff;
            
            $_SESSION['message'] = "Transaction detail updated successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: cash_disbursement_edit.php?id=" . $summary_id . "&tab=details");
            exit();
        } else {
            $message = "Error updating transaction detail: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
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

            // Update local summary totals
            $summary_data['total_transactions'] -= $num_trans;
            $summary_data['total_amount'] -= $amount;
            
            $_SESSION['message'] = "Transaction detail deleted successfully!";
            $_SESSION['message_type'] = "success";
        }
        $stmt->close();
    }
    header("Location: cash_disbursement_edit.php?id=" . $summary_id . "&tab=details");
    exit();
}

// Fetch all data for dropdowns
$cities_result = $conn->query("SELECT * FROM cities WHERE status = 1 ORDER BY city_name") or die($conn->error);
$customers_result = $conn->query("SELECT * FROM customers WHERE status = 1 ORDER BY customer_name") or die($conn->error);
$camp_sites_result = $conn->query("SELECT * FROM camp_sites WHERE status = 1 ORDER BY camp_site_name") or die($conn->error);
$authorities_result = $conn->query("SELECT * FROM revenue_authority WHERE is_active = 1 ORDER BY authority_name") or die($conn->error);
$devices_result = $conn->query("SELECT * FROM devices WHERE status = 1 ORDER BY device_name") or die($conn->error);
$agents_result = $conn->query("SELECT * FROM agents WHERE status = 1 ORDER BY agent_name") or die($conn->error);

// Store results in arrays
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

// Fetch details for this summary
$stmt = $conn->prepare("SELECT * FROM cash_disbursement_details WHERE summary_id = ? ORDER BY transaction_date DESC");
$stmt->bind_param("i", $summary_id);
if ($stmt->execute()) {
    $details_result = $stmt->get_result();
    while ($row = $details_result->fetch_assoc()) {
        $details_data[] = $row;
    }
}
$stmt->close();

// Check if we're editing a specific detail
$editing_detail_id = isset($_GET['edit_detail']) ? (int)$_GET['edit_detail'] : 0;
$editing_detail = null;
if ($editing_detail_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM cash_disbursement_details WHERE detail_id = ? AND summary_id = ?");
    $stmt->bind_param("ii", $editing_detail_id, $summary_id);
    if ($stmt->execute()) {
        $detail_result = $stmt->get_result();
        $editing_detail = $detail_result->fetch_assoc();
    }
    $stmt->close();
}

// Calculate statistics
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

    <title>Edit Cash Disbursement - GUARDING DASHBOARD</title>
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

        .edit-badge {
            background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        .btn-edit-detail {
            background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
            color: white;
        }

        .btn-edit-detail:hover {
            background: linear-gradient(135deg, #e0a800 0%, #b38c00 100%);
            color: white;
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
                            Edit Cash Disbursement
                            <span class="edit-badge">ID: <?php echo $summary_id; ?></span>
                        </h1>
                        <div>
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

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="disbursementTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'summary' ? 'active' : ''; ?>" id="summary-tab" data-toggle="tab" href="#summary" role="tab" aria-controls="summary" aria-selected="<?php echo $active_tab == 'summary' ? 'true' : 'false'; ?>">
                                <i class="fas fa-file-alt"></i> Edit Summary
                                <span class="tab-complete-indicator complete" title="Summary exists"></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab == 'details' ? 'active' : ''; ?>" id="details-tab" data-toggle="tab" href="#details" role="tab" aria-controls="details" aria-selected="<?php echo $active_tab == 'details' ? 'true' : 'false'; ?>">
                                <i class="fas fa-list"></i> Manage Details
                                <span class="badge badge-primary"><?php echo count($details_data); ?></span>
                                <span class="tab-complete-indicator <?php echo count($details_data) > 0 ? 'complete' : 'incomplete'; ?>" 
                                      title="<?php echo count($details_data) > 0 ? 'Details exist' : 'No details yet'; ?>"></span>
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
                                    <h6 class="m-0 font-weight-bold text-primary">Edit Summary Information</h6>
                                    <span class="text-primary">
                                        <i class="fas fa-info-circle"></i> Total: <?php echo $summary_data['total_transactions']; ?> transactions, 
                                        PKR <?php echo number_format($summary_data['total_amount'], 2); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="cash_disbursement_edit.php?id=<?php echo $summary_id; ?>&tab=summary" id="summaryForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="transaction_date">Transaction Date *</label>
                                                    <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date"
                                                        value="<?php echo date('Y-m-d\TH:i', strtotime($summary_data['transaction_date'])); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="city_id">City *</label>
                                                    <select class="form-control" id="city_id" name="city_id" required>
                                                        <option value="">Select City</option>
                                                        <?php foreach ($cities as $city): ?>
                                                            <option value="<?php echo $city['city_id']; ?>" <?php echo ($city['city_id'] == $summary_data['city_id']) ? 'selected' : ''; ?>>
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
                                                    <select class="form-control" id="camp_site_id" name="camp_site_id" required>
                                                        <option value="">Select Camp Site</option>
                                                        <?php foreach ($camp_sites as $camp_site): ?>
                                                            <option value="<?php echo $camp_site['camp_site_id']; ?>" <?php echo ($camp_site['camp_site_id'] == $summary_data['camp_site_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($camp_site['camp_site_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="setup_fee_applied">Setup Fee Applied *</label>
                                                    <select class="form-control" id="setup_fee_applied" name="setup_fee_applied" required>
                                                        <option value="">Select Option</option>
                                                        <option value="Yes" <?php echo ($summary_data['setup_fee_applied'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                                        <option value="No" <?php echo ($summary_data['setup_fee_applied'] == 'No') ? 'selected' : ''; ?>>No</option>
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
                                                        <option value="Fixed" <?php echo ($summary_data['setup_fee_type'] == 'Fixed') ? 'selected' : ''; ?>>Fixed</option>
                                                        <option value="Percentage" <?php echo ($summary_data['setup_fee_type'] == 'Percentage') ? 'selected' : ''; ?>>Percentage</option>
                                                        <option value="Variable" <?php echo ($summary_data['setup_fee_type'] == 'Variable') ? 'selected' : ''; ?>>Variable</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="total_transactions">Total Transactions</label>
                                                    <input type="number" class="form-control" id="total_transactions" name="total_transactions"
                                                        value="<?php echo $summary_data['total_transactions']; ?>" min="0" readonly>
                                                    <small class="form-text text-muted">Auto-calculated from details</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="customer_id">Customer *</label>
                                                    <select class="form-control" id="customer_id" name="customer_id" required>
                                                        <option value="">Select Customer</option>
                                                        <?php foreach ($customers as $customer): ?>
                                                            <option value="<?php echo $customer['customer_id']; ?>" <?php echo ($customer['customer_id'] == $summary_data['customer_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($customer['customer_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="authority_id">Revenue Authority *</label>
                                                    <select class="form-control" id="authority_id" name="authority_id" required>
                                                        <option value="">Select Authority</option>
                                                        <?php foreach ($authorities as $authority): ?>
                                                            <option value="<?php echo $authority['id']; ?>" <?php echo ($authority['id'] == $summary_data['authority_id']) ? 'selected' : ''; ?>>
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
                                                    <label for="total_amount">Total Amount (PKR)</label>
                                                    <input type="number" class="form-control" id="total_amount" name="total_amount"
                                                        value="<?php echo $summary_data['total_amount']; ?>" min="0" step="0.01" readonly>
                                                    <small class="form-text text-muted">Auto-calculated from details</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="submit" name="update_summary" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Update Summary
                                                </button>
                                                <button type="button" class="btn btn-next-tab" onclick="window.location.href='cash_disbursement_edit.php?id=<?php echo $summary_id; ?>&tab=details'">
                                                    <i class="fas fa-arrow-right"></i> Manage Details
                                                    <i class="fas fa-arrow-right ml-2"></i>
                                                </button>
                                                <a href="cash_disbursement.php" class="btn btn-secondary">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            </div>
                                        </div>
                                    </form>

                                    <hr>
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle"></i> Summary Information</h6>
                                        <p><strong>Created:</strong> <?php echo date('Y-m-d H:i', strtotime($summary_data['created_date'])); ?> by <?php echo htmlspecialchars($summary_data['created_by']); ?></p>
                                        <?php if ($summary_data['updated_date']): ?>
                                            <p><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i', strtotime($summary_data['updated_date'])); ?> by <?php echo htmlspecialchars($summary_data['updated_by'] ?? 'N/A'); ?></p>
                                        <?php endif; ?>
                                        <p><strong>Current Totals:</strong> <?php echo $summary_data['total_transactions']; ?> transactions, PKR <?php echo number_format($summary_data['total_amount'], 2); ?></p>
                                        <p class="mb-0">Go to "Manage Details" tab to add, edit, or delete transaction details.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Details Tab -->
                        <div class="tab-pane fade <?php echo $active_tab == 'details' ? 'show active' : ''; ?>" id="details" role="tabpanel" aria-labelledby="details-tab">
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="card form-card">
                                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                            <h6 class="m-0 font-weight-bold <?php echo $editing_detail ? 'text-warning' : 'text-primary'; ?>">
                                                <?php echo $editing_detail ? 'Edit Transaction Detail' : 'Add New Transaction Detail'; ?>
                                            </h6>
                                            <?php if ($editing_detail): ?>
                                                <span class="badge badge-warning">Editing ID: <?php echo $editing_detail_id; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="cash_disbursement_edit.php?id=<?php echo $summary_id; ?>&tab=details" id="detailForm">
                                                <?php if ($editing_detail): ?>
                                                    <input type="hidden" name="detail_id" value="<?php echo $editing_detail_id; ?>">
                                                <?php endif; ?>
                                                
                                                <div class="form-group">
                                                    <label for="device_id">Device *</label>
                                                    <select class="form-control" id="device_id" name="device_id" required>
                                                        <option value="">Select Device</option>
                                                        <?php foreach ($devices as $device): ?>
                                                            <option value="<?php echo $device['device_id']; ?>" 
                                                                <?php echo ($editing_detail && $device['device_id'] == $editing_detail['device_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($device['device_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="agent_id">Agent *</label>
                                                    <select class="form-control" id="agent_id" name="agent_id" required>
                                                        <option value="">Select Agent</option>
                                                        <?php foreach ($agents as $agent): ?>
                                                            <option value="<?php echo $agent['agent_id']; ?>"
                                                                <?php echo ($editing_detail && $agent['agent_id'] == $editing_detail['agent_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($agent['agent_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="person_name">Person Name</label>
                                                    <input type="text" class="form-control" id="person_name" name="person_name" 
                                                        value="<?php echo $editing_detail ? htmlspecialchars($editing_detail['person_name']) : ''; ?>" 
                                                        placeholder="Enter person name">
                                                </div>

                                                <div class="form-group">
                                                    <label for="cit_shipment_ref">CIT/Shipment Reference</label>
                                                    <input type="text" class="form-control" id="cit_shipment_ref" name="cit_shipment_ref" 
                                                        value="<?php echo $editing_detail ? htmlspecialchars($editing_detail['cit_shipment_ref']) : ''; ?>" 
                                                        placeholder="Enter reference">
                                                </div>

                                                <div class="form-group">
                                                    <label for="num_transactions">Number of Transactions *</label>
                                                    <input type="number" class="form-control" id="num_transactions" name="num_transactions" 
                                                        value="<?php echo $editing_detail ? $editing_detail['num_transactions'] : ''; ?>" 
                                                        min="1" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="total_trans_amount">Total Amount (PKR) *</label>
                                                    <input type="number" class="form-control" id="total_trans_amount" name="total_trans_amount" 
                                                        value="<?php echo $editing_detail ? $editing_detail['total_trans_amount'] : ''; ?>" 
                                                        min="0" step="0.01" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="detail_transaction_date">Transaction Date *</label>
                                                    <input type="datetime-local" class="form-control" id="detail_transaction_date" name="detail_transaction_date" 
                                                        value="<?php echo $editing_detail ? date('Y-m-d\TH:i', strtotime($editing_detail['transaction_date'])) : date('Y-m-d\TH:i'); ?>" required>
                                                </div>

                                                <?php if ($editing_detail): ?>
                                                    <button type="submit" name="update_detail" class="btn btn-warning btn-block">
                                                        <i class="fas fa-save"></i> Update Detail
                                                    </button>
                                                    <a href="cash_disbursement_edit.php?id=<?php echo $summary_id; ?>&tab=details" class="btn btn-secondary btn-block mt-2">
                                                        <i class="fas fa-times"></i> Cancel Edit
                                                    </a>
                                                <?php else: ?>
                                                    <button type="submit" name="add_detail" class="btn btn-success btn-block">
                                                        <i class="fas fa-plus"></i> Add New Detail
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="btn btn-next-tab btn-block mt-2" onclick="goToStatistics()">
                                                    <i class="fas fa-chart-bar"></i> View Statistics
                                                    <i class="fas fa-arrow-right ml-2"></i>
                                                </button>
                                                <a href="cash_disbursement_edit.php?id=<?php echo $summary_id; ?>&tab=summary" class="btn btn-secondary btn-block mt-2">
                                                    <i class="fas fa-arrow-left"></i> Back to Summary
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
                                                    <p class="text-muted">No transaction details added yet. Add your first detail above.</p>
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
                                                                $is_editing = ($editing_detail_id == $detail['detail_id']);
                                                            ?>
                                                                <tr class="<?php echo $is_editing ? 'table-warning' : ''; ?>">
                                                                    <td><?php echo $index + 1; ?></td>
                                                                    <td><?php echo htmlspecialchars($detail['person_name']); ?></td>
                                                                    <td><?php echo htmlspecialchars($agent_name); ?></td>
                                                                    <td><?php echo htmlspecialchars($device_name); ?></td>
                                                                    <td><?php echo htmlspecialchars($detail['cit_shipment_ref']); ?></td>
                                                                    <td><?php echo $detail['num_transactions']; ?></td>
                                                                    <td><?php echo number_format($detail['total_trans_amount'], 2); ?></td>
                                                                    <td><?php echo date('Y-m-d H:i', strtotime($detail['transaction_date'])); ?></td>
                                                                    <td>
                                                                        <a href="cash_disbursement_edit.php?id=<?php echo $summary_id; ?>&tab=details&edit_detail=<?php echo $detail['detail_id']; ?>"
                                                                            class="btn btn-sm btn-edit-detail <?php echo $is_editing ? 'disabled' : ''; ?>">
                                                                            <i class="fas fa-edit"></i>
                                                                        </a>
                                                                        <a href="cash_disbursement_edit.php?id=<?php echo $summary_id; ?>&delete_detail=<?php echo $detail['detail_id']; ?>&tab=details"
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
                                                    <a href="cash_disbursement.php" class="btn btn-success ml-2">
                                                        <i class="fas fa-check"></i> Back to List
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
                                                        <p class="mb-0"><i class="fas fa-exclamation-triangle"></i> There are differences between summary and details.</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-center">
                                                    <a href="cash_disbursement_edit.php?id=<?php echo $summary_id; ?>&tab=details" class="btn btn-primary">
                                                        <i class="fas fa-arrow-left"></i> Back to Manage Details
                                                    </a>
                                                    <a href="cash_disbursement_edit.php?id=<?php echo $summary_id; ?>&tab=summary" class="btn btn-info ml-2">
                                                        <i class="fas fa-file-alt"></i> Edit Summary
                                                    </a>
                                                    <a href="cash_disbursement.php" class="btn btn-secondary ml-2">
                                                        <i class="fas fa-list"></i> Back to List
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> No statistics available. Add some transaction details first.
                                    <div class="mt-2">
                                        <a href="cash_disbursement_edit.php?id=<?php echo $summary_id; ?>&tab=details" class="btn btn-sm btn-primary">
                                            <i class="fas fa-list"></i> Go to Manage Details
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

            // Set current datetime as default for transaction date if adding new
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            if (!$('#detail_transaction_date').val() && !<?php echo $editing_detail ? 'true' : 'false'; ?>) {
                $('#detail_transaction_date').val(now.toISOString().slice(0, 16));
            }

            // Handle form submissions to show loading state
            $('#summaryForm').on('submit', function() {
                $('button[name="update_summary"]').html('<i class="fas fa-spinner fa-spin"></i> Updating...');
                $('button[name="update_summary"]').prop('disabled', true);
            });

            $('#detailForm').on('submit', function() {
                if ($('button[name="update_detail"]').length) {
                    $('button[name="update_detail"]').html('<i class="fas fa-spinner fa-spin"></i> Updating...');
                    $('button[name="update_detail"]').prop('disabled', true);
                } else if ($('button[name="add_detail"]').length) {
                    $('button[name="add_detail"]').html('<i class="fas fa-spinner fa-spin"></i> Adding...');
                    $('button[name="add_detail"]').prop('disabled', true);
                }
            });
        });

        // Function to navigate to statistics tab
        function goToStatistics() {
            window.location.href = 'cash_disbursement_edit.php?id=<?php echo $summary_id; ?>&tab=statistics';
        }
    </script>

</body>

</html>
<?php $conn->close(); ?>