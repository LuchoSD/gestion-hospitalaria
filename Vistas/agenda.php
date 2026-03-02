<?php
session_start();
require_once '../config/db.php';

//filtros
$inactividad = 1200;

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !=2) {
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
//calendario
$id_medico = $_SESSION['user_id'];
$ano = date('Y');
$mes = date('m');
$mes_actual = date('F Y');
$hoy = date('j');

$primer_dia_mes = mktime(0, 0, 0, $mes, 1, $ano);
$dias_del_mes = date('t', $primer_dia_mes);
$dia_semana_inicio = date('N', $primer_dia_mes);
//busqueda
$mes_busqueda = date('Y-m');
$stmt_agenda = $pdo->prepare("SELECT DAY(fecha_cita) as dia, p.nombre, p.apellidos, TIME(fecha_cita) as hora
                                FROM citas c
                                JOIN pacientes p ON c.id_paciente = p.id
                                WHERE c.id_medico = ? AND fecha_cita LIKE ?
                                ORDER BY c.fecha_cita ASC");
$stmt_agenda->execute([$id_medico, $mes_busqueda . '%']);
$citas_mes = $stmt_agenda->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

$stmt_hoy = $pdo->prepare("SELECT c.id AS cita_id, p.id AS pac_id, p.nombre, p.apellidos, p.dni 
                           FROM citas c JOIN pacientes p ON c.id_paciente = p.id 
                           WHERE c.id_medico = ? AND c.fecha_cita = CURDATE()");
$stmt_hoy->execute([$id_medico]);
$mis_citas_hoy = $stmt_hoy->fetchAll();
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
    <!--calendario-->
    <div class= "min-h-screen w-screen bg-[oklch(98.4%_0.019_200.873)] flex flex-col">
        <?php include '../components/headerM.php'; ?>
        <div class="flex flex-1 overflow-hidden">
            <?php include '../components/sidebarM.php'; ?>
            <main class="flex-1 p-8 overflow-y-auto">
                <h2 class="text-2xl font-bold text-blue-900 mb-6">Mi Agenda - <?php echo $mes_actual; ?></h2>
                <div class="bg-white rounded-3xl shadow-md border border-gray-200 overflow-hidden">
                    <div class="grid grid-cols-7 bg-gray-50 p-3 text-center text-xs font-bold uppercase text-gray-500 tracking-wider">
                        <div>Lun</div><div>Mar</div><div>Mie</div><div>Jue</div><div>Vie</div><div>Sab</div><div>Dom</div>
                    </div>
                    <div class="grid grid-cols-7 gap-2 p-3 bg-white">
                        <?php for($i=1; $i<$dia_semana_inicio; $i++){
                            echo '<div class="min-h-[6rem] border border-transparent bg-gray-50/30 rounded-xl"></div>';
                        }
                        for($dia=1; $dia <= $dias_del_mes; $dia++){
                            $clase = 'min-h-[6rem] border rounded-xl p-2 relative transition cursor-pointer';
                            $tiene_citas = isset($citas_mes[$dia]);

                            if ($dia == $hoy){
                                $clase .= ' bg-blue-600 border-blue-700 text-white shadow-md z-10';
                            }else{
                                $clase .= $tiene_citas ? ' bg-indigo-50 border-indigo-200 hover:bg-indigo-100' : 'bg-white border-gray-100 hover:border-blue-200';
                            }
                            $dato_citas = $tiene_citas ? htmlspecialchars(json_encode($citas_mes[$dia], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)) : '[]';

                            echo "<div class=\"$clase\" onclick='verCitasDia($dia, $dato_citas)'>";
                            echo "<span class=\"text-sm font-bold\">$dia</span>";
                            //citas
                            if($tiene_citas){
                                $num = count($citas_mes[$dia]);
                                $color_punto = ($dia == $hoy) ? 'bg-white' : 'bg-indigo-500';
                                echo "<div class=\"absolute bottom-2 right-2 $color_punto w-5 h-5 rounded-full text-[10px] flex items-center justify-center " . (($dia == $hoy) ? 'text-blue-600' : 'text-white') . "\">$num</div>";
                            }
                            echo '</div>';
                        }
                        $total_casillas = $dia_semana_inicio - 1 + $dias_del_mes;
                        $vacio_final = (7 - ($total_casillas % 7)) % 7;
                        for ($i = 0; $i < $vacio_final;$i++){
                            echo '<div class="min-h-[6rem] border border-transparent rounded-xl bg-gray-50/30>"</div>';
                        } 
                        ?>
                    </div>
                </div>
            </main>
        </div>
        <!--crear recetas-->
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
                            <?php foreach($mis_citas_hoy as $ci): ?>
                                <option value="<?php echo $ci['pac_id']; ?>">
                                    <?php echo htmlspecialchars($ci['apellidos'] . ", " . $ci['nombre'] . " (" . $ci['dni'] . ")"); ?>
                                </option>
                            <?php endforeach;?>
                        </select>         
                    </div>
                    <!--historial clinico-->
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
    <script>
        //funcion ocultar menu
    function toggleMenu(){
        const menu = document.getElementById('perfilMenu');
        menu.classList.toggle('hidden');
    }

    //funion de ver citas
    function verCitasDia(dia, citas){
        if (citas.length === 0){
            alert("no hay citas para el dia " + dia);
            return;
        }
        let mensaje = "Citas para el dia " + dia + ":\n\n";
        citas.forEach(c => {
            let horaCorta = c.hora.substring(0, 5);
            mensaje += horaCorta + "h: " + c.nombre + " " + c.apellidos + "\n";
        });
        alert(mensaje);
    }
    </script>
    <script src="../scripts.js?v=<?= time() ?>"></script>
    <script src="../modales.js?v=<?= time() ?>"></script>
</body>
</html>
