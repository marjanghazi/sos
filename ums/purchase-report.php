<?php
session_start();
$sidebar_id = '0201';
if (!isset($_SESSION['admin-login'])) {
    echo "access Denied. <a href='../index.php' >Go Back</a>";
    die();
}
if (isset($_GET['strt-date'])) {
    $strt_date = $_GET['strt-date'];
}
else{
    $strt_date = date("Y-m-d",strtotime("first day of this month"));
}

if (isset($_GET['end-date'])) {
    $end_date = $_GET['end-date'];
}
else{
    $end_date = date("Y-m-d",strtotime("last day of this month"));
}

if (isset($_GET['type'])) {
    $fltr_type = $_GET['type'];
}
else{
    $fltr_type = "All";
}

if (isset($_GET['breed'])) {
    $fltr_breed = $_GET['breed'];
}
else{
    $fltr_breed = "All";
}
include 'assets/include/dbconnect.php';
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

    <title>Purchase Report - SOS Livestock</title>
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
.hover_img a { position:relative; }
.hover_img a span { position:absolute; display:none; z-index:99; }
.hover_img a:hover span { display:block; }
</style>
<script>
    function breedd(argument) {
        xml = new XMLHttpRequest();
            var url = "ajax.php?breedf="+argument;
            xml.open("GET",url,"true");
            xml.send();
            xml.onreadystatechange = function(){
                if (xml.readystate = 4) {
                    document.getElementById('ware').innerHTML = xml.responseText;
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
                        <div class="p-5">
                            <form action="" method="get">
                            <div class="row">
                                <div class="col-lg-2">
                                    <strong>START DATE</strong>
                                    <input type="date" name="strt-date" value="<?=$strt_date;?>" class="form-control">    
                                </div>
                                <div class="col-lg-2">
                                    <strong>END DATE</strong>
                                    <input type="date" name="end-date" value="<?=$end_date;?>" class="form-control">    
                                </div>
                                <div class="col-lg-2">
                                    <strong>TYPE</strong>
                                    <select class="form-control" name="type" onchange="breedd(this.value);">
                                        <option>All</option>
                                        <?php
                                            $result = mysqli_query($conn,"SELECT * from livestock_animal_type") or die(mysqli_error($conn));
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "<option value='".$row['id_pk']."' ";
                                            if ($row['id_pk'] == $fltr_type) {
                                                echo "selected";
                                            }
                                            echo " >".$row['type_name']."</option>";
                                        }
                                            ?>
                                    </select>   
                                </div>
                                <div class="col-lg-3">
                                    <strong>BREED</strong>
                                    <select class="form-control" name="breed" id='ware'>
                                        <option>All</option>
                                        <?php
                                        if ($fltr_type != 'All') {
                                            $result = mysqli_query($conn,"SELECT * from livestock_animal_breed where animal_type='$fltr_type'") or die(mysqli_error($conn));
                                            while ($row = mysqli_fetch_array($result)) {
                                                echo "<option value='".$row['id_pk']."' ";
                                                if ($row["id_pk"] == $fltr_breed) {
                                                    echo "selected";
                                                }
                                                echo " >".$row['breed_name']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>   
                                </div>
                                <div class="col-lg-3">
                                    <div style="opacity: 0;">t</div>
                                    <button type="submit"  name="filter" class="btn btn-success"><i class="fas fa-filter"></i> Filter</button>
                                    <a href="profit-report.php" class="btn btn-danger"><i class="fas fa-recycle"></i> Reset</a>
                                </div> 
                            </div>
                            </form>
                            <hr>
                            <div class="row">
                                <table class="table table-bordered table-stripped">
                                    <thead style="background: #020024;background: linear-gradient(180deg, rgba(2, 0, 36, 1) 0%, rgba(9, 9, 121, 1) 35%, rgba(5, 130, 156, 1) 100%);color: white;">
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Animal</th>
                                            <th>Type</th>
                                            <th>Breed</th>
                                            <th>Manual Code</th>
                                            <th>Status</th>
                                            <th>Weight</th>
                                            <th>Purchase Date</th>
                                            <th>Purchase Price</th>
                                        </tr>
                                    </thead>
                                    <tbody> 
                                <?php
                                
                                $sql = "SELECT * from livestock_animal where purchase_date between '$strt_date' and '$end_date'";
                                if ($fltr_type != 'All') {
                                    $sql = $sql." AND animal_type = $fltr_type";
                                }
                                if ($fltr_breed != 'All') {
                                    $sql = $sql." AND breed = $fltr_breed";
                                }
                                $sql = $sql." order by purchase_date desc";
                                $query = mysqli_query($conn,$sql) or die(mysqli_error($conn));
                                $n=1;
                                $tpur = 0;
                                $twgt = 0;
                                while ($row = mysqli_fetch_array($query)) {
                                    echo "<tr>";
                                    echo "<td>$n</td>";
                                    echo "<td><div class='hover_img'>";
                                    echo "<a target='_blank' href='assets/images/".$row['image_1']."' >Show Image<span><img src='assets/images/".$row['image_1']."' alt='image' height='300' /></span></a></div></td>";
                                    $query3 = mysqli_query($conn,"SELECT type_name from livestock_animal_type where id_pk='".$row['animal_type']."'") or die(mysqli_error($conn));
                                    $row3 = mysqli_fetch_array($query3);
                                    echo "<td>".$row3['type_name']."</td>";
                                    $query3 = mysqli_query($conn,"SELECT breed_name from livestock_animal_breed where id_pk='".$row['breed']."'") or die(mysqli_error($conn));
                                    $row3 = mysqli_fetch_array($query3);
                                    echo "<td>".$row3['breed_name']."</td>";
                                    echo "<td>".$row['manual_code']."</td>";
                                    if ($row['status'] == 0) {
                                        echo "<td><span class='lbl text-white bg-success'>Alive</span></td>";
                                    }
                                    elseif($row['status'] == 1){
                                        echo "<td><span class='lbl text-white bg-primary'>Sold</span></td>";
                                    }
                                    else{
                                        echo "<td><span class='lbl text-white bg-danger'>Dead</span></td>";
                                    }
                                    echo "<td>".$row['weight']." KG</td>";
                                    $twgt += $row['weight'];
                                    echo "<td>".date("d-M-Y",strtotime($row['purchase_date']))."</td>";
                                    echo "<td>".number_format($row['price'])."</td>";
                                    $tpur += $row['price'];
                                    echo "</tr>";
                                    $n++;
                                }
                                ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7"></td>
                                    </tr>
                                    <tr>
                                        <th colspan="5">TOTAL</th>
                                        <th><?=number_format($twgt);?> KG</th>
                                        <th colspan="2"></th>
                                        <th><?=number_format($tpur);?></th>
                                    </tr>
                                </tfoot>
                                </table>
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
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>