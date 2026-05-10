<?php
requireLogin();
db()->prepare("INSERT INTO activities (stop_id,name,category,cost,duration_hrs,description,activity_date) VALUES (?,?,?,?,?,?,?)")
    ->execute([$_POST['stop_id'],$_POST['name'],$_POST['category'],$_POST['cost']??0,$_POST['duration_hrs']??1,$_POST['description'],$_POST['activity_date']]);
flash("Activity added!");
header("Location: ?page=itinerary&tid={$_POST['trip_id']}"); exit;