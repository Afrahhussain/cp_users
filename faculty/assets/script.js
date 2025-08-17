// faculty/assets/script.js
document.addEventListener('DOMContentLoaded', () => {
  const hamburger = document.getElementById('hamburger');
  const sidebar   = document.getElementById('sidebar');
  const overlay   = document.getElementById('overlay');

  function openSidebar(){
    sidebar.classList.add('open');
    if (overlay) overlay.style.display = 'block';
  }
  function closeSidebar(){
    sidebar.classList.remove('open');
    if (overlay) overlay.style.display = 'none';
  }
  function toggleSidebar(){
    if (sidebar.classList.contains('open')) closeSidebar(); else openSidebar();
  }

  // START HIDDEN (no open class)
  closeSidebar();

  if (hamburger) hamburger.addEventListener('click', toggleSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSidebar();
  });

  // Keep overlay hidden if resized to desktop, but leave state controlled by open class
  window.addEventListener('resize', () => {
    if (!sidebar.classList.contains('open')) {
      if (overlay) overlay.style.display = 'none';
    }
  });
});
