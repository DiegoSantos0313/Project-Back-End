<?php
session_start();
require_once __DIR__ . '/conexao.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function responder(int $status, string $mensagem): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($mensagem);
}

function obterPost(string $campo): ?string
{
    $valor = $_POST[$campo] ?? '';

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

function normalizarData(string $data): ?string
{
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $partes)) {
        $dia = (int) $partes[1];
        $mes = (int) $partes[2];
        $ano = (int) $partes[3];
    } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data, $partes)) {
        $ano = (int) $partes[1];
        $mes = (int) $partes[2];
        $dia = (int) $partes[3];
    } else {
        return null;
    }

    if (!checkdate($mes, $dia, $ano)) {
        return null;
    }

    $normalizada = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);

    // Impede datas futuras.
    if ($normalizada > date('Y-m-d')) {
        return null;
    }

    return $normalizada;
}

function validarCpf(string $cpf): bool
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

function normalizarTelefone(string $telefone): string
{
    $digitos = preg_replace('/\D/', '', $telefone);

    // Remove o DDI 55 se o número tiver DDI e telefone brasileiro completos.
    if (
        (strlen($digitos) === 12 || strlen($digitos) === 13) &&
        substr($digitos, 0, 2) === '55'
    ) {
        $digitos = substr($digitos, 2);
    }

    return $digitos;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responder(405, 'Método não permitido.');
}

// O formulário precisa enviar um campo hidden chamado csrf_token.
$csrfToken = obterPost('csrf_token');

if (
    $csrfToken === null ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    responder(403, 'Sessão expirada ou requisição inválida. Atualize o formulário.');
}

$nome              = obterPost('nome');
$dataInformada     = obterPost('dataNascimento');
$mae               = obterPost('mae');
$cpfInformado      = obterPost('cpf');
$cepInformado      = obterPost('cep');
$endereco          = obterPost('endereco');
$complemento       = obterPost('complemento');
$email             = obterPost('email');
$telefoneInformado = obterPost('telefone');
$login             = obterPost('login');
$senha             = $_POST['senha'] ?? null;

$campos = [
    $nome,
    $dataInformada,
    $mae,
    $cpfInformado,
    $cepInformado,
    $endereco,
    $complemento,
    $email,
    $telefoneInformado,
    $login,
];

foreach ($campos as $campo) {
    if ($campo === null) {
        responder(400, 'Os dados enviados são inválidos.');
    }
}

if (!is_string($senha)) {
    responder(400, 'A senha informada é inválida.');
}

if (tamanhoTexto($nome) < 5 || tamanhoTexto($nome) > 80) {
    responder(400, 'O nome deve ter entre 5 e 80 caracteres.');
}

$dataNascimento = normalizarData($dataInformada);

if ($dataNascimento === null) {
    responder(400, 'Informe uma data de nascimento válida, que não seja futura.');
}

$dataNascimentoObj = new DateTimeImmutable($dataNascimento);
$idade = $dataNascimentoObj->diff(new DateTimeImmutable('today'))->y;

if ($idade > 120) {
    responder(400, 'A data de nascimento informada é inválida.');
}

if ($mae !== '' && (tamanhoTexto($mae) < 5 || tamanhoTexto($mae) > 80)) {
    responder(400, 'O nome da mãe deve ter entre 5 e 80 caracteres.');
}

if (!validarCpf($cpfInformado)) {
    responder(400, 'CPF inválido.');
}

$cpf = preg_replace('/\D/', '', $cpfInformado);
$cep = preg_replace('/\D/', '', $cepInformado);
$telefone = normalizarTelefone($telefoneInformado);

if (!preg_match('/^\d{8}$/', $cep)) {
    responder(400, 'Informe um CEP válido com 8 números.');
}

if (tamanhoTexto($endereco) < 5 || tamanhoTexto($endereco) > 180) {
    responder(400, 'O endereço deve ter entre 5 e 180 caracteres.');
}

if (tamanhoTexto($complemento) > 100) {
    responder(400, 'O complemento deve ter no máximo 100 caracteres.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    responder(400, 'Informe um e-mail válido.');
}

if (!in_array(strlen($telefone), [10, 11], true)) {
    responder(400, 'Informe um telefone válido com DDD.');
}

if (!preg_match('/^[A-Za-z0-9_]{4,20}$/', $login)) {
    responder(400, 'O login deve ter de 4 a 20 caracteres e conter apenas letras, números ou sublinhado.');
}

if (
    strlen($senha) < 8 ||
    strlen($senha) > 64 ||
    !preg_match('/[A-Za-z]/', $senha) ||
    !preg_match('/\d/', $senha)
) {
    responder(400, 'A senha deve ter de 8 a 64 caracteres e conter pelo menos uma letra e um número.');
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

if ($senhaHash === false) {
    error_log('Não foi possível gerar o hash da senha do cadastro.');
    responder(500, 'Não foi possível processar o cadastro. Tente novamente.');
}

$codigoVerificacao = (string) random_int(100000, 999999);

try {
    // A consulta ajuda a retornar uma mensagem amigável.
    // Índices UNIQUE no banco também são necessários para evitar duplicidades.
    $stmt = $conn->prepare(
        'SELECT id FROM usuarios
         WHERE email = ? OR login = ? OR cpf = ?
         LIMIT 1'
    );
    $stmt->bind_param('sss', $email, $login, $cpf);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        responder(409, 'E-mail, login ou CPF já cadastrado.');
    }

    $stmt->close();

    $sql = 'INSERT INTO usuarios
        (nome, data_nascimento, nome_mae, cpf, cep, endereco, complemento,
         email, telefone, login, senha_hash, codigo_verificacao, verificado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)';

    $stmt = $conn->prepare($sql);

    // Há 12 valores dinâmicos; por isso são usados 12 "s".
    $stmt->bind_param(
        'ssssssssssss',
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
        $senhaHash,
        $codigoVerificacao
    );

    $stmt->execute();
    $stmt->close();

    // O registro de log é complementar; sua falha não invalida o cadastro.
    try {
        $log = $conn->prepare(
            'INSERT INTO mensagens_log (tipo, mensagem) VALUES (?, ?)'
        );
        $tipoLog = 'cadastro_pendente_verificacao';
        $mensagemLog = 'Novo cadastro pendente de verificação.';
        $log->bind_param('ss', $tipoLog, $mensagemLog);
        $log->execute();
        $log->close();
    } catch (mysqli_sql_exception $e) {
        error_log('Falha ao registrar log do cadastro: ' . $e->getMessage());
    }

    unset($_SESSION['csrf_token']);

    header('Location: verificar_conta.php?email=' . urlencode($email));
    exit;
} catch (mysqli_sql_exception $e) {
    error_log('Erro ao cadastrar usuário: ' . $e->getMessage());

    // Trata também duplicidade detectada pelo índice UNIQUE do banco.
    if ((int) $e->getCode() === 1062) {
        responder(409, 'E-mail, login ou CPF já cadastrado.');
    }

    responder(500, 'Não foi possível concluir o cadastro. Tente novamente.');
}
