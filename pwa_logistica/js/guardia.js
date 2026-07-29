if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js');
}

let scanner = null;
let registroActual = null;

function iniciarScanner() {
    scanner = new Html5QrcodeScanner('reader', { fps: 10, qrbox: { width: 250, height: 250 } }, false);
    scanner.render(onScanSuccess, onScanError);
}

function onScanError() {
    // Se ignoran los errores de frames sin QR detectado (ocurren constantemente mientras se escanea)
}

async function onScanSuccess(codigoDecodificado) {
    if (!scanner) return;
    try {
        await scanner.clear();
    } catch (err) {
        // Si ya estaba limpio, continuamos de todos modos
    }
    scanner = null;
    document.getElementById('reader').style.display = 'none';
    document.getElementById('scannerHint').style.display = 'none';
    await buscarRegistro(codigoDecodificado);
}

async function buscarRegistro(codigo) {
    try {
        const res = await fetch(`api/obtener_registro.php?codigo=${encodeURIComponent(codigo)}`);
        const data = await res.json();

        if (data.success) {
            mostrarInfoRegistro(data.registro);
        } else {
            alert(data.error || 'Registro no encontrado.');
            volverAEscanear();
        }
    } catch (err) {
        alert('Error al consultar el registro. Verifica tu conexión.');
        volverAEscanear();
    }
}

function mostrarInfoRegistro(registro) {
    registroActual = registro;

    document.getElementById('infoOperador').textContent = registro.nombre_operador;
    document.getElementById('infoPlacas').textContent = registro.placas_unidad;
    document.getElementById('infoContenedor').textContent = registro.num_contenedor;
    document.getElementById('infoOperacion').textContent = {
        dejar: 'Dejar Contenedor',
        retirar: 'Retirar Contenedor',
        ambos: 'Dejar y Retirar'
    }[registro.tipo_operacion] || registro.tipo_operacion;
    document.getElementById('infoLinea').textContent = registro.linea_transporte;
    document.getElementById('infoCorreo').textContent = registro.correo_cliente;

    const badgeEstado = document.getElementById('infoEstado');
    badgeEstado.textContent = registro.estado;
    badgeEstado.className = 'badge-status status-' + registro.estado;

    const foto = document.getElementById('infoFoto');
    foto.src = registro.identificacion_foto;

    const btn = document.getElementById('btnOperacionRealizada');
    const yaCompletado = registro.estado === 'completado';
    btn.disabled = yaCompletado;
    btn.textContent = yaCompletado ? 'Ya fue completado' : 'Operación Realizada';

    document.getElementById('panelInfo').style.display = 'block';
}

async function marcarOperacionRealizada() {
    if (!registroActual) return;
    if (!confirm('¿Confirmas que la operación fue realizada? Se notificará al cliente por correo.')) return;

    const btn = document.getElementById('btnOperacionRealizada');
    btn.disabled = true;
    btn.textContent = 'Procesando...';

    try {
        const res = await fetch('api/completar_registro.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ codigo_qr: registroActual.codigo_qr })
        });
        const data = await res.json();

        if (data.success) {
            alert(data.correo_enviado
                ? 'Operación completada y cliente notificado por correo.'
                : 'Operación completada, pero no se pudo enviar el correo: ' + (data.correo_error || 'revisa la configuración en config/email.php'));
            volverAEscanear();
        } else {
            alert(data.error || 'No se pudo completar la operación.');
            btn.disabled = false;
            btn.textContent = 'Operación Realizada';
        }
    } catch (err) {
        alert('Error al procesar la operación. Verifica tu conexión.');
        btn.disabled = false;
        btn.textContent = 'Operación Realizada';
    }
}

function volverAEscanear() {
    registroActual = null;
    document.getElementById('panelInfo').style.display = 'none';
    document.getElementById('reader').style.display = 'block';
    document.getElementById('scannerHint').style.display = 'block';
    document.getElementById('reader').innerHTML = '';
    iniciarScanner();
}

document.addEventListener('DOMContentLoaded', async () => {
    const usuario = await protegerPagina(['operador']);
    if (!usuario) return;

    document.getElementById('badgeUsuario').textContent = usuario.nombre;
    iniciarScanner();
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
