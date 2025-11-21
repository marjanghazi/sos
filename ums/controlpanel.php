<?php
if (!isset($_SESSION['period'])) {
    $_SESSION['period'] = date("M Y");
}
if (isset($_GET['period'])) {
    $_SESSION['period'] = $_GET["period"];
    header("location:index.php");
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

    <title>DASHBOARD - UMS</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/dashboard.png">

    <!-- Custom fonts for this template-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/js/standalone/selectize.min.js" integrity="sha256-+C0A5Ilqmu4QcSPxrlGpaZxJ04VjsRjKu+G82kl5UJk=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/css/selectize.bootstrap3.min.css" integrity="sha256-ze/OEYGcFbPRmvCnrSeKbRTtjG4vGLHXgOqsyLFTRjg=" crossorigin="anonymous" />
    <script src="https://cdn.canvasjs.com/ga/canvasjs.min.js"></script>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script src="https://cdn.canvasjs.com/ga/canvasjs.stock.min.js"></script>
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.css" rel="stylesheet">
    <style>
        .grad-nvb {
            background-image: linear-gradient(180deg, rgba(1, 47, 95, 1) -0.4%, rgba(56, 141, 217, 1) 106.1%);
            color: white;
        }
    </style>
    <script>
        function perchnage(argument) {
            window.open(window.location.href + "?period=" + argument, "_self");
        }
    </script>
</head>

<body id="page-top sidebar-toggled" style="background-color:#F6F6F9!important;">

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

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Overview</h1>
                        <select style="width:15%;" class="form-control" class="js-example-basic-single d-sm-inline-block" onchange="perchnage(this.value);">
                            <?php
                            $n = 0;
                            while ($n < 15) {
                                echo "<option ";
                                if ($_SESSION['period'] == date("M Y", strtotime(date("1-M-Y") . "-$n Months"))) {
                                    echo "SELECTED";
                                }
                                echo " >" . date("M Y", strtotime(date("1-M-Y") . "-$n Months")) . "</option>";
                                $n++;
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Content Row -->
                    <div class="row">


                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card  shadow-sm h-100 py-2 grad-nvb">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-s font-weight-bold text-uppercase mb-1" id='dopen1'>
                                                Total Bills</div>
                                            <div class="fas fa-inventory fa-2x">
                                                <?php
                                                include 'assets/include/dbconnect.php';
                                                $sql = "SELECT COUNT(id_pk) FROM ums_bill where billing_month = '" . $_SESSION['period'] . "'";
                                                $result = mysqli_query($conn, $sql) or die(mysqli_errors());
                                                $row = mysqli_fetch_array($result);
                                                $temp = $row[0];
                                                ?>
                                                <h2 class=""><strong><?php echo number_format($row[0], 0); ?></strong></h2>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-inventory fa-2x">
                                            </i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card  shadow-sm h-100 py-2 grad-nvb">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-s font-weight-bold text-uppercase mb-1" id='dopen1'>
                                                Total Units</div>
                                            <div class="fas fa-inventory fa-2x">
                                                <?php
                                                include 'assets/include/dbconnect.php';
                                                $sql = "SELECT SUM(unit_consumed) FROM ums_bill where billing_month = '" . $_SESSION['period'] . "'";
                                                $result = mysqli_query($conn, $sql) or die(mysqli_errors());
                                                $row = mysqli_fetch_array($result);
                                                $temp = $row[0];
                                                ?>
                                                <h2 class=""><strong><?php echo number_format($row[0], 0); ?></strong></h2>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-inventory fa-2x">
                                            </i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card  shadow-sm h-100 py-2 grad-nvb">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-s font-weight-bold text-uppercase mb-1" id='dopen1'>
                                                Total Expense</div>
                                            <div class="fas fa-inventory fa-2x">
                                                <?php
                                                include 'assets/include/dbconnect.php';
                                                $sql = "SELECT SUM(total_amount) FROM ums_bill where billing_month = '" . $_SESSION['period'] . "'";
                                                $result = mysqli_query($conn, $sql) or die(mysqli_errors());
                                                $row = mysqli_fetch_array($result);
                                                $temp = $row[0];
                                                ?>
                                                <h2 class=""><strong><?php echo number_format($row[0], 0); ?> PKR</strong></h2>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-inventory fa-2x">
                                            </i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card  shadow-sm h-100 py-2 grad-nvb">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-s font-weight-bold text-uppercase mb-1" id='dopen1'>
                                                Expense Per Unit</div>
                                            <div class="fas fa-inventory fa-2x">
                                                <?php
                                                include 'assets/include/dbconnect.php';
                                                $sql = "SELECT SUM(total_amount)/SUM(unit_consumed) FROM ums_bill where billing_month = '" . $_SESSION['period'] . "'";
                                                $result = mysqli_query($conn, $sql) or die(mysqli_errors());
                                                $row = mysqli_fetch_array($result);
                                                $temp = $row[0];
                                                ?>
                                                <h2 class=""><strong><?php echo number_format($row[0], 2); ?> PKR</strong></h2>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-inventory fa-2x">
                                            </i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <br>

                    <div class="row">
                        <div class="col-sm-6">
                            <div id="chartContainerservice" style="height: 370px; width: 100%;"></div>
                        </div>
                        <div class="col-sm-6">
                            <div id="chartContainerserviceg" style="height: 370px; width: 100%;"></div>
                        </div>
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

            <!-- Page level plugins -->
            <script src="vendor/chart.js/Chart.min.js"></script>

            <!-- Page level custom scripts -->
            <script src="js/demo/chart-area-demo.js"></script>
            <script src="js/demo/chart-pie-demo.js"></script>



            <!-- Page level plugins -->
            <script src="vendor/datatables/jquery.dataTables.min.js"></script>
            <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

            <!-- Page level custom scripts -->
            <script>
                $(document).ready(function() {
                    $('#dataTable').DataTable({
                        "pageLength": 5
                    });
                });
            </script>
</body>
<script>
    window.onload = function() {

        var chartservice = new CanvasJS.Chart("chartContainerservice", {
            animationEnabled: true,
            theme: "light2", // "light1", "light2", "dark1", "dark2"
            title: {
                text: "Highest Expense per unit Offices"
            },
            axisY: {
                title: "Expense Per Unit"
            },
            data: [{
                type: "column",
                <?php
                $query = mysqli_query($conn, "SELECT office,SUM(total_amount)/SUM(unit_consumed) FROM ums_bill where billing_month = '" . $_SESSION['period'] . "' and office != 0 group by office ORDER BY SUM(total_amount)/SUM(unit_consumed) DESC") or die(mysqli_error($conn));
                $n = 1;
                ?>
                dataPoints: [
                    <?php
                    while ($n < 6) {
                        $row = mysqli_fetch_array($query);
                        $query2 = mysqli_query($conn, "SELECT office_name FROM ums_offices where id_pk='" . $row['office'] . "'") or die(mysqli_error($conn));
                        $row2 = mysqli_fetch_array($query2);
                        if ($n == 1) {
                            echo "{ label: '" . $row2["office_name"] . "', y: " . number_format($row[1], 2) . " }";
                        } else {
                            echo ",{ label: '" . $row2["office_name"] . "', y: " . number_format($row[1], 2) . " }";
                        }
                        $n++;
                    }
                    ?>
                ]
            }]
        });
        chartservice.render();


        var chartContainerserviceg = new CanvasJS.Chart("chartContainerserviceg", {
            animationEnabled: true,
            title: {
                text: "Phase Wise unit Consumption",
                horizontalAlign: "center"
            },
            data: [{
                type: "doughnut",
                startAngle: 60,
                //innerRadius: 60,
                indexLabelFontSize: 17,
                indexLabel: "{label} - {y} units",
                toolTipContent: "<b>{label}:</b> {y}",
                dataPoints: [
                    <?php
                    $query2 = mysqli_query($conn, "SELECT type,sum(unit_consumed) FROM ums_bill where billing_month = '" . $_SESSION['period'] . "' group by type") or die(mysqli_error($conn));
                    while ($row2 = mysqli_fetch_array($query2)) {
                        echo "{ y: " . $row2[1] . ", label: '" . $row2['type'] . "' },";
                    }
                    ?>
                ]
            }]
        });
        chartContainerserviceg.render();

    }
</script>

</html>