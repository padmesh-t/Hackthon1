<?php
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