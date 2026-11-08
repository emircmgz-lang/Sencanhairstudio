// Yönetim paneli: yorumları düzenle, gizle / yeniden yayınla.
(function(){
 const g=id=>document.getElementById(id);
 let loadedAt=0;
 const fmt=iso=>{try{return new Date(iso).toLocaleString('tr-TR',{timeZone:'Europe/Istanbul'})}catch{return ''}};
 function card(r){
  const form=document.createElement('form');form.className='editor-card';form.style.marginBottom='16px';
  const meta=document.createElement('p');meta.className='photo-help';
  meta.textContent='Yazıldı: '+fmt(r.createdAt)+(r.edited?' · yönetici düzenledi':'')+(r.appointment?' · Randevu: '+r.appointment.date+' '+r.appointment.time+(r.appointment.barberName?' · '+r.appointment.barberName:'')+' · '+r.appointment.code:'')+(r.visible?'':' · SİTEDE GİZLİ');
  const name=document.createElement('input');name.value=r.name;name.maxLength=100;name.required=true;
  const rating=document.createElement('select');for(let i=5;i>=1;i--)rating.append(new Option('★'.repeat(i)+' ('+i+')',String(i)));rating.value=String(r.rating);
  const body=document.createElement('textarea');body.value=r.body;body.rows=4;body.maxLength=1000;body.required=true;
  const vis=document.createElement('input');vis.type='checkbox';vis.checked=r.visible;
  const visLabel=document.createElement('label');visLabel.className='checkbox-label';visLabel.append(vis,document.createTextNode(' Sitede yayınla'));
  const lab=(t,el)=>{const l=document.createElement('label');l.append(t,el);return l};
  const save=document.createElement('button');save.type='submit';save.className='save-button';save.textContent='Kaydet';
  const fb=document.createElement('p');fb.setAttribute('role','status');
  form.append(meta,lab('Görünen isim',name),lab('Puan',rating),lab('Yorum metni',body),visLabel,save,fb);
  form.onsubmit=async e=>{e.preventDefault();save.disabled=true;fb.textContent='';
   try{await api('/api/admin/reviews/'+r.id,{method:'PATCH',body:JSON.stringify({name:name.value,rating:Number(rating.value),body:body.value,visible:vis.checked})});fb.textContent='Kaydedildi.';loadedAt=0;setTimeout(load,600)}
   catch(err){fb.textContent=err.message}finally{save.disabled=false}};
  return form;
 }
 async function load(){
  const list=g('reviews-admin-list');if(!list)return;
  try{const data=await api('/api/admin/reviews');loadedAt=Date.now();list.replaceChildren();
   if(!data.reviews.length){list.textContent='Henüz yorum yok.';return}
   data.reviews.forEach(r=>list.append(card(r)));
  }catch(e){list.textContent=e.message}
 }
 document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-tab="reviews"]').forEach(a=>a.addEventListener('click',()=>{if(Date.now()-loadedAt>5000)load()}));
 });
})();
