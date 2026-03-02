<?php
session_start();
require_once '../config/db.php';


//filtros
$inactividad = 600;

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !=1) {
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

//contador 
$total_medicos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE id_rol = 2")->fetchColumn();
$total_recep = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE id_rol = 3")->fetchColumn();

if($busqueda){
    $sql = "SELECT u.id, u.email, u.estado, p.nombre, p.apellidos, r.nombre_rol, p.telefono, e.nombre_especialidad
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id
            LEFT JOIN perfiles_empleados p ON u.id = p.id_usuario
            LEFT JOIN especialidades e ON p.id_especialidad = e.id
            WHERE (p.nombre LIKE ? OR p.apellidos LIKE ? or e.nombre_especialidad LIKE ? OR u.email LIKE ?)
            AND u.id_rol IN (2, 3)
            ORDER BY p.nombre ASC";
    $stmt = $pdo->prepare($sql);
    $stmt ->execute(["%$busqueda%", "%$busqueda%", "%$busqueda%", "%$busqueda%"]);
}else{
    $sql = "SELECT u.id, u.email, u.estado, p.nombre, p.apellidos, r.nombre_rol, p.telefono, e.nombre_especialidad
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id
            LEFT JOIN perfiles_empleados p ON u.id = p.id_usuario
            LEFT JOIN especialidades e ON p.id_especialidad = e.id
            WHERE u.id_rol IN (2, 3)
            ORDER BY u.id DESC LIMIT 20";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
$empleados = $stmt->fetchAll();
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
        <!--header-->
        <div class= "min-h-screen w-screen bg-[oklch(98.4%_0.019_200.873)] flex flex-col">
            <?php include '../components/headerA.php'; ?>
            <div class="flex flex-1 overflow-hidden">
                <?php include '../components/sidebarA.php'; ?>
                <main class="flex-1 p-8 overflow-y-auto">                  
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h1 class="text-3xl font-black text-blue-900">Gestion de Personal</h2>
                            <p class="text-gray-500">Administrar cuenta empleados</p>
                        </div>
                        <button type="button" onclick="crearEmpleado()" class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold hover:bg-blue-700 shadow-lg transition-all flex items-center gap-2">Crear Empleado</button>
                    </div>    
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-blue-900">Gestion de Empleados</h2>
                        <form action="" method="GET" class="flex gap-2">
                            <input type="text" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Nombre, apellido, especialidad..." class="border border-gray-300 px-4 py-2 rounded-xl outline-none focus:border-blue-500">
                            <button type="submit" class="bg-blue-900 p-3 rounded-2xl text-white hover:bg-blue-700 transition-colors shadow-sm">Buscar</button>
                        </form>
                    </div>
                    <div class="bg-white shadow rounded-xl overflow-hidden">
                        <!--tabla-->
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500">Empleado</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500">Contacto</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500">Especialidad</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500">Estado</th>
                                    <th class="p-4 text-xs font-semibold uppercase text-gray-500">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($empleados as $emp): ?>
                                    <tr class="border-b hover:bg-slate-50 transition-colors">
                                        <td class="p-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars(($emp['nombre']) . " " . ($emp['apellidos'])); ?></span>
                                                <span class="text-xs text-gray-400"><?php echo htmlspecialchars($emp['email']); ?></span>
                                            </div>
                                        </td>
                                        <td class="p-4 text-gray-600 text-sm"><?php echo htmlspecialchars($emp['telefono']); ?></td>
                                        <td class="p-4">
                                            <div class="flex flex-col">
                                                <span class="text-[10px] font-black uppercase text-indigo-600"><?php echo $emp['nombre_rol']?></span>
                                                <span class="text-xs text-gray-500"><?php echo htmlspecialchars($emp['nombre_especialidad'] ?? 'Administracion');?></span>
                                            </div>
                                        </td>
                                        <td class="p-4">
                                            <?php
                                                $estado = strtolower($emp['estado'] ?? 'activo');
                                                $colorEstado = ($estado == 'activo') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                                            ?>
                                            <span class="px-3 py-1 rounded-fill text-[10px] font-black uppercase <?php echo $colorEstado; ?>">
                                                <?php echo htmlspecialchars($estado); ?>
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            <div class="flex flex-col">
                                                <button onclick="cambioEstado(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['email'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $estado; ?>')" class="group flex items-center gap-2 px-3 py-1 rounded-xl border-2 border-slate-100 hover:border-blue-500 hover:bg-blue-50 transition-all cursor-pointer">
                                                    <span class="text-[10px] font-black uppercase text-slate-500 group-hover:text-blue-700">Cambiar Estado</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </main>
            </div>
        <!--modal estado-->
        <div id="modalEstado" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm justify-center items-center z-[60] p-4">
            <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl p-8 text-center">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Confirmar cambio?</h3>
                <p class="text-sm text-gray-500 mb-6">Vas a cambiar el estado del usuario <br><span id="emailEmpleadoModal" class="font-bold text-blue-900 italic"></span></p>
                <form action="../api/cambiar_estado.php" method="POST" class="flex gap-3">
                    <input type="hidden" name="id" id="idUsuarioModal">
                    <input type="hidden" name="nuevo_estado" id="nuevoEstadoModal">
                    <input type="hidden" name="tabla" value="usuarios">
                    <button type="button" onclick="cerrarModalEstado()" class="flex-1 px-4 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold hover:bg-slate-200 transition-all">Cancelar</button>
                    <button type="submit" class="flex-1 px-4 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg transition-all">Confirmar</button>
                </form>
            </div>
        </div>
        <div id="nuevoEmpleado" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm justify-center items-center z-50 p-4">
            <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden">
                <div class="bg-blue-900 p-6 flex justify-between items-center text-white">
                    <h3 class="text-xl font-bold">Registrar nuevo empleado</h3>
                    <button onclick="cerrarModalEmpleado()" class="hover:text-red-400 text-2xl transition-colors">
                        <div class="p-3 rounded-2xl text-black">
                            <svg width="28" height="28" class="fill-current hover:text-red-600 transition-colors">
                                <use href="../assets/sprite.svg?v=2#icon-cerrar"></use>
                            </svg>
                    </div>
                    </button>
                </div>
                <!--formulario de empleadso-->
                <form action="../api/crear_usuario.php" method="POST" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2 border-b pb-2 text-blue-900 font-bold text-xs uppercase tracking-widest">Credenciales</div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">Correo</label>
                        <input type="email" name="email" required class="w-full border-2 border-slate-100 p-2.5 rounded-xl focus:border-blue-500 outline-none" placeholder="">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">Contraseña Temporal</label>
                        <input type="password" name="password" required class="w-full border-2 border-slate-100 p-2.5 rounded-xl focus:border-blue-500 outline-none" placeholder="">
                    </div>
                    <div class="md:col-span-2 border-b pb-2 mt-2 text-blue-900 font-bold text-xs uppercase tracking-widest">Perfil</div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">Nombre</label>
                        <input type="text" name="nombre" required class="w-full border-2 border-slate-100 p-2.5 rounded-xl focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">Apellidos</label>
                        <input type="text" name="apellidos" required class="w-full border-2 border-slate-100 p-2.5 rounded-xl focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">Telefono de Contacto</label>
                        <input type="tel" name="telefono" pattern="[0-9]{9,15}" title="Ingresa solo numeros" required class="w-full border-2 border-slate-100 p-2.5 rounded-xl focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">Rol</label>
                        <select id="rolSelect" name="id_rol" required onchange="toggleEspecialidad()" class="w-full border-2 border-slate-100 p-2.5 rounded-xl focus:border-blue-500 outline-none">
                            <option value="2">Médico</option>
                            <option value="3">Recepcionista</option>
                            <option value="1">Administrador</option>
                        </select>
                    </div>
                    <div id="campoEspecialidad">
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">Especialidad Médica</label>
                        <select name="id_especialidad" class="w-full border-2 border-slate-100 p-2.5 rounded-xl focus:border-blue-500 outline-none">
                            <?php
                                $esp = $pdo->query("SELECT * FROM especialidades");
                                while($e = $esp->fetch()){
                                    echo"<option value='{$e['id']}'>{$e['nombre_especialidad']}</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="md:col-span-2 mt-4">
                        <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-2xl font-bold hover:bg-blue-700 shadow-lg transition-all">
                            Finalizar alta Empleado
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script src="../scripts.js?v=<?= time() ?>"></script>
        <script src="../modales.js?v=<?= time() ?>"></script>
    </body>
</html>
