(function(){document.addEventListener('DOMContentLoaded',async()=>{
 let business={};try{business=(await api('/api/config')).business}catch{}
 const raw=String(business.phone||'').trim(),digits=raw.replace(/\D/g,'');const valid=/^[+\d\s().-]+$/.test(raw)&&digits.length>=7&&digits.length<=15;
 document.querySelectorAll('[data-footer-phone]').forEach(el=>{el.hidden=!valid;if(valid){el.textContent=raw;el.href='tel:'+(raw.startsWith('+')?'+':'')+digits}});
 document.querySelectorAll('[data-footer-address]').forEach(el=>{el.textContent=String(business.address||'').trim();el.hidden=!el.textContent});
 document.querySelectorAll('[data-footer-booking]').forEach(el=>el.addEventListener('click',e=>{const button=document.getElementById('show-booking');if(!button)return;e.preventDefault();button.click();document.getElementById('booking-flow').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'})}));
});})();
