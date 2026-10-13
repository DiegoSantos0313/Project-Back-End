<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// CONFIGURAÇÃO DO BANCO
$host = "localhost";
$db   = 'unicoffe_db';
$user = 'admin';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Função simples para limpar strings
function limpar($str) {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

// Impede GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Método inválido.");
}

// Recebe dados
$email  = limpar($_POST['email'] ?? '');
$codigo = limpar($_POST['codigo'] ?? '');

// Verificação básica
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("E-mail inválido.");
}

if (!preg_match('/^\d{6}$/', $codigo)) {
    die("Código inválido. Deve conter 6 números.");
}

// Buscar usuário pelo email
$sql = "SELECT id, codigo_verificacao, verificado FROM usuarios WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Usuário não encontrado.");
}

$usuario = $result->fetch_assoc();
$stmt->close();

// Verificar se já está verificado
if ($usuario['verificado'] == 1) {
    die("Conta já verificada. Você já pode fazer login.");
}

// Comparar código informado com o salvo no banco
if ($usuario['codigo_verificacao'] !== $codigo) {
    // Log de tentativa de código errado
    $log_stmt = $conn->prepare("INSERT INTO mensagens_log (tipo, mensagem) VALUES (?, ?)");
    if ($log_stmt) {
        $tipo = 'codigo_incorreto';
        $mens = "Tentativa com código incorreto para email {$email}. Código digitado: {$codigo}";
        $log_stmt->bind_param("ss", $tipo, $mens);
        $log_stmt->execute();
        $log_stmt->close();
    }

    die("Código inválido. Tente novamente.");
}

// Código correto → Atualiza usuário como verificado
$update = $conn->prepare("UPDATE usuarios SET verificado = 1, codigo_verificacao = NULL WHERE email = ?");
$update->bind_param("s", $email);

if ($update->execute()) {

    // Log de verificação concluída
    $log_stmt = $conn->prepare("INSERT INTO mensagens_log (tipo, mensagem) VALUES (?, ?)");
    if ($log_stmt) {
        $tipo = 'conta_verificada';
        $mens = "Conta verificada com sucesso para o email: {$email}";
        $log_stmt->bind_param("ss", $tipo, $mens);
        $log_stmt->execute();
        $log_stmt->close();
    }

    // Redireciona para login
    header("Location: login.html?verificado=1");
    exit;

} else {
    die("Erro ao verificar conta: " . $update->error);
}

$update->close();
$conn->close();
?>
