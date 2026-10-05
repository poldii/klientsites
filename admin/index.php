<?php
declare(strict_types=1);

@ini_set("display_errors", "0");

require __DIR__ . '/lib.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
header('X-Robots-Tag: noindex, nofollow');

start_session();
$schema = require __DIR__ . '/schema.php';
$admin = admin_data();
$logged = !empty($_SESSION['ok']);
$page = $_GET['page'] ?? 'edit';
if (!in_array($page, ['edit', 'leads', 'password'], true)) $page = 'edit';

function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }
function go(string $to = ''): never { header('Location: index.php' . $to); exit; }

/* ================= Действия ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $wait = throttle_locked();
        if ($wait > 0) {
            flash('err', 'Слишком много попыток. Подождите ' . ceil($wait / 60) . ' мин. и попробуйте снова.');
            go();
        }
        $pass = (string)($_POST['password'] ?? '');
        if (!empty($admin['hash']) && password_verify($pass, $admin['hash'])) {
            throttle_reset();
            session_regenerate_id(true);
            $_SESSION['ok'] = true;
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            if (!empty($admin['must_change'])) { flash('info', 'Для безопасности придумайте свой пароль.'); go('?page=password'); }
            go();
        }
        throttle_fail();
        flash('err', 'Неверный пароль.');
        go();
    }

    if (!$logged) go();
    if (!csrf_ok()) { flash('err', 'Страница устарела. Обновите её и повторите.'); go('?page=' . $page); }

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        go();
    }

    if ($action === 'save') {
        try {
            $old = load_content();
            $new = $old;
            $posted = $_POST['c'] ?? [];
            foreach ($schema as $sec => $def) {
                foreach ($def['fields'] as $f) {
                    $present = !empty($f['flat'])
                        ? (is_array($posted) && array_key_exists($sec, $posted))
                        : (isset($posted[$sec]) && is_array($posted[$sec]) && array_key_exists($f['key'], $posted[$sec]));
                    $wantsUpload = $f['type'] === 'image' && !empty($_FILES['up']['name'][$sec . '.' . $f['key']]);
                    if (!$present && !$wantsUpload) continue; // поля нет в запросе: оставляем как было
                    $raw = !$present ? '' : (!empty($f['flat']) ? $posted[$sec] : $posted[$sec][$f['key']]);
                    $fallback = (string)($old[$sec][$f['key']] ?? '');
                    $val = sanitize_field($f, $raw, $fallback);
                    if ($f['type'] === 'image') {
                        $up = handle_upload($sec . '.' . $f['key']);
                        if ($up) {
                            if (str_starts_with($fallback, 'uploads/')) @unlink(ROOT . '/' . $fallback);
                            $val = $up;
                        }
                    }
                    if (!empty($f['flat'])) $new[$sec] = $val; else $new[$sec][$f['key']] = $val;
                }
            }
            save_content($new);
            flash('ok', 'Готово! Изменения уже на сайте.');
        } catch (RuntimeException $e) {
            flash('err', $e->getMessage());
        }
        go('?page=edit');
    }

    if ($action === 'password') {
        $cur = (string)($_POST['current'] ?? '');
        $n1 = (string)($_POST['new1'] ?? '');
        $n2 = (string)($_POST['new2'] ?? '');
        if (!password_verify($cur, $admin['hash'] ?? '')) flash('err', 'Текущий пароль указан неверно.');
        elseif (mb_strlen($n1) < 10) flash('err', 'Новый пароль слишком короткий: нужно минимум 10 символов.');
        elseif ($n1 !== $n2) flash('err', 'Пароли не совпадают.');
        else { save_password($n1, false); flash('ok', 'Пароль изменён.'); go('?page=edit'); }
        go('?page=password');
    }
    go();
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ================= Отображение ================= */
function render_field(string $sec, array $f, array $c, string $name, $value): void {
    $id = 'f_' . md5($name);
    echo '<div class="field">';
    if ($f['type'] !== 'list') echo '<label for="' . $id . '">' . h($f['label']) . '</label>';
    switch ($f['type']) {
        case 'text':
        case 'url':
            echo '<input id="' . $id . '" type="text" name="' . h($name) . '" value="' . h($value) . '" maxlength="' . (int)($f['max'] ?? 300) . '">';
            break;
        case 'textarea':
            echo '<textarea id="' . $id . '" name="' . h($name) . '" rows="3" maxlength="' . (int)($f['max'] ?? 1200) . '">' . h($value) . '</textarea>';
            break;
        case 'lines':
            echo '<textarea id="' . $id . '" name="' . h($name) . '" rows="4">' . h(implode("\n", (array)$value)) . '</textarea>';
            break;
        case 'image':
            $src = (string)$value;
            $pk = $sec . '.' . $f['key'];
            echo '<div class="imgfield">';
            echo '<img src="../' . h($src) . '" alt="" width="96" height="96" class="thumb">';
            echo '<div><input type="hidden" name="' . h($name) . '" value="' . h($src) . '">';
            echo '<input id="' . $id . '" type="file" name="up[' . h($pk) . ']" accept="image/jpeg,image/png,image/webp">';
            echo '<small>Выберите файл, чтобы заменить фото. Затем нажмите «Сохранить».</small></div></div>';
            break;
        case 'list':
            echo '<fieldset class="list" data-list><legend>' . h($f['label']) . '</legend>';
            echo '<input type="hidden" name="' . h($name) . '[_]" value="1">'; // маркер: список есть в форме, даже если пустой
            echo '<div class="items" data-items>';
            $i = 0;
            foreach ((array)$value as $row) { render_item($f, $name, (string)$i, (array)$row); $i++; }
            echo '</div>';
            echo '<template data-tpl>'; render_item($f, $name, '__I__', []); echo '</template>';
            echo '<button type="button" class="btn btn--ghost" data-add data-max="' . (int)($f['max'] ?? 10) . '">+ Добавить: ' . h(mb_strtolower($f['item_label'] ?? 'элемент')) . '</button>';
            echo '</fieldset>';
            break;
    }
    if (!empty($f['help'])) echo '<small>' . h($f['help']) . '</small>';
    echo '</div>';
}
function render_item(array $f, string $name, string $i, array $row): void {
    echo '<div class="item" data-item><div class="item__bar"><b>' . h($f['item_label'] ?? 'Элемент') . '</b>';
    echo '<span><button type="button" class="mini" data-up title="Выше">↑</button><button type="button" class="mini" data-down title="Ниже">↓</button><button type="button" class="mini mini--del" data-del title="Удалить">Удалить</button></span></div>';
    foreach ($f['fields'] as $sf) {
        render_field('', $sf, [], $name . '[' . $i . '][' . $sf['key'] . ']', $row[$sf['key']] ?? ($sf['type'] === 'lines' ? [] : ''));
    }
    echo '</div>';
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Управление сайтом</title>
<link rel="stylesheet" href="admin.css">
</head>
<body>
<?php if (!$logged): ?>
  <main class="login">
    <form method="post" class="panel">
      <h1>Вход в управление сайтом</h1>
      <?php if ($flash): ?><p class="msg msg--<?= h($flash[0]) ?>"><?= h($flash[1]) ?></p><?php endif; ?>
      <input type="hidden" name="action" value="login">
      <label for="pw">Пароль</label>
      <input id="pw" type="password" name="password" autocomplete="current-password" required autofocus>
      <button class="btn" type="submit">Войти</button>
    </form>
  </main>
<?php else: ?>
  <header class="bar">
    <strong>Управление сайтом</strong>
    <nav>
      <a href="?page=edit"<?= $page === 'edit' ? ' class="on"' : '' ?>>Тексты и фото</a>
      <a href="?page=leads"<?= $page === 'leads' ? ' class="on"' : '' ?>>Заявки</a>
      <a href="?page=password"<?= $page === 'password' ? ' class="on"' : '' ?>>Пароль</a>
      <a href="../" target="_blank" rel="noopener">Открыть сайт ↗</a>
    </nav>
    <form method="post" class="bar__out">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="logout">
      <button class="link" type="submit">Выйти</button>
    </form>
  </header>

  <main class="wrap">
  <?php if ($flash): ?><p class="msg msg--<?= h($flash[0]) ?>" role="status"><?= h($flash[1]) ?></p><?php endif; ?>

  <?php if ($page === 'edit'): $c = load_content(); ?>
    <form method="post" enctype="multipart/form-data" id="editForm">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="save">
      <p class="lead">Меняйте тексты и фото в нужных разделах, затем нажмите «Сохранить». Изменения сразу появятся на сайте.</p>
      <?php foreach ($schema as $sec => $def): ?>
        <details class="sec"<?= $sec === 'hero' ? ' open' : '' ?>>
          <summary><span><?= h($def['label']) ?></span><small><?= h($def['hint'] ?? '') ?></small></summary>
          <div class="sec__body">
          <?php foreach ($def['fields'] as $f):
              $name = !empty($f['flat']) ? "c[$sec]" : "c[$sec][{$f['key']}]";
              render_field($sec, $f, $c, $name, field_value($c, $sec, $f));
          endforeach; ?>
          </div>
        </details>
      <?php endforeach; ?>
      <div class="savebar"><button class="btn" type="submit">Сохранить изменения</button></div>
    </form>

  <?php elseif ($page === 'leads'): $rows = leads(); ?>
    <h1>Заявки с сайта</h1>
    <?php if (!$rows): ?><p>Пока заявок нет.</p><?php else: ?>
      <ul class="leads">
        <?php foreach ($rows as $r): $p = explode(' | ', $r, 4); ?>
          <li><time><?= h($p[0] ?? '') ?></time><b><?= h($p[1] ?? '') ?></b><span><?= h($p[2] ?? '') ?></span><p><?= h($p[3] ?? '') ?></p></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

  <?php else: ?>
    <h1>Смена пароля</h1>
    <form method="post" class="panel">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="password">
      <label for="p0">Текущий пароль</label>
      <input id="p0" type="password" name="current" autocomplete="current-password" required>
      <label for="p1">Новый пароль (минимум 10 символов)</label>
      <input id="p1" type="password" name="new1" autocomplete="new-password" minlength="10" required>
      <label for="p2">Повторите новый пароль</label>
      <input id="p2" type="password" name="new2" autocomplete="new-password" minlength="10" required>
      <button class="btn" type="submit">Сменить пароль</button>
    </form>
  <?php endif; ?>
  </main>
  <script src="admin.js"></script>
<?php endif; ?>
</body>
</html>
