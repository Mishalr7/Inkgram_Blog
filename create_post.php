<?php
require_once 'config.php';
require_login();
if(is_admin()){
    $_SESSION['flash']=['type'=>'error','msg'=>'Administrators cannot write essays.'];
    redirect(SITE_URL.'/dashboard.php');
}
$uid = $_SESSION['user_id'];
$post = ['title'=>'','excerpt'=>'','content'=>'','cat_id'=>null];
$errors = [];
$cats = $conn->query("SELECT id,name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $title   = trim($_POST['title']??'');
    $excerpt = trim($_POST['excerpt']??'');
    $content = trim($_POST['content']??'');
    $cat_id  = intval($_POST['category_id']??0)?:null;
    $post    = array_merge($post,compact('title','excerpt','content'),['cat_id'=>$cat_id]);

    if(!$title)   $errors['title']='Title is required.';
    if(!$content) $errors['content']='Content cannot be empty.';
    if(mb_strlen($excerpt)>500) $errors['excerpt']='Excerpt max 500 chars.';

    if(!$errors){
        $slug=unique_slug($conn,make_slug($title));
        $st=$conn->prepare("INSERT INTO posts(user_id,category_id,title,slug,excerpt,content) VALUES(?,?,?,?,?,?)");
        $st->bind_param('iissss',$uid,$cat_id,$title,$slug,$excerpt,$content);
        if($st->execute()){
            $st->close();
            $_SESSION['flash']=['type'=>'success','msg'=>'Essay published successfully!'];
            redirect(SITE_URL.'/dashboard.php');
        }
        $errors['db']='Failed to publish essay.'; $st->close();
    }
}
$page_title='Write New Essay'; $active='dash';
require 'header.php';
?>
<div class="container">
  <?php if(!empty($errors['db'])): ?><div class="flash flash-error mt-md"><?= e($errors['db']) ?></div><?php endif; ?>
  <div style="display:flex;align-items:center;justify-content:space-between;padding:1.5rem 0;border-bottom:1px solid var(--clr-border);flex-wrap:wrap;gap:1rem">
    <div>
      <p class="page-hero__eyebrow">New Essay</p>
      <h1 class="font-serif" style="font-size:1.75rem">Put words to the page</h1>
    </div>
    <a href="dashboard.php" class="btn btn-ghost btn-sm">← Back</a>
  </div>

  <form id="pf" method="POST" action="create_post.php" novalidate>
    <?= csrf_field() ?>
    <div class="write-layout">
      <div>
        <div class="form-group">
          <input type="text" id="post-title" name="title" value="<?= e($post['title']) ?>" placeholder="Your essay title…" class="<?= isset($errors['title'])?'invalid':'' ?>">
          <span class="field-error <?= isset($errors['title'])?'visible':'' ?>" id="e_title"><?= e($errors['title']??'') ?></span>
        </div>
        <div class="form-group">
          <label for="excerpt">Opening Hook <span style="font-weight:400;text-transform:none">(optional)</span></label>
          <textarea id="excerpt" name="excerpt" rows="3" placeholder="A teaser shown on post cards…" class="<?= isset($errors['excerpt'])?'invalid':'' ?>"><?= e($post['excerpt']??'') ?></textarea>
          <div class="char-counter" id="ec">0 / 500</div>
          <span class="field-error <?= isset($errors['excerpt'])?'visible':'' ?>" id="e_excerpt"><?= e($errors['excerpt']??'') ?></span>
        </div>
        <div class="form-group">
          <label for="post-content">Essay Body</label>
          <textarea id="post-content" name="content" class="<?= isset($errors['content'])?'invalid':'' ?>"><?= e($post['content']??'') ?></textarea>
          <span class="field-error <?= isset($errors['content'])?'visible':'' ?>" id="e_content"><?= e($errors['content']??'') ?></span>
        </div>
      </div>
      <div class="write-sidebar">
        <h3>Publish</h3>
        <div class="form-group">
          <label for="cat">Category</label>
          <select name="category_id" id="cat">
            <option value="">— Uncategorised —</option>
            <?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($post['cat_id']??0)==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">Publish Essay</button>
        <a href="dashboard.php" class="btn btn-ghost" style="width:100%;margin-top:.5rem;text-align:center">Cancel</a>
      </div>
    </div>
  </form>
</div>
<?php require 'footer.php'; ?>
<script>
const ex=document.getElementById('excerpt'),ec=document.getElementById('ec');
ex.oninput=()=>{ec.textContent=ex.value.length+' / 500';ec.classList.toggle('warn',ex.value.length>400)};
document.getElementById('pf').onsubmit=function(e){
  let ok=1;
  [['post-title','e_title','Title is required.'],['post-content','e_content','Content cannot be empty.']].forEach(([id,eid,msg])=>{
    const el=document.getElementById(id),er=document.getElementById(eid),bad=!el.value.trim();
    el.classList.toggle('invalid',bad); er.textContent=bad?msg:''; er.classList.toggle('visible',bad);
    if(bad) ok=0;
  });
  if(ex.value.length>500) ok=0;
  if(!ok){e.preventDefault();document.querySelector('.invalid')?.scrollIntoView({behavior:'smooth',block:'center'});}
};
</script>
