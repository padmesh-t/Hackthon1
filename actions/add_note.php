<?php
requireLogin();
db()->prepare("INSERT INTO trip_notes (trip_id,stop_id,title,content,note_day) VALUES (?,?,?,?,?)")
    ->execute([$_POST['trip_id'],$_POST['stop_id']??null,$_POST['title'],$_POST['content'],$_POST['note_day']]);
flash("Note saved!");
header("Location: ?page=notes&tid={$_POST['trip_id']}"); exit;