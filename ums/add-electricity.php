<?php
session_start();
$sidebar_id = '0201';
if (!isset($_SESSION['admin-login'])) {
    echo "access Denied. <a href='../index.php' >Go Back</a>";
    die();
}
if (isset($_POST['sale-submit'])) {
    $station = $_POST['station'];
    $office = $_POST['office'];
    $provider = $_POST['provider'];
    $type = $_POST['type'];
    $consumer_no = $_POST['consumer_no'];
    $reference_no = $_POST['reference_no'];
    $consumer_name = $_POST['consumer_name'];
    $bmonth = $_POST['bmonth'];
    $reading_date = $_POST['reading_date'];
    $issuance_date = $_POST['issuance_date'];
    $due_date = $_POST['due_date'];
    $p_reading = $_POST['p_reading'];
    $c_reading = $_POST['c_reading'];
    $units = $_POST['units'];
    $stime = $_POST['stime'];
    $etime = $_POST['etime'];
    $days = $_POST['days'];
    $hours = $_POST['hours'];
    $total_amount = $_POST['total_amount'];
    $remarks = $_POST['remarks'];
    $img_name = $_FILES['img']['name'];
    $img_temp = $_FILES['img']['tmp_name'];
    move_uploaded_file($img_temp,"assets/images/bills/$img_name");
    include 'assets/include/dbconnect.php';
    mysqli_query($conn,"INSERT into ums_bill(station,office,provider,type,consumer_no,reference_no,consumer_name,billing_month,reading_date,issuance_date,due_date,previous_reading,current_reading,unit_consumed,start_time,end_time,working_days,working_hours,total_amount,attachment,remarks) values('$station','$office','$provider','$type','$consumer_no','$reference_no','$consumer_name','$bmonth','$reading_date','$issuance_date','$due_date','$p_reading','$c_reading','$units','$stime','$etime','$days','$hours','$total_amount','$img_name','$remarks')") or die(mysqli_error($conn));

$_SESSION['success']='Bill Added Successfully!';
header("location:utility-listing.php");
die();
}


if (isset($_GET['cancel'])) {
    $id = $_GET['cancel'];
    include 'assets/include/dbconnect.php';
    $query = mysqli_query($conn,"DELETE from prolist where sp_id='$id'");
    mysqli_query($conn,"DELETE from att where invno='".$_GET['cancel']."'");
    header("location:sale-list.php");
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
    <?php include 'assets/include/select2.php'; ?>

    <title>Add Monthly  - SOS UMS</title>
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
.btn-grad {background-image: linear-gradient(to right, #314755 0%, #26a0da  51%, #314755  100%)}
         .btn-grad {
            margin: 10px;
            padding: 5px 25px;
            text-align: center;
            text-transform: uppercase;
            transition: 0.5s;
            background-size: 200% auto;
            color: white;            
            box-shadow: 0 0 20px #eee;
            border-radius: 10px;
          }

          .btn-grad:hover {
            background-position: right center; /* change the direction of the change here */
            color: #fff;
            text-decoration: none;
          }
          .lbl{
            padding: 2px 20px;
            border-radius: 15px;
            color: white;
            margin-top: 15px;
            background-image: linear-gradient(to top, #314755 0%, #26a0da  51%, #314755  100%)
          }
</style>
<script type="text/javascript">
    function unitscon() {
        document.getElementById("ucon").value = parseInt(document.getElementById("cread").value-document.getElementById("pread").value)
    }
    function workingh(days) {
        var from = document.getElementById("endtime").value;
        var to = document.getElementById("strttime").value;
        var diff = ( new Date("1970-1-1 " + from) - new Date("1970-1-1 " + to) ) / 1000 / 60 / 60; 
        diff = diff*days;
        document.getElementById("twh").value = diff;
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
                                <?php
                                if (isset($_SESSION['success'])) {
                                    unset($_SESSION['success']);
                                    ?>
                                <div class="alert alert-success in alert-dismissible" style="margin-top:18px;">
                                    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
                                    <strong>Success!</strong> The Sale Is Submitted.
                                </div>
                                    <?php
                                }
                                if (isset($_SESSION['error'])) {
                                    
                                    ?>
                                <div class="alert alert-danger in alert-dismissible" style="margin-top:18px;">
                                    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
                                    <strong>Failed!</strong> <?=$_SESSION['error'];?>
                                </div>
                                    <?php
                                    unset($_SESSION['error']);
                                }
                                ?>
                                
                                <h2 class=" mb-4 lbl">ADD BILL</h2>
                            </div>
                            <form class="user" method="post" action="" enctype="multipart/form-data" onkeydown="return event.key != 'Enter';">
                                <?php 
                                include 'assets/include/dbconnect.php';
                                ?>
                                <div class="form-group row">
                                    <div class="col-sm-4 mb-2 mb-sm-0">
                                        <label class="lbl">Station</label>
                                        <select class="form-control js-example-basic-single" name="station" required>
                                            <?php
                                            $result = mysqli_query($conn,"SELECT * from ums_stations order by x_description") or die(mysqli_error($conn));
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "<option value='".$row['x_code']."'>".$row['x_description']."</option>";
                                        }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-4 mb-2 mb-sm-0">
                                        <label class="lbl">Office*</label>
                                        <select class="form-control js-example-basic-single" name="office" required>
                                            <?php
                                            $result = mysqli_query($conn,"SELECT * from ums_offices order by office_name") or die(mysqli_error($conn));
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "<option value='".$row['id_pk']."'>".$row['office_name']."</option>";
                                        }
                                            ?>
                                        </select> 
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                        <label class="lbl">Provider*</label>
                                        <select class="form-control js-example-basic-single" name="provider" required>
                                           <?php
                                            $result = mysqli_query($conn,"SELECT * from ums_providers") or die(mysqli_error($conn));
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "<option value='".$row['id_pk']."'>".$row['provider_name']."</option>";
                                        }
                                            ?>
                                        </select>
                                        <label style="color:red;display: none;" id="venerr">*Select Customer</label>
                                    
                                </div>
                                    </div>
                                    
                                </div>

                                <div class="form-group row">
                                    <div class="col-lg-3">
                                        <label class="lbl">Meter Type</label>
                                        <select class="js-example-basic-single form-control" name="type" >
                                            <option>Single Phase</option>
                                            <option>Dual Phase</option>
                                            <option>Third Phase</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group" >
                                        <label class="lbl">Consumer #</label>
                                        <input type="text" class="form-control" name="consumer_no" 
                                            placeholder="Consumer # (optional)" value="" >
                                    
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group" >
                                        <label class="lbl">Reference #</label>
                                        <input type="text" class="form-control" name="reference_no" 
                                            placeholder="Reference #" value="" required>
                                    
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group">
                                        <label class="lbl">Consumer Name</label>
                                        <input type="text" class="form-control"name="consumer_name" 
                                            placeholder="Consumer Name" value="" required>
                                    
                                        </div>
                                    </div>
                                     
                                </div>

                                <div class="form-group row">
                                    <div class="col-lg-3">
                                        <label class="lbl">Billing Month</label>
                                        <select class="js-example-basic-single form-control" name="bmonth">
                                        <?php
                                        $date = date('M Y', time());
                                        echo "<option>".$date."</option>";
                                        $n=1;
                                        while ($n<36) {
                                            $str = "-".$n." months";
                                            $date = date('M Y', strtotime($str));
                                            echo "<option>".$date."</option>";
                                            $n++;
                                        }
                                        ?>
                                        </select>
                                        
                                    </div>
                                    <div class="col-lg-3">
                                        <label class="lbl">Reading Date</label>
                                        <input type="date" class="form-control" value="" required name="reading_date">
                                    </div>
                                    <div class="col-lg-3">
                                        <label class="lbl">Issuance Date</label>
                                        <input type="date" class="form-control" value="" required name="issuance_date">
                                    </div>
                                    <div class="col-lg-3">
                                        <label class="lbl">Due Date</label>
                                        <input type="date" class="form-control" value="" required name="due_date">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-lg-4">
                                        <label class="lbl">Previous Reading</label>
                                        <input type="number" class="form-control" id="pread" value="0" required name="p_reading">
                                    </div>
                                    <div class="col-lg-4">
                                        <label class="lbl">Current Reading</label>
                                        <input type="number" class="form-control" onkeyup="unitscon();" id="cread" value="0" required name="c_reading">
                                    </div>
                                    <div class="col-lg-4">
                                        <label class="lbl">Unit Consumed</label>
                                        <input type="number" class="form-control" id="ucon" readonly value="0" required name="units" min='1'>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-lg-3">
                                        <label class="lbl">Office Start Time</label>
                                        <input type="time" value="09:00" id="strttime" class="form-control" required name="stime">
                                    </div>
                                    <div class="col-lg-3">
                                        <label class="lbl">Office End Time</label>
                                        <input type="time" value="17:30" id="endtime" class="form-control" required name="etime">
                                    </div>
                                    <div class="col-lg-3">
                                        <label class="lbl">Working Days in Month</label>
                                        <input type="number" value="1" min='1' id="wd" onchange="workingh(this.value);" class="form-control" required name="days">
                                    </div>
                                    <div class="col-lg-3">
                                        <label class="lbl">Total Working Hours</label>
                                        <input type="number" min='1' id="twh" value="8.5" readonly class="form-control" required name="hours">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-lg-3">
                                        <label class="lbl">Total Bill Amount</label>
                                        <input type="number" min='1' value="0" class="form-control" required name="total_amount">
                                    </div>
                                    <div class="col-lg-3">
                                        <label class="lbl">Bill Picture</label>
                                        <input type="file"  class="form-control" required name="img">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-lg-12">
                                        <label class="lbl">Remarks</label>
                                        <textarea name="remarks" class="form-control" placeholder="Remarks(Optional)"></textarea>
                                    </div>
                                </div>
     
                                <div class="row text-center">
                                    <div class="col-lg-12">
                                    <input type='submit' class="btn btn-grad btn-sm" name="sale-submit" value="Save">
                                <a href="utility-listing.php" class="btn btn-sm btn-danger">Cancel</a>
                                <br><br>
                                     </div>
                                 </div>                                 
                            </form>                      
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
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>