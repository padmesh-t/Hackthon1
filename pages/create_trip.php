<div style="max-width:680px">
  <div class="card">
    <div class="card-title" style="margin-bottom:20px">🗺️ Plan a New Trip</div>
    <form method="POST">
      <input type="hidden" name="action" value="create_trip">
      <div class="form-group">
        <label class="form-label">Trip Name *</label>
        <input type="text" name="name" class="form-control" placeholder="Paris & Rome Adventure" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Start Date</label>
          <input type="date" name="start_date" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">End Date</label>
          <input type="date" name="end_date" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Estimated Budget ($)</label>
        <input type="number" name="budget" class="form-control" placeholder="5000" min="0" step="0.01">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" placeholder="Describe your trip..."></textarea>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
          <input type="checkbox" name="is_public" value="1" style="width:16px;height:16px;accent-color:var(--accent)">
          Make this trip public (visible to community)
        </label>
      </div>
      <button type="submit" class="btn btn-primary">Create Trip & Build Itinerary →</button>
    </form>
  </div>
</div>