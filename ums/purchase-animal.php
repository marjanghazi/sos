<?php
session_start();
$sidebar_id = '0101';

if (!isset($_SESSION['admin-login'])) {
    echo "access Denied. <a href='index.php' >Go Back</a>";
    die();
}

if (isset($_POST['purchase-submit'])) {
    $pid = $_POST['pid'];
    $pdate = $_POST['date'];
    $price = $_POST['price'];
    $freight = $_POST['freight'];
    $age = $_POST['age'];
    $weight = $_POST['weight'];
    $prid = $_POST['prid'];
    $breed = $_POST['breed'];
    $gender = $_POST['gender'];
    if ($gender == 0) {
        $tagcode = date("Y")."/".$pid."/M";
    }
    else{
        $tagcode = date("Y")."/".$pid."/F";
    }
    $manualid = $_POST['manualid'];
    if ($_FILES['img']['size']!= 0) {  
        $img_name = $_FILES['img']['name'];
        $img_temp = $_FILES['img']['tmp_name'];
        move_uploaded_file($img_temp,"assets/images/$img_name");
    }
    else{
        $img_name = 'noimage.png';
    }
include 'assets/include/dbconnect.php';
mysqli_query($conn,"INSERT into livestock_animal(purchase_id,animal_type,purchase_date,breed,price,freight,age,weight,gender,image_1,tag_code,manual_code) values('$pid','$prid','$pdate','$breed','$price','$freight','$age','$weight','$gender','$img_name','$tagcode','$manualid')") or die(mysqli_error($conn));
$_SESSION['success']='Animal Purchase done';
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

    <title>Purchase Animal - Livestock</title>
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
    function totalcost() {
        document.getElementById("totaljs").value = parseInt(document.getElementById("pricejs").value) + parseInt(document.getElementById("freightjs").value);
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
                    <div class="col-lg-12">
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
                                
                                <h1 class="h4 text-gray-900 mb-4">Purchase Animal</h1>
                            </div>
                            <form class="user" method="post" action="" enctype="multipart/form-data" onkeydown="return event.key != 'Enter';" >
                                <?php 
                                include 'assets/include/dbconnect.php';
                                $result = mysqli_query($conn,"select concat(lpad(ifnull(max(substring(purchase_id,1,6)),0)+1,6,0),'P') as 'ID' from livestock_animal") or die(mysqli_error($conn));

                                $row = mysqli_fetch_array($result);
                                $sp_id = $row[0];
                                ?>
                                <div class="form-group row">
                                    <div class="col-sm-3 mb-3 mb-sm-0">
                                        <label>Purchase ID*</label>
                                        <input type="hidden" id='pid_ajax' value="<?=$row[0];?>">
                                        <input type="text" readonly class="form-control" name="pid" 
                                            placeholder="Purchase ID" value="<?=$row[0];?>">
                                    </div>
                                    <div class="col-sm-3 mb-3 mb-sm-0">
                                        <label>Manual Tag ID (Optional)</label>
                                        <input type="text" class="form-control" name="manualid" 
                                            placeholder="Mannual Tag ID" value=""> 
                                    </div>
                                    <div class="col-sm-3 mb-3 mb-sm-0">
                                        <label>Date*</label>
                                        <input type="date" class="form-control" name="date" 
                                            placeholder="Date" required value="<?=date("Y-m-d")?>"> 
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                        <label>Select Animal Type*</label>
                                        <select class="form-control js-example-basic-single" onchange="breedd(this.value);" name="prid" id='ven'>
                                            <option disabled selected>SELECT TYPE</option>
                                            <?php
                                            $result = mysqli_query($conn,"SELECT * from livestock_animal_type") or die(mysqli_error($conn));
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "<option value='".$row['id_pk']."'>".$row['type_name']."</option>";
                                        }
                                            ?>
                                        </select>
                                        <label style="color:red;display: none;" id="venerr">*Select Type</label>
                                </div>
                                    </div>

                                </div>

                                <div class="row">
                                     <div class="col-sm-3">
                                        <div class="form-group">
                                        <label>Select breed*</label>
                                        <select class="form-control" name="breed" id='ware'>
                                            <option disabled selected>SELECT BREED</option>
                                            
                                        </select>
                                        <label style="color:red;display: none;" id="warerr">*Select Breed</label>
                                </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <label>Price (PKR)</label>
                                        <input type="number" class="form-control" name="price" 
                                            placeholder="Price" required value="0" onchange="totalcost();" id="pricejs"> 
                                    </div>
                                    <div class="col-sm-3">
                                        <label>Freight (PKR)</label>
                                        <input type="number" class="form-control" name="freight" 
                                            placeholder="Freight" onchange="totalcost();" id="freightjs" required value="0"> 
                                    </div>
                                    <div class="col-sm-3">
                                        <label>Total (PKR)</label>
                                        <input type="number" class="form-control"  
                                            placeholder="Total" id="totaljs" readonly value="0"> 
                                    </div>
                                    <div class="col-sm-2">
                                        <label>Age (MONTHS)</label>
                                        <input type="number" step="any" class="form-control" name="age" 
                                            placeholder="RMB" required value="0"> 
                                    </div>
                                    <div class="col-sm-2">
                                        <label>Weight (KG)</label>
                                        <input type="number" step="any" class="form-control" name="weight" 
                                            placeholder="RMB" required value="0"> 
                                    </div>
                                    <div class="col-sm-2">
                                        <label>Gender</label>
                                        <select class="form-control" name="gender" required > 
                                            <option value="0">Male</option>
                                            <option value="1">Female</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <label>Image (optional)</label>
                                        <input type="file" name="img" class="form-control">
                                    </div>
                                </div>
                                <hr>
                                
                                
                                <div class="row text-center">
                                     <div class="col-lg-12">
                                         <input type='submit'  class="btn btn-primary btn-sm" name="purchase-submit" value="Register Purchase" onclick="return validateform();">
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