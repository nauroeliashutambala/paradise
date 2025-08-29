<?php
// Conexão com DB (ajuste caminho e credenciais)
require_once("../../config/conexao.php");

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loja de Moda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/home.css">
    <link rel="stylesheet" href="../../assets/css/bottomnav.css">
</head>

<body>
    <!-- HEADER -->
    <header>
        <div class="search-bar">
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" id="search" placeholder="Search...">
            </div>
            <div class="header-icons">
                <a href="../chat/" class="icon">
                    <i class="fas fa-comment-dots"></i>
                    <span class="notification-badge">3</span>
                </a>

                <a href="../notificacoes/" class="icon">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">5</span>
                </a>

            </div>
        </div>
    </header>

    <!-- BODY -->
    <div class="container">
        <!-- Banner -->
        <div class="banner">
            <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1350&q=80" alt="Promoção">
            <div class="banner-content">
                <div class="banner-title">80% OFF</div>
                <div class="banner-subtitle">Discover fashion that suits your style</div>
                <button class="banner-button">Check this out</button>
            </div>
        </div>

        <!-- Categorias -->
        <div class="categories">
            <div class="category-item">
                <div class="category-icon"><i class="fas fa-th-large"></i></div><span>Category</span>
            </div>
            <div class="category-item">
                <div class="category-icon"><i class="fas fa-plane"></i></div><span>Flight</span>
            </div>
            <div class="category-item">
                <div class="category-icon"><i class="fas fa-calendar-alt"></i></div><span>Date Plan</span>
            </div>
            <div class="category-item">
                <div class="category-icon"><i class="fas fa-star"></i></div><span>Top List</span>
            </div>
        </div>

        <!-- Produtos -->
        <div class="section-header">
            <h2 class="section-title">Best Sale Product</h2>
            <a href="#" class="see-more">See more</a>
        </div>

        <div class="products-grid" id="products-container">
            <!-- Produtos carregados via JS -->
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <nav class="bottom-nav">
            <a href="../homepage/index.php"><i class="fas fa-home"></i><span>Início</span></a>
            <a href="../favoritos/index.php"><i class="fas fa-heart"></i><span>Favoritos</span></a>
            <a href="../reservas/index.php"><i class="fas fa-calendar-check"></i><span>Reservas</span></a>
            <a href="../perfil/index.php"><i class="fas fa-user"></i><span>Perfil</span></a>
        </nav>
    </footer>

    <script>
        async function carregarProdutos() {
            const resposta = await fetch('get_products.php');
            const produtos = await resposta.json();
            const container = document.getElementById('products-container');
            container.innerHTML = '';

            produtos.forEach(prod => {
                container.innerHTML += `
                <div class="product-card">
                    <div class="product-image">
                        <img src="${prod.imagem}" alt="${prod.nome}">
                        <div class="favorite-icon" onclick="favoritarProduto(${prod.id})"><i class="far fa-heart"></i></div>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">${prod.nome}</h3>
                        <div class="product-price">AOA ${prod.preco}</div>
                        <div class="product-rating">
                            <i class="fas fa-star"></i> ${prod.avaliacao || ''}
                            <span>(${prod.vendas || 0})</span>
                        </div>
                    </div>
                </div>
            `;
            });
        }

        async function favoritarProduto(id_produto) {
            const resp = await fetch('favoritar.php', {
                method: 'POST',
                body: JSON.stringify({
                    id_produto
                }),
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            const data = await resp.json();
            if (data.status === "erro") {
                showToast("Você precisa estar logado para favoritar.", "erro");
                // Opcional: abrir modal de login
            } else {
                showToast("Produto favoritado!", "sucesso");
            }
        }

        document.addEventListener('DOMContentLoaded', carregarProdutos);
    </script>
</body>

</html>