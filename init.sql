CREATE DATABASE IF NOT EXISTS quizizz_db;
USE quizizz_db;

CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option CHAR(1) NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS game_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    pin VARCHAR(6) NOT NULL UNIQUE,
    status ENUM('waiting', 'active', 'finished') DEFAULT 'waiting',
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    nickname VARCHAR(100) NOT NULL,
    score INT DEFAULT 0,
    FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE
);

-- Data Sampel Kuis
INSERT INTO quizzes (id, title) VALUES (1, 'Kuis Pengetahuan Umum');
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES
(1, 'Apa ibu kota Indonesia?', 'Bandung', 'Jakarta', 'Surabaya', 'Medan', 'B'),
(1, 'Berapakah 5 x 6?', '20', '25', '30', '35', 'C');