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
        header("Location: cash_disbursement.php");
        exit();
    } else {
        $message = "Error adding cash disbursement summary: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Fetch all cities and camp sites for dropdowns
$cities_result = $conn->query("SELECT * FROM cities WHERE status = 1 ORDER BY city_name");
$camp_sites_result = $conn->query("SELECT * FROM camp_sites WHERE status = 1 ORDER BY camp_site_name");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Add Cash Disbursement Summary - GUARDING DASHBOARD</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/dashboard.png">

    <!-- Custom fonts for this template-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.css" rel="stylesheet">
    
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
                        <h1 class="h3 mb-0 text-gray-800">Add Cash Disbursement Summary</h1>
                        <a href="cash_disbursement.php" class="d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
                        </a>
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

                    <!-- Form Card -->
                    <div class="card form-card">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Summary Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="cash_disbursement_add.php">
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
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <button type="submit" name="add_summary" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save"></i> Add Summary
                                        </button>
                                        <a href="cash_disbursement.php" class="btn btn-secondary btn-lg">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </form>
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

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
            
            // Set current datetime as default
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('transaction_date').value = now.toISOString().slice(0,16);
        });
    </script>

</body>

</html>
<?php $conn->close(); ?>