<?php
if (!isset($_GET["id"])) {
	echo "Kindly Select a Product";
}
else{
	$id = $_GET['id'];
	include 'assets/include/dbconnect.php';
	$query = mysqli_query($conn,"SELECT * from livestock_animal where id_pk='$id'");
	$row=mysqli_fetch_array($query);
	$barcodeText = $row['tag_code'];
	$barcodeType = "code128";
	//codabar , code128 , code39
	$barcodeDisplay = 'horizontal';
	//horizontal, vertical
	$barcodeSize = 40;
	$printText = "false";

	echo '<h4>Barcode:</h4>';
echo '<img class="barcode" alt="'.$barcodeText.'" src="barcode.php?text='.$barcodeText.'&codetype='.$barcodeType.'&orientation='.$barcodeDisplay.
'&size='.$barcodeSize.'&print='.$printText.'"/>';
echo "<br>&nbsp&nbsp".$row['tag_code'];
echo "<br>&nbsp&nbsp".$row['weight']." KG";
}

?>