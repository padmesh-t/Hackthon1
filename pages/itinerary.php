<?php
$tid = (int)($_GET['tid'] ?? 0);
$trip = trip_by($tid);
if (!$trip || $trip['user_id'] != uid()) { echo "<div class='card'>Trip not found.</div>"; return; }
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

<div class="section-title">Stops (<?= count($stops) ?>)</div>
<?php if (empty($stops)): ?>
<div class="card" style="text-align:center;padding:40px;color:var(--muted)">
  No stops added yet. Add your first city above!
</div>
<?php endif; ?>
<?php foreach ($stops as $stop):
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
      <button class="btn btn-secondary btn-sm" onclick="document.getElementById('act-form-<?= $stop['id'] ?>').classList.toggle('hidden')">+ Activity</button>
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