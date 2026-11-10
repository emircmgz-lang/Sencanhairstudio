// Fotoğraf kırpma penceresi: sürükle, yakınlaştır (kaydırıcı / fare tekeri / iki parmak), "Uygula".
// Çıktı: sabit oranlı JPEG data URL. İptalde null döner.
(function(root){
 'use strict';
 const CSS=`.crop-dialog{border:1px solid #8a7a52;border-radius:20px;padding:20px;background:#17150f;color:#efe8d6;width:min(560px,calc(100vw - 20px));max-height:calc(100dvh - 20px);overflow:auto;box-sizing:border-box}
.crop-dialog::backdrop{background:#000c}.crop-dialog h3{margin:0 0 6px;font:22px Georgia,serif;color:#cbb98c}.crop-dialog p{margin:0 0 14px;font-size:12px;line-height:1.6;color:#b9ae91}
.crop-view{position:relative;width:100%;overflow:hidden;border-radius:12px;background:#000;touch-action:none;cursor:grab;user-select:none;-webkit-user-select:none;outline:1px solid #cbb98c66}
.crop-view:active{cursor:grabbing}.crop-view img{position:absolute;left:0;top:0;max-width:none;transform-origin:0 0;pointer-events:none;-webkit-user-drag:none}
.crop-zoom{display:flex;align-items:center;gap:12px;margin:16px 0 4px;font-size:18px;color:#cbb98c}.crop-zoom input{flex:1;min-width:0;min-height:44px;accent-color:#cbb98c}
.crop-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:14px}.crop-actions button{min-height:46px;padding:0 22px;border-radius:999px;border:1px solid #8a7a52;background:transparent;color:#efe8d6;font-size:14px;cursor:pointer}
.crop-actions button.crop-ok{background:#cbb98c;color:#1b180f;border-color:#cbb98c;font-weight:600}.crop-actions button:disabled{opacity:.5}`;
 let styled=false;
 function open(file,opt={}){
  const W=opt.width||960,H=opt.height||720,maxChars=opt.maxChars||380000,ratio=W/H;
  if(!styled){const st=document.createElement('style');st.textContent=CSS;document.head.append(st);styled=true}
  return new Promise((resolve,reject)=>{
   const url=URL.createObjectURL(file),img=new Image();
   img.onerror=()=>{URL.revokeObjectURL(url);reject(Error('Fotoğraf açılamadı. JPG, JPEG, PNG veya WebP deneyin.'))};
   img.onload=()=>{
    const d=document.createElement('dialog');d.className='crop-dialog';
    d.innerHTML='<h3>Fotoğrafı kırp</h3><p>Sürükleyerek konumlandırın, kaydırıcı veya iki parmakla yakınlaştırın. Çerçevenin içi sitede görünen alandır.</p><div class="crop-view"></div><label class="crop-zoom"><span aria-hidden="true">−</span><input type="range" min="1" max="4" step="0.01" value="1" aria-label="Yakınlaştırma"><span aria-hidden="true">＋</span></label><div class="crop-actions"><button type="button" data-cancel>İptal</button><button type="button" class="crop-ok" data-ok>Uygula</button></div>';
    const view=d.querySelector('.crop-view'),slider=d.querySelector('input');view.style.aspectRatio=W+' / '+H;view.append(img);document.body.append(d);
    const nw=img.naturalWidth,nh=img.naturalHeight;let vw=0,vh=0,s0=1,z=1,x=0,y=0,done=false;
    const clamp=()=>{const s=s0*z;x=Math.min(0,Math.max(vw-nw*s,x));y=Math.min(0,Math.max(vh-nh*s,y))};
    const paint=()=>{clamp();img.style.transform=`translate(${x}px,${y}px) scale(${s0*z})`};
    function setZoom(nz){nz=Math.min(4,Math.max(1,nz));const s=s0*z,px=(vw/2-x)/s,py=(vh/2-y)/s;z=nz;const s2=s0*z;x=vw/2-px*s2;y=vh/2-py*s2;slider.value=String(z);paint()}
    function measure(){const r=view.getBoundingClientRect();vw=r.width;vh=r.height;s0=Math.max(vw/nw,vh/nh);x=(vw-nw*s0*z)/2;y=(vh-nh*s0*z)/2;paint()}
    function finish(value,error){if(done)return;done=true;URL.revokeObjectURL(url);if(d.open)d.close();d.remove();error?reject(error):resolve(value)}
    const pts=new Map();let last=0;
    view.addEventListener('pointerdown',e=>{view.setPointerCapture(e.pointerId);pts.set(e.pointerId,{x:e.clientX,y:e.clientY});if(pts.size===2){const[a,b]=[...pts.values()];last=Math.hypot(a.x-b.x,a.y-b.y)}});
    view.addEventListener('pointermove',e=>{const p=pts.get(e.pointerId);if(!p)return;const dx=e.clientX-p.x,dy=e.clientY-p.y;p.x=e.clientX;p.y=e.clientY;
     if(pts.size===1){x+=dx;y+=dy;paint()}else if(pts.size===2){const[a,b]=[...pts.values()],dist=Math.hypot(a.x-b.x,a.y-b.y);if(last>0)setZoom(z*dist/last);last=dist}});
    const up=e=>{pts.delete(e.pointerId);last=0};view.addEventListener('pointerup',up);view.addEventListener('pointercancel',up);
    view.addEventListener('wheel',e=>{e.preventDefault();setZoom(z*Math.exp(-e.deltaY*.0015))},{passive:false});
    slider.oninput=()=>setZoom(Number(slider.value));
    d.querySelector('[data-cancel]').onclick=()=>finish(null);d.addEventListener('cancel',e=>{e.preventDefault();finish(null)});
    d.querySelector('[data-ok]').onclick=()=>{
     try{const s=s0*z,c=document.createElement('canvas');c.width=W;c.height=H;const g=c.getContext('2d');g.fillStyle='#111';g.fillRect(0,0,W,H);g.drawImage(img,-x/s,-y/s,vw/s,vh/s,0,0,W,H);
      let q=.88,out=c.toDataURL('image/jpeg',q);while(out.length>maxChars&&q>.4){q-=.08;out=c.toDataURL('image/jpeg',q)}
      if(out.length>maxChars+20000)throw Error('Fotoğraf çok ayrıntılı. Daha küçük bir fotoğraf deneyin.');finish(out)}catch(e){finish(null,e)}};
    d.showModal();measure();
   };
   img.src=url;
  });
 }
 root.StudioCrop={open};
})(typeof globalThis!=='undefined'?globalThis:this);
