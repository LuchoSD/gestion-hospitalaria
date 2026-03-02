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
$paciente_id = $_GET['id'] ?? 0;

try {
    //verificacion de que el pacietne ha tenido cita con este medico
    $check = $pdo->prepare("SELECT p.* FROM pacientes p 
                            INNER JOIN citas c ON p.id = c.id_paciente 
                            WHERE p.id = ? AND c.id_medico = ?");
    $check->execute([$paciente_id, $medico_id]);
    $paciente = $check->fetch(PDO::FETCH_ASSOC);

    if (!$paciente) {
        echo "<p class='p-8 text-center text-gray-400 italic'>No tienes acceso a este historial.</p>";
        exit;
    }
    $stmt_citas = $pdo->prepare("SELECT fecha_cita, hora_cita, motivo, estado 
                                FROM citas 
                                WHERE id_paciente = ? AND id_medico = ? 
                                ORDER BY fecha_cita DESC");
    $stmt_citas->execute([$paciente_id, $medico_id]);
    $citas = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);


    $stmt_recetas = $pdo->prepare("SELECT contenido, fecha_creacion 
                                    FROM recetas 
                                    WHERE id_paciente = ? AND id_medico = ? 
                                    ORDER BY fecha_creacion DESC");
    $stmt_recetas->execute([$paciente_id, $medico_id]);
    $recetas = $stmt_recetas->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="space-y-8">
        <div class="flex justify-between items-start border-b border-gray-100 pb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-900"><?= htmlspecialchars($paciente['nombre'] . " " . $paciente['apellidos']) ?></h2>
                <p class="text-sm text-slate-500 font-medium">DNI: <?= htmlspecialchars($paciente['dni']) ?> | Tel: <?= htmlspecialchars($paciente['telefono']) ?></p>
            </div>
        </div>
        <!--citgas-->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <h4 class="text-xs font-black uppercase text-indigo-600 tracking-widest flex items-center gap-2"><span class="w-2 h-2 bg-indigo-600 rounded-full"></span> Historial de Visitas</h4>
                <div class="space-y-3">
                    <?php if ($citas): foreach ($citas as $c): ?>
                        <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm">
                            <div class="flex justify-between mb-2">
                                <span class="text-xs font-bold text-slate-400"><?= date("d/m/Y", strtotime($c['fecha_cita'])) ?></span>
                                <span class="text-[10px] font-black uppercase <?= $c['estado'] == 'pendiente' ? 'text-amber-500' : 'text-green-500' ?>"><?= $c['estado'] ?></span>
                            </div>
                            <p class="text-sm text-slate-700 leading-relaxed"><?= htmlspecialchars($c['motivo'] ?: 'Sin observaciones') ?></p>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="text-sm text-slate-400 italic">No hay visitas anteriores registradas.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-4">
                <h4 class="text-xs font-black uppercase text-emerald-600 tracking-widest flex items-center gap-2">
                    <span class="w-2 h-2 bg-emerald-600 rounded-full"></span> Recetas
                </h4>
                <!--recetas-->
                <div class="space-y-3">
                    <?php if ($recetas): foreach ($recetas as $r): ?>
                        <div class="bg-emerald-50/50 border border-emerald-100 p-4 rounded-2xl">
                            <span class="text-[10px] font-bold text-emerald-700 block mb-2 uppercase"><?= date("d M Y", strtotime($r['fecha_creacion'])) ?></span>
                            <p class="text-sm text-slate-600 italic">"<?= htmlspecialchars($r['contenido']) ?>"</p>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="text-sm text-slate-400 italic">No se han emitido recetas aun.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
} catch (PDOException $e) {
    echo "<p class='p-4 text-red-500'>Error de base de datos.</p>";
}