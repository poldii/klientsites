<?php
declare(strict_types=1);

@ini_set("display_errors", "0");

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function out(bool $ok, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(false, 405);

// Антиспам: honeypot
if (!empty($_POST['website'])) out(true);

// Простейший rate limit: не чаще 1 заявки в 30 секунд с одного IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'x';
$dir = __DIR__ . '/data/.rate';
if (!is_dir($dir)) @mkdir($dir, 0750, true);
$stamp = $dir . '/' . sha1($ip);
if (is_file($stamp) && time() - (int)filemtime($stamp) < 30) out(false, 429);
@touch($stamp);

// Чистим ввод и защищаемся от инъекции заголовков
function clean(string $v, int $max): string {
    $v = preg_replace('/[\r\n\t]+/u', ' ', $v) ?? '';
    $v = trim(strip_tags($v));
    return mb_substr($v, 0, $max);
}
$name    = clean((string)($_POST['name'] ?? ''), 80);
$contact = clean((string)($_POST['contact'] ?? ''), 80);
$message = mb_substr(trim(strip_tags((string)($_POST['message'] ?? ''))), 0, 800);

if ($name === '' || $contact === '') out(false, 422);

// Заявки лежат в .php-файле с «запиратором» в первой строке: даже если его откроют по ссылке, текст не покажется
$leadsFile = __DIR__ . '/data/leads.php';
if (!is_file($leadsFile)) @file_put_contents($leadsFile, "<?php http_response_code(404); exit; ?>\n", LOCK_EX);
$line = date('d.m.Y H:i') . ' | ' . $name . ' | ' . $contact . ' | ' . str_replace(["\r", "\n"], ' ', $message) . PHP_EOL;
@file_put_contents($leadsFile, $line, FILE_APPEND | LOCK_EX);

// Письмо владелице (если настроен email в content.json)
$raw = @file_get_contents(__DIR__ . '/data/content.json');
$c = $raw ? json_decode($raw, true) : [];
$to = $c['site']['email'] ?? '';
if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $host = preg_replace('/[^a-z0-9.-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $body = "Имя: $name\nКонтакт: $contact\n\n$message\n";
    @mail($to, '=?UTF-8?B?' . base64_encode('Новая заявка с сайта') . '?=', $body,
        "From: no-reply@$host\r\nContent-Type: text/plain; charset=UTF-8\r\n");
}

out(true);
