<?php
function uid() { return $_SESSION['uid'] ?? null; }
function user() {
    if (!uid()) return null;
    static $u = null;
    if ($u) return $u;
    $st = db()->prepare("SELECT * FROM users WHERE id=?");
    $st->execute([uid()]);
    return $u = $st->fetch();
}
function requireLogin() {
    if (!uid()) { header("Location: ?page=login"); exit; }
}
function requireAdmin() {
    $u = user();
    if (!$u || $u['role'] !== 'admin') { header("Location: ?page=dashboard"); exit; }
}
function flash($msg, $type='success') { $_SESSION['flash'] = ['msg'=>$msg,'type'=>$type]; }
function getFlash() {
    if (!isset($_SESSION['flash'])) return null;
    $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f;
}
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function statusBadge($s) {
    $map=['upcoming'=>'badge-upcoming','ongoing'=>'badge-ongoing','completed'=>'badge-completed'];
    return '<span class="badge '.($map[$s]??'').'">'.ucfirst($s).'</span>';
}
function trips_of($uid) {
    $st = db()->prepare("SELECT * FROM trips WHERE user_id=? ORDER BY created_at DESC");
    $st->execute([$uid]); return $st->fetchAll();
}
function stops_of($tid) {
    $st = db()->prepare("SELECT * FROM stops WHERE trip_id=? ORDER BY sort_order,start_date");
    $st->execute([$tid]); return $st->fetchAll();
}
function acts_of($sid) {
    $st = db()->prepare("SELECT * FROM activities WHERE stop_id=? ORDER BY activity_date,id");
    $st->execute([$sid]); return $st->fetchAll();
}
function trip_by($id) {
    $st = db()->prepare("SELECT t.*,u.first_name,u.last_name FROM trips t JOIN users u ON u.id=t.user_id WHERE t.id=?");
    $st->execute([$id]); return $st->fetch();
}
function expenses_of($tid) {
    $st = db()->prepare("SELECT * FROM expenses WHERE trip_id=? ORDER BY id");
    $st->execute([$tid]); return $st->fetchAll();
}
function checklist_of($tid) {
    $st = db()->prepare("SELECT * FROM checklist_items WHERE trip_id=? ORDER BY category,id");
    $st->execute([$tid]); return $st->fetchAll();
}
function notes_of($tid) {
    $st = db()->prepare("SELECT n.*,s.city as stop_city FROM trip_notes n LEFT JOIN stops s ON s.id=n.stop_id WHERE n.trip_id=? ORDER BY n.created_at DESC");
    $st->execute([$tid]); return $st->fetchAll();
}