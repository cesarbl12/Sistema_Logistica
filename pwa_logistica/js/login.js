const ROL_HOME = { cliente: 'index.html', operador: 'guardia.html', admin: 'admin.html' };

document.getElementById('formLogin').addEventListener('submit', async (e) => {
    e.preventDefault();

    const errorBox = document.getElementById('loginError');
    const btn = document.getElementById('btnLogin');
    errorBox.classList.remove('visible');

    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    btn.disabled = true;
    btn.textContent = 'Entrando...';

    try {
        const res = await fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();

        if (data.success) {
            window.location.href = ROL_HOME[data.usuario.rol] || 'index.html';
        } else {
            errorBox.textContent = data.error || 'No se pudo iniciar sesión.';
            errorBox.classList.add('visible');
        }
    } catch (err) {
        errorBox.textContent = 'Error de conexión. Intenta de nuevo.';
        errorBox.classList.add('visible');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Entrar';
    }
});

// Si ya hay sesión activa, saltamos directo a la página correspondiente.
(async () => {
    try {
        const res = await fetch('api/sesion.php');
        const data = await res.json();
        if (data.success) {
            window.location.href = ROL_HOME[data.usuario.rol] || 'index.html';
        }
    } catch (err) {
        // Si falla la verificación, dejamos al usuario en la pantalla de login.
    }
})();
