<?php
requireLogin();
$st = db()->prepare("SELECT packed FROM checklist_items WHERE id=?");
$st->execute([$_POST['item_id']]);
$row = $st->fetch();
db()->prepare("UPDATE checklist_items SET packed=? WHERE id=?")->execute([$row ? ($row['packed']?0:1) : 0, $_POST['item_id']]);
header("Location: ?page=packing&tid={$_POST['trip_id']}"); exit;