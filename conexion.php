<?php
$servername = "metro.proxy.rlwy.net:37002";
$username = "root";
$password = "TgLfsXmJIImdpGcCfzZUGQEqqOPBNVAJ";
$dbname = "financiero";
$con= new mysqli($servername,$username,$password, $dbname);
if($con->connect_error){
die("Fallo en la conexión: " . $con->connect_error);
}    
?>