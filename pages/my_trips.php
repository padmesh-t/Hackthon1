<?php
$trips = trips_of(uid());
$filter = $_GET['status'] ?? 'all';
?>
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
    <div class="trip-cover"><span style="z-index:1;font-size:36px"><?php $emojis=['🏙️','🏖️','🏔️','🌆','🗼','🏛️','🌴','🏝️']; echo $emojis[crc32($t['name'])%8]; ?></span><div class="overlay"></div></div>
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