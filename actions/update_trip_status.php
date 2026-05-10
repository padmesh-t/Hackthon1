<?php
requireLogin();
db()->prepare("UPDATE trips SET status=? WHERE id=? AND user_id=?")->execute([$_POST['status'],$_POST['trip_id'],uid()]);
header("Location: ?page=my_trips"); exit;