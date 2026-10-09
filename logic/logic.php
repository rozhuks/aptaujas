<?php

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require __DIR__ . '/../data/db.php';

// Palig funkcijas

// Drosa teksta izvade 
function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// Datums formata 09/10/2026
function show_date($date)
{
    return date('d/m/Y', strtotime($date));
}

// Vai aptauju vel var aizpildit
function is_open($survey)
{
    return date('Y-m-d') <= $survey['deadline'];
}

// Saite uz aptaujas aizpildisanu
function survey_link($code)
{
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return 'http://' . $_SERVER['HTTP_HOST'] . $dir . '/fill.php?code=' . $code;
}

// Iss pazinojums kas paradas nakamaja lapa
function flash($text, $type = 'ok')
{
    $_SESSION['flash'] = ['text' => $text, 'type' => $type];
}

function get_flash()
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_field()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}

function csrf_check()
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        exit('Nederīgs pieprasījums. Atjauno lapu un mēģini vēlreiz.');
    }
}

// Pieteiksanas 

function login_user($id, $username)
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['username'] = $username;
}

function user_id()
{
    return $_SESSION['user_id'] ?? null;
}

function require_login()
{
    if (!user_id()) {
        redirect('login.php');
    }
}

// Validacija 
// Katra funkcija atgriez kludas tekstu vai null, ja viss ir kartiba

// Lietotajvards: 3-30 simboli, tikai burti, cipari un _
function validate_username($value)
{
    return preg_match('/^[A-Za-z0-9_]{3,30}$/', $value) ? null : 'Lietotājvārdam jābūt 3–30 simboli (burti, cipari, _).';
}

// E-pasts pec RFC 5322, lidz 254 simboliem
function validate_email($value)
{
    if (strlen($value) > 254 || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return 'Nepareiza e-pasta adrese.';
    }
    return null;
}

// Parole: 8-64 simboli (NIST SP 800-63B)
function validate_password($value)
{
    $len = mb_strlen($value);
    return ($len >= 8 && $len <= 64) ? null : 'Parolei jābūt no 8 līdz 64 simboliem.';
}

// Termins: ISO 8601 datums, nav pagatne, ne talak par 1 gadu
function validate_deadline($value)
{
    $date = DateTime::createFromFormat('!Y-m-d', (string) $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        return 'Nepareizs termiņa datums.';
    }
    $today = new DateTime('today');
    if ($date < $today) {
        return 'Termiņš nevar būt pagātnē.';
    }
    if ($date > (clone $today)->modify('+1 year')) {
        return 'Termiņš nevar būt garāks par 1 gadu.';
    }
    return null;
}

// Satira jautajumus, kas atnak no formas
function clean_questions($raw)
{
    $clean = [];
    foreach (is_array($raw) ? $raw : [] as $q) {
        $type = is_string($q['type'] ?? null) ? $q['type'] : '';
        $text = is_string($q['text'] ?? null) ? trim($q['text']) : '';
        $choices = [];
        if (($type === 'single' || $type === 'multiple') && is_array($q['choices'] ?? null)) {
            foreach ($q['choices'] as $c) {
                if (is_string($c) && trim($c) !== '') {
                    $choices[] = trim($c);
                }
            }
        }
        $clean[] = ['text' => $text, 'type' => $type, 'choices' => $choices];
    }
    return $clean;
}

// Parbauda visu aptauju, atgriez kludu sarakstu
function validate_survey($title, $deadline, $questions)
{
    $errors = [];

    $len = mb_strlen($title);
    if ($len < 3 || $len > 100) {
        $errors[] = 'Aptaujas nosaukumam jābūt no 3 līdz 100 simboliem.';
    }
    if ($err = validate_deadline($deadline)) {
        $errors[] = $err;
    }
    if (count($questions) < 1 || count($questions) > 30) {
        $errors[] = 'Aptaujā jābūt no 1 līdz 30 jautājumiem.';
    }

    foreach ($questions as $i => $q) {
        $nr = $i + 1;
        $len = mb_strlen($q['text']);
        if ($len < 3 || $len > 255) {
            $errors[] = "$nr. jautājumam jābūt no 3 līdz 255 simboliem.";
        }
        if (!in_array($q['type'], ['text', 'single', 'multiple', 'scale'], true)) {
            $errors[] = "$nr. jautājumam nav izvēlēts atbilžu veids.";
        }
        if ($q['type'] === 'single' || $q['type'] === 'multiple') {
            if (count($q['choices']) < 2 || count($q['choices']) > 10) {
                $errors[] = "$nr. jautājumam vajag no 2 līdz 10 atbilžu variantiem.";
            }
            foreach ($q['choices'] as $c) {
                if (mb_strlen($c) > 100) {
                    $errors[] = "$nr. jautājuma atbilde ir garāka par 100 simboliem.";
                    break;
                }
            }
        }
    }
    return $errors;
}

// Parbauda respondenta atbildes, atgriez [kludas, tiras atbildes]
function validate_answers($questions, $input)
{
    $errors = [];
    $clean = [];

    foreach ($questions as $i => $q) {
        $nr = $i + 1;
        $value = $input[$q['id']] ?? null;
        $ids = array_map('intval', array_column($q['choices'], 'id'));

        if ($q['type'] === 'text') {
            $text = is_string($value) ? trim($value) : '';
            if ($text === '' || mb_strlen($text) > 1000) {
                $errors[] = "$nr. jautājums: atbildei jābūt no 1 līdz 1000 simboliem.";
            } else {
                $clean[] = [$q['id'], null, $text];
            }
        } elseif ($q['type'] === 'scale') {
            $n = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
            if ($n === false) {
                $errors[] = "$nr. jautājums: izvēlies vērtību no 1 līdz 5.";
            } else {
                $clean[] = [$q['id'], null, (string) $n];
            }
        } elseif ($q['type'] === 'single') {
            // Izveletajam variantam jabut no si jautajuma
            if (!is_scalar($value) || !in_array((int) $value, $ids, true)) {
                $errors[] = "$nr. jautājums: izvēlies vienu atbildi.";
            } else {
                $clean[] = [$q['id'], (int) $value, null];
            }
        } else {
            $picked = is_array($value) ? array_unique(array_map('intval', $value)) : [];
            if (!$picked || array_diff($picked, $ids)) {
                $errors[] = "$nr. jautājums: atzīmē vismaz vienu atbildi.";
            } else {
                foreach ($picked as $id) {
                    $clean[] = [$q['id'], $id, null];
                }
            }
        }
    }
    return [$errors, $clean];
}

    // Rezultati 

// Saskaita, cik reizes izveleta katra atbilde (diagrammai)
function chart_data($q, $responses)
{
    if ($q['type'] === 'text') {
        return null;
    }
    $labels = $q['type'] === 'scale' ? ['1', '2', '3', '4', '5'] : array_column($q['choices'], 'text');
    $counts = array_fill(0, count($labels), 0);
    $sum = 0;

    foreach ($responses as $r) {
        foreach ($r['answers'][$q['id']] ?? [] as $value) {
            $index = array_search($value, $labels, true);
            if ($index !== false) {
                $counts[$index]++;
                $sum += (int) $value;
            }
        }
    }

    // Videja vertiba tikai skalai
    $total = array_sum($counts);
    $avg = ($q['type'] === 'scale' && $total > 0) ? round($sum / $total, 1) : null;

    return ['labels' => $labels, 'counts' => $counts, 'avg' => $avg];
}

// Lejupielade CSV failu (RFC 4180)
function export_csv($survey, $questions, $responses)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="aptauja_' . $survey['id'] . '.csv"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // lai Excel pareizi raditu garumzimes

    $head = ['Iesniegts', 'E-pasts'];
    foreach ($questions as $q) {
        $head[] = $q['text'];
    }
    fputcsv($out, array_map('safe_cell', $head), ',', '"', '');

    foreach ($responses as $r) {
        $row = [$r['time'], $r['email']];
        foreach ($questions as $q) {
            $row[] = implode('; ', $r['answers'][$q['id']] ?? []);
        }
        fputcsv($out, array_map('safe_cell', $row), ',', '"', '');
    }
    exit;
}

// Aizsardziba pret CSV formulu injekciju (OWASP)
function safe_cell($value)
{
    $value = (string) $value;
    return ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) ? "'" . $value : $value;
}

// Parada vienkarsu zinu (kluda, paldies, termins beidzies) un beidz darbu
function show_message($title, $text = '')
{
    $pageTitle = $title;
    require __DIR__ . '/../views/header.php';
    echo '<div class="card card-head"><h1 class="page-title">' . e($title) . '</h1>';
    if ($text) {
        echo '<p class="sub">' . e($text) . '</p>';
    }
    echo '</div>';
    require __DIR__ . '/../views/footer.php';
    exit;
}
