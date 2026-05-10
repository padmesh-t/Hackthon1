<?php
requireLogin();
$st = db()->prepare("SELECT id FROM trips WHERE id=? AND user_id=?");
$st->execute([$_POST['trip_id'], uid()]);
if ($st->fetch()) {
    db()->prepare("DELETE FROM trips WHERE id=?")->execute([$_POST['trip_id']]);
    flash("Trip deleted.");
}
header("Location: ?page=my_trips"); exit;