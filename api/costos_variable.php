<?php
require '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = 'variable';
    $descripcion = $_POST['descripcion'];
    $valor = $_POST['valor'];

    $sql = "INSERT INTO costos (tipo, descripcion, valor) VALUES (?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ssd", $tipo, $descripcion, $valor);
    $stmt->execute();
    echo json_encode(["status" => "ok"]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $con->query("SELECT * FROM costos WHERE tipo = 'variable'");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}
?>
