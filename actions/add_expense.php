<?php
requireLogin();
db()->prepare("INSERT INTO expenses (trip_id,category,description,qty,unit_cost,amount) VALUES (?,?,?,?,?,?)")
    ->execute([$_POST['trip_id'],$_POST['category'],$_POST['description'],$_POST['qty'],$_POST['unit_cost']??0,$_POST['amount']??0]);
flash("Expense logged!");
header("Location: ?page=budget&tid={$_POST['trip_id']}"); exit;