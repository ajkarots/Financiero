<?php
// Archivo: api/produccion.php
header('Content-Type: application/json');
require __DIR__ . '/../conexion.php';

// POST: insertar un registro de producción
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anio     = isset($_POST['anio'])     ? (int) $_POST['anio']     : null;
    $mes      = isset($_POST['mes'])      ? $_POST['mes']            : null;
    $producto = isset($_POST['producto']) ? $_POST['producto']       : null;
    $unidades = isset($_POST['unidades']) ? (int) $_POST['unidades']  : null;

    if ($anio !== null && $mes !== null && $producto !== null && $unidades !== null) {
        $stmt = $con->prepare(
            "INSERT INTO produccion (anio, mes, producto, unidades) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('issi', $anio, $mes, $producto, $unidades);
        $stmt->execute();
        echo json_encode(['status' => 'ok']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    }
    exit;
}

// GET: años o datos de producción
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Sin parámetro 'anio': devolver lista de años
    if (!isset($_GET['anio'])) {
        $resYears = $con->query("SELECT *FROM produccion ORDER BY anio");
        if (!$resYears) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al obtener años: ' . $con->error]);
            exit;
        }
        $years = $resYears->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode($years);
        exit;
    }
    
    // Con parámetro 'anio': devolver registros mensuales
    $anio = (int) $_GET['anio'];
    $monthsOrder = "FIELD(mes,'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre')";
    $sql = "SELECT * FROM produccion WHERE anio = $anio ORDER BY $monthsOrder";
    $res = $con->query($sql);
    if (!$res) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error en consulta: ' . $con->error]);
        exit;
    }
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    exit;
}

// Métodos no soportados
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
?>
