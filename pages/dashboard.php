<?php
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
    <div class="trip-cover"><span style="z-index:1;font-size:36px"><?php $emojis=['🏙️','🏖️','🏔️','🌆','🗼','🏛️','🌴','🏝️']; echo $emojis[crc32($t['name'])%8]; ?></span><div class="overlay"></div></div>
    <div class="trip-body">
      <div class="trip-name"><?= h($t['name']) ?></div>
      <div class="trip-meta">
        <?= statusBadge($t['status']) ?>
        <?php if ($t['start_date']): ?><span>📅 <?= date('M j, Y', strtotime($t['start_date'])) ?></span><?php endif; ?>
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