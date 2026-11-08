// Yorumlar: herkes okur; yazma formu yalnız sunucu "bu cihazın ağından alınmış, saati geçmiş randevu var" derse görünür.
(function(){
 const $=id=>document.getElementById(id);
 let rating=0,eligible=null;
 const stars=n=>{const s=document.createElement('span');s.className='review-stars';s.setAttribute('role','img');s.setAttribute('aria-label',n+' / 5 yıldız');for(let i=1;i<=5;i++){const x=document.createElement('span');x.textContent='★';if(i>n)x.className='off';s.append(x)}return s};
 const day=iso=>{try{return new Date(iso).toLocaleDateString('tr-TR',{dateStyle:'long',timeZone:'Europe/Istanbul'})}catch{return ''}};
 function renderList(data){
  const list=$('reviews-list');list.replaceChildren();
  $('reviews-summary').textContent=data.count?data.average.toString().replace('.',',')+' / 5 · '+data.count+' yorum':'';
  if(!data.reviews.length){const p=document.createElement('p');p.className='review-empty';p.textContent='Henüz yorum yok.';list.append(p);return}
  for(const r of data.reviews){const card=document.createElement('article');card.className='review-card';const head=document.createElement('header');const name=document.createElement('strong');name.textContent=r.name;const time=document.createElement('time');time.dateTime=r.createdAt;time.textContent=day(r.createdAt);head.append(name,time);const body=document.createElement('p');body.textContent=r.body;card.append(head,stars(r.rating),body);list.append(card)}
 }
 function paintStars(){document.querySelectorAll('#star-input button').forEach(b=>{const on=Number(b.dataset.v)<=rating;b.classList.toggle('on',on);b.setAttribute('aria-pressed',String(Number(b.dataset.v)===rating))})}
 function renderCompose(e){
  eligible=e;const box=$('review-compose');box.hidden=!e;if(!e)return;
  const when=new Date(e.date+'T12:00:00').toLocaleDateString('tr-TR',{dateStyle:'long'});
  $('review-for').textContent=e.name+' · '+when+' '+e.time+(e.barberName?' · '+e.barberName:'')+' randevunuz için';
 }
 async function load(){
  try{const data=await api('/api/reviews');renderList(data);renderCompose(data.eligibility)}
  catch{$('reviews-list').textContent='Yorumlar şu an yüklenemedi.'}
 }
 document.addEventListener('DOMContentLoaded',()=>{
  if(!$('reviews'))return;
  const input=$('star-input');for(let i=1;i<=5;i++){const b=document.createElement('button');b.type='button';b.dataset.v=String(i);b.textContent='★';b.setAttribute('aria-label',i+' yıldız');b.onclick=()=>{rating=i;paintStars()};input.append(b)}
  const text=$('review-text');text.oninput=()=>{$('review-count').textContent=text.value.length+' / 600'};
  $('review-form').onsubmit=async ev=>{ev.preventDefault();const msg=$('review-message'),btn=ev.target.querySelector('button[type=submit]');msg.textContent='';
   if(!rating){msg.textContent='Lütfen yıldız seçin.';return}
   btn.disabled=true;try{await api('/api/reviews',{method:'POST',body:JSON.stringify({rating,body:text.value})});rating=0;text.value='';paintStars();$('review-count').textContent='0 / 600';await load();$('reviews').scrollIntoView({behavior:'smooth',block:'start'});$('reviews-summary').textContent='Yorumunuz için teşekkürler! '+$('reviews-summary').textContent}
   catch(err){msg.textContent=err.message}finally{btn.disabled=false}};
  load();
 });
})();
