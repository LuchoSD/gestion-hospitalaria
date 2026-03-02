<?php
session_start();
require_once '../config/db.php';

//filtros
$inactividad = 1200;

if (!isset($_SESSION['user_id']) || (int)$_SESSION['rol'] !=2) {
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

$id_medico = $_SESSION['user_id'];
$hoy = date('Y-m-d');

$sql_citas = "SELECT c.id AS cita_id, c.hora_cita, c.motivo, c.estado, p.id AS pac_id, p.nombre, p.apellidos, p.dni, p.telefono
                FROM citas c
                JOIN pacientes p ON c.id_paciente = p.id
                WHERE c.id_medico = ? AND c.fecha_cita = ?
                ORDER BY c.hora_cita ASC";
$stmt = $pdo->prepare($sql_citas);
$stmt->execute([$id_medico, $hoy]);
$mis_citas = $stmt->fetchAll();

$total_citas_hoy = count($mis_citas);
$proxima_cita = null;
if(!empty($mis_citas)){
    foreach($mis_citas as $c){
        if($c['estado'] === 'pendiente'){
            $proxima_cita = $c;
            break;
        }
    }
}
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
        <?php include '../components/headerM.php'; ?>
        <div class="flex flex-1 overflow-hidden">
            <?php include '../components/sidebarM.php'; ?>
            <main class="flex-1 p-8 overflow-y-auto">
            <!--Cuadro Resumen diario-->
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                <div class="bg-white shadow-md rounded-3xl p-8 border-l-8 border-blue-900 relative overflow-hidden">
                    <div class="flex justify-between items-start z-10 relative">
                        <div>
                            <h2 class="text-gray-500 font-bold uppercase text-xs tracking-widest">Resumen del Día</h2>
                            <div class="mt-6">
                                <p class="text-gray-400 text-sm">Citas para hoy:</p>
                                <p class="text-5xl font-black text-blue-900"><?php echo $total_citas_hoy; ?></p>
                            </div>
                            <div class="mt-4">
                                <p class="text-gray-400 text-sm">Usuario:</p>
                                <p class="text-sm font-bold text-green-600 uppercase">Médico</p>
                            </div>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-2xl">
                            <svg width="45" height="45" class="fill-current text-blue-900">
                                <use href="../assets/sprite.svg?v=2#icon-user"></use>
                            </svg>
                        </div>
                    </div>
                </div>

                <!--informacion de citas-->
                <div class="bg-white shadow-md rounded-3xl p-8 border-l-8 border-indigo-600">
                    <div class="flex justify-between items-start">
                        <div class="w-full">
                            <!--citas dia-->
                            <h2 class="text-gray-500 font-bold uppercase text-xs tracking-widest">Agenda de Hoy</h2>
                            <p class="text-gray-600 mt-3 text-sm font-medium">Citas programadas:<span class="font-bold text-indigo-600 text-lg ml-1"><?php echo $total_citas_hoy; ?></span></p>
                            <!--proximo paciente-->
                            <div class="mt-6 p-5 bg-indigo-50/50 rounded-2xl border border-indigo-100">
                                <p class="text-[10px] text-indigo-400 font-black uppercase tracking-tighter">Proximo Paciente:</p>
                                <?php if(isset($proxima_cita) && $proxima_cita !== null): ?>
                                    <div class="flex items-center justify-between mt-1">
                                        <p class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($proxima_cita['nombre']); ?></p>
                                        <div class="flex items-center gap-2 text-indigo-600 bg-white px-3 py-1 rounded-full shadow-sm">
                                            <svg width="16" height="16" class="fill-current">
                                                <use href="../assets/sprite.svg?v=2#icon-clock"></use>
                                            </svg>
                                            <span class="font-bold text-sm">
                                                <?php echo date("H:i", strtotime($proxima_cita['hora_cita'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-gray-400 italic text-sm mt-1">No hay mas citas hoy</p>
                                <?php endif; ?>
                            </div>   
                        </div>
                    </div>
                </div>
            </section>

            <!--tabla de pacientes-->
            <div class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-100 mt-8">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-bold text-blue-900">Lista de Pacientes Recientes</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-gray-600 uppercase text-xs font-semibold">
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Paciente</th>
                                <th class="px-6 py-4">DNI</th>
                                <th class="px-6 py-4">Telefono</th>
                                <th class="px-6 py-4">Motivo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if ($total_citas_hoy > 0): ?>
                                <?php foreach ($mis_citas as $citas): ?>
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-6 py-4 text-sm text-gray-500">#<?php echo $citas['pac_id']; ?></td>
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            <?php echo htmlspecialchars($citas['nombre']. " " . $citas['apellidos']);?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars ($citas['dni']);?></td>
                                        <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars ($citas['telefono']);?></td>
                                        <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars ($citas['motivo']);?></td>
                                    </tr>
                                <?php endforeach;?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-400"> No hay pacientes hoy.</td>
                                </tr>
                            <?php endif; ?>    
                        </tbody>
                    </table>
                </div>
            </div>   
        </main>
    </div>
    <div id="creadorRecetas" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm justify-center items-center z-50"> 
        <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-blue-900 p-6 flex justify-between items-center text-white">
                <h3 class="text-xl font-bold">Gestion de Recetas</h3>
                <button onclick="cerrarPanel()" class="text-white/80 hover:text-white text-2xl">
                    <div class="p-3 rounded-2xl text-black">
                        <svg width="28" height="28" class="fill-current hover:text-red-600 transition-colors">
                            <use href="../assets/sprite.svg?v=2#icon-cerrar"></use>
                        </svg>
                    </div>
                </button>
            </div>
            <!--recetas-->
            <div class="p-8">
                <form action="../api/procesar_recetas.php" method="POST">
                    <div class="mb-6">
                        <label class="block txt-xs font-black uppercase text-gray-400 mb-2">Seleccionar Paciente</label>
                        <select name="id_paciente" required class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-blue-500 outline-none transition">
                            <option value="">-- Buscar en la lista de hoy --</option>
                            <?php foreach($mis_citas as $ci): ?>
                                <option value="<?php echo $ci['pac_id']; ?>">
                                    <?php echo htmlspecialchars($ci['apellidos'] . ", " . $ci['nombre'] . " (" . $ci['dni'] . ")"); ?>
                                </option>
                            <?php endforeach;?>
                        </select>         
                    </div>
                    <div id="historialPrevio" class="mb-6">
                        <label class="block text-xs font-black uppercase text-gray-400 mb-2">Recetas anteroires</label>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
                            <p class="text-sm text-gray-500 italic">Selecciona un paciente</p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-black uppercase text-gray-400 mb-2">Nueva Receta</label>
                        <textarea name="contenido"  required class="w-full border-2 border-slate-100 p-4 rounded-xl h-40 focus:border-blue-500 outline-none" placeholder="Introducir receta medidca..."></textarea>
                    </div>
                    <div class="flex gap-4">
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:shadow-lg transition">Enviar al paciente</button>
                    </div>  
                </form>  
            </div>
        </div>
    </div>
    <script src="../scripts.js?v=<?= time() ?>"></script>
    <script src="../modales.js?v=<?= time() ?>"></script>
</body>
</html>