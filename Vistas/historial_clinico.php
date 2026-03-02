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

$medico_id = $_SESSION['user_id'];
$busqueda = trim($_GET['q'] ?? '');

$sql = "SELECT DISTINCT p.id, p.nombre, p.apellidos, p.dni, p.email
        FROM pacientes p
        INNER JOIN citas c ON p.id = c.id_paciente
        WHERE c.id_medico = :medico_id";
if($busqueda){
    $sql .= " AND (p.nombre LIKE :q OR p.apellidos LIKE :q OR p.dni LIKE :q)";
}
$sql .= " ORDER BY p.apellidos ASC";

$stmt = $pdo->prepare($sql);
$params = ['medico_id' => $medico_id];
if($busqueda) $params['q'] = "%$busqueda%";

$stmt->execute($params);
$pacientes_asignados = $stmt->fetchAll();

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
            <main class="flex-1 p-8">
                <form action="" method="GET" class="mb-8 max-w-md flex gap-2">
                    <input type="text" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar por nombreo DNI..." class="flex-1 bg-white border border-gray-200 p-3 rounded-2xl outline-none focus:border-blue-500 shadow-sm transition-all">
                    <button type="submit" class="bg-blue-900 text-white px-6 rounded-2xl font-bold">Buscar</button>
                </form>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if($pacientes_asignados): ?>
                        <?php foreach($pacientes_asignados as $pac): ?>
                            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition-all">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 font-black">
                                        <?= strtoupper(substr($pac['nombre'], 0, 1) . substr($pac['apellidos'], 0, 1)) ?>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900"><?= htmlspecialchars($pac['nombre'] . " " . $pac['apellidos']) ?></h3>
                                    <p class="text-xs text-gray-400 uppercase font-bold tracking-tighter">DNI: <?= htmlspecialchars($pac['dni']) ?></p>
                                </div>
                                <button onclick="verFichaCompleta(<?= $pac['id'] ?>)" class="w-full bg-slate-900 text-white py-3 rounded-xl font-bold text-sm hover:bg-blue-600 transition-colors">Ver Historial Clinico</button>
                            </div>
                        <?php endforeach;?>
                    <?php else: ?>
                        <p class="col-span-full text-gray-400 italic text-center py-20 bg-white/50 rounded-[3rem] border-2 border-dashed border-gray-200">No hay pacientes en el historial.</p>
                    <?php endif; ?>
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
            <div class="p-8">
                <div class="mb-6">
                    <label class="block text-xs font-black uppercase text-gray-400 mb-2">Seleccionar Paciente</label>
                    <select id="seleccionPacienteReceta" class="w-full border-2 border-slate-100 p-3 rounded-xl focus:border-blue-500 outline-none transition">
                        <option value="">-- Buscar en la lista --</option>
                        <?php foreach($pacientes_asignados as $p){
                            echo "<option value='{$p['id']}'>{$p['apellidos']}, {$p['nombre']} ({$p['dni']})</option>";
                            }?>
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
                    <textarea id="textoReceta" class="w-full border-2 border-slate-100 p-4 rounded-xl h-40 focus:border-blue-500 outline-none" placeholder="Introducir receta medidca..."></textarea>
                </div>
                <div class="flex gap-4">
                    <button onclick="accionReceta('imprimir')" class="flex-1 bg-slate-800 text-white py-3 rounded-xl font-bold hover:shadow-lg transition">Imprimir Receta</button>
                    <button onclick="accionReceta('enviar')" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:shadow-lg transition">Enviar al correo</button>
                </div>    
            </div>
        </div>
    </div>
    <!--modal Histroial-->
    <div id="modalHistorial" class="hidden fixed inset-0 bg-slate-900/70 backdrop-blur-md justify-center items-center z-[50] p-4">
        <div class="bg-white w-full max-w-4xl max-h-[90vh] rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col">
            <div class="bg-slate-900 p-6 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-xl font-bold">Expediente Clinico</h3>
                </div>
                <button onclick="cerrarModalHistorial()" class="bg-slate-800 p-2 rounded-xl hover:bg-red-500 transition-all cursor-pointer">
                    <svg width="24" height="24" class="fill-current">
                        <use href="../assets/sprite.svg?v=2#icon-cerrar"></use>
                    </svg>
                </button>
            </div>
            <div id="contenidoHistorial" class="flex-1 overflow-y-auto p-8 bg-slate-50/50"></div>
        </div>
    </div>
    <script src="../scripts.js?v=<?= time() ?>"></script>
    <script src="../modales.js?v=<?= time() ?>"></script>
</body>
</html>