// Simple client-side shop for eggs with localStorage cart
// PRODUCTS now supports variants for size (small, medium, large, XL, XXL)
const PRODUCTS = [
  {
    id: 'free-range',
    title: 'Free-range Chicken Eggs',
  // use images from the project's eggs/ folder
  img: 'eggs/OIP (5).webp',
    // prices are per dozen
    variants: [
  { id: 'free-small', size: 'Small', price: 4.99, img: 'eggs/OIP (5).webp' },
  { id: 'free-medium', size: 'Medium', price: 5.99, img: 'eggs/OIP (5).webp' },
  { id: 'free-large', size: 'Large', price: 6.99, img: 'eggs/OIP (5).webp' },
  { id: 'free-xl', size: 'XL', price: 8.5, img: 'eggs/OIP (5).webp' },
  { id: 'free-xxl', size: 'XXL', price: 10.0, img: 'eggs/OIP (5).webp' }
    ]
  },
  {
    id: 'organic',
    title: 'Organic Chicken Eggs',
    // organic product uses an image from eggs/
    img: 'eggs/OIP (4).webp',
    variants: [
      { id: 'org-small', size: 'Small', price: 6.49, img: 'eggs/OIP (4).webp' },
      { id: 'org-medium', size: 'Medium', price: 7.49, img: 'eggs/OIP (4).webp' },
      { id: 'org-large', size: 'Large', price: 8.49, img: 'eggs/OIP (4).webp' },
      { id: 'org-xl', size: 'XL', price: 10.5, img: 'eggs/OIP (4).webp' },
      { id: 'org-xxl', size: 'XXL', price: 12.0, img: 'eggs/OIP (4).webp' }
    ]
  },
  { id: 'bulk', title: 'Bulk Dozen (12 x Dozens)', price: 60.0, img: 'eggs/OIP (3).webp' }
];

const CART_KEY = 'kenth_cart_v1';

function byId(id){return document.getElementById(id)}

function formatPrice(n){return n.toFixed(2)}

function loadCart(){
  try{
    const raw = localStorage.getItem(CART_KEY);
    if(raw) return JSON.parse(raw);
  }catch(e){
    // fall through to in-memory fallback
  }
  // if localStorage unavailable, use in-memory fallback stored on window
  if(window._kenth_cart) return window._kenth_cart;
  return {};
}

function saveCart(cart){
  try{
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
  }catch(e){
    // fallback to in-memory storage when localStorage is restricted (file:// or browser policies)
    window._kenth_cart = cart;
  }
}

function cartCount(cart){ return Object.values(cart).reduce((s,i)=>s+i.qty,0) }

function renderProducts(){
  const grid = byId('productsGrid');
  grid.innerHTML = '';
  PRODUCTS.forEach(p=>{
    const el = document.createElement('div'); el.className='product';
    // if product has variants, render a select for sizes
    let variantHtml = '';
    if(p.variants && Array.isArray(p.variants)){
      variantHtml = `<label>Size: <select class="variant-select">${p.variants.map(v=>`<option value="${v.id}" data-price="${v.price}">${v.size} — $${formatPrice(v.price)}</option>`).join('')}</select></label>`;
    }
    const priceText = p.price ? `$${formatPrice(p.price)}` : '';
    el.innerHTML = `
      <img src="${p.img}" alt="${p.title}" class="prod-img">
      <h3>${p.title}</h3>
      <div class="price">${priceText}</div>
      ${variantHtml}
      <div class="actions">
        <input class="qty" type="number" min="1" value="1" aria-label="Quantity">
        <button class="btn add">Add</button>
      </div>
    `;

    // if variants exist, wire select to update image and price preview
    if(p.variants && Array.isArray(p.variants)){
      const sel = el.querySelector('.variant-select');
      const imgEl = el.querySelector('.prod-img');
      const priceEl = el.querySelector('.price');
      const setFromVariant = ()=>{
        const opt = sel.selectedOptions[0];
        const price = parseFloat(opt.dataset.price);
        const variantId = opt.value;
        const found = p.variants.find(v=>v.id===variantId);
        if(found && found.img) imgEl.src = found.img;
        if(!isNaN(price)) priceEl.textContent = `$${formatPrice(price)}`;
      };
      sel.addEventListener('change', setFromVariant);
      // initialize
      setFromVariant();
    }

    el.querySelector('.add').addEventListener('click', ()=>{
      const qty = parseInt(el.querySelector('.qty').value || '1',10);
      if(p.variants && Array.isArray(p.variants)){
        const sel = el.querySelector('.variant-select');
        const variantId = sel.value;
        // if variant has img, pass it via a temporary map in PRODUCTS lookup
        addToCart(variantId, qty);
      } else {
        addToCart(p.id, qty);
      }
    });
    grid.appendChild(el);
  })
}

function findProductOrVariant(id){
  // find variant first
  for(const p of PRODUCTS){
    if(p.variants && Array.isArray(p.variants)){
      const v = p.variants.find(x=>x.id===id);
      if(v) return { product: p, variant: v };
    }
    if(p.id === id) return { product: p, variant: null };
  }
  return null;
}

function addToCart(itemId, qty=1){
  const cart = loadCart();
  const found = findProductOrVariant(itemId);
  if(!found) return;
  let key = itemId; // unique key for variant or product
  if(!cart[key]){
    if(found.variant){
      cart[key] = { id: key, title: `${found.product.title} — ${found.variant.size}`, price: found.variant.price, img: found.variant.img || found.product.img, qty: 0 };
    } else {
      cart[key] = { id: key, title: found.product.title, price: found.product.price, img: found.product.img, qty: 0 };
    }
  }
  cart[key].qty += qty;
  saveCart(cart);
  updateCartUI();
}

function removeFromCart(productId){
  const cart = loadCart();
  delete cart[productId];
  saveCart(cart);
  updateCartUI();
}

function changeQty(productId, qty){
  const cart = loadCart();
  if(!cart[productId]) return;
  cart[productId].qty = qty;
  if(cart[productId].qty <= 0) delete cart[productId];
  saveCart(cart);
  updateCartUI();
}

function updateCartUI(){
  const cart = loadCart();
  const count = cartCount(cart);
  byId('cartCount').textContent = count;
  // render items
  const itemsEl = byId('cartItems'); itemsEl.innerHTML='';
  let total = 0;
  Object.values(cart).forEach(item=>{
    total += item.price * item.qty;
    const el = document.createElement('div'); el.className='cart-item';
    el.innerHTML = `
      <img src="${item.img}" alt="${item.title}">
      <div class="meta">
        <div class="title">${item.title}</div>
        <div class="small">$${formatPrice(item.price)} × <input type="number" min="0" value="${item.qty}" data-id="${item.id}" class="qty-input" style="width:60px"></div>
      </div>
      <div class="line-price">$${formatPrice(item.price * item.qty)}</div>
      <button class="remove" data-id="${item.id}" aria-label="Remove">×</button>
    `;
    el.querySelector('.remove').addEventListener('click', ()=>removeFromCart(item.id));
    el.querySelector('.qty-input').addEventListener('change', (e)=>{
      const q = parseInt(e.target.value,10) || 0; changeQty(item.id, q);
    });
    itemsEl.appendChild(el);
  })
  byId('cartTotal').textContent = formatPrice(total);
}

function openCart(){
  const d = byId('cartDrawer'); d.classList.add('open'); d.setAttribute('aria-hidden','false');
}
function closeCart(){
  const d = byId('cartDrawer'); d.classList.remove('open'); d.setAttribute('aria-hidden','true');
}

function init(){
  renderProducts();
  updateCartUI();

  byId('cartBtn').addEventListener('click', ()=>{
    openCart();
  });
  byId('closeCart').addEventListener('click', closeCart);

  byId('checkoutBtn').addEventListener('click', ()=>{
    closeCart();
    byId('checkoutModal').classList.add('open'); byId('checkoutModal').setAttribute('aria-hidden','false');
  });

  byId('closeModal').addEventListener('click', ()=>{
    byId('checkoutModal').classList.remove('open'); byId('checkoutModal').setAttribute('aria-hidden','true');
  });

  byId('checkoutForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    // Collect form data
    const form = e.target;
    const name = (form.querySelector('[name="name"]').value || '').trim();
    const address = (form.querySelector('[name="address"]').value || '').trim();
    const phone = (form.querySelector('[name="phone"]').value || '').trim();

    // Build cart payload
    const cart = loadCart();
    const cartItems = Object.values(cart).map(i=>({ id: i.id, price: i.price, qty: i.qty }));
    const totalAmount = Object.values(cart).reduce((s,i)=>s + (i.price * i.qty), 0);

    if(!name || !address || !phone) {
      alert('Please fill name, address and phone before placing order.');
      return;
    }

    // Disable submit while processing
    const submitBtn = form.querySelector('button[type="submit"]');
    if(submitBtn) submitBtn.disabled = true;

    try {
      const resp = await fetch('/web/api/orders.php?action=create_order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name, address, phone,
          total_amount: totalAmount,
          cart_items: cartItems
        })
      });
      const data = await resp.json();
      if(data && data.success) {
        // clear cart
        localStorage.removeItem(CART_KEY);
        updateCartUI();
        // show success with order number
        document.getElementById('checkoutForm-wrapper').style.display = 'none';
        const sm = document.getElementById('successMessage');
        sm.style.display = 'block';
        const h = sm.querySelector('h3');
        if(h) h.textContent = '✓ Thank you for your order!';
        const p = sm.querySelector('p');
        if(p) p.textContent = `Order ${data.order_number} received. We'll contact you with pickup details.`;
      } else {
        alert('Failed to place order: ' + (data && data.message ? data.message : 'Unknown error'));
      }
    } catch(err) {
      console.error(err);
      alert('Network error while placing order.');
    } finally {
      if(submitBtn) submitBtn.disabled = false;
    }
  });

  byId('successClose').addEventListener('click', ()=>{
    byId('checkoutModal').classList.remove('open'); byId('checkoutModal').setAttribute('aria-hidden','true');
    // Reset modal for next use
    document.getElementById('checkoutForm-wrapper').style.display = 'block';
    document.getElementById('successMessage').style.display = 'none';
    document.getElementById('checkoutForm').reset();
  });

  // set year
  const y = new Date().getFullYear(); document.getElementById('year').textContent = y;
}

document.addEventListener('DOMContentLoaded', init);
