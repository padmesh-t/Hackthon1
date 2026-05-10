<?php
$posts = db()->query("SELECT p.*,u.first_name,u.last_name,t.name as trip_name FROM community_posts p JOIN users u ON u.id=p.user_id LEFT JOIN trips t ON t.id=p.trip_id ORDER BY p.created_at DESC LIMIT 50")->fetchAll();
$myTrips = trips_of(uid());
?>
<div class="search-row">
  <input class="search-input" type="text" id="postSearch" placeholder="Search community posts...">
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">
  <div id="postsFeed">
    <?php if (empty($posts)): ?>
    <div class="card" style="text-align:center;padding:50px;color:var(--muted)">
      No community posts yet. Be the first to share!
    </div>
    <?php else: ?>
    <?php foreach ($posts as $post): ?>
    <div class="post-card post-item" data-title="<?= strtolower(h($post['title'])) ?>">
      <div class="post-header">
        <div class="avatar"><?= strtoupper(substr($post['first_name'],0,1).substr($post['last_name'],0,1)) ?></div>
        <div>
          <div style="font-size:14px;font-weight:600"><?= h($post['first_name'].' '.$post['last_name']) ?></div>
          <?php if ($post['trip_name']): ?>
          <div style="font-size:12px;color:var(--muted)">✈ <?= h($post['trip_name']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="post-title"><?= h($post['title']) ?></div>
      <div class="post-content"><?= nl2br(h($post['content'])) ?></div>
      <div class="post-meta">🕐 <?= date('M j, Y · g:ia',strtotime($post['created_at'])) ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div>
    <div class="card">
      <div class="card-title" style="margin-bottom:16px">🌐 Share Your Experience</div>
      <form method="POST">
        <input type="hidden" name="action" value="add_community_post">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" placeholder="My incredible Tokyo trip!" required>
        </div>
        <div class="form-group">
          <label class="form-label">Link a Trip (optional)</label>
          <select name="trip_id" class="form-control">
            <option value="">No trip linked</option>
            <?php foreach ($myTrips as $t): ?>
            <option value="<?= $t['id'] ?>"><?= h($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Share your story *</label>
          <textarea name="content" class="form-control" rows="5" placeholder="Tell the community about your experience..." required></textarea>
        </div>
        <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">Share Post</button>
      </form>
    </div>
  </div>
</div>
<script>
document.getElementById('postSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.post-item').forEach(el => {
    el.style.display = el.dataset.title.includes(q) ? '' : 'none';
  });
});
</script>