<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php"); // troquei .html para .php (login real do sistema)
    exit;
}

// Garante que o nome está definido para evitar avisos
$nome = isset($_SESSION['usuario_nome']) ? htmlspecialchars($_SESSION['usuario_nome']) : 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Bem-vindo</title>
</head>
<body>
  <h1>Bem-vindo, <?= $nome ?>!</h1>

  <a href="logout.php">Sair</a>
</body>
</html>

