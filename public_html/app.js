let config, state = { barberId: null, date: null, time: null }, requestId = 0, lastBooking=null;
const $ = id => document.getElementById(id);
function message(text) { $('message').textContent = text; }
function goToStep(n) { document.querySelectorAll('.step').forEach((el,i)=>{el.classList.toggle('hidden',i!==n-1);el.classList.toggle('active',i===n-1);}); }
function goBack(n) { message(''); if(n===2) renderTimes(); goToStep(n); }
function resetFlow() { $('copy-code').textContent='Kodu kopyala';state={barberId:null,date:null,time:null}; $('booking-form').reset(); refreshServiceTotal();message(''); goToStep(1); }
async function renderTimes() {
  const current = ++requestId; state.time=null; $('time-list').replaceChildren(); message('Saatler yükleniyor…');
  try {
    const slots = await api(`/api/availability?barberId=${state.barberId}&date=${state.date}`);
    if(current!==requestId) return;
    message(slots.some(s=>s.available)?'':'Bu tarih için uygun saat bulunmuyor.');
    slots.forEach(s=>{
      const button=document.createElement('button'); button.type='button'; button.className='time-slot'+(s.available?'':' disabled'); button.textContent=s.time; button.disabled=!s.available;
      button.onclick=()=>{state.time=s.time; $('summary-barber').textContent=config.barbers.find(b=>b.id===state.barberId).name; $('summary-time').textContent=new Date(state.date+'T12:00:00+03:00').toLocaleDateString('tr-TR',{dateStyle:'full'})+' · '+s.time; message(''); goToStep(3);};
      $('time-list').append(button);
    });
  } catch(e) { if(current===requestId) message(e.message); }
}
document.addEventListener('DOMContentLoaded',async()=>{
  try {
    config=await api('/api/config'); $('date-input').min=config.today; $('date-input').max=config.maxDate;
    config.barbers.filter(b=>b.active!==false&&!b.archived).forEach(b=>{
      const entry=document.createElement('article');entry.className='employee-profile';
      const portrait=document.createElement('div');portrait.className='employee-portrait';
      if(b.photo){const img=document.createElement('img');img.src=b.photo;img.alt=b.name+' profil fotoğrafı';img.loading='lazy';portrait.append(img)}else{const initials=document.createElement('span');initials.className='portrait-initials';initials.textContent=b.initials||b.name.slice(0,1);const note=document.createElement('span');note.className='portrait-placeholder-label';note.textContent='DAVUT ŞENCAN · HAIR STUDIO';portrait.append(initials,note)}
      const content=document.createElement('div');content.className='employee-profile-content';
      const name=document.createElement('h3');name.textContent=b.name+'’dan randevu al';
      const bio=document.createElement('p');bio.className='employee-profile-description';bio.textContent=b.bio||b.specialty||'Saç kesimi ve bakım için size uygun randevu saatini seçin.';
      content.append(name,bio);
      const contacts=employeeContactLinks(b);if(contacts.length){const row=document.createElement('div');row.className='employee-profile-contacts';for(const contact of contacts){const link=document.createElement('a');link.href=contact.href;link.textContent=contact.label;link.setAttribute('aria-label',b.name+' · '+contact.label);if(contact.external){link.target='_blank';link.rel='noopener noreferrer'}row.append(link)}content.append(row)}
      const button=document.createElement('button');button.type='button';button.className='employee-book-button';button.textContent='Randevu al';button.setAttribute('aria-label',b.name+' için randevu al');button.onclick=()=>{state.barberId=b.id;state.date=state.date||config.today;$('date-input').value=state.date;$('selected-barber-name').textContent=b.name+' için randevu oluşturuyorsunuz.';goToStep(2);renderTimes();};content.append(button);entry.append(portrait,content);$('barber-list').append(entry);
    });
  if(!config.barbers.some(b=>b.active!==false)) message('Şu anda randevu alınabilecek çalışan bulunmuyor.');
  } catch(e) { message('Sistem yüklenemedi. '+e.message); }
  $('date-input').addEventListener('change',()=>{state.date=$('date-input').value; renderTimes();});
  $('show-booking').onclick=()=>showCustomerPanel(false);$('show-lookup').onclick=()=>showCustomerPanel(true);$('success-lookup').onclick=()=>{showCustomerPanel(true);$('lookup-phone').value=lastBooking.phone;$('lookup-code').value=lastBooking.code;$('lookup-form').requestSubmit()};$('copy-code').onclick=async()=>{try{await navigator.clipboard.writeText(lastBooking.code);$('copy-code').textContent='Kopyalandı ✓'}catch{message('Randevu kodunuz: '+lastBooking.code)}};
  $('lookup-form').onsubmit=async e=>{e.preventDefault();const btn=e.target.querySelector('button');btn.disabled=true;$('lookup-message').textContent='';$('lookup-result').replaceChildren();try{const a=await api('/api/bookings/lookup',{method:'POST',body:JSON.stringify({phone:$('lookup-phone').value,code:$('lookup-code').value})});renderLookup(a)}catch(err){$('lookup-message').textContent=err.message}finally{btn.disabled=false}};
  $('booking-form').addEventListener('submit',async e=>{
    e.preventDefault(); const button=e.target.querySelector('[type=submit]'); button.disabled=true; message('');
    try {
      const booking=await api('/api/bookings',{method:'POST',body:JSON.stringify({...state,name:$('customer-name').value,phone:$('customer-phone').value,paymentMethod:$('payment-method').value,serviceIds:selectedServiceIds()})});
      $('success-services').textContent=(booking.services||[]).map(s=>s.name).join(' + ')+' · '+StudioServices.totalLabel(booking);
      $('success-details').textContent=$('summary-barber').textContent+' · '+$('summary-time').textContent;
      lastBooking=booking;$('booking-reference').textContent=booking.code; goToStep(4);
    } catch(e) { if(e.status===409) {goToStep(2); await renderTimes();}if(!selectedServiceIds().length)$('service-picker').scrollIntoView({behavior:'smooth',block:'center'});message(e.message); }
    finally {button.disabled=false;}
  });
});

window.addEventListener("storage",e=>{if(e.key===BUSINESS_STORE)location.reload()});

function showCustomerPanel(lookup){$('booking-flow').hidden=lookup;$('lookup-section').hidden=!lookup;$('show-booking').classList.toggle('active',!lookup);$('show-lookup').classList.toggle('active',lookup);message('')}
function renderLookup(a){const box=$('lookup-result');box.replaceChildren();const card=document.createElement('div');card.className='appointment-card';const heading=document.createElement('h3');heading.textContent=a.status==='cancelled'?'Randevunuz iptal edildi':'Randevu detayları';card.append(heading);const status={confirmed:'Onaylandı',cancelled:'İptal edildi',completed:'Tamamlandı'};const end=clock(mins(a.time)+(a.duration||60));for(const [label,value] of [['Ad soyad',a.name],['Çalışan',a.barberName],['Tarih',new Date(a.date+'T12:00:00').toLocaleDateString('tr-TR',{dateStyle:'full'})],['Saat',a.time+' – '+end],['Hizmetler',(a.services||[]).map(s=>s.name).join(' + ')||'Önceki kayıt'],['Tutar',StudioServices.totalLabel(a)||'Belirtilmemiş'],['Randevu kodu',a.code],['Durum',status[a.status]||a.status]]){const row=document.createElement('p');const name=document.createElement('span');name.textContent=label;const v=document.createElement('strong');v.textContent=value;row.append(name,v);card.append(row)}const note=document.createElement('p');note.className='cancel-note';note.textContent=a.canCancel?'Başlangıç saatine kadar tarih/saat değiştirebilir veya randevunuzu iptal edebilirsiniz.':a.status==='cancelled'?'Randevunuz iptal edildi. İsterseniz yeni bir randevu oluşturabilirsiniz.':'Bu randevu online iptal edilemez. İşletmeyle iletişime geçin.';card.append(note);if(a.canReschedule){const change=document.createElement('button');change.type='button';change.className='secondary-btn reschedule-trigger';change.textContent='Tarih / saat değiştir';change.onclick=()=>{card.querySelector('.reschedule-editor')?.remove();const editor=createRescheduleEditor(a);card.append(editor);editor.scrollIntoView({behavior:'smooth',block:'nearest'})};card.append(change)}if(a.canCancel){const button=document.createElement('button');button.className='cancel-appointment';button.textContent='Randevuyu iptal et';button.onclick=async()=>{if(!confirm('Randevunuzu iptal etmek istediğinize emin misiniz?'))return;button.disabled=true;try{const updated=await api('/api/bookings/cancel',{method:'POST',body:JSON.stringify({phone:a.phone,code:a.code})});renderLookup(updated)}catch(e){$('lookup-message').textContent=e.message;button.disabled=false}};card.append(button)}box.append(card)}

function createRescheduleEditor(a){
 const editor=document.createElement('form');editor.className='reschedule-editor';const title=document.createElement('h3');title.textContent='Yeni tarih ve saat';const label=document.createElement('label');label.textContent='Yeni tarih';const date=document.createElement('input');date.type='date';date.required=true;date.min=config.today;date.max=config.maxDate;date.value=a.date;label.append(date);const slots=document.createElement('div');slots.className='time-grid';slots.setAttribute('aria-label','Yeni randevu saati');const status=document.createElement('p');status.className='reschedule-feedback';status.setAttribute('role','status');const save=document.createElement('button');save.type='submit';save.className='primary-btn';save.textContent='Yeni saati kaydet';save.disabled=true;const cancel=document.createElement('button');cancel.type='button';cancel.className='secondary-btn';cancel.textContent='Vazgeç';cancel.onclick=()=>editor.remove();let selected=null,request=0;
 async function load(){const seq=++request;selected=null;save.disabled=true;slots.replaceChildren();status.textContent='Müsait saatler yükleniyor…';try{const list=await api('/api/bookings/change-options',{method:'POST',body:JSON.stringify({phone:a.phone,code:a.code,date:date.value})});if(seq!==request)return;status.textContent=list.some(s=>s.available)?'Yeni saatinizi seçin. Mevcut randevunuz kaydedene kadar korunur.':'Bu gün için uygun saat yok. Başka bir tarih seçin.';for(const s of list){const b=document.createElement('button');b.type='button';b.className='time-slot'+(s.available?'':' disabled');b.textContent=s.time+(date.value===a.date&&s.time===a.time?' · Şu an':'');b.disabled=!s.available;b.onclick=()=>{selected=s.time;slots.querySelectorAll('button').forEach(x=>x.classList.toggle('selected',x===b));save.disabled=date.value===a.date&&s.time===a.time};slots.append(b)}}catch(e){if(seq===request)status.textContent=e.message}}
 date.onchange=load;editor.onsubmit=async e=>{e.preventDefault();if(!selected)return;if(!confirm('Randevunuz '+date.value+' tarihinde '+selected+' saatine taşınsın mı?'))return;save.disabled=true;date.disabled=true;try{const updated=await api('/api/bookings/reschedule',{method:'POST',body:JSON.stringify({phone:a.phone,code:a.code,date:date.value,time:selected})});renderLookup(updated);$('lookup-message').textContent='Randevu saatiniz güncellendi. Randevu kodunuz aynı kaldı.'}catch(err){status.textContent=err.message;save.disabled=false;date.disabled=false}};editor.append(title,label,slots,status,save,cancel);load();return editor;
}

function employeeContactLinks(b){
 const links=[],phone=String(b.phone||'').trim();const digits=phone.replace(/\D/g,'');
 if(/^[+\d\s().-]+$/.test(phone)&&digits.length>=7&&digits.length<=15){let dial=(phone.startsWith('+')?'+':'')+digits;if(digits.length===10&&digits.startsWith('5')&&!phone.startsWith('+'))dial='0'+digits;links.push({href:'tel:'+dial,label:'☎ '+phone,external:false})}
 let handle=String(b.instagram||'').trim();if(/^(?:https?:\/\/)?(?:www\.)?instagram\.com\//i.test(handle)){try{const url=new URL(/^https?:/i.test(handle)?handle:'https://'+handle);handle=['instagram.com','www.instagram.com'].includes(url.hostname)?url.pathname.split('/').filter(Boolean)[0]||'':''}catch{handle=''}}else handle=handle.replace(/^@/,'');
 if(/^[a-z0-9._]{1,30}$/i.test(handle))links.push({href:'https://www.instagram.com/'+encodeURIComponent(handle)+'/',label:'@'+handle,external:true});return links;
}
