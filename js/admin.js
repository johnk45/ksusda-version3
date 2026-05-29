/* ========================================
   Admin Panel — JavaScript
   localStorage CRUD for ministry management
   ======================================== */

var AdminApp = (function () {
  'use strict';

  var ADMIN_PASSWORD = 'admin2026';
  var AUTH_KEY = 'admin_authenticated';

  var MINISTRIES = [
    { slug: 'community-outreach', name: 'Community Outreach' },
    { slug: 'health', name: 'Health Ministry' },
    { slug: 'youth-and-children', name: 'Youth & Children' },
    { slug: 'bible-studies', name: 'Bible Studies' },
    { slug: 'evangelism', name: 'Evangelism' },
    { slug: 'new-zion', name: 'New Zion' },
    { slug: 'christ-messengers', name: 'Christ Messengers' },
    { slug: 'first-fruits', name: 'First Fruits' },
    { slug: 'the-sentinels', name: 'The Sentinels' },
    { slug: 'hom', name: 'HOM — Hands On Mission' }
  ];

  // --- Auth ---
  function isAuthenticated() {
    return sessionStorage.getItem(AUTH_KEY) === 'true';
  }

  function login(password) {
    if (password === ADMIN_PASSWORD) {
      sessionStorage.setItem(AUTH_KEY, 'true');
      return true;
    }
    return false;
  }

  function logout() {
    sessionStorage.removeItem(AUTH_KEY);
    window.location.href = 'index.html';
  }

  // --- Data ---
  function getMinistryData(slug) {
    var key = 'ministry_' + slug;
    try {
      var data = JSON.parse(localStorage.getItem(key));
      if (!data) {
        data = { photos: [], livestream: { url: '', active: false }, description: '' };
      }
      return data;
    } catch (e) {
      return { photos: [], livestream: { url: '', active: false }, description: '' };
    }
  }

  function saveMinistryData(slug, data) {
    var key = 'ministry_' + slug;
    localStorage.setItem(key, JSON.stringify(data));
  }

  // --- Photo Management ---
  function addPhoto(slug, src, description) {
    var data = getMinistryData(slug);
    data.photos.push({
      id: Date.now().toString(36) + Math.random().toString(36).substr(2, 5),
      src: src,
      description: description || '',
      dateAdded: new Date().toISOString()
    });
    saveMinistryData(slug, data);
    return data;
  }

  function removePhoto(slug, photoId) {
    var data = getMinistryData(slug);
    data.photos = data.photos.filter(function (p) { return p.id !== photoId; });
    saveMinistryData(slug, data);
    return data;
  }

  function updatePhotoDescription(slug, photoId, newDesc) {
    var data = getMinistryData(slug);
    data.photos.forEach(function (p) {
      if (p.id === photoId) p.description = newDesc;
    });
    saveMinistryData(slug, data);
    return data;
  }

  function movePhoto(slug, photoId, direction) {
    var data = getMinistryData(slug);
    var idx = -1;
    data.photos.forEach(function (p, i) { if (p.id === photoId) idx = i; });
    if (idx === -1) return data;
    var newIdx = direction === 'up' ? idx - 1 : idx + 1;
    if (newIdx < 0 || newIdx >= data.photos.length) return data;
    var temp = data.photos[idx];
    data.photos[idx] = data.photos[newIdx];
    data.photos[newIdx] = temp;
    saveMinistryData(slug, data);
    return data;
  }

  // --- Livestream ---
  function updateLivestream(slug, url, active) {
    var data = getMinistryData(slug);
    data.livestream = { url: url, active: active };
    saveMinistryData(slug, data);
    return data;
  }

  // --- File to Base64 ---
  function fileToBase64(file, callback) {
    if (!file) return callback(null);
    if (file.size > 5 * 1024 * 1024) {
      return callback(null, 'File too large. Maximum size is 5MB.');
    }
    var reader = new FileReader();
    reader.onload = function (e) {
      callback(e.target.result);
    };
    reader.onerror = function () {
      callback(null, 'Failed to read file.');
    };
    reader.readAsDataURL(file);
  }

  // --- Stats ---
  function getStats() {
    var totalPhotos = 0;
    var activeLivestreams = 0;
    MINISTRIES.forEach(function (m) {
      var data = getMinistryData(m.slug);
      totalPhotos += data.photos.length;
      if (data.livestream && data.livestream.active) activeLivestreams++;
    });
    return {
      totalMinistries: MINISTRIES.length,
      totalPhotos: totalPhotos,
      activeLivestreams: activeLivestreams
    };
  }

  // --- Toast ---
  function showToast(message, type) {
    type = type || 'success';
    var existing = document.querySelector('.admin-toast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.className = 'admin-toast ' + type;
    toast.innerHTML = (type === 'success' ? '✅' : '❌') + ' ' + message;
    document.body.appendChild(toast);

    setTimeout(function () { toast.classList.add('show'); }, 50);
    setTimeout(function () {
      toast.classList.remove('show');
      setTimeout(function () { toast.remove(); }, 400);
    }, 3000);
  }

  // --- Embed URL ---
  function convertToEmbed(url) {
    if (!url) return '';
    var ytMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/live\/)([A-Za-z0-9_-]+)/);
    if (ytMatch) return 'https://www.youtube.com/embed/' + ytMatch[1];
    if (url.indexOf('facebook.com') !== -1) {
      return 'https://www.facebook.com/plugins/video.php?href=' + encodeURIComponent(url) + '&show_text=false&width=560';
    }
    return url;
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
  }

  return {
    MINISTRIES: MINISTRIES,
    isAuthenticated: isAuthenticated,
    login: login,
    logout: logout,
    getMinistryData: getMinistryData,
    saveMinistryData: saveMinistryData,
    addPhoto: addPhoto,
    removePhoto: removePhoto,
    updatePhotoDescription: updatePhotoDescription,
    movePhoto: movePhoto,
    updateLivestream: updateLivestream,
    fileToBase64: fileToBase64,
    getStats: getStats,
    showToast: showToast,
    convertToEmbed: convertToEmbed,
    escapeHtml: escapeHtml
  };
})();
