<?php
$conexion = new mysqli("localhost", "root", "", "nombre_de_tu_bd");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$sql = "SELECT nombre, correo FROM usuarios";
$resultado = $conexion->query($sql);

echo "<table class='tabla'>";
echo "<tr><th>Nombre</th><th>Correo</th></tr>";

if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($fila["nombre"]) . "</td><td>" . htmlspecialchars($fila["correo"]) . "</td></tr>";
    }
} else {
    echo "<tr><td colspan='2'>No hay usuarios registrados</td></tr>";
}

echo "</table>";
$conexion->close();
?>
