<?php
session_start();
include 'conexao.php';

$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($senha, $user['senha'])) {

        // login ok
        $_SESSION['usuario_id'] = $user['id'];

        header("Location: painel.php");
        exit;
    }
}

// se falhar:
header("Location: login.php?erro=login");
exit;
?>
