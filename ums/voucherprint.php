<?php
session_start();
if (!isset($_SESSION['admin-login'])) {
    echo "access Denied. <a href='../index.php' >Go Back</a>";
    die();
}

if (isset($_GET['voucherid'])) {
        include 'assets/include/dbconnect.php';
        $vid = $_GET['voucherid'];
        $query = mysqli_query($conn,"SELECT * from livestock_sale where sale_id='$vid'") or die(mysqli_error($conn));
        $row = mysqli_fetch_array($query);
        ?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Profit Calculation Print - ZBS</title>
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
                <div class="container-fluid">

                    <div class="container">

        <div class="card o-hidden border-0 shadow-lg my-5">
            <div class="card-body p-0">
                <!-- Nested Row within Card Body -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="p-5">
                            <div class="row" >
                            <div class="col-lg-4">
                                
                            </div>
                            <div class="col-lg-4 text-center">
                                <img src='assets/images/livestock.png' style='width: 40%;'><br>
                               <h3><b>Profit Calculation</b></h3>
                            </div>
                            <div class="col-lg-4">
                                
                            </div>
                            </div>
                                
                                <hr>
                                <div class="form-group row">
                                    <div class="col-sm-12 mb-3 mb-sm-0">
                                        <label onclick="window.print();" style="cursor: pointer;">
                                            <i class="fas fa-print"></i>
                                        </label>  
                                    </div>

                                    <div class="col-sm-3 mb-3 mb-sm-0">
                                        
                                    </div>
                                </div>
                                <div class="row">
                                    <?php
                                    $query2 = mysqli_query($conn,"SELECT tag_code,type_name,breed_name,image_1 from livestock_animal,livestock_animal_type,livestock_animal_breed where livestock_animal.animal_type = livestock_animal_type.id_pk AND livestock_animal.breed = livestock_animal_breed.id_pk AND livestock_animal.id_pk='".$row['animal_id']."'") or die(mysqli_error($conn));
                                        $row2 = mysqli_fetch_array($query2);  
                                    ?>
                                    <div class="col-sm-9">
                                        <p>
                                            <b>Animal ID:</b> <?=$row2['tag_code'];?>
                                        </p>
                                        <p>
                                            <b>Animal Type:</b> <?=$row2['type_name'];?>
                                        </p>
                                        <p>
                                            <b>Animal Breed:</b> <?=$row2['breed_name'];?>
                                        </p>
                                    </div>
                                    <div class="col-sm-3 text-right">
                                        <img src="assets/images/<?=$row2['image_1'];?>" style="width:70%;">
                                    </div>
                                </div>
                                <br>
                                <div class="form-group" id='result_table'>
                                    <div class="table-responsive">
                                    <table class="table-bordered" style="width:100%;">
                                        <tr class="bg-primary text-center" style="color: white;">
                                            <th>Sr.</th>
                                            <th>Activity</th>
                                            <th>Activity Date</th>
                                            <th>Activity Amount</th>
                                        </tr>
                                        <?php
                                        $query = mysqli_query($conn,"SELECT * from livestock_animal where id_pk = '".$row['animal_id']."'") or die(mysqli_error($conn));
                                        $row2 = mysqli_fetch_array($query);
                                        ?>
                                        <tr>
                                            <td class='text-center'>1</td>
                                            <td>Purchase of Livestock</td>
                                            <td><?=date("d-M-Y",strtotime($row2['purchase_date']));?></td>
                                            <td class='text-center'><?=number_format($row2['price']);?></td>
                                        </tr>
                                        <tr>
                                            <td class='text-center'>2</td>
                                            <td>Freight of Livestock</td>
                                            <td><?=date("d-M-Y",strtotime($row2['purchase_date']));?></td>
                                            <td class='text-center'><?=number_format($row2['freight']);?></td>
                                        </tr>
                                        <?php
                                        $query = mysqli_query($conn,"SELECT * from livestock_animal_vaccination where animal_id = '".$row['animal_id']."' order by vaccination_date desc") or die(mysqli_error($conn));
                                        $n=3;
                                        $sumv = 0;
                                        if (mysqli_num_rows($query)>0) {
                                            while ($row3 = mysqli_fetch_array($query)) {
                                                echo "<tr>";
                                                echo "<td class='text-center'>$n</td>";
                                                echo "<td>Vaccination Expense</td>";
                                                echo "<td>".date("d-M-Y",strtotime($row3['vaccination_date']))."</td>";
                                                echo "<td class='text-center'>".number_format($row3['expense'],2)."</td>";
                                                echo "</tr>";
                                                $n++;
                                                $sumv = $sumv + $row3['expense'];
                                            }
                                        }

                                        $query = mysqli_query($conn,"SELECT * from livestock_animal_expense where animal_id = '".$row['animal_id']."' order by expense_date desc") or die(mysqli_error($conn));
                                        $sume = 0;
                                        if (mysqli_num_rows($query)>0) {
                                            while ($row3 = mysqli_fetch_array($query)) {
                                                echo "<tr>";
                                                echo "<td class='text-center'>$n</td>";
                                                $qry10 = mysqli_query($conn,"SELECT type_name from livestock_expense_type where id_pk = '".$row3['expense_type']."'");
                                                $row10 = mysqli_fetch_array($qry10);
                                                echo "<td>".$row10['type_name']." Expense</td>";
                                                echo "<td>".date("d-M-Y",strtotime($row3['expense_date']))."</td>";
                                                echo "<td class='text-center'>".number_format($row3['expense'],2)."</td>";
                                                echo "</tr>";
                                                $n++;
                                                $sume = $sume + $row3['expense'];
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td class="text-center"><?=$n;?></td>
                                            <td>Sale of Livestock</td>
                                            <td><?=date("d-M-Y",strtotime($row['sale_date']));?></td>
                                            <td class="text-center"><?=number_format($row['payment_amount']);?></td>
                                        </tr>
                                        <?php
                                        $profit = $row['payment_amount'] - $row2['price']-$row2['freight']-$sumv-$sume;
                                        ?>
                                        <tr>
                                            <th colspan="3" class="text-center">Profit</th>
                                            <?php
                                            if ($profit > 0) {
                                                echo "<th class='text-success text-center'>".number_format($profit)."/-</th>";
                                              } 
                                              else{
                                                echo "<th class='text-danger text-center'>".number_format($profit)."/-</th>";
                                              }
                                            ?>
                                            <td></td>
                                        </tr>
                                    </table>
                                </div>
                                 </div>
                                <hr>
                                
                            </form>
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