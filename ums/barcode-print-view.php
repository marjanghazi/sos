<?php
session_start();
$sidebar_id = '0807';
if (!isset($_SESSION['admin-login'])) {
    echo "Access Denied. <a href='index.php' >Go Back</a>";
    die();
}

include 'assets/include/dbconnect.php';
$prcheck = mysqli_query($conn,"SELECT * from user_to_permission where user_id='".$_SESSION['admin-login']."' and permission_id='1' and sidebar_id='$sidebar_id'") or die(mysql_error($conn));
if (!mysqli_num_rows($prcheck) && $_SESSION['type']!='1') {
        $_SESSION['error'] = 'You do Not have rights to view this Report!';
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

    <title>Stock Report - ZBS</title>

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
th{
    position: sticky;
    top: 0;
    background-color: black;
    color: white;
}
</style>
</head>

<body id="page-top">

    <div class="container-fluid">
                <div style="width: 100%;">
                    <?php
                    include "assets/include/dbconnect.php";
                    $query = mysqli_query($conn,"SELECT * from product where active='0'") or die(mysqli_error($conn));
                    while ($row = mysqli_fetch_array($query)) {
                        $prid = $row['product_id'];
                        if (!isset($_GET["pr-$prid"])) {
                            continue;
                        }
                        $n = 1;
                        while ($n <= $_GET["qt-$prid"]) {
                        echo "<div style='padding:20px;width:20%;display:inline-block;'>";
                        echo "<div style='border:1px solid gray;padding:5px;'>";
                        $barcodeText = $row['coa'];
                        $barcodeType = "code128";
                        //codabar , code128 , code39
                        $barcodeDisplay = 'horizontal';
                        //horizontal, vertical
                        $barcodeSize = 40;
                        $printText = "false";
                    echo '<img class="barcode" alt="'.$barcodeText.'" src="barcode.php?text='.$barcodeText.'&codetype='.$barcodeType.'&orientation='.$barcodeDisplay.
                    '&size='.$barcodeSize.'&print='.$printText.'"/>';
                    echo "<br><b>".substr($row['coa'],5,4)."</b>";
                    echo "<br>".$row['product_name'];
                    echo "</div>";
                    echo "</div>";
                    $n++;
                    }
                    }
                    ?>

                </div>
                </div>

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