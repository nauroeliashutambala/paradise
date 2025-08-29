<?php
header('Content-Type: application/json');
$mysqli = new mysqli("localhost", "root", "", "casamento_marketplace");

if ($mysqli->connect_errno) {
    echo json_encode(["status" => "erro", "mensagem" => "Falha na conexão com o banco"]);
    exit;
}