// Galeri: ilk 6 görsel gösterilir, "Tüm galeriyi gör" ile geri kalanı açılır; görsele tıklayınca büyür.
(function(){
 'use strict';
 const $=id=>document.getElementById(id);
 const FIRST=6;let images=[],index=0,expanded=false;
 function render(){
  const grid=$('gallery-grid');grid.replaceChildren();
  (expanded?images:images.slice(0,FIRST)).forEach((img,i)=>{
   const b=document.createElement('button');b.type='button';b.className='gallery-item';b.setAttribute('aria-label',img.caption||('Galeri görseli '+(i+1)));
   const el=document.createElement('img');el.src=galleryUrl(img.id);el.alt=img.caption||'';el.loading='lazy';el.decoding='async';b.append(el);b.onclick=()=>show(i);grid.append(b);
  });
  $('gallery-more').hidden=expanded||images.length<=FIRST;
 }
 function show(i){
  index=(i+images.length)%images.length;const img=images[index];
  $('gallery-big').src=galleryUrl(img.id);$('gallery-big').alt=img.caption||'';$('gallery-caption').textContent=img.caption||'';$('gallery-count').textContent=(index+1)+' / '+images.length;
  const d=$('gallery-dialog');if(typeof d.showModal==='function'){if(!d.open)d.showModal()}else d.setAttribute('open','');
 }
 document.addEventListener('DOMContentLoaded',async()=>{
  const section=$('gallery');if(!section)return;
  try{images=(await api('/api/gallery')).images}catch{images=[]}
  section.hidden=!images.length;document.querySelectorAll('[data-gallery-link]').forEach(a=>a.hidden=!images.length);
  if(!images.length)return;render();
  $('gallery-more').onclick=()=>{expanded=true;render()};
  const d=$('gallery-dialog');d.querySelectorAll('[data-close]').forEach(b=>b.onclick=()=>d.close());
  $('gallery-prev').onclick=()=>show(index-1);$('gallery-next').onclick=()=>show(index+1);
  d.addEventListener('click',e=>{const r=d.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)d.close()});
  d.addEventListener('keydown',e=>{if(e.key==='ArrowLeft')show(index-1);if(e.key==='ArrowRight')show(index+1)});
  let x=null;d.addEventListener('touchstart',e=>{x=e.touches[0].clientX},{passive:true});d.addEventListener('touchend',e=>{if(x===null)return;const dx=e.changedTouches[0].clientX-x;x=null;if(Math.abs(dx)>50)show(index+(dx<0?1:-1))});
  if(location.hash==='#gallery')section.scrollIntoView();
 });
})();
