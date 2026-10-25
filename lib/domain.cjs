const crypto=require('node:crypto').webcrypto;
function demoFail(message,status=400){const e=Error(message);e.status=status;throw e}
const defaultSchedule=()=>Array.from({length:7},()=>({enabled:true,start:'09:00',end:'22:00',breakEnabled:false,breakStart:'',breakEnd:''}));
const defaultBarbers=['Kadircan','Davut','Alperen','Enescan'].map((name,i)=>({id:i+1,name,initials:name[0],specialty:'Saç kesimi & bakım',active:true}));
function fullBarber(b){return {photo:'',phone:'',instagram:'',bio:'',duration:60,schedule:defaultSchedule(),daysOff:[],...b}}

function normalizePhone(p){return String(p||'').replace(/[\s()+-]/g,'').replace(/^90/,'0')}
function validDay(d){return typeof d==='string'&&/^\d{4}-\d{2}-\d{2}$/.test(d)&&!isNaN(Date.parse(d))&&new Date(d).toISOString().slice(0,10)===d}
function mins(t){return /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(t)?Number(t.slice(0,2))*60+Number(t.slice(3)):NaN}
function clock(m){return String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0')}
function slotsFor(b,date,records){
 if(!b||b.active===false||b.archived||!validDay(date)||b.daysOff.includes(date))return [];
 const s=b.schedule[(new Date(date+'T12:00:00Z').getUTCDay()+6)%7];if(!s?.enabled)return [];
 const start=mins(s.start),end=mins(s.end),bs=s.breakEnabled===false?NaN:mins(s.breakStart),be=s.breakEnabled===false?NaN:mins(s.breakEnd),duration=b.duration,slots=[];
 const ranges=Number.isFinite(bs)?[[start,bs],[be,end]]:[[start,end]];
 for(const [from,to] of ranges)for(let n=from;n+duration<=to;n+=duration){const time=clock(n);slots.push({time,duration,available:new Date(date+'T'+time+':00+03:00')>new Date()&&!records.some(a=>a.barberId===b.id&&a.date===date&&a.status!=='cancelled'&&mins(a.time)<n+duration&&mins(a.time)+(a.duration||60)>n)})}return slots;
}
function validatedBarber(data,previous){
 const b=fullBarber({...previous,...data});b.name=String(b.name||'').trim();b.specialty=String(b.specialty||'').trim();b.bio=String(b.bio||'').trim();b.phone=String(b.phone||'').trim();b.instagram=String(b.instagram||'').trim().replace(/^@/,'');
 if(b.name.length<2||b.name.length>80||b.specialty.length>200||b.bio.length>600||b.phone.length>30||b.instagram.length>60)demoFail('Çalışan adını ve açıklamalarını kontrol edin.');
 b.duration=Number(b.duration);if(![15,30,45,60,90,120].includes(b.duration))demoFail('Randevu süresi geçersiz.');
 if(typeof b.photo!=='string'||b.photo.length>400000||(b.photo&&!/^data:image\/(jpeg|png|webp);base64,[A-Za-z0-9+/=]+$/.test(b.photo)))demoFail('Geçerli ve küçük bir fotoğraf yükleyin.');
 if(!Array.isArray(b.schedule)||b.schedule.length!==7)demoFail('Haftanın 7 günü için çalışma saati gerekli.');
 b.schedule=b.schedule.map((s,i)=>{if(!s||typeof s!=='object')demoFail('Günlük çalışma saatleri geçersiz.');const r={enabled:!!s.enabled,start:s.start||'09:00',end:s.end||'22:00',breakEnabled:s.breakEnabled===undefined?!!(s.breakStart||s.breakEnd):!!s.breakEnabled,breakStart:s.breakStart||'',breakEnd:s.breakEnd||''};if(!r.breakEnabled){r.breakStart='';r.breakEnd=''}if(r.enabled){const st=mins(r.start),en=mins(r.end),bs=mins(r.breakStart),be=mins(r.breakEnd);if(!Number.isFinite(st)||!Number.isFinite(en)||en-st<b.duration)demoFail((i+1)+'. günün çalışma aralığı en az randevu süresi kadar olmalı.');if(r.breakEnabled&&(!Number.isFinite(bs)||!Number.isFinite(be)||bs>=be||bs<st||be>en))demoFail((i+1)+'. günün mola saatlerini kontrol edin.')}return r});
 if(!Array.isArray(b.daysOff)||b.daysOff.length>180||b.daysOff.some(d=>!validDay(d)))demoFail('İzin tarihlerini kontrol edin.');
 b.daysOff=[...new Set(b.daysOff)].sort();b.active=b.active!==false;b.initials=b.name.split(/\s+/).map(s=>s[0]).slice(0,2).join('').toLocaleUpperCase('tr');return b;
}

function randomCode(){return Array.from(crypto.getRandomValues(new Uint8Array(5)),n=>n.toString(16).padStart(2,'0')).join('').toUpperCase()}

module.exports={defaultSchedule,defaultBarbers,fullBarber,validatedBarber,slotsFor,validDay,normalizePhone,randomCode,mins,clock};
