// Yorumlar: girişin hemen altında 3 yorum + "Tüm yorumları gör" penceresi. Herkes yorum yazabilir.
(function(){
 'use strict';
 const $=id=>document.getElementById(id);
 let data={reviews:[],count:0,average:null,byBarber:{}},barbers=[],rating=0;
 const fmtAvg=n=>String(n).replace('.',',');
 const day=iso=>{try{return new Date(iso).toLocaleDateString('tr-TR',{dateStyle:'long',timeZone:'Europe/Istanbul'})}catch{return ''}};
 const stars=n=>{const s=document.createElement('span');s.className='review-stars';s.setAttribute('role','img');s.setAttribute('aria-label',n+' / 5 yıldız');for(let i=1;i<=5;i++){const x=document.createElement('span');x.textContent='★';if(i>n)x.className='off';s.append(x)}return s};
 function card(r,clamp){
  const el=document.createElement('article');el.className='review-card';
  const head=document.createElement('header');const name=document.createElement('strong');name.textContent=r.name;const time=document.createElement('time');time.dateTime=r.createdAt;time.textContent=day(r.createdAt);head.append(name,time);
  const meta=document.createElement('div');meta.className='review-meta';meta.append(stars(r.rating));if(r.barberName){const b=document.createElement('span');b.className='review-barber';b.textContent='Usta: '+r.barberName;meta.append(b)}
  const body=document.createElement('p');body.textContent=r.body;if(clamp)body.className='clamp';
  el.append(head,meta,body);return el;
 }
 function summaryText(count,average){return count?'★ '+fmtAvg(average)+' / 5 · '+count+' yorum':'Henüz yorum yok. İlk yorumu siz yazın.'}
 function renderPreview(){
  $('reviews-summary').textContent=summaryText(data.count,data.average);
  const box=$('reviews-preview');box.replaceChildren();data.reviews.slice(0,3).forEach(r=>box.append(card(r,true)));
  $('reviews-all').hidden=data.count<=3;
 }
 function renderList(){
  const filter=$('reviews-filter').value;const list=$('reviews-list');list.replaceChildren();
  const rows=data.reviews.filter(r=>!filter||String(r.barberId)===filter);
  const stat=filter?data.byBarber[filter]:{count:data.count,average:data.average};
  $('reviews-dialog-summary').textContent=stat&&stat.count?summaryText(stat.count,stat.average):'Bu filtrede henüz yorum yok.';
  if(!rows.length){const p=document.createElement('p');p.className='review-empty';p.textContent='Yorum bulunamadı.';list.append(p);return}
  rows.forEach(r=>list.append(card(r,false)));
 }
 function fillBarbers(){
  for(const sel of [$('review-barber'),$('reviews-filter')]){const keep=sel.value;[...sel.options].slice(1).forEach(o=>o.remove());barbers.forEach(b=>sel.append(new Option(b.name,String(b.id))));if([...sel.options].some(o=>o.value===keep))sel.value=keep}
 }
 async function load(){
  try{data=await api('/api/reviews');renderPreview();renderList()}
  catch{$('reviews-summary').textContent='Yorumlar şu an yüklenemedi.'}
 }
 function paintStars(){document.querySelectorAll('#star-input button').forEach(b=>{const on=Number(b.dataset.v)<=rating;b.classList.toggle('on',on);b.setAttribute('aria-pressed',String(Number(b.dataset.v)===rating))})}
 function showForm(show){$('review-form').hidden=!show;$('reviews-dialog-write').hidden=show;if(show)$('review-name').focus()}
 function openDialog(opts={}){
  const d=$('reviews-dialog');if(opts.barberId!==undefined&&opts.barberId!==null){$('reviews-filter').value=String(opts.barberId);if(opts.write)$('review-barber').value=String(opts.barberId)}
  renderList();showForm(!!opts.write);
  if(typeof d.showModal==='function'){if(!d.open)d.showModal()}else d.setAttribute('open','');
 }
 function thanks(){
  const s=$('reviews-dialog-summary');s.replaceChildren(document.createTextNode('Yorumunuz için teşekkürler! '));
  const a=document.createElement('a');a.href=document.querySelector('[data-google-review]').href;a.target='_blank';a.rel='noopener noreferrer';a.textContent='Google’da da puan verebilirsiniz ↗';s.append(a);
 }
 document.addEventListener('DOMContentLoaded',()=>{
  if(!$('reviews'))return;
  const input=$('star-input');for(let i=1;i<=5;i++){const b=document.createElement('button');b.type='button';b.dataset.v=String(i);b.textContent='★';b.setAttribute('aria-label',i+' yıldız');b.onclick=()=>{rating=i;paintStars()};input.append(b)}
  $('review-text').oninput=()=>{$('review-count').textContent=$('review-text').value.length+' / 600'};
  $('reviews-all').onclick=()=>openDialog();$('reviews-write').onclick=()=>openDialog({write:true});$('reviews-dialog-write').onclick=()=>showForm(true);$('reviews-filter').onchange=renderList;
  const d=$('reviews-dialog');d.querySelectorAll('[data-close]').forEach(b=>b.onclick=()=>d.close?d.close():d.removeAttribute('open'));
  d.addEventListener('click',e=>{const r=d.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)d.close()});
  $('review-form').onsubmit=async ev=>{ev.preventDefault();const msg=$('review-message'),btn=ev.target.querySelector('button[type=submit]');msg.textContent='';
   if(!rating){msg.textContent='Lütfen yıldız seçin.';return}
   btn.disabled=true;
   try{await api('/api/reviews',{method:'POST',body:JSON.stringify({name:$('review-name').value,barberId:$('review-barber').value||null,rating,body:$('review-text').value,website:$('review-website').value})});
    rating=0;paintStars();$('review-text').value='';$('review-count').textContent='0 / 600';showForm(false);await load();thanks()}
   catch(err){msg.textContent=err.message}finally{btn.disabled=false}};
  window.addEventListener('studio-config',e=>{barbers=(e.detail.barbers||[]).filter(b=>b.active!==false&&!b.archived);fillBarbers()});
  load();
 });
 window.StudioReviews={open:openDialog};
})();
