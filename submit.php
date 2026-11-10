<?php
require __DIR__ . '/config.php';
$uid = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
csrf_check();

$quiz = $_SESSION['quiz'] ?? null;
if (!$quiz || empty($quiz['ids'])) { header('Location: index.php'); exit; }

$given = json_decode($_POST['answers'] ?? '{}', true);
if (!is_array($given)) { $given = []; }

$ids = $quiz['ids'];
$in = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, correct FROM questions WHERE id IN ($in)");
$stmt->execute($ids);
$correct = [];
foreach ($stmt->fetchAll() as $r) { $correct[(int)$r['id']] = $r['correct']; }

$score = 0;
$rows = [];
foreach ($ids as $qid) {
    $choice = $given[(string)$qid] ?? null;
    $choice = (is_string($choice) && in_array($choice, ['A', 'B', 'C', 'D'], true)) ? $choice : null;
    $ok = ($choice !== null && $choice === ($correct[$qid] ?? null)) ? 1 : 0;
    $score += $ok;
    $rows[] = [$qid, $choice, $ok];
}
$duration = max(0, min(86400, time() - (int)$quiz['started']));

$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO attempts (user_id, score, total, duration_sec) VALUES (?, ?, ?, ?)')
        ->execute([$uid, $score, count($ids), $duration]);
    $aid = (int)$pdo->lastInsertId();
    $usage = $pdo->prepare('INSERT IGNORE INTO question_usage (user_id, category, question_id) VALUES (?, ?, ?)');
    foreach ($ids as $qid) { $usage->execute([$uid, $quiz['category'] ?? 'Easy', $qid]); }
    $ins = $pdo->prepare('INSERT INTO attempt_answers (attempt_id, question_id, chosen, is_correct) VALUES (?, ?, ?, ?)');
    foreach ($rows as [$qid, $choice, $ok]) { $ins->execute([$aid, $qid, $choice, $ok]); }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    http_response_code(500);
    exit('Could not save your result. Please try again.');
}

unset($_SESSION['quiz']);   // prevents resubmitting the same quiz
header('Location: result.php?id=' . $aid);
exit;
