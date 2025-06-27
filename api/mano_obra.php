<?php
require '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cargo = $_POST['cargo'];
    $salario = $_POST['salario'];
    $anio = $_POST['anio'];

    $decimotercero = $salario / 12;
    $decimocuarto = 450 / 12; // valor fijo en Ecuador

    $sql = "INSERT INTO mano_obra (cargo, salario, decimotercero, decimocuarto, anio)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("sdddi", $cargo, $salario, $decimotercero, $decimocuarto, $anio);
    $stmt->execute();
    echo json_encode(["status" => "ok"]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $con->query("SELECT * FROM mano_obra ORDER BY anio");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}
?>
