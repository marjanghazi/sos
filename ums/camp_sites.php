<?php
session_start();
include 'assets/include/dbconnect.php';

// Handle CRUD operations
$message = '';
$message_type = '';

// Add new camp site
if (isset($_POST['add_camp_site'])) {
    $city_id = $_POST['city_id'];
    $camp_site_name = $_POST['camp_site_name'];
    $setup_type = $_POST['setup_type'];
    $status = isset($_POST['status']) ? 1 : 0;
    $created_by = $_SESSION['username'] ?? 'Admin';

    $stmt = $conn->prepare("INSERT INTO camp_sites (city_id, camp_site_name, setup_type, status, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $city_id, $camp_site_name, $setup_type, $status, $created_by);

    if ($stmt->execute()) {
        $message = "Camp site added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding camp site: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
    // Redirect to avoid resubmission
    header("Location: camp_sites.php");
    exit();
}

// Update camp site
if (isset($_POST['update_camp_site'])) {
    $camp_site_id = $_POST['camp_site_id'];
    $city_id = $_POST['city_id'];
    $camp_site_name = $_POST['camp_site_name'];
    $setup_type = $_POST['setup_type'];
    $status = isset($_POST['status']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE camp_sites SET city_id = ?, camp_site_name = ?, setup_type = ?, status = ? WHERE camp_site_id = ?");
    $stmt->bind_param("issii", $city_id, $camp_site_name, $setup_type, $status, $camp_site_id);

    if ($stmt->execute()) {
        $message = "Camp site updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating camp site: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Delete camp site
if (isset($_GET['delete_id'])) {
    $camp_site_id = $_GET['delete_id'];

    $stmt = $conn->prepare("DELETE FROM camp_sites WHERE camp_site_id = ?");
    $stmt->bind_param("i", $camp_site_id);

    if ($stmt->execute()) {
        $message = "Camp site deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting camp site: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();

    // Redirect to avoid resubmission
    header("Location: camp_sites.php");
    exit();
}

// Fetch all cities for dropdown
$cities_result = $conn->query("SELECT * FROM cities WHERE status = 1 ORDER BY city_name");

// Fetch all camp sites for the table with city names
$camp_sites_result = $conn->query("
    SELECT cs.*, c.city_name 
    FROM camp_sites cs 
    LEFT JOIN cities c ON cs.city_id = c.city_id 
    ORDER BY cs.camp_site_id DESC
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

    <title>Camp Sites Management</title>
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

        .setup-type-badge {
            font-size: 0.8em;
            padding: 4px 8px;
            border-radius: 12px;
        }

        .setup-type-full {
            background-color: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }

        .setup-type-light {
            background-color: #fff3e0;
            color: #ef6c00;
            border: 1px solid #ef6c00;
        }

        .setup-type-mobile {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #1565c0;
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
                        <h1 class="h3 mb-0 text-gray-800">Camp Sites Management</h1>
                        <button class="d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addCampSiteModal">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Camp Site
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

                    <!-- DataTable Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Camp Sites</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="campSitesTable" class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Camp Site Name</th>
                                            <th>City</th>
                                            <th>Setup Type</th>
                                            <th>Status</th>
                                            <th class="d-none">Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($camp_site = $camp_sites_result->fetch_assoc()):
                                            $setup_type_class = '';
                                            switch (strtolower($camp_site['setup_type'])) {
                                                case '1':
                                                    $setup_type_class = 'setup-type-full';
                                                    break;
                                                case '2':
                                                    $setup_type_class = 'setup-type-light';
                                                    break;
                                                default:
                                                    $setup_type_class = 'setup-type-full';
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo $camp_site['camp_site_id']; ?></td>
                                                <td><?php echo htmlspecialchars($camp_site['camp_site_name']); ?></td>
                                                <td><?php echo htmlspecialchars($camp_site['city_name']); ?></td>
                                                <td>
                                                    <span class="setup-type-badge <?php echo $setup_type_class; ?>">
                                                        <?php echo htmlspecialchars($camp_site['setup_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="<?php echo $camp_site['status'] ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo $camp_site['status'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td class="d-none"><?php echo htmlspecialchars($camp_site['created_by']); ?></td>
                                                <td class="action-buttons">
                                                    <button class="btn btn-sm btn-primary edit-camp-site"
                                                        data-id="<?php echo $camp_site['camp_site_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($camp_site['camp_site_name']); ?>"
                                                        data-city-id="<?php echo $camp_site['city_id']; ?>"
                                                        data-setup-type="<?php echo htmlspecialchars($camp_site['setup_type']); ?>"
                                                        data-status="<?php echo $camp_site['status']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-camp-site"
                                                        data-id="<?php echo $camp_site['camp_site_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($camp_site['camp_site_name']); ?>">
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

    <!-- Add Camp Site Modal -->
    <div class="modal fade" id="addCampSiteModal" tabindex="-1" role="dialog" aria-labelledby="addCampSiteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCampSiteModalLabel">Add New Camp Site</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="camp_sites.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="camp_site_name">Camp Site Name *</label>
                            <input type="text" class="form-control" id="camp_site_name" name="camp_site_name" required>
                        </div>
                        <div class="form-group">
                            <label for="city_id">City *</label>
                            <select class="form-control" id="city_id" name="city_id" required>
                                <option value="">Select City</option>
                                <?php while ($city = $cities_result->fetch_assoc()): ?>
                                    <option value="<?php echo $city['city_id']; ?>"><?php echo htmlspecialchars($city['city_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="setup_type">Setup Type *</label>
                            <select class="form-control" id="setup_type" name="setup_type" required>
                                <option value="">Select Setup Type</option>
                                <option value="1">Full</option>
                                <option value="2">Light</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                <label class="form-check-label" for="status">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_camp_site" class="btn btn-primary">Add Camp Site</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Camp Site Modal -->
    <div class="modal fade" id="editCampSiteModal" tabindex="-1" role="dialog" aria-labelledby="editCampSiteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCampSiteModalLabel">Edit Camp Site</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="camp_sites.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_camp_site_id" name="camp_site_id">
                        <div class="form-group">
                            <label for="edit_camp_site_name">Camp Site Name *</label>
                            <input type="text" class="form-control" id="edit_camp_site_name" name="camp_site_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_city_id">City *</label>
                            <select class="form-control" id="edit_city_id" name="city_id" required>
                                <option value="">Select City</option>
                                <?php
                                // Reset cities result pointer
                                $cities_result->data_seek(0);
                                while ($city = $cities_result->fetch_assoc()): ?>
                                    <option value="<?php echo $city['city_id']; ?>"><?php echo htmlspecialchars($city['city_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_setup_type">Setup Type *</label>
                            <select class="form-control" id="edit_setup_type" name="setup_type" required>
                                <option value="">Select Setup Type</option>
                                <option value="1">full</option>
                                <option value="2">light</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_status" name="status">
                                <label class="form-check-label" for="edit_status">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_camp_site" class="btn btn-primary">Update Camp Site</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCampSiteModal" tabindex="-1" role="dialog" aria-labelledby="deleteCampSiteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCampSiteModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete camp site: <strong id="delete_camp_site_name"></strong>?</p>
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
            $('#campSitesTable').DataTable({
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
                    "emptyTable": "No camp sites found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ camp sites",
                    "infoEmpty": "Showing 0 to 0 of 0 camp sites",
                    "infoFiltered": "(filtered from _MAX_ total camp sites)",
                    "lengthMenu": "Show _MENU_ camp sites",
                    "search": "Search:",
                    "zeroRecords": "No matching camp sites found",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });

            // Edit camp site button click
            $('.edit-camp-site').click(function() {
                var campSiteId = $(this).data('id');
                var campSiteName = $(this).data('name');
                var cityId = $(this).data('city-id');
                var setupType = $(this).data('setup-type');
                var status = $(this).data('status');

                $('#edit_camp_site_id').val(campSiteId);
                $('#edit_camp_site_name').val(campSiteName);
                $('#edit_city_id').val(cityId);
                $('#edit_setup_type').val(setupType);
                $('#edit_status').prop('checked', status == 1);

                $('#editCampSiteModal').modal('show');
            });

            // Delete camp site button click
            $('.delete-camp-site').click(function() {
                var campSiteId = $(this).data('id');
                var campSiteName = $(this).data('name');

                $('#delete_camp_site_name').text(campSiteName);
                $('#confirm_delete').attr('href', 'camp_sites.php?delete_id=' + campSiteId);

                $('#deleteCampSiteModal').modal('show');
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