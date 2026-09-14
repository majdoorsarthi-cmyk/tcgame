<?php
$host = "mysql-9d9cc53-majdoorsarthi-d1a8.k.aivencloud.com";
$port = 13848;
$username = "avnadmin";
$password = "AVNS_CXh977fYw0GUSdyTCUU";
$dbname = "tcgame"; // updated to tcgame database

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

if (!@mysqli_real_connect($conn, $host, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
