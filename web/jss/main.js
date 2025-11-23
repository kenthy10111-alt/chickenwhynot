document.addEventListener('DOMContentLoaded', function(){
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.nav');
  if(toggle && nav){
    toggle.addEventListener('click', ()=>{
      const shown = nav.style.display === 'flex';
      nav.style.display = shown ? 'none' : 'flex';
    });
  }

  const yearEl = document.getElementById('year');
  if(yearEl) yearEl.textContent = new Date().getFullYear();

});

function handleSubmit(e){
  e.preventDefault();
  const status = document.getElementById('formStatus');
  status.textContent = 'Sending...';
  setTimeout(()=>{ status.textContent = 'Message sent (demo) — no backend configured.'; }, 800);
}
