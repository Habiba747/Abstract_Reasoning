
<?php
require __DIR__ . '/config.php';

$uid = require_login();

// Available categories
$allowed = [
    'easy' => 'Easy',
    'medium' => 'Medium',
    'hard' => 'Hard',
    'mix' => 'Mix'
];

$slug = strtolower($_GET['category'] ?? 'easy');

if (!isset($allowed[$slug])) {
    $slug = 'easy';
}

$category = $allowed[$slug];

// Find questions already attempted by this user
$used = $pdo->prepare(
    'SELECT question_id FROM question_usage
     WHERE user_id = ? AND category = ?'
);

$used->execute([$uid, $category]);

$usedIds = array_map(
    'intval',
    $used->fetchAll(PDO::FETCH_COLUMN)
);

// Count questions in this category
$totalStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM questions WHERE category = ?'
);

$totalStmt->execute([$category]);

$totalInCategory = (int)$totalStmt->fetchColumn();

if ($totalInCategory === 0) {
    exit('No questions found in this category. Please import your question bank.');
}

// Reset the question cycle when all questions have been used
if (count($usedIds) >= $totalInCategory) {
    $pdo->prepare(
        'DELETE FROM question_usage
         WHERE user_id = ? AND category = ?'
    )->execute([$uid, $category]);

    $usedIds = [];
}

// Number of questions per quiz
$limit = (int)QUESTIONS_PER_QUIZ;

// Select question text and image paths
$sql = '
    SELECT
        id,
        category,
        question,
        option_a,
        option_b,
        option_c,
        option_d,
        question_image,
        option_a_image,
        option_b_image,
        option_c_image,
        option_d_image
    FROM questions
    WHERE category = ?
';

$params = [$category];

if (!empty($usedIds)) {
    $placeholders = implode(
        ',',
        array_fill(0, count($usedIds), '?')
    );

    $sql .= " AND id NOT IN ($placeholders)";

    $params = array_merge($params, $usedIds);
}

$sql .= ' ORDER BY RAND() LIMIT ' . $limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    exit('No questions available in this category.');
}

// Store quiz information in session
$_SESSION['quiz'] = [
    'ids' => array_map(
        fn($r) => (int)$r['id'],
        $rows
    ),
    'started' => time(),
    'category' => $category,
];

// Prepare questions for JavaScript
$payload = array_map(function ($r) {
    return [
        'id' => (int)$r['id'],
        'category' => $r['category'],
        'question' => $r['question'],

        'questionImage' => $r['question_image'],

        'options' => [
            'A' => [
                'text' => $r['option_a'],
                'image' => $r['option_a_image']
            ],
            'B' => [
                'text' => $r['option_b'],
                'image' => $r['option_b_image']
            ],
            'C' => [
                'text' => $r['option_c'],
                'image' => $r['option_c_image']
            ],
            'D' => [
                'text' => $r['option_d'],
                'image' => $r['option_d_image']
            ]
        ]
    ];
}, $rows);

$title = 'Quiz';

require __DIR__ . '/includes/header.php';
?>

<div class="wrap">

    <div class="card">

        <div class="row" style="margin-top:0">

            <span class="pill" id="cat">
                <?= e($category) ?> level
            </span>

            <span class="muted">
                <span id="num">1</span>
                /
                <?= count($payload) ?>
                ·
                <span id="timer">0:00</span>
            </span>

        </div>

        <div class="progress">
            <div id="bar"></div>
        </div>

        <!-- Question text -->
        <div class="qtext" id="qtext"></div>

        <!-- Question image -->
        <div id="questionImageBox" class="question-image-box"></div>

        <!-- Answer options -->
        <div class="opts" id="opts"></div>

        <div class="row">

            <button
                class="btn ghost"
                id="prev"
                type="button"
            >
                Back
            </button>

            <button
                class="btn"
                id="next"
                type="button"
            >
                Next
            </button>

        </div>

    </div>

    <form id="f" method="post" action="submit.php">

        <input
            type="hidden"
            name="csrf"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="answers"
            id="answers"
        >

    </form>

</div>

<script>
const Q = <?= json_encode(
    $payload,
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
) ?>;

const answers = {};

let i = 0;

const $ = id => document.getElementById(id);


// Create an image safely
function createImage(path, className, altText) {

    if (!path) {
        return null;
    }

    const img = document.createElement('img');

    img.src = path;
    img.className = className;
    img.alt = altText;

    img.onerror = function () {
        this.style.display = 'none';
    };

    return img;
}


// Display current question
function render() {

    const q = Q[i];

    $('cat').textContent = q.category;

    $('num').textContent = i + 1;

    $('qtext').textContent = q.question;

    $('bar').style.width =
        ((i + 1) / Q.length * 100) + '%';


    // Display question image if available
    const imageBox = $('questionImageBox');

    imageBox.innerHTML = '';

    if (q.questionImage) {

        const questionImg = createImage(
            q.questionImage,
            'question-image',
            'Question diagram'
        );

        if (questionImg) {
            imageBox.appendChild(questionImg);
        }
    }


    // Display answer options
    const box = $('opts');

    box.innerHTML = '';

    Object.entries(q.options).forEach(([key, option]) => {

        const button = document.createElement('button');

        button.type = 'button';

        button.className =
            'opt' +
            (answers[q.id] === key ? ' sel' : '');


        // Option letter
        const letter = document.createElement('span');

        letter.className = 'k';

        letter.textContent = key;

        button.appendChild(letter);


        // Option text
        if (option.text) {

            const text = document.createElement('span');

            text.textContent = option.text;

            button.appendChild(text);
        }


        // Option image
        if (option.image) {

            const optionImg = createImage(
                option.image,
                'option-image',
                'Option ' + key
            );

            if (optionImg) {
                button.appendChild(optionImg);
            }
        }


        // Select answer
        button.onclick = function () {

            answers[q.id] = key;

            render();
        };

        box.appendChild(button);

    });


    // Back button
    $('prev').style.visibility =
        i === 0 ? 'hidden' : 'visible';


    // Next / Finish button
    $('next').textContent =
        i === Q.length - 1 ? 'Finish' : 'Next';

}


// Previous question
$('prev').onclick = function () {

    if (i > 0) {

        i--;

        render();
    }
};


// Next question or submit quiz
$('next').onclick = function () {

    if (i < Q.length - 1) {

        i++;

        render();

        return;
    }

    const unanswered = Q.filter(
        q => !answers[q.id]
    ).length;

    if (
        unanswered &&
        !confirm(
            unanswered +
            ' question(s) unanswered. Finish anyway?'
        )
    ) {
        return;
    }

    $('answers').value = JSON.stringify(answers);

    $('f').submit();

};


// Quiz timer
const t0 = Date.now();

setInterval(function () {

    const seconds = Math.floor(
        (Date.now() - t0) / 1000
    );

    const minutes = Math.floor(seconds / 60);

    const remainingSeconds = seconds % 60;

    $('timer').textContent =
        minutes +
        ':' +
        String(remainingSeconds).padStart(2, '0');

}, 500);


// Start quiz
render();

</script>

</body>
</html>
