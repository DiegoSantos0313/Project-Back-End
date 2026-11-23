<?php

/**
 * Arquivo: conexao.php
 * Projeto: UniCoffee
 * Descrição: conexão com o banco de dados usando MySQLi.
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
 * Em desenvolvimento local, os valores padrão podem ser usados.
 * Em produção, configure DB_HOST, DB_USER, DB_PASSWORD e DB_NAME
 * nas variáveis de ambiente do servidor.
 */
$host = getenv('DB_HOST') ?: 'localhost';
$usuario = getenv('DB_USER') ?: 'root';
$senha = getenv('DB_PASSWORD');
$banco = getenv('DB_NAME') ?: 'unicoffe_db';

if ($senha === false) {
    $senha = ''; // Padrão comum em instalações locais de XAMPP.
}

try {
    $conn = new mysqli($host, $usuario, $senha, $banco);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Registra o detalhe apenas no log configurado para o PHP.
    error_log('Erro de conexão com o banco de dados: ' . $e->getMessage());

    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');

    exit(
        '<h2>Erro de conexão</h2>' .
        '<p>Não foi possível conectar ao banco de dados. Tente novamente mais tarde.</p>'
    );
}
