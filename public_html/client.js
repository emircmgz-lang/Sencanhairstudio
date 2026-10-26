const defaultSchedule=()=>Array.from({length:7},()=>({enabled:true,start:'09:00',end:'22:00',breakEnabled:false,breakStart:'',breakEnd:''}));
const defaultBarbers=['Kadircan','Davut','Alperen','Enescan'].map((name,i)=>({id:i+1,name,initials:name[0],specialty:'Saç kesimi & bakım',active:true}));
function fullBarber(b){return {photo:'',phone:'',email:'',instagram:'',bio:'',duration:60,schedule:defaultSchedule(),daysOff:[],...b}}

function applyBrand(business){document.querySelectorAll('[data-hero-title]').forEach(el=>el.textContent=business.heroTitle||'TARZINIZI\nYENİDEN\nŞEKİLLENDİRİYORUZ.');document.querySelectorAll('[data-hero-description]').forEach(el=>el.textContent=business.heroDescription||business.description||'Kendinize bir saat ayırın. Gerisini bize bırakın.');document.querySelectorAll('[data-shop-name]').forEach(el=>el.textContent=el.classList.contains('heritage-title')?business.name.replace(/\s+(Hair\s*Studio)$/i,'\n$1'):business.name);document.querySelectorAll('[data-shop-description]').forEach(el=>el.textContent=business.description);document.querySelectorAll('[data-shop-contact]').forEach(el=>el.textContent=[business.phone,business.address].filter(Boolean).join(' · '));if(typeof StudioContact!=='undefined')StudioContact.render(business);document.title=business.name+(document.getElementById('admin-content')?' | Yönetim Paneli':' | Randevu')}

function mins(t){return /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(t)?Number(t.slice(0,2))*60+Number(t.slice(3)):NaN}
function clock(m){return String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0')}

const STUDIO_BASE=new URL('.',document.currentScript.src);
const BUSINESS_STORE='sencan-server-business-signal',DEMO_STORE='sencan-server-booking-signal';
let csrfToken=null;const pendingRequests=new Map();
async function api(url,options={}){
 const method=options.method||'GET',headers={...options.headers};if(options.body&&!(options.body instanceof FormData))headers['Content-Type']='application/json';if(csrfToken&&method!=='GET')headers['X-CSRF-Token']=csrfToken;
 let fingerprint;if(url==='/api/bookings'&&method==='POST'){fingerprint=options.body;if(!pendingRequests.has(fingerprint))pendingRequests.set(fingerprint,crypto.randomUUID());headers['Idempotency-Key']=pendingRequests.get(fingerprint)}
 let response;try{const requested=new URL(url,location.origin),target=new URL('api/index.php',STUDIO_BASE);target.searchParams.set('route',requested.pathname.replace(/^\/api/,'')||'/');requested.searchParams.forEach((v,k)=>target.searchParams.set(k,v));response=await fetch(target.pathname+target.search,{...options,headers,credentials:'same-origin',cache:'no-store'})}catch{throw Error('Sunucuya ulaşılamadı. Bağlantıyı kontrol edip tekrar deneyin; aynı isteğin tekrarı ikinci randevu oluşturmaz.')}
 let data;try{data=await response.json()}catch{throw Error(response.status===413?'Yükleme sunucu sınırını aşıyor. Daha küçük video deneyin veya PHP yükleme limitlerini kontrol edin.':'Sunucu geçerli yanıt vermedi. Bağlantıyı ve PHP hata kaydını kontrol edin.')} if(fingerprint)pendingRequests.delete(fingerprint);if(!response.ok){const e=Error(data.error||'İşlem tamamlanamadı.');e.status=response.status;if(e.status===401)csrfToken=null;throw e}
 if(Object.prototype.hasOwnProperty.call(data,'csrfToken'))csrfToken=data.csrfToken;if(url==='/api/logout')csrfToken=null;
 if(method!=='GET')try{localStorage.setItem(url.includes('/barbers')||url.includes('/business')?BUSINESS_STORE:DEMO_STORE,Date.now()+':'+Math.random())}catch{}
 return data;
}
document.addEventListener('DOMContentLoaded',async()=>{try{const config=await api('/api/config');applyBrand(config.business);window.dispatchEvent(new CustomEvent('studio-config',{detail:config.business}))}catch{}});

function mediaUrl(id){const u=new URL("api/index.php",STUDIO_BASE);u.searchParams.set("route","/media");u.searchParams.set("id",id);return u.href}
