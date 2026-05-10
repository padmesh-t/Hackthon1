<?php
requireLogin();
db()->prepare("UPDATE users SET first_name=?,last_name=?,phone=?,city=?,country=?,bio=? WHERE id=?")
    ->execute([$_POST['first_name'],$_POST['last_name'],$_POST['phone'],$_POST['city'],$_POST['country'],$_POST['bio'],uid()]);
if (!empty($_POST['new_password'])) {
    db()->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($_POST['new_password'],PASSWORD_DEFAULT),uid()]);
}
flash("Profile updated!");
header("Location: ?page=profile"); exit;