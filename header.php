<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="description" content="<?= e($meta_desc??'Inkgram — A place for thoughtful writing.') ?>">
  <title><?= e($page_title??'Inkgram') ?> — Inkgram</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="site-nav"><div class="container"><div class="site-nav__inner">
  <a href="index.php" class="site-nav__brand">Inkgram<span class="brand-dot">.</span></a>
  <ul class="site-nav__links">
    <li><a href="index.php"<?= ($active??'')==='home'?' class="active"':'' ?>>Home</a></li>
    <?php if(is_logged_in()): ?>
      <li><a href="dashboard.php"<?= ($active??'')==='dash'?' class="active"':'' ?>><?= is_admin()?' Admin Panel':'Dashboard' ?></a></li>
      <?php if(!is_admin()): ?><li><a href="create_post.php" class="btn btn-primary btn-sm">+ Write</a></li><?php endif; ?>
      <li><a href="logout.php" class="btn btn-ghost btn-sm">Log out</a></li>
    <?php else: ?>
      <li><a href="login.php">Sign in</a></li>
      <li><a href="register.php" class="btn btn-primary btn-sm">Join Inkgram</a></li>
    <?php endif; ?>
  </ul>
</div></div></nav>
<main>
