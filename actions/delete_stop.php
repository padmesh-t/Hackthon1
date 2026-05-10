<?php
requireLogin();
db()->prepare("DELETE FROM stops WHERE id=? AND trip_id IN (SELECT id FROM trips WHERE user_id=?)")->execute([$_POST['stop_id'],uid()]);
flash("Stop removed.");
header("Location: ?page=itinerary&tid={$_POST['trip_id']}"); exit;