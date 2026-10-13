<?php
// Configurações da conexão
$host = "localhost";
$usuario = 'admin';
$senha = ''; // senha padrão no XAMPP
$banco = 'unicoffe_db';

// Criar conexão
$conn = new mysqli($host, $usuario, $senha, $banco);

// Verificar conexão
if ($conn->connect_error) {
    error_log("Erro de conexão: " . $conn->connect_error); // registra log
    die("Erro ao conectar ao banco de dados."); // mensagem segura
}

// Ajustar charset
if (!$conn->set_charset("utf8mb4")) {
    error_log("Erro ao definir charset: " . $conn->error);
}
?>

