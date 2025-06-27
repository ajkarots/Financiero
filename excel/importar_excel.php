<?php
// Archivo: excel/importar_excel.php
// Requiere: composer require phpoffice/phpspreadsheet

header('Content-Type: application/json');
require __DIR__ . '/../conexion.php';
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Crear directorio de uploads si no existe
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);

// Validar archivo subido
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status'=>'error','message'=>'Por favor sube un archivo Excel válido.']);
    exit;
}

// Guardar archivo
$tmp = $_FILES['file']['tmp_name'];
$name = basename($_FILES['file']['name']);
$path = UPLOAD_DIR . $name;
if (!move_uploaded_file($tmp, $path)) {
    echo json_encode(['status'=>'error','message'=>'Error al guardar el archivo.']);
    exit;
}

try {
    // Cargar Excel
    $spreadsheet = IOFactory::load($path);

    // Función helper para encontrar hoja por palabra clave
    function findSheet($spreadsheet, array $keywords) {
        foreach ($keywords as $key) {
            foreach ($spreadsheet->getSheetNames() as $name) {
                if (stripos($name, $key) !== false) {
                    return $spreadsheet->getSheetByName($name);
                }
            }
        }
        return null;
    }

    // Limpiar tablas antes de importar
    foreach (['produccion','costos','mano_obra','inversiones','flujo_caja','depreciacion'] as $tbl) {
        $con->query("TRUNCATE TABLE `$tbl`");
    }

    // 1) PRODUCCIÓN
    $sheet = findSheet($spreadsheet, ['PRODUCCIÓN','PRODUCCION']);
    if ($sheet) {
        $rowYear   = 5;  // fila con año (Excel row 5)
        $rowMonth  = 6;  // fila con meses (Excel row 6)
        $firstData = 7;  // primera fila de datos (Excel row 7)
        $maxRow = $sheet->getHighestRow();
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $mapping = [];
        $lastYear = null;
        for ($c = 3; $c <= $maxCol; $c++) {
            $col = Coordinate::stringFromColumnIndex($c);
            // Leer año (puede estar solo en la primera columna del rango fusionado)
            $yrVal = $sheet->getCell($col . $rowYear)->getValue();
            if (is_numeric($yrVal)) {
                $lastYear = (int)$yrVal;
            }
            // Leer mes
            $mesVal = $sheet->getCell($col . $rowMonth)->getValue();
            if (!empty($mesVal) && $lastYear !== null) {
                $mapping[$col] = ['anio' => $lastYear, 'mes' => $mesVal];
            }
        }
        // Preparar inserción
        $stmt = $con->prepare("INSERT INTO produccion (anio, mes, producto, unidades) VALUES (?, ?, ?, ?)");

        
        foreach ($mapping as $col => $info) {
            // no-op to ensure mapping loaded
        }
        for ($r = $firstData; $r <= $maxRow; $r++) {
            $prod = $sheet->getCell('B' . $r)->getValue();
            if (empty($prod)) continue;
            foreach ($mapping as $col => $info) {
                $val = $sheet->getCell($col . $r)->getValue();
                if (is_numeric($val)) {
                    $anioInt = $info['anio'];
                    $mesStr  = $info['mes'];
                    $uniInt  = (int)$val;
                    $stmt->bind_param('issi', $anioInt, $mesStr, $prod, $uniInt);
                    $stmt->execute();
                }
            }
        }
    }

    // 2) COSTOS
    $sheet = findSheet($spreadsheet, ['COSTOS','Costos Fijos','Costos y Gastos']);
    if ($sheet) {
        $stmtF = $con->prepare("INSERT INTO costos (tipo, descripcion, valor) VALUES ('fijo', ?, ?)");
        $stmtV = $con->prepare("INSERT INTO costos (tipo, descripcion, valor) VALUES ('variable', ?, ?)");
        for ($r = 4; $r <= $sheet->getHighestRow(); $r++) {
            $desc = trim($sheet->getCell('B' . $r)->getValue());
            $fv   = $sheet->getCell('C' . $r)->getValue();
            $vv   = $sheet->getCell('D' . $r)->getValue();
            if ($desc) {
                if (is_numeric($fv)) {
                    $valF = (float)$fv;
                    $stmtF->bind_param('sd', $desc, $valF);
                    $stmtF->execute();
                }
                if (is_numeric($vv)) {
                    $valV = (float)$vv;
                    $stmtV->bind_param('sd', $desc, $valV);
                    $stmtV->execute();
                }
            }
        }
    }

    // 3) MANO DE OBRA
    $sheet = findSheet($spreadsheet, ['MANO DE OBRA']);
    if ($sheet) {
        $stmt = $con->prepare("INSERT INTO mano_obra (cargo, salario, decimotercero, decimocuarto, anio) VALUES (?, ?, ?, ?, ?)");
        for ($r = 3; $r <= $sheet->getHighestRow(); $r++) {
            $cargo = $sheet->getCell('B' . $r)->getValue();
            $sal   = $sheet->getCell('C' . $r)->getValue();
            $d3    = $sheet->getCell('D' . $r)->getValue();
            $d4    = $sheet->getCell('E' . $r)->getValue();
            $yr    = $sheet->getCell('F' . $r)->getValue();
            if ($cargo && is_numeric($sal) && is_numeric($yr)) {
                $salVal = (float)$sal;
                $d3Val  = (float)$d3;
                $d4Val  = (float)$d4;
                $yrInt  = (int)$yr;
                $stmt->bind_param('sdddi', $cargo, $salVal, $d3Val, $d4Val, $yrInt);
                $stmt->execute();
            }
        }
    }

    // 4) INVERSIONES
    $sheet = findSheet($spreadsheet, ['INVERSIÓN','PRESUPUESTO DE INVERSIÓN']);
    if ($sheet) {
        $stmt = $con->prepare("INSERT INTO inversiones (descripcion, tipo, monto, anio) VALUES (?, ?, ?, ?)");
        for ($r = 3; $r <= $sheet->getHighestRow(); $r++) {
            $desc = $sheet->getCell('B' . $r)->getValue();
            $tip  = $sheet->getCell('C' . $r)->getValue();
            $mto  = $sheet->getCell('D' . $r)->getValue();
            $yr   = $sheet->getCell('E' . $r)->getValue();
            if ($desc && is_numeric($mto) && is_numeric($yr)) {
                $mtoVal = (float)$mto;
                $yrInt  = (int)$yr;
                $stmt->bind_param('ssdi', $desc, $tip, $mtoVal, $yrInt);
                $stmt->execute();
            }
        }
    }

    // 5) FLUJO DE CAJA
    $sheet = findSheet($spreadsheet, ['FLUJO DE CAJA']);
    if ($sheet) {
        $stmt = $con->prepare("INSERT INTO flujo_caja (anio, ingreso, egreso) VALUES (?, ?, ?)");
        for ($r = 3; $r <= $sheet->getHighestRow(); $r++) {
            $y = $sheet->getCell('B' . $r)->getValue();
            $i = $sheet->getCell('C' . $r)->getValue();
            $e = $sheet->getCell('D' . $r)->getValue();
            if (is_numeric($y)) {
                $yInt = (int)$y;
                $iVal = (float)$i;
                $eVal = (float)$e;
                $stmt->bind_param('idd', $yInt, $iVal, $eVal);
                $stmt->execute();
            }
        }
    }

    // 6) DEPRECIACIÓN
    $sheet = findSheet($spreadsheet, ['DEPRECIACIÓN']);
    if ($sheet) {
        $stmt = $con->prepare("INSERT INTO depreciacion (activo, costo_total, vida_util, valor_residual) VALUES (?, ?, ?, ?)");
        for ($r = 3; $r <= $sheet->getHighestRow(); $r++) {
            $act = $sheet->getCell('B' . $r)->getValue();
            $cst = $sheet->getCell('C' . $r)->getValue();
            $vu  = $sheet->getCell('D' . $r)->getValue();
            $vr  = $sheet->getCell('E' . $r)->getValue();
            if ($act && is_numeric($cst) && is_numeric($vu) && is_numeric($vr)) {
                $cstVal = (float)$cst;
                $vuInt  = (int)$vu;
                $vrInt  = (int)$vr;
                $stmt->bind_param('sdii', $act, $cstVal, $vuInt, $vrInt);
                $stmt->execute();
            }
        }
    }

    echo json_encode(['status'=>'ok','message'=>'Importación completada exitosamente.']);
} catch (Exception $ex) {
    echo json_encode(['status'=>'error','message'=>'Error: ' . $ex->getMessage()]);
}
?>
