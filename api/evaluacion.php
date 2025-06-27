<?php
require '../conexion.php';
header('Content-Type: application/json');

function calcularVAN(array $flujos, float $tasa): float {
    $van = 0.0;
    foreach ($flujos as $i => $f) {
        $neto = $f['ingreso'] - $f['egreso'];
        $van += $neto / pow(1 + $tasa, $i);
    }
    return round($van, 2);
}

function calcularPRI(array $flujos): int {
    $acum = 0.0;
    foreach ($flujos as $i => $f) {
        $acum += ($f['ingreso'] - $f['egreso']);
        if ($acum >= 0) {
            return $i;
        }
    }
    return count($flujos);
}

function calcularTIR(array $flujos, float $guess = 0.1, int $maxIter = 100, float $tol = 1e-6): ?float {
    $r = $guess;
    for ($iter = 0; $iter < $maxIter; $iter++) {
        $npv = 0.0;
        $der = 0.0;
        foreach ($flujos as $n => $f) {
            $neto = $f['ingreso'] - $f['egreso'];
            $den  = pow(1 + $r, $n);
            $npv += $neto / $den;
            $der -= $n * $neto / ($den * (1 + $r));
        }
        if (abs($npv) < $tol) {
            return round($r, 5);
        }
        if (abs($der) < 1e-12) {
            break;
        }
        $r -= $npv / $der;
    }
    return null;
}

// Obtener flujos de caja
$res = $con->query("SELECT anio, ingreso, egreso FROM flujo_caja ORDER BY anio");
$flujos = $res->fetch_all(MYSQLI_ASSOC);

// Tasa de descuento fija al 10%
$tasa = 0.10;

$van = calcularVAN($flujos, $tasa);
$pri = calcularPRI($flujos);
$tir = calcularTIR($flujos, $tasa);

echo json_encode([
    'VAN'    => $van,
    'TIR'    => $tir !== null ? $tir : 0.0,
    'PRI'    => $pri,
    'TASA'   => $tasa,
    'errorTIR' => $tir === null ? 'No converge' : null
]);
?>