<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Traveloop – Personalized Travel Planning</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app">
<aside class="sidebar" id="sidebar">
  <div class="logo">
    <div class="logo-icon">✈</div>
    Traveloop
  </div>
  <nav class="nav">
    <div class="nav-section">Main</div>
    <a href="?page=dashboard" class="<?= $page==='dashboard'?'active':'' ?>"><span class="icon">🏠</span> Dashboard</a>
    <a href="?page=my_trips" class="<?= $page==='my_trips'?'active':'' ?>"><span class="icon">🗺️</span> My Trips</a>
    <a href="?page=create_trip" class="<?= $page==='create_trip'?'active':'' ?>"><span class="icon">➕</span> Plan New Trip</a>
    <div class="nav-section">Explore</div>
    <a href="?page=community" class="<?= $page==='community'?'active':'' ?>"><span class="icon">🌐</span> Community</a>
    <a href="?page=city_search" class="<?= $page==='city_search'?'active':'' ?>"><span class="icon">🔍</span> Search Cities</a>
    <?php if ($u && $u['role']==='admin'): ?>
    <div class="nav-section">Admin</div>
    <a href="?page=admin" class="<?= $page==='admin'?'active':'' ?>"><span class="icon">⚙️</span> Admin Panel</a>
    <?php endif; ?>
    <div class="nav-section">Account</div>
    <a href="?page=profile" class="<?= $page==='profile'?'active':'' ?>"><span class="icon">👤</span> Profile</a>
    <form method="POST" style="margin:2px 0">
      <input type="hidden" name="action" value="logout">
      <button type="submit" style="width:100%;justify-content:flex-start;gap:10px;border-radius:10px;background:none;color:var(--muted2);font-size:14px;padding:9px 12px;border:none;cursor:pointer;font-family:inherit;display:flex;align-items:center">
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
<div class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button onclick="document.getElementById('sidebar').classList.toggle('open')" style="display:none;background:none;border:none;color:var(--text);font-size:22px;cursor:pointer" id="menu-btn">☰</button>
      <span class="page-title">
        <?php
        $titles = [
          'dashboard'=>'Dashboard','my_trips'=>'My Trips','create_trip'=>'Plan New Trip',
          'itinerary'=>'Itinerary Builder','itinerary_view'=>'Itinerary View',
          'budget'=>'Budget & Expenses','packing'=>'Packing Checklist',
          'notes'=>'Trip Notes','community'=>'Community','profile'=>'Profile & Settings',
          'city_search'=>'City Search','admin'=>'Admin Panel'
        ];
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