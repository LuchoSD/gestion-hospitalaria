//modales empleados
function crearEmpleado(){
    const modal = document.getElementById('nuevoEmpleado');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function cerrarModalEmpleado(){
    const modal = document.getElementById('nuevoEmpleado');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function toggleEspecialidad(){
    const rol = document.getElementById('rolSelect').value;
    const campo = document.getElementById('campoEspecialidad');
    //oculto especialidad si es recepcionista o admin
    campo.style.display = (rol==='2') ? "block" : "none";
}

//modales recetas
function abrirRecetas(){
    const modal = document.getElementById('creadorRecetas');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function cerrarPanel(){
    const modal = document.getElementById('creadorRecetas');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function accionReceta(modo){
    const seleccion = document.getElementById('seleccionPacienteReceta');    
    const pacienteID = seleccion.value;
    const pacienteNombre = seleccion.options[seleccion.selectedIndex].text;
    const texto = document.getElementById('textoReceta').value;

    if(!pacienteID || !texto){
        alert("Selecciona un paciente y escribe la receta, por favor.");
        return;
    }

    if(modo === 'imprimir'){
        window.open('../api/generar_pdf.php?nombre=' + encodeURIComponent(pacienteNombre) + '&msg=' + encodeURIComponent(texto), '_blank');
    }else{
        alert("Enviando receta al correo del paciente...");
    }
}

//modales cambiar estado empleados (y pacientes)
function cambioEstado(id, email, estadoActual, nombreTabla='usuarios'){
    const modal = document.getElementById('modalEstado');
    const nuevoEstado = (estadoActual === 'activo') ? 'inactivo' : 'activo';

    document.getElementById('emailEmpleadoModal').innerText = email;
    document.getElementById('idUsuarioModal').value= id;
    document.getElementById('nuevoEstadoModal').value=nuevoEstado;

    const inputTabla = document.getElementById('tablaModal') || document.querySelector('input[name="tabla"]');
    if(inputTabla){
        inputTabla.value = nombreTabla;
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function cerrarModalEstado(){
    const modal = document.getElementById('modalEstado');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

//modales pacientes recepcionista
function abrirModalPacientes(){
    const modal = document.getElementById('modalPacientes');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function cerrarModalPacientes(){
    const modal = document.getElementById('modalPacientes');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

//citas
function abrirModalCita(){
    const modal = document.getElementById('modalCita');
    if(modal){
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}
function cerrarModalCita(){
    const modal = document.getElementById('modalCita');
    if(modal){
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

//historial clinico
async function verFichaCompleta(id){
    const modal = document.getElementById('modalHistorial');
    const contenido = document.getElementById('contenidoHistorial');

    contenido.innerHTML = '<div class="div class="p-20 text-center"><p class="text-slate-400 animate-pulse">Cargando historial...</p></div>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    fetch('../api/obtener_detalle_historial.php?id=' + id)
        .then(response => response.text())
        .then(html =>{
            contenido.innerHTML = html;
        })
    .catch (err =>{
        contenido.innerHTML = '<p class= "text-red-500 p-8">Error al cargar los datos.</p>';
    });
}
function cerrarModalHistorial(){
    const modal = document.getElementById('modalHistorial');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}