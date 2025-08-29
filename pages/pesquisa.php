
<?php
// ================== SETUP PHP (no topo, antes de qualquer HTML) ==================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli("localhost", "root", "", "casamento_marketplace");
$conn->set_charset('utf8mb4');

// Filtros recebidos via GET
$termo = isset($_GET['q']) ? trim($_GET['q']) : "";
$categoria = (isset($_GET['categoria']) && $_GET['categoria'] !== "Todos") ? trim($_GET['categoria']) : "";

// Monta a query de forma segura (prepared statements)
$like = "%{$termo}%";

if ($categoria) {
    $stmt = $conn->prepare("
        SELECT s.id_servico, s.titulo, s.preco_base, s.categoria, u.nome AS fornecedor
        FROM servicos s
        JOIN usuarios u ON s.id_fornecedor = u.id_usuario
        WHERE (s.titulo LIKE ? OR s.categoria LIKE ?) AND s.categoria = ?
        ORDER BY s.titulo
    ");
    $stmt->bind_param("sss", $like, $like, $categoria);
} else {
    $stmt = $conn->prepare("
        SELECT s.id_servico, s.titulo, s.preco_base, s.categoria, u.nome AS fornecedor
        FROM servicos s
        JOIN usuarios u ON s.id_fornecedor = u.id_usuario
        WHERE (s.titulo LIKE ? OR s.categoria LIKE ?)
        ORDER BY s.titulo
    ");
    $stmt->bind_param("ss", $like, $like);
}

$stmt->execute();
$result = $stmt->get_result();

// Helper: primeira foto do serviço
function primeira_foto(mysqli $conn, int $id_servico): string {
    $sf = $conn->prepare("SELECT url_foto FROM fotos_servico WHERE id_servico = ? LIMIT 1");
    $sf->bind_param("i", $id_servico);
    $sf->execute();
    $rf = $sf->get_result();
    $row = $rf->fetch_assoc();
    return $row ? $row['url_foto'] : 'img/sem_foto.jpg';
}

// Resposta parcial para AJAX (somente os cards)
if (isset($_GET['ajax'])) {
    if ($result->num_rows === 0) {
        echo "<p>Nenhum serviço encontrado.</p>";
    } else {
        while ($row = $result->fetch_assoc()) {
            $foto = primeira_foto($conn, (int)$row['id_servico']); ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($row['titulo']) ?>">
                </div>
                <div class="product-info">
                    <h3 class="product-title"><?= htmlspecialchars($row['titulo']) ?></h3>
                    <div class="product-price">Kz <?= number_format($row['preco_base'], 2, ',', '.') ?></div>
                </div>
            </div>
        <?php }
    }
    $stmt->close();
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Pesquisa - Casamento Marketplace</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Usa o teu CSS ou este básico -->
<style>
body { font-family: system-ui, Arial, sans-serif; margin:0; background:#f5f5f5; }
.search-header { background:#fff; padding:10px; border-bottom:1px solid #e9e9e9; position:sticky; top:0; z-index:10; }
.search-bar-container { display:flex; align-items:center; gap:10px; }
.back-button a { color:#333; font-size:18px; text-decoration:none; }
.search-input { flex:1; border:none; background:#f0f0f0; border-radius:20px; padding:10px 14px; outline:none; }
.filter-button { border:none; background:none; font-size:18px; cursor:pointer; color:#333; }
.filters-container { display:flex; gap:10px; overflow-x:auto; padding:10px; background:#fff; border-bottom:1px solid #e9e9e9; }
.filter-chip { background:#eee; padding:8px 14px; border-radius:20px; white-space:nowrap; cursor:pointer; text-transform:capitalize; }
.filter-chip.active { background:#4CAF50; color:#fff; }
.results-container { padding:15px; }
.section-title { font-size:18px; margin:0 0 10px; }
.products-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:15px; }
.product-card { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.08); }
.product-image img { width:100%; height:150px; object-fit:cover; display:block; }
.product-info { padding:10px; }
.product-title { margin:0 0 6px; font-size:15px; line-height:1.3; }
.product-price { color:#2e7d32; font-weight:700; }
.footer-nav { position:fixed; left:0; right:0; bottom:0; background:#fff; border-top:1px solid #e9e9e9; display:flex; justify-content:space-around; padding:10px 0; }
.footer-item { text-align:center; color:#666; }
.footer-item.active { color:#4CAF50; font-weight:600; }
</style>
</head>
<body>

<!-- HEADER -->
<div class="search-header">
    <form method="GET" class="search-bar-container" id="form-busca">
        <div class="back-button">
            <a href="index.php"><i class="fas fa-arrow-left"></i></a>
        </div>
        <input type="text" name="q" value="<?= htmlspecialchars($termo) ?>"
               class="search-input" placeholder="Pesquisar salões, serviços...">
        <button type="submit" class="filter-button" aria-label="Pesquisar">
            <i class="fas fa-search"></i>
        </button>
    </form>
</div>

<!-- FILTROS (valores iguais aos da coluna s.categoria) -->
<div class="filters-container" id="chips">
    <?php
    // Ajuste estes rótulos conforme os valores que realmente grava em s.categoria
    $chips = ['Todos', 'salão de eventos', 'decoração', 'fotografia', 'buffet'];
    foreach ($chips as $chip) {
        $active = ($chip === 'Todos' && $categoria === '') || ($chip === $categoria) ? 'active' : '';
        echo "<div class='filter-chip $active' data-categoria='".htmlspecialchars($chip)."'>".$chip."</div>";
    }
    ?>
</div>

<!-- RESULTADOS -->
<div class="results-container">
    <h2 class="section-title">Resultados da pesquisa</h2>
    <div class="products-grid" id="resultados">
        <?php
        if ($result->num_rows === 0) {
            echo "<p>Nenhum serviço encontrado.</p>";
        } else {
            while ($row = $result->fetch_assoc()) {
                $foto = primeira_foto($conn, (int)$row['id_servico']); ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($row['titulo']) ?>">
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?= htmlspecialchars($row['titulo']) ?></h3>
                        <div class="product-price">Kz <?= number_format($row['preco_base'], 2, ',', '.') ?></div>
                    </div>
                </div>
            <?php }
        }
        ?>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <nav class="footer-nav">
        <div class="footer-item">
            <i class="fas fa-home"></i><div>Home</div>
        </div>
        <div class="footer-item active">
            <i class="fas fa-search"></i><div>Pesquisa</div>
        </div>
        <div class="footer-item">
            <i class="fas fa-calendar-alt"></i><div>Agendamentos</div>
        </div>
        <div class="footer-item">
            <i class="fas fa-user"></i><div>Perfil</div>
        </div>
    </nav>
</footer>

<script>
// Clicar num chip => filtra via AJAX sem recarregar
$('#chips').on('click', '.filter-chip', function () {
    $('.filter-chip').removeClass('active');
    $(this).addClass('active');

    const categoria = $(this).data('categoria');            // "Todos" ou valor da DB
    const q = $('input[name="q"]').val();

    const params = { ajax: 1, q: q };
    if (categoria && categoria !== 'Todos') params.categoria = categoria;

    $.get(window.location.pathname, params, function (html) {
        $('#resultados').html(html);
    });
});

// Submeter a busca (Enter ou botão) também por AJAX
$('#form-busca').on('submit', function (e) {
    e.preventDefault();
    const q = $('input[name="q"]').val();
    const categoria = $('.filter-chip.active').data('categoria');
    const params = { ajax: 1, q: q };
    if (categoria && categoria !== 'Todos') params.categoria = categoria;

    $.get(window.location.pathname, params, function (html) {
        $('#resultados').html(html);
    });
});
</script>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
