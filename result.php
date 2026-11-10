<?php
require __DIR__ . '/config.php';
$uid = require_login();

$id = (int)($_GET['id'] ?? 0);
$a = $pdo->prepare('SELECT id, score, total, duration_sec, created_at FROM attempts WHERE id = ? AND user_id = ?');
$a->execute([$id, $uid]);
$attempt = $a->fetch();
if (!$attempt) { http_response_code(404); exit('Result not found.'); }

$r = $pdo->prepare('SELECT q.category, q.question, q.option_a, q.option_b, q.option_c, q.option_d,
                           q.correct, q.explanation, aa.chosen, aa.is_correct
                    FROM attempt_answers aa JOIN questions q ON q.id = aa.question_id
                    WHERE aa.attempt_id = ? ORDER BY aa.id');
$r->execute([$id]);
$answers = $r->fetchAll();

$pct = $attempt['total'] ? round($attempt['score'] / $attempt['total'] * 100) : 0;
$msg = $pct >= 80 ? 'Excellent reasoning!' : ($pct >= 50 ? 'Good job, keep practising.' : 'Keep going, patterns get easier with practice.');

$title = 'Result';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
  <div class="card" style="text-align:center">
    <div class="big"><?= (int)$attempt['score'] ?> / <?= (int)$attempt['total'] ?></div>
    <p><strong><?= $pct ?>%</strong> · <?= e($msg) ?></p>
    <p class="muted">Time: <?= floor($attempt['duration_sec'] / 60) ?>m <?= $attempt['duration_sec'] % 60 ?>s</p>
    <a class="btn" href="quiz.php">Try another quiz</a>
    <a class="btn ghost" href="index.php">Dashboard</a>
  </div>

  <div class="card">
    <h2>Review</h2>
    <?php foreach ($answers as $n => $x):
      $opt = ['A' => $x['option_a'], 'B' => $x['option_b'], 'C' => $x['option_c'], 'D' => $x['option_d']]; ?>
      <div class="review <?= $x['is_correct'] ? 'good' : 'bad' ?>">
        <span class="pill"><?= e($x['category']) ?></span>
        <p><strong><?= $n + 1 ?>.</strong> <?= e($x['question']) ?></p>
        <p>Your answer:
          <?= $x['chosen'] ? e($x['chosen'] . ') ' . $opt[$x['chosen']]) : '<em>not answered</em>' ?>
          <?= $x['is_correct'] ? '✅' : '❌' ?></p>
        <?php if (!$x['is_correct']): ?>
          <p>Correct answer: <strong><?= e($x['correct'] . ') ' . $opt[$x['correct']]) ?></strong></p>
        <?php endif; ?>
        <p class="muted"><?= e($x['explanation']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body></html>
