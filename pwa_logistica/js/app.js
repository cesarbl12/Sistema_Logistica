if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js');
}

let usuarioActual = null;
let historialData = [];

document.addEventListener('DOMContentLoaded', async () => {
    usuarioActual = await protegerPagina(['cliente']);
    if (!usuarioActual) return;

    document.getElementById('badgeUsuario').textContent = usuarioActual.nombre;
    cargarHistorial();
});

// Registro de contenedor
document.getElementById('formRegistro').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);

    const res = await fetch('api/crear_registro.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
        mostrarQR(data.codigo_qr);
        e.target.reset();
        cargarHistorial();
    } else {
        alert('Error: ' + data.error);
    }
});

// Cambiar contraseña
document.getElementById('formPass').addEventListener('submit', async (e) => {
    e.preventDefault();
    const res = await fetch('api/cambiar_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            passActual: document.getElementById('passActual').value,
            passNueva: document.getElementById('passNueva').value
        })
    });
    const data = await res.json();
    alert(data.message);
    if(data.success) {
        e.target.reset();
        cerrarModalPass();
    }
});

// Cargar Historial desde MySQL
async function cargarHistorial() {
    const res = await fetch('api/obtener_registros.php');
    const data = await res.json();
    const tbody = document.getElementById('tablaHistorial');
    tbody.innerHTML = '';

    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;">Sin registros previos</td></tr>';
        return;
    }

    historialData = data;

    data.forEach(item => {
        const esPendiente = item.estado === 'pendiente';
        const accionesExtra = esPendiente
            ? `<button class="btn btn-secondary btn-inline" onclick="editarRegistro(${item.id})">Editar</button>
               <button class="btn btn-inline btn-danger" onclick="eliminarRegistroCliente(${item.id})">Eliminar</button>`
            : '';

        tbody.innerHTML += `
            <tr>
                <td>#${item.id}</td>
                <td>${escapeHtml(item.nombre_operador)}</td>
                <td>${escapeHtml(item.placas_unidad)}</td>
                <td>${escapeHtml(item.num_contenedor)}</td>
                <td style="text-transform:capitalize;">${escapeHtml(item.tipo_operacion)}</td>
                <td>${escapeHtml(item.linea_transporte)}</td>
                <td><span class="badge-status status-${item.estado}">${escapeHtml(item.estado)}</span></td>
                <td style="white-space:nowrap;">
                    <button class="btn btn-secondary btn-inline" onclick="mostrarQR('${item.codigo_qr}')">QR</button>
                    ${accionesExtra}
                </td>
            </tr>
        `;
    });
}

function mostrarQR(codigo) {
    const div = document.getElementById('qrcode');
    div.innerHTML = '';
    new QRCode(div, { text: codigo, width: 200, height: 200 });
    document.getElementById('modalQR').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalQR').style.display = 'none';
}

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

function abrirModalPass() {
    document.getElementById('modalPass').style.display = 'flex';
}

function cerrarModalPass() {
    document.getElementById('modalPass').style.display = 'none';
}

// ---------- Editar / Eliminar registro (solo mientras esté pendiente) ----------
function editarRegistro(id) {
    const item = historialData.find(r => r.id === id);
    if (!item) return;

    document.getElementById('editarId').value = item.id;
    document.getElementById('editarOperador').value = item.nombre_operador;
    document.getElementById('editarCorreo').value = item.correo_cliente;
    document.getElementById('editarPlacas').value = item.placas_unidad;
    document.getElementById('editarOperacion').value = item.tipo_operacion;
    document.getElementById('editarContenedor').value = item.num_contenedor;
    document.getElementById('editarLinea').value = item.linea_transporte;
    document.getElementById('modalEditar').style.display = 'flex';
}

function cerrarModalEditar() {
    document.getElementById('modalEditar').style.display = 'none';
}

document.getElementById('formEditar').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);

    try {
        const res = await fetch('api/actualizar_registro.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            cerrarModalEditar();
            cargarHistorial();
        } else {
            alert(data.error || 'No se pudo actualizar el registro.');
        }
    } catch (err) {
        alert('Error al actualizar el registro. Verifica tu conexión.');
    }
});

async function eliminarRegistroCliente(id) {
    if (!confirm('¿Seguro que deseas eliminar este registro? Esta acción no se puede deshacer.')) return;

    try {
        const res = await fetch('api/eliminar_registro.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();

        if (data.success) {
            cargarHistorial();
        } else {
            alert(data.error || 'No se pudo eliminar el registro.');
        }
    } catch (err) {
        alert('Error al eliminar el registro. Verifica tu conexión.');
    }
}