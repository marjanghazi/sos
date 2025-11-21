<?php
session_start();
$sidebar_id = '0201';
if (!isset($_SESSION['admin-login'])) {
    echo "access Denied. <a href='../index.php' >Go Back</a>";
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

    <title>Animal Detail - SOS Livestock</title>
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
.lbl{
        padding: 0px 15px;
        border-radius: 10px;
        color: white;
    }
</style>
<script type="text/javascript">
    function anmlpic(argument) {
        xml = new XMLHttpRequest();
            var url = "ajax.php?anmldtl="+argument;
            xml.open("GET",url,"true");
            xml.send();
            xml.onreadystatechange = function(){
                if (xml.readystate = 4) {
                    var res = xml.responseText.split('|');
                    document.getElementById('apic').src = res[0];
                    document.getElementById('ajsts').innerHTML = res[1];
                    document.getElementById('ajtype').innerHTML = res[2];
                    document.getElementById('ajbreed').innerHTML = res[3];
                    document.getElementById('ajdate').innerHTML = res[4];
                    document.getElementById('ajtag').innerHTML = res[5];
                    document.getElementById('ajgender').innerHTML = res[6];
                    document.getElementById('ajagep').innerHTML = res[7];
                    document.getElementById('ajagen').innerHTML = res[8];
                    document.getElementById('ajprice').innerHTML = res[9];
                    document.getElementById('ajfreight').innerHTML = res[10];
                    document.getElementById('ajexpense').innerHTML = res[11];
                    document.getElementById('ajsale').innerHTML = res[12];
                    document.getElementById('ajsdate').innerHTML = res[13];
                    document.getElementById('ajprofit').innerHTML = res[14];
            }
            return false;
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
                    <div class="col-lg-3" style="display:flex;justify-content: center;">
                        <div class="row" style="padding: 10px;">
                        <div class="col-lg-12 text-center">
                                        <label class="lbl btn-danger">Animal List</label>
                                        <select class="js-example-basic-single form-control" id="pro_id" name="animal" onchange="anmlpic(this.value);">
                                            <option disabled selected>Please Select</option>
                                            <?php
                                            include 'assets/include/dbconnect.php';
                                            $result = mysqli_query($conn,"SELECT livestock_animal.id_pk,tag_code,type_name,manual_code from livestock_animal,livestock_animal_type where livestock_animal.animal_type=livestock_animal_type.id_pk order by purchase_id") or die(mysqli_error($conn));
                                            while ($row = mysqli_fetch_array($result)) {
                                                echo "<option value='".$row['id_pk']."' >".$row['tag_code']." - ".$row['type_name']." - ".$row['manual_code']."</option>";
                                            }
                                            ?>
                                        </select>
                        </div>
                        <div class="col-lg-12">
                            <?php
                            $query2 = mysqli_query($conn,"SELECT image_1 from livestock_animal where status='0' order by purchase_id") or die(mysqli_error($conn));
                            $row2 = mysqli_fetch_array($query2);
                            ?>
                            <img id='apic' src="" style="width:100%;margin: auto;">
                        </div>
                    </div>
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
                                <table class="table table-bordered">
                                    <tr class="bg-primary text-white">
                                        <th>STATUS</th>
                                        <th>TYPE</th>
                                        <th>BREED</th>
                                    </tr>
                                    <tr>
                                        <td id="ajsts"></td>
                                        <td id="ajtype"></td>
                                        <td id="ajbreed"></td>
                                    </tr>
                                    <tr>   
                                        <td colspan="3"></td>
                                    </tr>
                                    <tr class="bg-warning text-white">
                                        <th>PURCHASE DATE</th>
                                        <th>TAG CODE</th>
                                        <th>GENDER</th>
                                    </tr>
                                    <tr>
                                        <td id="ajdate"></td>
                                        <td id="ajtag"></td>
                                        <td id="ajgender"></td>
                                    </tr>
                                    <tr>   
                                        <td colspan="3"></td>
                                    </tr>
                                    <tr class="bg-info text-white">
                                        <th>AGE (PURCHASE)</th>
                                        <th>AGE (NOW)</th>
                                        <th>PRICE</th>
                                    </tr>
                                    <tr>
                                        <td id="ajagep"></td>
                                        <td id="ajagen"></td>
                                        <td id="ajprice"></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>Freight of Livestock</td>
                                        <td class='text-center' id="ajfreight"></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>Total Expenses</td>
                                        <td class='text-center' id="ajexpense"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center" id="ajsdate"></td>
                                        <td>SALE</td>
                                        <td class='text-center' id="ajsale"></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>Profit/Loss</td>
                                        <td class='text-center' id="ajprofit"></td>
                                    </tr>
                                </table>
                            </div>

                            <hr>
                        
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