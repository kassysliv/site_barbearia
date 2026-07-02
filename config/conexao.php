<?php
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistema_barbearia"; // Nome do banco que criamos juntos no phpMyAdmin
$porta = 3306;

// Criando a conexão com a variável exatamente como o login.php espera ($conexao)
$conexao = new mysqli($host, $usuario, $senha, $banco, $porta);

// Se der erro, avisa na tela imediatamente
if ($conexao->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conexao->connect_error);
}
?>