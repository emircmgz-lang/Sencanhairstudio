// "Takvime ekle": Google Takvim bağlantısı + .ics dosyası (iPhone / Android / Outlook).
(function(root){
 const pad=n=>String(n).padStart(2,'0');
 // Türkiye yıl boyu UTC+3; saatleri UTC'ye çevirerek cihaz saat diliminden bağımsız yaparız.
 function span(a){const[y,m,d]=a.date.split('-').map(Number),[h,mi]=a.time.split(':').map(Number),start=new Date(Date.UTC(y,m-1,d,h-3,mi)),end=new Date(start.getTime()+(a.duration||60)*60000);return{start,end}}
 const stamp=d=>d.toISOString().replace(/[-:]/g,'').replace(/\.\d{3}/,'');
 const esc=t=>String(t).replace(/\\/g,'\\\\').replace(/\n/g,'\\n').replace(/,/g,'\\,').replace(/;/g,'\\;');
 function fold(line){const enc=new TextEncoder(),out=[];let cur='';for(const ch of line){if(enc.encode(cur+ch).length>73){out.push(cur);cur=' '+ch}else cur+=ch}out.push(cur);return out.join('\r\n')}
 function details(a,barber){return ['Usta: '+(barber||'—'),'Hizmetler: '+((a.services||[]).map(s=>s.name).join(' + ')||'—'),'Randevu kodu: '+a.code].join('\n')}
 function title(business){return (business&&business.name||'Hair Studio')+' · Randevu'}
 function googleUrl(a,barber,business){const{start,end}=span(a),u=new URL('https://calendar.google.com/calendar/render');u.searchParams.set('action','TEMPLATE');u.searchParams.set('text',title(business));u.searchParams.set('dates',stamp(start)+'/'+stamp(end));u.searchParams.set('details',details(a,barber));if(business&&business.address)u.searchParams.set('location',business.address);return u.href}
 function ics(a,barber,business){const{start,end}=span(a),lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Sencan Hair Studio//Randevu//TR','CALSCALE:GREGORIAN','METHOD:PUBLISH','BEGIN:VEVENT','UID:'+a.code+'@sencanhairstudio','DTSTAMP:'+stamp(new Date()),'DTSTART:'+stamp(start),'DTEND:'+stamp(end),'SUMMARY:'+esc(title(business)),'DESCRIPTION:'+esc(details(a,barber))];if(business&&business.address)lines.push('LOCATION:'+esc(business.address));lines.push('BEGIN:VALARM','TRIGGER:-PT1H','ACTION:DISPLAY','DESCRIPTION:'+esc('Randevunuza 1 saat kaldı'),'END:VALARM','END:VEVENT','END:VCALENDAR');return lines.map(fold).join('\r\n')+'\r\n'}
 function render(box,a,barber,business){
  if(!box)return;box.replaceChildren();if(!a||!a.date||!a.time)return;
  const label=document.createElement('p');label.className='calendar-label';label.textContent='Unutmamak için takvime ekleyin';
  const google=document.createElement('a');google.className='secondary-btn calendar-btn';google.href=googleUrl(a,barber,business);google.target='_blank';google.rel='noopener noreferrer';google.textContent='Google Takvim';
  const file=document.createElement('button');file.type='button';file.className='secondary-btn calendar-btn';file.textContent='iPhone / diğer (.ics)';
  file.onclick=()=>{const blob=new Blob([ics(a,barber,business)],{type:'text/calendar;charset=utf-8'}),url=URL.createObjectURL(blob),link=document.createElement('a');link.href=url;link.download='randevu-'+a.code+'.ics';document.body.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),4000)};
  const row=document.createElement('div');row.className='calendar-row';row.append(google,file);box.append(label,row);
 }
 const api={render,googleUrl,ics};if(typeof module!=='undefined'&&module.exports)module.exports=api;else root.StudioCalendar=api;
})(typeof globalThis!=='undefined'?globalThis:this);
