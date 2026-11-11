<?php
require_once 'config.php';
$cat  = trim($_GET['category'] ?? '');
$q    = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;

// ── Build a safe parameterised query ────────────────────────────────────────
$where_parts = [];
$bind_types  = '';
$bind_vals   = [];

if ($cat !== '') {
    $where_parts[] = 'c.name = ?';
    $bind_types   .= 's';
    $bind_vals[]   = $cat;
}
if ($q !== '') {
    $like = '%' . $q . '%';
    $where_parts[] = '(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)';
    $bind_types   .= 'sss';
    $bind_vals[]   = $like;
    $bind_vals[]   = $like;
    $bind_vals[]   = $like;
}

$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

// ── Count total for pagination ───────────────────────────────────────────────
$count_sql = "SELECT COUNT(*) FROM posts p
              JOIN users u ON u.id=p.user_id
              LEFT JOIN categories c ON c.id=p.category_id
              $where_sql";
$st = $conn->prepare($count_sql);
if ($bind_types) {
    $st->bind_param($bind_types, ...$bind_vals);
}
$st->execute();
$total_posts = (int)$st->get_result()->fetch_row()[0];
$st->close();
$total_pages = (int)ceil($total_posts / $per_page);

// ── Fetch current page ───────────────────────────────────────────────────────
$fetch_sql = "SELECT p.title, p.slug, p.excerpt, p.created_at, u.full_name, c.name AS cat
              FROM posts p
              JOIN users u ON u.id=p.user_id
              LEFT JOIN categories c ON c.id=p.category_id
              $where_sql
              ORDER BY p.created_at DESC
              LIMIT ? OFFSET ?";
$st = $conn->prepare($fetch_sql);
$page_types = $bind_types . 'ii';
$page_vals  = array_merge($bind_vals, [$per_page, $offset]);
$st->bind_param($page_types, ...$page_vals);
$st->execute();
$posts = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

$cats = $conn->query("SELECT name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$page_title = 'Home'; $active = 'home';
require 'header.php';
?>
<section class="page-hero"><div class="container">
  <p class="page-hero__eyebrow">A Publishing Platform for Careful Minds</p>
  <h1 class="page-hero__title">Ideas worth <em>writing down.</em></h1>
  <p class="page-hero__subtitle">Inkgram is a home for long-form essays — written by humans who still read to the end.</p>
  
  <form method="GET" action="index.php" class="d-flex gap-sm mt-lg" style="max-width:520px;flex-wrap:wrap">
    <?php if($cat): ?><input type="hidden" name="category" value="<?= e($cat) ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search essays by keyword…" style="flex:1;min-width:220px">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if($q || $cat): ?><a href="index.php" class="btn btn-ghost">Reset</a><?php endif; ?>
  </form>

  <?php if(!is_logged_in()): ?>
    <div class="d-flex gap-md mt-md">
      <a href="register.php" class="btn btn-primary btn-sm">Start Writing</a>
      <a href="#posts" class="btn btn-ghost btn-sm">Browse Essays</a>
    </div>
  <?php else: ?>
    <a href="create_post.php" class="btn btn-primary btn-sm mt-md">+ Write New Essay</a>
  <?php endif; ?>
</div></section>

<?php if($cats): ?>
<div style="padding:1rem 0;border-bottom:1px solid var(--clr-border);margin-bottom:2.5rem;">
  <div class="container"><div class="d-flex gap-sm align-center" style="flex-wrap:wrap">
    <span class="text-muted text-sm">Filter:</span>
    <a href="index.php" class="tag" style="<?= !$cat?'background:var(--clr-accent);color:#fff;border-color:var(--clr-accent)':'' ?>">All</a>
    <?php foreach($cats as $c): ?>
      <a href="?category=<?= urlencode($c['name']) ?>" class="tag" style="<?= $cat===$c['name']?'background:var(--clr-accent);color:#fff;border-color:var(--clr-accent)':'' ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div></div>
</div>
<?php endif; ?>

<section id="posts" style="padding-bottom:4rem"><div class="container">
  <h2 class="section-title">
    <?php
      if($q && $cat) echo 'Essays matching &ldquo;'.e($q).'&rdquo; in &ldquo;'.e($cat).'&rdquo;';
      elseif($q) echo 'Essays matching &ldquo;'.e($q).'&rdquo;';
      elseif($cat) echo 'Essays in &ldquo;'.e($cat).'&rdquo;';
      else echo 'Recent Essays';
    ?>
  </h2>
  <?php if(!$posts): ?>
    <div class="empty-state">
      <div class="empty-state__icon">✒</div>
      <p class="empty-state__title">No essays here yet.</p>
      <a href="register.php" class="btn btn-primary mt-md">Start Writing</a>
    </div>
  <?php else: ?>
    <div class="post-grid">
      <?php foreach($posts as $p): ?>
        <article class="post-card">
          <div class="post-card__meta">
            <?php if($p['cat']): ?><span class="tag"><?= e($p['cat']) ?></span><?php endif; ?>
            <span class="text-muted text-xs"><?= fmt_date($p['created_at']) ?></span>
          </div>
          <h2 class="post-card__title"><a href="view_post.php?slug=<?= urlencode($p['slug']) ?>"><?= e($p['title']) ?></a></h2>
          <p class="post-card__excerpt"><?= e(mb_strimwidth($p['excerpt']??'',0,160,'…')) ?></p>
          <div class="post-card__footer">
            <div class="author-chip">
              <div class="author-chip__avatar"><?= strtoupper($p['full_name'][0]) ?></div>
              <span><?= e($p['full_name']) ?></span>
            </div>
            <a href="view_post.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-ghost btn-sm">Read →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div></section>
<?php if ($total_pages > 1): ?>
<div style="display:flex;justify-content:center;gap:.5rem;padding:2rem 0;">
  <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(array_filter(['category'=>$cat,'q'=>$q,'page'=>$page-1])) ?>" class="btn btn-ghost btn-sm">← Prev</a>
  <?php endif; ?>
  <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
    <a href="?<?= http_build_query(array_filter(['category'=>$cat,'q'=>$q,'page'=>$i])) ?>"
       class="btn btn-sm <?= $i===$page?'btn-primary':'btn-ghost' ?>"><?= $i ?></a>
  <?php endfor; ?>
  <?php if ($page < $total_pages): ?>
    <a href="?<?= http_build_query(array_filter(['category'=>$cat,'q'=>$q,'page'=>$page+1])) ?>" class="btn btn-ghost btn-sm">Next →</a>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php require 'footer.php'; ?>
