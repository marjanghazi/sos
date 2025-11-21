<?php
session_start();
$sidebar_id = '0101';

if (!isset($_SESSION['admin-login'])) {
    echo "access Denied. <a href='../index.php' >Go Back</a>";
    die();
}

if (isset($_POST['purchase-submit'])) {
    $pid = $_POST['pid'];
    $pdate = $_POST['date'];
    $expense = $_POST['expense'];
    $weight = $_POST['weight'];
    $prid = $_POST['prid'];
    $quantity = $_POST['quantity'];
    $animalid = $_POST['animalid'];
    $nar = $_POST['nar'];
include 'assets/include/dbconnect.php';
mysqli_query($conn,"INSERT into livestock_animal_vaccination(vaccination_id,vaccination_type,quantity,vaccination_date,animal_id,expense,weight,nar) values('$pid','$prid','$quantity','$pdate','$animalid','$expense','$weight','$nar')") or die(mysqli_error($conn));
$_SESSION['success']='Vaccination done';
header("location:purchase-listing.php");
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

    <title>Vaccination - Livestock</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> 
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/dashboard.png">
    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.css" rel="stylesheet">
<style type="text/css">
    input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
</style>
<script type="text/javascript">
    function searchP(ar) {
        if(ar == ''){
         document.getElementById('searchDrop').style.display = "None";   
        }
        else{
            xml = new XMLHttpRequest();
            var url = "ajax.php?str="+ar;
            xml.open("GET",url,"true");
            xml.send();
            xml.onreadystatechange = function(){
                if (xml.readystate = 4) {
                    if (xml.responseText == 'no') {
                        document.getElementById("searchDrop").style.display = "none";
                    }
                    else{
                    document.getElementById("searchDrop").innerHTML = xml.responseText;
                    document.getElementById("searchDrop").style.display = "block";
                    }
                }
            }
        }
        }
        function selectP(arg) {
            document.getElementById("searchDrop").style.display = "none";
            xml = new XMLHttpRequest();
            var url = "ajax.php?selectp="+arg;
            xml.open("GET",url,"true");
            xml.send();
            xml.onreadystatechange = function(){
                if (xml.readystate = 4) {
                    var res = xml.responseText.split('||');
                    document.getElementById('prid').value = res[0];
                    document.getElementById('pn').value = res[1];
                }
            }
            return false;
        }
        function addP() {
            if (document.getElementById("pro_p").value == "" || document.getElementById("pro_p").value == "0" || document.getElementById("pro_q").value == "" || document.getElementById("pro_q").value == "0") {
                return false;
            }
            var id = document.getElementById("pro_id").value;
            var q = document.getElementById("pro_q").value;
            var p = document.getElementById("pro_p").value;
            var pid = document.getElementById("pid_ajax").value;
            var t = document.getElementById("dis_type").value;
            var a = document.getElementById("dis_amount").value;
            xml = new XMLHttpRequest();
            var url = "ajax.php?addid="+id+"&q="+q+"&p="+p+"&pid="+pid+"&t="+t+"&a="+a;
            xml.open("GET",url,"true");
            xml.send();
            xml.onreadystatechange = function(){
                if (xml.readystate = 4) {
                    var res = xml.responseText.split('||');
                    document.getElementById("result_table").innerHTML = res[0];
                    document.getElementById("pro_id").innerHTML = res[1];
            }
            document.getElementById("pro_q").value='1';
            document.getElementById("pro_p").value = '0';
            document.getElementById("dis_amount").value = '0';
            document.getElementById("perr").style.display = 'None';
            return false;
        }
        }

        function delp(arg) {
            var pid = document.getElementById("pid_ajax").value;
            xml = new XMLHttpRequest();
            var url = "ajax.php?delp="+arg+"&pid="+pid;
            xml.open("GET",url,"true");
            xml.send();
            xml.onreadystatechange = function(){
                if (xml.readystate = 4) {
                    var res = xml.responseText.split('||');
                    document.getElementById("result_table").innerHTML = res[0];
                    document.getElementById("pro_id").innerHTML = res[1];
            }
            return false;
        }
    }
        function ad(argument) {
            var sub = document.getElementById('subajax').value;
            sub = sub - argument;
            document.getElementById('gtotalajax').value = sub;
        }

        function price(arg) {
            var ware = 0;
            xml = new XMLHttpRequest();
            var url = "ajax.php?price="+arg+"&ware="+ware;
            xml.open("GET",url,"true");
            xml.send();
            xml.onreadystatechange = function(){
                if (xml.readystate = 4) {
                    document.getElementById('pro_p').value = xml.responseText;
            }
        }
    }
    function validatepayment(p) {
        var gt = parseInt(document.getElementById("gtotalajax").value);
        p = parseInt(p);
        if(p > gt){
            document.getElementById("gpaymentajax").value = gt;
        }
    }
    function validateform() {

        if (document.getElementById("ven").value == 'SELECT VENDOR') {
            document.getElementById("venerr").style.display = 'Block';
            return false;
        }
        else{
            document.getElementById("venerr").style.display = 'None';
        }
        if (document.getElementById("ware").value == 'SELECT WAREHOUSE') {
            document.getElementById("warerr").style.display = 'Block';
            return false;
        }
        else{
            document.getElementById("warerr").style.display = 'None';
        }
        if (document.getElementById("fr") == null) {
            document.getElementById("perr").style.display = 'Block';
            return false;
        }
        else{
            document.getElementById("perr").style.display = 'None';
        }
    }
    function calculategst(arg) {
        var subtotal = parseInt(document.getElementById("subajax").value);
        var discount = parseInt(document.getElementById("discountajax").value);
        document.getElementById("gstamountajax").value = ((subtotal-discount)/100)*arg;
        document.getElementById("gtotalajax").value = (subtotal-discount)+((subtotal-discount)/100)*arg;
    }
    function breedd(argument) {
        xml = new XMLHttpRequest();
            var url = "ajax.php?breed="+argument;
            xml.open("GET",url,"true");
            xml.send();
            xml.onreadystatechange = function(){
                if (xml.readystate = 4) {
                    document.getElementById('ware').innerHTML = xml.responseText;
            }
        }
    }
</script>
<?php include "assets/include/fileuploadjs.php" ?>
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
                    <div class="col-lg-3" style="display: flex;
  justify-content: center;">
                        <?php
                            include 'assets/include/dbconnect.php';
                            $query2 = mysqli_query($conn,"SELECT image_1 from livestock_animal where id_pk='".$_GET['id']."'") or die(mysqli_error($conn));
                            $row2 = mysqli_fetch_array($query2);
                        ?>
                        <img src="assets/images/<?=$row2['image_1'];?>" style="width:100%;
  margin: auto;">
                    </div>
                    <div class="col-lg-9">
                        <div class="p-5">
                            <div class="text-center">
                                <?php
                                if (isset($_SESSION['success'])) {
                                    unset($_SESSION['success']);
                                    ?>
                                <div class="alert alert-success in alert-dismissible" style="margin-top:18px;">
                                    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
                                    <strong>Success!</strong> The Purchase Is Submitted.
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
                                
                                <h1 class="h4 text-gray-900 mb-4">Animal Vaccination</h1>
                            </div>
                            <form class="user" method="post" action="" enctype="multipart/form-data" onkeydown="return event.key != 'Enter';" >
                                <?php 
                                include 'assets/include/dbconnect.php';
                                $result = mysqli_query($conn,"select concat(lpad(ifnull(max(substring(vaccination_id,1,6)),0)+1,6,0),'V') as 'ID' from livestock_animal_vaccination") or die(mysqli_error($conn));

                                $row = mysqli_fetch_array($result);
                                $sp_id = $row[0];
                                ?>
                                <div class="form-group row">
                                    <div class="col-sm-3 mb-3 mb-sm-0">
                                        <label>Transaction ID*</label>
                                        <input type="hidden" id='pid_ajax' value="<?=$row[0];?>">
                                        <input type="text" readonly class="form-control" name="pid" 
                                            placeholder="Purchase ID" value="<?=$row[0];?>">
                                    </div>
                                    <div class="col-sm-3 mb-3 mb-sm-0">
                                        <label>Date*</label>
                                        <input type="date" class="form-control" name="date" 
                                            placeholder="Date" required value="<?=date("Y-m-d")?>"> 
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                        <?php
                                        $query2 = mysqli_query($conn,"SELECT type_name from livestock_animal,livestock_animal_type where livestock_animal.animal_type = livestock_animal_type.id_pk and livestock_animal.id_pk='".$_GET['id']."'") or die(mysqli_error($conn));
                                            $row2 = mysqli_fetch_array($query2);
                                        ?>
                                        <label>Animal Type*</label>
                                        <input type="hidden" name="animalid" value="<?=$_GET['id']?>">
                                        <input type="text" readonly class="form-control" value="<?=$row2['type_name']?>">
                                </div>
                                    </div>

                                     <div class="col-sm-3">
                                        <div class="form-group">
                                            <?php
                                        $query2 = mysqli_query($conn,"SELECT breed_name from livestock_animal,livestock_animal_breed where livestock_animal.breed = livestock_animal_breed.id_pk and livestock_animal.id_pk='".$_GET['id']."'") or die(mysqli_error($conn));
                                            $row2 = mysqli_fetch_array($query2);
                                        ?>
                                        <label>breed*</label>
                                         <input type="text" readonly class="form-control" value="<?=$row2['breed_name']?>">
                                </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-sm-3">
                                        <label>Select Vaccination Type*</label>
                                        <select class="form-control js-example-basic-single" name="prid" id='ven'>
                                            <option disabled selected>SELECT TYPE</option>
                                            <?php
                                            $result = mysqli_query($conn,"SELECT * from livestock_vaccination_type") or die(mysqli_error($conn));
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "<option value='".$row['id_pk']."'>".$row['type_name']."</option>";
                                        }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-2">
                                        <label>Quantity (ml)</label>
                                        <input type="number" step="any" class="form-control" name="quantity" 
                                            placeholder="Quantity" required value="0"> 
                                    </div>
                                    <div class="col-sm-2">
                                        <label>Expense</label>
                                        <input type="number" step="any" class="form-control" name="expense" 
                                            placeholder="" required value="0"> 
                                    </div>
                                    <div class="col-sm-2">
                                        <label>Weight (KG)</label>
                                        <input type="number" step="any" class="form-control" name="weight" 
                                            placeholder="RMB" required value="0"> 
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <label>Remarks</label>
                                        <textarea class="form-control" name="nar" placeholder="Remarks (Optional)"></textarea>
                                    </div>
                                </div>

                                <hr>
                                
                                
                                <div class="row text-center">
                                     <div class="col-lg-12">
                                         <input type='submit'  class="btn btn-primary btn-sm" name="purchase-submit" value="Add" onclick="return validateform();">
                                <a href="purchase-listing.php" class="btn btn-sm btn-danger">Cancel</a>
                                <br>
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