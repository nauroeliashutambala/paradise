<?php
require_once("../../config/conexao.php");
header('Content-Type: application/json');

// Consulta serviços ativos

$sql = "SELECT id_servico, titulo, preco_base, categoria, descricao 
    FROM servicos 
    WHERE status = 'ativo' ";
if (isset($_REQUEST['search'])) {
    $sql .= " and titulo like " . $_GET['search'];
}
    $sql .= "
        ORDER BY data_criacao DESC 
        LIMIT 12";
        
$result = $mysqli->query($sql);

$servicos = [];

while ($row = $result->fetch_assoc()) {
    $servicos[] = [
        'id'        => $row['id_servico'],
        'nome'      => $row['titulo'],
        'preco'     => number_format($row['preco_base'], 2, ',', '.'),
        'imagem'    => 'https://via.placeholder.com/150', // Substitua se tiver campo de imagem
        'categoria' => $row['categoria'],
        'descricao' => $row['descricao']
    ];
}

echo json_encode($servicos);
$mysqli->close();
