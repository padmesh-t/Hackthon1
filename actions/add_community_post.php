<?php
requireLogin();
db()->prepare("INSERT INTO community_posts (user_id,trip_id,title,content) VALUES (?,?,?,?)")
    ->execute([uid(),$_POST['trip_id']??null,$_POST['title'],$_POST['content']]);
flash("Post shared with the community!");
header("Location: ?page=community"); exit;