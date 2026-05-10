<?php
$tid = (int)($_GET['tid'] ?? 0);
$trip = trip_by($tid);
if (!$trip) { echo "<div class='card'>Trip not found.</div>"; return; }
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