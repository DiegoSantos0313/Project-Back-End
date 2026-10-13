<?php
session_start();
include 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha e-mail e senha.';
    } else {
        // Consulta segura
        $stmt = $conn->prepare("SELECT id, senha_hash FROM usuarios WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Verificação da senha
                if (password_verify($senha, $user['senha_hash'])) {

                    // Criar sessão do usuário
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $email;

                    // Redirecionar para validar o segundo fator
                    header('Location: segundo_fator.php');
                    exit;

                } else {
                    $erro = 'E-mail ou senha inválidos.';
                }

            } else {
                $erro = 'E-mail ou senha inválidos.';
            }

            $stmt->close();

        } else {
            // Caso a consulta falhe por algum motivo
            $erro = 'Erro interno. Tente novamente mais tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - UniCoffe</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #fbeedb;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .form-container {
      background: #fffaf3;
      padding: 2rem;
      border-radius: 1rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      width: 100%;
      max-width: 420px;
      animation: fadeIn 0.5s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    h2 {
      text-align: center;
      color: #4e342e;
      margin-bottom: 1.5rem;
    }
    form label {
      display: block;
      margin-bottom: 0.4rem;
      font-weight: bold;
      color: #6d4c41;
    }
    form input,
    form button {
      width: 100%;
      padding: 0.75rem;
      margin-bottom: 1rem;
      border: 1px solid #d7ccc8;
      border-radius: 0.5rem;
      font-size: 1rem;
    }
    form input:focus {
      border-color: #a1887f;
      box-shadow: 0 0 0 3px rgba(161, 136, 127, 0.2);
      outline: none;
    }
    form button {
      font-weight: bold;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    form button[type="submit"] {
      background-color: #6d4c41;
      color: white;
    }
    form button[type="submit"]:hover {
      background-color: #5d4037;
    }
    form button[type="reset"] {
      background-color: #d7ccc8;
      color: #4e342e;
    }
    form button[type="reset"]:hover {
      background-color: #c8b7af;
    }
    .extra-links {
      text-align: center;
    }
    .extra-links a {
      color: #4e342e;
      text-decoration: none;
      font-weight: 500;
      margin: 0 0.5rem;
    }
    .extra-links a:hover {
      text-decoration: underline;
    }
    .error-message {
      color: #c62828;
      text-align: center;
      margin-bottom: 1rem;
      background-color: #ffebee;
      padding: 0.5rem;
      border-radius: 0.5rem;
      font-size: 0.95rem;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Login</h2>

    <?php if ($erro): ?>
      <div class="error-message"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST" autocomplete="off">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required autofocus autocomplete="username" />

      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required autocomplete="current-password" />

      <button type="submit">Entrar</button>
      <button type="reset">Limpar</button>
    </form>

    <div class="extra-links">
      <a href="cadastro.php">Criar conta</a> |
      <a href="recuperar.php">Esqueci minha senha</a>
    </div>
  </div>
</body>
</html>
