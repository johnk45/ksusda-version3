/* ========================================
   Ministry Gallery & Lightbox — Frontend
   Loaded on ministry detail pages
   ======================================== */

document.addEventListener('DOMContentLoaded', function () {
  // --- Gallery Rendering ---
  var galleryEl = document.getElementById('ministry-gallery');
  var gridEl = document.getElementById('gallery-grid');
  var emptyEl = document.getElementById('gallery-empty');

  if (!galleryEl || !gridEl) return;

  var ministrySlug = galleryEl.getAttribute('data-ministry');
  if (!ministrySlug) return;

  var storageKey = 'ministry_' + ministrySlug;
  var data = null;

  try {
    data = JSON.parse(localStorage.getItem(storageKey));
  } catch (e) {
    data = null;
  }

  var photos = (data && data.photos) ? data.photos : [];

  if (photos.length === 0) {
    if (emptyEl) emptyEl.style.display = 'block';
    gridEl.style.display = 'none';
  } else {
    if (emptyEl) emptyEl.style.display = 'none';
    gridEl.style.display = 'grid';
    renderGallery(photos);
  }

  function renderGallery(photos) {
    gridEl.innerHTML = '';
    photos.forEach(function (photo, index) {
      var item = document.createElement('div');
      item.className = 'gallery-item';
      item.innerHTML =
        '<img src="' + photo.src + '" alt="' + escapeHtml(photo.description || 'Ministry photo') + '" loading="lazy">' +
        (photo.description ? '<p class="gallery-item-desc">' + escapeHtml(photo.description) + '</p>' : '');
      item.addEventListener('click', function () {
        openLightbox(index);
      });
      gridEl.appendChild(item);
    });
  }

  // --- Lightbox ---
  var lightbox = document.getElementById('gallery-lightbox');
  var lightboxImg = document.getElementById('lightbox-img');
  var lightboxCaption = document.getElementById('lightbox-caption');
  var lightboxClose = document.getElementById('lightbox-close');
  var lightboxPrev = document.getElementById('lightbox-prev');
  var lightboxNext = document.getElementById('lightbox-next');
  var currentIndex = 0;

  function openLightbox(index) {
    if (!lightbox || photos.length === 0) return;
    currentIndex = index;
    updateLightbox();
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.classList.remove('active');
    document.body.style.overflow = '';
  }

  function updateLightbox() {
    if (!lightboxImg || photos.length === 0) return;
    var photo = photos[currentIndex];
    lightboxImg.src = photo.src;
    lightboxImg.alt = photo.description || 'Ministry photo';
    if (lightboxCaption) {
      lightboxCaption.textContent = photo.description || '';
    }
  }

  if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
  if (lightboxPrev) {
    lightboxPrev.addEventListener('click', function () {
      currentIndex = (currentIndex - 1 + photos.length) % photos.length;
      updateLightbox();
    });
  }
  if (lightboxNext) {
    lightboxNext.addEventListener('click', function () {
      currentIndex = (currentIndex + 1) % photos.length;
      updateLightbox();
    });
  }

  if (lightbox) {
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) closeLightbox();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (!lightbox || !lightbox.classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') {
      currentIndex = (currentIndex - 1 + photos.length) % photos.length;
      updateLightbox();
    }
    if (e.key === 'ArrowRight') {
      currentIndex = (currentIndex + 1) % photos.length;
      updateLightbox();
    }
  });

  // --- Livestream Rendering ---
  var livestreamEl = document.getElementById('ministry-livestream');
  if (livestreamEl) {
    var lsMinistry = livestreamEl.getAttribute('data-ministry');
    var lsKey = 'ministry_' + lsMinistry;
    var lsData = null;
    try { lsData = JSON.parse(localStorage.getItem(lsKey)); } catch (e) { lsData = null; }

    var container = document.getElementById('livestream-container');
    if (lsData && lsData.livestream && lsData.livestream.url && lsData.livestream.active) {
      var embedUrl = convertToEmbed(lsData.livestream.url);
      container.innerHTML =
        '<div class="livestream-embed"><iframe src="' + embedUrl +
        '" allowfullscreen allow="autoplay; encrypted-media"></iframe></div>';
      livestreamEl.style.display = 'block';
    } else {
      livestreamEl.style.display = 'none';
    }
  }

  function convertToEmbed(url) {
    // YouTube
    var ytMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/live\/)([A-Za-z0-9_-]+)/);
    if (ytMatch) return 'https://www.youtube.com/embed/' + ytMatch[1];
    // Facebook
    if (url.indexOf('facebook.com') !== -1) {
      return 'https://www.facebook.com/plugins/video.php?href=' + encodeURIComponent(url) + '&show_text=false&width=560';
    }
    return url;
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
});
