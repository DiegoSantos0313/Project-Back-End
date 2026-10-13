<?php
session_start();

// Impede acesso direto sem iniciar 2FA
if (!isset($_SESSION['2fa_usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Evita que o navegador use página antiga em cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Confirmação de Segurança - 2FA</title>
</head>
<body>
    <h2>Confirmação de Segurança (2FA)</h2>
    <p>Digite o código de 6 dígitos enviado para seu dispositivo.</p>

    <form action="verificar_2fa_processa.php" method="POST">
        <label for="codigo_2fa">Código do segundo fator:</label><br><br>
        
        <input 
            type="text"
            id="codigo_2fa"
            name="codigo_2fa"
            required
            pattern="\d{6}"
            maxlength="6"
            autocomplete="off"
            inputmode="numeric"
            style="padding:6px;"
        >
        
        <br><br>
        <button type="submit">Validar</button>
    </form>

    <p><a href="login.php">Cancelar e voltar</a></p>
</body>
</html>
