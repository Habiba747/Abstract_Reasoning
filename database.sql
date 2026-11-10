-- Abstract Reasoning Quiz - database schema + seed questions
-- Import this file in phpMyAdmin (Import tab). It creates the database for you.

CREATE DATABASE IF NOT EXISTS abstract_reasoning
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE abstract_reasoning;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(40) NOT NULL,
  question TEXT NOT NULL,
  option_a VARCHAR(120) NOT NULL,
  option_b VARCHAR(120) NOT NULL,
  option_c VARCHAR(120) NOT NULL,
  option_d VARCHAR(120) NOT NULL,
  correct CHAR(1) NOT NULL,           -- 'A','B','C' or 'D'
  explanation VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  score TINYINT UNSIGNED NOT NULL,
  total TINYINT UNSIGNED NOT NULL,
  duration_sec INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attempt_answers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id INT UNSIGNED NOT NULL,
  question_id INT UNSIGNED NOT NULL,
  chosen CHAR(1) NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO questions (category, question, option_a, option_b, option_c, option_d, correct, explanation) VALUES
('Number Series','2, 4, 8, 16, ?','24','30','32','36','C','Each number doubles.'),
('Number Series','3, 6, 11, 18, 27, ?','36','38','40','42','B','Differences are 3, 5, 7, 9, so the next is +11.'),
('Number Series','1, 1, 2, 3, 5, 8, ?','11','12','13','14','C','Fibonacci: add the previous two numbers.'),
('Number Series','100, 91, 82, 73, ?','62','63','64','65','C','Subtract 9 each time.'),
('Number Series','2, 6, 12, 20, 30, ?','40','42','44','46','B','Differences grow by 2: 4, 6, 8, 10, so +12.'),
('Number Series','1, 4, 9, 16, 25, ?','30','34','36','49','C','Square numbers: 6 x 6 = 36.'),
('Letter Series','A, C, E, G, ?','H','I','J','K','B','Skip one letter each time.'),
('Letter Series','Z, X, V, T, ?','S','R','Q','P','B','Move back two letters each time.'),
('Letter Series','AB, CD, EF, GH, ?','HI','IJ','JK','KL','B','Consecutive letter pairs.'),
('Letter Series','B, E, H, K, ?','M','N','O','P','B','Skip two letters each time (+3).'),
('Odd One Out','Which one does not belong? Circle, Square, Triangle, Cube','Circle','Square','Triangle','Cube','D','Cube is 3D; the others are flat shapes.'),
('Odd One Out','Which one does not belong? 3, 5, 9, 11','3','5','9','11','C','9 is not prime.'),
('Odd One Out','Which one does not belong? Dog, Cat, Horse, Sparrow','Dog','Cat','Horse','Sparrow','D','Sparrow is a bird; the rest are mammals.'),
('Odd One Out','Which one does not belong? 16, 25, 36, 48','16','25','36','48','D','48 is not a perfect square.'),
('Analogy','Bird : Nest :: Bee : ?','Hive','Web','Den','Burrow','A','A bee lives in a hive.'),
('Analogy','Up : Down :: Left : ?','Back','Right','Front','Side','B','Opposites.'),
('Analogy','3 : 27 :: 4 : ?','16','48','64','81','C','Cube of the number: 4 x 4 x 4 = 64.'),
('Analogy','Book : Read :: Fork : ?','Cook','Eat','Cut','Plate','B','A book is used to read; a fork is used to eat.'),
('Pattern','▲ ● ■ ▲ ● ■ ▲ ● ?','▲','●','■','◆','C','The three-shape pattern repeats.'),
('Pattern','🔴 🔵 🔵 🔴 🔵 🔵 🔴 🔵 ?','🔴','🔵','🟢','🟡','B','Pattern is red, blue, blue repeating.'),
('Pattern','⬆️ ➡️ ⬇️ ⬅️ ⬆️ ➡️ ?','⬆️','➡️','⬇️','⬅️','C','Arrows rotate clockwise.'),
('Pattern','◐ ◑ ◐ ◑ ◐ ?','◐','◑','●','○','B','The two symbols alternate.'),
('Logic','All Bloops are Razzies. All Razzies are Lazzies. Are all Bloops definitely Lazzies?','Yes','No','Only some','Cannot be determined','A','Transitive: Bloops are inside Razzies, which are inside Lazzies.'),
('Logic','Row 1: 2, 4, 8. Row 2: 3, 6, 12. Row 3: 5, 10, ?','15','20','25','40','B','Each row is x, 2x, 4x.'),
('Logic','Tom is taller than Sam. Sam is taller than Ravi. Who is the shortest?','Tom','Sam','Ravi','Cannot tell','C','Tom > Sam > Ravi.'),
('Logic','5 machines make 5 widgets in 5 minutes. How long for 100 machines to make 100 widgets?','5 minutes','20 minutes','100 minutes','500 minutes','A','Each machine makes 1 widget in 5 minutes.');


-- Tracks questions already served to each user in each category.
CREATE TABLE IF NOT EXISTS question_usage (
  user_id INT UNSIGNED NOT NULL,
  category VARCHAR(40) NOT NULL,
  question_id INT UNSIGNED NOT NULL,
  used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, category, question_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
