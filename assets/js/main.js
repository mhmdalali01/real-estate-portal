/* EstateHub — Main JavaScript */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Init Lucide icons ── */
  if (typeof lucide !== 'undefined') lucide.createIcons();

  /* ── Navbar: scroll shadow ── */
  const navbar = document.getElementById('navbar');
  if (navbar) {
    const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 10);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── Mobile nav toggle ── */
  const navToggle = document.getElementById('navToggle');
  const navLinks  = document.getElementById('navLinks');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
      navLinks.classList.toggle('open');
      navToggle.classList.toggle('open');
    });
  }

  /* ── User dropdown ── */
  const userMenuBtn = document.getElementById('userMenuBtn');
  const userMenu    = document.getElementById('userMenu');
  if (userMenuBtn && userMenu) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      userMenu.classList.toggle('open');
    });
    document.addEventListener('click', () => userMenu.classList.remove('open'));
  }

  /* ── Alert dismiss ── */
  document.querySelectorAll('.alert-close').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('.alert').remove());
  });
  // Auto-dismiss after 5s
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => alert.style.opacity === '0' ? alert.remove() : (alert.style.transition = 'opacity .4s', alert.style.opacity = '0', setTimeout(() => alert.remove(), 400)), 5000);
  });

  /* ── Gallery slider ── */
  const galleryMain   = document.querySelector('.gallery-main img');
  const galleryThumbs = document.querySelectorAll('.gallery-thumb');
  if (galleryMain && galleryThumbs.length) {
    galleryThumbs.forEach((thumb, i) => {
      thumb.addEventListener('click', () => {
        galleryMain.style.opacity = '0';
        setTimeout(() => {
          galleryMain.src = thumb.querySelector('img').src;
          galleryMain.style.opacity = '1';
        }, 150);
        galleryThumbs.forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
      });
    });
    galleryMain.style.transition = 'opacity .15s ease';
    if (galleryThumbs[0]) galleryThumbs[0].classList.add('active');
  }

  /* ── Image upload preview ── */
  const fileInput   = document.getElementById('imageInput');
  const uploadArea  = document.getElementById('uploadArea');
  const previewGrid = document.getElementById('imagePreviewGrid');
  if (fileInput && uploadArea && previewGrid) {
    const showPreviews = (files) => {
      Array.from(files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
          const wrap = document.createElement('div');
          wrap.className = 'image-preview-item';
          wrap.innerHTML = `<img src="${e.target.result}" alt="preview"><span class="remove-img">×</span>`;
          wrap.querySelector('.remove-img').addEventListener('click', () => wrap.remove());
          previewGrid.appendChild(wrap);
        };
        reader.readAsDataURL(file);
      });
    };
    fileInput.addEventListener('change', () => showPreviews(fileInput.files));
    uploadArea.addEventListener('click', () => fileInput.click());
    uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
    uploadArea.addEventListener('drop', e => {
      e.preventDefault();
      uploadArea.classList.remove('dragover');
      showPreviews(e.dataTransfer.files);
    });
  }

  /* ── Favorites (AJAX toggle) ── */
  document.querySelectorAll('.card-fav').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      const listingId = btn.dataset.id;
      try {
        const res  = await fetch(`${siteUrl}/listings/toggle_favorite.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `listing_id=${listingId}`,
        });
        const data = await res.json();
        if (data.status === 'added') {
          btn.classList.add('active');
          btn.title = 'Remove from favorites';
        } else if (data.status === 'removed') {
          btn.classList.remove('active');
          btn.title = 'Add to favorites';
        } else if (data.status === 'login_required') {
          window.location.href = `${siteUrl}/auth/login.php`;
        }
        // Re-init icons
        if (typeof lucide !== 'undefined') lucide.createIcons();
      } catch (err) {
        console.error('Favorite toggle failed:', err);
      }
    });
  });

  /* ── Type-chip filter (search page) ── */
  document.querySelectorAll('.type-chip[data-type]').forEach(chip => {
    chip.addEventListener('click', () => {
      const typeInput = document.getElementById('typeFilter');
      if (!typeInput) return;
      document.querySelectorAll('.type-chip').forEach(c => c.classList.remove('active'));
      if (typeInput.value === chip.dataset.type) {
        typeInput.value = '';
      } else {
        chip.classList.add('active');
        typeInput.value = chip.dataset.type;
      }
    });
  });

  /* ── Price range labels ── */
  const priceMin = document.getElementById('priceMin');
  const priceMax = document.getElementById('priceMax');
  const priceMinLabel = document.getElementById('priceMinLabel');
  const priceMaxLabel = document.getElementById('priceMaxLabel');
  const formatCurrency = v => '$' + Number(v).toLocaleString();
  if (priceMin && priceMinLabel) {
    priceMin.addEventListener('input', () => priceMinLabel.textContent = formatCurrency(priceMin.value));
  }
  if (priceMax && priceMaxLabel) {
    priceMax.addEventListener('input', () => priceMaxLabel.textContent = formatCurrency(priceMax.value));
  }

  /* ── Confirm dialogs ── */
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  /* ── Animate cards on scroll ── */
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity  = '1';
          entry.target.style.transform = 'translateY(0)';
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08 });

    document.querySelectorAll('.property-card, .feature-tile, .stat-card').forEach(el => {
      el.style.opacity   = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = 'opacity .45s ease, transform .45s ease';
      observer.observe(el);
    });
  }

  /* ── Character counter for textarea ── */
  document.querySelectorAll('textarea[maxlength]').forEach(ta => {
    const counter = document.createElement('small');
    counter.className = 'form-hint text-right';
    counter.style.display = 'block';
    const update = () => counter.textContent = `${ta.value.length} / ${ta.maxLength}`;
    ta.addEventListener('input', update);
    ta.after(counter);
    update();
  });

});

/* Global siteUrl used by inline scripts */
const siteUrl = document.querySelector('meta[name="site-url"]')?.content || '';
