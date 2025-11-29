<?php
session_start();
include 'assets/include/dbconnect.php';

// Handle CRUD operations
$message = '';
$message_type = '';

// Add new city
if (isset($_POST['add_city'])) {
    $city_name = $_POST['city_name'];
    $status = isset($_POST['status']) ? 1 : 0;
    $created_by = $_SESSION['username'] ?? 'Admin';
    
    $stmt = $conn->prepare("INSERT INTO cities (city_name, status, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $city_name, $status, $created_by);
    
    if ($stmt->execute()) {
        $message = "City added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding city: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Update city
if (isset($_POST['update_city'])) {
    $city_id = $_POST['city_id'];
    $city_name = $_POST['city_name'];
    $status = isset($_POST['status']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE cities SET city_name = ?, status = ? WHERE city_id = ?");
    $stmt->bind_param("sii", $city_name, $status, $city_id);
    
    if ($stmt->execute()) {
        $message = "City updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating city: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Delete city
if (isset($_GET['delete_id'])) {
    $city_id = $_GET['delete_id'];
    
    $stmt = $conn->prepare("DELETE FROM cities WHERE city_id = ?");
    $stmt->bind_param("i", $city_id);
    
    if ($stmt->execute()) {
        $message = "City deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting city: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
    
    // Redirect to avoid resubmission
    header("Location: cities_management.php");
    exit();
}

// Fetch all cities for the table
$cities_result = $conn->query("SELECT * FROM cities ORDER BY city_id DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Cities Management</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Cities Management</h1>
                        <button class="d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addCityModal">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New City
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
                            <h6 class="m-0 font-weight-bold text-primary">All Cities</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="citiesTable" class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>City Name</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($city = $cities_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $city['city_id']; ?></td>
                                            <td><?php echo htmlspecialchars($city['city_name']); ?></td>
                                            <td>
                                                <span class="<?php echo $city['status'] ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $city['status'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($city['created_by']); ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-sm btn-primary edit-city" 
                                                        data-id="<?php echo $city['city_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($city['city_name']); ?>"
                                                        data-status="<?php echo $city['status']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-city" 
                                                        data-id="<?php echo $city['city_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($city['city_name']); ?>">
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

    <!-- Add City Modal -->
    <div class="modal fade" id="addCityModal" tabindex="-1" role="dialog" aria-labelledby="addCityModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCityModalLabel">Add New City</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="cities_management.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="city_name">City Name</label>
                            <input type="text" class="form-control" id="city_name" name="city_name" required>
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
                        <button type="submit" name="add_city" class="btn btn-primary">Add City</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit City Modal -->
    <div class="modal fade" id="editCityModal" tabindex="-1" role="dialog" aria-labelledby="editCityModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCityModalLabel">Edit City</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="cities_management.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_city_id" name="city_id">
                        <div class="form-group">
                            <label for="edit_city_name">City Name</label>
                            <input type="text" class="form-control" id="edit_city_name" name="city_name" required>
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
                        <button type="submit" name="update_city" class="btn btn-primary">Update City</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCityModal" tabindex="-1" role="dialog" aria-labelledby="deleteCityModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCityModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete city: <strong id="delete_city_name"></strong>?</p>
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
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap4.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#citiesTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "order": [[0, "desc"]],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                       '<"row"<"col-sm-12"tr>>' +
                       '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "language": {
                    "emptyTable": "No cities found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ cities",
                    "infoEmpty": "Showing 0 to 0 of 0 cities",
                    "infoFiltered": "(filtered from _MAX_ total cities)",
                    "lengthMenu": "Show _MENU_ cities",
                    "search": "Search:",
                    "zeroRecords": "No matching cities found",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
            
            // Edit city button click
            $('.edit-city').click(function() {
                var cityId = $(this).data('id');
                var cityName = $(this).data('name');
                var status = $(this).data('status');
                
                $('#edit_city_id').val(cityId);
                $('#edit_city_name').val(cityName);
                $('#edit_status').prop('checked', status == 1);
                
                $('#editCityModal').modal('show');
            });
            
            // Delete city button click
            $('.delete-city').click(function() {
                var cityId = $(this).data('id');
                var cityName = $(this).data('name');
                
                $('#delete_city_name').text(cityName);
                $('#confirm_delete').attr('href', 'cities_management.php?delete_id=' + cityId);
                
                $('#deleteCityModal').modal('show');
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