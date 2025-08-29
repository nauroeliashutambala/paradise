<?php
require_once("../../config/conexao.php");
header('Content-Type: application/json');

try {
    // Recebe e sanitiza os campos
    $nome = htmlspecialchars(substr(trim($_POST['nome'] ?? ''), 0, 100));
    $email = substr(trim($_POST['email'] ?? ''), 0, 100);
    $telefone = substr(trim($_POST['telefone'] ?? ''), 0, 20);
    $senha = trim($_POST['senha'] ?? '');
    $confirmar = trim($_POST['confirmar_senha'] ?? '');
    $tipo_conta = $_POST['tipo_conta'] ?? '';

    // Validação de campos obrigatórios
    if (empty($nome) || empty($email) || empty($telefone) || empty($senha) || empty($confirmar) || empty($tipo_conta)) {
        echo json_encode(["status" => "erro", "mensagem" => "Preencha todos os campos obrigatórios."]);
        exit;
    }

    // Ajuste para os valores aceitos no campo tipo
    $tipo_map = [
        'usuario' => 'cliente',
        'cliente' => 'cliente',
        'fornecedor' => 'fornecedor',
        'admin' => 'admin'
    ];
    if (!isset($tipo_map[$tipo_conta])) {
        echo json_encode(["status" => "erro", "mensagem" => "Tipo de conta inválido."]);
        exit;
    }
    $tipo = $tipo_map[$tipo_conta];

    if ($senha !== $confirmar) {
        echo json_encode(["status" => "erro", "mensagem" => "As senhas não coincidem"]);
        exit;
    }

    if (strlen($senha) < 6) {
        echo json_encode(["status" => "erro", "mensagem" => "A senha deve ter pelo menos 6 caracteres."]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "erro", "mensagem" => "E-mail inválido"]);
        exit;
    }

    if (!preg_match('/^(\+244)?\d{9}$/', $telefone)) {
        echo json_encode(["status" => "erro", "mensagem" => "Telefone inválido. Use o formato +244XXXXXXXXX ou XXXXXXXXX."]);
        exit;
    }

    // Verifica se email já existe
    $stmt = $mysqli->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "erro", "mensagem" => "E-mail já cadastrado"]);
        $stmt->close();
        $mysqli->close();
        exit;
    }
    $stmt->close();

    // Insere no banco
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("INSERT INTO usuarios (nome, email, senha_hash, telefone, tipo) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nome, $email, $senha_hash, $telefone, $tipo);

    if ($stmt->execute()) {
        echo json_encode(["status" => "sucesso"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao cadastrar: " . $stmt->error]);
    }

    $stmt->close();
    $mysqli->close();
} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro inesperado no servidor."]);
}
?>
