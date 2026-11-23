<?php
session_start();

if (!isset($_SESSION['2fa_usuario_id'])) {
  header('Location: login.php');
  exit;
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erro = ($_GET['erro'] ?? '') === 'cod_2fa_invalido'
  ? 'O código informado é inválido. Tente novamente.'
  : '';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Segundo Fator - UniCoffee</title>
</head>
<body>
  <main>
    <h2>Confirmação de Segurança (2FA)</h2>
    <p>Insira o código de seis dígitos do seu aplicativo autenticador.</p>

    <?php if ($erro): ?>
      <p role="alert" style="color: #b00020;"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form action="verificar_2fa_processa.php" method="POST" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
      <label for="codigo_2fa">Código:</label>
      <input type="text" id="codigo_2fa" name="codigo_2fa" required inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autofocus aria-describedby="codigo-ajuda">
      <small id="codigo-ajuda">Use os seis dígitos exibidos no aplicativo autenticador.</small>
      <button type="submit">Validar</button>
    </form>

    <p><a href="logout.php">Cancelar e sair</a></p>
  </main>
</body>
</html>
