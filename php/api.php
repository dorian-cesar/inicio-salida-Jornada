<?php
// Permitir solicitudes desde cualquier origen
header("Access-Control-Allow-Origin: *");

// Permitir los métodos de solicitud que se utilizarán
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Permitir ciertos encabezados en las solicitudes preflight OPTIONS, incluido Content-Type
header("Access-Control-Allow-Headers: Content-Type");

$servername = "ls-8ce02ad0b7ea586d393e375c25caa3488acb80a5.cylsiewx0zgx.us-east-1.rds.amazonaws.com";
$username = "dbmasteruser";
$password = ':&T``E~r:r!$1c6d:m143lzzvGJ$NuP;';
$dbname = "interurbano";


// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function checkRestrictions($rut, $tipo, $conn) {
    // Verificar la última entrada o salida del RUT
    $sql = "SELECT tipo, timestamp FROM registros WHERE rut = ? ORDER BY timestamp DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $stmt->bind_result($lastTipo, $lastTimestamp);
    $stmt->fetch();
    $stmt->close();

    // Si hay un último registro, aplicar restricciones
    if ($lastTimestamp) {
        $lastTimestamp = new DateTime($lastTimestamp);
        $now = new DateTime();

        // Restricción 1: No más de 12 horas entre una entrada y una salida
        if ($tipo == 'salida' && $lastTipo == 'entrada') {
            $interval = $lastTimestamp->diff($now);
            if ($interval->h > 12 || ($interval->d * 24 + $interval->h) > 12) {
                return ["error" => "No puede haber más de 12 horas entre una entrada y una salida."];
            }
        }

        // Restricción 2: No dos entradas o salidas consecutivas
        if ($lastTipo == $tipo) {
            return [
                "consecutive" => true,
                "message" => "No puede haber dos " . $tipo . " consecutivas. ¿Desea registrar la " . ($tipo == 'entrada' ? 'salida' : 'entrada') . " automáticamente?",
                "missingTipo" => $tipo == 'entrada' ? 'salida' : 'entrada'
                
            ];
        }
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rut = $_POST['rut'];
    $tipo = $_POST['tipo'];
    $pat = $_POST['pat'];
    $force = isset($_POST['force']) ? $_POST['force'] : false;

    // Verificar restricciones
    $error = checkRestrictions($rut, $tipo, $conn);
    if ($error) {
        if (isset($error['consecutive']) && $force) {
            // Registrar la entrada o salida faltante
            $missingTipo = $error['missingTipo'];
            $sql = "INSERT INTO registros (rut, tipo, timestamp, metodo,patente) VALUES (?, ?, NOW() - INTERVAL 1 SECOND, 'forzado',?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $rut, $missingTipo,$pat);
            $stmt->execute();
            $stmt->close();
        } else {
            echo json_encode($error);
            $conn->close();
            exit();
        }
    }

    // Insertar el registro actual
    $metodo = 'manual';
    $sql = "INSERT INTO registros (rut, tipo, timestamp, metodo) VALUES (?, ?, NOW(), ?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $rut, $tipo, $metodo,$pat);
    if ($stmt->execute()) {
        echo json_encode(["success" => "Registro añadido correctamente."]);
    } else {
        echo json_encode(["error" => "Error al añadir el registro."]);
    }
    $stmt->close();
}

$conn->close();
?>
