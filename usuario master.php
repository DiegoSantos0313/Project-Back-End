<?php
session_start();
include 'conexao.php'; // precisa retornar um objeto PDO em $conn

// --- SEGURANÇA ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'master') {
    header("Location: login.php");
    exit;
}

$busca = $_GET['busca'] ?? '';

// --- CONSULTA ---
$query = "SELECT * FROM usuarios WHERE perfil = 'comum'";
$params = [];

if (!empty($busca)) {
    $query .= " AND nome LIKE :busca";
    $params[':busca'] = "%" . $busca . "%";
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Consulta de Usuários - UniCoffee</title>
</head>
<body>
  <h2>Consulta de Usuários</h2>

  <form method="GET" action="">
    <input 
        type="text" 
        name="busca" 
        placeholder="Buscar por nome" 
        value="<?= htmlspecialchars($busca) ?>"
    >
    <button type="submit">Buscar</button>
  </form>

  <table border="1" cellpadding="5" cellspacing="0">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Login</th>
        <th>E-mail</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($usuarios)): ?>
        <?php foreach ($usuarios as $user): ?>
          <tr>
            <td><?= htmlspecialchars($user['nome']) ?></td>
            <td><?= htmlspecialchars($user['login']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td>
              <form 
                method="POST" 
                action="excluir_usuario.php" 
                onsubmit="return confirm('Confirma exclusão do usuário?');" 
                style="display:inline;"
              >
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                <button type="submit">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="4">Nenhum usuário encontrado.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <p><a href="principal.php">Voltar</a></p>
</body>
</html>
