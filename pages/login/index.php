<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Dinâmico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>

<body>
    <div id="toast" class="toast"></div>
    <form id="formLogin" class="meuForm">
        <div class="signup-header">
            <div class="logo">
                <i class="fas fa-store"></i>
            </div>
            <h2>Bem-vindo de volta</h2>
        </div>
        <div class="input-group">
            <label for="email">E-mail</label>
            <input type="email" name="email" id="email" class="input-field" placeholder="Digite seu e-mail" required>
        </div>
        <div class="input-group">
            <label for="password">Senha</label>
            <input type="password" name="password" id="password" class="input-field" placeholder="Digite sua senha" required>
        </div>
        <div class="forgot-password">
            <a href="#">Esqueceu a senha?</a>
        </div>
        <button type="submit" class="login-button">Entrar</button>
        <div class="forgot-password">
            <a href="../cadastro/" class="texto_center">Criar conta</a>
        </div>
    </form>
    <script src="../../assets/js/toast.js"></script>
    <script>
        document.getElementById("formLogin").addEventListener("submit", async function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            let response = await fetch("login_action.php", {
                method: "POST",
                body: formData
            });
            let result = await response.json();
            if (result.success) {
                showToast(result.message, "sucesso");
                setTimeout(() => {
                    window.location.href = "../homepage/";
                }, 1500);
            } else {
                showToast(result.message, "erro");
            }
        });
    </script>
</body>

</html>