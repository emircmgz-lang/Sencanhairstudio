(()=>{
 const key='sencan-appearance-v1';let theme='dark';
 try{const saved=localStorage.getItem(key);if(saved==='light'||saved==='dark')theme=saved}catch{}
 function apply(next,persist=false){theme=next==='light'?'light':'dark';document.documentElement.dataset.theme=theme;document.documentElement.style.colorScheme=theme;document.querySelector('meta[name="theme-color"]')?.setAttribute('content',theme==='dark'?'#101612':'#f3f1eb');document.querySelectorAll('[data-theme-switch]').forEach(button=>{button.setAttribute('aria-checked',String(theme==='dark'));button.setAttribute('aria-label',theme==='dark'?'Siyah tema açık. Beyaz temaya geç.':'Beyaz tema açık. Siyah temaya geç.');button.closest('.theme-control').dataset.selected=theme});if(persist)try{localStorage.setItem(key,theme)}catch{}}
 apply(theme);
 document.addEventListener('DOMContentLoaded',()=>{apply(theme);document.querySelectorAll('[data-theme-switch]').forEach(button=>{let startX=null,swiped=false;button.addEventListener('pointerdown',e=>{startX=e.clientX;swiped=false});button.addEventListener('pointercancel',()=>{startX=null});button.addEventListener('pointerup',e=>{if(startX!==null&&Math.abs(e.clientX-startX)>12){swiped=true;apply(e.clientX>startX?'dark':'light',true)}startX=null});button.addEventListener('click',()=>{if(swiped){swiped=false;return}apply(theme==='dark'?'light':'dark',true)})})});
 window.addEventListener('storage',e=>{if(e.key===key)apply(e.newValue==='light'?'light':'dark')});
})();
