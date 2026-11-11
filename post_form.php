<?php
require_once 'config.php';
require_login();

$uid  = $_SESSION['user_id'];
$id   = intval($_GET['id'] ?? $_POST['post_id'] ?? 0);
$edit = $id > 0;
$post = ['title'=>'','excerpt'=>'','content'=>'','cat_id'=>null,'status'=>'published','slug'=>'','updated_at'=>null];
$errors = [];

$cats = $conn->query("SELECT id,name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if($edit){
    if(is_admin()){
        $_SESSION['flash']=['type'=>'error','msg'=>'Administrators cannot edit essays.'];
        redirect(SITE_URL.'/dashboard.php');
    }
    $st=$conn->prepare("SELECT p.*,c.id AS cat_id FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.user_id=? LIMIT 1");
    $st->bind_param('ii',$id,$uid); $st->execute();
    $row=$st->get_result()->fetch_assoc(); $st->close();
    if(!$row){$_SESSION['flash']=['type'=>'error','msg'=>'Post not found or permission denied.'];redirect(SITE_URL.'/dashboard.php');}
    $post=$row;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $title   = trim($_POST['title']??'');
    $excerpt = trim($_POST['excerpt']??'');
    $content = trim($_POST['content']??'');
    $cat_id  = intval($_POST['category_id']??0)?:null;
    $status  = in_array($_POST['status']??'',['published','draft'])?$_POST['status']:'published';
    $post    = array_merge($post,compact('title','excerpt','content','status'),['cat_id'=>$cat_id]);

    if(!$title)                           $errors['title']  ='Title is required.';
    if(!$content)                         $errors['content']='Content cannot be empty.';
    if($excerpt && mb_strlen($excerpt)>500) $errors['excerpt']='Excerpt max 500 chars.';

    if(!$errors){
        if($edit){
            $st=$conn->prepare("UPDATE posts SET title=?,excerpt=?,content=?,category_id=?,status=? WHERE id=? AND user_id=?");
            $st->bind_param('sssssii',$title,$excerpt,$content,$cat_id,$status,$id,$uid);
        } else {
            $slug=unique_slug($conn,make_slug($title));
            $st=$conn->prepare("INSERT INTO posts(user_id,category_id,title,slug,excerpt,content,status) VALUES(?,?,?,?,?,?,?)");
            $st->bind_param('iisssss',$uid,$cat_id,$title,$slug,$excerpt,$content,$status);
        }
        if($st->execute()){$st->close();$_SESSION['flash']=['type'=>'success','msg'=>$edit?'Essay updated!':'Essay published!'];redirect(SITE_URL.'/dashboard.php');}
        $errors['db']='Save failed.'; $st->close();
    }
}

$page_title = $edit ? 'Edit: '.($post['title']??'') : 'Write New Essay';
$active='dash';
require 'header.php';
?>
<div class="container">
  <?php if(!empty($errors['db'])): ?><div class="flash flash-error mt-md"><?= e($errors['db']) ?></div><?php endif; ?>
  <div style="display:flex;align-items:center;justify-content:space-between;padding:1.5rem 0;border-bottom:1px solid var(--clr-border);flex-wrap:wrap;gap:1rem">
    <div>
      <p class="page-hero__eyebrow"><?= $edit?'Editing Essay':'New Essay' ?></p>
      <h1 class="font-serif" style="font-size:1.75rem"><?= $edit ? e(mb_strimwidth($post['title'],0,60,'…')) : 'Put words to the page' ?></h1>
      <?php if($edit): ?><p style="font-size:.8rem;color:var(--clr-placeholder)">Last saved: <?= fmt_date($post['updated_at']) ?> · <a href="view_post.php?slug=<?= urlencode($post['slug']) ?>" style="color:var(--clr-muted)">View live ↗</a></p><?php endif; ?>
    </div>
    <a href="dashboard.php" class="btn btn-ghost btn-sm">← Back</a>
  </div>

  <form id="pf" method="POST" action="post_form.php" novalidate>
    <?php if($edit): ?><input type="hidden" name="post_id" value="<?= $id ?>"><?php endif; ?>
    <div class="write-layout">
      <div>
        <div class="form-group">
          <input type="text" id="post-title" name="title" value="<?= e($post['title']) ?>" placeholder="Your essay title…" class="<?= isset($errors['title'])?'invalid':'' ?>">
          <span class="field-error <?= isset($errors['title'])?'visible':'' ?>" id="e_title"><?= e($errors['title']??'') ?></span>
        </div>
        <div class="form-group">
          <label for="excerpt">Opening Hook <span style="font-weight:400;text-transform:none;letter-spacing:0">(optional)</span></label>
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
          <label for="status">Status</label>
          <select name="status" id="status">
            <option value="published" <?= ($post['status']??'')==='published'?'selected':'' ?>>Published</option>
            <option value="draft"     <?= ($post['status']??'')==='draft'?'selected':'' ?>>Draft</option>
          </select>
        </div>
        <div class="form-group">
          <label for="cat">Category</label>
          <select name="category_id" id="cat">
            <option value="">— Uncategorised —</option>
            <?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($post['cat_id']??0)==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%"><?= $edit?'Save Changes':'Publish Essay' ?></button>
        <?php if($edit): ?><a href="view_post.php?slug=<?= urlencode($post['slug']) ?>" class="btn btn-ghost" style="width:100%;margin-top:.5rem;text-align:center">Preview</a><?php endif; ?>
        <a href="dashboard.php" class="btn btn-ghost" style="width:100%;margin-top:.5rem;text-align:center">Cancel</a>
      </div>
    </div>
  </form>
</div>
<?php require 'footer.php'; ?>
<script>
(function(){
  function fe(id,m){const i=document.getElementById(id),e=document.getElementById('e_'+id);if(i)i.classList.add('invalid');if(e){e.textContent=m;e.classList.add('visible');}}
  function ce(id){const i=document.getElementById(id),e=document.getElementById('e_'+id);if(i)i.classList.remove('invalid');if(e)e.classList.remove('visible');}
  const ex=document.getElementById('excerpt'),ec=document.getElementById('ec');
  function updCnt(){ec.textContent=ex.value.length+' / 500';ec.classList.toggle('warn',ex.value.length>400);}
  ex.addEventListener('input',updCnt); updCnt();
  document.getElementById('post-title').addEventListener('blur',function(){this.value.trim()?ce('title'):fe('title','Title is required.');});
  document.getElementById('post-content').addEventListener('blur',function(){this.value.trim()?ce('content'):fe('content','Content cannot be empty.');});
  document.getElementById('pf').addEventListener('submit',function(ev){
    let ok=1;
    if(!document.getElementById('post-title').value.trim()){fe('title','Title is required.');ok=0;}
    if(!document.getElementById('post-content').value.trim()){fe('content','Content cannot be empty.');ok=0;}
    if(ex.value.length>500){fe('excerpt','Max 500 chars.');ok=0;}
    if(!ok){ev.preventDefault();document.querySelector('.invalid')?.scrollIntoView({behavior:'smooth',block:'center'});}
  });
})();
</script>
