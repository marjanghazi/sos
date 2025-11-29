<?php
session_start();
include 'assets/include/dbconnect.php';

// Handle CRUD operations
$message = '';
$message_type = '';

// Add new agent
if (isset($_POST['add_agent'])) {
    $agent_name = $_POST['agent_name'];
    $device_id = $_POST['device_id'];
    $status = isset($_POST['status']) ? 1 : 0;
    $created_by = $_SESSION['username'] ?? 'Admin';
    
    $stmt = $conn->prepare("INSERT INTO agents (agent_name, device_id, status, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $agent_name, $device_id, $status, $created_by);
    
    if ($stmt->execute()) {
        $message = "Agent added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding agent: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Update agent
if (isset($_POST['update_agent'])) {
    $agent_id = $_POST['agent_id'];
    $agent_name = $_POST['agent_name'];
    $device_id = $_POST['device_id'];
    $status = isset($_POST['status']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE agents SET agent_name = ?, device_id = ?, status = ? WHERE agent_id = ?");
    $stmt->bind_param("iiii", $agent_name, $device_id, $status, $agent_id);
    
    if ($stmt->execute()) {
        $message = "Agent updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating agent: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Delete agent
if (isset($_GET['delete_id'])) {
    $agent_id = $_GET['delete_id'];
    
    $stmt = $conn->prepare("DELETE FROM agents WHERE agent_id = ?");
    $stmt->bind_param("i", $agent_id);
    
    if ($stmt->execute()) {
        $message = "Agent deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting agent: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
    
    // Redirect to avoid resubmission
    header("Location: agents.php");
    exit();
}

// Fetch all devices for dropdown
$devices_result = $conn->query("SELECT * FROM devices WHERE status = 1 ORDER BY device_name");

// Fetch all agents for the table with device names
$agents_result = $conn->query("
    SELECT a.*, d.device_name 
    FROM agents a 
    LEFT JOIN devices d ON a.device_id = d.device_id 
    ORDER BY a.agent_id DESC
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

    <title>Agents Management - GUARDING DASHBOARD</title>
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
        
        .agent-icon {
            font-size: 1.2em;
            margin-right: 8px;
            color: #4e73df;
        }
        
        .device-badge {
            font-size: 0.8em;
            padding: 4px 8px;
            border-radius: 12px;
            background-color: #e8f4fd;
            color: #2c5aa0;
            border: 1px solid #2c5aa0;
        }
        
        .stats-card {
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
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
                        <h1 class="h3 mb-0 text-gray-800">Agents Management</h1>
                        <button class="d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addAgentModal">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Agent
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
                        <!-- Total Agents Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Agents</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $total_agents = $conn->query("SELECT COUNT(*) as total FROM agents")->fetch_assoc()['total'];
                                                echo $total_agents;
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Agents Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Active Agents</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $active_agents = $conn->query("SELECT COUNT(*) as active FROM agents WHERE status = 1")->fetch_assoc()['active'];
                                                echo $active_agents;
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

                        <!-- Inactive Agents Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Inactive Agents</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $inactive_agents = $conn->query("SELECT COUNT(*) as inactive FROM agents WHERE status = 0")->fetch_assoc()['inactive'];
                                                echo $inactive_agents;
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

                        <!-- Assigned Devices Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Active Rate</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $active_rate = $total_agents > 0 ? round(($active_agents / $total_agents) * 100, 1) : 0;
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
                            <h6 class="m-0 font-weight-bold text-primary">All Agents</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="agentsTable" class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Agent Name</th>
                                            <th>Assigned Device</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($agent = $agents_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $agent['agent_id']; ?></td>
                                            <td>
                                                <i class="fas fa-user-shield agent-icon"></i>
                                                <?php echo htmlspecialchars($agent['agent_name']); ?>
                                            </td>
                                            <td>
                                                <?php if($agent['device_name']): ?>
                                                    <span class="device-badge">
                                                        <i class="fas fa-microchip"></i>
                                                        <?php echo htmlspecialchars($agent['device_name']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not Assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="<?php echo $agent['status'] ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $agent['status'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($agent['created_by']); ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-sm btn-primary edit-agent" 
                                                        data-id="<?php echo $agent['agent_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($agent['agent_name']); ?>"
                                                        data-device-id="<?php echo $agent['device_id']; ?>"
                                                        data-status="<?php echo $agent['status']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-agent" 
                                                        data-id="<?php echo $agent['agent_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($agent['agent_name']); ?>">
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

    <!-- Add Agent Modal -->
    <div class="modal fade" id="addAgentModal" tabindex="-1" role="dialog" aria-labelledby="addAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAgentModalLabel">Add New Agent</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="agents.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="agent_name">Agent Name *</label>
                            <input type="text" class="form-control" id="agent_name" name="agent_name" placeholder="Enter agent name or ID" required>
                            <small class="form-text text-muted">Enter agent name or unique identifier</small>
                        </div>
                        <div class="form-group">
                            <label for="device_id">Assign Device</label>
                            <select class="form-control" id="device_id" name="device_id">
                                <option value="">No Device Assigned</option>
                                <?php while($device = $devices_result->fetch_assoc()): ?>
                                    <option value="<?php echo $device['device_id']; ?>"><?php echo htmlspecialchars($device['device_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <small class="form-text text-muted">Optional: Assign a device to this agent</small>
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
                        <button type="submit" name="add_agent" class="btn btn-primary">Add Agent</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Agent Modal -->
    <div class="modal fade" id="editAgentModal" tabindex="-1" role="dialog" aria-labelledby="editAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAgentModalLabel">Edit Agent</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="agents.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_agent_id" name="agent_id">
                        <div class="form-group">
                            <label for="edit_agent_name">Agent Name *</label>
                            <input type="text" class="form-control" id="edit_agent_name" name="agent_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_device_id">Assign Device</label>
                            <select class="form-control" id="edit_device_id" name="device_id">
                                <option value="">No Device Assigned</option>
                                <?php 
                                // Reset devices result pointer
                                $devices_result->data_seek(0);
                                while($device = $devices_result->fetch_assoc()): ?>
                                    <option value="<?php echo $device['device_id']; ?>"><?php echo htmlspecialchars($device['device_name']); ?></option>
                                <?php endwhile; ?>
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
                        <button type="submit" name="update_agent" class="btn btn-primary">Update Agent</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteAgentModal" tabindex="-1" role="dialog" aria-labelledby="deleteAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAgentModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete agent: <strong id="delete_agent_name"></strong>?</p>
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
            $('#agentsTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "order": [[0, "desc"]],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                       '<"row"<"col-sm-12"tr>>' +
                       '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "language": {
                    "emptyTable": "No agents found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ agents",
                    "infoEmpty": "Showing 0 to 0 of 0 agents",
                    "infoFiltered": "(filtered from _MAX_ total agents)",
                    "lengthMenu": "Show _MENU_ agents",
                    "search": "Search:",
                    "zeroRecords": "No matching agents found",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
            
            // Edit agent button click
            $('.edit-agent').click(function() {
                var agentId = $(this).data('id');
                var agentName = $(this).data('name');
                var deviceId = $(this).data('device-id');
                var status = $(this).data('status');
                
                $('#edit_agent_id').val(agentId);
                $('#edit_agent_name').val(agentName);
                $('#edit_device_id').val(deviceId);
                $('#edit_status').prop('checked', status == 1);
                
                $('#editAgentModal').modal('show');
            });
            
            // Delete agent button click
            $('.delete-agent').click(function() {
                var agentId = $(this).data('id');
                var agentName = $(this).data('name');
                
                $('#delete_agent_name').text(agentName);
                $('#confirm_delete').attr('href', 'agents.php?delete_id=' + agentId);
                
                $('#deleteAgentModal').modal('show');
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