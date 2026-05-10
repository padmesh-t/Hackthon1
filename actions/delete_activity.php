<?php
requireLogin();
db()->prepare("DELETE FROM activities WHERE id=?")->execute([$_POST['activity_id']]);
flash("Activity removed.");
header("Location: ?page=itinerary&tid={$_POST['trip_id']}"); exit;