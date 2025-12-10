<?php
session_start();
include 'assets/include/dbconnect.php';

// Initialize messages from session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

// Clear session messages after displaying
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Delete cash disbursement summary
if (isset($_GET['delete_id'])) {
    $summary_id = $_GET['delete_id'];

    // 1. Delete detail rows first
    $detail_stmt = $conn->prepare("DELETE FROM cash_disbursement_details WHERE summary_id = ?");
    $detail_stmt->bind_param("i", $summary_id);
    $detail_stmt->execute();
    $detail_stmt->close();

    // 2. Delete summary record
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

    header("Location: cash_disbursement.php");
    exit();
}


// Fetch all cash disbursement summaries for the table with related data
$summaries_result = $conn->query("
    SELECT 
        cds.*, 
        c.city_name, 
        cs.camp_site_name, 
        cust.customer_name,
        ra.authority_name
    FROM cash_disbursement_summary cds 
    LEFT JOIN cities c ON cds.city_id = c.city_id 
    LEFT JOIN camp_sites cs ON cds.camp_site_id = cs.camp_site_id 
    LEFT JOIN customers cust ON cds.customer_id = cust.customer_id
    LEFT JOIN revenue_authority ra ON cds.authority_id = ra.id
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
                        <a href="add_cash_disbursement.php" class="d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Summary
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
                                            <th>Customer</th>
                                            <th>Revenue Authority</th>
                                            <th>Created Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($summary = $summaries_result->fetch_assoc()): ?>
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
                                                <td><?php echo htmlspecialchars($summary['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($summary['authority_name']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($summary['created_date'])); ?></td>
                                                <td class="action-buttons">
                                                    <a href="cash_disbursement_edit.php?id=<?php echo $summary['summary_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
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