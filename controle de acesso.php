<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Verifica se existe o perfil do usuário na sessão
if (!isset($_SESSION['usuario_perfil'])) {
    echo "Erro: perfil de usuário não encontrado.";
    exit;
}

// Restringe acesso apenas ao perfil master
if ($_SESSION['usuario_perfil'] !== 'master') {
    echo "Acesso negado. Você não tem permissão para acessar esta página.";
    exit;
}

// Conteúdo da página para usuário master
$usuario_login = isset($_SESSION['usuario_login']) ? htmlspecialchars($_SESSION['usuario_login']) : 'Usuário';

echo "Bem-vindo, {$usuario_login}!";
?>
