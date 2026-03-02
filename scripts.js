//toast notification
document.addEventListener('DOMContentLoaded', ()=>{
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const mensaje = urlParams.get('mensaje');

    const mensajes = {
        'no_autorizado':{ texto: 'No tienes autorizacion para acceder aqui.', tipo: 'error' },
        'error_db':{ texto: 'Error en la base de datos.', tipo:'error'},
        'rol_invalido':{ texto: 'Datos incorrectos.', tipo: 'error' },
        'bloqueado':{ texto: 'Has superado el número de intentos, comunicate con recepción.', tipo: 'error' },
        'credenciales_incorrectas':{ texto: 'Usuario y/o Contraseña incorrectos.', tipo: 'error'},
        'diferentes_pass':{ texto: 'Las contraseñas no coinciden.', tipo: 'error'},
        'password_actualizado':{ texto: 'Contraseña actualizada', tipo: 'exito'},
        'estado_actualizado':{ texto: 'Estado actualizado correctamente.', tipo: 'exito' },
        'sesion_cerrada':{ texto: 'Sesión cerrada.', tipo: 'exito'},
        'sin_privilegios':{ texto: 'No tienes privilegios para realizar ésta acción.', tipo:'error'},
        'email_duplicado':{ texto: 'Email duplicado en la base de datos.', tipo: 'error'},
        'empleado_creado':{ texto: 'Empleado creado.', tipo: 'exito'},
        'datos_incompletos':{ texto: 'Datos incompletos.', tipo:'error'},
        'tabla_invalida':{ texto: 'Tabla Invalida', tipo:'error'},
        'error_autocambio':{ texto: 'No te puedes eliminar a ti mismo.', tipo: 'error'},
        'fuera_de_horario':{ texto: 'Fuera de horario laboral.', tipo: 'error'},
        'fecha_pasada':{ texto: 'Debes elegir una fecha igual o posterior a hoy', tipo: 'error'},
        'cita_agendada':{ texto: 'Cita agendada con exito.', tipo: 'exito'},
        'paciente_creado':{ texto: 'Paciente crfeado con exito', tipo: 'exito'},
        'sin_datos':{ text: 'Datos vacios', tipo: 'error'},
        'receta_enviada':{ text: 'Reecta enviada con exito', tipo: 'exito'},
        'olvido_credenciales':{ texto: 'Por motivos de seguridad, para reestablecer tu contraseña debes acudir presencialmente a recepcion o llamar al +34 900 00 00.', tipo:'error'},
    };

    if (error && mensajes[error]) {
        showToast(mensajes[error].texto, 'error');
    }else if (mensaje && mensajes[mensaje]){
        showToast(mensajes[mensaje].texto, 'exito');
    }
    window.history.replaceState({}, document.title, window.location.pathname);
});

function showToast(texto, tipo){
    const toast = document.createElement('div');
    const baseClasses = "fixed bottom-5 right-5 px-6 py-4 rounded-2xl shadow-2xl text-white font-bold transition-all duration-500 transform translate-y-20 opacity-0 z-[9999] flex items-center gap-3";
    const bgClass = (tipo === 'exito') ? "bg-green-600" : "bg-red-600";

    toast.className = `${baseClasses} ${bgClass}`;
    toast.innerHTML = `
                    <span>${texto}</span>
                    `;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.remove('translate-y-20', 'opacity-0');
    }, 100);

    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
        setTimeout(() => toast.remove(), 500);
    }, 10000);
}