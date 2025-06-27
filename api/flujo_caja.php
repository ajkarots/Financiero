<?php
require '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anio = $_POST['anio'];
    $ingreso = $_POST['ingreso'];
    $egreso = $_POST['egreso'];

    $sql = "INSERT INTO flujo_caja (anio, ingreso, egreso) VALUES (?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("idd", $anio, $ingreso, $egreso);
    $stmt->execute();
    echo json_encode(["status" => "ok"]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $con->query("SELECT * FROM flujo_caja ORDER BY anio");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}
?>
