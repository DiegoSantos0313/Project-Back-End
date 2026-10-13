<?php
session_start();
include 'conexao.php';
require_once 'vendor/autoload.php';
use PHPGangsta_GoogleAuthenticator;

if (!isset($_SESSION['2fa_usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['2fa_usuario_id'];
$codigo_2fa = $_POST['codigo_2fa'] ?? '';

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: login.php?erro=usuario_nao_encontrado");
    exit;
}

$ga = new PHPGangsta_GoogleAuthenticator();
$validar = $ga->verifyCode($usuario['chave_2fa'], $codigo_2fa, 2); // 2 = janela de 2x30s

if ($validar) {
    // Login completo
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_login'] = $usuario['login'];
    $_SESSION['usuario_perfil'] = $usuario['perfil'];

    // Grava log
    $stmtLog = $conn->prepare("INSERT INTO logs_autenticacao (usuario_id, sucesso, tipo_2fa, ip_acesso) VALUES (?, ?, ?, ?)");
    $stmtLog->execute([$usuario['id'], true, 'Google Authenticator', $_SERVER['REMOTE_ADDR']]);

    // Limpa variáveis da sessão para 2FA
    unset($_SESSION['2fa_usuario_id']);

    // Redireciona para página principal
    header("Location: principal.php");
    exit;
} else {
    // Log de falha
    $stmtLog = $conn->prepare("INSERT INTO logs_autenticacao (usuario_id, sucesso, tipo_2fa, ip_acesso) VALUES (?, ?, ?, ?)");
    $stmtLog->execute([$usuario['id'], false, 'Google Authenticator', $_SERVER['REMOTE_ADDR']]);

    header("Location: verificar_2fa.php?erro=cod_2fa_invalido");
    exit;
}
?>