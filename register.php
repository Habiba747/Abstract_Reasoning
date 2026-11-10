<?php
require __DIR__ . '/config.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$error = '';
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($name === '' || mb_strlen($name) > 80) {
        $error = 'Please enter your name (max 80 characters).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists. Try logging in.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)')
                ->execute([$name, $email, $hash]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            header('Location: index.php');
            exit;
        }
    }
}
$title = 'Sign up';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap auth">
  <div class="card">
    <h1>Create account</h1>
    <p class="muted">Sign up to save your scores and see your progress.</p>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="on">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= e($name) ?>" required maxlength="80">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
      <label for="password">Password (8+ characters)</label>
      <input type="password" id="password" name="password" required minlength="8">
      <label for="password2">Confirm password</label>
      <input type="password" id="password2" name="password2" required minlength="8">
      <div class="row"><button class="btn" type="submit">Sign up</button>
      <span class="muted">Have an account? <a href="login.php">Log in</a></span></div>
    </form>
  </div>
</div>
</body></html>
