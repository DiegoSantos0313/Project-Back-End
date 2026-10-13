<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configurações de conexão
$host = "localhost";
$db   = 'unicoffe_db';
$user = 'admin';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Função para limpar dados
function limpar($str) {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

// === CPF ===
function validarCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/^(.)\1+$/', $cpf)) return false;

    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

// Normalizar data
function normalizarData($data) {
    $data = trim($data);
    if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $data)) {
        $parts = explode('/', $data);
        return sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
    }
    if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $data)) {
        return $data;
    }
    return null;
}

// Normalizar telefone
function normalizarTelefone($tel) {
    $digits = preg_replace('/\D+/', '', $tel);
    $digits = ltrim($digits, '0');
    if (substr($digits, 0, 2) === '55') $digits = substr($digits, 2);
    return $digits;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Método inválido.");
}

// Coleta dos dados
$nome            = limpar($_POST['nome'] ?? '');
$dataNascimento  = limpar($_POST['dataNascimento'] ?? '');
$mae             = limpar($_POST['mae'] ?? '');
$cpf             = limpar($_POST['cpf'] ?? '');
$cep             = limpar($_POST['cep'] ?? '');
$endereco        = limpar($_POST['endereco'] ?? '');
$complemento     = limpar($_POST['complemento'] ?? '');
$email           = limpar($_POST['email'] ?? '');
$telefone_raw    = $_POST['telefone'] ?? '';
$login           = limpar($_POST['login'] ?? '');
$senha           = $_POST['senha'] ?? '';

// ========== VALIDAÇÕES ==========
if (mb_strlen($nome) < 5 || mb_strlen($nome) > 80) die("Nome inválido.");

$data_nasc_db = normalizarData($dataNascimento);
if (!$data_nasc_db) die("Data inválida.");

$idade = (new DateTime())->diff(new DateTime($data_nasc_db))->y;
if ($idade < 0 || $idade > 120) die("Data de nascimento inválida.");

if ($mae !== '' && mb_strlen($mae) < 5) die("Nome da mãe inválido.");

if (!validarCPF($cpf)) die("CPF inválido.");

$cep_digits = preg_replace('/\D/', '', $cep);
if (!preg_match('/^\d{8}$/', $cep_digits)) die("CEP inválido.");

if (mb_strlen($endereco) < 5) die("Endereço inválido.");

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) die("E-mail inválido.");

$telefone_digits = normalizarTelefone($telefone_raw);
if (!in_array(strlen($telefone_digits), [10,11])) die("Telefone inválido.");

if (!preg_match('/^[A-Za-z0-9_]{4,20}$/', $login)) die("Login inválido.");

if (strlen($senha) < 8 || strlen($senha) > 64) die("Senha fora do padrão.");
if (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha))
    die("Senha precisa ter pelo menos uma letra e um número.");


// Verificar e-mail/login duplicado
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? OR login = ? LIMIT 1");
$stmt->bind_param("ss", $email, $login);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    die("E-mail ou login já cadastrado.");
}
$stmt->close();

// Criar código de verificação
$codigo_verificacao = random_int(100000, 999999);

// Hash da senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Insert do usuário com código de verificação
$sql = "INSERT INTO usuarios 
    (nome, data_nascimento, nome_mae, cpf, cep, endereco, complemento, email, telefone, login, senha_hash, codigo_verificacao, verificado) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssssssss",
    $nome,
    $data_nasc_db,
    $mae,
    $cpf_digits,
    $cep_digits,
    $endereco,
    $complemento,
    $email,
    $telefone_digits,
    $login,
    $senhaHash,
    $codigo_verificacao
);

// Execução
if ($stmt->execute()) {

    // LOG mensagem no banco dizendo que o usuário está em verificação
    $log = $conn->prepare("INSERT INTO mensagens_log (tipo, mensagem) VALUES (?, ?)");
    if ($log) {
        $tipo = 'cadastro_pendente_verificacao';
        $mens = "Usuário '$login' cadastrado. Código de verificação gerado.";
        $log->bind_param("ss", $tipo, $mens);
        $log->execute();
        $log->close();
    }

    // Redireciona para página de verificação
    header("Location: verificar_conta.php?email=" . urlencode($email));
    exit();

} else {
    die("Erro ao cadastrar: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>
