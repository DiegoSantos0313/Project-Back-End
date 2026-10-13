<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.html');
  exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Segundo Fator - UniCoffee</title>
</head>
<body>
  <h2>Confirmação de Segurança (2FA)</h2>
  <p>Insira o código enviado para seu dispositivo.</p>
  <form action="validar_2fa.php" method="POST">
    <label for="codigo">Código:</label>
    <input type="text" id="codigo" name="codigo" required maxlength="6" />
    <button type="submit">Validar</button>
  </form>
</body>
</html>