<?php
session_start();
if (isset($_GET['changepassword'])) {
	$p = md5($_GET['changepassword']);
	$newp = md5($_GET['newp']);
	include 'dbconnect.php';
	$query = mysqli_query($conn,"SELECT id_pk from login where id_pk='".$_SESSION['admin-login']."' and password='$p' ") or die(mysqli_error($conn));
	if (mysqli_num_rows($query)>0) {
		mysqli_query($conn,"UPDATE login set password='$newp' where id_pk='".$_SESSION['admin-login']."'") or die(mysqli_error($conn));
		echo "<label class='alert alert-success in alert-dismissible' style='margin:auto;' ><a href='#' class='close' data-dismiss='alert' aria-label='close' title='close'>×</a><strong>password Changed Successfully!</strong></label>";
	}
	else{
		echo "<label class='alert alert-danger in alert-dismissible' style='margin:auto;'><a href='#' class='close' data-dismiss='alert' aria-label='close' title='close'>×</a><strong>Incorrect Old Password!</strong> Try Again</label>";
	}
}
?>