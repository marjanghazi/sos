<?php
session_start();
if (!isset($_SESSION['admin-login'])) {
    echo "access Denied. <a href='../index.php' >Go Back</a>";
    die();
}
if (isset($_GET['sp-id'])) {
    

?>
<!DOCTYPE html>
<html lang="en">
<?php 
                                include 'assets/include/dbconnect.php';
                                $id = $_GET['sp-id'];
                                $result = mysqli_query($conn,"SELECT * from ums_bill where id_pk='$id'") or die(mysqli_error($conn));
                                $row1 = mysqli_fetch_array($result);
                                ?>
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title><?php echo $row1['reference_no']; ?> - Bill Detail</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/dashboard.png">

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/js/standalone/selectize.min.js" integrity="sha256-+C0A5Ilqmu4QcSPxrlGpaZxJ04VjsRjKu+G82kl5UJk=" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/css/selectize.bootstrap3.min.css" integrity="sha256-ze/OEYGcFbPRmvCnrSeKbRTtjG4vGLHXgOqsyLFTRjg=" crossorigin="anonymous" />

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.css" rel="stylesheet">
<style type="text/css">
    input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
</style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar included here-->
       
         <!-- End of Sidebar -->
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                

                <!-- Begin Page Content -->
                <div class="container-fluid" style="background-color: white;">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="p-5">
                            <div class="row" >
                            <div class="col-sm-5">
                                <img src="assets/images/pay.png" style="width:35%">
                            </div>
                            <div class="col-sm-2">
                                
                            </div>
                            <div class="col-sm-5">
                               <h1 class="text-right"><b>Bill Detail</b></h1>
                           <p class="text-right">
                                    <b>Billing Month:</b> <br><?php echo $row1['billing_month']; ?> <br>
                            </p>
                            </div>
                            </div>
                                
                                <hr>
                                <div class="form-group row">
                                    <div class="col-sm-4 mb-3 mb-sm-0">
                                        <table class="table table-bordered">
                                            <tr>
                                                <td><strong>Reference #:</strong></td>
                                                <td><?php echo $row1['reference_no']; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Bill Date:</strong></td>
                                                <td><?php echo date("d-M-Y",strtotime($row1['issuance_date'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Due Date:</strong></td>
                                                <td><?php echo date("d-M-Y",strtotime($row1['due_date'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Consumer Name:</strong></td>
                                                <td><?php echo $row1['consumer_name']; ?></td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="col-sm-4 mb-3 mb-sm-0">
                                        <table class="table table-bordered">
                                            <tr>
                                                <?php
                                                $result2 = mysqli_query($conn,"SELECT x_description from ums_stations where x_code='".$row1['station']."'") or die(mysqli_error($conn));
                                                $row2 = mysqli_fetch_array($result2);
                                                ?>
                                                <td><strong>Station:</strong></td>
                                                <td><?php echo $row2['x_description']; ?></td>
                                            </tr>
                                            <tr>
                                                <?php
                                                $result2 = mysqli_query($conn,"SELECT office_name from ums_offices where id_pk='".$row1['office']."'") or die(mysqli_error($conn));
                                                $row2 = mysqli_fetch_array($result2);
                                                ?>
                                                <td><strong>Building:</strong></td>
                                                <td><?php echo $row2['office_name']; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Meter Type:</strong></td>
                                                <td><?php echo $row1['type']; ?></td>
                                            </tr>
                                            <tr>
                                                <?php
                                                $result2 = mysqli_query($conn,"SELECT provider_name from ums_providers where id_pk='".$row1['provider']."'") or die(mysqli_error($conn));
                                                $row2 = mysqli_fetch_array($result2);
                                                ?>
                                                <td><strong>Provider:</strong></td>
                                                <td><?php echo $row2['provider_name']; ?></td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="col-sm-4 mb-3 mb-sm-0">
                                        <table class="table table-bordered">
                                            <tr>
                                                <td><strong>Office timings:</strong></td>
                                                <td><?php echo $row1['start_time']; ?> to <?php echo $row1['end_time']; ?> </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Working Days:</strong></td>
                                                <td><?php echo $row1['working_days']; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Working Hours:</strong></td>
                                                <td><?php echo $row1['working_hours']; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Per Hour Cost:</strong></td>
                                                <td><?php echo number_format($row1['total_amount']/$row1['working_hours'],2); ?> PKR</td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="col-sm-3 mb-3 mb-sm-0">
                                        
                                    </div>
                                </div>

                                
                                <?php include 'assets/include/dbconnect.php'; ?>
                                <div class="form-group" id='result_table' style="border: 1px solid grey;">
                                    <div class="table-responsive">
                                    <table class="table-bordered text-center" style="width:100%;">
                                        <tr class="bg-primary" style="color: white;">
                                            <th>Previous Reading</th>
                                            <th>Current Reading</th>
                                            <th>Units Consumed</th>
                                            <th>Amount</th>
                                            <th>Per unit Cost</th>
            
                                        </tr>
                                        <?php
                                            echo "<tr>";
                                            echo "<td>".$row1['previous_reading']."</td>";
                                            echo "<td>".$row1['current_reading']."</td>";
                                            echo "<td>".$row1['unit_consumed']."</td>";
                                            echo "<td>".number_format($row1['total_amount'])." PKR</td>";
                                            echo "<td>".number_format($row1['total_amount']/$row1['unit_consumed'],2)." PKR</td>";
                                            echo "</tr>";
                                        
                                        ?>
                                    </table></div><br>
                               
                                 </div>

                                <button class="btn btn-primary btn-sm d-print-none" name="purchase-submit" onclick="window.print();">Print</button>
                                <?php  
                                        if ($row1['attachment'] != '') {
                                            echo "<a class='btn btn-success d-print-none' href='assets/images/bills/".$row1['attachment']."' target='_blank'>View Attachment</a>";
                                        }
                                        ?>
                                
                                <hr>
                                
                            <hr>
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

</body>

</html>
<?php
}
?>