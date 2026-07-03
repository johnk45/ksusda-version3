/**
 * Church Blog — resources page interactions
 */
(function () {
  'use strict';

  const verses = [
    { text: 'Trust the Lord with all your heart and lean not on your own understanding.', ref: 'Proverbs 3:5' },
    { text: 'I can do all things through Christ who strengthens me.', ref: 'Philippians 4:13' },
    { text: 'The Lord is my shepherd; I shall not want.', ref: 'Psalm 23:1' },
    { text: 'Be strong and courageous. Do not be afraid; do not be discouraged.', ref: 'Joshua 1:9' },
    {
      text: 'For God so loved the world that he gave his only Son, that whoever believes in him shall not perish but have eternal life.',
      ref: 'John 3:16',
    },
  ];

  function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'blog-toast';
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
      toast.classList.add('is-leaving');
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  function showVerseNotification() {
    const verse = verses[Math.floor(Math.random() * verses.length)];
    const notification = document.getElementById('verseNotification');
    const verseText = document.getElementById('verseText');
    const verseReference = document.getElementById('verseReference');
    const progress = document.getElementById('timerProgress');

    if (!notification || !verseText || !verseReference || !progress) return;

    verseText.textContent = verse.text;
    verseReference.textContent = verse.ref;
    notification.classList.add('active');

    const displayTime = 10000;
    progress.style.width = '100%';
    progress.style.transition = `width ${displayTime}ms linear`;

    requestAnimationFrame(() => {
      progress.style.width = '0%';
    });

    setTimeout(() => {
      notification.classList.remove('active');
    }, displayTime);
  }

  function initLikeButtons() {
    document.querySelectorAll('.resources-page .like-btn').forEach((likeBtn) => {
      const likeCountEl = likeBtn.closest('.like-section')?.querySelector('.like-count-num');
      if (!likeCountEl) return;

      let liked = false;
      let count = parseInt(likeCountEl.textContent, 10) || 0;

      likeBtn.addEventListener('click', () => {
        if (!liked) {
          count += 1;
          likeCountEl.textContent = count;
          likeBtn.innerHTML = '<i class="fas fa-thumbs-up"></i> Liked';
          likeBtn.classList.add('liked');
          likeBtn.setAttribute('aria-pressed', 'true');
          liked = true;
          showToast('Thanks for liking this sermon!');
        } else {
          count -= 1;
          likeCountEl.textContent = count;
          likeBtn.innerHTML = '<i class="far fa-thumbs-up"></i> Like';
          likeBtn.classList.remove('liked');
          likeBtn.setAttribute('aria-pressed', 'false');
          liked = false;
        }
      });
    });
  }

  function initShareButtons() {
    document.querySelectorAll('.resources-page .share-btn[data-share]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const platform = btn.dataset.share;
        const title = btn.dataset.shareTitle || document.title;
        const baseUrl = window.location.href.split('#')[0];
        const article = btn.closest('.blog-post');
        const hash = article?.id ? `#${article.id}` : '';
        const url = encodeURIComponent(baseUrl + hash);
        const encodedTitle = encodeURIComponent(title);
        const encodedText = encodeURIComponent(`Check out this sermon from Kisii University SDA Church: ${title}`);

        if (platform === 'facebook') {
          window.open(
            `https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${encodedTitle}`,
            'facebook-share',
            'width=600,height=400'
          );
        } else if (platform === 'twitter') {
          window.open(
            `https://twitter.com/intent/tweet?url=${url}&text=${encodedText}`,
            'twitter-share',
            'width=600,height=400'
          );
        } else if (platform === 'whatsapp') {
          window.open(`https://wa.me/?text=${encodedText}%20${url}`, 'whatsapp-share', 'width=600,height=400');
        }
      });
    });
  }

  function initCommentForm() {
    const commentForm = document.getElementById('commentForm');
    const commentsList = document.getElementById('commentsList');
    const commentsTitle = document.querySelector('.comments-title');
    if (!commentForm || !commentsList) return;

    commentForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const name = document.getElementById('comment-name')?.value.trim();
      const comment = document.getElementById('comment-text')?.value.trim();
      if (!name || !comment) return;

      const newComment = document.createElement('div');
      newComment.className = 'comment';

      const date = new Date().toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });

      newComment.innerHTML = `
        <div class="comment-header">
          <div class="comment-author">${escapeHtml(name)}</div>
          <div class="comment-date">${date}</div>
        </div>
        <div class="comment-content">${escapeHtml(comment)}</div>
      `;

      commentsList.insertBefore(newComment, commentsList.firstChild);
      commentForm.reset();
      showToast('Thank you for your comment!');

      if (commentsTitle) {
        const match = commentsTitle.textContent.match(/\d+/);
        const currentCount = match ? parseInt(match[0], 10) : 0;
        commentsTitle.innerHTML = `<i class="fas fa-comments"></i> Comments (${currentCount + 1})`;
      }
    });
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function initEllenWhiteCarousel() {
    const carousel = document.getElementById('ellenWhiteCarousel');
    const prevBtn = document.getElementById('prevQuote');
    const nextBtn = document.getElementById('nextQuote');
    const quoteCounter = document.getElementById('quoteNumber');
    const totalQuotes = document.getElementById('totalQuotes');

    if (!carousel || !prevBtn || !nextBtn) return;

    const containers = carousel.querySelectorAll('.quote-container');
    let currentIndex = 0;

    if (totalQuotes) {
      totalQuotes.textContent = containers.length;
    }

    function updateCarousel() {
      containers.forEach((container, index) => {
        container.classList.remove('active');
        if (index === currentIndex) {
          container.classList.add('active');
        }
      });

      if (quoteCounter) {
        quoteCounter.textContent = currentIndex + 1;
      }
    }

    function nextQuote() {
      currentIndex = (currentIndex + 1) % containers.length;
      updateCarousel();
    }

    function prevQuote() {
      currentIndex = (currentIndex - 1 + containers.length) % containers.length;
      updateCarousel();
    }

    prevBtn.addEventListener('click', prevQuote);
    nextBtn.addEventListener('click', nextQuote);

    // Auto-rotate quotes every 8 seconds
    setInterval(nextQuote, 8000);

    // Initialize first quote as active
    updateCarousel();
  }

  document.addEventListener('DOMContentLoaded', () => {
    initLikeButtons();
    initShareButtons();
    initCommentForm();
    initEllenWhiteCarousel();
    setTimeout(showVerseNotification, 2000);
  });
})();
