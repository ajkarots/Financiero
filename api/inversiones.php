<?php
require '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descripcion = $_POST['descripcion'];
    $tipo = $_POST['tipo'];
    $monto = $_POST['monto'];
    $anio = $_POST['anio'];

    $sql = "INSERT INTO inversiones (descripcion, tipo, monto, anio) VALUES (?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ssdi", $descripcion, $tipo, $monto, $anio);
    $stmt->execute();
    echo json_encode(["status" => "ok"]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $con->query("SELECT * FROM inversiones ORDER BY anio");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}
?>
