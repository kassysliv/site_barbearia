<?php
session_start();

// Exibir erros (apenas durante o desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===============================
// CONEXÃO COM O BANCO
// ===============================

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

// ===============================
// LOGIN
// ===============================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $senhaLogin = $_POST["senha"];

    $sql = "SELECT id, nome, senha FROM clientes WHERE email = ?";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {

        $cliente = $resultado->fetch_assoc();

        if (password_verify($senhaLogin, $cliente["senha"])) {

            $_SESSION["cliente_id"] = $cliente["id"];
            $_SESSION["cliente_nome"] = $cliente["nome"];

            header("Location: agendar.php");
            exit;

        } else {
            $mensagem = "E-mail ou senha inválidos.";
        }
    } else {
        $mensagem = "E-mail ou senha inválidos.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbearia Estilo & Cia - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="login-box">

    <div class="logo-container">
        <img src="imagens/logo.png" alt="Logo Barbearia" class="logo">
    </div>

    <h1>Bem-vindo de volta!</h1>
    <p class="subtitulo">Faça login para continuar</p>

    <?php if(!empty($mensagem)): ?>
        <div class="msg-erro">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="input-group">
            <i class="fa-regular fa-envelope input-icon"></i>
            <input
                type="email"
                name="email"
                placeholder="Digite seu e-mail"
                required>
        </div>

        <div class="input-group">
            <i class="fa-solid fa-lock input-icon"></i>
            <input
                type="password"
                id="senha"
                name="senha"
                placeholder="Digite sua senha"
                required>
            <i class="fa-regular fa-eye toggle-password"></i>
        </div>

        <div class="login-options">
            <label>
                <input type="checkbox" name="lembrar"> Lembrar-me
            </label>
            <a href="#">Esqueci minha senha</a>
        </div>

        <button type="submit">Entrar</button>

    </form>

    <div class="footer-text">
        Ainda não possui uma conta? <a href="cadastro.php">Cadastre-se</a>
    </div>

</div>

<script>
// Controle visual para mostrar/ocultar a senha
const botao = document.querySelector(".toggle-password");

botao.addEventListener("click", function(){
    const senha = document.getElementById("senha");

    if(senha.type === "password"){
        senha.type = "text";
        this.classList.remove("fa-regular", "fa-eye");
        this.classList.add("fa-solid", "fa-eye-slash");
    } else {
        senha.type = "password";
        this.classList.remove("fa-solid", "fa-eye-slash");
        this.classList.add("fa-regular", "fa-eye");
    }
});
</script>

</body>
</html>