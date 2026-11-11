<?php
require_once 'config.php';
if(is_logged_in()) redirect(SITE_URL.'/dashboard.php');

$errors=[]; $old_un='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    if (is_rate_limited($conn)) {
        $errors['auth'] = 'Too many login attempts. Please wait 15 minutes and try again.';
    } else {
    $un=trim($_POST['username']??''); $pw=$_POST['password']??'';
    $old_un=$un;
    if(!$un) $errors['username']='Username required.';
    if(!$pw) $errors['password']='Password required.';

    if(!$errors){
        $st=$conn->prepare("SELECT id,full_name,username,password,role FROM users WHERE username=? LIMIT 1");
        $st->bind_param('s',$un); $st->execute();
        $row=$st->get_result()->fetch_assoc(); $st->close();
        if($row && password_verify($pw,$row['password'])){
            session_regenerate_id(true);
            $_SESSION['user_id']=$row['id']; $_SESSION['username']=$row['username'];
            $_SESSION['full_name']=$row['full_name']; $_SESSION['role']=$row['role'];
            $_SESSION['flash']=['type'=>'success','msg'=>'Welcome back, '.$row['full_name'].'!'];
            clear_login_attempts($conn);
            redirect(SITE_URL.'/dashboard.php');
        }
        $errors['auth']='Incorrect username or password.';
        record_login_attempt($conn);
    } // end rate-limit else
}

$page_title='Sign In';
require 'header.php';
?>
<div class="auth-wrapper"><div class="auth-card">
  <p class="auth-card__eyebrow">Welcome Back</p>
  <h1 class="auth-card__title">Sign In</h1>
  <p class="auth-card__sub">Continue where you left off.</p>

  <?php if(!empty($errors['auth'])): ?><div class="flash flash-error"><?= e($errors['auth']) ?></div><?php endif; ?>

  <form id="login-form" method="POST" novalidate>
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?= e($old_un) ?>" placeholder="e.g. admin or eleanor_writes"
             class="<?= isset($errors['username'])?'invalid':'' ?>" autocomplete="username">
      <span class="field-error <?= isset($errors['username'])?'visible':'' ?>" id="e_username"><?= e($errors['username']??'') ?></span>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <div class="password-wrap">
        <input type="password" id="password" name="password" placeholder="Your password"
               class="<?= isset($errors['password'])?'invalid':'' ?>" autocomplete="current-password">
        <button type="button" class="btn-toggle-pw" aria-label="Show password" tabindex="-1"></button>
      </div>
      <span class="field-error <?= isset($errors['password'])?'visible':'' ?>" id="e_password"><?= e($errors['password']??'') ?></span>
    </div>
    <button type="submit" id="sub-btn" class="btn btn-primary" style="width:100%">Sign In</button>
  </form>
  <div class="auth-card__footer">No account? <a href="register.php">Create one — it's free</a></div>
</div></div>
<?php require 'footer.php'; ?>
<script>
(function(){
  const eyeOn='<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  const eyeOff='<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
  document.querySelectorAll('.btn-toggle-pw').forEach(b=>{
    b.innerHTML=eyeOff;
    b.addEventListener('click',function(){
      const inp=this.previousElementSibling; const isPw=inp.type==='password';
      inp.type=isPw?'text':'password'; this.innerHTML=isPw?eyeOn:eyeOff;
      this.setAttribute('aria-label',isPw?'Hide password':'Show password');
    });
  });
  function fe(id,m){const i=document.getElementById(id),e=document.getElementById('e_'+id);if(i)i.classList.add('invalid');if(e){e.textContent=m;e.classList.add('visible');}}
  function ce(id){const i=document.getElementById(id),e=document.getElementById('e_'+id);if(i)i.classList.remove('invalid');if(e)e.classList.remove('visible');}
  document.getElementById('username').addEventListener('blur',function(){this.value.trim()?ce('username'):fe('username','Username required.');});
  document.getElementById('password').addEventListener('blur',function(){this.value?ce('password'):fe('password','Password required.');});
  document.getElementById('login-form').addEventListener('submit',function(ev){
    let ok=1;
    if(!document.getElementById('username').value.trim()){fe('username','Username required.');ok=0;}
    if(!document.getElementById('password').value){fe('password','Password required.');ok=0;}
    if(!ok){ev.preventDefault();}else{const b=document.getElementById('sub-btn');b.textContent='Signing in…';b.disabled=true;}
  });
})();
</script>
