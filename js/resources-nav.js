/**
 * Resources section — highlight active link in subnav / sidebar
 */
(function () {
  'use strict';

  const page = document.body.dataset.resourcePage;
  if (!page) return;

  document.querySelectorAll('.resources-subnav a, .resources-sidebar-nav a').forEach((link) => {
    const href = link.getAttribute('href') || '';
    const isActive =
      (page === 'index' && (href === 'index.html' || href === './')) ||
      href.includes(page);

    if (isActive) {
      link.classList.add('active');
      link.setAttribute('aria-current', 'page');
    }
  });
})();
