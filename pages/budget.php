<?php
$tid = (int)($_GET['tid'] ?? 0);
$trip = trip_by($tid);
if (!$trip) { echo "<div class='card'>Trip not found.</div>"; return; }
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
  <div class="budget-circle" style="background:conic-gradient(var(--accent) <?= $trip['budget']>0?min(100,round($grand/$trip['budget']*100)):0 ?>%, var(--border) 0)">
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

<div class="card">
  <div class="card-title" style="margin-bottom:16px">Expense Invoice</div>
  <?php if (empty($expenses)): ?>
  <div style="text-align:center;padding:30px;color:var(--muted)">No expenses logged yet.</div>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table class="tbl">
      <thead><tr><th>#</th><th>Category</th><th>Description</th><th>Qty/Details</th><th>Unit Cost</th><th>Amount</th><th></th></tr></thead>
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