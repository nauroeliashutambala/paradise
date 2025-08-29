<?php
/**
 * pages/chat-list/index.php
 * Controller do chat: carrega lista de chats, mensagens do chat selecionado
 * e processa envio de novas mensagens.
 *
 * DEPENDE de:
 *   - ../../config/conexao.php  -> deve definir $mysqli (MySQLi)
 *   - Sessão com $_SESSION['id_usuario'] e (opcional) $_SESSION['tipo']
 *
 * Tabelas (conforme dump):
 *   chats(id, usuario_id, fornecedor_id, criado_em)
 *   chat_mensagens(id, remetente ENUM('usuario','fornecedor'), texto, hora, id_chat)
 *   usuarios(id_usuario, nome, email, senha_hash, telefone, tipo)
 */

require_once("../../config/conexao.php");
session_start();

/* --------------------------
   1) Autenticação básica
--------------------------- */
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    die("Não autenticado.");
}
$userId  = (int) $_SESSION['id_usuario'];
$userTipo = $_SESSION['tipo'] ?? null; // 'cliente' | 'fornecedor' | 'admin' (opcional)

/* --------------------------
   2) Helpers e estado
--------------------------- */
$erro        = null;
$chats       = [];
$mensagens   = [];
$chatAtualId = isset($_GET['chat_id']) ? (int) $_GET['chat_id'] : 0;

/**
 * Dispara erro amigável e interrompe.
 */
function fail($msg) {
    global $erro;
    $erro = $msg;
    // Em produção, poderias logar aqui.
}

/* --------------------------
   3) Envio de nova mensagem
   - Determina automaticamente o remetente ('usuario' ou 'fornecedor')
   - Verifica se o chat pertence ao utilizador logado
--------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'enviar') {
    $postChatId = (int) ($_POST['chat_id'] ?? 0);
    $texto      = trim((string)($_POST['texto'] ?? ''));

    if ($postChatId <= 0) {
        fail("Chat inválido.");
    } elseif ($texto === '') {
        fail("Mensagem vazia.");
    } else {
        // Verifica a pertença ao chat e determina o 'remetente' correto
        $sql = "SELECT 
                    id, usuario_id, fornecedor_id,
                    CASE 
                      WHEN usuario_id = ? THEN 'usuario'
                      WHEN fornecedor_id = ? THEN 'fornecedor'
                      ELSE NULL
                    END AS meu_papel
                FROM chats
                WHERE id = ? AND (usuario_id = ? OR fornecedor_id = ?)
                LIMIT 1";
        if (!$stmt = $mysqli->prepare($sql)) {
            fail("Erro ao preparar verificação do chat: " . $mysqli->error);
        } else {
            $stmt->bind_param("iiiii", $userId, $userId, $postChatId, $userId, $userId);
            $stmt->execute();
            $rs = $stmt->get_result();
            $chat = $rs->fetch_assoc();
            $stmt->close();

            if (!$chat || !$chat['meu_papel']) {
                fail("Não tens permissão para enviar mensagem neste chat.");
            } else {
                $remetente = $chat['meu_papel']; // 'usuario' ou 'fornecedor'

                $sqlIns = "INSERT INTO chat_mensagens (remetente, texto, id_chat) VALUES (?, ?, ?)";
                if (!$stmt = $mysqli->prepare($sqlIns)) {
                    fail("Erro ao preparar INSERT: " . $mysqli->error);
                } else {
                    // Limita o tamanho do texto para evitar payloads absurdos (ajuste se precisa)
                    if (mb_strlen($texto) > 5000) {
                        $texto = mb_substr($texto, 0, 5000);
                    }
                    $stmt->bind_param("ssi", $remetente, $texto, $postChatId);
                    if (!$stmt->execute()) {
                        fail("Erro ao enviar mensagem: " . $stmt->error);
                    }
                    $stmt->close();

                    // Mantém o chat selecionado após enviar
                    $chatAtualId = $postChatId;

                    // Redireciona para evitar reenvio em refresh (PRG pattern)
                    if (!headers_sent()) {
                        header("Location: ?chat_id=" . $chatAtualId);
                        exit;
                    }
                }
            }
        }
    }
}

/* --------------------------
   4) Lista de chats do usuário
   - Traz o "outro participante" (nome/tipo)
   - Última mensagem e hora
--------------------------- */
$sqlChats = "
    SELECT 
        c.id                              AS chat_id,
        -- ID do outro participante
        IF(c.usuario_id = ?, c.fornecedor_id, c.usuario_id) AS outro_id,
        u.nome                             AS outro_nome,
        u.tipo                             AS outro_tipo,
        -- Última mensagem do chat
        lm.texto                           AS ultima_msg,
        lm.hora                            AS ultima_hora
    FROM chats c
    JOIN usuarios u
      ON u.id_usuario = IF(c.usuario_id = ?, c.fornecedor_id, c.usuario_id)
    LEFT JOIN chat_mensagens lm
      ON lm.id = (
          SELECT m3.id
          FROM chat_mensagens m3
          WHERE m3.id_chat = c.id
          ORDER BY m3.hora DESC
          LIMIT 1
      )
    WHERE (c.usuario_id = ? OR c.fornecedor_id = ?)
    ORDER BY lm.hora DESC, c.id DESC
";

if (!$stmt = $mysqli->prepare($sqlChats)) {
    fail("Erro ao preparar lista de chats: " . $mysqli->error);
} else {
    $stmt->bind_param("iiii", $userId, $userId, $userId, $userId);
    $stmt->execute();
    $rs = $stmt->get_result();
    $chats = $rs->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

/* --------------------------
   5) Se houver um chat selecionado,
      valida pertença e carrega mensagens
--------------------------- */
if ($chatAtualId > 0) {
    // Valida que o chat pertence ao user
    $sqlValida = "SELECT id, usuario_id, fornecedor_id FROM chats WHERE id = ? AND (usuario_id = ? OR fornecedor_id = ?) LIMIT 1";
    if (!$stmt = $mysqli->prepare($sqlValida)) {
        fail("Erro ao preparar validação do chat: " . $mysqli->error);
    } else {
        $stmt->bind_param("iii", $chatAtualId, $userId, $userId);
        $stmt->execute();
        $rs = $stmt->get_result();
        $chatOk = $rs->fetch_assoc();
        $stmt->close();

        if ($chatOk) {
            // Carrega mensagens ordenadas
            $sqlMsgs = "
                SELECT 
                    m.id,
                    m.remetente,
                    m.texto,
                    m.hora,
                    -- Nome do remetente com base na coluna 'remetente'
                    CASE 
                        WHEN m.remetente = 'usuario'    THEN u_cli.nome
                        WHEN m.remetente = 'fornecedor' THEN u_for.nome
                        ELSE 'Desconhecido'
                    END AS remetente_nome
                FROM chat_mensagens m
                JOIN chats c      ON c.id = m.id_chat
                JOIN usuarios u_cli ON u_cli.id_usuario = c.usuario_id
                JOIN usuarios u_for ON u_for.id_usuario = c.fornecedor_id
                WHERE m.id_chat = ?
                ORDER BY m.hora ASC, m.id ASC
            ";
            if (!$stmt = $mysqli->prepare($sqlMsgs)) {
                fail("Erro ao preparar mensagens: " . $mysqli->error);
            } else {
                $stmt->bind_param("i", $chatAtualId);
                $stmt->execute();
                $rs = $stmt->get_result();
                $mensagens = $rs->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
        } else {
            // Chat não pertence ao utilizador
            $chatAtualId = 0;
            $mensagens   = [];
            fail("Chat não encontrado ou sem permissão.");
        }
    }
}

$chatSelecionado = $chatSelecionado ?? null;


/* -----------------------------------------
   6) A partir daqui, tens variáveis prontas
      para o teu HTML:
      - $erro         (string|null)
      - $chats        (array de chats)
      - $chatAtualId  (int do chat selecionado)
      - $mensagens    (array de mensagens do chat)
   Exemplo de uso no HTML:
      foreach ($chats as $c) { ... }
      foreach ($mensagens as $m) { ... }
------------------------------------------ */

// Se quiseres testar rapidamente, descomenta:
// header('Content-Type: application/json; charset=utf-8');
// echo json_encode([ 'erro' => $erro, 'chats' => $chats, 'chat_id' => $chatAtualId, 'mensagens' => $mensagens ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

?>


<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Chat</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: "Segoe UI", Arial, sans-serif;
      background: #f9f9f9;
      color: #333;
      display: flex;
      height: 100vh;
    }

    /* Sidebar de chats */
    .chats {
      width: 25%;
      background: #111;
      color: #fff;
      display: flex;
      flex-direction: column;
    }

    .chats h3 {
      padding: 20px;
      background: #0d0d0d;
      border-bottom: 1px solid #222;
      font-size: 18px;
      text-align: center;
    }

    .chats ul {
      list-style: none;
      flex: 1;
      overflow-y: auto;
    }

    .chats ul li a {
      display: block;
      padding: 15px;
      color: #fff;
      text-decoration: none;
      border-bottom: 1px solid #222;
      transition: background 0.3s;
    }

    .chats ul li a:hover {
      background: #006400;
    }

    /* Área do chat */
    .chat-box {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: #fff;
    }

    .chat-box h3 {
      padding: 15px;
      background: #006400;
      color: #fff;
      font-size: 18px;
    }

    .lista-msg {
      flex: 1;
      padding: 15px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      overflow-y: auto;
    }

    .mensagem {
      padding: 10px 15px;
      border-radius: 12px;
      max-width: 60%;
      font-size: 14px;
      line-height: 1.4;
      position: relative;
    }

    .mensagem.usuario {
      background: #d1ffd1;
      align-self: flex-end;
    }

    .mensagem.fornecedor {
      background: #d1e0ff;
      align-self: flex-start;
    }

    .mensagem small {
      display: block;
      font-size: 11px;
      color: #555;
      margin-top: 5px;
    }

    /* Formulário */
    form {
      display: flex;
      border-top: 1px solid #ccc;
      padding: 10px;
      background: #f1f1f1;
    }

    form input[type="text"] {
      flex: 1;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 20px;
      outline: none;
    }

    form button {
      margin-left: 10px;
      padding: 12px 20px;
      border: none;
      background: #006400;
      color: #fff;
      border-radius: 20px;
      cursor: pointer;
      transition: background 0.3s;
    }

    form button:hover {
      background: #004d00;
    }

    /* Responsividade */
    @media (max-width: 768px) {
      body {
        flex-direction: column;
      }
      .chats {
        width: 100%;
        height: 200px;
      }
      .chat-box {
        flex: 1;
      }
    }
  </style>
</head>
<body>
  <!-- Lista de chats -->
  <div class="chats">
    <h3>Seus Chats</h3>
    <ul>
      <?php foreach ($chats as $c): ?>
        <li>
          <a href="?chat_id=<?= $c['id_chat'] ?>">
            <?= htmlspecialchars($c['parceiro_nome']) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Mensagens do chat -->
  <div class="chat-box">
    <h3>Mensagens</h3>
    <div class="lista-msg">
      <?php foreach ($mensagens as $m): ?>
        <div class="mensagem <?= $m['remetente'] ?>">
          <strong><?= htmlspecialchars($m['nome']) ?>:</strong>
          <?= htmlspecialchars($m['texto']) ?>
          <small><?= $m['hora'] ?></small>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($chatSelecionado): ?>
    <form method="POST">
      <input type="hidden" name="chat_id" value="<?= $chatSelecionado ?>">
      <input type="text" name="texto" placeholder="Digite sua mensagem..." required>
      <button type="submit">Enviar</button>
    </form>
    <?php endif; ?>
  </div>
</body>
</html>
