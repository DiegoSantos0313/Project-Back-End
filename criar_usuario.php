<?php
include 'conexao.php';

// Dados do usuário
$email = 'teste@exemplo.com';
$senha = '123456'; // senha original

// Gera hash seguro da senha
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// Prepara o INSERT
$stmt = $conn->prepare("INSERT INTO usuarios (email, senha_hash) VALUES (?, ?)");

if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}

// Faz o bind dos parâmetros
$stmt->bind_param("ss", $email, $senha_hash);

// Executa
if ($stmt->execute()) {
    echo "Usuário criado com sucesso!";
} else {
    echo "Erro ao criar usuário: " . $stmt->error;
}

// Fecha statement e conexão (boa prática)
$stmt->close();
$conn->close();
?>
