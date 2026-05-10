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