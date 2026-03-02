<?php
session_start();
require_once '../config/db.php';

//filtros
$inactividad = 600;

if(!isset($_SESSION['user_id']) || (int)$_SESSION['rol'] != 3){
    header("Location: ../login.php?error=no_autorizado");
    exit;
}

if (isset($_SESSION['ultimo_acceso']) && (time() - $_SESSION['ultimo_acceso'] > $inactividad)) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?error=timeout");
    exit;
}

$_SESSION['ultimo_acceso'] = time();
$sql_medicos = "SELECT u.id, pe.nombre
                FROM usuarios u
                JOIN perfiles_empleados pe ON u.id = pe.id_usuario
                WHERE u.id_rol = 2 
                AND u.estado = 'activo'";
$stmt = $pdo->query($sql_medicos);
$medicos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Calendario Global Care Digital</title>
    <style>
        /*agrego estilos para que cuadre la api con la pagina*/
        .fc .fc-button-primary { background-color: #1e3a8a; border-color: #1e3a8a; }
        .fc .fc-button-primary:hover { background-color: #172554; border-color: #172554; }
        .fc-event { border-radius: 8px; padding: 2px 4px; }
    </style>
</head>
<body>
    <div class= "min-h-screen w-screen bg-[oklch(98.4%_0.019_200.873)] flex flex-col">
            <?php include '../components/headerR.php'; ?>
            <div class="flex flex-1 overflow-hidden">
                <?php include '../components/sidebarR.php'; ?>
                <main class="flex-1 p-8 overflow-y-auto">
                    <div class="max-w-6xl mx-auto p-6">
                        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 bg-white p-4 rounded-xl shadow-sm gap-4">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">Calendario Citas Pacientes</h1>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <label for="filtroMedico" class="font-medium text-gray-700">Filtrar por Medico</label>
                                <select id="filtroMedico" class="border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 text-sm bg-white">
                                    <option value="">Todos los medicos</option>
                                    <?php foreach($medicos as $m): ?>
                                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                                    <?php endforeach;?>
                                </select>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-xl shadow-md">
                            <div id='calendar'></div>
                        </div>
                    </div>
                </main>
            </div>
            <!--modal citas-->
            <div id="modalDetalleCita" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm justify-center items-center z-[100] p-4">
                <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden transform transition-all">
                    <div class="bg-blue-900 p-6 text-white flex justify-between items-center">
                        <h3 class="text-xl font-bold">Detalle de la Cita</h3>
                        <button onclick="cerrarDetalleCita()" class="hover:text-red-400 transition-colors">
                            <svg width="24" height="24" fill="currentColor">
                                <use href="../assets/sprite.svg#icon-cerrar"></use>
                            </svg>
                        </button>
                    </div>
                    <div class="p-8 space-y-4 text-left">
                        <div>
                            <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Paciente</p>
                            <p id="detPaciente" class="text-lg font-bold text-blue-900"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Fecha y Hora</p>
                                <p id="detFechaHora" class="text-gray-700 font-semibold"></p>
                            </div>
                            <div> 
                                <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Estado</p>
                                <div id="detEstadoCont">
                                    <span id="detEstado" class="px-2 py-1 rounded-md text-[10px font-black uppercase"></span>
                                </div>
                            </div>
                                <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Motivo</p>
                                <p id="detMotivo" class="text-sm text-gray-600 italic bg-slate-50 p-3 rounded-xl border border-slate-100"></p>
                            </div>
                            <button type="button" onclick="cerrarDetalleCita()" class="w-full bg-slate-100 text-slate-600 py-3 rounded-2xl font-bold hover:bg-slate-200 transition-all mt-4">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            //cerrar detalle cita
            function cerrarDetalleCita(){
                        const modal = document.getElementById('modalDetalleCita');
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }
            document.addEventListener('DOMContentLoaded', function(){
                let calendarEl = document.getElementById('calendar');
                let filtroMedico = document.getElementById('filtroMedico');

                let calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    firstDay: 1,
                    buttonText: {today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Dia'},
                    headerToolbar:{
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: function(info, successCallback, failureCallback){
                        let medicoId = filtroMedico.value;
                        fetch(`../api/obtener_citas.php?id_medico=${medicoId}`)
                        .then(response => response.json())
                        .then(data => successCallback(data))
                        .catch(err => {
                            console.error("Error cargando citas:", err);
                            failureCallback(err);
                        });
                    },
                    eventClick: function(info){
                        const titulo = info.event.title;
                        const inicio = info.event.start;
                        const motivo = info.event.extendedProps.motivo || 'Sin motivo';
                        const estado = info.event.extendedProps.estado || 'pendiente';

                        const opciones = {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'};
                        const fecha = inicio.toLocaleDateString('es-ES', opciones);

                        document.getElementById('detPaciente').innerText = titulo;
                        document.getElementById('detFechaHora').innerText = fecha;
                        document.getElementById('detMotivo').innerText = motivo;

                        const elEstado = document.getElementById('detEstado');
                        elEstado.innerText = estado.toUpperCase();
                        elEstado.className = (estado === 'pendiente') ? 'px-2 py-1 rounded-md text-[10px] font-black bg-amber-100 text-amber-700' : 'px-2 py-1 rounded-md text-[10px] font-black bg-green-100 text-green-700';

                        const modal = document.getElementById('modalDetalleCita');
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    }
                });
                calendar.render();

                //filtro para recargar el calendario cuando cambia el filtro
                filtroMedico.addEventListener('change', function(){
                    calendar.refetchEvents();
                });
            });
        </script>
        <script src="../scripts.js?v=<?= time() ?>"></script>
        <script src="../modales.js?v=<?= time() ?>"></script> 
    </body>
</html>