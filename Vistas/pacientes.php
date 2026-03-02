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

$busqueda = $_GET['q'] ?? '';

if($busqueda){
    $sql = "SELECT * FROM pacientes WHERE nombre LIKE ? OR apellidos LIKE ? OR dni LIKE ? ORDER BY apellidos ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$busqueda%", "%$busqueda%", "%$busqueda"]);
}else{
    //filtrare ultimos 20 pacientes por defecto
    $stmt = $pdo->query("SELECT * FROM pacientes ORDER BY id DESC LIMIT 20");
};

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

//Numero de citas
$hoy_inicio = date('Y-m-d 00:00:00');
$hoy_fin = date('Y-m-d 23:59:59');

$stmt_citas_hoy = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE id_medico = ? AND fecha_cita BETWEEN ?  AND ?");
$stmt_citas_hoy->execute([$_SESSION['user_id'], $hoy_inicio, $hoy_fin]);
$total_citas_hoy = $stmt_citas_hoy->fetchColumn();

//paciente siguiente
$ahora = date('Y-m-d H:i:s');
$stmt_proxima = $pdo->prepare("SELECT p.nombre, c.fecha_cita
                              FROM citas c
                              JOIN pacientes p ON c.id_paciente = p.id
                              WHERE c.id_medico = ? 
                              AND c.fecha_cita >= ?
                              AND c.estado = 'pendiente'
                              ORDER BY c.fecha_cita ASC LIMIT 1");
$stmt_proxima->execute([$_SESSION['user_id'], $ahora]);
$proxima_cita = $stmt_proxima->fetch();
$pacientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Gestor de Pacientes - Global Care</title>
    <script src="modales.js"></script>

</head>
<body>
    <div class= "min-h-screen w-screen bg-[oklch(98.4%_0.019_200.873)] flex flex-col">
        <?php include '../components/headerM.php'; ?> 
        <div class="flex flex-1 overflow-hidden">
            <?php include '../components/sidebarM.php'; ?>
            <main class="flex-1 p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-blue-900">Gestion de Pacientes</h2>
                    <form action="" method="GET" class="flex gap-2">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Nombre, apellido, DNI..." class="border border-gray-300 px-4 py-2 rounded-xl outline-none focus:border-blue-500">
                        <button type="submit" class="bg-blue-900 p-3 rounded-2xl text-white hover:bg-blue-700 transition-colors shadow-sm">Buscar</button>
                    </form>
                </div>
                <div class="bg-white shadow rounded-xl overflow-hidden">
                    <!--tabla-->
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-by">
                            <tr>
                                <th class="p-4 text-xs font-semibold uppercase text-gray-500">Paciente</th>
                                <th class="p-4 text-xs font-semibold uppercase text-gray-500">DNI</th>
                                <th class="p-4 text-xs font-semibold uppercase text-gray-500">Estado</th>
                                <th class="p-4 text-xs font-semibold uppercase text-gray-500">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pacientes as $p): ?>
                                <tr class="border-b hover:bg-slate-50">
                                    <td class="p-4 font-medium"><?php echo $p['nombre'] . " " . $p['apellidos']; ?></td>
                                    <td class="p-4 text-gray-600"><?php echo $p['dni']; ?></td>
                                    <td class="p-4">
                                        <span class="px-2 py-1 rounded text-xs <?php echo $p['estado'] == 'activo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                            <?php echo $p['estado']; ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <a href="historial_clinico.php?id=<?php echo $p['id']; ?>" class="text-blue-600 font-bold">Ficha</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
    </div>
    <!--creador recetas-->
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
                        <label class="block text-xs font-black uppercase text-gray-400 mb-2">Seleccionar Paciente</label>
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