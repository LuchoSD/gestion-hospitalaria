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

$busqueda = trim($_GET['q'] ?? '');

if ($busqueda){
    $sql = "SELECT p.id, p.nombre, p.apellidos, p.email, p.telefono, p.estado,
            (SELECT COUNT(*) FROM citas WHERE id_paciente = p.id) as total_citas
            FROM pacientes p
            WHERE p.nombre LIKE ? OR p.apellidos LIKE ? OR p.dni = ?
            ORDER BY p.nombre ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$busqueda%", "%$busqueda%", "%$busqueda%"]);
}else{
    $sql = "SELECT p.id, p.nombre, p.apellidos, p.email, p.telefono, p.estado,
            (SELECT COUNT(*) FROM citas WHERE id_paciente= p.id) as total_citas
            FROM pacientes p
            ORDER BY p.id DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
$pacientes = $stmt->fetchAll();
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
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h1 class="text-3xl font-black text-blue-900 uppercase">Pacientes</h1>
                            <p class="text-gray-500">Datos de pacientes</p>
                        </div>
                        <button onclick="abrirModalPacientes()" class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold hover:bg-blue-700 shadow-lg transition-all flex items-center gap-2">Nuevo Paciente</button>
                    </div>
                    <div class="mb-6">
                        <!--formulario-->
                        <form action="" method="GET" class="flex gap-2">
                            <input type="text" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Nombre, apellido, DNI..." class="flex-1 border-2 border-white bg-white/50 px-4 py-3 rounded-2xl outline-none focus:border-blue-500 transition-all">
                            <button type="submit" class="bg-blue-900 px-6 py-3 rounded-2xl text-white font-bold hover:bg-blue-800 transition-colors">Buscar</button>
                        </form>
                    </div>
                    <div class="bg-white shadow-lg rounded-3xl overflow-hidden border border-gray-100">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-gray-600 uppercase text-[10px] font-black tracking-widest">
                                    <th class="px-6 py-4">Paciente</th>
                                    <th class="px-6 py-4">Contacto</th>
                                    <th class="px-6 py-4">Citas Totales</th>
                                    <th class="px-6 py-4">Estado</th>
                                    <th class="px-6 py-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach($pacientes as $pac): ?>
                                    <tr class="hover:bg-blue-50/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-gray-900"><?php echo htmlspecialchars($pac['nombre'] . " " . $pac['apellidos']); ?></p>
                                            <p class="text-[10px] text-blue-600 font-bold tracking-tighter">ID: <?php echo $pac['id']; ?></p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col text-sm">
                                                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($pac['telefono']); ?> </span>
                                                <span class="text-gray-400 text-xs"> <?php echo htmlspecialchars ($pac['email']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full font-bold text-xs">
                                                <?php echo $pac['total_citas']; ?> visitas
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php
                                                $est = strtolower($pac['estado']);
                                                $color = ($est == 'activo') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                                            ?>
                                            <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase <?php echo $color; ?>">
                                                <?php echo $est; ?> 
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <button onclick="cambioEstado(<?php echo $pac['id']; ?>, '<?php echo htmlspecialchars ($pac['nombre'], ENT_QUOTES); ?>', '<?php echo $est; ?>', 'pacientes')" class="text-blue-600 hover:text-blue-800 font-bold text-xs">Cambiar Estado</button>
                                        </td>
                                    </tr>
                                <?php endforeach;?>
                            </tbody>
                        </table>
                    </div>
                </main>
            </div>
        </div>
        <!--modal pacientes-->
        <div id="modalPacientes" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm justify-center items-center z-20 p-4">
            <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden">
                <div class="bg-blue-900 p-6 flex justify-between items-center text-white">
                    <h3 class="text-xl font-bold">Registrar Nuevo Paciente</h3>
                    <button onclick="cerrarModalPacientes()" class="hover:text-red-400 transition-colors">
                        <svg width="24" height="24" class="fill-current">
                            <use href="../assets/sprite.svg?v=2#icon-cerrar"></use>
                        </svg>
                    </button>
                </div>
                <form action="../api/crear_usuario.php" method="POST" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <input type="hidden" name="id_rol" value="4">
                    <div class="md:col-span-2 border-b pb-2 text-blue-900 font-bold text-xs uppercase tracking-widest">Informacion Personal</div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase text-left">Nombre</label>
                        <input type="text" name="nombre" required class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase text-left">Apellidos</label>
                        <input type="text" name="apellidos" required class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase text-left">DNI</label>
                        <input type="text" name="dni" required class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase text-left">Telefono</label>
                        <input type="text" name="telefono" required class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-blue-500 outline-none transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase text-left">Correo</label>
                        <input type="email" name="email" required class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-blue-500 outline-none transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase text-left">Contrasena Provisional</label>
                        <input type="text" name="password" required class="w-full border-2 border-slate-100 p-3 rounded-xl bg-slate-50 font-mono">
                    </div>
                    <div class="md:col-span-2 mt-4">
                        <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold hover:bg-blue-700 shadow-lg transition-all transform active:scale-95">Crar Paciente</button>
                    </div>
                </form>
            </div>
        </div>
        <!--modal cambio estado-->
        <div id="modalEstado" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm justify-center items-center z-[50] p-4">
            <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl p-8 text-center">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Confirmar cambio?</h3>
                <p class="text-sm text-gray-500 mb-6">Vas a cambiar el estado de <br><span id="emailEmpleadoModal" class="font-bold text-blue-900 italic"></span></p>
                <form action="../api/cambiar_estado.php" method="POST" class="flex gap-3">
                    <input type="hidden" name="id" id="idUsuarioModal">
                    <input type="hidden" name="nuevo_estado" id="nuevoEstadoModal">
                    <input type="hidden" name="tabla" id="tablaModal" value="pacientes">
                    <button type="button" onclick="cerrarModalEstado()" class="flex-1 px-4 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold hover:bg-slate-200 transition-all">Cancelar</button>
                    <button type="submit" class="flex-1 px-4 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg transition-all">Confirmar</button>
                </form>
            </div>
        </div>
        <!--modal procesar citas-->
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