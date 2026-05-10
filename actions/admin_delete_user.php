<?php
requireAdmin();
if ($_POST['user_id'] != uid()) {
    db()->prepare("DELETE FROM users WHERE id=?")->execute([$_POST['user_id']]);
    flash("User deleted.");
}
header("Location: ?page=admin"); exit;