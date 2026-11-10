// Yönetim paneli: galeri görselleri ekle / sırala / açıklama / sil.
(function(){
 'use strict';
 const g=id=>document.getElementById(id);
 async function toJpeg(file,max=1600){
  if(!isImageFile(file))throw Error('“'+file.name+'”: JPG, JPEG, PNG veya WebP seçin.');
  if(file.size>25*1024*1024)throw Error('“'+file.name+'”: dosya 25 MB’dan büyük.');
  const url=URL.createObjectURL(file);
  try{
   const img=new Image();await new Promise((ok,no)=>{img.onload=ok;img.onerror=()=>no(Error('“'+file.name+'” açılamadı.'));img.src=url});
   const k=Math.min(1,max/Math.max(img.naturalWidth,img.naturalHeight)),w=Math.max(100,Math.round(img.naturalWidth*k)),h=Math.max(100,Math.round(img.naturalHeight*k));
   const c=document.createElement('canvas');c.width=w;c.height=h;const x=c.getContext('2d');x.fillStyle='#fff';x.fillRect(0,0,w,h);x.drawImage(img,0,0,w,h);
   let q=.85,out=c.toDataURL('image/jpeg',q);while(out.length>1800000&&q>.45){q-=.08;out=c.toDataURL('image/jpeg',q)}
   if(out.length>2700000)throw Error('“'+file.name+'” çok büyük.');return out;
  }finally{URL.revokeObjectURL(url)}
 }
 let busy=false;
 function row(img,i,n,reload){
  const el=document.createElement('div');el.className='editor-card gallery-admin-item';el.style.cssText='display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-bottom:12px';
  const th=document.createElement('img');th.src=galleryUrl(img.id);th.alt='';th.style.cssText='width:110px;height:82px;object-fit:cover;border-radius:10px;flex:none';
  const cap=document.createElement('input');cap.value=img.caption||'';cap.maxLength=120;cap.placeholder='Açıklama (isteğe bağlı)';cap.style.cssText='flex:1 1 180px;min-width:0';
  const fb=document.createElement('span');fb.setAttribute('role','status');fb.style.fontSize='12px';
  cap.onchange=async()=>{try{await api('/api/admin/gallery/'+img.id,{method:'PATCH',body:JSON.stringify({caption:cap.value})});fb.textContent='Kaydedildi'}catch(e){fb.textContent=e.message}};
  const btn=(t,fn,dis)=>{const b=document.createElement('button');b.type='button';b.textContent=t;b.disabled=!!dis;b.onclick=async()=>{b.disabled=true;try{await fn();await reload()}catch(e){fb.textContent=e.message;b.disabled=false}};return b};
  const move=dir=>()=>api('/api/admin/gallery/'+img.id,{method:'PATCH',body:JSON.stringify({move:dir})});
  const del=btn('Sil',async()=>{if(!confirm('Bu görsel galeriden silinsin mi?'))throw Error('');await api('/api/admin/gallery/'+img.id,{method:'DELETE',body:'{}'})});
  el.append(th,cap,btn('↑',move('up'),i===0),btn('↓',move('down'),i===n-1),del,fb);return el;
 }
 async function load(){
  const list=g('gallery-admin-list');if(!list)return;
  try{const {images}=await api('/api/gallery');list.replaceChildren();if(!images.length){list.textContent='Henüz görsel yok.';return}images.forEach((im,i)=>list.append(row(im,i,images.length,load)))}
  catch(e){list.textContent=e.message}
 }
 document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-tab="gallery"]').forEach(a=>a.addEventListener('click',load));
  const input=g('gallery-file');if(!input)return;
  input.onchange=async()=>{
   if(busy)return;const files=[...input.files];if(!files.length)return;busy=true;const fb=g('gallery-feedback');let ok=0;
   for(const [i,f] of files.entries()){
    fb.textContent='Yükleniyor… ('+(i+1)+'/'+files.length+')';
    try{await api('/api/admin/gallery',{method:'POST',body:JSON.stringify({image:await toJpeg(f),caption:''})});ok++}
    catch(e){fb.textContent=e.message;break}
   }
   if(ok===files.length)fb.textContent=ok+' görsel eklendi.';input.value='';busy=false;await load();
  };
 });
})();
