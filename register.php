<?php
require_once 'config.php';
if(is_logged_in()) redirect(SITE_URL.'/dashboard.php');
$errors=[]; $old=[];

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $fn=trim($_POST['full_name']??''); $un=trim($_POST['username']??'');
    $pw=$_POST['password']??'';         $cf=$_POST['confirm']??'';
    $bio=trim($_POST['bio']??'');       $old=compact('fn','un','bio');

    if(!$fn||mb_strlen($fn)>100) $errors['full_name']=!$fn?'Full name required.':'Max 100 chars.';
    if(!$un||!preg_match('/^[a-zA-Z0-9_]{3,20}$/',$un)) $errors['username']='3–20 chars (letters, numbers, _ only).';
    if(!$pw||mb_strlen($pw)<8)   $errors['password']=!$pw?'Password required.':'Min 8 characters.';
    if($pw!==$cf)                $errors['confirm']='Passwords do not match.';

    if(empty($errors['username'])){
        $st=$conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $st->bind_param('s',$un); $st->execute();
        if($st->get_result()->num_rows) $errors['username']='Username already taken.';
        $st->close();
    }
    if(!$errors){
        $hash=password_hash($pw,PASSWORD_BCRYPT);
        $st=$conn->prepare("INSERT INTO users(full_name,username,password,role,bio) VALUES(?,?,?,'user',?)");
        $st->bind_param('ssss',$fn,$un,$hash,$bio);
        if($st->execute()){
            session_regenerate_id(true);
            $_SESSION['user_id']=$conn->insert_id; $_SESSION['username']=$un;
            $_SESSION['full_name']=$fn; $_SESSION['role']='user';
            $_SESSION['flash']=['type'=>'success','msg'=>"Welcome to Inkgram, $fn!"];
            $st->close(); redirect(SITE_URL.'/dashboard.php');
        }
        $errors['db']='Registration failed.'; $st->close();
    }
}
$page_title='Create Account'; require 'header.php';
?>
<div class="auth-wrapper"><div class="auth-card">
  <p class="auth-card__eyebrow">Join the Community</p>
  <h1 class="auth-card__title">Create Account</h1>
  <p class="auth-card__sub">Start writing and sharing your essays.</p>
  <?php if(!empty($errors['db'])): ?><div class="flash flash-error"><?= e($errors['db']) ?></div><?php endif; ?>
  <form id="rf" method="POST" novalidate>
    <?= csrf_field() ?>
    <?php
    $fields=[
      ['full_name','text','Full Name','Eleanor Voss',$old['fn']??''],
      ['username','text','Username','eleanor_writes',$old['un']??''],
      ['password','password','Password','Minimum 8 characters',''],
      ['confirm','password','Confirm Password','Re-enter password',''],
    ];
    foreach($fields as [$name,$type,$label,$ph,$val]): ?>
      <div class="form-group">
        <label for="<?= $name ?>"><?= $label ?></label>
        <?php if($type==='password'): ?>
          <div class="password-wrap">
            <input type="password" id="<?= $name ?>" name="<?= $name ?>" placeholder="<?= $ph ?>" class="<?= isset($errors[$name])?'invalid':'' ?>">
            <button type="button" class="btn-toggle-pw" aria-label="Toggle password" onclick="togglePw(this)"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          </div>
        <?php else: ?>
          <input type="text" id="<?= $name ?>" name="<?= $name ?>" value="<?= e($val) ?>" placeholder="<?= $ph ?>" class="<?= isset($errors[$name])?'invalid':'' ?>">
          <?php if($name==='username'): ?><div class="field-hint" id="un-hint">3–20 characters (letters, numbers, _)</div><?php endif; ?>
        <?php endif; ?>
        <span class="field-error <?= isset($errors[$name])?'visible':'' ?>" id="e_<?= $name ?>"><?= e($errors[$name]??'') ?></span>
      </div>
    <?php endforeach; ?>
    <div class="form-group">
      <label for="bio">Short Bio <span style="font-weight:400;text-transform:none">(optional)</span></label>
      <textarea id="bio" name="bio" rows="3" placeholder="Tell readers about yourself…"><?= e($old['bio']??'') ?></textarea>
      <div class="char-counter" id="bc">0 / 300</div>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%">Create My Account</button>
  </form>
  <div class="auth-card__footer">Already have an account? <a href="login.php">Sign in</a></div>
</div></div>
<?php require 'footer.php'; ?>
<script>
function togglePw(b){const i=b.previousElementSibling,isP=i.type==='password';i.type=isP?'text':'password';b.innerHTML=isP?'<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>':'<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';}
const unInp=document.getElementById('username'),unH=document.getElementById('un-hint'),bio=document.getElementById('bio'),bc=document.getElementById('bc');
bio.oninput=()=>{bc.textContent=bio.value.length+' / 300';bc.classList.toggle('warn',bio.value.length>240)};
let ut; unInp.oninput=()=>{
  clearTimeout(ut); const v=unInp.value.trim(),err=document.getElementById('e_username');
  if(!v){err.classList.remove('visible');unH.textContent='3–20 characters (letters, numbers, _)';unH.className='field-hint';return;}
  if(!/^[a-zA-Z0-9_]{3,20}$/.test(v)){err.textContent='Must be 3–20 letters, numbers, or _';err.classList.add('visible');unH.className='field-hint';return;}
  ut=setTimeout(()=>fetch('check_username.php?username='+encodeURIComponent(v)).then(r=>r.json()).then(d=>{
    err.classList.toggle('visible',!d.available); err.textContent=d.available?'':d.msg;
    unH.textContent=d.available?'✓ '+d.msg:''; unH.className=d.available?'field-hint avail-ok':'field-hint';
  }),200);
};
document.getElementById('rf').onsubmit=function(e){
  let ok=1;
  const rules={
    full_name:v=>v.trim()?'':'Full name required.',
    username:v=>/^[a-zA-Z0-9_]{3,20}$/.test(v)?'':'3–20 chars (letters, numbers, _ only).',
    password:v=>v.length>=8?'':'Min 8 characters.',
    confirm:v=>v===document.getElementById('password').value?'':'Passwords do not match.'
  };
  Object.keys(rules).forEach(id=>{
    const el=document.getElementById(id),er=document.getElementById('e_'+id),m=rules[id](el.value);
    el.classList.toggle('invalid',!!m); er.textContent=m; er.classList.toggle('visible',!!m);
    if(m) ok=0;
  });
  if(!ok){e.preventDefault();document.querySelector('.invalid')?.scrollIntoView({behavior:'smooth',block:'center'});}
};
</script>
