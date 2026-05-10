<?php
requireLogin();
db()->prepare("UPDATE checklist_items SET packed=0 WHERE trip_id=?")->execute([$_POST['trip_id']]);
header("Location: ?page=packing&tid={$_POST['trip_id']}"); exit;