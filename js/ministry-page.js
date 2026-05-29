/* ========================================
   Ministry Page — Shared JavaScript
   Handles: Accordion, Join Form, Back-to-Top
   ======================================== */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    /* --- Department Accordion --- */
    const deptItems = document.querySelectorAll('.dept-item');
    deptItems.forEach(function (item) {
      const question = item.querySelector('.dept-question');
      const toggle = item.querySelector('.dept-toggle');
      if (!question) return;

      question.addEventListener('click', function () {
        const wasActive = item.classList.contains('active');

        deptItems.forEach(function (i) {
          i.classList.remove('active');
          const t = i.querySelector('.dept-toggle');
          if (t) t.textContent = '+';
        });

        if (!wasActive) {
          item.classList.add('active');
          if (toggle) toggle.textContent = '−';
        }
      });
    });

    /* --- Join Ministry Form --- */
  const joinForm = document.getElementById('joinMinistryForm');
  if (joinForm) {
    joinForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var nameInput = document.getElementById('joinFullName');
      var name = nameInput ? nameInput.value.trim() : '';
      if (!name) {
        alert('Please enter your full name.');
        return;
      }
      var ministryName = joinForm.getAttribute('data-ministry') || 'this ministry';
      alert('Thank you ' + name + '! You have successfully expressed interest in joining ' + ministryName + '. A leader will contact you soon. God bless you!');
      joinForm.reset();
    });
  }

  /* --- Back to Top Button --- */
  var backBtn = document.getElementById('backToTopBtn');
  if (backBtn) {
    window.addEventListener('scroll', function () {
      if (window.pageYOffset > 300) {
        backBtn.classList.add('show');
      } else {
        backBtn.classList.remove('show');
      }
    });
    backBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

})();
