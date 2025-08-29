<?php
require_once("../../config/conexao.php");
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$texto = htmlspecialchars(trim($data['texto'] ?? ''));
$id_chat = isset($data['id_chat']) ? intval($data['id_chat']) : 1;
$remetente = $_SESSION['tipo'] ?? 'usuario'; // 'usuario' ou 'fornecedor'

if (empty($texto)) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Mensagem vazia']);
    exit;
}

$stmt = $mysqli->prepare("INSERT INTO chat_mensagens (remetente, texto, id_chat) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $remetente, $texto, $id_chat);

if ($stmt->execute()) {
    echo json_encode(['status' => 'sucesso']);
} else {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao enviar mensagem']);
}

$stmt->close();
$mysqli->close();
?>