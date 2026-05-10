<div class="auth-wrap">
<div class="auth-card">
  <div class="auth-logo">✈ Traveloop</div>
  <p class="auth-sub">Plan your next adventure</p>
  <?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= h($flash['msg']) ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="action" value="login">
    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Sign In</button>
  </form>
  <div style="text-align:center;margin-top:18px;font-size:14px;color:var(--muted)">
    Don't have an account? <a href="?page=register" class="auth-link">Sign Up</a>
  </div>
  <div style="text-align:center;margin-top:10px;font-size:12px;color:var(--muted)">
    
  </div>
</div>
</div>