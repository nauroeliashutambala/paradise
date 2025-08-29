<?php
// filepath: c:\xampp\htdocs\Casamentos\pages\login\login_action.php
require_once("../../config/conexao.php");
session_start();
header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['password'] ?? '');

if (empty($email) || empty($senha)) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id_usuario, nome, email, senha_hash, tipo FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($senha, $user['senha_hash'])) {
        // Define variáveis de sessão
        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['nome'] = $user['nome'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['tipo'] = $user['tipo'];
        // Se for fornecedor, pode adicionar $_SESSION['id_fornecedor'] = $user['id_usuario'];
        if ($user['tipo'] === 'fornecedor') {
            $_SESSION['id_fornecedor'] = $user['id_usuario'];
        }
        echo json_encode(['success' => true, 'message' => 'Login realizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Senha incorreta.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'E-mail não encontrado.']);
}

$stmt->close();
$mysqli->close();
?>
