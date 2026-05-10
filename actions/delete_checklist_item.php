<?php
requireLogin();
db()->prepare("DELETE FROM checklist_items WHERE id=?")->execute([$_POST['item_id']]);
header("Location: ?page=packing&tid={$_POST['trip_id']}"); exit;