<?php
require_once 'config.php';
require_login();
$uid = $_SESSION['user_id'];
$admin = is_admin();

if($admin){
    $posts = $conn->query("SELECT p.id,p.title,p.slug,p.created_at,c.name AS cat,u.full_name AS author,u.username AS author_un FROM posts p JOIN users u ON u.id=p.user_id LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC")->fetch_all(MYSQLI_ASSOC);
    $users = $conn->query("SELECT u.id,u.full_name,u.username,u.role,u.created_at,COUNT(p.id) AS post_count FROM users u LEFT JOIN posts p ON p.user_id=u.id GROUP BY u.id ORDER BY u.role='admin' DESC, u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
} else {
    $st=$conn->prepare("SELECT p.id,p.title,p.slug,p.created_at,c.name AS cat FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.user_id=? ORDER BY p.created_at DESC");
    $st->bind_param('i',$uid); $st->execute();
    $posts=$st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
}

$st=$conn->prepare("SELECT bio,created_at FROM users WHERE id=? LIMIT 1");
$st->bind_param('i',$uid); $st->execute();
$me=$st->get_result()->fetch_assoc(); $st->close();

$total=count($posts);
$page_title=$admin?'Admin Panel':'Dashboard'; $active='dash';
require 'header.php';
?>
<div class="container" style="padding-top:2.5rem;padding-bottom:4rem">
  <?php render_flash(); ?>
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem">
    <div>
      <p class="page-hero__eyebrow"><?= $admin ? ' General Administration' : 'Your Writing Space' ?></p>
      <h1 class="font-serif" style="font-size:2rem;display:flex;align-items:center;gap:.75rem">
        <?= e($_SESSION['full_name']) ?> <?php if($admin): ?><span class="badge-admin">Admin</span><?php endif; ?>
      </h1>
      <?php if(!empty($me['bio'])): ?><p class="text-muted" style="margin-top:.25rem;font-size:.9rem;max-width:540px"><?= e($me['bio']) ?></p><?php endif; ?>
      <p class="text-xs text-muted mt-sm">Member since <?= fmt_date($me['created_at']) ?> · <a href="change_password.php" style="color:var(--clr-accent);font-weight:600"> Change Password</a></p>
    </div>
    <?php if(!$admin): ?><a href="create_post.php" class="btn btn-primary btn-lg">✒ Write New Essay</a><?php endif; ?>
  </div>

  <div class="dashboard-stats mb-xl">
    <div class="stat-card"><div class="stat-card__num"><?= $total ?></div><div class="stat-card__label"><?= $admin?'Total Essays':'My Essays' ?></div></div>
    <?php if($admin): ?>
      <div class="stat-card"><div class="stat-card__num" style="color:var(--clr-accent)"><?= count($users) ?></div><div class="stat-card__label">Total Users</div></div>
    <?php endif; ?>
  </div>

  <h2 class="section-title"><?= $admin ? 'All Essays (Platform-Wide)' : 'Your Essays' ?></h2>
  <?php if(!$posts): ?>
    <div class="empty-state">
      <div class="empty-state__icon">✒</div>
      <p class="empty-state__title">No essays found.</p>
      <?php if(!$admin): ?><a href="create_post.php" class="btn btn-primary mt-md">Start Writing</a><?php endif; ?>
    </div>
  <?php else: ?>
    <div style="background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-lg);overflow-x:auto;margin-bottom:3rem">
      <table class="post-table">
        <thead><tr><th>Title</th><?php if($admin): ?><th>Author</th><?php endif; ?><th>Category</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($posts as $p): ?>
          <tr>
            <td class="post-table__title"><a href="view_post.php?slug=<?= urlencode($p['slug']) ?>"><?= e($p['title']) ?></a></td>
            <?php if($admin): ?><td><span class="text-sm font-semibold"><?= e($p['author']) ?></span> <span class="text-xs text-muted">(@<?= e($p['author_un']) ?>)</span></td><?php endif; ?>
            <td><?= $p['cat'] ? '<span class="tag">'.e($p['cat']).'</span>' : '<span class="text-muted text-xs">—</span>' ?></td>
s            <td class="text-muted text-sm"><?= fmt_date($p['created_at']) ?></td>
            <td>
              <a href="edit_post.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm" style="margin-right:.35rem">Edit</a>
              <button class="btn btn-danger btn-sm" onclick="delModal('delete_post.php','post_id',<?= $p['id'] ?>,'Delete Essay?','Permanently delete &ldquo;<?= e(addslashes($p['title'])) ?>&rdquo;?','Yes, Delete')">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if($admin): ?>
    <h2 class="section-title" style="margin-top:2.5rem">User Management (<?= count($users) ?> Users)</h2>
    <div style="background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-lg);overflow-x:auto">
      <table class="post-table">
        <thead><tr><th>User</th><th>Username</th><th>Role</th><th>Essays</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($users as $u): ?>
          <tr>
            <td class="font-semibold"><?= e($u['full_name']) ?></td>
            <td><code><?= e($u['username']) ?></code></td>
            <td><span class="<?= $u['role']==='admin'?'badge-admin':'tag' ?>"><?= strtoupper($u['role']) ?></span></td>
            <td><?= $u['post_count'] ?></td>
            <td class="text-muted text-sm"><?= fmt_date($u['created_at']) ?></td>
            <td>
              <?php if($u['id'] != $uid): ?>
                <button class="btn btn-danger btn-sm" onclick="delModal('delete_user.php','user_id',<?= $u['id'] ?>,'Remove User?','Remove <strong><?= e(addslashes($u['full_name'])) ?> (@<?= e(addslashes($u['username'])) ?>)</strong> and all essays?','Remove User')">Remove User</button>
              <?php else: ?>
                <span class="text-xs text-muted">(Current Session)</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="modal-backdrop" id="del-modal">
  <div class="modal">
    <h3 id="m-head"></h3>
    <p id="m-msg"></p>
    <div class="modal__actions">
      <button class="btn btn-ghost" onclick="document.getElementById('del-modal').classList.remove('open')">Cancel</button>
      <form id="m-form" method="POST" style="display:inline">
        <input type="hidden" name="id" id="m-id">
        <button type="submit" class="btn btn-danger" id="m-btn"></button>
      </form>
    </div>
  </div>
</div>
<?php require 'footer.php'; ?>
<script>
function delModal(act,key,id,h,msg,btn){
  const f=document.getElementById('m-form'),i=document.getElementById('m-id');
  f.action=act; i.name=key; i.value=id;
  document.getElementById('m-head').textContent=h;
  document.getElementById('m-msg').innerHTML=msg;
  document.getElementById('m-btn').textContent=btn;
  document.getElementById('del-modal').classList.add('open');
}
document.getElementById('del-modal').onclick=e=>{if(e.target===e.currentTarget)e.currentTarget.classList.remove('open')};
document.onkeydown=e=>{if(e.key==='Escape')document.getElementById('del-modal').classList.remove('open')};
</script>
