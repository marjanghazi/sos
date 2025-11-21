<?php
session_start();
if (!isset($_SESSION['admin-login'])) {
    echo "access Denied. <a href='index.php' >Go Back</a>";
    die();
}
if (isset($_POST['setting-submit'])) {
    $pname = $_POST['pname'];
    $add = $_POST['caddress'];
    $contact = $_POST['contact'];
    if ($_FILES['img']['size']!= 0) {  
    $img_name = $_FILES['img']['name'];
    $img_temp = $_FILES['img']['tmp_name'];
    move_uploaded_file($img_temp,"assets/Images/$img_name");
}
include 'assets/include/dbconnect.php';
mysqli_query($conn,"UPDATE livestock_companyinfo set company_name='$pname',logo='$img_name',contact = '$contact',address='$add' where id_pk='1'") or die(mysqli_error($conn));
$_SESSION['success']='1';
header("location:setting.php");
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

    <title>SETTINGS - ZBS</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/dashboard.png">
    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.css" rel="stylesheet">

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
                                    <strong>Success!</strong> The Setting Has Been Updated.
                                </div>
                                    <?php
                                }
                                include 'assets/include/dbconnect.php';
                                $result = mysqli_query($conn,"SELECT * from livestock_companyinfo where active='0'") or die(mysqli_error($conn));
                                $row = mysqli_fetch_array($result);
                                ?>
                                
                                <h1 class="h4 text-gray-900 mb-4">SETTINGS</h1>
                            </div>
                            <?php 

                             ?>
                            <form class="user" method="post" action="" enctype="multipart/form-data" >
                                <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <label>Company Name</label>
                                        <input type="text" class="form-control form-control-user" name="pname" placeholder="Company Name" value="<?=$row['company_name'];?>" required autofocus>
                                    </div>
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <label>Company Logo(optional)</label>
                                        <?php
                                        if($row['logo'] == ''){
                                            $logo = 'noimage.png';
                                        }
                                        else{
                                            $logo = $row['logo'];
                                        }
                                        ?>
                                        <img style="width:9rem" src="/assets/images/<?=$logo?>" >
                                        <input type="file" class="custom-file" name='img'>
                                    </div>

                                    <div class="col-sm-6">
                                        <label>Company Address (Printed On Invoice)</label>
                                        <input type="text" class="form-control form-control-user" name="caddress" placeholder="Company Address" value="<?=$row['address'];?>" required>
                                        
                                    </div>
                                    <div class="col-sm-6">
                                        <label>Company Contact (Printed On Invoice)</label>
                                        <input type="text" class="form-control form-control-user" name="contact" placeholder="Company Contact" value="<?=$row['contact'];?>" required>
                                        
                                    </div>
                                </div>
                                <input type='submit'  class="btn btn-primary btn-user" name="setting-submit" value="Save Changes">
                                
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