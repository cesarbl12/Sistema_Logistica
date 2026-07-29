if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js');
}

const ETIQUETAS_EVENTO = {
    creacion_qr: 'Creación de QR',
    lectura_qr: 'Lectura de QR',
    operacion_completada: 'Operación Completada',
    mensaje_enviado: 'Mensaje Enviado',
    mensaje_fallido: 'Mensaje Fallido',
    edicion_registro: 'Edición de Registro',
    eliminacion_registro: 'Eliminación de Registro'
};

let usuariosData = [];

const CLASES_EVENTO = {
    creacion_qr: 'evento-creacion',
    lectura_qr: 'evento-lectura',
    operacion_completada: 'evento-completado',
    mensaje_enviado: 'evento-mensaje',
    mensaje_fallido: 'evento-fallido',
    edicion_registro: 'evento-edicion',
    eliminacion_registro: 'evento-fallido'
};

document.addEventListener('DOMContentLoaded', async () => {
    const usuario = await protegerPagina(['admin']);
    if (!usuario) return;

    document.getElementById('badgeUsuario').textContent = usuario.nombre;
    cargarEstadisticas();
    cargarBitacora();
    cargarUsuarios();
});

function toggleMenuUsuario() {
    document.getElementById('menuUsuario').classList.toggle('open');
}

document.addEventListener('click', (e) => {
    const menu = document.getElementById('menuUsuario');
    const userMenu = document.querySelector('.user-menu');
    if (menu.classList.contains('open') && !userMenu.contains(e.target)) {
        menu.classList.remove('open');
    }
});

async function cerrarSesion() {
    toggleMenuUsuario();
    if (!confirm('¿Seguro que deseas cerrar sesión?')) return;
    await cerrarSesionGlobal();
}

// ---------- Estadísticas ----------
async function cargarEstadisticas() {
    try {
        const res = await fetch('api/admin/obtener_estadisticas.php');
        const data = await res.json();
        if (!data.success) return;

        document.getElementById('statCreacionQr').textContent = data.eventos.creacion_qr;
        document.getElementById('statLecturaQr').textContent = data.eventos.lectura_qr;
        document.getElementById('statMensajeEnviado').textContent = data.eventos.mensaje_enviado;
        document.getElementById('statPendientes').textContent = data.registros_pendientes;
    } catch (err) {
        console.error('Error al cargar estadísticas', err);
    }
}

// ---------- Bitácora ----------
async function cargarBitacora() {
    const tipo = document.getElementById('filtroTipoEvento').value;
    const tbody = document.getElementById('tablaBitacora');

    try {
        const res = await fetch(`api/admin/obtener_bitacora.php?tipo=${encodeURIComponent(tipo)}`);
        const data = await res.json();

        if (!data.success) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;">Error al cargar la bitácora.</td></tr>`;
            return;
        }

        if (!data.eventos.length) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;">Sin movimientos registrados.</td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        data.eventos.forEach(ev => {
            const fecha = new Date(ev.fecha).toLocaleString('es-MX');
            tbody.innerHTML += `
                <tr>
                    <td>${fecha}</td>
                    <td><span class="badge-status ${CLASES_EVENTO[ev.tipo_evento] || ''}">${escapeHtml(ETIQUETAS_EVENTO[ev.tipo_evento] || ev.tipo_evento)}</span></td>
                    <td>${escapeHtml(ev.detalle)}</td>
                    <td>${ev.num_contenedor ? '#' + escapeHtml(ev.num_contenedor) : '—'}</td>
                </tr>
            `;
        });
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;">Error al cargar la bitácora.</td></tr>`;
    }
}

// ---------- Usuarios ----------
async function cargarUsuarios() {
    const tbody = document.getElementById('tablaUsuarios');
    try {
        const res = await fetch('api/admin/listar_usuarios.php');
        const data = await res.json();

        if (!data.success) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">Error al cargar usuarios.</td></tr>`;
            return;
        }

        usuariosData = data.usuarios;

        tbody.innerHTML = '';
        data.usuarios.forEach(u => {
            const fecha = new Date(u.creado_en).toLocaleDateString('es-MX');
            tbody.innerHTML += `
                <tr>
                    <td>${escapeHtml(u.nombre)}</td>
                    <td>${escapeHtml(u.email)}</td>
                    <td style="text-transform:capitalize;">${escapeHtml(u.rol)}</td>
                    <td>${fecha}</td>
                    <td>
                        <button class="btn btn-secondary btn-inline" onclick="abrirModalUsuario(${u.id})">Editar</button>
                        <button class="btn btn-inline btn-danger" onclick="eliminarUsuario(${u.id})">Eliminar</button>
                    </td>
                </tr>
            `;
        });
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">Error al cargar usuarios.</td></tr>`;
    }
}

function abrirModalUsuario(id) {
    const usuario = id ? usuariosData.find(u => u.id === id) : null;
    const form = document.getElementById('formUsuario');
    form.reset();

    if (usuario) {
        document.getElementById('tituloModalUsuario').textContent = 'Editar Usuario';
        document.getElementById('usuarioId').value = usuario.id;
        document.getElementById('usuarioNombre').value = usuario.nombre;
        document.getElementById('usuarioEmail').value = usuario.email;
        document.getElementById('usuarioRol').value = usuario.rol;
        document.getElementById('usuarioPassword').required = false;
        document.getElementById('labelPassword').textContent = 'Nueva Contraseña (opcional)';
        document.getElementById('hintPassword').style.display = 'block';
    } else {
        document.getElementById('tituloModalUsuario').textContent = 'Nuevo Usuario';
        document.getElementById('usuarioId').value = '';
        document.getElementById('usuarioPassword').required = true;
        document.getElementById('labelPassword').textContent = 'Contraseña';
        document.getElementById('hintPassword').style.display = 'none';
    }

    document.getElementById('modalUsuario').style.display = 'flex';
}

function cerrarModalUsuario() {
    document.getElementById('modalUsuario').style.display = 'none';
}

document.getElementById('formUsuario').addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('usuarioId').value;
    const payload = {
        nombre: document.getElementById('usuarioNombre').value,
        email: document.getElementById('usuarioEmail').value,
        rol: document.getElementById('usuarioRol').value,
        password: document.getElementById('usuarioPassword').value
    };

    const esEdicion = !!id;
    if (esEdicion) payload.id = parseInt(id, 10);

    const url = esEdicion ? 'api/admin/actualizar_usuario.php' : 'api/admin/crear_usuario.php';

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            cerrarModalUsuario();
            cargarUsuarios();
        } else {
            alert(data.error || 'No se pudo guardar el usuario.');
        }
    } catch (err) {
        alert('Error al guardar el usuario. Verifica tu conexión.');
    }
});

async function eliminarUsuario(id) {
    const usuario = usuariosData.find(u => u.id === id);
    if (!confirm(`¿Eliminar al usuario "${usuario ? usuario.nombre : id}"? Esta acción no se puede deshacer.`)) return;

    try {
        const res = await fetch('api/admin/eliminar_usuario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();

        if (data.success) {
            cargarUsuarios();
        } else {
            alert(data.error || 'No se pudo eliminar el usuario.');
        }
    } catch (err) {
        alert('Error al eliminar el usuario. Verifica tu conexión.');
    }
}
