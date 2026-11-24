<?php
session_start();
require_once __DIR__ . '/conexao.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function encerrar(int $status, string $mensagem): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($mensagem);
}

function campoPost(string $nome): ?string
{
    $valor = $_POST[$nome] ?? '';

    return is_string($valor) ? trim($valor) : null;
}

function tamanhoTexto(string $texto): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($texto, 'UTF-8');
    }

    $quantidade = preg_match_all('/./us', $texto, $correspondencias);

    return $quantidade === false ? 0 : $quantidade;
}

function cpfValido(string $cpf): bool
{
    if (!preg_match('/^[0-9.\-\s]+$/', $cpf)) {
        return false;
    }

    $cpf = preg_replace('/\D/', '', $cpf);

    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($posicao = 9; $posicao <= 10; $posicao++) {
        $soma = 0;

        for ($indice = 0; $indice < $posicao; $indice++) {
            $soma += (int) $cpf[$indice] * (($posicao + 1) - $indice);
        }

        $digito = ($soma * 10) % 11;
        $digito = $digito === 10 ? 0 : $digito;

        if ((int) $cpf[$posicao] !== $digito) {
            return false;
        }
    }

    return true;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    encerrar(405, 'Método não permitido.');
}

$csrfToken = campoPost('csrf_token');

if (
    $csrfToken === null ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    encerrar(403, 'Sessão expirada ou requisição inválida. Atualize o formulário e tente novamente.');
}

$nome           = campoPost('nome');
$dataNascimento = campoPost('dataNascimento');
$mae            = campoPost('mae');
$cpfInformado   = campoPost('cpf');
$cepInformado   = campoPost('cep');
$endereco       = campoPost('endereco');
$complemento    = campoPost('complemento');
$email          = campoPost('email');
$telefoneInformado = campoPost('telefone');
$login          = campoPost('login');
$senha          = $_POST['senha'] ?? null;

if (
    $nome === null ||
    $dataNascimento === null ||
    $mae === null ||
    $cpfInformado === null ||
    $cepInformado === null ||
    $endereco === null ||
    $complemento === null ||
    $email === null ||
    $telefoneInformado === null ||
    $login === null ||
    !is_string($senha)
) {
    encerrar(400, 'Os dados enviados são inválidos.');
}

if (tamanhoTexto($nome) < 3 || tamanhoTexto($nome) > 80) {
    encerrar(400, 'Informe um nome com no mínimo 3 e no máximo 80 caracteres.');
}

if ($mae === '' || tamanhoTexto($mae) > 80) {
    encerrar(400, 'Informe o nome da mãe, com até 80 caracteres.');
}

if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNascimento) ||
    !checkdate(
        (int) substr($dataNascimento, 5, 2),
        (int) substr($dataNascimento, 8, 2),
        (int) substr($dataNascimento, 0, 4)
    ) ||
    $dataNascimento > date('Y-m-d')
) {
    encerrar(400, 'Informe uma data de nascimento válida.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    encerrar(400, 'Informe um e-mail válido.');
}

if (
    !preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $login)
) {
    encerrar(400, 'O login deve ter de 3 a 30 caracteres e conter apenas letras, números, ponto, hífen ou sublinhado.');
}

if (strlen($senha) < 8) {
    encerrar(400, 'A senha deve ter pelo menos 8 caracteres.');
}

if (tamanhoTexto($endereco) < 3 || tamanhoTexto($endereco) > 180) {
    encerrar(400, 'Informe um endereço válido, com até 180 caracteres.');
}

if (tamanhoTexto($complemento) > 100) {
    encerrar(400, 'O complemento deve ter até 100 caracteres.');
}

if (!cpfValido($cpfInformado)) {
    encerrar(400, 'Informe um CPF válido.');
}

$cpf = preg_replace('/\D/', '', $cpfInformado);
$cep = preg_replace('/\D/', '', $cepInformado);
$telefone = preg_replace('/\D/', '', $telefoneInformado);

if (strlen($cep) !== 8) {
    encerrar(400, 'Informe um CEP válido com 8 números.');
}

if (!in_array(strlen($telefone), [10, 11], true)) {
    encerrar(400, 'Informe um telefone válido com DDD.');
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

if ($senhaHash === false) {
    error_log('Não foi possível gerar o hash da senha do cadastro.');
    encerrar(500, 'Não foi possível processar o cadastro. Tente novamente.');
}

try {
    $stmt = $conn->prepare(
        'SELECT id FROM usuarios WHERE email = ? OR login = ? OR cpf = ? LIMIT 1'
    );
    $stmt->bind_param('sss', $email, $login, $cpf);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        encerrar(409, 'E-mail, login ou CPF já cadastrado.');
    }

    $stmt->close();

    $sql = 'INSERT INTO usuarios
        (nome, data_nascimento, nome_mae, cpf, cep, endereco, complemento, email, telefone, login, senha_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'sssssssssss',
        $nome,
        $dataNascimento,
        $mae,
        $cpf,
        $cep,
        $endereco,
        $complemento,
        $email,
        $telefone,
        $login,
        $senhaHash
    );
    $stmt->execute();
    $stmt->close();

    unset($_SESSION['csrf_token']);

    header('Location: login.html?sucesso=cadastro');
    exit;
} catch (mysqli_sql_exception $e) {
    error_log('Erro ao cadastrar usuário: ' . $e->getMessage());

    // Também cobre duplicidade ocorrida entre a consulta e a inserção.
    if ((int) $e->getCode() === 1062) {
        encerrar(409, 'E-mail, login ou CPF já cadastrado.');
    }

    encerrar(500, 'Não foi possível concluir o cadastro. Tente novamente.');
}
