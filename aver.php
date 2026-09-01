<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');
echo "Hora actual del servidor (Argentina): " . date('d/m/Y H:i');
echo "<br>";
echo "Timestamp actual: " . time();
echo "<br>";
echo "Fecha desde timestamp: " . date('d/m/Y H:i', time());
?>