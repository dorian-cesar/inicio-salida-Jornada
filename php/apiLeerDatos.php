<?php

// Permitir solicitudes desde cualquier origen
header("Access-Control-Allow-Origin: *");

// Permitir los métodos de solicitud que se utilizarán
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Permitir ciertos encabezados en las solicitudes preflight OPTIONS, incluido Content-Type
header("Access-Control-Allow-Headers: Content-Type");

// Conexión a la base de datos

$servername = "ls-8ce02ad0b7ea586d393e375c25caa3488acb80a5.cylsiewx0zgx.us-east-1.rds.amazonaws.com";
$username = "dbmasteruser";
$password = ':&T``E~r:r!$1c6d:m143lzzvGJ$NuP;';
$dbname = "interurbano";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Parámetro rut (si se proporciona)
$rut_filter = isset($_GET['rut']) ? $_GET['rut'] : '';

// Consulta SQL para obtener los registros
$sql = "SELECT id, rut, tipo, timestamp, metodo, patente FROM registros";
if ($rut_filter != '') {
    $sql .= " WHERE rut = '$rut_filter'";
}
$sql .= " ORDER BY timestamp";

$result = $conn->query($sql);

// Crear un array con los resultados
$rows = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

// Devolver los resultados como JSON
header('Content-Type: application/json');
echo json_encode($rows);

// Cerrar la conexión a la base de datos
$conn->close();
?>