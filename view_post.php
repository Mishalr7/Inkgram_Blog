<?php
require_once 'config.php';
$slug = trim($_GET['slug'] ?? '');
if(!$slug) redirect(SITE_URL.'/index.php');

$st=$conn->prepare("SELECT p.*,u.full_name,u.username,u.bio AS author_bio,c.name AS cat FROM posts p JOIN users u ON u.id=p.user_id LEFT JOIN categories c ON c.id=p.category_id WHERE p.slug=? LIMIT 1");
$st->bind_param('s',$slug); $st->execute();
$post=$st->get_result()->fetch_assoc(); $st->close();

if(!$post){
    http_response_code(404);
    $page_title='Not Found'; require 'header.php'; ?>
    <div class="container" style="padding:4rem 0;text-align:center">
      <p style="font-size:4rem">📖</p>
      <h1 class="font-serif" style="font-size:2rem;margin:.5rem 0">Essay Not Found</h1>
      <p class="text-muted">This page may have been moved or unpublished.</p>
      <a href="index.php" class="btn btn-primary mt-lg">← Back to Home</a>
    </div>
    <?php require 'footer.php'; exit;
}

$owner = is_logged_in() && $_SESSION['user_id'] == $post['user_id'];
$page_title = $post['title'];
require 'header.php';
?>
<div id="reading-progress"></div>
<div class="container">
  <article class="single-post">
    <div class="single-post__eyebrow">
      <?php if($post['cat']): ?><a href="index.php?category=<?= urlencode($post['cat']) ?>" class="tag"><?= e($post['cat']) ?></a><?php endif; ?>
      <span class="text-muted text-sm"><?= fmt_date($post['created_at']) ?></span>
    </div>

    <h1 class="single-post__title"><?= e($post['title']) ?></h1>
    <?php if($post['excerpt']): ?><p class="single-post__excerpt"><?= e($post['excerpt']) ?></p><?php endif; ?>

    <div class="single-post__byline">
      <div style="width:44px;height:44px;border-radius:50%;background:var(--clr-accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;flex-shrink:0">
        <?= strtoupper($post['full_name'][0]) ?>
      </div>
      <div>
        <p style="font-weight:600;font-size:.95rem;margin:0"><?= e($post['full_name']) ?></p>
        <p style="font-size:.8rem;color:var(--clr-muted);margin:0">@<?= e($post['username']) ?></p>
      </div>
    </div>

    <div class="single-post__content" id="post-body">
      <?php foreach(array_filter(array_map('trim',explode("\n\n",$post['content']))) as $para): ?>
        <p><?= nl2br(e($para)) ?></p>
      <?php endforeach; ?>
    </div>

    <div class="post-actions">
      <a href="index.php" class="btn btn-ghost">← All Essays</a>
      <?php if($owner || is_admin()): ?>
        <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn btn-ghost">Edit</a>
        <form method="POST" action="delete_post.php" onsubmit="return confirm('Permanently delete this essay?')">
          <?= csrf_field() ?>
          <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
          <button type="submit" class="btn btn-danger">Delete</button>
        </form>
      <?php elseif(!is_logged_in()): ?>
        <a href="register.php" class="btn btn-primary">Start Writing on Inkgram</a>
      <?php endif; ?>
    </div>
  </article>

  <?php if($post['author_bio']): ?>
  <div style="max-width:720px;margin:0 auto 4rem;background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-lg);padding:1.5rem;display:flex;gap:1rem;align-items:flex-start">
    <div style="width:48px;height:48px;border-radius:50%;background:var(--clr-accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;flex-shrink:0"><?= strtoupper($post['full_name'][0]) ?></div>
    <div>
      <p style="font-weight:600;margin-bottom:.25rem"><?= e($post['full_name']) ?></p>
      <p style="color:var(--clr-muted);font-size:.9rem;margin:0"><?= e($post['author_bio']) ?></p>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
<script>
  const bar=document.getElementById('reading-progress'),body=document.getElementById('post-body');
  window.addEventListener('scroll',()=>{const r=body.getBoundingClientRect(),pct=Math.min(Math.max(((window.innerHeight-r.top)/(r.bottom-r.top))*100,0),100);bar.style.width=pct+'%';},{passive:true});
</script>
