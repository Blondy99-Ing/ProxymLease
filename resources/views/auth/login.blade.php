<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SwapManager</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
    :root {
        /* Mode clair */
        --bg-primary: #f8f9fa;
        --bg-secondary: #ffffff;
        --bg-card: #ffffff;
        --text-primary: #333333;
        --text-secondary: #666666;
        --text-muted: #999999;
        --border-color: #e9ecef;
        --shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        --shadow-large: 0 10px 30px rgba(0, 0, 0, 0.15);
        --accent-yellow: #ffc107;
        --accent-green: #28a745;
        --accent-red: #dc3545;
        --accent-blue: #007bff;
        --hover-bg: #f8f9fa;
        --input-focus: rgba(255, 193, 7, 0.1);
    }

    [data-theme="dark"] {
        /* Mode sombre */
        --bg-primary: #1a1a1a;
        --bg-secondary: #2d2d2d;
        --bg-card: #2d2d2d;
        --text-primary: #ffffff;
        --text-secondary: #cccccc;
        --text-muted: #999999;
        --border-color: #404040;
        --shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        --shadow-large: 0 10px 30px rgba(0, 0, 0, 0.5);
        --hover-bg: #404040;
        --input-focus: rgba(255, 193, 7, 0.2);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        color: var(--text-primary);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        position: relative;
        overflow-x: hidden;
    }

    /* Arrière-plan animé */
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background:
            radial-gradient(circle at 20% 80%, rgba(255, 193, 7, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(40, 167, 69, 0.1) 0%, transparent 50%);
        z-index: -1;
        animation: backgroundFloat 20s ease-in-out infinite;
    }

    @keyframes backgroundFloat {

        0%,
        100% {
            transform: translate(0, 0) rotate(0deg);
        }

        33% {
            transform: translate(30px, -30px) rotate(120deg);
        }

        66% {
            transform: translate(-20px, 20px) rotate(240deg);
        }
    }

    /* Bouton de mode sombre */
    .theme-toggle {
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: var(--bg-card);
        border: 2px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.75rem;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: var(--shadow);
        z-index: 1000;
        font-size: 1.2rem;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .theme-toggle:hover {
        background-color: var(--accent-yellow);
        color: #333;
        transform: rotate(180deg) scale(1.1);
    }

    /* Container principal */
    .wrap {
        width: 100%;
        max-width: 450px;
        padding: 2rem;
    }

    .card {
        background: var(--bg-card);
        border-radius: 1rem;
        box-shadow: var(--shadow-large);
        padding: 3rem 2.5rem;
        backdrop-filter: blur(10px);
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
        animation: slideUp 0.6s ease-out;
    }

    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--accent-yellow), #ffdb4d);
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Logo/Titre */
    .card h1 {
        font-family: 'Orbitron', monospace;
        font-size: 2.5rem;
        font-weight: 900;
        color: var(--accent-yellow);
        text-align: center;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
    }

    .lead {
        text-align: center;
        color: var(--text-secondary);
        font-size: 1.1rem;
        margin-bottom: 2.5rem;
        font-weight: 400;
    }

    /* Alertes */
    .alert {
        padding: 1rem 1.25rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        font-weight: 500;
        border-left: 4px solid;
        animation: alertSlide 0.4s ease-out;
    }

    .alert.info {
        background-color: rgba(23, 162, 184, 0.1);
        border-color: #17a2b8;
        color: #17a2b8;
    }

    .alert.danger {
        background-color: rgba(220, 53, 69, 0.1);
        border-color: var(--accent-red);
        color: var(--accent-red);
    }

    @keyframes alertSlide {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .alert ul {
        margin: 0 0 0 18px;
        padding: 0;
    }

    /* Formulaire */
    form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    label {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
        display: block;
    }

    input[type="email"],
    input[type="password"] {
        padding: 1rem 1.25rem;
        border: 2px solid var(--border-color);
        border-radius: 0.75rem;
        background-color: var(--bg-secondary);
        color: var(--text-primary);
        font-size: 1rem;
        transition: all 0.3s ease;
        width: 100%;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
        outline: none;
        border-color: var(--accent-yellow);
        box-shadow: 0 0 0 3px var(--input-focus);
        transform: translateY(-2px);
    }

    input[type="email"]:hover,
    input[type="password"]:hover {
        border-color: var(--accent-yellow);
    }

    /* Erreurs de champ */
    .field-error {
        color: var(--accent-red);
        font-size: 0.875rem;
        margin-top: 0.5rem;
        font-weight: 500;
        animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
    }

    /* Ligne des options */
    .row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .remember {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        font-weight: 500;
        color: var(--text-secondary);
        transition: color 0.3s ease;
    }

    .remember:hover {
        color: var(--text-primary);
    }

    input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--accent-yellow);
        cursor: pointer;
    }

    .link {
        color: var(--accent-blue);
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .link:hover {
        color: var(--accent-yellow);
        text-decoration: underline;
    }

    /* Bouton de connexion */
    .actions {
        margin-top: 1rem;
    }

    .btn {
        width: 100%;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, var(--accent-yellow), #ffdb4d);
        color: #333;
        border: none;
        border-radius: 0.75rem;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        position: relative;
        overflow: hidden;
    }

    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s ease;
    }

    .btn:hover::before {
        left: 100%;
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
    }

    .btn:active {
        transform: translateY(-1px);
    }

    /* Badge SwapManager */
    .brand-badge {
        position: absolute;
        top: -12px;
        right: -12px;
        background: linear-gradient(135deg, var(--accent-yellow), #ffdb4d);
        color: #333;
        padding: 0.5rem 1rem;
        border-radius: 0 1rem 0 1rem;
        font-family: 'Orbitron', monospace;
        font-weight: 700;
        font-size: 0.8rem;
        box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        border: 2px solid var(--bg-card);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .wrap {
            padding: 1rem;
            max-width: 100%;
        }

        .card {
            padding: 2rem 1.5rem;
        }

        .card h1 {
            font-size: 2rem;
        }

        .theme-toggle {
            top: 1rem;
            right: 1rem;
            width: 45px;
            height: 45px;
        }

        .row {
            flex-direction: column;
            align-items: flex-start;
        }

        .link {
            align-self: flex-end;
        }
    }

    /* Animation au focus des inputs */
    .input-group {
        position: relative;
    }

    .input-group input:focus+label,
    .input-group input:not(:placeholder-shown)+label {
        transform: translateY(-25px) scale(0.9);
        color: var(--accent-yellow);
    }

    /* Effets de particules (optionnel) */
    .particles {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
    }

    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: var(--accent-yellow);
        border-radius: 50%;
        opacity: 0.6;
        animation: float 15s infinite linear;
    }

    @keyframes float {
        0% {
            transform: translateY(100vh) rotate(0deg);
            opacity: 0;
        }

        10% {
            opacity: 0.6;
        }

        90% {
            opacity: 0.6;
        }

        100% {
            transform: translateY(-10vh) rotate(360deg);
            opacity: 0;
        }
    }
    </style>
</head>

<body data-theme="light">
    <!-- Bouton de basculement du thème -->
    <button class="theme-toggle" id="themeToggle">🌙</button>

    <!-- Particules flottantes -->
    <div class="particles" id="particles"></div>

    <div class="wrap">
        <div class="card">
            <div class="brand-badge">PROXYM Lease</div>

            <h1>Connexion</h1>
            <p class="lead">Accédez à votre compte employé.</p>

            <!-- Message de statut simulé -->
            <div class="alert info" style="display: none;" id="statusAlert">
                Mot de passe réinitialisé avec succès.
            </div>

            <!-- Erreurs de validation simulées -->
            <div class="alert danger" style="display: none;" id="errorAlert">
                <ul>
                    <li>L'adresse email est requise.</li>
                    <li>Le mot de passe doit contenir au moins 8 caractères.</li>
                </ul>
            </div>

            <form id="loginForm" method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="input-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        autocomplete="username">
                    @error('email') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="input-group">
                    <label for="password">Mot de passe</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                    @error('password') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <label class="remember">
                        <input id="remember_me" type="checkbox" name="remember"> Se souvenir de moi
                    </label>

                    @if (Route::has('password.request'))
                    <a class="link" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
                    @endif
                </div>

                <div class="actions">
                    <button class="btn" type="submit" id="submitBtn">Se connecter</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Fonction pour basculer la visibilité du mot de passe
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.querySelector('.password-toggle');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleButton.textContent = '🙈';
            toggleButton.setAttribute('aria-label', 'Masquer le mot de passe');
        } else {
            passwordInput.type = 'password';
            toggleButton.textContent = '👁️';
            toggleButton.setAttribute('aria-label', 'Afficher le mot de passe');
        }

        // Animation du bouton
        toggleButton.style.transform = 'translateY(-50%) scale(0.9)';
        setTimeout(() => {
            toggleButton.style.transform = 'translateY(-50%) scale(1)';
        }, 100);
    }

    // Gestion du mode sombre
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;

    // Vérifier le thème sauvegardé
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        body.setAttribute('data-theme', savedTheme);
        themeToggle.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
    }

    // Basculer le thème
    themeToggle.addEventListener('click', () => {
        const currentTheme = body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        body.setAttribute('data-theme', newTheme);
        themeToggle.textContent = newTheme === 'dark' ? '☀️' : '🌙';
        localStorage.setItem('theme', newTheme);

        // Animation de transition
        body.style.transition = 'all 0.5s ease';
        setTimeout(() => {
            body.style.transition = 'all 0.3s ease';
        }, 500);
    });

    // Création des particules flottantes
    function createParticles() {
        const particlesContainer = document.getElementById('particles');
        const particleCount = 20;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            particlesContainer.appendChild(particle);
        }
    }

    // Gestion du formulaire de connexion
    function handleLogin(event) {
        event.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const submitBtn = event.target.querySelector('.btn');

        // Réinitialiser les erreurs
        document.getElementById('emailError').style.display = 'none';
        document.getElementById('passwordError').style.display = 'none';
        document.getElementById('errorAlert').style.display = 'none';

        // Validation simple
        let hasErrors = false;

        if (!email || !email.includes('@')) {
            document.getElementById('emailError').style.display = 'block';
            hasErrors = true;
        }

        if (!password || password.length < 6) {
            document.getElementById('passwordError').style.display = 'block';
            hasErrors = true;
        }

        if (hasErrors) {
            document.getElementById('errorAlert').style.display = 'block';
            return;
        }

        // Animation du bouton de connexion
        submitBtn.disabled = true;
        submitBtn.textContent = 'Connexion...';
        submitBtn.style.background = 'linear-gradient(135deg, #ccc, #999)';

        // Simuler la connexion
        setTimeout(() => {
            if (email === 'admin@swapmanager.com' && password === 'password') {
                // Succès
                submitBtn.textContent = 'Connecté !';
                submitBtn.style.background = 'linear-gradient(135deg, #28a745, #32cd32)';

                // Redirection simulée
                setTimeout(() => {
                    alert('Connexion réussie ! Redirection vers le tableau de bord...');
                    // window.location.href = '/dashboard';
                }, 1000);
            } else {
                // Échec
                submitBtn.textContent = 'Échec de connexion';
                submitBtn.style.background = 'linear-gradient(135deg, #dc3545, #ff6b7a)';
                document.getElementById('errorAlert').innerHTML =
                    '<ul><li>Email ou mot de passe incorrect.</li></ul>';
                document.getElementById('errorAlert').style.display = 'block';

                // Réinitialiser le bouton après 2 secondes
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Se connecter';
                    submitBtn.style.background =
                        'linear-gradient(135deg, var(--accent-yellow), #ffdb4d)';
                }, 2000);
            }
        }, 2000);
    }

    // Afficher la notification de réinitialisation de mot de passe
    function showPasswordReset() {
        document.getElementById('statusAlert').style.display = 'block';
        setTimeout(() => {
            document.getElementById('statusAlert').style.display = 'none';
        }, 5000);
    }

    // Animation des inputs au focus
    document.querySelectorAll('input[type="email"], input[type="password"]').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });

    // Initialisation
    document.addEventListener('DOMContentLoaded', () => {
        createParticles();

        // Animation d'entrée de la carte
        setTimeout(() => {
            document.querySelector('.card').style.transform = 'translateY(0) scale(1)';
        }, 100);
    });

    // Effet de parallax sur la souris
    document.addEventListener('mousemove', (e) => {
        const mouseX = e.clientX / window.innerWidth;
        const mouseY = e.clientY / window.innerHeight;

        const card = document.querySelector('.card');
        const moveX = (mouseX - 0.5) * 10;
        const moveY = (mouseY - 0.5) * 10;

        card.style.transform = `translate(${moveX}px, ${moveY}px)`;
    });
    </script>
</body>

</html>