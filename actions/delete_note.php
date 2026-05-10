<?php
requireLogin();
db()->prepare("DELETE FROM trip_notes WHERE id=?")->execute([$_POST['note_id']]);
flash("Note deleted.");
header("Location: ?page=notes&tid={$_POST['trip_id']}"); exit;a