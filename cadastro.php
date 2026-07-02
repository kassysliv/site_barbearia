<?php
// Ativa a exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CONEXÃO COM O BANCO
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistema_barbearia";
$porta = 3306;

$conexao = new mysqli($host, $usuario, $senha, $banco, $porta);

if ($conexao->connect_error) {
    die("Erro na conexão: " . $conexao->connect_error);
}

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST['nome'] ?? "");
    $telefone = trim($_POST['telefone'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $senha_cadastro = $_POST['senha'] ?? "";
    $confirmar_senha = $_POST['confirmar_senha'] ?? "";

    if (empty($nome) || empty($telefone) || empty($email) || empty($senha_cadastro) || empty($confirmar_senha)) {
        $mensagem = "<p class='msg-erro'>Preencha todos os campos.</p>";
    } elseif ($senha_cadastro !== $confirmar_senha) {
        $mensagem = "<p class='msg-erro'>As senhas não coincidem!</p>";
    } else {
        $sql_verificar = "SELECT id FROM clientes WHERE email = ?";
        $stmt_verificar = $conexao->prepare($sql_verificar);
        $stmt_verificar->bind_param("s", $email);
        $stmt_verificar->execute();
        $resultado_verificar = $stmt_verificar->get_result();

        if ($resultado_verificar->num_rows > 0) {
            $mensagem = "<p class='msg-erro'>Este e-mail já está cadastrado!</p>";
        } else {
            $senha_criptografada = password_hash($senha_cadastro, PASSWORD_DEFAULT);

            $sql_inserir = "INSERT INTO clientes (nome, telefone, email, senha) VALUES (?, ?, ?, ?)";
            $stmt_inserir = $conexao->prepare($sql_inserir);
            $stmt_inserir->bind_param("ssss", $nome, $telefone, $email, $senha_criptografada);

            if ($stmt_inserir->execute()) {
                $mensagem = "<p class='msg-sucesso'>Cadastro realizado com sucesso! <a href='login.php'>Faça login aqui</a></p>";
            } else {
                $mensagem = "<p class='msg-erro'>Erro ao cadastrar: " . $stmt_inserir->error . "</p>";
            }
            $stmt_inserir->close();
        }
        $stmt_verificar->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Barbearia</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=4.0">
    
    <style>
        .close-header { display: flex; justify-content: flex-end; margin-bottom: -10px; }
        .close-button { color: #888; font-size: 20px; cursor: pointer; transition: .3s; }
        .close-button:hover { color: var(--primary-gold); }
        .divider-container { display: flex; align-items: center; text-align: center; color: #555; margin: 20px 0; font-size: 14px; }
        .divider-container::before, .divider-container::after { content: ''; flex: 1; border-bottom: 1px solid var(--border); }
        .divider-container:not(:empty)::before { margin-right: .5em; }
        .divider-container:not(:empty)::after { margin-left: .5em; }
        .btn-google { background: transparent; border: 1px solid var(--border); color: #fff; display: flex; justify-content: center; align-items: center; gap: 10px; font-weight: 500; font-size: 15px; }
        .btn-google:hover { background: #1c1c1c; border-color: #666; transform: none; box-shadow: none; }
        .btn-google img { width: 18px; height: 18px; }
    </style>
</head>
<body>

    <div class="login-box">
        
        <div class="close-header">
            <i class="fa-solid fa-xmark close-button" onclick="window.location.href='login.php'"></i>
        </div>

        <div class="logo-container">
            <img src="imagens/logo.png" alt="Barbearia Estilo & Cia" class="logo">
        </div>

        <h1>Criar sua conta</h1>
        <p class="subtitulo">Preencha os dados abaixo para se cadastrar</p>

        <?php echo $mensagem; ?>

        <form action="cadastro.php" method="POST">

            <div class="input-group">
                <i class="fa-regular fa-user input-icon"></i>
                <input type="text" name="nome" placeholder="Digite seu nome completo" required>
            </div>

            <div class="input-group">
                <i class="fa-regular fa-envelope input-icon"></i>
                <input type="email" name="email" placeholder="Digite seu melhor e-mail" required>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-phone input-icon"></i>
                <input type="text" name="telefone" id="telefone" placeholder="(00) 00000-0000" required>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock input-icon"></i>
                <input type="password" name="senha" placeholder="Digite sua senha" required>
                <i class="fa-regular fa-eye toggle-password"></i>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock input-icon"></i>
                <input type="password" name="confirmar_senha" placeholder="Confirme sua senha" required>
                <i class="fa-regular fa-eye toggle-password"></i>
            </div>

            <button type="submit">CRIAR CONTA</button>

        </form>

        <div class="divider-container">ou</div>

        <button class="btn-google" type="button">
            <img src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/web-24dp/copy_of_24dp.png" alt="Google">
            Continuar com o Google
        </button>

        <div class="footer-text">
            Já tem uma conta? <a href="login.php">Fazer login</a>
        </div>

    </div>

    <script>
        // Alternar visibilidade da senha
        document.querySelectorAll('.toggle-password').forEach(item => {
            item.addEventListener('click', function () {
                const input = this.parentElement.querySelector('input');
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('fa-regular', 'fa-eye');
                    this.classList.add('fa-solid', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    this.classList.remove('fa-solid', 'fa-eye-slash');
                    this.classList.add('fa-regular', 'fa-eye');
                }
            });
        });

        // Máscara dinâmica de Telefone
        const telInput = document.getElementById('telefone');
        telInput.addEventListener('input', (e) => {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    </script>

</body>
</html>