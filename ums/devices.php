<?php
session_start();
include 'assets/include/dbconnect.php';

// Handle CRUD operations
$message = '';
$message_type = '';

// Add new device
if (isset($_POST['add_device'])) {
    $device_name = $_POST['device_name'];
    $status = isset($_POST['status']) ? 1 : 0;
    $created_by = $_SESSION['username'] ?? 'Admin';
    
    $stmt = $conn->prepare("INSERT INTO devices (device_name, status, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $device_name, $status, $created_by);
    
    if ($stmt->execute()) {
        $message = "Device added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding device: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Update device
if (isset($_POST['update_device'])) {
    $device_id = $_POST['device_id'];
    $device_name = $_POST['device_name'];
    $status = isset($_POST['status']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE devices SET device_name = ?, status = ? WHERE device_id = ?");
    $stmt->bind_param("sii", $device_name, $status, $device_id);
    
    if ($stmt->execute()) {
        $message = "Device updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating device: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Delete device
if (isset($_GET['delete_id'])) {
    $device_id = $_GET['delete_id'];
    
    $stmt = $conn->prepare("DELETE FROM devices WHERE device_id = ?");
    $stmt->bind_param("i", $device_id);
    
    if ($stmt->execute()) {
        $message = "Device deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting device: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
    
    // Redirect to avoid resubmission
    header("Location: devices.php");
    exit();
}

// Fetch all devices for the table
$devices_result = $conn->query("SELECT * FROM devices ORDER BY device_id DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Devices Management - GUARDING DASHBOARD</title>
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
        
        .device-icon {
            font-size: 1.2em;
            margin-right: 8px;
        }
        
        .device-card {
            transition: transform 0.2s;
        }
        
        .device-card:hover {
            transform: translateY(-2px);
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
                        <h1 class="h3 mb-0 text-gray-800">Devices Management</h1>
                        <button class="d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addDeviceModal">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Device
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
                        <!-- Total Devices Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Devices</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $total_devices = $conn->query("SELECT COUNT(*) as total FROM devices")->fetch_assoc()['total'];
                                                echo $total_devices;
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-microchip fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Devices Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Active Devices</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $active_devices = $conn->query("SELECT COUNT(*) as active FROM devices WHERE status = 1")->fetch_assoc()['active'];
                                                echo $active_devices;
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

                        <!-- Inactive Devices Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Inactive Devices</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $inactive_devices = $conn->query("SELECT COUNT(*) as inactive FROM devices WHERE status = 0")->fetch_assoc()['inactive'];
                                                echo $inactive_devices;
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Device Usage Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Active Rate</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $active_rate = $total_devices > 0 ? round(($active_devices / $total_devices) * 100, 1) : 0;
                                                echo $active_rate . '%';
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
                    </div>

                    <!-- DataTable Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Devices</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="devicesTable" class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Device Name</th>
                                            <th>Status</th>
                                            <th class="d-none">Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($device = $devices_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $device['device_id']; ?></td>
                                            <td>
                                                <i class="fas fa-microchip device-icon text-primary"></i>
                                                <?php echo htmlspecialchars($device['device_name']); ?>
                                            </td>
                                            <td>
                                                <span class="<?php echo $device['status'] ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $device['status'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="d-none"><?php echo htmlspecialchars($device['created_by']); ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-sm btn-primary edit-device" 
                                                        data-id="<?php echo $device['device_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($device['device_name']); ?>"
                                                        data-status="<?php echo $device['status']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-device" 
                                                        data-id="<?php echo $device['device_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($device['device_name']); ?>">
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

    <!-- Add Device Modal -->
    <div class="modal fade" id="addDeviceModal" tabindex="-1" role="dialog" aria-labelledby="addDeviceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDeviceModalLabel">Add New Device</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="devices.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="device_name">Device Name *</label>
                            <input type="text" class="form-control" id="device_name" name="device_name" placeholder="Enter device name" required>
                            <small class="form-text text-muted">e.g., CCTV Camera, Access Control, Alarm System, etc.</small>
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
                        <button type="submit" name="add_device" class="btn btn-primary">Add Device</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Device Modal -->
    <div class="modal fade" id="editDeviceModal" tabindex="-1" role="dialog" aria-labelledby="editDeviceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDeviceModalLabel">Edit Device</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="devices.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_device_id" name="device_id">
                        <div class="form-group">
                            <label for="edit_device_name">Device Name *</label>
                            <input type="text" class="form-control" id="edit_device_name" name="device_name" required>
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
                        <button type="submit" name="update_device" class="btn btn-primary">Update Device</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteDeviceModal" tabindex="-1" role="dialog" aria-labelledby="deleteDeviceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteDeviceModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete device: <strong id="delete_device_name"></strong>?</p>
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
            $('#devicesTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "order": [[0, "desc"]],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                       '<"row"<"col-sm-12"tr>>' +
                       '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "language": {
                    "emptyTable": "No devices found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ devices",
                    "infoEmpty": "Showing 0 to 0 of 0 devices",
                    "infoFiltered": "(filtered from _MAX_ total devices)",
                    "lengthMenu": "Show _MENU_ devices",
                    "search": "Search:",
                    "zeroRecords": "No matching devices found",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
            
            // Edit device button click
            $('.edit-device').click(function() {
                var deviceId = $(this).data('id');
                var deviceName = $(this).data('name');
                var status = $(this).data('status');
                
                $('#edit_device_id').val(deviceId);
                $('#edit_device_name').val(deviceName);
                $('#edit_status').prop('checked', status == 1);
                
                $('#editDeviceModal').modal('show');
            });
            
            // Delete device button click
            $('.delete-device').click(function() {
                var deviceId = $(this).data('id');
                var deviceName = $(this).data('name');
                
                $('#delete_device_name').text(deviceName);
                $('#confirm_delete').attr('href', 'devices.php?delete_id=' + deviceId);
                
                $('#deleteDeviceModal').modal('show');
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