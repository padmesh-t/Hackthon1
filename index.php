<?php
// ─────────────────────────────────────────────────────────────
//  TRAVELOOP  –  Single-file PHP + MySQL App
//  All screens: Login · Register · Dashboard · Trips · Create
//               Itinerary Builder · Itinerary View · Budget ·
//               Packing List · Notes · Community · Admin
// ─────────────────────────────────────────────────────────────
session_start();

// ── DB CONFIG – change these ──────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'traveloop');
define('DB_USER', 'root');
define('DB_PASS', '');
// ─────────────────────────────────────────────────────────────

// ── DB CONNECT ───────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        // Auto-create DB if not found
        try {
            $pdo2 = new PDO("mysql:host=".DB_HOST.";charset=utf8mb4", DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo2->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e2) {
            die("<div style='font:16px monospace;color:#f55;padding:40px'>DB Error: ".$e2->getMessage()."<br>Please create a MySQL database named <b>".DB_NAME."</b> and update DB_USER/DB_PASS at the top of index.php.</div>");
        }
    }
    return $pdo;
}

// ── INSTALL SCHEMA ────────────────────────────────────────────
function install() {
    $db = db();
    $db->exec("
    CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      first_name VARCHAR(80),
      last_name VARCHAR(80),
      email VARCHAR(180) UNIQUE NOT NULL,
      phone VARCHAR(30),
      city VARCHAR(80),
      country VARCHAR(80),
      bio TEXT,
      photo VARCHAR(255),
      role ENUM('user','admin') DEFAULT 'user',
      password VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS trips (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      name VARCHAR(255) NOT NULL,
      description TEXT,
      start_date DATE,
      end_date DATE,
      cover_photo VARCHAR(255),
      budget DECIMAL(12,2) DEFAULT 0,
      is_public TINYINT(1) DEFAULT 0,
      status ENUM('upcoming','ongoing','completed') DEFAULT 'upcoming',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS stops (
      id INT AUTO_INCREMENT PRIMARY KEY,
      trip_id INT NOT NULL,
      city VARCHAR(120),
      country VARCHAR(80),
      start_date DATE,
      end_date DATE,
      notes TEXT,
      budget DECIMAL(12,2) DEFAULT 0,
      sort_order INT DEFAULT 0,
      FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS activities (
      id INT AUTO_INCREMENT PRIMARY KEY,
      stop_id INT NOT NULL,
      name VARCHAR(255),
      category VARCHAR(80),
      cost DECIMAL(10,2) DEFAULT 0,
      duration_hrs DECIMAL(4,1) DEFAULT 1,
      description TEXT,
      activity_date DATE,
      FOREIGN KEY (stop_id) REFERENCES stops(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS expenses (
      id INT AUTO_INCREMENT PRIMARY KEY,
      trip_id INT NOT NULL,
      category VARCHAR(80),
      description VARCHAR(255),
      qty VARCHAR(80),
      unit_cost DECIMAL(10,2) DEFAULT 0,
      amount DECIMAL(10,2) DEFAULT 0,
      FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS checklist_items (
      id INT AUTO_INCREMENT PRIMARY KEY,
      trip_id INT NOT NULL,
      category VARCHAR(80) DEFAULT 'General',
      item VARCHAR(255),
      packed TINYINT(1) DEFAULT 0,
      FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS trip_notes (
      id INT AUTO_INCREMENT PRIMARY KEY,
      trip_id INT NOT NULL,
      stop_id INT,
      title VARCHAR(255),
      content TEXT,
      note_day DATE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS community_posts (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      trip_id INT,
      title VARCHAR(255),
      content TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ");
    // Seed admin
    $chk = $db->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch();
    if (!$chk) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (first_name,last_name,email,role,password) VALUES ('Admin','User','admin@traveloop.com','admin',?)")->execute([$hash]);
    }
}
install();

// ── AUTH HELPERS ──────────────────────────────────────────────
function uid() { return $_SESSION['uid'] ?? null; }
function user() { 
    if (!uid()) return null;
    static $u = null;
    if ($u) return $u;
    $u = db()->prepare("SELECT * FROM users WHERE id=?")->execute([uid()]) ? db()->prepare("SELECT * FROM users WHERE id=?"): null;
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

// ── FLASH MESSAGES ────────────────────────────────────────────
function flash($msg, $type='success') { $_SESSION['flash'] = ['msg'=>$msg,'type'=>$type]; }
function getFlash() {
    if (!isset($_SESSION['flash'])) return null;
    $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f;
}

// ── ROUTING ───────────────────────────────────────────────────
$page = $_GET['page'] ?? (uid() ? 'dashboard' : 'login');
$action = $_POST['action'] ?? '';

// ── POST ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {

        case 'login':
            $st = db()->prepare("SELECT * FROM users WHERE email=?");
            $st->execute([trim($_POST['email'])]);
            $u = $st->fetch();
            if ($u && password_verify($_POST['password'], $u['password'])) {
                $_SESSION['uid'] = $u['id'];
                flash("Welcome back, {$u['first_name']}!");
                header("Location: ?page=dashboard"); exit;
            }
            flash("Invalid email or password.", 'error');
            header("Location: ?page=login"); exit;

        case 'register':
            $email = trim($_POST['email']);
            $chk = db()->prepare("SELECT id FROM users WHERE email=?");
            $chk->execute([$email]);
            if ($chk->fetch()) { flash("Email already registered.", 'error'); header("Location: ?page=register"); exit; }
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            db()->prepare("INSERT INTO users (first_name,last_name,email,phone,city,country,bio,password) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$_POST['first_name'],$_POST['last_name'],$email,$_POST['phone'],$_POST['city'],$_POST['country'],$_POST['bio'],$hash]);
            $uid = db()->lastInsertId();
            $_SESSION['uid'] = $uid;
            flash("Account created! Welcome to Traveloop 🌍");
            header("Location: ?page=dashboard"); exit;

        case 'logout':
            session_destroy();
            header("Location: ?page=login"); exit;

        case 'create_trip':
            requireLogin();
            $tid = null;
            db()->prepare("INSERT INTO trips (user_id,name,description,start_date,end_date,budget,is_public) VALUES (?,?,?,?,?,?,?)")
                ->execute([uid(),$_POST['name'],$_POST['description'],$_POST['start_date'],$_POST['end_date'],$_POST['budget']??0,$_POST['is_public']??0]);
            $tid = db()->lastInsertId();
            flash("Trip created!");
            header("Location: ?page=itinerary&tid=$tid"); exit;

        case 'delete_trip':
            requireLogin();
            $st = db()->prepare("SELECT id FROM trips WHERE id=? AND user_id=?");
            $st->execute([$_POST['trip_id'], uid()]);
            if ($st->fetch()) {
                db()->prepare("DELETE FROM trips WHERE id=?")->execute([$_POST['trip_id']]);
                flash("Trip deleted.");
            }
            header("Location: ?page=my_trips"); exit;

        case 'add_stop':
            requireLogin();
            db()->prepare("INSERT INTO stops (trip_id,city,country,start_date,end_date,notes,budget) VALUES (?,?,?,?,?,?,?)")
                ->execute([$_POST['trip_id'],$_POST['city'],$_POST['country'],$_POST['start_date'],$_POST['end_date'],$_POST['notes'],$_POST['budget']??0]);
            flash("Stop added!");
            header("Location: ?page=itinerary&tid={$_POST['trip_id']}"); exit;

        case 'delete_stop':
            requireLogin();
            db()->prepare("DELETE FROM stops WHERE id=? AND trip_id IN (SELECT id FROM trips WHERE user_id=?)")->execute([$_POST['stop_id'],uid()]);
            flash("Stop removed.");
            header("Location: ?page=itinerary&tid={$_POST['trip_id']}"); exit;

        case 'add_activity':
            requireLogin();
            db()->prepare("INSERT INTO activities (stop_id,name,category,cost,duration_hrs,description,activity_date) VALUES (?,?,?,?,?,?,?)")
                ->execute([$_POST['stop_id'],$_POST['name'],$_POST['category'],$_POST['cost']??0,$_POST['duration_hrs']??1,$_POST['description'],$_POST['activity_date']]);
            flash("Activity added!");
            header("Location: ?page=itinerary&tid={$_POST['trip_id']}"); exit;

        case 'delete_activity':
            requireLogin();
            db()->prepare("DELETE FROM activities WHERE id=?")->execute([$_POST['activity_id']]);
            flash("Activity removed.");
            header("Location: ?page=itinerary&tid={$_POST['trip_id']}"); exit;

        case 'add_expense':
            requireLogin();
            db()->prepare("INSERT INTO expenses (trip_id,category,description,qty,unit_cost,amount) VALUES (?,?,?,?,?,?)")
                ->execute([$_POST['trip_id'],$_POST['category'],$_POST['description'],$_POST['qty'],$_POST['unit_cost']??0,$_POST['amount']??0]);
            flash("Expense logged!");
            header("Location: ?page=budget&tid={$_POST['trip_id']}"); exit;

        case 'delete_expense':
            requireLogin();
            db()->prepare("DELETE FROM expenses WHERE id=? AND trip_id=?")->execute([$_POST['expense_id'],$_POST['trip_id']]);
            flash("Expense removed.");
            header("Location: ?page=budget&tid={$_POST['trip_id']}"); exit;

        case 'add_checklist':
            requireLogin();
            db()->prepare("INSERT INTO checklist_items (trip_id,category,item) VALUES (?,?,?)")
                ->execute([$_POST['trip_id'],$_POST['category'],$_POST['item']]);
            header("Location: ?page=packing&tid={$_POST['trip_id']}"); exit;

        case 'toggle_checklist':
            requireLogin();
            $st = db()->prepare("SELECT packed FROM checklist_items WHERE id=?");
            $st->execute([$_POST['item_id']]);
            $row = $st->fetch();
            db()->prepare("UPDATE checklist_items SET packed=? WHERE id=?")->execute([$row ? ($row['packed']?0:1) : 0, $_POST['item_id']]);
            header("Location: ?page=packing&tid={$_POST['trip_id']}"); exit;

        case 'reset_checklist':
            requireLogin();
            db()->prepare("UPDATE checklist_items SET packed=0 WHERE trip_id=?")->execute([$_POST['trip_id']]);
            header("Location: ?page=packing&tid={$_POST['trip_id']}"); exit;

        case 'delete_checklist_item':
            requireLogin();
            db()->prepare("DELETE FROM checklist_items WHERE id=?")->execute([$_POST['item_id']]);
            header("Location: ?page=packing&tid={$_POST['trip_id']}"); exit;

        case 'add_note':
            requireLogin();
            db()->prepare("INSERT INTO trip_notes (trip_id,stop_id,title,content,note_day) VALUES (?,?,?,?,?)")
                ->execute([$_POST['trip_id'],$_POST['stop_id']??null,$_POST['title'],$_POST['content'],$_POST['note_day']]);
            flash("Note saved!");
            header("Location: ?page=notes&tid={$_POST['trip_id']}"); exit;

        case 'delete_note':
            requireLogin();
            db()->prepare("DELETE FROM trip_notes WHERE id=?")->execute([$_POST['note_id']]);
            flash("Note deleted.");
            header("Location: ?page=notes&tid={$_POST['trip_id']}"); exit;

        case 'add_community_post':
            requireLogin();
            db()->prepare("INSERT INTO community_posts (user_id,trip_id,title,content) VALUES (?,?,?,?)")
                ->execute([uid(),$_POST['trip_id']??null,$_POST['title'],$_POST['content']]);
            flash("Post shared with the community!");
            header("Location: ?page=community"); exit;

        case 'update_profile':
            requireLogin();
            db()->prepare("UPDATE users SET first_name=?,last_name=?,phone=?,city=?,country=?,bio=? WHERE id=?")
                ->execute([$_POST['first_name'],$_POST['last_name'],$_POST['phone'],$_POST['city'],$_POST['country'],$_POST['bio'],uid()]);
            if (!empty($_POST['new_password'])) {
                db()->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($_POST['new_password'],PASSWORD_DEFAULT),uid()]);
            }
            flash("Profile updated!");
            header("Location: ?page=profile"); exit;

        case 'admin_delete_user':
            requireAdmin();
            if ($_POST['user_id'] != uid()) {
                db()->prepare("DELETE FROM users WHERE id=?")->execute([$_POST['user_id']]);
                flash("User deleted.");
            }
            header("Location: ?page=admin"); exit;

        case 'update_trip_status':
            requireLogin();
            db()->prepare("UPDATE trips SET status=? WHERE id=? AND user_id=?")->execute([$_POST['status'],$_POST['trip_id'],uid()]);
            header("Location: ?page=my_trips"); exit;
    }
}

// ── HELPERS ───────────────────────────────────────────────────
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
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function statusBadge($s) {
    $map=['upcoming'=>'badge-upcoming','ongoing'=>'badge-ongoing','completed'=>'badge-completed'];
    return '<span class="badge '.($map[$s]??'').'">'.ucfirst($s).'</span>';
}

$flash = getFlash();
$u = user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Traveloop – Personalized Travel Planning</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --bg:       #0a0c10;
  --bg2:      #111520;
  --bg3:      #181d2a;
  --card:     #141926;
  --border:   #232b3e;
  --border2:  #2d3650;
  --accent:   #3b82f6;
  --accent2:  #6366f1;
  --gold:     #f59e0b;
  --teal:     #14b8a6;
  --rose:     #f43f5e;
  --green:    #22c55e;
  --text:     #e2e8f0;
  --muted:    #64748b;
  --muted2:   #94a3b8;
  --radius:   14px;
  --shadow:   0 4px 32px rgba(0,0,0,.45);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  font-size: 15px;
  line-height: 1.6;
}

/* ── SCROLLBAR ── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--bg2); }
::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 3px; }

/* ── LAYOUT ── */
.app { display: flex; min-height: 100vh; }
.sidebar {
  width: 240px; flex-shrink: 0;
  background: var(--bg2);
  border-right: 1px solid var(--border);
  display: flex; flex-direction: column;
  position: fixed; top: 0; left: 0; height: 100vh;
  z-index: 100; overflow-y: auto;
}
.main { margin-left: 240px; flex: 1; display: flex; flex-direction: column; }
.topbar {
  height: 60px; background: var(--bg2);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 28px; position: sticky; top: 0; z-index: 50;
}
.content { padding: 32px 28px; flex: 1; }

/* ── LOGO ── */
.logo {
  padding: 24px 20px 20px;
  font-family: 'Playfair Display', serif;
  font-size: 22px; font-weight: 700;
  color: var(--text);
  letter-spacing: -.3px;
  display: flex; align-items: center; gap: 10px;
  border-bottom: 1px solid var(--border);
  margin-bottom: 8px;
}
.logo-icon {
  width: 34px; height: 34px; border-radius: 10px;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
}

/* ── NAV ── */
.nav { padding: 8px 12px; flex: 1; }
.nav a {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 12px; border-radius: 10px;
  color: var(--muted2); text-decoration: none;
  font-size: 14px; font-weight: 500;
  transition: all .18s; margin-bottom: 2px;
}
.nav a:hover { background: var(--bg3); color: var(--text); }
.nav a.active { background: rgba(59,130,246,.13); color: var(--accent); }
.nav a .icon { width: 18px; text-align: center; font-size: 16px; }
.nav-section {
  font-size: 10px; font-weight: 600; letter-spacing: 1.2px;
  text-transform: uppercase; color: var(--muted);
  padding: 14px 12px 6px;
}

/* ── SIDEBAR BOTTOM ── */
.sidebar-bottom {
  padding: 14px 12px;
  border-top: 1px solid var(--border);
}
.user-pill {
  display: flex; align-items: center; gap: 10px;
  padding: 10px; border-radius: 10px;
  background: var(--bg3); cursor: pointer;
}
.avatar {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg,var(--accent),var(--teal));
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 700; color: #fff;
  flex-shrink: 0;
}
.user-pill .uname { font-size: 13px; font-weight: 600; }
.user-pill .uemail { font-size: 11px; color: var(--muted); }

/* ── TOPBAR ── */
.page-title {
  font-family: 'Playfair Display', serif;
  font-size: 19px; font-weight: 600;
}
.topbar-right { display: flex; align-items: center; gap: 12px; }

/* ── FLASH ── */
.flash {
  padding: 12px 18px; border-radius: 10px;
  margin-bottom: 22px; font-size: 14px; font-weight: 500;
  display: flex; align-items: center; gap: 10px;
}
.flash.success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
.flash.error   { background: rgba(244,63,94,.12); border: 1px solid rgba(244,63,94,.3); color: #fda4af; }

/* ── CARDS ── */
.card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 22px;
}
.card-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 18px;
}
.card-title {
  font-family: 'Playfair Display', serif;
  font-size: 17px; font-weight: 600;
}

/* ── GRID ── */
.grid { display: grid; gap: 18px; }
.grid-2 { grid-template-columns: repeat(2,1fr); }
.grid-3 { grid-template-columns: repeat(3,1fr); }
.grid-4 { grid-template-columns: repeat(4,1fr); }

/* ── STAT CARDS ── */
.stat-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  display: flex; flex-direction: column; gap: 6px;
  position: relative; overflow: hidden;
}
.stat-card::before {
  content: ''; position: absolute;
  top: 0; left: 0; right: 0; height: 3px;
  background: var(--accent-color, var(--accent));
}
.stat-label { font-size: 12px; color: var(--muted); font-weight: 500; letter-spacing: .5px; }
.stat-value { font-size: 28px; font-weight: 700; font-family: 'Playfair Display',serif; }
.stat-sub { font-size: 12px; color: var(--muted2); }

/* ── TRIP CARDS ── */
.trip-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
}
.trip-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
.trip-cover {
  height: 130px;
  background: linear-gradient(135deg, var(--bg3) 0%, var(--bg2) 100%);
  display: flex; align-items: center; justify-content: center;
  font-size: 40px; position: relative;
}
.trip-cover .overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to bottom,transparent 50%,rgba(0,0,0,.7));
}
.trip-body { padding: 16px; }
.trip-name { font-size: 15px; font-weight: 600; margin-bottom: 4px; }
.trip-meta { font-size: 12px; color: var(--muted); display: flex; gap: 10px; flex-wrap: wrap; }
.trip-actions { display: flex; gap: 8px; margin-top: 14px; }

/* ── BADGES ── */
.badge {
  display: inline-flex; align-items: center;
  padding: 2px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 600; letter-spacing: .3px;
}
.badge-upcoming  { background: rgba(59,130,246,.15); color: #93c5fd; }
.badge-ongoing   { background: rgba(245,158,11,.15); color: #fcd34d; }
.badge-completed { background: rgba(34,197,94,.15); color: #86efac; }

/* ── BUTTONS ── */
.btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px; border-radius: 9px;
  font-size: 14px; font-weight: 500;
  cursor: pointer; border: none;
  text-decoration: none; transition: all .15s;
  font-family: inherit;
}
.btn-primary {
  background: var(--accent);
  color: #fff;
}
.btn-primary:hover { background: #2563eb; }
.btn-secondary {
  background: var(--bg3);
  border: 1px solid var(--border2);
  color: var(--text);
}
.btn-secondary:hover { background: var(--border); }
.btn-danger { background: rgba(244,63,94,.15); color: var(--rose); border: 1px solid rgba(244,63,94,.3); }
.btn-danger:hover { background: var(--rose); color: #fff; }
.btn-sm { padding: 5px 12px; font-size: 12px; border-radius: 7px; }
.btn-icon { padding: 7px; border-radius: 8px; }

/* ── FORMS ── */
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 13px; font-weight: 500; color: var(--muted2); margin-bottom: 6px; }
.form-control {
  width: 100%; padding: 10px 14px;
  background: var(--bg3); border: 1px solid var(--border2);
  border-radius: 9px; color: var(--text);
  font-family: inherit; font-size: 14px;
  transition: border-color .15s;
}
.form-control:focus { outline: none; border-color: var(--accent); }
.form-control::placeholder { color: var(--muted); }
textarea.form-control { resize: vertical; min-height: 80px; }
select.form-control { cursor: pointer; }
.form-row { display: grid; gap: 14px; grid-template-columns: repeat(2,1fr); }

/* ── TABLE ── */
.tbl { width: 100%; border-collapse: collapse; }
.tbl th { padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--border); }
.tbl td { padding: 12px 14px; font-size: 14px; border-bottom: 1px solid var(--border); }
.tbl tr:last-child td { border-bottom: none; }
.tbl tr:hover td { background: rgba(255,255,255,.02); }

/* ── STOP CARDS ── */
.stop-card {
  background: var(--bg3);
  border: 1px solid var(--border2);
  border-radius: 12px;
  padding: 18px;
  margin-bottom: 14px;
}
.stop-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 14px;
}
.stop-city { font-size: 16px; font-weight: 600; }
.stop-dates { font-size: 12px; color: var(--muted); }
.activity-row {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 0; border-top: 1px solid var(--border);
  font-size: 13px;
}
.act-name { flex: 1; }
.act-cat {
  font-size: 11px; padding: 2px 8px; border-radius: 6px;
  background: var(--card); color: var(--muted2);
}
.act-cost { color: var(--gold); font-weight: 600; font-size: 13px; min-width: 70px; text-align: right; }

/* ── CHECKLIST ── */
.checklist-category {
  background: var(--bg3);
  border: 1px solid var(--border2);
  border-radius: 12px;
  margin-bottom: 14px;
  overflow: hidden;
}
.checklist-cat-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px;
  background: var(--card);
  font-size: 13px; font-weight: 600;
  border-bottom: 1px solid var(--border2);
}
.checklist-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 16px;
  border-bottom: 1px solid var(--border);
  transition: background .15s;
}
.checklist-item:last-child { border-bottom: none; }
.checklist-item:hover { background: rgba(255,255,255,.02); }
.checklist-item label { flex: 1; cursor: pointer; font-size: 14px; }
.checklist-item input[type=checkbox] { display: none; }
.check-box {
  width: 18px; height: 18px; border-radius: 5px;
  border: 2px solid var(--border2);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; transition: all .15s; cursor: pointer;
}
.check-box.checked { background: var(--green); border-color: var(--green); }
.check-box.checked::after { content: '✓'; color: #fff; font-size: 11px; font-weight: 700; }
.item-packed { text-decoration: line-through; color: var(--muted); }

/* ── PROGRESS BAR ── */
.progress-bar {
  height: 6px; background: var(--border);
  border-radius: 3px; overflow: hidden;
  margin: 10px 0;
}
.progress-fill {
  height: 100%; border-radius: 3px;
  background: linear-gradient(90deg, var(--accent), var(--teal));
  transition: width .5s ease;
}

/* ── NOTES ── */
.note-card {
  background: var(--bg3);
  border: 1px solid var(--border2);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
  transition: border-color .2s;
}
.note-card:hover { border-color: var(--accent); }
.note-title { font-size: 15px; font-weight: 600; margin-bottom: 6px; }
.note-content { font-size: 13px; color: var(--muted2); line-height: 1.6; }
.note-meta { font-size: 11px; color: var(--muted); margin-top: 10px; display: flex; align-items: center; justify-content: space-between; }

/* ── COMMUNITY ── */
.post-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 14px;
  transition: box-shadow .2s;
}
.post-card:hover { box-shadow: 0 0 0 1px var(--border2); }
.post-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.post-title { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
.post-content { font-size: 14px; color: var(--muted2); line-height: 1.6; }
.post-meta { font-size: 12px; color: var(--muted); margin-top: 10px; }

/* ── AUTH SCREENS ── */
.auth-wrap {
  min-height: 100vh;
  background: var(--bg);
  display: flex; align-items: center; justify-content: center;
  padding: 24px;
}
.auth-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 40px;
  width: 100%; max-width: 440px;
  box-shadow: var(--shadow);
}
.auth-logo {
  font-family: 'Playfair Display', serif;
  font-size: 26px; font-weight: 700;
  text-align: center; margin-bottom: 8px;
}
.auth-sub { color: var(--muted); text-align: center; font-size: 14px; margin-bottom: 28px; }
.auth-link { color: var(--accent); text-decoration: none; }
.auth-link:hover { text-decoration: underline; }

/* ── BANNER ── */
.banner {
  border-radius: 16px;
  height: 200px;
  background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 50%, #1a2a1a 100%);
  display: flex; align-items: center; justify-content: center;
  position: relative; overflow: hidden;
  margin-bottom: 24px;
}
.banner-text {
  font-family: 'Playfair Display', serif;
  font-size: 30px; font-weight: 700;
  text-align: center; z-index: 1;
}
.banner-sub { color: var(--muted2); font-size: 15px; margin-top: 6px; z-index: 1; text-align: center; }
.banner-inner { z-index: 1; }
.banner::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(circle at 30% 50%, rgba(59,130,246,.18) 0%, transparent 60%),
              radial-gradient(circle at 70% 50%, rgba(99,102,241,.15) 0%, transparent 60%);
}

/* ── SEARCH BAR ── */
.search-row {
  display: flex; gap: 10px; margin-bottom: 22px;
}
.search-input {
  flex: 1; padding: 10px 16px;
  background: var(--bg3); border: 1px solid var(--border2);
  border-radius: 9px; color: var(--text);
  font-family: inherit; font-size: 14px;
}
.search-input:focus { outline: none; border-color: var(--accent); }

/* ── BUDGET PIE ── */
.budget-summary {
  display: flex; gap: 20px; align-items: center;
  padding: 20px; background: var(--bg3);
  border-radius: 12px; margin-bottom: 20px;
  border: 1px solid var(--border2);
}
.budget-circle {
  width: 90px; height: 90px; border-radius: 50%;
  flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: var(--text);
}
.budget-stats { flex: 1; }
.budget-stat-row { display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0; }
.over-budget { color: var(--rose); }
.under-budget { color: var(--green); }

/* ── ADMIN TABS ── */
.tab-bar { display: flex; gap: 6px; margin-bottom: 22px; }
.tab {
  padding: 8px 18px; border-radius: 9px;
  font-size: 13px; font-weight: 500; cursor: pointer;
  background: var(--bg3); border: 1px solid var(--border2);
  color: var(--muted2); text-decoration: none;
  transition: all .15s;
}
.tab.active, .tab:hover { background: var(--accent); color: #fff; border-color: var(--accent); }

/* ── MODAL ── */
.modal-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(0,0,0,.7); z-index: 200;
  align-items: center; justify-content: center;
  padding: 20px;
}
.modal-overlay.open { display: flex; }
.modal {
  background: var(--card); border: 1px solid var(--border2);
  border-radius: 16px; padding: 28px;
  width: 100%; max-width: 520px;
  max-height: 90vh; overflow-y: auto;
  box-shadow: var(--shadow);
}
.modal-title {
  font-family: 'Playfair Display', serif;
  font-size: 18px; font-weight: 600; margin-bottom: 20px;
}
.modal-close {
  float: right; background: none; border: none;
  color: var(--muted); font-size: 20px; cursor: pointer; line-height: 1;
}

/* ── INLINE DIVIDERS ── */
.divider { height: 1px; background: var(--border); margin: 20px 0; }
.section-title {
  font-size: 13px; font-weight: 600; color: var(--muted);
  text-transform: uppercase; letter-spacing: .8px;
  margin-bottom: 12px;
}

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); transition: transform .3s; }
  .sidebar.open { transform: none; }
  .main { margin-left: 0; }
  .grid-3 { grid-template-columns: repeat(2,1fr); }
  .grid-4 { grid-template-columns: repeat(2,1fr); }
  .form-row { grid-template-columns: 1fr; }
}

/* ── NO SIDEBAR PAGES ── */
.no-sidebar .main { margin-left: 0; }
</style>
</head>
<body>

<?php if (in_array($page, ['login','register'])): ?>
<!-- ═══════════════════════════════════════════════════════════
     AUTH PAGES (no sidebar)
═══════════════════════════════════════════════════════════ -->
<div class="auth-wrap">
<?php if ($page === 'login'): ?>
<div class="auth-card">
  <div class="auth-logo">✈ Traveloop</div>
  <p class="auth-sub">Plan your next adventure</p>
  <?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= h($flash['msg']) ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="action" value="login">
    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Sign In</button>
  </form>
  <div style="text-align:center;margin-top:18px;font-size:14px;color:var(--muted)">
    Don't have an account? <a href="?page=register" class="auth-link">Sign Up</a>
  </div>
  <div style="text-align:center;margin-top:10px;font-size:12px;color:var(--muted)">
    Admin: admin@traveloop.com / admin123
  </div>
</div>

<?php elseif ($page === 'register'): ?>
<div class="auth-card" style="max-width:560px">
  <div class="auth-logo">✈ Traveloop</div>
  <p class="auth-sub">Create your account</p>
  <?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= h($flash['msg']) ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="action" value="register">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">First Name</label>
        <input type="text" name="first_name" class="form-control" placeholder="Jane" required>
      </div>
      <div class="form-group">
        <label class="form-label">Last Name</label>
        <input type="text" name="last_name" class="form-control" placeholder="Doe" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="text" name="phone" class="form-control" placeholder="+1 555 0000">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" placeholder="New York">
      </div>
      <div class="form-group">
        <label class="form-label">Country</label>
        <input type="text" name="country" class="form-control" placeholder="USA">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
    </div>
    <div class="form-group">
      <label class="form-label">About You (optional)</label>
      <textarea name="bio" class="form-control" placeholder="Travel enthusiast..."></textarea>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Create Account</button>
  </form>
  <div style="text-align:center;margin-top:18px;font-size:14px;color:var(--muted)">
    Already have an account? <a href="?page=login" class="auth-link">Sign In</a>
  </div>
</div>
<?php endif; ?>
</div>

<?php else: ?>
<?php requireLogin(); ?>
<!-- ═══════════════════════════════════════════════════════════
     MAIN APP
═══════════════════════════════════════════════════════════ -->
<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="logo">
    <div class="logo-icon">✈</div>
    Traveloop
  </div>
  <nav class="nav">
    <div class="nav-section">Main</div>
    <a href="?page=dashboard" class="<?= $page==='dashboard'?'active':'' ?>">
      <span class="icon">🏠</span> Dashboard
    </a>
    <a href="?page=my_trips" class="<?= $page==='my_trips'?'active':'' ?>">
      <span class="icon">🗺️</span> My Trips
    </a>
    <a href="?page=create_trip" class="<?= $page==='create_trip'?'active':'' ?>">
      <span class="icon">➕</span> Plan New Trip
    </a>
    <div class="nav-section">Explore</div>
    <a href="?page=community" class="<?= $page==='community'?'active':'' ?>">
      <span class="icon">🌐</span> Community
    </a>
    <a href="?page=city_search" class="<?= $page==='city_search'?'active':'' ?>">
      <span class="icon">🔍</span> Search Cities
    </a>
    <?php if ($u && $u['role']==='admin'): ?>
    <div class="nav-section">Admin</div>
    <a href="?page=admin" class="<?= $page==='admin'?'active':'' ?>">
      <span class="icon">⚙️</span> Admin Panel
    </a>
    <?php endif; ?>
    <div class="nav-section">Account</div>
    <a href="?page=profile" class="<?= $page==='profile'?'active':'' ?>">
      <span class="icon">👤</span> Profile
    </a>
    <form method="POST" style="margin:2px 0">
      <input type="hidden" name="action" value="logout">
      <button type="submit" class="btn" style="width:calc(100% - 0px);justify-content:flex-start;gap:10px;border-radius:10px;background:none;color:var(--muted2);font-size:14px;padding:9px 12px">
        <span class="icon">🚪</span> Sign Out
      </button>
    </form>
  </nav>
  <div class="sidebar-bottom">
    <div class="user-pill">
      <div class="avatar"><?= strtoupper(substr($u['first_name'],0,1).substr($u['last_name'],0,1)) ?></div>
      <div>
        <div class="uname"><?= h($u['first_name'].' '.$u['last_name']) ?></div>
        <div class="uemail"><?= h($u['email']) ?></div>
      </div>
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button onclick="document.getElementById('sidebar').classList.toggle('open')"
        style="display:none;background:none;border:none;color:var(--text);font-size:22px;cursor:pointer" id="menu-btn">☰</button>
      <span class="page-title">
        <?php
        $titles = ['dashboard'=>'Dashboard','my_trips'=>'My Trips','create_trip'=>'Plan New Trip',
                   'itinerary'=>'Itinerary Builder','itinerary_view'=>'Itinerary View',
                   'budget'=>'Budget & Expenses','packing'=>'Packing Checklist',
                   'notes'=>'Trip Notes','community'=>'Community','profile'=>'Profile & Settings',
                   'city_search'=>'City Search','admin'=>'Admin Panel'];
        echo h($titles[$page] ?? ucwords(str_replace('_',' ',$page)));
        ?>
      </span>
    </div>
    <div class="topbar-right">
      <a href="?page=create_trip" class="btn btn-primary btn-sm">+ Plan a Trip</a>
    </div>
  </div>

  <div class="content">
    <?php if ($flash): ?>
    <div class="flash <?= $flash['type'] ?>"><?= h($flash['msg']) ?></div>
    <?php endif; ?>

    <!-- ══════════════════════════ DASHBOARD ══════════════════════ -->
    <?php if ($page === 'dashboard'):
      $trips = trips_of(uid());
      $totalTrips = count($trips);
      $upcoming = array_filter($trips, fn($t)=>$t['status']==='upcoming');
      $completed = array_filter($trips, fn($t)=>$t['status']==='completed');
      $totalBudget = array_sum(array_column($trips,'budget'));
    ?>
    <div class="banner">
      <div class="banner-inner">
        <div class="banner-text">Where to next, <?= h($u['first_name']) ?>? 🌍</div>
        <div class="banner-sub">You have <?= count($upcoming) ?> upcoming trip<?= count($upcoming)!=1?'s':'' ?></div>
      </div>
    </div>

    <div class="grid grid-4" style="margin-bottom:28px">
      <div class="stat-card" style="--accent-color:var(--accent)">
        <div class="stat-label">TOTAL TRIPS</div>
        <div class="stat-value"><?= $totalTrips ?></div>
        <div class="stat-sub">All time</div>
      </div>
      <div class="stat-card" style="--accent-color:var(--gold)">
        <div class="stat-label">UPCOMING</div>
        <div class="stat-value"><?= count($upcoming) ?></div>
        <div class="stat-sub">Planned trips</div>
      </div>
      <div class="stat-card" style="--accent-color:var(--green)">
        <div class="stat-label">COMPLETED</div>
        <div class="stat-value"><?= count($completed) ?></div>
        <div class="stat-sub">Adventures done</div>
      </div>
      <div class="stat-card" style="--accent-color:var(--teal)">
        <div class="stat-label">TOTAL BUDGET</div>
        <div class="stat-value">$<?= number_format($totalBudget) ?></div>
        <div class="stat-sub">Across all trips</div>
      </div>
    </div>

    <div class="section-title">Recent Trips</div>
    <?php if (empty($trips)): ?>
    <div class="card" style="text-align:center;padding:50px">
      <div style="font-size:50px;margin-bottom:14px">🗺️</div>
      <div style="font-size:17px;font-weight:600;margin-bottom:8px">No trips yet</div>
      <div style="color:var(--muted);margin-bottom:20px">Start planning your first adventure!</div>
      <a href="?page=create_trip" class="btn btn-primary">+ Plan New Trip</a>
    </div>
    <?php else: ?>
    <div class="grid grid-3">
      <?php foreach (array_slice($trips,0,6) as $t): ?>
      <div class="trip-card">
        <div class="trip-cover">
          <span style="z-index:1;font-size:36px"><?php
            $emojis=['🏙️','🏖️','🏔️','🌆','🗼','🏛️','🌴','🏝️'];
            echo $emojis[crc32($t['name'])%8];
          ?></span>
          <div class="overlay"></div>
        </div>
        <div class="trip-body">
          <div class="trip-name"><?= h($t['name']) ?></div>
          <div class="trip-meta">
            <?= statusBadge($t['status']) ?>
            <?php if ($t['start_date']): ?>
            <span>📅 <?= date('M j, Y', strtotime($t['start_date'])) ?></span>
            <?php endif; ?>
            <?php if ($t['budget']): ?><span>💰 $<?= number_format($t['budget']) ?></span><?php endif; ?>
          </div>
          <div class="trip-actions">
            <a href="?page=itinerary&tid=<?= $t['id'] ?>" class="btn btn-secondary btn-sm">Build</a>
            <a href="?page=itinerary_view&tid=<?= $t['id'] ?>" class="btn btn-secondary btn-sm">View</a>
            <a href="?page=budget&tid=<?= $t['id'] ?>" class="btn btn-secondary btn-sm">💰</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php elseif ($page === 'my_trips'):
      $trips = trips_of(uid());
      $filter = $_GET['status'] ?? 'all';
    ?>
    <!-- ════════════════════════ MY TRIPS ══════════════════════════ -->
    <div class="search-row">
      <input class="search-input" type="text" id="tripSearch" placeholder="Search trips...">
      <a href="?page=my_trips" class="tab <?= $filter==='all'?'active':'' ?>">All</a>
      <a href="?page=my_trips&status=upcoming" class="tab <?= $filter==='upcoming'?'active':'' ?>">Upcoming</a>
      <a href="?page=my_trips&status=ongoing" class="tab <?= $filter==='ongoing'?'active':'' ?>">Ongoing</a>
      <a href="?page=my_trips&status=completed" class="tab <?= $filter==='completed'?'active':'' ?>">Completed</a>
    </div>
    <?php
    $filtered = $filter==='all' ? $trips : array_filter($trips, fn($t)=>$t['status']===$filter);
    if (empty($filtered)): ?>
    <div class="card" style="text-align:center;padding:50px">
      <div style="font-size:50px;margin-bottom:14px">🗺️</div>
      <div style="font-size:17px;font-weight:600;margin-bottom:8px">No trips found</div>
      <a href="?page=create_trip" class="btn btn-primary">+ Plan New Trip</a>
    </div>
    <?php else: ?>
    <div id="tripGrid" class="grid grid-3">
      <?php foreach ($filtered as $t):
        $stops = stops_of($t['id']);
      ?>
      <div class="trip-card trip-item" data-name="<?= strtolower(h($t['name'])) ?>">
        <div class="trip-cover">
          <span style="z-index:1;font-size:36px"><?php
            $emojis=['🏙️','🏖️','🏔️','🌆','🗼','🏛️','🌴','🏝️'];
            echo $emojis[crc32($t['name'])%8];
          ?></span>
          <div class="overlay"></div>
        </div>
        <div class="trip-body">
          <div class="trip-name"><?= h($t['name']) ?></div>
          <div class="trip-meta">
            <?= statusBadge($t['status']) ?>
            <?php if ($t['start_date']): ?><span>📅 <?= date('M j',strtotime($t['start_date'])) ?> – <?= $t['end_date']?date('M j, Y',strtotime($t['end_date'])):'?' ?></span><?php endif; ?>
          </div>
          <div style="font-size:12px;color:var(--muted);margin-top:6px">
            <?= count($stops) ?> stop<?= count($stops)!=1?'s':'' ?>
            <?php if ($t['budget']): ?> · $<?= number_format($t['budget']) ?> budget<?php endif; ?>
          </div>
          <div class="trip-actions" style="flex-wrap:wrap">
            <a href="?page=itinerary&tid=<?= $t['id'] ?>" class="btn btn-secondary btn-sm">✏️ Build</a>
            <a href="?page=itinerary_view&tid=<?= $t['id'] ?>" class="btn btn-secondary btn-sm">👁 View</a>
            <a href="?page=budget&tid=<?= $t['id'] ?>" class="btn btn-secondary btn-sm">💰 Budget</a>
            <a href="?page=packing&tid=<?= $t['id'] ?>" class="btn btn-secondary btn-sm">🎒 Pack</a>
            <a href="?page=notes&tid=<?= $t['id'] ?>" class="btn btn-secondary btn-sm">📝 Notes</a>
            <form method="POST" onsubmit="return confirm('Delete this trip?')">
              <input type="hidden" name="action" value="delete_trip">
              <input type="hidden" name="trip_id" value="<?= $t['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">🗑</button>
            </form>
          </div>
          <div style="margin-top:10px">
            <form method="POST" style="display:flex;gap:6px;align-items:center">
              <input type="hidden" name="action" value="update_trip_status">
              <input type="hidden" name="trip_id" value="<?= $t['id'] ?>">
              <select name="status" class="form-control" style="padding:4px 8px;font-size:12px">
                <option value="upcoming" <?= $t['status']==='upcoming'?'selected':'' ?>>Upcoming</option>
                <option value="ongoing" <?= $t['status']==='ongoing'?'selected':'' ?>>Ongoing</option>
                <option value="completed" <?= $t['status']==='completed'?'selected':'' ?>>Completed</option>
              </select>
              <button class="btn btn-secondary btn-sm" type="submit">Update</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <script>
    document.getElementById('tripSearch').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.trip-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
      });
    });
    </script>

    <!-- ══════════════════════ CREATE TRIP ══════════════════════════ -->
    <?php elseif ($page === 'create_trip'): ?>
    <div style="max-width:680px">
      <div class="card">
        <div class="card-title" style="margin-bottom:20px">🗺️ Plan a New Trip</div>
        <form method="POST">
          <input type="hidden" name="action" value="create_trip">
          <div class="form-group">
            <label class="form-label">Trip Name *</label>
            <input type="text" name="name" class="form-control" placeholder="Paris & Rome Adventure" required>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Start Date</label>
              <input type="date" name="start_date" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">End Date</label>
              <input type="date" name="end_date" class="form-control">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Estimated Budget ($)</label>
            <input type="number" name="budget" class="form-control" placeholder="5000" min="0" step="0.01">
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" placeholder="Describe your trip..."></textarea>
          </div>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
              <input type="checkbox" name="is_public" value="1" style="width:16px;height:16px;accent-color:var(--accent)">
              Make this trip public (visible to community)
            </label>
          </div>
          <button type="submit" class="btn btn-primary">Create Trip & Build Itinerary →</button>
        </form>
      </div>
    </div>

    <!-- ════════════════════ ITINERARY BUILDER ══════════════════════ -->
    <?php elseif ($page === 'itinerary'):
      $tid = (int)($_GET['tid'] ?? 0);
      $trip = trip_by($tid);
      if (!$trip || $trip['user_id'] != uid()) { echo "<div class='card'>Trip not found.</div>"; goto end; }
      $stops = stops_of($tid);
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:22px"><?= h($trip['name']) ?></h2>
        <div style="color:var(--muted);font-size:13px">
          <?php if ($trip['start_date']): ?>📅 <?= date('M j, Y',strtotime($trip['start_date'])) ?> – <?= date('M j, Y',strtotime($trip['end_date'])) ?><?php endif; ?>
          &nbsp;·&nbsp; <?= count($stops) ?> stops
        </div>
      </div>
      <div style="display:flex;gap:8px">
        <a href="?page=itinerary_view&tid=<?= $tid ?>" class="btn btn-secondary btn-sm">👁 View Itinerary</a>
        <a href="?page=budget&tid=<?= $tid ?>" class="btn btn-secondary btn-sm">💰 Budget</a>
      </div>
    </div>

    <!-- Add Stop -->
    <div class="card" style="margin-bottom:24px">
      <div class="card-title" style="margin-bottom:16px">+ Add a Stop</div>
      <form method="POST">
        <input type="hidden" name="action" value="add_stop">
        <input type="hidden" name="trip_id" value="<?= $tid ?>">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">City *</label>
            <input type="text" name="city" class="form-control" placeholder="Paris" required>
          </div>
          <div class="form-group">
            <label class="form-label">Country</label>
            <input type="text" name="country" class="form-control" placeholder="France">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Arrival Date</label>
            <input type="date" name="start_date" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Departure Date</label>
            <input type="date" name="end_date" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Stop Budget ($)</label>
            <input type="number" name="budget" class="form-control" placeholder="1000" min="0" step="0.01">
          </div>
          <div class="form-group">
            <label class="form-label">Notes</label>
            <input type="text" name="notes" class="form-control" placeholder="Hotel info, tips...">
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Add Stop</button>
      </form>
    </div>

    <!-- Stops -->
    <div class="section-title">Stops (<?= count($stops) ?>)</div>
    <?php if (empty($stops)): ?>
    <div class="card" style="text-align:center;padding:40px;color:var(--muted)">
      No stops added yet. Add your first city above!
    </div>
    <?php endif; ?>
    <?php foreach ($stops as $si => $stop):
      $acts = acts_of($stop['id']);
    ?>
    <div class="stop-card">
      <div class="stop-header">
        <div>
          <div class="stop-city">📍 <?= h($stop['city']) ?><?= $stop['country'] ? ', '.h($stop['country']) : '' ?></div>
          <div class="stop-dates">
            <?= $stop['start_date']?date('M j',strtotime($stop['start_date'])):'?' ?>
            – <?= $stop['end_date']?date('M j, Y',strtotime($stop['end_date'])):'?' ?>
            <?php if ($stop['budget']): ?> · Budget: $<?= number_format($stop['budget']) ?><?php endif; ?>
          </div>
        </div>
        <div style="display:flex;gap:6px">
          <button class="btn btn-secondary btn-sm" onclick="document.getElementById('act-form-<?= $stop['id'] ?>').classList.toggle('hidden')">
            + Activity
          </button>
          <form method="POST">
            <input type="hidden" name="action" value="delete_stop">
            <input type="hidden" name="stop_id" value="<?= $stop['id'] ?>">
            <input type="hidden" name="trip_id" value="<?= $tid ?>">
            <button class="btn btn-danger btn-sm" onclick="return confirm('Remove this stop?')">🗑</button>
          </form>
        </div>
      </div>
      <?php if ($stop['notes']): ?>
      <div style="font-size:12px;color:var(--muted);margin-bottom:10px;padding:8px 12px;background:var(--card);border-radius:8px">
        📝 <?= h($stop['notes']) ?>
      </div>
      <?php endif; ?>

      <!-- Activity form -->
      <div id="act-form-<?= $stop['id'] ?>" class="hidden" style="background:var(--card);border-radius:10px;padding:14px;margin-bottom:12px;border:1px solid var(--border2)">
        <form method="POST">
          <input type="hidden" name="action" value="add_activity">
          <input type="hidden" name="stop_id" value="<?= $stop['id'] ?>">
          <input type="hidden" name="trip_id" value="<?= $tid ?>">
          <div class="form-row">
            <div class="form-group" style="margin-bottom:10px">
              <label class="form-label">Activity Name *</label>
              <input type="text" name="name" class="form-control" placeholder="Eiffel Tower visit" required>
            </div>
            <div class="form-group" style="margin-bottom:10px">
              <label class="form-label">Category</label>
              <select name="category" class="form-control">
                <option value="Sightseeing">Sightseeing</option>
                <option value="Food">Food & Dining</option>
                <option value="Adventure">Adventure</option>
                <option value="Culture">Culture & Arts</option>
                <option value="Transport">Transport</option>
                <option value="Accommodation">Accommodation</option>
                <option value="Shopping">Shopping</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group" style="margin-bottom:10px">
              <label class="form-label">Cost ($)</label>
              <input type="number" name="cost" class="form-control" placeholder="25" min="0" step="0.01">
            </div>
            <div class="form-group" style="margin-bottom:10px">
              <label class="form-label">Duration (hrs)</label>
              <input type="number" name="duration_hrs" class="form-control" placeholder="2" min="0.5" step="0.5">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group" style="margin-bottom:10px">
              <label class="form-label">Date</label>
              <input type="date" name="activity_date" class="form-control" value="<?= $stop['start_date'] ?>">
            </div>
            <div class="form-group" style="margin-bottom:10px">
              <label class="form-label">Description</label>
              <input type="text" name="description" class="form-control" placeholder="Optional notes">
            </div>
          </div>
          <div style="display:flex;gap:8px">
            <button class="btn btn-primary btn-sm" type="submit">Add Activity</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('act-form-<?= $stop['id'] ?>').classList.add('hidden')">Cancel</button>
          </div>
        </form>
      </div>

      <!-- Activities list -->
      <?php if (empty($acts)): ?>
      <div style="color:var(--muted);font-size:13px;padding:8px 0">No activities yet – click + Activity above</div>
      <?php else: ?>
      <?php foreach ($acts as $act): ?>
      <div class="activity-row">
        <span class="act-cat"><?= h($act['category']) ?></span>
        <span class="act-name"><?= h($act['name']) ?>
          <?php if ($act['activity_date']): ?><span style="color:var(--muted);font-size:11px"> · <?= date('M j',strtotime($act['activity_date'])) ?></span><?php endif; ?>
          <?php if ($act['duration_hrs']): ?><span style="color:var(--muted);font-size:11px"> · <?= $act['duration_hrs'] ?>h</span><?php endif; ?>
        </span>
        <span class="act-cost">$<?= number_format($act['cost'],2) ?></span>
        <form method="POST">
          <input type="hidden" name="action" value="delete_activity">
          <input type="hidden" name="activity_id" value="<?= $act['id'] ?>">
          <input type="hidden" name="trip_id" value="<?= $tid ?>">
          <button class="btn btn-danger btn-icon btn-sm" onclick="return confirm('Remove?')">✕</button>
        </form>
      </div>
      <?php endforeach; ?>
      <div style="text-align:right;font-size:13px;color:var(--gold);font-weight:600;margin-top:8px;padding-top:8px;border-top:1px solid var(--border)">
        Stop Total: $<?= number_format(array_sum(array_column($acts,'cost')),2) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- ═════════════════════ ITINERARY VIEW ═══════════════════════ -->
    <?php elseif ($page === 'itinerary_view'):
      $tid = (int)($_GET['tid'] ?? 0);
      $trip = trip_by($tid);
      if (!$trip) { echo "<div class='card'>Trip not found.</div>"; goto end; }
      $stops = stops_of($tid);
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px">
      <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:24px"><?= h($trip['name']) ?></h2>
        <div style="color:var(--muted);font-size:13px">
          by <?= h($trip['first_name'].' '.$trip['last_name']) ?>
          <?php if ($trip['start_date']): ?> · <?= date('M j, Y',strtotime($trip['start_date'])) ?> – <?= date('M j, Y',strtotime($trip['end_date'])) ?><?php endif; ?>
          <?php if ($trip['budget']): ?> · Budget: $<?= number_format($trip['budget']) ?><?php endif; ?>
        </div>
        <?php if ($trip['description']): ?>
        <div style="color:var(--muted2);font-size:14px;margin-top:6px"><?= h($trip['description']) ?></div>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:8px">
        <?php if ($trip['user_id']==uid()): ?>
        <a href="?page=itinerary&tid=<?= $tid ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
        <?php endif; ?>
        <a href="?page=budget&tid=<?= $tid ?>" class="btn btn-secondary btn-sm">💰 Budget</a>
      </div>
    </div>

    <?php if (empty($stops)): ?>
    <div class="card" style="text-align:center;padding:40px;color:var(--muted)">No stops yet.</div>
    <?php endif; ?>

    <?php foreach ($stops as $stop):
      $acts = acts_of($stop['id']);
      $startD = $stop['start_date'] ? new DateTime($stop['start_date']) : null;
      $endD = $stop['end_date'] ? new DateTime($stop['end_date']) : null;
      $days = ($startD && $endD) ? $startD->diff($endD)->days + 1 : null;
    ?>
    <div class="card" style="margin-bottom:18px;border-left:3px solid var(--accent)">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
        <div>
          <div style="font-size:18px;font-weight:700;font-family:'Playfair Display',serif">
            📍 <?= h($stop['city']) ?><?= $stop['country']?', '.h($stop['country']):'' ?>
          </div>
          <div style="color:var(--muted);font-size:13px;margin-top:3px">
            <?= $stop['start_date']?date('M j, Y',strtotime($stop['start_date'])):'' ?>
            <?= $stop['end_date']?' – '.date('M j, Y',strtotime($stop['end_date'])):'' ?>
            <?= $days ? " ($days day".($days>1?'s':'').")" : '' ?>
          </div>
        </div>
        <div style="text-align:right">
          <?php if ($stop['budget']): ?>
          <div style="font-size:13px;color:var(--muted)">Stop Budget</div>
          <div style="font-size:16px;font-weight:700;color:var(--gold)">$<?= number_format($stop['budget']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <?php
      // Group by day
      $byDay = [];
      foreach ($acts as $act) {
          $k = $act['activity_date'] ?: 'unscheduled';
          $byDay[$k][] = $act;
      }
      ?>
      <?php if (empty($acts)): ?>
      <div style="color:var(--muted);font-size:13px">No activities planned for this stop.</div>
      <?php else: ?>
      <?php foreach ($byDay as $d => $dayActs): ?>
      <div style="margin-bottom:14px">
        <div style="font-size:12px;font-weight:600;color:var(--accent);margin-bottom:8px;letter-spacing:.5px">
          <?= $d!=='unscheduled' ? '📅 '.date('l, M j, Y',strtotime($d)) : 'Unscheduled' ?>
        </div>
        <?php foreach ($dayActs as $i => $act): ?>
        <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-top:1px solid var(--border)">
          <div style="width:28px;height:28px;border-radius:50%;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--accent);flex-shrink:0"><?= $i+1 ?></div>
          <div style="flex:1">
            <div style="font-size:14px;font-weight:600"><?= h($act['name']) ?></div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">
              <?= h($act['category']) ?> · <?= $act['duration_hrs'] ?>h
              <?php if ($act['description']): ?> · <?= h($act['description']) ?><?php endif; ?>
            </div>
          </div>
          <div style="font-size:14px;font-weight:700;color:var(--gold)">$<?= number_format($act['cost'],2) ?></div>
        </div>
        <?php endforeach; ?>
        <div style="text-align:right;font-size:12px;color:var(--muted);margin-top:6px">
          Day total: $<?= number_format(array_sum(array_column($dayActs,'cost')),2) ?>
        </div>
      </div>
      <?php endforeach; ?>
      <div style="text-align:right;font-size:14px;font-weight:700;color:var(--gold);padding-top:10px;border-top:1px solid var(--border2)">
        Stop Total: $<?= number_format(array_sum(array_column($acts,'cost')),2) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (!empty($stops)):
      $allActs = [];
      foreach ($stops as $s) $allActs = array_merge($allActs, acts_of($s['id']));
      $grandTotal = array_sum(array_column($allActs,'cost'));
    ?>
    <div class="card" style="border:1px solid var(--gold);background:rgba(245,158,11,.05)">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div style="font-size:16px;font-weight:600">Grand Total (Activities)</div>
        <div style="font-size:24px;font-weight:700;color:var(--gold)">$<?= number_format($grandTotal,2) ?></div>
      </div>
      <?php if ($trip['budget']): ?>
      <div style="font-size:13px;color:var(--muted);margin-top:6px">
        Budget: $<?= number_format($trip['budget']) ?> ·
        <span class="<?= $grandTotal>$trip['budget']?'over-budget':'under-budget' ?>">
          <?= $grandTotal>$trip['budget'] ? '⚠️ Over by $'.number_format($grandTotal-$trip['budget'],2) : '✅ Under by $'.number_format($trip['budget']-$grandTotal,2) ?>
        </span>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════ BUDGET ══════════════════════════════ -->
    <?php elseif ($page === 'budget'):
      $tid = (int)($_GET['tid'] ?? 0);
      $trip = trip_by($tid);
      if (!$trip) { echo "<div class='card'>Trip not found.</div>"; goto end; }
      $expenses = expenses_of($tid);
      $subtotal = array_sum(array_column($expenses,'amount'));
      $tax = round($subtotal * 0.05, 2);
      $discount = 0;
      $grand = $subtotal + $tax - $discount;
      $catTotals = [];
      foreach ($expenses as $e) {
          $catTotals[$e['category']] = ($catTotals[$e['category']]??0) + $e['amount'];
      }
      $remaining = $trip['budget'] - $grand;
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px">
      <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:22px"><?= h($trip['name']) ?></h2>
        <div style="color:var(--muted);font-size:13px">Budget & Expense Tracker</div>
      </div>
      <a href="?page=itinerary_view&tid=<?= $tid ?>" class="btn btn-secondary btn-sm">← Itinerary</a>
    </div>

    <div class="budget-summary">
      <div class="budget-circle" style="background:conic-gradient(var(--accent) <?= $trip['budget']>0?min(100,round($grand/$trip['budget']*100),100):0 ?>%, var(--border) 0)">
        <div style="background:var(--bg3);width:70px;height:70px;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center">
          <div style="font-size:11px;color:var(--muted)">Spent</div>
          <div style="font-size:13px;font-weight:700"><?= $trip['budget']>0?round($grand/$trip['budget']*100).'%':'–' ?></div>
        </div>
      </div>
      <div class="budget-stats">
        <div class="budget-stat-row"><span style="color:var(--muted)">Total Budget</span><strong>$<?= number_format($trip['budget'],2) ?></strong></div>
        <div class="budget-stat-row"><span style="color:var(--muted)">Total Spent</span><strong>$<?= number_format($grand,2) ?></strong></div>
        <div class="budget-stat-row">
          <span style="color:var(--muted)">Remaining</span>
          <strong class="<?= $remaining<0?'over-budget':'under-budget' ?>">$<?= number_format(abs($remaining),2) ?> <?= $remaining<0?'over':'left' ?></strong>
        </div>
      </div>
      <?php if (!empty($catTotals)): ?>
      <div style="flex:1">
        <?php foreach ($catTotals as $cat=>$amt): ?>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0">
          <span style="color:var(--muted)"><?= h($cat) ?></span>
          <span style="font-weight:600">$<?= number_format($amt,2) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Add Expense -->
    <div class="card" style="margin-bottom:22px">
      <div class="card-title" style="margin-bottom:16px">+ Log Expense</div>
      <form method="POST">
        <input type="hidden" name="action" value="add_expense">
        <input type="hidden" name="trip_id" value="<?= $tid ?>">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category" class="form-control">
              <option>Hotel</option><option>Transport</option><option>Food</option>
              <option>Activities</option><option>Shopping</option><option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Description *</label>
            <input type="text" name="description" class="form-control" placeholder="Hotel booking Paris" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Qty/Details</label>
            <input type="text" name="qty" class="form-control" placeholder="3 nights">
          </div>
          <div class="form-group">
            <label class="form-label">Unit Cost ($)</label>
            <input type="number" name="unit_cost" class="form-control" placeholder="100" min="0" step="0.01">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Total Amount ($)</label>
          <input type="number" name="amount" class="form-control" placeholder="300" min="0" step="0.01" required>
        </div>
        <button class="btn btn-primary" type="submit">Log Expense</button>
      </form>
    </div>

    <!-- Expense Table -->
    <div class="card">
      <div class="card-title" style="margin-bottom:16px">Expense Invoice</div>
      <?php if (empty($expenses)): ?>
      <div style="text-align:center;padding:30px;color:var(--muted)">No expenses logged yet.</div>
      <?php else: ?>
      <div style="overflow-x:auto">
        <table class="tbl">
          <thead><tr>
            <th>#</th><th>Category</th><th>Description</th><th>Qty/Details</th>
            <th>Unit Cost</th><th>Amount</th><th></th>
          </tr></thead>
          <tbody>
          <?php foreach ($expenses as $i => $e): ?>
          <tr>
            <td style="color:var(--muted)"><?= $i+1 ?></td>
            <td><?= h($e['category']) ?></td>
            <td><?= h($e['description']) ?></td>
            <td style="color:var(--muted)"><?= h($e['qty']) ?></td>
            <td>$<?= number_format($e['unit_cost'],2) ?></td>
            <td style="font-weight:600">$<?= number_format($e['amount'],2) ?></td>
            <td>
              <form method="POST">
                <input type="hidden" name="action" value="delete_expense">
                <input type="hidden" name="expense_id" value="<?= $e['id'] ?>">
                <input type="hidden" name="trip_id" value="<?= $tid ?>">
                <button class="btn btn-danger btn-icon btn-sm" onclick="return confirm('Remove?')">✕</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="5" style="text-align:right;color:var(--muted);font-size:13px">Subtotal</td><td colspan="2" style="font-weight:600">$<?= number_format($subtotal,2) ?></td></tr>
            <tr><td colspan="5" style="text-align:right;color:var(--muted);font-size:13px">Tax (5%)</td><td colspan="2" style="color:var(--muted2)">$<?= number_format($tax,2) ?></td></tr>
            <tr><td colspan="5" style="text-align:right;color:var(--muted);font-size:13px">Discount</td><td colspan="2" style="color:var(--green)">–$<?= number_format($discount,2) ?></td></tr>
            <tr style="background:rgba(245,158,11,.05)">
              <td colspan="5" style="text-align:right;font-weight:700;font-size:16px">Grand Total</td>
              <td colspan="2" style="font-weight:700;font-size:18px;color:var(--gold)">$<?= number_format($grand,2) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ════════════════════ PACKING CHECKLIST ══════════════════════ -->
    <?php elseif ($page === 'packing'):
      $tid = (int)($_GET['tid'] ?? 0);
      $trip = trip_by($tid);
      if (!$trip) { echo "<div class='card'>Trip not found.</div>"; goto end; }
      $items = checklist_of($tid);
      $total = count($items);
      $packed = count(array_filter($items, fn($i)=>$i['packed']));
      $bycat = [];
      foreach ($items as $item) $bycat[$item['category']][] = $item;
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px">
      <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:22px">🎒 <?= h($trip['name']) ?></h2>
        <div style="color:var(--muted);font-size:13px">Packing Checklist</div>
      </div>
      <div style="display:flex;gap:8px">
        <form method="POST"><input type="hidden" name="action" value="reset_checklist"><input type="hidden" name="trip_id" value="<?= $tid ?>">
          <button class="btn btn-secondary btn-sm">↺ Reset All</button>
        </form>
      </div>
    </div>

    <div class="card" style="margin-bottom:20px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
        <span style="font-weight:600">Progress</span>
        <span style="font-size:13px;color:var(--muted)"><?= $packed ?> / <?= $total ?> packed</span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" style="width:<?= $total>0?round($packed/$total*100):0 ?>%"></div>
      </div>
    </div>

    <!-- Add item -->
    <div class="card" style="margin-bottom:22px">
      <div class="card-title" style="margin-bottom:14px">+ Add Item</div>
      <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap">
        <input type="hidden" name="action" value="add_checklist">
        <input type="hidden" name="trip_id" value="<?= $tid ?>">
        <select name="category" class="form-control" style="width:170px">
          <option>Documents</option><option>Clothing</option><option>Electronics</option>
          <option>Toiletries</option><option>Medications</option><option>Other</option>
        </select>
        <input type="text" name="item" class="form-control" placeholder="Item name" style="flex:1;min-width:180px" required>
        <button class="btn btn-primary" type="submit">Add</button>
      </form>
    </div>

    <?php if (empty($items)): ?>
    <div class="card" style="text-align:center;padding:40px;color:var(--muted)">No items yet. Add things to pack above!</div>
    <?php else: ?>
    <?php
    $catIcons = ['Documents'=>'📄','Clothing'=>'👕','Electronics'=>'🔌','Toiletries'=>'🧴','Medications'=>'💊','Other'=>'📦'];
    foreach ($bycat as $cat => $catItems):
      $catPacked = count(array_filter($catItems,fn($i)=>$i['packed']));
    ?>
    <div class="checklist-category">
      <div class="checklist-cat-header">
        <span><?= ($catIcons[$cat]??'📦').' '.h($cat) ?></span>
        <span style="color:var(--muted)"><?= $catPacked ?>/<?= count($catItems) ?></span>
      </div>
      <?php foreach ($catItems as $item): ?>
      <div class="checklist-item">
        <form method="POST" style="margin:0">
          <input type="hidden" name="action" value="toggle_checklist">
          <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
          <input type="hidden" name="trip_id" value="<?= $tid ?>">
          <button type="submit" class="check-box <?= $item['packed']?'checked':'' ?>" style="background:none;border-color:var(--border2)"></button>
        </form>
        <label class="<?= $item['packed']?'item-packed':'' ?>"><?= h($item['item']) ?></label>
        <form method="POST">
          <input type="hidden" name="action" value="delete_checklist_item">
          <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
          <input type="hidden" name="trip_id" value="<?= $tid ?>">
          <button class="btn btn-danger btn-icon btn-sm">✕</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- ══════════════════════ TRIP NOTES ════════════════════════════ -->
    <?php elseif ($page === 'notes'):
      $tid = (int)($_GET['tid'] ?? 0);
      $trip = trip_by($tid);
      if (!$trip) { echo "<div class='card'>Trip not found.</div>"; goto end; }
      $notes = notes_of($tid);
      $stops = stops_of($tid);
      $filter_note = $_GET['filter']??'all';
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px">
      <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:22px">📝 <?= h($trip['name']) ?></h2>
        <div style="color:var(--muted);font-size:13px">Trip Notes & Journal</div>
      </div>
    </div>

    <div class="tab-bar" style="margin-bottom:20px">
      <a href="?page=notes&tid=<?=$tid?>" class="tab <?= $filter_note==='all'?'active':'' ?>">All</a>
      <a href="?page=notes&tid=<?=$tid?>&filter=day" class="tab <?= $filter_note==='day'?'active':'' ?>">By Day</a>
      <a href="?page=notes&tid=<?=$tid?>&filter=stop" class="tab <?= $filter_note==='stop'?'active':'' ?>">By Stop</a>
    </div>

    <div class="card" style="margin-bottom:20px">
      <div class="card-title" style="margin-bottom:14px">+ Add Note</div>
      <form method="POST">
        <input type="hidden" name="action" value="add_note">
        <input type="hidden" name="trip_id" value="<?= $tid ?>">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" placeholder="Hotel check-in details" required>
          </div>
          <div class="form-group">
            <label class="form-label">Date</label>
            <input type="date" name="note_day" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Stop (optional)</label>
            <select name="stop_id" class="form-control">
              <option value="">General / No Stop</option>
              <?php foreach ($stops as $s): ?>
              <option value="<?= $s['id'] ?>"><?= h($s['city']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Note Content *</label>
            <input type="text" name="content" class="form-control" placeholder="Check in after 2pm, room 302..." required>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Save Note</button>
      </form>
    </div>

    <?php if (empty($notes)): ?>
    <div class="card" style="text-align:center;padding:40px;color:var(--muted)">No notes yet. Jot something down!</div>
    <?php else: ?>
    <?php foreach ($notes as $note): ?>
    <div class="note-card">
      <div style="display:flex;align-items:flex-start;justify-content:space-between">
        <div style="flex:1">
          <div class="note-title"><?= h($note['title']) ?></div>
          <div class="note-content"><?= h($note['content']) ?></div>
          <div class="note-meta">
            <?= $note['note_day']?'📅 '.date('M j, Y',strtotime($note['note_day'])).'' :'' ?>
            <?= $note['stop_city']?' · 📍 '.h($note['stop_city']) :'' ?>
            <span style="color:var(--muted)">· <?= date('M j, Y',strtotime($note['created_at'])) ?></span>
          </div>
        </div>
        <form method="POST" style="margin-left:12px">
          <input type="hidden" name="action" value="delete_note">
          <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
          <input type="hidden" name="trip_id" value="<?= $tid ?>">
          <button class="btn btn-danger btn-icon btn-sm" onclick="return confirm('Delete note?')">🗑</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- ═════════════════════ COMMUNITY ══════════════════════════════ -->
    <?php elseif ($page === 'community'):
      $posts = db()->query("SELECT p.*,u.first_name,u.last_name,t.name as trip_name FROM community_posts p JOIN users u ON u.id=p.user_id LEFT JOIN trips t ON t.id=p.trip_id ORDER BY p.created_at DESC LIMIT 50")->fetchAll();
      $myTrips = trips_of(uid());
    ?>
    <div class="search-row">
      <input class="search-input" type="text" id="postSearch" placeholder="Search community posts...">
    </div>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">
      <div id="postsFeed">
        <?php if (empty($posts)): ?>
        <div class="card" style="text-align:center;padding:50px;color:var(--muted)">
          No community posts yet. Be the first to share!
        </div>
        <?php else: ?>
        <?php foreach ($posts as $post): ?>
        <div class="post-card post-item" data-title="<?= strtolower(h($post['title'])) ?>">
          <div class="post-header">
            <div class="avatar"><?= strtoupper(substr($post['first_name'],0,1).substr($post['last_name'],0,1)) ?></div>
            <div>
              <div style="font-size:14px;font-weight:600"><?= h($post['first_name'].' '.$post['last_name']) ?></div>
              <?php if ($post['trip_name']): ?>
              <div style="font-size:12px;color:var(--muted)">✈ <?= h($post['trip_name']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="post-title"><?= h($post['title']) ?></div>
          <div class="post-content"><?= nl2br(h($post['content'])) ?></div>
          <div class="post-meta">🕐 <?= date('M j, Y · g:ia',strtotime($post['created_at'])) ?></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div>
        <div class="card">
          <div class="card-title" style="margin-bottom:16px">🌐 Share Your Experience</div>
          <form method="POST">
            <input type="hidden" name="action" value="add_community_post">
            <div class="form-group">
              <label class="form-label">Title *</label>
              <input type="text" name="title" class="form-control" placeholder="My incredible Tokyo trip!" required>
            </div>
            <div class="form-group">
              <label class="form-label">Link a Trip (optional)</label>
              <select name="trip_id" class="form-control">
                <option value="">No trip linked</option>
                <?php foreach ($myTrips as $t): ?>
                <option value="<?= $t['id'] ?>"><?= h($t['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Share your story *</label>
              <textarea name="content" class="form-control" rows="5" placeholder="Tell the community about your experience..." required></textarea>
            </div>
            <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">Share Post</button>
          </form>
        </div>
      </div>
    </div>
    <script>
    document.getElementById('postSearch').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.post-item').forEach(el => {
        el.style.display = el.dataset.title.includes(q) ? '' : 'none';
      });
    });
    </script>

    <!-- ═════════════════════ CITY SEARCH ════════════════════════════ -->
    <?php elseif ($page === 'city_search'):
      $q = trim($_GET['q'] ?? '');
      $cities = [
        ['name'=>'Paris','country'=>'France','emoji'=>'🗼','cost'=>'High','pop'=>'Very Popular'],
        ['name'=>'Tokyo','country'=>'Japan','emoji'=>'🏯','cost'=>'Medium','pop'=>'Very Popular'],
        ['name'=>'Rome','country'=>'Italy','emoji'=>'🏛️','cost'=>'Medium','pop'=>'Popular'],
        ['name'=>'New York','country'=>'USA','emoji'=>'🗽','cost'=>'Very High','pop'=>'Very Popular'],
        ['name'=>'Bali','country'=>'Indonesia','emoji'=>'🌴','cost'=>'Low','pop'=>'Popular'],
        ['name'=>'Barcelona','country'=>'Spain','emoji'=>'🏖️','cost'=>'Medium','pop'=>'Popular'],
        ['name'=>'Dubai','country'=>'UAE','emoji'=>'🏙️','cost'=>'High','pop'=>'Very Popular'],
        ['name'=>'London','country'=>'UK','emoji'=>'🎡','cost'=>'Very High','pop'=>'Very Popular'],
        ['name'=>'Sydney','country'=>'Australia','emoji'=>'🦘','cost'=>'High','pop'=>'Popular'],
        ['name'=>'Istanbul','country'=>'Turkey','emoji'=>'🕌','cost'=>'Low','pop'=>'Growing'],
        ['name'=>'Santorini','country'=>'Greece','emoji'=>'⛪','cost'=>'High','pop'=>'Popular'],
        ['name'=>'Kyoto','country'=>'Japan','emoji'=>'⛩️','cost'=>'Medium','pop'=>'Popular'],
        ['name'=>'Marrakech','country'=>'Morocco','emoji'=>'🏺','cost'=>'Low','pop'=>'Growing'],
        ['name'=>'Cape Town','country'=>'South Africa','emoji'=>'🌅','cost'=>'Low','pop'=>'Growing'],
        ['name'=>'Prague','country'=>'Czech Republic','emoji'=>'🏰','cost'=>'Low','pop'=>'Popular'],
        ['name'=>'Amsterdam','country'=>'Netherlands','emoji'=>'🌷','cost'=>'High','pop'=>'Popular'],
      ];
      if ($q) {
          $cities = array_filter($cities, fn($c)=>stripos($c['name'],$q)!==false||stripos($c['country'],$q)!==false);
      }
      $myTrips = trips_of(uid());
    ?>
    <div class="search-row">
      <form method="GET" style="display:flex;gap:10px;flex:1">
        <input type="hidden" name="page" value="city_search">
        <input class="search-input" type="text" name="q" value="<?= h($q) ?>" placeholder="Search cities, countries...">
        <button class="btn btn-primary" type="submit">Search</button>
      </form>
    </div>

    <div class="section-title">
      <?= $q ? count($cities).' Result'.( count($cities)!=1?'s':'').' for "'.h($q).'"' : 'Top Destinations' ?>
    </div>

    <div class="grid grid-4">
      <?php foreach ($cities as $city): ?>
      <div class="card" style="text-align:center">
        <div style="font-size:40px;margin-bottom:10px"><?= $city['emoji'] ?></div>
        <div style="font-size:16px;font-weight:700"><?= h($city['name']) ?></div>
        <div style="font-size:13px;color:var(--muted);margin-bottom:10px"><?= h($city['country']) ?></div>
        <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin-bottom:12px">
          <span class="badge badge-upcoming">💰 <?= h($city['cost']) ?></span>
          <span class="badge badge-completed">⭐ <?= h($city['pop']) ?></span>
        </div>
        <?php if (!empty($myTrips)): ?>
        <form method="POST" action="?page=add_city_to_trip">
          <select class="form-control" style="margin-bottom:8px;font-size:12px" name="trip_id">
            <?php foreach ($myTrips as $t): ?>
            <option value="<?= $t['id'] ?>"><?= h($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="city_name" value="<?= h($city['name']) ?>">
          <a href="?page=create_trip" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">+ Add to Trip</a>
        </form>
        <?php else: ?>
        <a href="?page=create_trip" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">Plan Trip Here</a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ═════════════════════ PROFILE ════════════════════════════════ -->
    <?php elseif ($page === 'profile'): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:900px">
      <div class="card">
        <div class="card-title" style="margin-bottom:18px">👤 Profile Settings</div>
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:22px">
          <div class="avatar" style="width:64px;height:64px;font-size:24px">
            <?= strtoupper(substr($u['first_name'],0,1).substr($u['last_name'],0,1)) ?>
          </div>
          <div>
            <div style="font-size:17px;font-weight:700"><?= h($u['first_name'].' '.$u['last_name']) ?></div>
            <div style="font-size:13px;color:var(--muted)"><?= h($u['email']) ?></div>
            <?php if ($u['role']==='admin'): ?>
            <span class="badge badge-ongoing" style="margin-top:4px">Admin</span>
            <?php endif; ?>
          </div>
        </div>
        <form method="POST">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-control" value="<?= h($u['first_name']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control" value="<?= h($u['last_name']) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= h($u['phone']) ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">City</label>
              <input type="text" name="city" class="form-control" value="<?= h($u['city']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Country</label>
              <input type="text" name="country" class="form-control" value="<?= h($u['country']) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Bio</label>
            <textarea name="bio" class="form-control"><?= h($u['bio']) ?></textarea>
          </div>
          <div class="divider"></div>
          <div class="section-title">Change Password</div>
          <div class="form-group">
            <label class="form-label">New Password (leave blank to keep current)</label>
            <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters">
          </div>
          <button class="btn btn-primary" type="submit">Save Changes</button>
        </form>
      </div>

      <div>
        <div class="card" style="margin-bottom:18px">
          <div class="card-title" style="margin-bottom:14px">📊 My Stats</div>
          <?php
          $trips = trips_of(uid());
          $totalTrips = count($trips);
          $totalBudget = array_sum(array_column($trips,'budget'));
          $completed = count(array_filter($trips,fn($t)=>$t['status']==='completed'));
          ?>
          <div class="budget-stat-row" style="padding:8px 0;border-bottom:1px solid var(--border)">
            <span style="color:var(--muted)">Total Trips</span><strong><?= $totalTrips ?></strong>
          </div>
          <div class="budget-stat-row" style="padding:8px 0;border-bottom:1px solid var(--border)">
            <span style="color:var(--muted)">Completed</span><strong><?= $completed ?></strong>
          </div>
          <div class="budget-stat-row" style="padding:8px 0">
            <span style="color:var(--muted)">Total Budget</span><strong>$<?= number_format($totalBudget) ?></strong>
          </div>
        </div>
        <div class="card">
          <div class="card-title" style="margin-bottom:14px;color:var(--rose)">⚠️ Danger Zone</div>
          <p style="font-size:13px;color:var(--muted);margin-bottom:14px">Deleting your account is permanent and removes all your trips and data.</p>
          <button class="btn btn-danger" onclick="alert('Contact admin to delete your account.')">Delete Account</button>
        </div>
      </div>
    </div>

    <!-- ═════════════════════ ADMIN PANEL ════════════════════════════ -->
    <?php elseif ($page === 'admin'):
      requireAdmin();
      $tab = $_GET['tab']??'users';
      $users = db()->query("SELECT u.*,(SELECT COUNT(*) FROM trips WHERE user_id=u.id) as trip_count FROM users ORDER BY created_at DESC")->fetchAll();
      $tripsAll = db()->query("SELECT t.*,u.first_name,u.last_name FROM trips t JOIN users u ON u.id=t.user_id ORDER BY t.created_at DESC LIMIT 50")->fetchAll();
      $topCities = db()->query("SELECT city, COUNT(*) as cnt FROM stops GROUP BY city ORDER BY cnt DESC LIMIT 10")->fetchAll();
      $topActs = db()->query("SELECT category,COUNT(*) as cnt FROM activities GROUP BY category ORDER BY cnt DESC LIMIT 8")->fetchAll();
    ?>
    <div class="tab-bar">
      <a href="?page=admin&tab=users" class="tab <?= $tab==='users'?'active':'' ?>">👥 Manage Users</a>
      <a href="?page=admin&tab=trips" class="tab <?= $tab==='trips'?'active':'' ?>">🗺️ All Trips</a>
      <a href="?page=admin&tab=cities" class="tab <?= $tab==='cities'?'active':'' ?>">🏙️ Popular Cities</a>
      <a href="?page=admin&tab=analytics" class="tab <?= $tab==='analytics'?'active':'' ?>">📊 Analytics</a>
    </div>

    <?php if ($tab==='users'): ?>
    <div class="card">
      <div class="card-title" style="margin-bottom:16px">User Management (<?= count($users) ?>)</div>
      <div style="overflow-x:auto">
        <table class="tbl">
          <thead><tr>
            <th>User</th><th>Email</th><th>Role</th><th>Location</th><th>Trips</th><th>Joined</th><th>Actions</th>
          </tr></thead>
          <tbody>
          <?php foreach ($users as $usr): ?>
          <tr>
            <td style="display:flex;align-items:center;gap:10px">
              <div class="avatar" style="width:32px;height:32px;font-size:12px"><?= strtoupper(substr($usr['first_name'],0,1).substr($usr['last_name'],0,1)) ?></div>
              <?= h($usr['first_name'].' '.$usr['last_name']) ?>
            </td>
            <td style="color:var(--muted)"><?= h($usr['email']) ?></td>
            <td><?php if ($usr['role']==='admin'): ?><span class="badge badge-ongoing">Admin</span><?php else: ?><span class="badge">User</span><?php endif; ?></td>
            <td style="color:var(--muted)"><?= h($usr['city']?$usr['city']:'–') ?><?= $usr['country']?', '.h($usr['country']):'' ?></td>
            <td style="font-weight:600"><?= $usr['trip_count'] ?></td>
            <td style="color:var(--muted)"><?= date('M j, Y',strtotime($usr['created_at'])) ?></td>
            <td>
              <?php if ($usr['id'] != uid()): ?>
              <form method="POST" onsubmit="return confirm('Delete this user and all their data?')">
                <input type="hidden" name="action" value="admin_delete_user">
                <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                <button class="btn btn-danger btn-sm">Delete</button>
              </form>
              <?php else: ?><span style="color:var(--muted);font-size:12px">You</span><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php elseif ($tab==='trips'): ?>
    <div class="card">
      <div class="card-title" style="margin-bottom:16px">All Trips</div>
      <table class="tbl">
        <thead><tr><th>Trip</th><th>User</th><th>Status</th><th>Dates</th><th>Budget</th><th>Public</th></tr></thead>
        <tbody>
        <?php foreach ($tripsAll as $t): ?>
        <tr>
          <td style="font-weight:600"><?= h($t['name']) ?></td>
          <td style="color:var(--muted)"><?= h($t['first_name'].' '.$t['last_name']) ?></td>
          <td><?= statusBadge($t['status']) ?></td>
          <td style="color:var(--muted);font-size:12px"><?= $t['start_date']?date('M j, Y',strtotime($t['start_date'])):'-' ?></td>
          <td>$<?= number_format($t['budget']) ?></td>
          <td><?= $t['is_public']?'<span class="badge badge-completed">Public</span>':'<span style="color:var(--muted);font-size:12px">Private</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($tab==='cities'): ?>
    <div class="grid grid-2">
      <div class="card">
        <div class="card-title" style="margin-bottom:16px">🏙️ Top Cities</div>
        <?php foreach ($topCities as $i=>$c): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
          <div style="width:24px;height:24px;border-radius:50%;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--accent)"><?= $i+1 ?></div>
          <div style="flex:1;font-weight:500"><?= h($c['city']) ?></div>
          <div>
            <div style="height:6px;background:var(--border);border-radius:3px;width:100px">
              <div style="height:6px;background:var(--accent);border-radius:3px;width:<?= round($c['cnt']/$topCities[0]['cnt']*100) ?>%"></div>
            </div>
          </div>
          <div style="font-weight:600;min-width:30px;text-align:right"><?= $c['cnt'] ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($topCities)): ?><div style="color:var(--muted);text-align:center;padding:20px">No data yet.</div><?php endif; ?>
      </div>
      <div class="card">
        <div class="card-title" style="margin-bottom:16px">🎯 Popular Activities</div>
        <?php foreach ($topActs as $i=>$a): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
          <div style="width:24px;height:24px;border-radius:50%;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--teal)"><?= $i+1 ?></div>
          <div style="flex:1;font-weight:500"><?= h($a['category']) ?></div>
          <div style="font-weight:600"><?= $a['cnt'] ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($topActs)): ?><div style="color:var(--muted);text-align:center;padding:20px">No data yet.</div><?php endif; ?>
      </div>
    </div>

    <?php elseif ($tab==='analytics'): ?>
    <?php
    $totalUsers = db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalTrips2 = db()->query("SELECT COUNT(*) FROM trips")->fetchColumn();
    $totalActs = db()->query("SELECT COUNT(*) FROM activities")->fetchColumn();
    $totalExpenses = db()->query("SELECT SUM(amount) FROM expenses")->fetchColumn();
    ?>
    <div class="grid grid-4" style="margin-bottom:22px">
      <div class="stat-card" style="--accent-color:var(--accent)">
        <div class="stat-label">TOTAL USERS</div>
        <div class="stat-value"><?= $totalUsers ?></div>
      </div>
      <div class="stat-card" style="--accent-color:var(--teal)">
        <div class="stat-label">TOTAL TRIPS</div>
        <div class="stat-value"><?= $totalTrips2 ?></div>
      </div>
      <div class="stat-card" style="--accent-color:var(--gold)">
        <div class="stat-label">ACTIVITIES</div>
        <div class="stat-value"><?= $totalActs ?></div>
      </div>
      <div class="stat-card" style="--accent-color:var(--green)">
        <div class="stat-label">TOTAL EXPENSES</div>
        <div class="stat-value">$<?= number_format($totalExpenses??0) ?></div>
      </div>
    </div>
    <div class="card">
      <div class="card-title" style="margin-bottom:16px">Platform Overview</div>
      <table class="tbl">
        <thead><tr><th>Metric</th><th>Value</th></tr></thead>
        <tbody>
          <tr><td>Registered Users</td><td><strong><?= $totalUsers ?></strong></td></tr>
          <tr><td>Trips Created</td><td><strong><?= $totalTrips2 ?></strong></td></tr>
          <tr><td>Activities Planned</td><td><strong><?= $totalActs ?></strong></td></tr>
          <tr><td>Expenses Logged</td><td><strong>$<?= number_format($totalExpenses??0,2) ?></strong></td></tr>
          <tr><td>Community Posts</td><td><strong><?= db()->query("SELECT COUNT(*) FROM community_posts")->fetchColumn() ?></strong></td></tr>
          <tr><td>Notes Written</td><td><strong><?= db()->query("SELECT COUNT(*) FROM trip_notes")->fetchColumn() ?></strong></td></tr>
          <tr><td>Checklist Items</td><td><strong><?= db()->query("SELECT COUNT(*) FROM checklist_items")->fetchColumn() ?></strong></td></tr>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php endif; ?>
    <?php end: ?>
  </div><!-- .content -->
</div><!-- .main -->
</div><!-- .app -->

<style>
.hidden { display: none !important; }
@media (max-width: 900px) {
  #menu-btn { display: block !important; }
  .grid-4 { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 600px) {
  .grid-2,.grid-3,.grid-4 { grid-template-columns: 1fr; }
  .content { padding: 18px 14px; }
  .topbar { padding: 0 14px; }
  .form-row { grid-template-columns: 1fr; }
  div[style*="grid-template-columns:1fr 360px"] { grid-template-columns: 1fr !important; }
  div[style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>

<?php endif; // end main app ?>
</body>
</html>
