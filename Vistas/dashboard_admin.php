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

//consulta de pacientes
$consulta = $pdo->query("SELECT COUNT(*) FROM pacientes");
$total_pacientes = $consulta->fetchColumn();

//listar pacientes
$sql_pacientes = "SELECT p.id, p.nombre, p.apellidos, p.dni, p.telefono, c.motivo FROM pacientes p LEFT JOIN citas c ON p.id = c.id_paciente GROUP BY p.id ORDER BY id DESC";
$stmt_pacientes = $pdo->query($sql_pacientes);
$lista_pacientes = $stmt_pacientes->fetchAll();

//pacientes activos
$stmt_activos = $pdo->query("SELECT COUNT(*) FROM pacientes WHERE estado = 'activo'");
$pacientes_activos = $stmt_activos->fetchColumn();

//contar personal (medicos y recpecionistas)
$total_medicos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE id_rol = 2")->fetchColumn();
$total_recep = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE id_rol=3")->fetchColumn();

//listar personal
$stmt_personal = $pdo->query("SELECT u.id, u.email, p.nombre, p.apellidos, r.nombre_rol, u.estado
                                    FROM usuarios u
                                    JOIN roles r ON u.id_rol = r.id
                                    LEFT JOIN perfiles_empleados p ON u.id = p.id_usuario
                                    WHERE u.id_rol IN (2, 3)
                                    ORDER BY u.id_rol DESC");
$lista_personal = $stmt_personal->fetchAll();
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
            <?php include '../components/headerA.php'; ?>
            <div class="flex flex-1 overflow-hidden">
                <?php include '../components/sidebarA.php'; ?>
                <main class="flex-1 p-8 overflow-y-auto">
                <!--Cuadro contador pacientes-->
                <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                    <div class="bg-white shadow-md rounded-3xl p-8 border-l-8 border-blue-900 relative overflow-hidden">
                        <div class="flex justify-between items-start z-10 relative">
                            <div>
                                <h2 class="text-gray-500 font-bold uppercase text-xs tracking-widest">Estadisticas de Pacientes</h2>
                                <div class="mt-6">
                                    <p class="text-gray-400 text-sm">Pacientes Activos:</p>
                                    <p class="text-4xl font-black text-blue-900"><?php echo $pacientes_activos; ?></p>
                                </div>
                                <div class="mt-4">
                                    <p class="text-gray-400 text-sm">Pacintes Registrados:</p>
                                    <p class="text-2xl font-bold text-blue-600"><?php echo $total_pacientes; ?></p>
                                </div>
                            </div>
                            <!--simbolo usuario-->
                            <div class="bg-blue-50 p-4 rounded-2xl">
                                <svg width="45" height="45" class="fill-current text-blue-900">
                                    <use href="../assets/sprite.svg?v=2#icon-user"></use>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <!--cuadro contador empleados-->
                    <div class="bg-white shadow-md rounded-3xl p-8 border-l-8 border-indigo-600">
                        <div class="flex justify-between items-start z-10 relative">
                            <div>
                                <h2 class="text-gray-500 font-bold uppercase text-xs tracking-widest">Personal Clinica</h2>
                                <!--medicos-->
                                <div class="mt-6">
                                    <p class="text-gray-400 text-sm">Médicos:</p>
                                    <p class="text-4xl font-black text-indigo-600"><?php echo $total_medicos; ?></p>
                                </div>
                                <!--recepcionistas-->
                                <div class="mt-6">
                                    <p class="text-gray-400 text-sm">Recepcionistas:</p>
                                    <p class="text-4xl font-black text-indigo-600"><?php echo $total_recep; ?></p>
                                </div>
                            </div>
                            <!--simbolo-->
                            <div class="bg-indigo-50 p-4 rounded-2xl">
                                <svg width="45" height="45" class="fill-current text-indigo-600">
                                    <use href="../assets/sprite.svg?v=2#icon-doctor"></use>
                                </svg>
                            </div>
                        </div>
                    </div>   
                </section>
                <!--tabla de empleados-->
                <div class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-100 mb-8">
                    <div class="p-6 border-b border-gray-100 bg-slate-50/50 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-blue-900">Equipo Trabajo</h2>
                        <span class="text-xs font-black uppercase text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">Médicos y Recepcionistas</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50 text-gray-600 uppercase text-[10px] font-black tracking-widest">
                                    <th class="px-6 py-4">Correo</th>
                                    <th class="px-6 py-4">Nombre Completp</th>
                                    <th class="px-6 py-4">Rol</th>
                                    <th class="px-6 py-4">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($lista_personal as $empleado): ?>
                                    <tr class="hover: bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($empleado['email']); ?></td>
                                        <td class="px-6 py-4 text-gray-600 italic">
                                            <?php echo htmlspecialchars(($empleado['nombre']) . " " . ($empleado ['apellidos'] ?? '')); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-lg text-[10px] font-bold uppercase <?php echo $empleado['nombre_rol'] == 'Medico' ?  'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700'; ?>">
                                                <?php echo htmlspecialchars($empleado['nombre_rol']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                            $estadoEmp = $empleado['estado'] ?? 'Activo';
                                            $claseEstado = ($estadoEmp == 'activo') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                                            ?>
                                            <span class="px-2 py-1 rounded text-xs font-bold uppercase <?php echo $claseEstado; ?>">
                                                <?php echo $estadoEmp; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>    
                            </tbody>
                        </table>    
                    </div>
                </div>
            </main>
        </div>
        <!--nuevo empleado-->
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
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">Contrasena Temporal</label>
                        <input type="password" name="password" required class="w-full border-2 border-slate-100 p-2.5 rounded-xl focus:border-blue-500 outline-none" placeholder="">
                    </div>
                    <div class="md:col-span-2 border-b pb-2 mt-2 text-blue-900 font-bold-text-xs uppercase tracking-widest">Perfil</div>
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
                        <input type="tel" name="telefono" pattern="[0-9]{9-15}" title="Ingresa solo numeros" required class="w-full border-2 border-slate-100 p-2.5 rounded-xl focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">Rol</label>
                        <select id="rolSelect" name="id_rol" onchange="toggleEspecialidad()" class="w-full border-2 border-slate-100 p-2.5 rounded-xl focus:border-blue-500 outline-none">
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