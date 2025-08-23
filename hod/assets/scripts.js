document.addEventListener('DOMContentLoaded', function () {
  const hamburger = document.getElementById('hamburger');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const body = document.body;

  function isMobile() { return window.innerWidth <= 900; }

  function openMobile() {
    sidebar.classList.add('open');
    if (overlay) overlay.style.display = 'block';
  }
  function closeMobile() {
    sidebar.classList.remove('open');
    if (overlay) overlay.style.display = 'none';
  }

  hamburger.addEventListener('click', function () {
    if (isMobile()) {
      if (sidebar.classList.contains('open')) closeMobile(); else openMobile();
    } else {
      sidebar.classList.toggle('collapsed');
      body.classList.toggle('sidebar-collapsed');
    }
  });

  if (overlay) {
    overlay.addEventListener('click', closeMobile);
    overlay.style.display = 'none';
  }

  window.addEventListener('resize', function () {
    if (!isMobile()) {
      closeMobile();
      if (overlay) overlay.style.display = 'none';
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeMobile();
      sidebar.classList.remove('collapsed');
    }
  });
});
