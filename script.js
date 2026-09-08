const $=s=>document.querySelector(s), $$=s=>document.querySelectorAll(s);
let cart=[];
const toast=m=>{const t=$('#toast');if(!t)return;t.textContent=m;t.classList.add('show');clearTimeout(window.tt);window.tt=setTimeout(()=>t.classList.remove('show'),2200)};

$('#hamb')?.addEventListener('click',()=>$('#nav')?.classList.toggle('open'));
$$('#nav a').forEach(a=>a.addEventListener('click',()=>$('#nav')?.classList.remove('open')));

async function api(action,data={}){
  try{
    const body=new URLSearchParams({action,...data});
    const r=await fetch('api.php',{method:'POST',body});
    return await r.json();
  }catch(e){return {success:false,message:'Unable to connect to the server. Make sure Apache and MySQL are running.'};}
}

async function loadCart(){const r=await api('cart');if(r.success){cart=r.data.items;render();}}

function render(){
  const box=$('#items'); let total=0,count=0;
  if(!cart.length) box.innerHTML='<p>Your order is empty.</p>';
  else box.innerHTML=cart.map(x=>{
    total+=x.price*x.qty; count+=x.qty;
    return `<div class="cart-row"><div><b>${escapeHtml(x.name)}</b><small>₱${x.price.toFixed(2)} × ${x.qty}<br>${x.stock} available</small></div><div><b>₱${(x.price*x.qty).toFixed(2)}</b><br><button class="qty" data-id="${x.id}" data-qty="${x.qty-1}" type="button">−</button><button class="qty" data-id="${x.id}" data-qty="${x.qty+1}" type="button">+</button><button class="remove" data-id="${x.id}" type="button">Remove</button></div></div>`;
  }).join('');
  $$('.remove').forEach(b=>b.onclick=async()=>{const r=await api('remove',{product_id:b.dataset.id});if(r.success){cart=r.data.items;render();toast('Item removed');}else toast(r.message)});
  $$('.qty').forEach(b=>b.onclick=async()=>{const r=await api('update',{product_id:b.dataset.id,qty:b.dataset.qty});if(r.success){cart=r.data.items;render();}else toast(r.message)});
  $('#total').textContent='₱'+total.toLocaleString(undefined,{minimumFractionDigits:2});
  $('#count').textContent=count;
}

function escapeHtml(v){const d=document.createElement('div');d.textContent=v;return d.innerHTML;}

$$('.add').forEach(b=>b.addEventListener('click',async()=>{
  if(b.disabled)return;
  b.disabled=true;
  const old=b.innerHTML;b.innerHTML='Adding…';
  const r=await api('add',{product_id:b.dataset.id,qty:1});
  b.disabled=false;b.innerHTML=old;
  if(r.success){cart=r.data.items;render();toast('Added to your order');$('#cart')?.classList.add('open');}
  else toast(r.message);
}));

$('#cartOpen')?.addEventListener('click',()=>$('#cart')?.classList.add('open'));
$('#close')?.addEventListener('click',()=>$('#cart')?.classList.remove('open'));

document.addEventListener('keydown',e=>{if(e.key==='Escape'){ $('#cart')?.classList.remove('open'); closeLightbox(); }});

document.addEventListener('click',e=>{
  const social=e.target.closest('[data-social]');
  if(social) toast(`${social.dataset.social} page link can be added here.`);
});

$('#checkout')?.addEventListener('click',async()=>{
  if(!cart.length)return toast('Please add an item first.');
  const name=$('#customerName').value.trim(),email=$('#customerEmail').value.trim();
  if(!name||!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))return toast('Please enter a valid name and email.');
  const b=$('#checkout');b.disabled=true;b.textContent='Processing…';
  const r=await api('checkout',{name,email});
  if(r.success)location.href='success.php?id='+encodeURIComponent(r.data.order_id);
  else{b.disabled=false;b.textContent='Place Order';toast(r.message);await loadCart();}
});

$$('.tabs button').forEach(b=>b.addEventListener('click',()=>{
  $$('.tabs button').forEach(x=>x.classList.remove('selected'));b.classList.add('selected');
  const f=b.dataset.filter;
  $$('.gallery-grid img').forEach(im=>im.classList.toggle('hide',f!=='all'&&im.dataset.type!==f));
}));

const lightbox=$('#lightbox'),lightboxImg=$('#lightboxImg');
function openLightbox(src,alt){if(!lightbox)return;lightboxImg.src=src;lightboxImg.alt=alt||'Gallery image';lightbox.classList.add('open');lightbox.setAttribute('aria-hidden','false');}
function closeLightbox(){if(!lightbox)return;lightbox.classList.remove('open');lightbox.setAttribute('aria-hidden','true');}
$$('.gallery-grid img').forEach(im=>im.addEventListener('click',()=>openLightbox(im.src,im.alt)));
$('#lightboxClose')?.addEventListener('click',closeLightbox);
lightbox?.addEventListener('click',e=>{if(e.target===lightbox)closeLightbox();});
$('#fullGallery')?.addEventListener('click',()=>{
  $$('.tabs button').forEach(x=>x.classList.remove('selected'));
  $('.tabs button[data-filter="all"]')?.classList.add('selected');
  $$('.gallery-grid img').forEach(im=>im.classList.remove('hide'));
  document.querySelector('#gallery')?.scrollIntoView({behavior:'smooth'});
  toast('Full gallery opened — click any photo to enlarge.');
});

// Make every in-page navigation link smooth without breaking normal PHP links.
$$('a[href^="#"]').forEach(a=>a.addEventListener('click',e=>{
  const id=a.getAttribute('href'); if(id&&id.length>1&&$(id)){e.preventDefault();$(id).scrollIntoView({behavior:'smooth',block:'start'});}
}));

loadCart();


document.querySelectorAll('[data-social]').forEach(btn => {
  btn.addEventListener('click', () => {
    const url = btn.dataset.url;
    if (url) window.open(url, '_blank', 'noopener,noreferrer');
    else toast(`${btn.dataset.social || 'Social'} link is not configured.`);
  });
});
