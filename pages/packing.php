<?php
$tid = (int)($_GET['tid'] ?? 0);
$trip = trip_by($tid);
if (!$trip) { echo "<div class='card'>Trip not found.</div>"; return; }
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