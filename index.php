
<?php
require __DIR__ . '/config.php';
$uid = require_login();

$s = $pdo->prepare('SELECT COUNT(*) AS n,
                           COALESCE(MAX(score/total*100),0) AS best,
                           COALESCE(AVG(score/total*100),0) AS avg
                    FROM attempts WHERE user_id = ?');
$s->execute([$uid]);
$stats = $s->fetch();

$h = $pdo->prepare('SELECT id, score, total, duration_sec, created_at
                    FROM attempts
                    WHERE user_id = ?
                    ORDER BY created_at DESC, id DESC
                    LIMIT 20');
$h->execute([$uid]);
$history = $h->fetchAll();

$c = $pdo->prepare('SELECT q.category, COUNT(*) AS n,
                           SUM(aa.is_correct) AS right_n
                    FROM attempt_answers aa
                    JOIN attempts a ON a.id = aa.attempt_id
                    JOIN questions q ON q.id = aa.question_id
                    WHERE a.user_id = ?
                    GROUP BY q.category
                    ORDER BY q.category');
$c->execute([$uid]);
$cats = $c->fetchAll();

$title = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="wrap">

    <div class="hero card">
        <div class="hero-intro">
            <div class="eyebrow">YOUR DAILY BRAIN WORKOUT</div>

            <h1>Hi, <?= e($_SESSION['user_name']) ?> 👋</h1>

            <p class="muted">
                Ready to train your abstract reasoning?
                Challenge your pattern recognition, logic and problem-solving skills.
                
        </div>

        <div class="stats">
            <div class="stat">
                <b><?= (int)$stats['n'] ?></b>
                <span class="muted">Quizzes taken</span>
            </div>

            <div class="stat">
                <b><?= round($stats['best']) ?>%</b>
                <span class="muted">Best score</span>
            </div>

            <div class="stat">
                <b><?= round($stats['avg']) ?>%</b>
                <span class="muted">Average score</span>
            </div>
        </div>

        <h2 class="choose-title">Choose your challenge ✨</h2>

        <div class="category-grid">
            <?php
            $levels = [
                ['Easy', 'A gentle warm-up to get started.', 'easy-card', '🐣'],
                ['Medium', 'Build your reasoning skills.', 'medium-card', '🍀'],
                ['Hard', 'Put your logic to the test.', 'hard-card', '🧠'],
                ['Mix', 'A little bit of everything.', 'mix-card', '💡']
            ];

            foreach ($levels as [$name, $desc, $cls, $emoji]):
            ?>
                <a class="category-card <?= $cls ?>"
                   href="quiz.php?category=<?= strtolower($name) ?>">

                    <span class="category-icon"><?= $emoji ?></span>

                    <span class="category-name">
                        <?= e($name) ?>
                    </span>

                    <span class="category-desc">
                        <?= e($desc) ?>
                    </span>

                    <span class="category-action">
                        Take a quiz <b>→</b>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($cats): ?>
        <div class="card">
            <h2>Accuracy by category 📊</h2>

            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Category</th>
                        <th>Correct answers</th>
                        <th>Accuracy</th>
                    </tr>

                    <?php foreach ($cats as $r):
                        $pct = $r['n']
                            ? round($r['right_n'] / $r['n'] * 100)
                            : 0;
                    ?>
                        <tr>
                            <td><?= e($r['category']) ?></td>
                            <td>
                                <?= (int)$r['right_n'] ?>
                                /
                                <?= (int)$r['n'] ?>
                            </td>
                            <td>
                                <span class="pill"><?= $pct ?>%</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Your previous attempts 🏆</h2>

        <?php if (!$history): ?>
            <div class="empty-state">
                <p><strong>No attempts yet!</strong></p>
                <p>Take your first quiz and your results will appear here.</p>
            </div>
        <?php else: ?>

            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Score</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>

                    <?php foreach ($history as $r): ?>
                        <tr>
                            <td>
                                <?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?>
                            </td>

                            <td>
                                <span class="pill">
                                    <?= (int)$r['score'] ?>
                                    /
                                    <?= (int)$r['total'] ?>
                                </span>
                            </td>

                            <td>
                                <?= floor($r['duration_sec'] / 60) ?>m
                                <?= $r['duration_sec'] % 60 ?>s
                            </td>

                            <td>
                                <a href="result.php?id=<?= (int)$r['id'] ?>">
                                    Review →
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

        <?php endif; ?>
    </div>

</div>

</body>
</html>
