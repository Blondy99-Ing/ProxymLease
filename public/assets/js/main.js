 // Gestion du mode sombre
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        
        // Vérifier le thème sauvegardé
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            html.setAttribute('data-theme', savedTheme);
            themeToggle.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
        }
        
        // Basculer le thème
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            themeToggle.textContent = newTheme === 'dark' ? '☀️' : '🌙';
            localStorage.setItem('theme', newTheme);
        });
        
        // Animation pour les boutons d'export
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
        
        // Recherche en temps réel
        const searchInput = document.querySelector('.search-input');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });







// pour les message d'erreurs et de succes
        (function(){
  const stack = document.getElementById('flash-stack');
  if(!stack) return;

  // Fermer au clic sur le bouton ✕
  stack.addEventListener('click', (e)=>{
    const btn = e.target.closest('.flash-close');
    if(!btn) return;
    const card = btn.closest('.flash');
    if(card){
      card.classList.add('hide');
      setTimeout(()=> card.remove(), 300);
    }
  });

  // Auto-hide après 5s
  Array.from(stack.querySelectorAll('.flash')).forEach((card, idx)=>{
    const delay = 5000 + (idx * 300); // échelonne légèrement si plusieurs
    setTimeout(()=>{
      if(!card.isConnected) return;
      card.classList.add('hide');
      setTimeout(()=> card.remove(), 300);
    }, delay);
  });
})();