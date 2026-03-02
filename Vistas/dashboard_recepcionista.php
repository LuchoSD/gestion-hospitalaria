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

//contar citas
$hoy = date('Y-m-d');
$citas_hoy = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE fecha_cita = ?");
$citas_hoy->execute([$hoy]);
$total_hoy = $citas_hoy->fetchColumn();

//ultimas citas para verlas en tabla
$sql_recientes = "SELECT c.hora_cita, c.estado, p.nombre, p.apellidos, u.nombre AS med_nom
                    FROM citas c
                    JOIN pacientes p ON c.id_paciente = p.id
                    JOIN perfiles_empleados u ON c.id_medico = u.id_usuario
                    WHERE c.fecha_cita = ?
                    ORDER BY c.hora_cita ASC LIMIT 10";
$stmt = $pdo->prepare($sql_recientes);
$stmt->execute([$hoy]);
$proximas_citas = $stmt->fetchAll();
//datos para citas
$stmt_p =$pdo->query("SELECT id, nombre, apellidos
                     FROM pacientes 
                     WHERE estado = 'activo' 
                     ORDER BY nombre ASC");
        $lista_pacientes = $stmt_pacientes = $stmt_p->fetchAll();

        $sql_medicos = "SELECT u.id, e.nombre, e.apellidos
                        FROM usuarios u
                        JOIN perfiles_empleados e ON u.id = e.id_usuario
                        WHERE u.id_rol = 2 AND u.estado = 'activo'";
        $stmt_m = $pdo->query($sql_medicos);
        $lista_medicos = $stmt_m->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
        <title>Gestor de Pacientes - Global Care</title>
    </head>
    <body>
        <div class= "min-h-screen w-screen bg-[oklch(98.4%_0.019_200.873)] flex flex-col">
            <?php include '../components/headerR.php'; ?>
            <div class="flex flex-1 overflow-hidden">
                <?php include '../components/sidebarR.php'; ?>
                <main class="flex-1 p-8 overflow-y-auto">
                    <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                        <div class="bg-white shadow-md rounded-3xl p-8 border-l-8 border-blue-900 relative overflow-hidden">
                            <div class="flex justify-between items-start">
                                <div class="text-left">
                                    <h2 class="text-gray-500 font-bold uppercase text-xs tracking-widest">Paciente Siguiente</h2>
                                    <?php if(!empty($proximas_citas)): $proxima = $proximas_citas[0];?>
                                        <div class="mt-6 text-left">
                                            <p class="text-4xl font-black text-blue-900"><?php echo htmlspecialchars($proxima['nombre']); ?></p>
                                            <p class="text-gray-400 text-sm italic"> Cita con: <span class="text-indigo-600 font-bold">Dr/a: <?php echo htmlspecialchars($proxima['med_nom']); ?></span></p>
                                        </div>
                                        <div class="mt-4 flex items-center gap-2 text-blue-600 bg-blue-50 w-fit px-4 py-2 rounded-full shadow-sm">
                                            <svg width="20" height="20" class="fill-current">
                                                <use href="../assets/sprite.svg?v=2#icon-clock"></use>
                                            </svg>
                                            <span class="font-bold text-lg"><?php echo date("H:i", strtotime($proxima['hora_cita'])); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <p class="mt-6 text-gray-400 italic">No hay mas citas hoy.</p>
                                    <?php endif;?>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-2xl">
                                    <svg width="45" height="45" class="fill-current text-blue-900">
                                        <use href="../assets/sprite.svg?v=2#icon-user"></use>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white shadow-md rounded-3xl p-8 border-l-8 border-indigo-600">
                            <h2 class="text-gray-500 font-bold uppercase text-xs tracking-widest mb-6">Resumen de Agenda</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <p class="text-gray-400 text-xs uppercase font-black">Citas Hoy</p>
                                <p class="text-3xl font-black text-indigo-600"><?php echo $total_hoy;?></p>
                            </div>
                        </div>
                    </section>
                    <div class="bg-white shadow-lg rounded-2xl overflow-hidden boder border-gray-100 mt-8">
                        <div class="p-6 border-b boorder-gray flex-justify-between items-center">
                            <h2 class="text-xl font-bold text-blue-900">Agenda Diaria</h2>
                            <a href="calendario_recep.php" class="text-sm font-bold text-blue-600 hover:underline text-left">Ver Calendario</a>
                        </div>
                        <div class="overflow-x-auto text-left">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-gray-600 uppercase text-[10px] font-black tracking-widest">
                                        <th class="px-6 py-4">Hora</th>
                                        <th class="px-6 py-4">Paciente</th>
                                        <th class="px-6 py-4">Medico</h2>
                                        <th class="px-6 py-4">Estado</h2>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-left">
                                    <?php if (count($proximas_citas) > 0): ?>
                                        <?php foreach ($proximas_citas as $cita): ?>
                                            <tr class="hover:bg-blue-50/50 transition-colors">
                                                <td class="px-6 py-4 font-bold text-blue-900 italic">
                                                    <?php echo date("H:i", strtotime($cita['hora_cita'])); ?>
                                                </td>
                                                <td class="px-6 py-4 font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($cita['nombre'] . " " . $cita['apellidos']); ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="text-sm text-gray-600 font-medium">Dr/a: <?php echo htmlspecialchars($cita['med_nom']);?></span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?php
                                                        $estado = strtolower($cita['estado']);
                                                        $clase = ($estado == 'pendiente') ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700';
                                                    ?>
                                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase <?php echo $clase; ?>">
                                                        <?php echo $estado; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-gray-400 italic text-left">No hay citas hoy.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <div id="modalCita" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm justify-center items-center z-[70] p-4">
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden">
                <div class="bg-indigo-700 p-6 flex justify-between items-center text-white">
                    <div>
                        <h3 class="text-xl font-bold">Agendar Nueva Cita</h3>
                        <p class="text-indigo-200 text-xs uppercase tracking-widest font-medium">Turno Medico</p>
                    </div>
                    <button onclick="cerrarModalCita()" class="hover: text-red-400 transition-colors">
                        <svg width="24" height="24" class="fill-current">
                            <use href="../assets/sprite.svg?v=2#icon-cerrar"></use>
                        </svg>
                    </button>
                </div>
                <form action="../api/procesar_cita.php" method="POST" class="p-8 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase text-left">Paciente</label>
                        <select name="id_paciente" required class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-indigo-500 outline-none appearance-none bg-white">
                            <option value="">Selecciona un paciente</option>
                            <?php foreach ($lista_pacientes as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre'] . " " . $p['apellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase text-left">Medico</label>
                        <select name="id_medico" required class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-indigo-500 outline-none appearance-none bg-white">
                            <option value="">Selecciona un medico</option>
                            <?php foreach($lista_medicos as $m): ?>
                                <option value="<?= $m['id'] ?>">Dr/a: <?=htmlspecialchars($m['nombre'] . " " . $m['apellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1 uppercase text-left">Fecha</label>
                            <input type="date" name="fecha_cita" required min="<?= date('Y-m-d') ?>" class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-1 uppercase text-left">Hora</label>
                            <input type="time" name="hora_cita" required class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-indigo-500 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase text-left">Motivo de consulta</label>
                        <textarea name="motivo" rows="2" class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-indigo-500 outline-none resize-none"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all transform active:scale-95">Confirmar y Agendar</button>
                </form>
            </div>
        </div>
        <script src="../scripts.js?v=<?= time() ?>"></script>
        <script src="../modales.js?v=<?= time() ?>"></script>
    </body>
</html>