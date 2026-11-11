<?php
require_once 'config.php';
require_login();
$uid = $_SESSION['user_id'];
$errors = [];

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $cur = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $cfm = $_POST['confirm_password'] ?? '';

    $st = $conn->prepare("SELECT password FROM users WHERE id=? LIMIT 1");
    $st->bind_param('i', $uid); $st->execute();
    $user = $st->get_result()->fetch_assoc(); $st->close();

    if(!$cur || !password_verify($cur, $user['password'])) $errors['cur'] = 'Current password incorrect.';
    if(!$new || mb_strlen($new) < 8) $errors['new'] = 'New password must be at least 8 characters.';
    if($new !== $cfm) $errors['cfm'] = 'New passwords do not match.';

    if(!$errors){
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $st = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $st->bind_param('si', $hash, $uid);
        if($st->execute()){
            $st->close();
            $_SESSION['flash'] = ['type'=>'success', 'msg'=>'Password updated successfully!'];
            redirect(SITE_URL . '/dashboard.php');
        }
        $errors['db'] = 'Failed to update password.'; $st->close();
    }
}
$page_title = 'Change Password'; require 'header.php';
?>
<div class="auth-wrapper"><div class="auth-card">
  <p class="auth-card__eyebrow">Account Security</p>
  <h1 class="auth-card__title">Change Password</h1>
  <p class="auth-card__sub">Update your password to keep your account safe.</p>
  <?php if(!empty($errors['db'])): ?><div class="flash flash-error"><?= e($errors['db']) ?></div><?php endif; ?>
  <form id="cp-form" method="POST" novalidate>
    <?= csrf_field() ?>
    <?php
    $fields = [
      ['current_password', 'Current Password', 'Enter current password', $errors['cur'] ?? ''],
      ['new_password', 'New Password', 'Minimum 8 characters', $errors['new'] ?? ''],
      ['confirm_password', 'Confirm New Password', 'Re-enter new password', $errors['cfm'] ?? ''],
    ];
    foreach($fields as [$name, $label, $ph, $err]): ?>
      <div class="form-group">
        <label for="<?= $name ?>"><?= $label ?></label>
        <div class="password-wrap">
          <input type="password" id="<?= $name ?>" name="<?= $name ?>" placeholder="<?= $ph ?>" class="<?= $err?'invalid':'' ?>">
          <button type="button" class="btn-toggle-pw" aria-label="Toggle password" onclick="togglePw(this)"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        </div>
        <span class="field-error <?= $err?'visible':'' ?>"><?= e($err) ?></span>
      </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary" style="width:100%">Update Password</button>
    <a href="dashboard.php" class="btn btn-ghost" style="width:100%;margin-top:.5rem;text-align:center">Cancel</a>
  </form>
</div></div>
<?php require 'footer.php'; ?>
<script>
function togglePw(b){const i=b.previousElementSibling,isP=i.type==='password';i.type=isP?'text':'password';b.innerHTML=isP?'<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>':'<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';}
document.getElementById('cp-form').onsubmit=function(e){
  let ok=1;
  const cur=document.getElementById('current_password'),n=document.getElementById('new_password'),c=document.getElementById('confirm_password');
  const chk=(el,bad,msg)=>{
    el.classList.toggle('invalid',bad);
    const sp=el.closest('.form-group').querySelector('.field-error');
    if(sp){sp.textContent=bad?msg:'';sp.classList.toggle('visible',bad);}
    if(bad) ok=0;
  };
  chk(cur,!cur.value.trim(),'Current password required.');
  chk(n,n.value.length<8,'New password must be at least 8 characters.');
  chk(c,c.value!==n.value||!c.value,'Passwords do not match.');
  if(!ok) e.preventDefault();
};
</script>
