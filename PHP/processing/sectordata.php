<?php
  session_start();
  $ticker = $_SESSION['ticker'];

  error_reporting( 0 );

$connection = mysqli_connect('localhost','root','',etfcsv);
  if(!$connection){
    die("Could not connect ".mysql_error());
}

$sql = "\nSELECT * FROM $ticker WHERE type = 's'";
$result =$connection->query($sql);

$data = array();
while ($row = $result->fetch_assoc() ) {
  $data[] = $row;
}

echo json_encode($data);
$connection->close();
?>