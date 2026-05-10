<?php
requireLogin();
db()->prepare("INSERT INTO trips (user_id,name,description,start_date,end_date,budget,is_public) VALUES (?,?,?,?,?,?,?)")
    ->execute([uid(),$_POST['name'],$_POST['description'],$_POST['start_date'],$_POST['end_date'],$_POST['budget']??0,$_POST['is_public']??0]);
$tid = db()->lastInsertId();
flash("Trip created!");
header("Location: ?page=itinerary&tid=$tid"); exit;