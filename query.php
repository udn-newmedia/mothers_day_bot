<?php
  $servername = 'localhost';
  $username = 'newmedia';
  $password = 'newmedia';
  $dbname = 'mothers_day_bot';
  $conn = new mysqli($servername, $username, $password, $dbname);
  mysqli_query($conn, "SET NAMES UTF8");

  if($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
  }

  $query = "SELECT id FROM cards WHERE user_id=2583760508306253";
  $result = mysqli_query($conn, $query);

  $row = mysqli_fetch_array($result);
  echo $row['id'];

  if (mysqli_num_rows($result) > 0) {
    echo 1;
  } else {
    echo 0;
  }

  $conn->close();