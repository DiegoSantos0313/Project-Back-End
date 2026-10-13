<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/php_errors.log');
error_reporting(E_ALL);
ini_set('display_errors',0);

// Permite somente requisições HTTP POST
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
exit("Método não permitido.");
}

// Configuração da conexão com o banco MySQL
$host = "localhost";
$user = 'root';
$pass = '';
$db   = 'unicoffe_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log($conn->connect_error);
http_response_code(500);
exit("Erro interno. Tente novamente mais tarde.");
}

// Remove espaços e sanitiza entradas de texto
function limpar(string $str): string
{
   return trim($str);
}
// Recebe e sanitiza os dados enviados pelo formulário
$nome           = limpar($_POST['nome'] ?? '');
$dataNascimento = limpar($_POST['dataNascimento'] ?? '');
$mae           = limpar($_POST['mae']  ?? '');
$cpf           = limpar($_POST['cpf']  ?? '');
$cep           = limpar($_POST['cep']  ?? '');
$endereco      = limpar($_POST['endereco']  ?? '');
$complemento   = limpar($_POST['complemento']  ?? '');
$email         = limpar($_POST['email']  ?? '');
$telefone      = limpar($_POST['telefone']  ?? '');
$login         = limpar($_POST['login']  ?? '');
$senha         = $_POST['senha']  ?? '';

// Validação dos dados recebidos
if (mb_strlen($nome) < 3 || mb_strlen($nome) > 80) {
   throw new Exception("Nome inválido.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
exit("E-mail inválido.");
}

if (strlen($senha) < 6) {
    http_response_code(400);
exit("A senha deve ter pelo menos 6 caracteres.");
}

// Verifica duplicidade do e-mail
$checkEmail = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$checkEmail->bind_param("s", $email);
$checkEmail->execute();
$checkEmail->store_result();
if ($checkEmail->num_rows > 0) {
    http_response_code(409);
exit("Este e-mail já está cadastrado.");
}

// Consulta o banco para verificar duplicidade do login
$checkLogin = $conn->prepare("SELECT id FROM usuarios WHERE login = ?");
$checkLogin->bind_param("s", $login);
$checkLogin->execute();
$checkLogin->store_result();
if ($checkLogin->num_rows > 0) {
   http_response_code(409);
exit("Este login já está em uso.");
}

// Gera o hash seguro da senha antes de armazená-la
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Prepara a inserção do novo usuário no banco de dados
$sql = "INSERT INTO usuarios 
(nome, data_nascimento, nome_mae, cpf, cep, endereco, complemento, email, telefone, login, senha_hash) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Erro ao preparar cadastro: " . $conn->error);
    http_response_code(500);
    exit("Não foi possível processar o cadastro.");
}
$stmt->bind_param("sssssssssss", 
    $nome, $dataNascimento, $mae, $cpf, $cep, $endereco, $complemento, 
    $email, $telefone, $login, $senhaHash
);

// Executa a inserção do usuário no banco de dados
if ($stmt->execute()) {
    error_log($stmt->error);
    http_response_code(500);
    exit("Não foi possível concluir o cadastro.");
    
   // Registra o sucesso do cadastro no sistema de logs
    $log = $conn->prepare("INSERT INTO mensagens_log (tipo, mensagem) VALUES ('cadastro', 'Cadastro realizado com sucesso')");
    if ($log) {
    if (!$log->execute()) {
        error_log($log->error);
    }
}

    header("Location: login.html?sucesso=cadastro");
    exit();
    
} else {
    // Registra a falha do cadastro no sistema de logs
    $log = $conn->prepare("INSERT INTO mensagens_log (tipo, mensagem) VALUES ('cadastro', 'Erro ao cadastrar usuário')");
   if ($log) {
    if (!$log->execute()) {
        error_log($log->error);
    }
}

    http_response_code(500);
exit("Não foi possível concluir o cadastro. Tente novamente.");
}

$stmt->close();
$conn->close();

header("Location: login.html?sucesso=cadastro");
exit();
?>
