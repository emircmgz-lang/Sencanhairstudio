(function(root){
 const catalog=[
  {id:'hair',category:'hair',name:'Saç',price:600},
  {id:'beard',category:'hair',name:'Sakal',price:300},
  {id:'hair-beard',category:'hair',name:'Saç + Sakal',price:800},
  {id:'perm',category:'hair',name:'Perma',price:2500,from:true,warning:'Perma fiyatı saçın uzunluğuna ve saç tipine göre değişir. Kesin fiyat işlem öncesinde belirlenir.'},
  {id:'child',category:'hair',name:'Çocuk tıraşı',price:400},
  {id:'dye',category:'hair',name:'Boya işlemleri',price:800},
  {id:'color',category:'hair',name:'Renklendirme',price:2000,from:true,warning:'Renklendirme fiyatı saçın uzunluğuna, saç tipine ve yapılacak işleme göre değişir. Kesin fiyat işlem öncesinde belirlenir.'},
  {id:'keratin',category:'care',name:'Keratin bakımı',price:2000,from:true,warning:'Keratin bakımı fiyatı saçın uzunluğuna ve saç tipine göre değişir. Kesin fiyat işlem öncesinde belirlenir.'},
  {id:'skin',category:'care',name:'Detaylı cilt bakımı',price:900},
  {id:'hair-care',category:'care',name:'Saç bakımı',price:500},
  {id:'mask',category:'care',name:'Maske',price:300},
  {id:'peeling',category:'care',name:'Peeling',price:300}
 ];
 function quoteServices(ids){if(!Array.isArray(ids)||!ids.length||ids.length>catalog.length||ids.some(id=>typeof id!=='string'||!catalog.some(s=>s.id===id))){const e=Error('En az bir geçerli hizmet seçin.');e.status=400;throw e}const services=catalog.filter(s=>ids.includes(s.id)).map(s=>({id:s.id,name:s.name,category:s.category,price:s.price,from:!!s.from}));return {services,total:services.reduce((n,s)=>n+s.price,0),priceFrom:services.some(s=>s.from)}}
 function formatTL(n){return new Intl.NumberFormat('tr-TR',{maximumFractionDigits:0}).format(n)+' TL'}
 function totalLabel(a){return Number.isFinite(a?.total)?formatTL(a.total)+(a.priceFrom?"'den başlayan fiyatlarla":''):''}
 const value={catalog,quoteServices,formatTL,totalLabel};if(typeof module!=='undefined'&&module.exports)module.exports=value;else root.StudioServices=value;
})(typeof globalThis!=='undefined'?globalThis:this);
