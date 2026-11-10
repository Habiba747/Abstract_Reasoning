<?php
require __DIR__ . '/config.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, name, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($pass, PASSWORD_DEFAULT), $user['id']]);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: index.php');
        exit;
    }
    $error = 'Incorrect email or password.';
}
$title = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap auth">
  <div class="card">
    <h1>Welcome back</h1>
    <p class="muted">Log in to see your previous scores.</p>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <div class="row"><button class="btn" type="submit">Log in</button>
      <span class="muted">New here? <a href="register.php">Sign up</a></span></div>
    </form>
  </div>
</div>
</body></html>
