// Helper de autenticación compartido por index.html, guardia.html y admin.html.
// La protección real vive en el servidor (cada endpoint valida sesión y rol);
// esto solo evita que alguien sin sesión o con el rol equivocado vea la página.

const ROL_HOME = { cliente: 'index.html', operador: 'guardia.html', admin: 'admin.html' };

/**
 * Escapa texto antes de insertarlo en HTML (previene XSS). Usar siempre que
 * un valor que venga del servidor/usuario se inserte vía innerHTML.
 */
function escapeHtml(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

/**
 * Verifica que haya una sesión activa con uno de los roles permitidos.
 * Si no la hay, redirige a login.html; si el rol no corresponde a esta
 * página, redirige a la página que sí le corresponde a ese usuario.
 * Devuelve el objeto usuario ({id, nombre, email, rol}) o null.
 */
async function protegerPagina(rolesPermitidos) {
    try {
        const res = await fetch('api/sesion.php');
        const data = await res.json();

        if (!data.success) {
            window.location.href = 'login.html';
            return null;
        }

        if (!rolesPermitidos.includes(data.usuario.rol)) {
            window.location.href = ROL_HOME[data.usuario.rol] || 'login.html';
            return null;
        }

        return data.usuario;
    } catch (err) {
        window.location.href = 'login.html';
        return null;
    }
}

async function cerrarSesionGlobal() {
    try {
        await fetch('api/logout.php', { method: 'POST' });
    } catch (err) {
        // continúa aunque falle la petición
    }
    window.location.href = 'login.html';
}
