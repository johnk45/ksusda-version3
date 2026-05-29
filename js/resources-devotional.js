/**
 * Daily devotional — rotates reading by day of year
 */
(function () {
  'use strict';

  const readings = [
    {
      verse: 'Trust in the Lord with all your heart, and lean not on your own understanding.',
      ref: 'Proverbs 3:5',
      body: 'God invites us to surrender our plans and anxieties to Him. On campus, deadlines and exams can crowd out prayer — yet when we acknowledge Him first, He directs our steps with wisdom we cannot manufacture alone.',
      prayer: 'Lord, I choose to trust You today with my studies, relationships, and future. Guide my path. Amen.'
    },
    {
      verse: 'I can do all things through Christ who strengthens me.',
      ref: 'Philippians 4:13',
      body: 'Paul wrote these words from prison, not from comfort. Our strength for ministry, exams, and witness comes from abiding in Christ — not from self-confidence.',
      prayer: 'Jesus, be my strength in weakness. Help me serve You faithfully today. Amen.'
    },
    {
      verse: 'Be still, and know that I am God.',
      ref: 'Psalm 46:10',
      body: 'Sabbath rest is God\'s gift in a restless world. Stillness before Him renews the soul and reminds us that He reigns over every nation and every semester.',
      prayer: 'Father, quiet my heart. Help me honor Your holy day and find peace in You. Amen.'
    },
    {
      verse: 'If God is for us, who can be against us?',
      ref: 'Romans 8:31',
      body: 'No opposition — academic, social, or spiritual — can separate us from the love of God in Christ. Stand firm in that promise as you face today.',
      prayer: 'Thank You for Your unfailing love. Give me courage to live as Your child. Amen.'
    },
    {
      verse: 'Come to Me, all you who labor and are heavy laden, and I will give you rest.',
      ref: 'Matthew 11:28',
      body: 'Jesus does not dismiss our burdens; He carries them with us. Bring your weariness to Him in morning prayer and evening reflection.',
      prayer: 'Lord Jesus, I come to You weary. Grant me Your rest and peace. Amen.'
    },
    {
      verse: 'Your word is a lamp to my feet and a light to my path.',
      ref: 'Psalm 119:105',
      body: 'Scripture illuminates decisions we face daily — friendships, integrity, career. Read a portion today and ask the Holy Spirit to apply it.',
      prayer: 'Holy Spirit, speak through Your Word. Light my path today. Amen.'
    },
    {
      verse: 'The Lord is my shepherd; I shall not want.',
      ref: 'Psalm 23:1',
      body: 'The Good Shepherd provides green pastures and still waters. Even in the valley, His rod and staff comfort those who follow Him.',
      prayer: 'Shepherd of my soul, lead me beside quiet waters. I follow You. Amen.'
    }
  ];

  const dateEl = document.getElementById('devotional-date');
  const verseEl = document.getElementById('devotional-verse');
  const refEl = document.getElementById('devotional-ref');
  const bodyEl = document.getElementById('devotional-body');
  const prayerEl = document.getElementById('devotional-prayer');

  if (!verseEl || !readings.length) return;

  const now = new Date();
  const start = new Date(now.getFullYear(), 0, 0);
  const dayOfYear = Math.floor((now - start) / 86400000);
  const reading = readings[dayOfYear % readings.length];

  if (dateEl) {
    dateEl.textContent = now.toLocaleDateString('en-KE', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  }

  verseEl.textContent = `"${reading.verse}"`;
  refEl.textContent = reading.ref;
  if (bodyEl) {
    bodyEl.innerHTML = `<p>${reading.body}</p>`;
  }
  if (prayerEl) {
    prayerEl.textContent = reading.prayer;
  }
})();
