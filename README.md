# Abstract Reasoning Quiz (PHP + MySQL)

Users sign up with email + password, take randomized abstract reasoning MCQs, and see
their previous scores and per-category accuracy every time they log in.

## Setup (XAMPP / WAMP / Laragon)

1. Copy this folder (`abstract-reasoning`) into `htdocs` (XAMPP) or `www` (WAMP).
2. Start **Apache** and **MySQL**.
3. Open **phpMyAdmin** -> **Import** -> choose `database.sql` -> **Go**.
   This creates the `abstract_reasoning` database, all tables and 26 questions.
4. If your MySQL user/password is not `root` / empty, edit `config.php`.
5. Visit `http://localhost/abstract-reasoning/register.php`.

## Files

| File | Purpose |
|------|---------|
| `database.sql` | Tables: users, questions, attempts, attempt_answers + seed questions |
| `config.php` | DB connection, sessions, CSRF + helper functions |
| `register.php` / `login.php` / `logout.php` | Authentication |
| `index.php` | Dashboard: stats, category accuracy, attempt history |
| `quiz.php` | Quiz page (one question at a time, timer) |
| `submit.php` | Grades answers on the server and saves the attempt |
| `result.php` | Score and answer review |

## Adding questions

In phpMyAdmin open the `questions` table -> **Insert**. Fill category, question,
four options and `correct` (A, B, C or D). Each quiz picks 10 at random
(change `QUESTIONS_PER_QUIZ` in `config.php`).

## Security notes

- Passwords are stored with `password_hash()` (bcrypt), never in plain text.
- All queries use prepared statements; output is escaped; forms use CSRF tokens.
- Correct answers stay on the server and are only revealed after submitting.
- Before putting this online: use a dedicated MySQL user (not root), enable HTTPS,
  and turn off `display_errors` in php.ini.


## Updated question bank
After importing `database.sql`, import `questions_seed.sql` in phpMyAdmin to add 320 questions (80 each for Easy, Medium, Hard and Mix). Each quiz serves 10 unseen questions in the selected category per user. After all questions in that category have been used, a fresh cycle begins.
