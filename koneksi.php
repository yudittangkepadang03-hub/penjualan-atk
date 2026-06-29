<?php
$host = "mysql.railway.internal";
$user = "root";
$pass = "dDQNKaSWhvYmayFgjRRMaaNzhFdETCqA";
$db   = "railway";
$port = 3306;

$conn = mysqli_connect($host, $user, $pass, $db, $port);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>