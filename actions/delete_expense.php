<?php
requireLogin();
db()->prepare("DELETE FROM expenses WHERE id=? AND trip_id=?")->execute([$_POST['expense_id'],$_POST['trip_id']]);
flash("Expense removed.");
header("Location: ?page=budget&tid={$_POST['trip_id']}"); exit;