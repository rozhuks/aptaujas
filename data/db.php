<?php

require __DIR__ . '/config.php';

// Savienojums ar datubazi
function db()
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $ex) {
            exit('Neizdevās pieslēgties datubāzei. Pārbaudi config.php');
        }
    }
    return $pdo;
}

function query($sql, $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

// Lietotaji 

// Atrod lietotaju pec lietotajvarda vai e-pasta
function find_user($login)
{
    return query('SELECT * FROM users WHERE username = ? OR email = ?', [$login, mb_strtolower($login)])->fetch();
}

function username_taken($username)
{
    return (bool) query('SELECT id FROM users WHERE username = ?', [$username])->fetch();
}

function email_taken($email)
{
    return (bool) query('SELECT id FROM users WHERE email = ?', [$email])->fetch();
}

// Parole tiek saglabata sifreta veida
function create_user($username, $email, $password)
{
    query('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)',
        [$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
    return (int) db()->lastInsertId();
}

// Aptaujas 

// Visas lietotaja aptaujas ar atbilzu skaitu
function get_user_surveys($userId)
{
    return query('SELECT s.*, (SELECT COUNT(*) FROM responses r WHERE r.survey_id = s.id) AS answers
                  FROM surveys s WHERE s.user_id = ? ORDER BY s.created_at DESC', [$userId])->fetchAll();
}

// Aptauj bet tikai ja ta pieder sim lietotajam
function get_survey($id, $userId)
{
    return query('SELECT * FROM surveys WHERE id = ? AND user_id = ?', [$id, $userId])->fetch();
}

function get_survey_by_code($code)
{
    return query('SELECT * FROM surveys WHERE code = ?', [$code])->fetch();
}

function count_responses($surveyId)
{
    return (int) query('SELECT COUNT(*) FROM responses WHERE survey_id = ?', [$surveyId])->fetchColumn();
}

// Jautajumi kopa ar atbilzu variantiem
function get_questions($surveyId)
{
    $questions = query('SELECT * FROM questions WHERE survey_id = ? ORDER BY position', [$surveyId])->fetchAll();
    foreach ($questions as &$q) {
        $q['choices'] = query('SELECT id, text FROM choices WHERE question_id = ? ORDER BY id', [$q['id']])->fetchAll();
    }
    return $questions;
}

// Saglaba jaunu aptauju vai atjauno esoso (ja ir $id)
function save_survey($userId, $title, $deadline, $questions, $id = null)
{
    db()->beginTransaction();

    if ($id) {
        query('UPDATE surveys SET title = ?, deadline = ? WHERE id = ?', [$title, $deadline, $id]);
        query('DELETE FROM questions WHERE survey_id = ?', [$id]);
    } else {
        $code = bin2hex(random_bytes(6)); // random kods saitei
        query('INSERT INTO surveys (user_id, title, deadline, code) VALUES (?, ?, ?, ?)', [$userId, $title, $deadline, $code]);
        $id = (int) db()->lastInsertId();
    }

    foreach ($questions as $i => $q) {
        query('INSERT INTO questions (survey_id, text, type, position) VALUES (?, ?, ?, ?)', [$id, $q['text'], $q['type'], $i]);
        $questionId = (int) db()->lastInsertId();
        foreach ($q['choices'] as $choice) {
            query('INSERT INTO choices (question_id, text) VALUES (?, ?)', [$questionId, $choice]);
        }
    }

    db()->commit();
}

function delete_survey($id)
{
    query('DELETE FROM surveys WHERE id = ?', [$id]);
}

// Atbildes 

function email_used($surveyId, $email)
{
    return (bool) query('SELECT id FROM responses WHERE survey_id = ? AND email = ?', [$surveyId, $email])->fetch();
}

// $answers ir saraksts ar [jautajuma_id, varianta_id, teksts]
function save_response($surveyId, $email, $answers)
{
    db()->beginTransaction();
    query('INSERT INTO responses (survey_id, email) VALUES (?, ?)', [$surveyId, $email]);
    $responseId = (int) db()->lastInsertId();
    foreach ($answers as $a) {
        query('INSERT INTO answers (response_id, question_id, choice_id, value) VALUES (?, ?, ?, ?)',
            [$responseId, $a[0], $a[1], $a[2]]);
    }
    db()->commit();
}

// Atbildes sagrupetas pa respondentiem
function get_responses($surveyId)
{
    $rows = query('SELECT r.id, r.email, r.submitted_at, a.question_id, a.value, c.text AS choice
                   FROM responses r
                   JOIN answers a ON a.response_id = r.id
                   LEFT JOIN choices c ON c.id = a.choice_id
                   WHERE r.survey_id = ? ORDER BY r.id, a.id', [$surveyId])->fetchAll();

    $result = [];
    foreach ($rows as $row) {
        $id = $row['id'];
        if (!isset($result[$id])) {
            $result[$id] = ['email' => $row['email'], 'time' => $row['submitted_at'], 'answers' => []];
        }
        $result[$id]['answers'][$row['question_id']][] = $row['choice'] ?? $row['value'];
    }
    return $result;
}
