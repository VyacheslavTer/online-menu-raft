<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

$pdo = db();
$auth = new Auth($pdo);
$repo = new MenuRepository($pdo);
$settings = new Settings($pdo);
$action = (string) ($_GET['action'] ?? 'dashboard');
$errors = [];
$notice = (string) ($_SESSION['notice'] ?? '');
unset($_SESSION['notice']);

if ($action === 'logout') {
    $auth->logout();
    redirect('/manage-raft/');
}

if ($action === 'forgot-password') {
    if (is_post()) {
        verify_csrf();
        try {
            request_password_reset($pdo, (string) ($_POST['email'] ?? ''));
            $_SESSION['notice'] = 'Если такой email есть в админке, мы отправили ссылку для восстановления.';
            redirect('/manage-raft/?action=forgot-password');
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    render_admin_header('Восстановление пароля', $auth);
    render_notice($notice);
    render_errors($errors);
    render_forgot_password_form($pdo);
    render_admin_footer();
    exit;
}

if ($action === 'reset-password') {
    $token = (string) ($_GET['token'] ?? '');
    if (is_post()) {
        verify_csrf();
        try {
            reset_password_with_token($pdo, (string) ($_POST['token'] ?? ''), (string) ($_POST['password'] ?? ''), (string) ($_POST['password_confirm'] ?? ''));
            $_SESSION['notice'] = 'Пароль обновлен. Теперь можно войти.';
            redirect('/manage-raft/');
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $token = (string) ($_POST['token'] ?? $token);
        }
    }

    render_admin_header('Новый пароль', $auth);
    render_errors($errors);
    render_reset_password_form($token);
    render_admin_footer();
    exit;
}

if (!$auth->check()) {
    if (is_post()) {
        verify_csrf();
        if ($auth->attempt((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            redirect('/manage-raft/');
        }
        $errors[] = 'Неверный email или пароль.';
    }

    render_admin_header('Вход', $auth);
    ?>
    <main class="admin-login">
        <form class="admin-card admin-card--narrow" method="post">
            <?= csrf_field() ?>
            <h1>Вход в админку</h1>
            <?php render_errors($errors); ?>
            <?php $loginEmail = (string) ($pdo->query('SELECT email FROM users WHERE is_active = 1 ORDER BY id LIMIT 1')->fetchColumn() ?: config('admin.email')); ?>
            <label>Email<input type="email" name="email" value="<?= e($loginEmail) ?>" required></label>
            <label>Пароль<input type="password" name="password" required></label>
            <button class="button" type="submit">Войти</button>
            <a class="form-link" href="/manage-raft/?action=forgot-password">Забыли пароль?</a>
        </form>
    </main>
    <?php
    render_admin_footer();
    exit;
}

try {
    if ($action === 'save' && is_post()) {
        verify_csrf();
        $item = !empty($_POST['id']) ? $repo->find((int) $_POST['id']) : null;
        $imagePath = (string) ($item['image_path'] ?? '');
        $uploaded = Upload::image($_FILES['image'] ?? []);
        if ($uploaded) {
            $imagePath = $uploaded;
        }
        if (!empty($_POST['remove_image'])) {
            $imagePath = '';
        }

        $id = $repo->save([
            'id' => $_POST['id'] ?? null,
            'parent_id' => $_POST['parent_id'] ?? null,
            'item_type' => $_POST['item_type'] ?? 'item',
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'title_kz' => $_POST['title_kz'] ?? '',
            'description_kz' => $_POST['description_kz'] ?? '',
            'title_en' => $_POST['title_en'] ?? '',
            'description_en' => $_POST['description_en'] ?? '',
            'price' => $_POST['price'] ?? '',
            'image_path' => $imagePath,
            'sort_order' => $_POST['sort_order'] ?? 0,
            'is_active' => $_POST['is_active'] ?? 0,
        ]);
        $_SESSION['notice'] = 'Сохранено.';
        redirect('/manage-raft/?action=edit&id=' . $id);
    }

    if ($action === 'delete' && is_post()) {
        verify_csrf();
        $repo->delete((int) ($_POST['id'] ?? 0));
        $_SESSION['notice'] = 'Удалено.';
        redirect('/manage-raft/');
    }

    if ($action === 'settings' && is_post()) {
        verify_csrf();
        update_admin_account($pdo, $auth, $_POST);
        $currentSettings = $settings->all();
        $faviconPath = (string) ($currentSettings['favicon_path'] ?? '');
        $uploadedFavicon = Upload::image($_FILES['favicon'] ?? []);
        if ($uploadedFavicon) {
            $faviconPath = $uploadedFavicon;
        }
        if (!empty($_POST['remove_favicon'])) {
            $faviconPath = '';
        }

        $settings->setMany([
            'site_name' => $_POST['site_name'] ?? '',
            'site_name_kz' => $_POST['site_name_kz'] ?? '',
            'site_name_en' => $_POST['site_name_en'] ?? '',
            'site_subtitle' => $_POST['site_subtitle'] ?? '',
            'site_subtitle_kz' => $_POST['site_subtitle_kz'] ?? '',
            'site_subtitle_en' => $_POST['site_subtitle_en'] ?? '',
            'contacts' => $_POST['contacts'] ?? '',
            'contacts_kz' => $_POST['contacts_kz'] ?? '',
            'contacts_en' => $_POST['contacts_en'] ?? '',
            'working_hours' => $_POST['working_hours'] ?? '',
            'working_hours_kz' => $_POST['working_hours_kz'] ?? '',
            'working_hours_en' => $_POST['working_hours_en'] ?? '',
            'instagram_url' => $_POST['instagram_url'] ?? '',
            'telegram_url' => $_POST['telegram_url'] ?? '',
            'whatsapp_url' => $_POST['whatsapp_url'] ?? '',
            'favicon_path' => $faviconPath,
        ]);
        $_SESSION['notice'] = 'Настройки обновлены.';
        redirect('/manage-raft/?action=settings');
    }
} catch (Throwable $exception) {
    $errors[] = $exception->getMessage();
}

if ($action === 'new' || $action === 'edit') {
    $item = null;
    if ($action === 'edit') {
        $item = $repo->find((int) ($_GET['id'] ?? 0));
        if (!$item) {
            http_response_code(404);
            exit('Позиция не найдена.');
        }
    }

    $prefillParentId = isset($_GET['parent_id']) ? (int) $_GET['parent_id'] : null;
    render_admin_header($item ? 'Редактирование' : 'Новая позиция', $auth);
    render_notice($notice);
    render_errors($errors);
    render_item_form($repo, $item, $prefillParentId);
    render_admin_footer();
    exit;
}

if ($action === 'settings') {
    $allSettings = $settings->all();
    render_admin_header('Настройки', $auth);
    render_notice($notice);
    render_errors($errors);
    ?>
    <main class="admin-shell">
        <form class="admin-card" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <h1>Настройки витрины</h1>
            <h2>Основное</h2>
            <label>Название заведения RU<input name="site_name" value="<?= e($allSettings['site_name'] ?? '') ?>"></label>
            <label>Название заведения KZ<input name="site_name_kz" value="<?= e($allSettings['site_name_kz'] ?? '') ?>"></label>
            <label>Название заведения EN<input name="site_name_en" value="<?= e($allSettings['site_name_en'] ?? '') ?>"></label>
            <label>Подзаголовок RU<input name="site_subtitle" value="<?= e($allSettings['site_subtitle'] ?? '') ?>"></label>
            <label>Подзаголовок KZ<input name="site_subtitle_kz" value="<?= e($allSettings['site_subtitle_kz'] ?? '') ?>"></label>
            <label>Подзаголовок EN<input name="site_subtitle_en" value="<?= e($allSettings['site_subtitle_en'] ?? '') ?>"></label>
            <label>Контакты RU<input name="contacts" value="<?= e($allSettings['contacts'] ?? '') ?>"></label>
            <label>Контакты KZ<input name="contacts_kz" value="<?= e($allSettings['contacts_kz'] ?? '') ?>"></label>
            <label>Контакты EN<input name="contacts_en" value="<?= e($allSettings['contacts_en'] ?? '') ?>"></label>
            <label>Режим работы RU<input name="working_hours" value="<?= e($allSettings['working_hours'] ?? '') ?>"></label>
            <label>Режим работы KZ<input name="working_hours_kz" value="<?= e($allSettings['working_hours_kz'] ?? '') ?>"></label>
            <label>Режим работы EN<input name="working_hours_en" value="<?= e($allSettings['working_hours_en'] ?? '') ?>"></label>
            <h2>Иконка вкладки</h2>
            <label>Favicon
                <input type="file" name="favicon" accept="image/png,image/jpeg,image/webp,image/gif" data-image-preview-input>
            </label>
            <div class="image-preview image-preview--favicon">
                <?php if (!empty($allSettings['favicon_path'])): ?>
                    <img src="<?= e(asset_url($allSettings['favicon_path'])) ?>" alt="" data-image-preview>
                    <label class="checkbox-row">
                        <input type="checkbox" name="remove_favicon" value="1">
                        <span>Убрать favicon</span>
                    </label>
                <?php else: ?>
                    <img src="" alt="" hidden data-image-preview>
                <?php endif; ?>
            </div>
            <h2>Соцсети</h2>
            <label>Instagram<input type="text" inputmode="url" name="instagram_url" placeholder="https://instagram.com/..." value="<?= e($allSettings['instagram_url'] ?? '') ?>"></label>
            <label>Telegram<input type="text" inputmode="url" name="telegram_url" placeholder="https://t.me/..." value="<?= e($allSettings['telegram_url'] ?? '') ?>"></label>
            <label>WhatsApp<input type="text" inputmode="url" name="whatsapp_url" placeholder="https://wa.me/..." value="<?= e($allSettings['whatsapp_url'] ?? '') ?>"></label>

            <h2>Почта для восстановления</h2>
            <p class="form-hint">Если письма не приходят через хостинг, включите SMTP и укажите данные почтового ящика.</p>
            <label class="checkbox-row">
                <input type="checkbox" name="smtp_enabled" value="1"<?= checked($allSettings['smtp_enabled'] ?? '') ?>>
                <span>Отправлять через SMTP</span>
            </label>
            <div class="form-grid">
                <label>SMTP сервер<input name="smtp_host" placeholder="smtp.mail.ru" value="<?= e($allSettings['smtp_host'] ?? '') ?>"></label>
                <label>Порт<input name="smtp_port" inputmode="numeric" placeholder="465" value="<?= e($allSettings['smtp_port'] ?? '587') ?>"></label>
                <label>Логин SMTP<input name="smtp_username" value="<?= e($allSettings['smtp_username'] ?? '') ?>" autocomplete="username"></label>
                <label>Пароль SMTP<input type="password" name="smtp_password" value="<?= e($allSettings['smtp_password'] ?? '') ?>" autocomplete="new-password"></label>
                <label>Email отправителя<input type="email" name="smtp_from_email" placeholder="name@example.com" value="<?= e($allSettings['smtp_from_email'] ?? '') ?>"></label>
                <label>Имя отправителя<input name="smtp_from_name" value="<?= e($allSettings['smtp_from_name'] ?? 'raft menu') ?>"></label>
            </div>
            <?php $adminUser = $auth->user(); ?>
            <h2>Доступ в админку</h2>
            <p class="form-hint">Email и пароль меняются в базе. Пароль из <code>config.local.php</code> нужен только при первом создании админа.</p>
            <label>Email администратора<input type="email" name="admin_email" value="<?= e($adminUser['email'] ?? '') ?>" autocomplete="username"></label>
            <div class="form-grid">
                <label>Текущий пароль<input type="password" name="admin_current_password" autocomplete="current-password"></label>
                <label>Новый пароль<input type="password" name="admin_new_password" autocomplete="new-password" minlength="8"></label>
            </div>
            <label>Повторите новый пароль<input type="password" name="admin_new_password_confirm" autocomplete="new-password" minlength="8"></label>
            <button class="button" type="submit">Сохранить</button>
        </form>
    </main>
    <?php
    render_admin_footer();
    exit;
}

render_admin_header('Меню', $auth);
render_notice($notice);
render_errors($errors);
?>
<main class="admin-shell">
    <?php if ($auth->usingDefaultPassword()): ?>
        <div class="notice notice--warning">Сейчас используется пароль из примера. Перед публикацией создайте <code>app/config.local.php</code> и задайте свой пароль.</div>
    <?php endif; ?>

    <div class="admin-actions">
        <a class="button" href="/manage-raft/?action=new">Добавить верхний раздел</a>
        <a class="button button--ghost" href="/manage-raft/?action=settings">Настройки</a>
        <a class="button button--ghost" href="/" target="_blank">Открыть меню</a>
    </div>

    <section class="admin-card">
        <h1>Структура меню</h1>
        <div class="tree-list">
            <?php render_tree($repo->tree(false)); ?>
        </div>
    </section>
</main>
<?php
render_admin_footer();

function render_forgot_password_form(PDO $pdo): void
{
    $email = (string) ($pdo->query('SELECT email FROM users WHERE is_active = 1 ORDER BY id LIMIT 1')->fetchColumn() ?: '');
    ?>
    <main class="admin-login">
        <form class="admin-card admin-card--narrow" method="post" action="/manage-raft/?action=forgot-password">
            <?= csrf_field() ?>
            <h1>Восстановление пароля</h1>
            <p class="form-hint">Введите email администратора. Мы отправим одноразовую ссылку для создания нового пароля.</p>
            <label>Email<input type="email" name="email" value="<?= e($email) ?>" required autocomplete="username"></label>
            <button class="button" type="submit">Отправить ссылку</button>
            <a class="form-link" href="/manage-raft/">Вернуться ко входу</a>
        </form>
    </main>
    <?php
}

function render_reset_password_form(string $token): void
{
    ?>
    <main class="admin-login">
        <form class="admin-card admin-card--narrow" method="post" action="/manage-raft/?action=reset-password">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <h1>Новый пароль</h1>
            <p class="form-hint">Ссылка действует один час и только один раз.</p>
            <label>Новый пароль<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
            <label>Повторите пароль<input type="password" name="password_confirm" minlength="8" required autocomplete="new-password"></label>
            <button class="button" type="submit">Сохранить пароль</button>
            <a class="form-link" href="/manage-raft/">Вернуться ко входу</a>
        </form>
    </main>
    <?php
}

function request_password_reset(PDO $pdo, string $email): void
{
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Укажите корректный email.');
    }

    $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    if (!$user) {
        return;
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $pdo->prepare('UPDATE password_reset_tokens SET used_at = :used_at WHERE user_id = :user_id AND used_at IS NULL')->execute([
        'used_at' => date('Y-m-d H:i:s'),
        'user_id' => (int) $user['id'],
    ]);

    $stmt = $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)');
    $stmt->execute([
        'user_id' => (int) $user['id'],
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
    ]);

    send_password_reset_email($pdo, (string) $user['email'], admin_absolute_url('?action=reset-password&token=' . urlencode($token)));
}

function reset_password_with_token(PDO $pdo, string $token, string $password, string $passwordConfirm): void
{
    if ($token === '') {
        throw new InvalidArgumentException('Ссылка восстановления некорректна.');
    }
    if (mb_strlen($password) < 8) {
        throw new InvalidArgumentException('Новый пароль должен быть не короче 8 символов.');
    }
    if ($password !== $passwordConfirm) {
        throw new InvalidArgumentException('Пароли не совпадают.');
    }

    $stmt = $pdo->prepare('SELECT * FROM password_reset_tokens WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at >= :now LIMIT 1');
    $stmt->execute([
        'token_hash' => hash('sha256', $token),
        'now' => date('Y-m-d H:i:s'),
    ]);
    $reset = $stmt->fetch();
    if (!$reset) {
        throw new InvalidArgumentException('Ссылка восстановления устарела или уже использована.');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id AND is_active = 1')->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => (int) $reset['user_id'],
        ]);
        $pdo->prepare('UPDATE password_reset_tokens SET used_at = :used_at WHERE id = :id')->execute([
            'used_at' => date('Y-m-d H:i:s'),
            'id' => (int) $reset['id'],
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function admin_absolute_url(string $query = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host . '/manage-raft/' . ltrim($query, '?');
}

function send_password_reset_email(PDO $pdo, string $email, string $url): void
{
    $settings = new Settings($pdo);
    $subject = 'Восстановление пароля raft';
    $body = "Здравствуйте.\n\nДля восстановления пароля администратора откройте ссылку:\n" . $url . "\n\nСсылка действует один час. Если вы не запрашивали восстановление, просто игнорируйте это письмо.\n";

    if ($settings->get('smtp_enabled') === '1') {
        smtp_send($settings, $email, $subject, $body);
        return;
    }

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: raft menu <noreply@' . preg_replace('/^www\./', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost')) . '>',
    ];

    if (!mail($email, $subject, $body, implode("\r\n", $headers))) {
        throw new RuntimeException('Не удалось отправить письмо. Настройте SMTP в админке.');
    }
}

function smtp_send(Settings $settings, string $to, string $subject, string $body): void
{
    $host = trim($settings->get('smtp_host'));
    $port = (int) ($settings->get('smtp_port', '587') ?: 587);
    $username = trim($settings->get('smtp_username'));
    $password = $settings->get('smtp_password');
    $fromEmail = trim($settings->get('smtp_from_email', $username));
    $fromName = trim($settings->get('smtp_from_name', 'raft menu'));
    if ($host === '' || $username === '' || $password === '' || $fromEmail === '') {
        throw new RuntimeException('SMTP включен, но заполнены не все настройки почты.');
    }

    $remote = ($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        throw new RuntimeException('Не удалось подключиться к SMTP: ' . $errstr);
    }
    stream_set_timeout($socket, 15);
    smtp_expect($socket, 220);
    smtp_cmd($socket, 'EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), 250);
    if ($port !== 465) {
        smtp_cmd($socket, 'STARTTLS', 220);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('SMTP TLS не включился.');
        }
        smtp_cmd($socket, 'EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), 250);
    }
    smtp_cmd($socket, 'AUTH LOGIN', 334);
    smtp_cmd($socket, base64_encode($username), 334);
    smtp_cmd($socket, base64_encode($password), 235);
    smtp_cmd($socket, 'MAIL FROM:<' . $fromEmail . '>', 250);
    smtp_cmd($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
    smtp_cmd($socket, 'DATA', 354);
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $message = "From: {$fromName} <{$fromEmail}>\r\n" .
        "To: <{$to}>\r\n" .
        "Subject: {$encodedSubject}\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n\r\n" .
        $body . "\r\n.";
    smtp_cmd($socket, $message, 250);
    smtp_cmd($socket, 'QUIT', 221);
    fclose($socket);
}

function smtp_cmd($socket, string $command, int|array $expected): string
{
    fwrite($socket, $command . "\r\n");
    return smtp_expect($socket, $expected);
}

function smtp_expect($socket, int|array $expected): string
{
    $expected = (array) $expected;
    $response = '';
    do {
        $line = fgets($socket, 512);
        if ($line === false) {
            throw new RuntimeException('SMTP не ответил.');
        }
        $response .= $line;
    } while (isset($line[3]) && $line[3] === '-');
    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expected, true)) {
        throw new RuntimeException('SMTP ошибка: ' . trim($response));
    }
    return $response;
}
function update_admin_account(PDO $pdo, Auth $auth, array $input): void
{
    $user = $auth->user();
    if (!$user) {
        throw new RuntimeException('Пользователь не найден. Войдите заново.');
    }

    $email = trim((string) ($input['admin_email'] ?? $user['email']));
    $currentPassword = (string) ($input['admin_current_password'] ?? '');
    $newPassword = (string) ($input['admin_new_password'] ?? '');
    $newPasswordConfirm = (string) ($input['admin_new_password_confirm'] ?? '');
    $emailChanged = strcasecmp($email, (string) $user['email']) !== 0;
    $passwordChanged = $newPassword !== '' || $newPasswordConfirm !== '';

    if (!$emailChanged && !$passwordChanged) {
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Укажите корректный email администратора.');
    }

    if ($currentPassword === '') {
        throw new InvalidArgumentException('Введите текущий пароль, чтобы изменить доступ в админку.');
    }

    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute(['id' => (int) $user['id']]);
    $storedUser = $stmt->fetch();
    if (!$storedUser || !password_verify($currentPassword, (string) $storedUser['password_hash'])) {
        throw new InvalidArgumentException('Текущий пароль указан неверно.');
    }

    if ($passwordChanged) {
        if (mb_strlen($newPassword) < 8) {
            throw new InvalidArgumentException('Новый пароль должен быть не короче 8 символов.');
        }
        if ($newPassword !== $newPasswordConfirm) {
            throw new InvalidArgumentException('Новый пароль и повтор не совпадают.');
        }
    }

    $duplicate = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
    $duplicate->execute(['email' => $email, 'id' => (int) $user['id']]);
    if ((int) $duplicate->fetchColumn() > 0) {
        throw new InvalidArgumentException('Пользователь с таким email уже существует.');
    }

    $fields = ['email = :email'];
    $params = ['email' => $email, 'id' => (int) $user['id']];
    if ($passwordChanged) {
        $fields[] = 'password_hash = :password_hash';
        $params['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
    $stmt->execute($params);
}
function render_admin_header(string $title, Auth $auth): void
{
    ?>
    <!doctype html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> · Админка</title>
        <link rel="stylesheet" href="/assets/app.css">
        <script src="/assets/app.js" defer></script>
    </head>
    <body class="admin-page">
        <header class="admin-header">
            <a class="brand" href="/manage-raft/">
                <span class="brand__name"><?= e(config('app.name', 'raft')) ?></span>
                <span class="brand__subtitle">Админка меню</span>
            </a>
            <?php if ($auth->check()): ?>
                <nav>
                    <a href="/manage-raft/">Меню</a>
                    <a href="/manage-raft/?action=settings">Настройки</a>
                    <a href="/manage-raft/?action=logout">Выйти</a>
                </nav>
            <?php endif; ?>
        </header>
    <?php
}

function render_admin_footer(): void
{
    echo '</body></html>';
}

function render_notice(string $notice): void
{
    if ($notice !== '') {
        echo '<div class="notice">' . e($notice) . '</div>';
    }
}

function render_errors(array $errors): void
{
    if (!$errors) {
        return;
    }
    echo '<div class="notice notice--error">';
    foreach ($errors as $error) {
        echo '<p>' . e($error) . '</p>';
    }
    echo '</div>';
}

function render_tree(array $nodes): void
{
    if (!$nodes) {
        echo '<p class="muted">Меню пока пустое.</p>';
        return;
    }

    echo '<ul>';
    foreach ($nodes as $node) {
        $activeClass = !empty($node['is_active']) ? '' : ' is-muted';
        echo '<li class="tree-item' . $activeClass . '">';
        echo '<div class="tree-row">';
        echo '<div><strong>' . e($node['title']) . '</strong>';
        if (!empty($node['price'])) {
            echo '<span class="tree-price">' . e($node['price']) . '</span>';
        }
        echo '</div>';
        echo '<div class="tree-actions">';
        $canAddChild = ($node['item_type'] ?? 'section') === 'section' && (int) ($node['depth'] ?? 0) < MenuRepository::MAX_DEPTH;
        if ($canAddChild) {
            echo '<a class="tree-action tree-action--add" href="/manage-raft/?action=new&parent_id=' . (int) $node['id'] . '" title="Добавить внутрь раздела" aria-label="Добавить внутрь раздела"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></a>';
        }
        echo '<a class="tree-action tree-action--edit" href="/manage-raft/?action=edit&id=' . (int) $node['id'] . '" title="Редактировать" aria-label="Редактировать"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg></a>';
        echo '</div>';
        echo '</div>';
        if (!empty($node['children'])) {
            render_tree($node['children']);
        }
        echo '</li>';
    }
    echo '</ul>';
}

function render_item_form(MenuRepository $repo, ?array $item, ?int $prefillParentId): void
{
    $isEdit = $item !== null;
    $parentId = $isEdit ? ($item['parent_id'] ?? null) : $prefillParentId;
    $options = $repo->parentOptions($isEdit ? (int) $item['id'] : null);
    ?>
    <main class="admin-shell">
        <form class="admin-card item-form" method="post" action="/manage-raft/?action=save" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
            <div class="form-title-row">
                <h1><?= $isEdit ? 'Редактирование записи' : 'Новая позиция' ?></h1>
                <?php if ($isEdit): ?>
                    <a class="button button--ghost" href="/?id=<?= (int) $item['id'] ?>" target="_blank">На витрине</a>
                <?php endif; ?>
            </div>

            <div class="form-grid">
                <label>Родительский раздел
                    <select name="parent_id">
                        <option value="">Верхний уровень</option>
                        <?php foreach ($options as $option): ?>
                            <option value="<?= (int) $option['id'] ?>"<?= selected($parentId, $option['id']) ?>><?= e($option['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Тип
                    <select name="item_type">
                        <option value="section"<?= selected($item['item_type'] ?? 'item', 'section') ?>>Раздел</option>
                        <option value="item"<?= selected($item['item_type'] ?? 'item', 'item') ?>>Позиция</option>
                    </select>
                </label>
            </div>

            <label>Название RU<input name="title" value="<?= e($item['title'] ?? '') ?>" required maxlength="190"></label>
            <label>Описание RU<textarea name="description" rows="5"><?= e($item['description'] ?? '') ?></textarea></label>

            <h2>Переводы</h2>
            <div class="translation-grid">
                <label>Название KZ<input name="title_kz" value="<?= e($item['title_kz'] ?? '') ?>" maxlength="190"></label>
                <label>Название EN<input name="title_en" value="<?= e($item['title_en'] ?? '') ?>" maxlength="190"></label>
                <label>Описание KZ<textarea name="description_kz" rows="4"><?= e($item['description_kz'] ?? '') ?></textarea></label>
                <label>Описание EN<textarea name="description_en" rows="4"><?= e($item['description_en'] ?? '') ?></textarea></label>
            </div>

            <div class="form-grid">
                <label>Цена<input name="price" value="<?= e($item['price'] ?? '') ?>" placeholder="3 200 ₸"></label>
                <label>Сортировка<input name="sort_order" type="number" value="<?= e($item['sort_order'] ?? '0') ?>"></label>
            </div>

            <label class="checkbox-row">
                <input type="checkbox" name="is_active" value="1"<?= checked($item['is_active'] ?? 1) ?>>
                <span>Показывать на витрине</span>
            </label>

            <label>Фото
                <input type="file" name="image" accept="image/*" data-image-preview-input>
            </label>

            <div class="image-preview">
                <?php if (!empty($item['image_path'])): ?>
                    <img src="<?= e(asset_url($item['image_path'])) ?>" alt="" data-image-preview>
                    <label class="checkbox-row">
                        <input type="checkbox" name="remove_image" value="1">
                        <span>Убрать фото</span>
                    </label>
                <?php else: ?>
                    <img src="" alt="" hidden data-image-preview>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button class="button" type="submit">Сохранить</button>
                <a class="button button--ghost" href="/manage-raft/">К списку</a>
            </div>
        </form>

        <?php if ($isEdit): ?>
            <form class="admin-card danger-card" method="post" action="/manage-raft/?action=delete" data-confirm="Удалить эту позицию и все вложенные позиции?">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <h2>Удаление</h2>
                <p>Удаление затронет все вложенные разделы и позиции.</p>
                <button class="button button--danger" type="submit">Удалить</button>
            </form>
        <?php endif; ?>
    </main>
    <?php
}

