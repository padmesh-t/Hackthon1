<?php
requireLogin();
db()->prepare("INSERT INTO checklist_items (trip_id,category,item) VALUES (?,?,?)")
    ->execute([$_POST['trip_id'],$_POST['category'],$_POST['item']]);
header("Location: ?page=packing&tid={$_POST['trip_id']}"); exit;