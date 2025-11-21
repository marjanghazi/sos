<?php
session_start();
$sidebar_id = '0201';
if (!isset($_SESSION['admin-login'])) {
    echo "access Denied. <a href='../index.php' >Go Back</a>";
    die();
}
if (!isset($_SESSION['period'])) {
    $_SESSION['period'] = date("M-Y");
}
if (isset($_GET['period'])) {
    $_SESSION['period'] = $_GET["period"];
    header("location:vaccination-listing.php");
    die();
}
if (isset($_GET["del-id"])) {
    $id = $_GET['del-id'];
    include 'assets/include/dbconnect.php';
    $qry = mysqli_query($conn,"SELECT status from livestock_animal,livestock_animal_vaccination where livestock_animal.id_pk = livestock_animal_vaccination.animal_id and vaccination_id='$id'");
    $row = mysqli_fetch_array($qry);
    if ($row['status'] == 0) {
        mysqli_query($conn,"DELETE from livestock_animal_vaccination where vaccination_id='$id'");
        $_SESSION['error'] = 'Vaccination Record Deleted!';   
    }
    header("location:vaccination-list.php");
    die();
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

    <title>Vaccination Listing - ZBS</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/dashboard.png">

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.css" rel="stylesheet">

      <!-- Custom styles for this page -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
<script type="text/javascript">
    function viewatt(arg) {
            xml = new XMLHttpRequest();
            var url = "ajax.php?viewatt="+arg;
            xml.open("GET",url,"true");
            xml.send();
            xml.onreadystatechange = function(){
                if (xml.readystate = 4) {
                    document.getElementById("result_table").innerHTML = xml.responseText;
            }
        }
        $('#viewatt').modal('show');
    }
    function perchnage(argument) {
        window.open(window.location.href+"?period="+argument, "_self");        
    }
</script>
<style>
    th,td {
            white-space: nowrap;
        }
</style>
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

        <div class="card o-hidden border-0 shadow-lg my-5">
            <div class="card-body p-0">
                <!-- Nested Row within Card Body -->
                <div class="row">
                    <div class="col-lg-12">
                            <!-- DataTables -->
                        <div class="card shadow mb-4" >
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">VACCINATION LISTING</h6>
                            <?php
                            include 'assets/include/dbconnect.php';
                            ?>
                            <div style="width: 15%;float: right;">
                                <select class="form-control" class="js-example-basic-single" onchange="perchnage(this.value);">
                                    <?php 
                                    $n = 0;
                                    while($n<15){
                                        echo "<option ";
                                        if ($_SESSION['period'] == date("M-Y",strtotime(date("1-M-Y")."-$n Months"))) {
                                            echo "SELECTED";
                                        }
                                        echo " >".date("M-Y",strtotime(date("1-M-Y")."-$n Months"))."</option>";
                                        $n++;
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php
                                if (isset($_SESSION['success'])) {
                                    ?>
                                <div class="alert alert-success in alert-dismissible" style="margin-top:18px;">
                                    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
                                    <strong>Success!</strong><?=$_SESSION['success'];?>
                                </div>
                                    <?php
                                    unset($_SESSION['success']);
                                }
                                if (isset($_SESSION['error'])) {
                                    
                                    ?>
                                <div class="alert alert-danger in alert-dismissible" style="margin-top:18px;">
                                    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
                                     <?=$_SESSION['error'];?>
                                </div>
                                    <?php
                                    unset($_SESSION['error']);
                                }
                                ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Vaccination ID</th>
                                            <th>Animal ID</th>
                                            <th>Type</th>
                                            <th>Breed</th>
                                            <th>Date</th>
                                            <th>Cost</th>
                                            <th>Vaccination</th>
                                            <th>Weight</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Vaccination ID</th>
                                            <th>Animal ID</th>
                                            <th>Type</th>
                                            <th>Breed</th>
                                            <th>Date</th>
                                            <th>Cost</th>
                                            <th>Vaccination</th>
                                            <th>Weight</th>
                                            <th>Actions</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <?php
                                        include 'assets/include/dbconnect.php';
                                        $query = mysqli_query($conn,"SELECT * from livestock_animal_vaccination where DATE_FORMAT(vaccination_date, '%b-%Y') = '".$_SESSION['period']."' order by vaccination_date desc,vaccination_id desc") or die(mysqli_error($conn));
                                        $n=1;
                                        while ($row = mysqli_fetch_array($query)) {
                                            echo "<tr>";
                                            echo "<td>$n</td>";
                                            echo "<td>".$row['vaccination_id']."</td>";
                                            $qry2 = mysqli_query($conn,"SELECT tag_code,type_name,breed_name from livestock_animal,livestock_animal_type,livestock_animal_breed where livestock_animal.animal_type = livestock_animal_type.id_pk AND livestock_animal.breed = livestock_animal_breed.id_pk AND livestock_animal.id_pk = '".$row['animal_id']."'");
                                            $row2 = mysqli_fetch_array($qry2);
                                            echo "<td>".$row2['tag_code']."</td>";
                                            echo "<td>".$row2['type_name']."</td>";
                                            echo "<td>".$row2['breed_name']."</td>";
                                            echo "<td>".date("d-M-Y",strtotime($row['vaccination_date']))."</td>";
                                            echo "<td>".number_format($row['expense'])."</td>";
                                            $qry2 = mysqli_query($conn,"SELECT type_name from livestock_vaccination_type where id_pk = '".$row['vaccination_type']."'");
                                            $row2 = mysqli_fetch_array($qry2);
                                            echo "<td>".$row2['type_name']."</td>";
                                            echo "<td>".number_format($row['weight'])." KG</td>";
                                            ?>
                                           <td><a href="vaccination-list.php?del-id=<?php echo $row['vaccination_id'];?>" onclick='return confirm("Are You Sure?")'><i class='fas fa-trash'></i></a>| <a href="editsale.php?editid=<?php echo $row['vaccination_id'];?>"><i class='fas fa-edit'></i></a>
                                           </td>
                                            <?php
                                            echo "</tr>";
                                            $n++;
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
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <?php include 'assets/include/footer.php'; ?>

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
<?php include 'assets/include/attachmentmodal.php';?>
</html>