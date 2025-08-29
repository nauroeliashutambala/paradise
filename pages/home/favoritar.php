<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Não autenticado']);
    exit;
}
// ... lógica para favoritar ...
echo json_encode(['status' => 'sucesso']);
?>