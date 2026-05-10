<?php
$email = trim($_POST['email']);
$chk = db()->prepare("SELECT id FROM users WHERE email=?");
$chk->execute([$email]);
if ($chk->fetch()) { flash("Email already registered.", 'error'); header("Location: ?page=register"); exit; }
$hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
db()->prepare("INSERT INTO users (first_name,last_name,email,phone,city,country,bio,password) VALUES (?,?,?,?,?,?,?,?)")
    ->execute([$_POST['first_name'],$_POST['last_name'],$email,$_POST['phone'],$_POST['city'],$_POST['country'],$_POST['bio'],$hash]);
$uid = db()->lastInsertId();
$_SESSION['uid'] = $uid;
flash("Account created! Welcome to Traveloop 🌍");
header("Location: ?page=dashboard"); exit;