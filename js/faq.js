document.addEventListener('DOMContentLoaded', function () {
  // FAQ Toggle with improved functionality
  const faqItems = document.querySelectorAll('.faq-item');
  
  function closeAllItems() {
    faqItems.forEach(openItem => {
      openItem.classList.remove('active');
      const openQuestion = openItem.querySelector('.faq-question');
      const openAnswer = openItem.querySelector('.faq-answer');
      const openToggle = openItem.querySelector('.faq-toggle');
      if (openQuestion) openQuestion.setAttribute('aria-expanded', 'false');
      if (openAnswer) {
        openAnswer.style.maxHeight = '0px';
        openAnswer.style.opacity = '0';
      }
      if (openToggle) openToggle.textContent = '+';
    });
  }
  
  function expandItem(item) {
    const question = item.querySelector('.faq-question');
    const answer = item.querySelector('.faq-answer');
    const toggle = item.querySelector('.faq-toggle');
    
    if (answer) {
      // Force reflow to ensure smooth animation
      answer.style.maxHeight = answer.scrollHeight + 'px';
      answer.style.opacity = '1';
      // Update after content renders
      setTimeout(() => {
        answer.style.maxHeight = answer.scrollHeight + 'px';
      }, 10);
    }
    item.classList.add('active');
    if (question) question.setAttribute('aria-expanded', 'true');
    if (toggle) toggle.textContent = '−';
  }
  
  faqItems.forEach(item => {
    const question = item.querySelector('.faq-question');
    const answer = item.querySelector('.faq-answer');
    const toggle = item.querySelector('.faq-toggle');
    
    if (!question || !answer) return;
    
    // Initialize closed state
    answer.style.maxHeight = '0px';
    answer.style.opacity = '0';
    answer.style.overflow = 'hidden';
    answer.style.transition = 'max-height 0.35s ease, opacity 0.35s ease';

    // Click handler
    question.addEventListener('click', function() {
      const isActive = item.classList.contains('active');
      
      if (isActive) {
        // Close this item
        item.classList.remove('active');
        answer.style.maxHeight = '0px';
        answer.style.opacity = '0';
        question.setAttribute('aria-expanded', 'false');
        if (toggle) toggle.textContent = '+';
      } else {
        // Close all, then open this one
        closeAllItems();
        expandItem(item);
      }
    });
    
    // Keyboard support
    question.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        question.click();
      }
    });
  });
  
  // Recalculate heights when window resizes
  window.addEventListener('resize', function() {
    faqItems.forEach(item => {
      if (item.classList.contains('active')) {
        const answer = item.querySelector('.faq-answer');
        if (answer) {
          answer.style.maxHeight = answer.scrollHeight + 'px';
        }
      }
    });
  });
});