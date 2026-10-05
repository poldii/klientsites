<?php
declare(strict_types=1);

const ROOT = __DIR__ . '/..';
const CONTENT_FILE = ROOT . '/data/content.json';
const ADMIN_FILE = ROOT . '/data/admin.php';
const BACKUP_DIR = ROOT . '/data/backup';
const UPLOAD_DIR = ROOT . '/uploads';
const MAX_UPLOAD = 6 * 1024 * 1024;

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function start_session(): void {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('adm_sid');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => $https,
        'httponly' => true, 'samesite' => 'Strict',
    ]);
    session_start();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_ok(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

function admin_data(): array {
    return is_file(ADMIN_FILE) ? (array)(include ADMIN_FILE) : [];
}
function save_password(string $plain, bool $must_change = false): void {
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $code = "<?php\nreturn " . var_export(['hash' => $hash, 'must_change' => $must_change], true) . ";\n";
    file_put_contents(ADMIN_FILE, $code, LOCK_EX);
    if (function_exists('opcache_invalidate')) @opcache_invalidate(ADMIN_FILE, true);
}

/* ---- Защита от перебора пароля ---- */
function throttle_file(): string {
    $dir = ROOT . '/data/.login';
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    return $dir . '/' . sha1($_SERVER['REMOTE_ADDR'] ?? 'x');
}
function throttle_state(): array {
    $f = throttle_file();
    $d = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    return is_array($d) ? $d : ['n' => 0, 'until' => 0];
}
function throttle_locked(): int {
    $s = throttle_state();
    return $s['until'] > time() ? $s['until'] - time() : 0;
}
function throttle_fail(): void {
    $s = throttle_state();
    $s['n']++;
    if ($s['n'] >= 5) { $s['until'] = time() + 300; $s['n'] = 0; }
    file_put_contents(throttle_file(), json_encode($s), LOCK_EX);
}
function throttle_reset(): void { @unlink(throttle_file()); }

/* ---- Контент ---- */
function load_content(): array {
    $d = json_decode((string)@file_get_contents(CONTENT_FILE), true);
    return is_array($d) ? $d : [];
}
function field_value(array $c, string $section, array $f) {
    if (!empty($f['flat'])) return $c[$section] ?? [];
    return $c[$section][$f['key']] ?? ($f['type'] === 'list' || $f['type'] === 'lines' ? [] : '');
}

function clean_text(string $v, int $max, bool $multiline = false): string {
    $v = str_replace("\r", '', $v);
    if (!$multiline) $v = preg_replace('/\s*\n\s*/u', ' ', $v) ?? '';
    return mb_substr(trim(strip_tags($v)), 0, $max);
}
function clean_url(string $v): string {
    $v = trim($v);
    if ($v === '') return '';
    return preg_match('~^(https?://[^\s<>"\']+|tel:\+?[0-9]+|mailto:[^\s<>"\']+)$~i', $v) ? mb_substr($v, 0, 300) : '';
}
function clean_image_path(string $v, string $fallback): string {
    $v = trim($v);
    if (preg_match('~^(assets/img|uploads)/[A-Za-z0-9._-]+$~', $v) && is_file(ROOT . '/' . $v)) return $v;
    return $fallback;
}

function sanitize_field(array $f, $val, string $fallback = '') {
    switch ($f['type']) {
        case 'text':     return clean_text((string)$val, $f['max'] ?? 200);
        case 'textarea': return clean_text((string)$val, $f['max'] ?? 1200, true);
        case 'url':      return clean_url((string)$val);
        case 'image':    return clean_image_path((string)$val, $fallback);
        case 'lines':
            $lines = preg_split('/\n/u', str_replace("\r", '', (string)(is_array($val) ? implode("\n", $val) : $val))) ?: [];
            $out = [];
            foreach ($lines as $l) {
                $l = clean_text($l, 140);
                if ($l !== '') $out[] = $l;
                if (count($out) >= ($f['max'] ?? 10)) break;
            }
            return $out;
        case 'list':
            $out = [];
            foreach ((array)$val as $row) {
                if (!is_array($row)) continue;
                $item = []; $any = false;
                foreach ($f['fields'] as $sf) {
                    $item[$sf['key']] = sanitize_field($sf, $row[$sf['key']] ?? '');
                    if ($item[$sf['key']] !== '' && $item[$sf['key']] !== []) $any = true;
                }
                if ($any) $out[] = $item;
                if (count($out) >= ($f['max'] ?? 10)) break;
            }
            return $out;
    }
    return '';
}

/** Загрузка картинки. Возвращает путь или null. Бросает RuntimeException с понятным текстом. */
function handle_upload(string $key): ?string {
    if (empty($_FILES['up']['name'][$key])) return null;
    $err = $_FILES['up']['error'][$key];
    if ($err === UPLOAD_ERR_NO_FILE) return null;
    if ($err !== UPLOAD_ERR_OK) throw new RuntimeException('Не удалось загрузить фото. Возможно, файл слишком большой.');
    $tmp = $_FILES['up']['tmp_name'][$key];
    if (!is_uploaded_file($tmp)) throw new RuntimeException('Файл не прошёл проверку.');
    if ($_FILES['up']['size'][$key] > MAX_UPLOAD) throw new RuntimeException('Фото больше 6 МБ. Уменьшите его и попробуйте снова.');
    $info = @getimagesize($tmp);
    $fi = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($tmp);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$info || !$ext) throw new RuntimeException('Подходят только фото JPG, PNG или WebP.');
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($tmp, UPLOAD_DIR . '/' . $name)) throw new RuntimeException('Не удалось сохранить фото.');
    @chmod(UPLOAD_DIR . '/' . $name, 0644);
    return 'uploads/' . $name;
}

function save_content(array $c): void {
    if (!is_dir(BACKUP_DIR)) @mkdir(BACKUP_DIR, 0750, true);
    if (is_file(CONTENT_FILE)) {
        @copy(CONTENT_FILE, BACKUP_DIR . '/content-' . date('Ymd-His') . '.json');
        $all = glob(BACKUP_DIR . '/content-*.json') ?: [];
        sort($all);
        foreach (array_slice($all, 0, max(0, count($all) - 30)) as $old) @unlink($old);
    }
    $json = json_encode($c, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $tmp = CONTENT_FILE . '.tmp';
    file_put_contents($tmp, $json, LOCK_EX);
    rename($tmp, CONTENT_FILE);
}

function leads(int $limit = 100): array {
    $f = ROOT . '/data/leads.php';
    if (!is_file($f)) return [];
    $lines = file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $lines = array_values(array_filter($lines, fn($l) => strpos($l, '<?php') !== 0));
    return array_slice(array_reverse($lines), 0, $limit);
}
