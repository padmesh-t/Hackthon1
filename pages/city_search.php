<?php
$q = trim($_GET['q'] ?? '');
$cities = [
    ['name'=>'Paris','country'=>'France','emoji'=>'🗼','cost'=>'High','pop'=>'Very Popular'],
    ['name'=>'Tokyo','country'=>'Japan','emoji'=>'🏯','cost'=>'Medium','pop'=>'Very Popular'],
    ['name'=>'Rome','country'=>'Italy','emoji'=>'🏛️','cost'=>'Medium','pop'=>'Popular'],
    ['name'=>'New York','country'=>'USA','emoji'=>'🗽','cost'=>'Very High','pop'=>'Very Popular'],
    ['name'=>'Bali','country'=>'Indonesia','emoji'=>'🌴','cost'=>'Low','pop'=>'Popular'],
    ['name'=>'Barcelona','country'=>'Spain','emoji'=>'🏖️','cost'=>'Medium','pop'=>'Popular'],
    ['name'=>'Dubai','country'=>'UAE','emoji'=>'🏙️','cost'=>'High','pop'=>'Very Popular'],
    ['name'=>'London','country'=>'UK','emoji'=>'🎡','cost'=>'Very High','pop'=>'Very Popular'],
    ['name'=>'Sydney','country'=>'Australia','emoji'=>'🦘','cost'=>'High','pop'=>'Popular'],
    ['name'=>'Istanbul','country'=>'Turkey','emoji'=>'🕌','cost'=>'Low','pop'=>'Growing'],
    ['name'=>'Santorini','country'=>'Greece','emoji'=>'⛪','cost'=>'High','pop'=>'Popular'],
    ['name'=>'Kyoto','country'=>'Japan','emoji'=>'⛩️','cost'=>'Medium','pop'=>'Popular'],
    ['name'=>'Marrakech','country'=>'Morocco','emoji'=>'🏺','cost'=>'Low','pop'=>'Growing'],
    ['name'=>'Cape Town','country'=>'South Africa','emoji'=>'🌅','cost'=>'Low','pop'=>'Growing'],
    ['name'=>'Prague','country'=>'Czech Republic','emoji'=>'🏰','cost'=>'Low','pop'=>'Popular'],
    ['name'=>'Amsterdam','country'=>'Netherlands','emoji'=>'🌷','cost'=>'High','pop'=>'Popular'],
];
if ($q) {
    $cities = array_filter($cities, fn($c)=>stripos($c['name'],$q)!==false||stripos($c['country'],$q)!==false);
}
$myTrips = trips_of(uid());
?>
<div class="search-row">
  <form method="GET" style="display:flex;gap:10px;flex:1">
    <input type="hidden" name="page" value="city_search">
    <input class="search-input" type="text" name="q" value="<?= h($q) ?>" placeholder="Search cities, countries...">
    <button class="btn btn-primary" type="submit">Search</button>
  </form>
</div>

<div class="section-title">
  <?= $q ? count($cities).' Result'.( count($cities)!=1?'s':'').' for "'.h($q).'"' : 'Top Destinations' ?>
</div>

<div class="grid grid-4">
  <?php foreach ($cities as $city): ?>
  <div class="card" style="text-align:center">
    <div style="font-size:40px;margin-bottom:10px"><?= $city['emoji'] ?></div>
    <div style="font-size:16px;font-weight:700"><?= h($city['name']) ?></div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:10px"><?= h($city['country']) ?></div>
    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin-bottom:12px">
      <span class="badge badge-upcoming">💰 <?= h($city['cost']) ?></span>
      <span class="badge badge-completed">⭐ <?= h($city['pop']) ?></span>
    </div>
    <?php if (!empty($myTrips)): ?>
    <form method="POST" action="?page=add_city_to_trip">
      <select class="form-control" style="margin-bottom:8px;font-size:12px" name="trip_id">
        <?php foreach ($myTrips as $t): ?>
        <option value="<?= $t['id'] ?>"><?= h($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="hidden" name="city_name" value="<?= h($city['name']) ?>">
      <button class="btn btn-secondary btn-sm" style="width:100%;justify-content:center" type="submit">+ Add to Trip</button>
    </form>
    <?php else: ?>
    <a href="?page=create_trip" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">Plan Trip Here</a>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>