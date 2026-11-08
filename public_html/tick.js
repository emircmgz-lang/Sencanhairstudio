// Cron'suz arka plan döngüsü: sayfa açıkken dakikada bir sunucuyu dürter.
// Sunucu işi en fazla ~55 sn'de bir gerçekten yapar; fazla istek zarar vermez.
(function(){
 const url=new URL('api/index.php',document.currentScript.src);url.searchParams.set('route','/tick');
 let last=0;
 function ping(){
  if(document.hidden||Date.now()-last<50000)return;last=Date.now();
  fetch(url.pathname+url.search,{method:'POST',headers:{'Content-Type':'application/json'},body:'{}',credentials:'same-origin',cache:'no-store',keepalive:true}).catch(()=>{});
 }
 ping();setInterval(ping,60000);document.addEventListener('visibilitychange',ping);
})();
