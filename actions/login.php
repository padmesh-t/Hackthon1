<?php
$st = db()->prepare("SELECT * FROM users WHERE email=?");
$st->execute([trim($_POST['email'])]);
$u = $st->fetch();
if ($u && password_verify($_POST['password'], $u['password'])) {
    $_SESSION['uid'] = $u['id'];
    flash("Welcome back, {$u['first_name']}!");
    header("Location: ?page=dashboard"); exit;
}
flash("Invalid email or password.", 'error');
header("Location: ?page=login"); exit;