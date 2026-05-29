/* ========================================
   Kisii University SDA Church — JavaScript
   ======================================== */

document.addEventListener('DOMContentLoaded', function () {

  // --- Hamburger Menu ---
  const hamburgerBtn = document.querySelector('.hamburger-btn');
  const menuOverlay = document.querySelector('.menu-overlay');
  const slideMenu = document.querySelector('.slide-menu');
  const menuCloseBtn = document.querySelector('.menu-close-btn');

  function openMenu() {
    menuOverlay.classList.add('active');
    slideMenu.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeMenu() {
    menuOverlay.classList.remove('active');
    slideMenu.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (hamburgerBtn) hamburgerBtn.addEventListener('click', openMenu);
  if (menuCloseBtn) menuCloseBtn.addEventListener('click', closeMenu);
  if (menuOverlay) menuOverlay.addEventListener('click', closeMenu);

  // ESC key to close
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMenu();
  });

  // --- Menu Accordion (Chevron dropdowns) ---
  document.querySelectorAll('.menu-chevron').forEach(function (chevron) {
    chevron.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      const parent = this.closest('.menu-nav-item');
      const submenu = parent.querySelector('.menu-submenu');
      if (submenu) {
        submenu.classList.toggle('expanded');
        this.classList.toggle('expanded');
      }
    });
  });

  // --- Header Inline Search ---
  const searchBtn = document.querySelector('.header-search-btn');
  const searchInline = document.querySelector('.header-search-inline');

  if (searchBtn && searchInline) {
    searchBtn.addEventListener('click', function () {
      searchInline.classList.toggle('active');
      if (searchInline.classList.contains('active')) {
        searchInline.querySelector('input').focus();
      }
    });

    // Close search on click outside
    document.addEventListener('click', function (e) {
      if (!searchInline.contains(e.target) && e.target !== searchBtn) {
        searchInline.classList.remove('active');
      }
    });
  }

  // --- Active Nav Highlighting ---
  const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
  document.querySelectorAll('.menu-nav-link, .sidebar-nav a').forEach(function (link) {
    const href = link.getAttribute('href');
    if (href) {
      const linkPath = href.replace(/\/$/, '') || '/';
      if (currentPath === linkPath || (linkPath !== '/' && currentPath.startsWith(linkPath))) {
        link.classList.add('active');
      }
    }
  });

  // --- Cookie Banner ---
  const cookieBanner = document.querySelector('.cookie-banner');
  const cookieAcceptBtn = document.querySelector('.cookie-accept');

  if (cookieBanner && !localStorage.getItem('cookieAccepted')) {
    cookieBanner.classList.add('active');
  }

  if (cookieAcceptBtn) {
    cookieAcceptBtn.addEventListener('click', function () {
      localStorage.setItem('cookieAccepted', 'true');
      cookieBanner.classList.remove('active');
    });
  }

  var cookieRejectBtn = document.querySelector('.cookie-reject');
  if (cookieRejectBtn) {
    cookieRejectBtn.addEventListener('click', function () {
      cookieBanner.classList.remove('active');
    });
  }

  // --- Contact Form Handling ---
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const name = formData.get('name');
      alert('Thank you, ' + name + '! Your message has been received. We will get back to you soon.');
      this.reset();
    });
  }

  // --- Scroll Animations ---
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('fade-in-up');
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);

  document.querySelectorAll('.card, .card-link, .announcement-card, .event-item, .bulletin-item').forEach(function (el) {
    el.style.opacity = '0';
    observer.observe(el);
  });

  // --- Header scroll behaviour: add solid background after scrolling past hero ---
  var siteHeader = document.querySelector('.site-header');
  if (siteHeader) {
    // For sub-pages with solid header (fixed), add body padding
    if (siteHeader.classList.contains('header-solid')) {
      document.body.classList.add('has-solid-header');
    }

    window.addEventListener('scroll', function () {
      if (window.scrollY > 80) {
        siteHeader.classList.add('scrolled');
      } else {
        siteHeader.classList.remove('scrolled');
      }
    });
  }

  // --- Search Page Functionality ---
  const searchPageForm = document.getElementById('searchPageForm');
  const searchResults = document.getElementById('searchResults');

  // Site content for search (simplified)
  const sitePages = [
    { title: 'Home', url: 'index.html', content: 'Welcome to the Kisii University Seventh-day Adventist Church worship Bible study prayer community campus Kenya' },
    { title: 'About Us', url: 'about/index.html', content: 'About Kisii University SDA Church Sabbath services pastor history beliefs worship potluck fellowship' },
    { title: 'Church History', url: 'about/history.html', content: 'History founding growth Kisii University SDA Church campus ministry' },
    { title: 'Our Pastor', url: 'about/our-pastor.html', content: 'Pastor biography leadership spiritual guidance church community' },
    { title: 'Sabbath Services', url: 'about/sabbath-services.html', content: 'Sabbath school divine service worship schedule times' },
    { title: 'What SDAs Believe', url: 'about/what-sda-believe.html', content: '28 Fundamental Beliefs Adventist doctrine Sabbath Second Coming prophecy' },
    { title: 'Worship With Us', url: 'about/worship-with-us.html', content: 'Worship directions campus church livestream service times' },
    { title: 'Potluck & Fellowship', url: 'about/potluck.html', content: 'Vegetarian potluck fellowship lunch community meal' },
    { title: 'Special Events', url: 'about/special-events.html', content: 'Week of Prayer evangelism youth day baptism special programs' },
    { title: 'Schools & Education', url: 'about/schools.html', content: 'Kisii University Adventist education schools learning' },
    { title: 'Nearby SDA Churches', url: 'about/nearby-churches.html', content: 'SDA churches Kisii region Kenya Adventist congregations' },
    { title: 'Giving', url: 'about/giving.html', content: 'Online giving tithe offerings Adventist Giving donate' },
    { title: 'In the News', url: 'about/in-the-news.html', content: 'News press articles mentions church activities' },
    { title: 'Calendar / Events', url: 'events.html', content: 'Calendar events schedule activities weekly monthly annual' },
    { title: 'Ministries', url: 'ministries/index.html', content: 'Ministries service community outreach health youth children Bible studies evangelism' },
    { title: 'Community Outreach', url: 'ministries/community-outreach.html', content: 'Community outreach service help vulnerable support campus students' },
    { title: 'Health Ministry', url: 'ministries/health.html', content: 'Health ministry plant-based NEWSTART wellness cooking demonstrations' },
    { title: 'Youth & Children', url: 'ministries/youth-and-children.html', content: 'Youth children ministry programs campus student Sabbath school' },
    { title: 'Bible Studies', url: 'ministries/bible-studies.html', content: 'Bible studies learn faith scripture study baptism preparation' },
    { title: 'Evangelism', url: 'ministries/evangelism.html', content: 'Evangelism sharing gospel outreach training faith witness' },
    { title: 'New Zion', url: 'ministries/new-zion.html', content: 'New Zion spiritual renewal worship prayer discipleship revival mentoring' },
    { title: 'Christ Messengers', url: 'ministries/christ-messengers.html', content: 'Christ Messengers evangelism gospel ambassadors preaching digital outreach campus' },
    { title: 'First Fruits', url: 'ministries/first-fruits.html', content: 'First Fruits stewardship financial literacy generosity tithe offerings giving' },
    { title: 'The Sentinels', url: 'ministries/the-sentinels.html', content: 'Sentinels prayer watch prophecy spiritual alertness accountability Daniel Revelation' },
    { title: 'HOM — Hands On Mission', url: 'ministries/hom.html', content: 'HOM Hands On Mission service community building feeding education support volunteer' },
    { title: 'Contact Us', url: 'contact.html', content: 'Contact phone email address Kisii University campus office' },
    { title: 'Announcements', url: 'announcements.html', content: 'Announcements news updates church activities' },
    { title: 'Livestream', url: 'livestream.html', content: 'Livestream live worship service online Facebook YouTube' },
    { title: 'Bulletin', url: 'bulletin.html', content: 'Bulletin weekly program Sabbath download PDF' },
    { title: 'Food Assistance', url: 'food.html', content: 'Food assistance community support hunger help program meals' },
    { title: 'Online Giving', url: 'giving.html', content: 'Online giving donate tithe offerings support church mission' }
  ];

  if (searchPageForm) {
    // Check URL params
    const urlParams = new URLSearchParams(window.location.search);
    const query = urlParams.get('s') || '';
    const searchInput = searchPageForm.querySelector('input');
    if (query && searchInput) {
      searchInput.value = query;
      performSearch(query);
    }

    searchPageForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const q = searchInput.value.trim();
      if (q) {
        performSearch(q);
        // Update URL
        const newUrl = window.location.pathname + '?s=' + encodeURIComponent(q);
        window.history.replaceState({}, '', newUrl);
      }
    });
  }

  function performSearch(query) {
    if (!searchResults) return;
    const q = query.toLowerCase();
    const results = sitePages.filter(function (page) {
      return page.title.toLowerCase().includes(q) || page.content.toLowerCase().includes(q);
    });

    if (results.length === 0) {
      searchResults.innerHTML = '<div class="no-results">No results found for "<strong>' + escapeHtml(query) + '</strong>". Please try a different search term.</div>';
    } else {
      let html = '<p class="mb-2" style="color: var(--text-muted);">Found ' + results.length + ' result(s) for "<strong>' + escapeHtml(query) + '</strong>"</p>';
      results.forEach(function (page) {
        html += '<div class="search-result-item">';
        html += '<h3><a href="' + getRelativePath(page.url) + '">' + page.title + '</a></h3>';
        html += '<p>' + page.content.substring(0, 150) + '...</p>';
        html += '</div>';
      });
      searchResults.innerHTML = html;
    }
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function getRelativePath(url) {
    // Calculate relative path based on current page depth
    var depth = (window.location.pathname.match(/\//g) || []).length - 1;
    var prefix = '';
    for (var i = 0; i < depth; i++) {
      prefix += '../';
    }
    return prefix + url;
  }

  // --- Back to Top Button ---
  const backToTopButtons = document.querySelectorAll('.back-to-top');
  if (backToTopButtons.length) {
    function updateBackToTopVisibility() {
      var shouldShow = window.pageYOffset > 300;
      backToTopButtons.forEach(function (btn) {
        btn.classList.toggle('show', shouldShow);
      });
    }

    window.addEventListener('scroll', updateBackToTopVisibility);
    updateBackToTopVisibility();

    backToTopButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    });
  }

});
