<?php
session_start();
require_once __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método não permitido.');
}

$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

if (!is_string($email) || !is_string($senha)) {
    header('Location: login.php?erro=login');
    exit;
}

$email = trim($email);

if (
    $email === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $senha === ''
) {
    header('Location: login.php?erro=login');
    exit;
}

$stmt = $conn->prepare(
    'SELECT id, senha_hash FROM usuarios WHERE email = ? LIMIT 1'
);

if (!$stmt) {
    error_log('Falha ao preparar consulta de login: ' . $conn->error);
    http_response_code(500);
    exit('Não foi possível processar o login. Tente novamente.');
}

$stmt->bind_param('s', $email);
$stmt->execute();

$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();

if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
    header('Location: login.php?erro=login');
    exit;
}

// Ainda não libera o painel: o usuário precisa concluir o 2FA.
session_regenerate_id(true);

// Evita manter dados de uma autenticação anterior nesta sessão.
unset($_SESSION['usuario_id'], $_SESSION['usuario_login'], $_SESSION['usuario_perfil']);

$_SESSION['2fa_usuario_id'] = (int) $usuario['id'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: verificar_2fa.php');
exit;
