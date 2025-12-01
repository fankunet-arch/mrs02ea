<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /mrs/ap/index.php?action=login');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

$attempts = $_SESSION['login_attempts'] ?? 0;
$lastAttempt = $_SESSION['last_attempt_time'] ?? 0;
if ($attempts >= 5 && (time() - $lastAttempt) < 300) {
    header('Location: /mrs/ap/index.php?action=login&error=too_many_attempts');
    exit;
}

if ($username === '' || $password === '') {
    $_SESSION['login_attempts'] = $attempts + 1;
    $_SESSION['last_attempt_time'] = time();
    header('Location: /mrs/ap/index.php?action=login&error=invalid');
    exit;
}

$user = mrs_authenticate_user($pdo, $username, $password);
if ($user === false) {
    $_SESSION['login_attempts'] = $attempts + 1;
    $_SESSION['last_attempt_time'] = time();
    header('Location: /mrs/ap/index.php?action=login&error=invalid');
    exit;
}

unset($_SESSION['login_attempts'], $_SESSION['last_attempt_time']);
mrs_create_user_session($user);
header('Location: /mrs/ap/index.php?action=dashboard');
exit;
