<?php
require_once("../../config/conexao.php");
session_start();
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['id_fornecedor'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Não autorizado']);
    exit;
}

$id_usuario = $_SESSION['id_usuario'] ?? null;
$id_fornecedor = $_SESSION['id_fornecedor'] ?? null;
$tipo_usuario = $_SESSION['tipo'] ?? 'usuario'; // 'usuario' ou 'fornecedor'
$id_chat = isset($_GET['id_chat']) ? intval($_GET['id_chat']) : 1;

// Verifica se o chat pertence ao usuário ou fornecedor
$verifica = $mysqli->prepare("SELECT 1 FROM chats WHERE id_chat = ? AND (id_usuario = ? OR id_fornecedor = ?)");
$verifica->bind_param("iii", $id_chat, $id_usuario, $id_fornecedor);
$verifica->execute();
$verifica->store_result();

if ($verifica->num_rows === 0) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Acesso negado']);
    $verifica->close();
    $mysqli->close();
    exit;
}
$verifica->close();

// Busca as mensagens do chat
$sql = "SELECT remetente, texto, DATE_FORMAT(hora, '%H:%i') as hora 
        FROM chat_mensagens 
        WHERE id_chat = ? 
        ORDER BY hora ASC";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id_chat);
$stmt->execute();
$result = $stmt->get_result();

$mensagens = [];
while ($row = $result->fetch_assoc()) {
    $mensagens[] = [
        'tipo' => $row['remetente'] === $tipo_usuario ? 'sent' : 'received',
        'texto' => htmlspecialchars($row['texto']),
        'hora' => $row['hora']
    ];
}

echo json_encode($mensagens);
$stmt->close();
$mysqli->close();
?>