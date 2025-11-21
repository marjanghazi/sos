<?php
session_start();
if (isset($_GET['breed'])) {
	include 'assets/include/dbconnect.php';
	$result = mysqli_query($conn,"SELECT * from livestock_animal_breed where animal_type='".$_GET['breed']."'") or die(mysqli_error($conn));
    while ($row = mysqli_fetch_array($result)) {
        echo "<option value='".$row['id_pk']."'>".$row['breed_name']."</option>";
    }
}

if (isset($_GET['breedf'])) {
    include 'assets/include/dbconnect.php';
    $result = mysqli_query($conn,"SELECT * from livestock_animal_breed where animal_type='".$_GET['breedf']."'") or die(mysqli_error($conn));
    echo "<option selected>All</option>";
    while ($row = mysqli_fetch_array($result)) {
        echo "<option value='".$row['id_pk']."'>".$row['breed_name']."</option>";
    }
}

if (isset($_GET['anmlpic'])) {
    include 'assets/include/dbconnect.php';
    $result = mysqli_query($conn,"SELECT image_1,manual_code,animal_type,breed from livestock_animal where id_pk='".$_GET['anmlpic']."'") or die(mysqli_error($conn));
    $row = mysqli_fetch_array($result);
    echo "assets/images/".$row['image_1'];
    echo "|";
    echo $row['manual_code'];
    echo "|";
    $query2 = mysqli_query($conn,"SELECT type_name from livestock_animal_type where id_pk='".$row['animal_type']."'") or die(mysqli_error($conn));
    $row2 = mysqli_fetch_array($query2);
    echo $row2['type_name'];
    echo "|";
    $query2 = mysqli_query($conn,"SELECT breed_name from livestock_animal_breed where id_pk='".$row['breed']."'") or die(mysqli_error($conn));
    $row2 = mysqli_fetch_array($query2);
    echo $row2['breed_name'];
}

if (isset($_GET['anmldtl'])) {
    include 'assets/include/dbconnect.php';
    $result = mysqli_query($conn,"SELECT * from livestock_animal where id_pk='".$_GET['anmldtl']."'") or die(mysqli_error($conn));
    $row = mysqli_fetch_array($result);
    echo "assets/images/".$row['image_1'];
    echo "|";
    if ($row['status'] == 0) {
        echo "<span class='lbl bg-success'>Alive</span>";
    }
    elseif($row['status'] == 1){
        echo "<span class='lbl bg-primary'>Sold</span>";
    }
    else{
        echo "<span class='lbl bg-danger'>Dead</span>";
    }
    echo "|";
    $query2 = mysqli_query($conn,"SELECT type_name from livestock_animal_type where id_pk='".$row['animal_type']."'") or die(mysqli_error($conn));
    $row2 = mysqli_fetch_array($query2);
    echo $row2['type_name'];
    echo "|";
    $query2 = mysqli_query($conn,"SELECT breed_name from livestock_animal_breed where id_pk='".$row['breed']."'") or die(mysqli_error($conn));
    $row2 = mysqli_fetch_array($query2);
    echo $row2['breed_name'];
    echo "|";
    echo date("d-M-Y",strtotime($row['purchase_date']));
    echo "|";
    echo $row['tag_code'];
    echo "|";
    if ($row['gender'] == 0) {
        echo "Male";
    }
    else{
        echo "Female";
    }
    echo "|";
    echo $row['age']." Months" ;
    echo "|";
    $date1 = strtotime($row['purchase_date']);
    $date2 = strtotime("now");
    $diff = $date2 - $date1;
    $days = floor($diff / (60 * 60 * 24));
    $days = intval($days/30);
    echo ($row['age']+$days)." Months" ;
    echo "|";
    echo number_format($row['price']);
    echo "|";
    echo number_format($row['freight']);
    $query = mysqli_query($conn,"SELECT sum(expense) from livestock_animal_vaccination where animal_id = '".$_GET['anmldtl']."' order by vaccination_date desc") or die(mysqli_error($conn));
    $sumv = 0;
    $row3 = mysqli_fetch_array($query);
    $sumv = $sumv + $row3[0];
    $query = mysqli_query($conn,"SELECT sum(expense) from livestock_animal_expense where animal_id = '".$_GET['anmldtl']."' order by expense_date desc") or die(mysqli_error($conn));
    $row3 = mysqli_fetch_array($query);
    $sumv = $sumv + $row3[0];
    echo "|";
    echo number_format($sumv);
    if ($row['status'] == 1) {
        $query = mysqli_query($conn,"SELECT sale_price,sale_date from livestock_sale where animal_id = '".$_GET['anmldtl']."'") or die(mysqli_error($conn));
        $row3 = mysqli_fetch_array($query);
        echo "|";
        echo number_format($row3['sale_price']);
        echo "|";
        echo date("d-M-Y",strtotime($row3['sale_date']));
        $profit = $row3['sale_price']-($row['price']+$row['freight']+$sumv);
    }else{
        echo "|";
        echo '0';
        echo "|";
        echo "";
        $profit = 0;
    }
    echo "|";
    if ($profit > 0) {
        echo "<label class='text-success text-center'>".number_format($profit)."/-</label>";
    } 
    else{
        echo "<label class='text-danger text-center'>".number_format($profit)."/-</label>";
    }
}

?>