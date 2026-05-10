<?php
$tid = (int)($_GET['tid'] ?? 0);
$trip = trip_by($tid);
if (!$trip) { echo "<div class='card'>Trip not found.</div>"; return; }
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