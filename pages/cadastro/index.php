<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>

<body>
    <div id="toast" class="toast"></div>
    <form id="signup-form" class="meuForm">
        <div class="signup-header">
            <div class="logo">
                <i class="fas fa-store"></i>
            </div>
            <h2>Crie sua conta</h2>
        </div>

        <!-- Passo 1: Dados pessoais -->
        <div class="step" id="step-1">
            <div class="input-group">
                <label>Nome completo</label>
                <input type="text" name="nome" placeholder="Digite seu nome completo" required>
            </div>
            <div class="input-group">
                <label>E-mail</label>
                <input type="email" name="email" placeholder="Digite seu e-mail" required>
            </div>
            <div class="input-group">
                <label>Telefone</label>
                <input type="tel" name="telefone" placeholder="Digite seu telefone" required pattern="^(\+244)?\d{9}$">
            </div>
            <button type="button" id="next-1">Próximo</button>
        </div>

        <!-- Passo 2: Senha -->
        <div class="step" id="step-2" style="display:none;">
            <div class="input-group">
                <label>Senha</label>
                <input type="password" name="senha" placeholder="Crie uma senha" required minlength="6">
            </div>
            <div class="input-group">
                <label>Confirmar senha</label>
                <input type="password" name="confirmar_senha" placeholder="Confirme sua senha" required>
            </div>
            <button type="button" id="prev-2">Voltar</button>
            <button type="button" id="next-2">Próximo</button>
        </div>

        <!-- Passo 3: Tipo de conta -->
        <div class="step" id="step-3" style="display:none;">
            <div class="input-group">
                <label>Tipo de conta</label>
                <select name="tipo_conta" required>
                    <option value="">Selecione...</option>
                    <option value="usuario">Usuário</option>
                    <option value="fornecedor">Fornecedor</option>
                </select>
            </div>
            <button type="button" id="prev-3">Voltar</button>
            <button type="submit">Cadastrar</button>
        </div>

        <a href="../login/">Iniciar sessão</a>
    </form>

    <script>
    // Controle de etapas
    const steps = [
        document.getElementById('step-1'),
        document.getElementById('step-2'),
        document.getElementById('step-3')
    ];
    let currentStep = 0;

    function showStep(n) {
        steps.forEach((step, i) => step.style.display = i === n ? 'block' : 'none');
    }

    function showToast(text, type = "erro") {
        const toast = document.getElementById('toast');
        toast.textContent = text;
        toast.className = `toast show ${type}`;
        setTimeout(() => {
            toast.className = 'toast';
        }, 2500);
    }

    // Próximo do passo 1
    document.getElementById('next-1').onclick = function() {
        const form = document.forms['signup-form'];
        const nome = form.nome.value.trim();
        const email = form.email.value.trim();
        const telefone = form.telefone.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const telRegex = /^(\+244)?\d{9}$/;
        let msg = document.getElementById('mensagem');

        if (!nome) {
            showToast("Preencha o nome completo.");
            return;
        }
        if (!emailRegex.test(email)) {
            showToast("E-mail inválido.");
            return;
        }
        if (!telRegex.test(telefone)) {
            showToast("Telefone inválido. Use o formato +244XXXXXXXXX ou XXXXXXXXX.");
            return;
        }
        showStep(1);
    };

    // Próximo do passo 2
    document.getElementById('next-2').onclick = function() {
        const form = document.forms['signup-form'];
        const senha = form.senha.value;
        const confirmar = form.confirmar_senha.value;
        let msg = document.getElementById('mensagem');

        if (senha.length < 6) {
            showToast("A senha deve ter pelo menos 6 caracteres.");
            return;
        }
        if (senha !== confirmar) {
            showToast("As senhas não coincidem.");
            return;
        }
        showStep(2);
    };

    // Voltar do passo 2
    document.getElementById('prev-2').onclick = function() {
        showStep(0);
    };

    // Voltar do passo 3
    document.getElementById('prev-3').onclick = function() {
        showStep(1);
    };

    // Envio final
    document.getElementById('signup-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        let form = e.target;

        const tipoConta = form.tipo_conta.value;
        if (!tipoConta) {
            showToast("Selecione o tipo de conta.");
            return;
        }

        let formData = new FormData(form);

        try {
            let resp = await fetch('cadastro.php', {
                method: 'POST',
                body: formData
            });

            let data = await resp.json();

            if (data.status === "sucesso") {
                showToast("Cadastro realizado com sucesso!", "sucesso");
                form.reset();
                showStep(0);
            } else {
                showToast(data.mensagem, "erro");
            }
        } catch (error) {
            showToast("Erro ao conectar com o servidor.", "erro");
        }
    });
    </script>
</body>
</html>
