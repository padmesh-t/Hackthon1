<?php
requireLogin();
db()->prepare("INSERT INTO stops (trip_id,city,country,start_date,end_date,notes,budget) VALUES (?,?,?,?,?,?,?)")
    ->execute([$_POST['trip_id'],$_POST['city'],$_POST['country'],$_POST['start_date'],$_POST['end_date'],$_POST['notes'],$_POST['budget']??0]);
flash("Stop added!");
header("Location: ?page=itinerary&tid={$_POST['trip_id']}"); exit;