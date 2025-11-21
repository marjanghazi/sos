<?php
session_start();
if (!isset($_SESSION['admin-login'])) {
    echo "access Denied. <a href='../index.php' >Go Back</a>";
    die();
}
if (isset($_POST['company-submit'])) {
    $name = ucwords($_POST['company']);
    $contact = $_POST['contact'];
    include 'assets/include/dbconnect.php';
    $query = mysqli_query($conn,"INSERT into livestock_bank(bank_name,bank_type) values('$name','$contact')") or die(mysqli_error($conn));
    header("location:bank.php");
    die();
}
if (isset($_POST['company-edit'])) {
    $id = $_POST['id'];
    $name = ucwords($_POST['company']);
    $contact = $_POST['contact'];
    include 'assets/include/dbconnect.php';
    $query = mysqli_query($conn,"UPDATE livestock_bank set bank_name='$name',bank_type = '$contact' where id_pk='$id'") or die(mysqli_error($conn));
    header("location:bank.php");
    die();
}
if (isset($_GET['del-id'])) {
    $id = $_GET['del-id'];
    include 'assets/include/dbconnect.php';
    $query = mysqli_query($conn,"SELECT count(id_pk) as 'tp' from livestock_sale where payment_account='$id'") or die(mysqli_error($conn));
        $row = mysqli_fetch_array($query);
        if ($row['tp']>0) {
            $_SESSION['error']='This bank has Sale entries! Cannot be deleted';
        }
        else{
    $query = mysqli_query($conn,"DELETE FROM livestock_bank where id_pk='$id'") or die(mysqli_error($conn));
    header("location:bank.php");
    die();
}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Payment Accounts - LiveStock</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/dashboard.png">
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.css" rel="stylesheet">

      <!-- Custom styles for this page -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style type="text/css">
        html{
            scroll-behavior: smooth;
        }
    </style>
<script type="text/javascript">
    function editcategory(arg,arg2,arg3) {
        document.getElementById('add-f').style.display = "none";
        document.getElementById('add-d').style.display = "none";
        document.getElementById('edit-f').style.display = "block";
        document.getElementById('edit-d').style.display = "block";
        document.getElementById('edit-input').value = arg;
        document.getElementById('edit-input2').value = arg2;
        document.getElementById('edit-input3').value = arg3;
        window.scrollTo(0,0);
    }
</script>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar included here-->
        <?php
        include 'assets/include/sidebar.php';
        ?>
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

                    <div class="container">

        <div class="card o-hidden border-0 shadow-lg my-5">
            <div class="card-body p-0">
                <!-- Nested Row within Card Body -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="p-5">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4" id="add-d">Register Account</h1>
                            </div>
                            <form class="user" method="post" action="" id="add-f">
                                <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input type="text" class="form-control" name="company"
                                            placeholder="Account Name">
                                    </div>
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <select class="form-control" name="contact">
                                            <option value="0" selected>Bank Acc.</option>
                                            <option value="1">Cash Acc.</option>
                                        </select>
                                    </div>
                                     <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input type="submit" class="btn btn-primary btn-user " name="company-submit"
                                            value='Register'>
                                    </div>
                                    </div>
                            </form>
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4" style="display: none;" id='edit-d'>Edit An Account</h1>
                            </div>
                            <form class="user" method="post" action="" style="display: none;" id="edit-f">
                                <div class="form-group row">
                                    <input type="hidden" name="id" id="edit-input">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input type="text" class="form-control" name="company"
                                            placeholder="Customer Name" id="edit-input2">
                                    </div>
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <select class="form-control" name="contact" id="edit-input3">
                                            <option value="0">Bank Acc.</option>
                                            <option value="1">Cash Acc.</option>
                                        </select>
                                    </div>
                                     <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input type="submit" class="btn btn-primary btn-user " name="company-edit"
                                            value='Edit'>
                                    </div>
                                    </div>
                            </form>
                        </div>
                            <hr>
                            <!-- DataTables -->
                        <div class="card shadow mb-4" >
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">List of Accounts</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Account Name</th>
                                            <th>Account Type</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Account Name</th>
                                            <th>Account Type</th>
                                            <th>Actions</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <?php
                                        include 'assets/include/dbconnect.php';
                                        $query = mysqli_query($conn,"SELECT * from livestock_bank");
                                        while ($row = mysqli_fetch_array($query)) {
                                            echo "<tr>";
                                            echo "<td>".$row['bank_name']."</td>";
                                            if ($row['bank_type'] == 0) {
                                                echo "<td>Bank Acc.</td>";
                                            }
                                            else{
                                                echo "<td>Cash Acc.</td>";
                                            }
                                            ?>
                                           <td><a  href='bank.php?del-id=<?php echo $row['id_pk'];?>' onclick="return confirm()" ><i class='fas fa-trash'></i></a> | <a onclick="editcategory(<?php echo $row['id_pk']?>,'<?php echo $row['bank_name'];?>','<?php echo $row['bank_type'];?>');" ><i class="fas fa-edit"></i></a></td>
                                            <?php
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                        </div>
                    </div>
                </div>
            </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2020</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <?php
    include 'assets/include/logoutmodal.php';
    ?>
    
    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/datatables-demo.js"></script>

</body>

</html>