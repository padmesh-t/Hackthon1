<div class="auth-wrap">
<div class="auth-card" style="max-width:560px">
  <div class="auth-logo">✈ Traveloop</div>
  <p class="auth-sub">Create your account</p>
  <?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= h($flash['msg']) ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="action" value="register">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">First Name</label>
        <input type="text" name="first_name" class="form-control" placeholder="Jane" required>
      </div>
      <div class="form-group">
        <label class="form-label">Last Name</label>
        <input type="text" name="last_name" class="form-control" placeholder="Doe" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="text" name="phone" class="form-control" placeholder="+1 555 0000">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" placeholder="New York">
      </div>
      <div class="form-group">
        <label class="form-label">Country</label>
        <input type="text" name="country" class="form-control" placeholder="USA">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
    </div>
    <div class="form-group">
      <label class="form-label">About You (optional)</label>
      <textarea name="bio" class="form-control" placeholder="Travel enthusiast..."></textarea>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Create Account</button>
  </form>
  <div style="text-align:center;margin-top:18px;font-size:14px;color:var(--muted)">
    Already have an account? <a href="?page=login" class="auth-link">Sign In</a>
  </div>
</div>
</div>