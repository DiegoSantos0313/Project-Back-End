<?php

/**
 * ==========================================================
 * Arquivo: conexao.php
 * Projeto: UniCoffee
 * Descrição: Responsável pela conexão com o banco de dados.
 * ==========================================================
 */

// Exibe erros do MySQL como exceções
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
|--------------------------------------------------------------------------
| Configurações do Banco de Dados
|--------------------------------------------------------------------------
| Em produção, o ideal é armazenar essas informações em um arquivo
| de configuração ou em variáveis de ambiente (.env).
*/

$host    = "localhost";
$usuario = "root";          // Recomenda-se um usuário específico da aplicação
$senha   = "";               // Utilize uma senha forte em produção
$banco   = "unicoffe_db";

try {

    // Cria a conexão
    $conn = new mysqli($host, $usuario, $senha, $banco);

    // Define o conjunto de caracteres
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {

    // Registra o erro internamente
    error_log(
        "[" . date("Y-m-d H:i:s") . "] Erro de conexão: " .
        $e->getMessage() . PHP_EOL,
        3,
        __DIR__ ."logs/mysql.log"
    );

    // Mensagem amigável para o usuário
    http_response_code(500);

    die("
        <h2>Erro de conexão</h2>
        <p>Não foi possível conectar ao banco de dados.</p>
        <p>Tente novamente em alguns minutos.</p>
    ");
}
?>
