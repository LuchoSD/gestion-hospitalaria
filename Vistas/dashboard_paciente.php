<?php 
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['rol'] != 4){
    header("Location: ../login.php?error=no_autorizado");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt_id = $pdo->prepare("SELECT id FROM pacientes WHERE id_usuario = ?");
$stmt_id->execute([$user_id]);
$paciente_h = $stmt_id->fetch();
if(!$paciente_h){
    die("Error: no se encontro tu ficha. Contacta a recepcion.");
}
$paciente_id = $paciente_h['id'];

//proxima cita
$sql_proxima = "SELECT c.*, pe.nombre as medico_nom, pe.apellidos as medico_ape
                FROM citas c
                JOIN perfiles_empleados pe
                ON c.id_medico = pe.id_usuario
                WHERE c.id_paciente = ? AND c.fecha_cita >= CURDATE()
                AND c.estado = 'pendiente'
                ORDER BY c.fecha_cita ASC, c.hora_cita ASC LIMIT 1";
$stmt_p = $pdo->prepare($sql_proxima);
$stmt_p->execute([$paciente_id]);
$proxima_cita = $stmt_p->fetch();

//historial
$sql_historial = "SELECT c.*, pe.nombre as medico_nom
                    FROM citas c
                    JOIN perfiles_empleados pe ON c.id_medico = pe.id_usuario
                    WHERE c.id_paciente = ? AND (c.fecha_cita < CURDATE() OR c.estado = 'completada')
                    ORDER BY c.fecha_cita DESC";
$stmt_h = $pdo->prepare($sql_historial);
$stmt_h->execute([$paciente_id]);
$historial = $stmt_h->fetchAll();

//recetas
$sql_recetas = "SELECT r.*, pe.nombre as medico_nom
                FROM recetas r
                JOIN perfiles_empleados pe
                ON r.id_medico = pe.id_usuario
                WHERE r.id_paciente = ?
                ORDER BY r.fecha_creacion DESC";
$stmt_r = $pdo->prepare($sql_recetas);
$stmt_r->execute([$paciente_id]);
$recetas = $stmt_r->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>Mi Portal - Global Care</title>
</head>
<body class="bg-slate-50 min-h-screen">
    <header class="w-full bg-white shadow z-10">
                <!--logo y texto central-->
                <div class="flex items-center justify-between p-4 w-full">
                    <img src="../images/logo.png" alt="Global Care Digital" class="h-20 w-auto">
                    <h1 class="text-3xl font-semibold text-blue-900 uppercase tracking-tight">Portal Paciente</h1>
                    <!--perfil-->
                    <div class="flex items-center gap-4 relative">
                        <span class="text-l text-gray-600 font-medium"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                        <button onclick="toggleMenu()" id="btnPerfil" class="bg-slate-100 p-2 w-12 h-12 flex items-center justify-center rounded-full hover:bg-slate-200 transition-all shadow-sm cursor-pointer focus:outline-none">
                            <svg width="35" height="35" class="fill-current text-blue-900">
                                <use xlink:href="../assets/sprite.svg#icon-user"></use>
                            </svg>
                        </button>
                        <div id="perfilMenu" class="hidden absolute right-0 top-14 w-40 bg-white border border-gray-200 shadow-xl rounded-lg py-2 z-50">
                            <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-semibold">Cerrar Sesion</a>
                        </div>
                    </div>
                    <script>
                    function toggleMenu() {
                        const menu = document.getElementById('perfilMenu');
                        menu.classList.toggle('hidden');
                    }

                    // Cerrar menú al clickear fuera
                    document.addEventListener('click', function(event) {
                        const menu = document.getElementById('perfilMenu');
                        const btn = document.getElementById('btnPerfil');
                        if (!btn.contains(event.target) && !menu.contains(event.target)) {
                            menu.classList.add('hidden');
                        }
                    });
                </script>
            </header> 
    <main class="max-w-5xl mx-auto p-8 space-y-8 text-left">
        <section class="bg-blue-900 rounded-[2rem] p-10 text-white shadow-xl relative overflow-hidden">
            <div class="relative z-10">
                <h1 class="text-3xl font-black uppercase">Bienvenido!</h1>
            </div>
            <div class="absolute right-[-20px] top-[-20px] opacity-10">
                <svg width="200" height="200" fill="currentColor"><use href="../assets/sprite.svg#icon-user"></use></svg>
            </div>
        </section>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-6">
                <!--proxima cita-->
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <h2 class="text-[10px] font-black uppercase text-gray-400 mb-4 tracking-widest">Proxima Cita</h2>
                    <?php if($proxima_cita): ?>
                        <div class="bg-blue-50 p-4 rounded-2xl mb-4 text-center">
                            <div class="text-blue-600 font-black text-2xl"><?= date('d/m/Y', strtotime($proxima_cita['fecha_cita'])) ?></div>
                            <div class="text-gray-400 font-bold text-sm"><?= date('H:i', strtotime($proxima_cita['hora_cita'])) ?> hs</div>
                        </div>
                        <p class="text-xs text-gray-700 italic">Con Dr/a: <?= htmlspecialchars($proxima_cita['medico_nom']) ?></p>
                    <?php else: ?>
                        <p class="text-sm text-gray-400 italic">No tienes citas programadas.</p>
                    <?php endif; ?>
                </div>
                <!--necesidad de cita-->
                <div class="bg-indigo-600 p-8 rounded-[2rem] text-white shadow-lg shadow-indigo-100">
                    <h2 class="text-[10px] font-black uppercase text-indigo-200 mb-4 tracking-widest">Necesitas una cita?</h2>
                    <p class="text-sm text-indigo-900 mb-6 leading-relaxed">Contacta con recepcion para agendar tu proximo turno:</p>
                    <a href="tel:+349000000" class="block w-full bg-white text-indigo-600 text-center py-3 rounded-xl font-black text-sm hover:bg-indigo-50 transition-all">+34 900 00 00</a>
                </div>
            </div>
            <!--recetas-->
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                    <h2 class="text-[10px] font-black uppercase text-gray-400 mb-6 tracking-widest">Mis Recetas </h2>
                    <div class="space-y-4">
                        <?php if($recetas): foreach($recetas as $r): ?>
                            <div class="group border-2 border-slate-50 p-5 rounded-3xl hover:border-emerald-100 hover:bg-emerald-50/30 transition">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-[10px] font-black text-emerald-600 uppercase"><?= date('d M, Y', strtotime($r['fecha_creacion'])) ?></span>
                                    <span class="text-xs font-bold text-gray-400 italic">Dr/a: <?= htmlspecialchars($r['medico_nom']) ?></span>
                                </div>
                                <p class="text-gray-700 italic text-sm leading-relaxed"><?= nl2br(htmlspecialchars($r['contenido'])) ?></p>
                            </div>
                        <?php endforeach; else: ?>
                            <p class="text-center py-6 text-gray-400 text-sm italic">No tienes nada en tu historial aun.</p>
                        <?php endif;?>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                    <h2 class="text-[10px] font-black uppercase text-gray-400 mb-6 tracking-widest">Historial de Viistas</h2>
                    <div class="space-y-3">
                        <?php if($historial): foreach($historial as $h): ?>
                            <div class="flex items-center justify-between p-4 bg-slate-50/50 rounded-2xl border border-transparent hover:border-blue-100 transition-all">
                                <div>
                                    <p class="text-[10px] font-black text-blue-900 uppercase mb-1"><?= date('d/m/Y', strtotime($h['fecha_cita'])) ?></p>
                                    <p class="text-sm font-bold text-gray-700 italic">"<?= htmlspecialchars($h['motivo']) ?>"</p>
                                </div>
                                <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase border bg-white text-gray-400"><?= $h['estado'] ?></span>
                            </div>
                        <?php endforeach; else: ?>
                            <p class="text-center py-6 text-gray-400 text-sm italic">Tu historial está vacío.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="../scripts.js?v=<?= time() ?>"></script>
    <script src="../modales.js?v=<?= time() ?>"></script>
</body>
</html>