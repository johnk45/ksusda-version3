

  // ===== Medical Missionary Leaders Data =====
  const leaders = [
    { name: "Dr. Moffat Misiani", role: "Head of Medical Missionary", qualification: "C NR", specialty: "Consultants Natural Remedies", photo: "https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=200&h=200&fit=crop" },
    { name: "Caleb Mokaya", role: "MM Coordinator", qualification: "CC Remedies", specialty: "Primary Care", photo: "https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=200&h=200&fit=crop" },
    { name: "Pr. Ruth Kemunto", role: "Spiritual Health Director", qualification: "MDiv, Chaplaincy", specialty: "Pastoral Counseling", photo: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&h=200&fit=crop" },
    { name: "Bro. Michael Omondi", role: "Natural Remedies Specialist", qualification: "Herbalist, CNHP", specialty: "Lifestyle Medicine", photo: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop" }
  ];

  const leadersContainer = document.getElementById('medicalLeadersContainer');
  if (leadersContainer) {
    leadersContainer.innerHTML = leaders.map(leader => `
      <div class="medical-leader-card">
        <img src="${leader.photo}" alt="${leader.name}" class="medical-leader-photo" loading="lazy">
        <h3 class="medical-leader-name">${leader.name}</h3>
        <p class="medical-leader-role">${leader.role}</p>
        <p class="medical-leader-qualification">${leader.qualification}</p>
        <span class="medical-leader-specialty">${leader.specialty}</span>
      </div>
    `).join('');
  }

  

  // Toggle remedy preparation (global function)
  window.toggleRemedy = function(btn) {
    const card = btn.closest('.remedy-card');
    const preparation = card.querySelector('.remedy-preparation');
    const icon = btn.querySelector('i');
    
    if (preparation.style.display === 'none' || preparation.style.display === '') {
      preparation.style.display = 'block';
      icon.className = 'fas fa-chevron-up';
      btn.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Preparation';
    } else {
      preparation.style.display = 'none';
      icon.className = 'fas fa-chevron-down';
      btn.innerHTML = '<i class="fas fa-chevron-down"></i> View Preparation';
    }
  };

  // Initialize
  displayRemedies();
  
  // Filter buttons
  if (filterBtns.length) {
    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        displayRemedies(btn.dataset.filter);
      });
    });
  }

  // ===== Join Form Submission =====
  const joinForm = document.getElementById('joinMinistryForm');
  if (joinForm) {
    joinForm.addEventListener('submit', function(e) {
      e.preventDefault();
      alert('Thank you for your interest! A ministry leader will contact you within 48 hours.');
      this.reset();
    });
  }

  // ===== Stat Counters Animation =====
  const statNumbers = document.querySelectorAll('.stat-number');
  function animateStats() {
    statNumbers.forEach(stat => {
      const target = parseInt(stat.getAttribute('data-count') || stat.innerText);
      if (!target) return;
      let current = 0;
      const increment = target / 50;
      const update = () => {
        current += increment;
        if (current < target) {
          stat.innerText = Math.floor(current) + '+';
          requestAnimationFrame(update);
        } else {
          stat.innerText = target + '+';
        }
      };
      update();
    });
  }
  
  const statsSection = document.querySelector('.stats-section');
  if (statsSection) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateStats();
          observer.disconnect();
        }
      });
    }, { threshold: 0.3 });
    observer.observe(statsSection);
  }

  // ===== Back to Top Button =====
  const backToTop = document.getElementById('backToTopBtn');
  if (backToTop) {
    window.addEventListener('scroll', () => {
      backToTop.style.display = window.scrollY > 300 ? 'flex' : 'none';
    });
    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // ===== WhatsApp Chat Button =====
  const whatsappNumber = "254791302316";
  let whatsappFloat = document.querySelector('.whatsapp-float');
  
  if (!whatsappFloat) {
    whatsappFloat = document.createElement('a');
    whatsappFloat.className = 'whatsapp-float';
    whatsappFloat.innerHTML = '<i class="fab fa-whatsapp"></i> <span>Chat with us</span>';
    whatsappFloat.setAttribute('target', '_blank');
    document.body.appendChild(whatsappFloat);
  }
  
  whatsappFloat.setAttribute('href', `https://wa.me/${whatsappNumber}?text=${encodeURIComponent('Hello Health Ministry, I want to learn more about your programs.')}`);
});