<?php
session_start();
$sidebar_id = '0807';
if (!isset($_SESSION['admin-login'])) {
    echo "access Denied. <a href='index.php' >Go Back</a>";
    die();
}
include 'assets/include/dbconnect.php';
$prcheck = mysqli_query($conn,"SELECT * from user_to_permission where user_id='".$_SESSION['admin-login']."' and permission_id='1' and sidebar_id='$sidebar_id'") or die(mysql_error($conn));
if (!mysqli_num_rows($prcheck) && $_SESSION['type']!='1') {
    $_SESSION['error'] = 'You do Not have rights to View this Report!';
    die($_SESSION['error']);
    unset($_SESSION['error']);
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
    <?php include 'assets/include/select2.php'; ?>

    <title>Selective Stock Report - ZBS</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/js/standalone/selectize.min.js" integrity="sha256-+C0A5Ilqmu4QcSPxrlGpaZxJ04VjsRjKu+G82kl5UJk=" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/css/selectize.bootstrap3.min.css" integrity="sha256-ze/OEYGcFbPRmvCnrSeKbRTtjG4vGLHXgOqsyLFTRjg=" crossorigin="anonymous" />

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.css" rel="stylesheet">

      <!-- Custom styles for this page -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
<script>
    function wrall(arg) {
        var checkall = document.getElementById('wr-all');
        if (checkall.checked === true) {
            var allInputs = document.getElementsByClassName("wr-ck");
            for (var i = 0, max = allInputs.length; i < max; i++){
                allInputs[i].checked = true;
            }
        }
        else{
            var allInputs = document.getElementsByClassName("wr-ck");
            for (var i = 0, max = allInputs.length; i < max; i++){
                allInputs[i].checked = false;
            }
        }
}
    function ctall(arg) {
        var checkall = document.getElementById('ct-all');
        if (checkall.checked === true) {
            var allInputs = document.getElementsByClassName("ct-ck");
            for (var i = 0, max = allInputs.length; i < max; i++){
                allInputs[i].checked = true;
            }
            var allInputs = document.getElementsByClassName("pr-ck");
            for (var i = 0, max = allInputs.length; i < max; i++){
                allInputs[i].checked = true;
            }
            document.getElementById("prdiv").style.display= 'block';
            var allInputs = document.getElementsByClassName("sp");
            for (var i = 0, max = allInputs.length; i < max; i++){
                allInputs[i].style.display = "block";
            }
        }
        else{
            var allInputs = document.getElementsByClassName("ct-ck");
            for (var i = 0, max = allInputs.length; i < max; i++){
                allInputs[i].checked = false;
            }
            var allInputs = document.getElementsByClassName("pr-ck");
            for (var i = 0, max = allInputs.length; i < max; i++){
                allInputs[i].checked = false;
            }
        }
}

 function ctone(arg) {
        var checkall = document.getElementById("chk-"+arg);
        if (checkall.checked === true) {
            var allInputs = document.getElementsByClassName("ct-"+arg);
            for (var i = 0, max = allInputs.length; i < max; i++){
                allInputs[i].checked = true;
            }
            document.getElementById("prdiv-"+arg).style.display= 'block';
        }
        else{
            var allInputs = document.getElementsByClassName("ct-"+arg);
            for (var i = 0, max = allInputs.length; i < max; i++){
                allInputs[i].checked = false;
            }
            document.getElementById("prdiv-"+arg).style.display= 'None';
        }
}
function ctuncheck(arg) {
        var checkall = document.getElementById("ctuncheck-"+arg);
        if (checkall.checked === true) {
            var allInputs = document.getElementsByClassName("ct-"+arg);
            for (var i = 0, max = allInputs.length; i < max; i++){
                allInputs[i].checked = true;
            }
        }
        else{
            var allInputs = document.getElementsByClassName("ct-"+arg);
            for (var i = 0, max = allInputs.length; i < max; i++){
                allInputs[i].checked = false;
            }
        }
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
                            <!-- DataTables -->
                        <div class="card shadow mb-4" >
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Barcode Print Report</h6>
                        </div>
                        <div class="card-body">
                        <form action="barcode-print-view.php" method="get" target="_blank">
                        <div class="form-group">
                            <input type="submit" name="ledger-search" class="btn btn-success">
                            <a href="selective-stock-report.php" class="btn btn-primary" >Reset</a>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-sm-12">
                                    <input type="checkbox" id="ct-all" onchange='ctall()' value="1"><br>
                                   <label><strong>Product</strong></label><br>
                                   <span id="prdiv">
                                    <?php
                                    $catquery = mysqli_query($conn,"SELECT * from category where active='0'");
                                    while ($catrow = mysqli_fetch_array($catquery)) {
                                        echo "<span id='prdiv-".$catrow['id_pk']."' class='sp'>";
                                        echo "<strong>".strtoupper($catrow['category_name'])."</strong>&nbsp<input type='checkbox' id='ctuncheck-".$catrow['id_pk']."' onchange='ctuncheck(".$catrow['id_pk'].")'><br>";
                                    $query = mysqli_query($conn,"SELECT * from product where active='0' and category_id='".$catrow['id_pk']."' order by product_name") or die(mysqli_error($conn));
                                    while ($row = mysqli_fetch_array($query)) {
                                        echo "<div class='row'>";
                                        echo "<div class='col-sm-5'>";
                                        echo "<input type='checkbox' class='pr-ck ct-".$catrow['id_pk']."'  name='pr-".$row['product_id']."' value='1'>".substr($row['coa'],5,4)." - ".$row['product_name'];
                                        echo "</div>";
                                        $sub_query = mysqli_query($conn,"SELECT sum(dr)-sum(cr) from stockd where product_id='".$row['product_id']."'");
                                            $row2 = mysqli_fetch_array($sub_query);
                                            if ($row2[0] == NULL) {
                                                $stock = 0;
                                            }
                                            else{
                                                $stock = $row2[0];
                                            }
                                            echo "<div class='col-sm-2'> <input type='number' class='form-control' name='qt-".$row['product_id']."' value='".round($stock)."'></div>";
                                            echo "</div>";
                                    }
                                    echo "</span>";
                                }
                                    ?>
                                    </span>
                                </div>
                            </div>
                        
                    </div>
                </form>
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
            <?php include 'assets/include/footer.php'; ?>
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