<?php
// INICIALIZAMOS EL BUFFER
ob_start();

// INICIALIZACION CENTRAL
require_once __DIR__ . '/includes/inicializar.php';

// EVITAR QUE EL NAVEGADOR MUESTRE PAGINAS DESDE CACHE
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// COMPROBAR LOGEO
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: index.php', true, 303);
    exit;
}

// MOSTRAR ERRORES EN DESARROLLO
ini_set('display_errors', 1);
error_reporting(E_ALL);

// PERMITIR SOLO USUARIOS CON ROL
require_role_view(['ADMIN', 'DIRECTOR', 'DOCENTE']);
$role = current_role();

// AUTOCOMPLETAR PARA DOCENTE O DIRECTOR
$docente_fullname = $docente_fullname ?? '';
$escuela_id = $escuela_id ?? 0;
$form_id = $form_id ?? 0;

// ENVIAR JSON Y LIMPIAMOS EL BUFFER
function send_json_and_exit($data)
{
    // LIMPIAR BUFFER
    while (ob_get_level()) ob_end_clean();

    ini_set('display_errors', 0);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// AJAX ENDPOINTS
if (isset($_GET['endpoint'])) {
    $escuela = (int)($_GET['escuela'] ?? 0);
    $formacion = (int)($_GET['formacion'] ?? 0);

    // ENDPOINT DE FORMACION PROFESIONAL
    if ($_GET['endpoint'] === 'formaciones') {
        $out = [];
        if ($escuela) {
            $q = mysqli_query(
                $conexion,
                "SELECT DISTINCT
                     f.formaciones_profesionales_id AS id,
                     f.formaciones_profesionales_nombre AS nombre
                 FROM formaciones_profesionales f
                 JOIN institucional i
                   ON i.formaciones_profesionales_id = f.formaciones_profesionales_id
                 WHERE i.escuelas_id = $escuela
                   AND f.formaciones_profesionales_eliminado = 0
                 ORDER BY f.formaciones_profesionales_nombre"
            );
            while ($r = mysqli_fetch_assoc($q)) $out[] = $r;
        }
        send_json_and_exit($out);
    }

    // ENDPOINT DE DOCENTE
    if ($_GET['endpoint'] === 'docente') {
        $doc = [];
        if ($escuela && $formacion) {
            $q = mysqli_query(
                $conexion,
                "SELECT p.personas_nombre, p.personas_apellido
                 FROM institucional i
                 JOIN personas p
                   ON p.personas_id = i.personas_id
                 WHERE i.escuelas_id = $escuela
                   AND i.formaciones_profesionales_id = $formacion
                 LIMIT 1"
            );
            $doc = mysqli_fetch_assoc($q) ?: [];
        }
        send_json_and_exit($doc);
    }

    // ENPOINT DE AÑOS
    if ($_GET['endpoint'] === 'anios') {
        $out = [];
        if ($escuela && $formacion) {
            $q = mysqli_query(
                $conexion,
                "SELECT DISTINCT r.registros_anio AS anio
                 FROM registros r
                 JOIN institucional i ON i.institucional_id = r.institucional_id
                 WHERE i.escuelas_id = $escuela
                   AND i.formaciones_profesionales_id = $formacion
                 ORDER BY r.registros_anio DESC"
            );
            while ($row = mysqli_fetch_assoc($q)) $out[] = (int)$row['anio'];
        }
        send_json_and_exit($out);
    }

    // ACTUALIZAR ESTADO SOLO ADMINISTRADOR Y DOCENTE
    if ($_GET['endpoint'] === 'update_estado') {
        require_role_action(['ADMIN', 'DOCENTE']);

        $pid = (int)($_GET['personas_id'] ?? 0);
        $estado = in_array($_GET['estado'] ?? '', ['CURSANDO', 'PROMOCIONO', 'ABANDONO'])
            ? $_GET['estado'] : null;
        if ($pid && $estado) {
            $hoy = date('Y-m-d');
            $ok = mysqli_query(
                $conexion,
                "UPDATE inscripciones_alumnos
                   SET estado = '$estado',
                       fecha_estado = '$hoy'
                 WHERE personas_id = $pid"
            );
            send_json_and_exit(['ok' => (bool)$ok, 'error' => $ok ? '' : mysqli_error($conexion)]);
        } else {
            send_json_and_exit(['ok' => false, 'error' => 'Parámetros inválidos']);
        }
    }

    if ($_GET['endpoint'] === 'check_month') {
        header('Content-Type: application/json');
        $escuela = (int)($_GET['escuela'] ?? 0);
        $formacion = (int)($_GET['formacion'] ?? 0);
        $anio = (int)($_GET['anio'] ?? 0);
        $mes = (int)($_GET['mes'] ?? 0);

        $exists = false;
        if ($escuela && $formacion && $anio && $mes) {

            // OBTENER EL ID INSTITUCIONAL
            $inst_id = 0;
            $r = mysqli_query(
                $conexion,
                "SELECT institucional_id
               FROM institucional
              WHERE escuelas_id = {$escuela}
                AND formaciones_profesionales_id = {$formacion}
              LIMIT 1"
            );
            if ($r && mysqli_num_rows($r)) $inst_id = (int) mysqli_fetch_assoc($r)['institucional_id'];

            if ($inst_id) {
                $q = mysqli_query(
                    $conexion,
                    "SELECT 1 FROM registros
                 WHERE institucional_id = {$inst_id}
                   AND registros_anio = {$anio}
                   AND registros_mes = {$mes}
                 LIMIT 1"
                );
                $exists = ($q && mysqli_num_rows($q) > 0);
            }
        }

        echo json_encode(['exists' => (bool)$exists]);
        exit;
    }

    send_json_and_exit(['ok' => false, 'error' => 'endpoint desconocido']);
}

// FILTROS Y PESTAÑAS
$escuela_id = array_key_exists('escuela', $_REQUEST) && $_REQUEST['escuela'] !== '' ? (int)$_REQUEST['escuela'] : null;
$form_id = array_key_exists('formacion', $_REQUEST) && $_REQUEST['formacion'] !== '' ? (int)$_REQUEST['formacion'] : null;
$anio = isset($_REQUEST['anio']) && $_REQUEST['anio'] !== '' ? (int)$_REQUEST['anio'] : 0;
$tab = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : 'resumen';

$saved = isset($_GET['saved']) ? (int)$_GET['saved'] : 0;
$saved_mode = $_GET['mode'] ?? '';
$calc_error = 0;
$calc_msg = '';

if (isset($_GET['calc_error'])) {
    $calc_error = (int)$_GET['calc_error'];
    $calc_msg = isset($_GET['calc_msg']) ? rawurldecode($_GET['calc_msg']) : '';
}

// INICIALIZAMOS
$docente_fullname = $docente_fullname ?? '';

// DOCENTE O DIRECTOR
if ((is_docente() || is_director()) && empty($escuela_id)) {

    // OBTENER PERSONA POR ID
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $personas_id = (int)($_SESSION['personas_id'] ?? 0);

    if (!$personas_id && $user_id) {
        $qr = mysqli_query($conexion, "SELECT personas_id FROM usuarios WHERE usuarios_id = {$user_id} LIMIT 1");
        if ($qr && mysqli_num_rows($qr)) {
            $personas_id = (int) mysqli_fetch_assoc($qr)['personas_id'];
        }
    }

    // CARGAMOS LOS DATOS
    if ($personas_id) {
        if (is_docente()) {
            $q = mysqli_query($conexion, "SELECT personas_nombre, personas_apellido FROM personas WHERE personas_id = {$personas_id} LIMIT 1");
            if ($q && mysqli_num_rows($q)) {
                $r = mysqli_fetch_assoc($q);
                $docente_fullname = trim(($r['personas_nombre'] ?? '') . ' ' . ($r['personas_apellido'] ?? ''));
            }
        }

        // BUSCAR INSTITUCIONAL
        $q2 = mysqli_query(
            $conexion,
            "SELECT escuelas_id, formaciones_profesionales_id
               FROM institucional
              WHERE personas_id = {$personas_id}
              ORDER BY institucional_id ASC
              LIMIT 1"
        );
        if ($q2 && mysqli_num_rows($q2)) {
            $inst = mysqli_fetch_assoc($q2);
            $escuela_id = (int)($inst['escuelas_id'] ?? 0);
            $form_id = isset($inst['formaciones_profesionales_id']) ? (int)$inst['formaciones_profesionales_id'] : null;
        }

        if (!empty($escuela_id) && empty($form_id)) {
            $q3 = mysqli_query(
                $conexion,
                "SELECT DISTINCT formaciones_profesionales_id
                   FROM institucional
                  WHERE escuelas_id = {$escuela_id}
                    AND formaciones_profesionales_id IS NOT NULL
                  LIMIT 1"
            );
            if ($q3 && mysqli_num_rows($q3)) {
                $form_id = (int) mysqli_fetch_assoc($q3)['formaciones_profesionales_id'];
            }
        }
    }
}

// NORMALIZAMOS
$escuela_id = $escuela_id ? (int)$escuela_id : 0;
$form_id = $form_id ? (int)$form_id : 0;

// MANEJO DEL FORMULARIO

// GUARDAR NUEVAS INSCRIPCIONES ADMINISTRADOR Y DOCENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['accion'] ?? '') === 'guardar') {
    require_role_action(['ADMIN', 'DOCENTE']);

    $esc = (int)$_POST['escuela'];
    $form = (int)$_POST['formacion'];
    $anioI = (int)$_POST['anio_ingreso'];
    $ids = $_POST['alumno_ids'] ?? [];

    foreach ($ids as $pid) {
        $pid = (int)$pid;
        mysqli_query(
            $conexion,
            "INSERT IGNORE INTO inscripciones_alumnos
             (personas_id, escuelas_id, formaciones_profesionales_id, anio_ingreso, fecha_ingreso)
             VALUES
             ($pid, $esc, $form, $anioI, NOW())"
        );
    }

    // REDIRIGIR
    header("Location: registros.php?escuela={$esc}&formacion={$form}&anio={$anioI}&tab=alumnos&saved=1&mode=ingresar_alumnos");
    exit;
}

// GUARDAR CAMBIOS DE ESTADO, ADMINISTRADOR Y DOCENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['accion'] ?? '') === 'guardar_estados') {
    require_role_action(['ADMIN', 'DOCENTE']);

    $estados = $_POST['estado'] ?? [];
    $hoy = date('Y-m-d');
    foreach ($estados as $insc_id => $estado) {
        $e = in_array($estado, ['CURSANDO', 'PROMOCIONO', 'ABANDONO'])
            ? $estado : 'CURSANDO';
        mysqli_query(
            $conexion,
            "UPDATE inscripciones_alumnos
               SET estado = '$e',
                   fecha_estado = '$hoy'
             WHERE inscripcion_id = " . intval($insc_id)
        );
    }

    header("Location: registros.php?escuela={$escuela_id}&formacion={$form_id}&anio={$anio}&tab=alumnos&saved=1&mode=alumnos_states");
    exit;
}

// GUARDAR CALCULOS ADMINISTRADOR Y DOCENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['accion'] ?? '') === 'guardar_calc') {
    require_role_action(['ADMIN', 'DOCENTE']);

    // VALORES BASICOS
    $anio = (int)($_POST['anio'] ?? 0);
    $mes = (int)($_POST['mes'] ?? 0);
    $dias_post = isset($_POST['dias_habiles']) ? trim($_POST['dias_habiles']) : '';

    // ARRAY POR FILA
    $asi_arr = isset($_POST['asi']) && is_array($_POST['asi']) ? $_POST['asi'] : [];
    $ina_arr = isset($_POST['ina']) && is_array($_POST['ina']) ? $_POST['ina'] : [];

    // INICIALIZAMOS VARIABLES
    $calc_error = 0;
    $calc_msg = '';

    // VALIDACIONES
    if (!$mes) {
        $calc_error = 1;
        $calc_msg = "Seleccione el mes antes de guardar.";
    }

    // DIAS HABILES
    $dias = 0;
    if ($dias_post !== '') {
        $dias = (int)$dias_post;
    }
    if ($dias <= 0) {
        if (isset($asi_arr[0]) || isset($ina_arr[0])) {
            $a0 = isset($asi_arr[0]) ? (int)$asi_arr[0] : 0;
            $i0 = isset($ina_arr[0]) ? (int)$ina_arr[0] : 0;
            $dias = $a0 + $i0;
        }
    }
    if ($dias <= 0 && !$calc_error) {
        $calc_error = 1;
        $calc_msg = "Días hábiles no definidos. Complete la PRIMERA fila o el campo DÍAS HÁBILES.";
    }

    // SI NO HAY FILAS CON DATOS, NO GUARDAR
    $anyData = false;
    $filaCount = max(count($asi_arr), count($ina_arr));
    for ($i = 0; $i < $filaCount; $i++) {
        $a = isset($asi_arr[$i]) ? (int)$asi_arr[$i] : 0;
        $n = isset($ina_arr[$i]) ? (int)$ina_arr[$i] : 0;
        if (($a + $n) > 0) {
            $anyData = true;
            break;
        }
    }
    if (!$anyData && !$calc_error) {
        $calc_error = 1;
        $calc_msg = "La calculadora está vacía. Ingrese al menos una fila con asistencia o inasistencia antes de guardar.";
    }

    // VALIDAR QUE CADA FILA TENGA SUMA = DIAS
    $mismatch = 0;
    $bad_numeric = 0;
    if (!$calc_error) {
        for ($i = 0; $i < $filaCount; $i++) {
            $asi_v = isset($asi_arr[$i]) ? $asi_arr[$i] : '';
            $ina_v = isset($ina_arr[$i]) ? $ina_arr[$i] : '';

            // NORMALIZAMOS
            if ($asi_v === '' && $ina_v === '') continue;
            if (!is_numeric($asi_v) || !is_numeric($ina_v)) {
                $bad_numeric++;
                continue;
            }
            $asi_i = (int)$asi_v;
            $ina_i = (int)$ina_v;
            if (($asi_i + $ina_i) !== (int)$dias) {
                $mismatch++;
            }
        }

        if ($bad_numeric > 0) {
            $calc_error = 1;
            $calc_msg = "Hay filas con valores no numéricos en asistencia/inasistencia. Corrija antes de guardar.";
        } elseif ($mismatch > 0) {
            $calc_error = 1;
            $calc_msg = "{$mismatch} fila(s) tienen asistencia+inasistencia distinta a los DÍAS HÁBILES ({$dias}). Revise las filas marcadas.";
        }
    }

    // SI HAY ERROR NO SE INSERTA
    if ($calc_error) {
        $tab = 'calculadora';
    } else {

        // PREPARAR TOTALES
        $asiVar = 0;
        $asiMuj = 0;
        $asiTot = 0;
        $inaVar = 0;
        $inaMuj = 0;
        $inaTot = 0;

        // CARGAR DATOS DE LOS ALUMNOS
        $sqlAl = "
          SELECT p.personas_sexo
          FROM inscripciones_alumnos ia
          JOIN personas p ON p.personas_id = ia.personas_id
         WHERE ia.escuelas_id={$escuela_id}
           AND ia.formaciones_profesionales_id={$form_id}
           AND ia.anio_ingreso={$anio}
         ORDER BY p.personas_apellido
        ";
        $resAl = mysqli_query($conexion, $sqlAl);
        $sexos = [];
        while ($row = mysqli_fetch_assoc($resAl)) $sexos[] = $row['personas_sexo'] ?? '';

        // SUMAMOS POR SEXO
        for ($i = 0; $i < $filaCount; $i++) {
            $asi_v = isset($asi_arr[$i]) && is_numeric($asi_arr[$i]) ? (int)$asi_arr[$i] : 0;
            $ina_v = isset($ina_arr[$i]) && is_numeric($ina_arr[$i]) ? (int)$ina_arr[$i] : 0;
            $tot_v = $asi_v;
            $tot_i = $ina_v;

            $asiTot += $asi_v;
            $inaTot += $ina_v;

            $sexo = $sexos[$i] ?? '';
            if (strtolower($sexo) === 'masculino') {
                $asiVar += $asi_v;
                $inaVar += $ina_v;
            } elseif (strtolower($sexo) === 'femenino') {
                $asiMuj += $asi_v;
                $inaMuj += $ina_v;
            } else {
            }
        }

        // MEDIAS Y PORCENTAJES
        $meVar = $dias ? (int)round($asiVar / $dias) : 0;
        $meMuj = $dias ? (int)round($asiMuj / $dias) : 0;
        $meTot = $dias ? (int)round($asiTot / $dias) : 0;
        $porVar = ($asiVar + $inaVar) ? round($asiVar * 100 / ($asiVar + $inaVar), 2) : 0;
        $porMuj = ($asiMuj + $inaMuj) ? round($asiMuj * 100 / ($asiMuj + $inaMuj), 2) : 0;
        $porTot = ($asiTot + $inaTot) ? round($asiTot * 100 / ($asiTot + $inaTot), 2) : 0;

        // OBTENER EL ID INSTITUCIONAL
        $res = mysqli_query(
            $conexion,
            "SELECT institucional_id
               FROM institucional
              WHERE escuelas_id={$escuela_id}
                AND formaciones_profesionales_id={$form_id}
              LIMIT 1"
        );
        $inst = $res && mysqli_num_rows($res) ? (int)mysqli_fetch_row($res)[0] : 0;

        // CHEQUEAMOS LOS DUPLICADOS, SEGUN INSTITUCIONAL, AÑO Y MES
        if ($inst && $mes) {
            $chk = mysqli_query(
                $conexion,
                "SELECT registros_id
                   FROM registros
                  WHERE institucional_id = {$inst}
                    AND registros_anio = {$anio}
                    AND registros_mes = {$mes}
                  LIMIT 1"
            );
            if ($chk && mysqli_num_rows($chk) > 0) {
                $calc_error = 1;
                $calc_msg = "Ya existe un registro para el mes {$mes} y año {$anio} para esta formación.";
                $tab = 'calculadora';
            }
        } else {
            if (!$inst) {
                $calc_error = 1;
                $calc_msg = "Imposible determinar la institucional asociada. Verifique escuela/formación.";
                $tab = 'calculadora';
            }
        }

        // SI NO HAY ERRORES, INSERTAMOS / ACTUALIZAMOS
        if (!$calc_error) {
            $sql = "
              INSERT INTO registros
                (institucional_id, registros_anio, registros_mes,
                 registros_dias_habi, registros_asi_va, registros_asi_mu, registros_asi_to, registros_ina_va, registros_ina_mu, registros_ina_to, registros_asi_me_va, registros_asi_me_mu, registros_asi_me_to, registros_por_asi_va, registros_por_asi_mu, registros_por_asi_to)
              VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
              ON DUPLICATE KEY UPDATE
                registros_dias_habi=VALUES(registros_dias_habi),
                registros_asi_va=VALUES(registros_asi_va),
                registros_asi_mu=VALUES(registros_asi_mu),
                registros_asi_to=VALUES(registros_asi_to),
                registros_ina_va=VALUES(registros_ina_va),
                registros_ina_mu=VALUES(registros_ina_mu),
                registros_ina_to=VALUES(registros_ina_to),
                registros_asi_me_va=VALUES(registros_asi_me_va),
                registros_asi_me_mu=VALUES(registros_asi_me_mu),
                registros_asi_me_to=VALUES(registros_asi_me_to),
                registros_por_asi_va=VALUES(registros_por_asi_va),
                registros_por_asi_mu=VALUES(registros_por_asi_mu),
                registros_por_asi_to=VALUES(registros_por_asi_to)
            ";
            $stmt = mysqli_prepare($conexion, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "iiiiiiiiiiiiiddd",
                $inst,
                $anio,
                $mes,
                $dias,
                $asiVar,
                $asiMuj,
                $asiTot,
                $inaVar,
                $inaMuj,
                $inaTot,
                $meVar,
                $meMuj,
                $meTot,
                $porVar,
                $porMuj,
                $porTot
            );

            $ok = mysqli_stmt_execute($stmt);
            $dbErr = mysqli_error($conexion);
            mysqli_stmt_close($stmt);

            if ($ok) {
                header("Location: registros.php?escuela={$escuela_id}&formacion={$form_id}&anio={$anio}&tab=calculadora&saved=1&mode=calculadora");
                exit;
            } else {
                $calc_error = 1;
                $calc_msg = "Error guardando en la base de datos: " . ($dbErr ?: 'Desconocido');
                $tab = 'calculadora';
            }
        }
    }
}

// FUNCION AUXILIAR PARA CREAR OPCIONES DE SELECT
function opts($rows, $id_f, $name_f, $sel)
{
    $h = '';
    foreach ($rows as $r) {
        $s = $r[$id_f] == $sel ? ' selected' : '';
        $h .= "<option value=\"{$r[$id_f]}\"{$s}>"
            . htmlspecialchars($r[$name_f]) .
            "</option>\n";
    }
    return $h;
}

// CARGO ESCUELAS
$escuelas = mysqli_query(
    $conexion,
    "SELECT escuelas_id, escuelas_nombre
     FROM escuelas
     WHERE escuelas_eliminado=0
     ORDER BY escuelas_nombre"
);

?>

<!DOCTYPE html>
<html lang="es">

<!-- HEAD -->
<?php include('head.php'); ?>

<!-- HEADER -->
<?php include('header.php'); ?>

<!-- ESTILOS CSS -->
<link rel="stylesheet" href="CSS/estilo_comun.css">
<link rel="stylesheet" href="CSS/estilo_app.css">

<!-- MENU LATERAL -->
<?php include('menu_lateral.php'); ?>

<body>
    <main class="fp-page">
        <h1 class="title">Registros</h1>

        <!-- FILTROS -->
        <form method="get" class="filtros" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">

            <!-- DOCENTE -->
            <?php if (is_docente()): ?>
                <label style="min-width:200px;">

                    <!-- ESCUELA FIJA -->
                    Escuela<br>
                    <select id="escuela" name="escuela_disabled" disabled>
                        <option value="">--</option>
                        <?= opts(mysqli_fetch_all($escuelas, MYSQLI_ASSOC), 'escuelas_id', 'escuelas_nombre', $escuela_id) ?>
                    </select>
                    <input type="hidden" name="escuela" value="<?= (int)$escuela_id ?>">
                </label>

                <!-- FORMACION PROFESIONAL FIJA -->
                <label style="min-width:220px;">
                    Formación Profesional<br>
                    <select id="formacion" name="formacion_disabled" disabled>
                        <option value="">--</option>
                    </select>
                    <input type="hidden" name="formacion" value="<?= (int)$form_id ?>">
                </label>

                <!-- DIRECTOR -->
            <?php elseif (is_director()): ?>
                <label style="min-width:200px;">

                    <!-- ESCUELA FIJA -->
                    Escuela<br>
                    <select id="escuela" name="escuela_disabled" disabled>
                        <option value="">--</option>
                        <?= opts(mysqli_fetch_all($escuelas, MYSQLI_ASSOC), 'escuelas_id', 'escuelas_nombre', $escuela_id) ?>
                    </select>
                    <input type="hidden" name="escuela" value="<?= (int)$escuela_id ?>">
                </label>

                <!-- FORMACION PROFESIONAL SELECCIONABLE -->
                <label style="min-width:220px;">
                    Formación Profesional<br>
                    <select name="formacion" id="formacion">
                        <option value="">--</option>
                    </select>
                </label>

                <!-- ADMINISTRADOR -->
            <?php else: ?>
                <label style="min-width:200px;">

                    <!-- ESCUELA SELECCIONABLE -->
                    Escuela<br>
                    <select name="escuela" id="escuela">
                        <option value="">--</option>
                        <?= opts(mysqli_fetch_all($escuelas, MYSQLI_ASSOC), 'escuelas_id', 'escuelas_nombre', $escuela_id) ?>
                    </select>
                </label>

                <!-- FORMACION PROFESIONAL SELECCIONABLE -->
                <label style="min-width:220px;">
                    Formación Profesional<br>
                    <select name="formacion" id="formacion">
                        <option value="">--</option>
                    </select>
                </label>
            <?php endif; ?>

            <!-- AÑO SELECCIONABLE PARA TODOS -->
            <label style="width:120px;">
                Año<br>
                <select name="anio" id="anio">
                    <option value="">--</option>
                    <?php for ($y = 2020; $y <= 2030; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $anio ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </label>

            <!-- DOCENTE -->
            <label style="min-width:300px;">
                Docente<br>
                <input type="text" id="docente" readonly placeholder="Docente"
                    value="<?= htmlspecialchars($docente_fullname ?? '') ?>" style="width:100%;">
            </label>

            <!-- BOTON FILTRAR -->
            <div style="display:flex;align-items:center;">
                <button type="submit" class="pill primary" style="height:40px;">Filtrar</button>
            </div>
        </form>

        <!-- PESTAÑAS -->
        <nav>
            <form method="get" class="tabs-form">
                <input type="hidden" name="escuela" value="<?= $escuela_id ?>">
                <input type="hidden" name="formacion" value="<?= $form_id ?>">
                <input type="hidden" name="anio" value="<?= $anio ?>">

                <?php

                // CONSTRUIMOS TABS SEGUN ROL
                $tabs = ['resumen' => 'Resumen', 'alumnos' => 'Alumnos'];
                // DIRECTOR NO VE CALCULADORA
                if (!is_director()) $tabs['calculadora'] = 'Calculadora';

                foreach ($tabs as $k => $lbl):
                    $url = "registros.php?escuela=" . urlencode($escuela_id)
                        . "&formacion=" . urlencode($form_id)
                        . "&anio=" . urlencode($anio)
                        . "&tab=" . urlencode($k);
                ?>
                    <a href="<?= $url ?>" class="<?= $tab === $k ? 'active' : '' ?>">
                        <?= htmlspecialchars($lbl) ?>
                    </a>
                <?php endforeach; ?>
            </form>
        </nav>

        <?php

        // EVITAR ACCESO DIRECTO POR URL
        if (is_director() && $tab === 'calculadora') {
            header("Location: registros.php?escuela={$escuela_id}&formacion={$form_id}&anio={$anio}&tab=resumen");
            exit;
        }
        ?>

        <section class="tab-content">

            <!-- RESUMEN -->
            <?php if ($tab === 'resumen'): ?>

                <?php if (!$anio): ?>
                    <p><em>Seleccione un año y presione "Filtrar" para ver el resumen.</em></p>
                <?php else: ?>
                    <?php

                    // DATOS PARA TABLA 4
                    $filtroEsc = $escuela_id ? "AND ia.escuelas_id = {$escuela_id}" : "";
                    $filtroFor = $form_id    ? "AND ia.formaciones_profesionales_id = {$form_id}" : "";
                    $filtroAn  = $anio       ? "AND ia.anio_ingreso = {$anio}" : "";

                    // TOTAL INSCRITOS
                    $sqlTot = "SELECT SUM(p.personas_sexo='Masculino') AS ins_va,
                    SUM(p.personas_sexo='Femenino') AS ins_mu,
                    COUNT(*) AS ins_to FROM inscripciones_alumnos ia
                    JOIN personas p ON p.personas_id = ia.personas_id WHERE 1=1
                    $filtroEsc
                    $filtroFor $filtroAn";
                    $rTot = mysqli_fetch_assoc(mysqli_query($conexion, $sqlTot));

                    // TOTAL PROMOCIONADOS
                    $sqlPro = " SELECT SUM(p.personas_sexo='Masculino' AND ia.estado='PROMOCIONO') AS pro_va,
                    SUM(p.personas_sexo='Femenino' AND ia.estado='PROMOCIONO') AS pro_mu,
                    SUM(ia.estado='PROMOCIONO') AS pro_to FROM inscripciones_alumnos ia
                    JOIN personas p ON p.personas_id = ia.personas_id WHERE 1=1
                    $filtroEsc
                    $filtroFor
                    $filtroAn";
                    $rPro = mysqli_fetch_assoc(mysqli_query($conexion, $sqlPro));

                    // DATOS PARA TABLA 1
                    // OBTENER LA FECHA MAS ANTIGUA DE INSCRIPCION
                    $sqlFecha = "SELECT MIN(fecha_ingreso) AS fecha_min FROM inscripciones_alumnos ia WHERE 1=1
                    $filtroEsc
                    $filtroFor
                    $filtroAn";
                    $rF = mysqli_fetch_assoc(mysqli_query($conexion, $sqlFecha));
                    $fecha_min = $rF['fecha_min'] ?? date('Y-m-d');

                    // CONTAR VARONES Y MUJERES HASTA ESA FECHA
                    $sqlCnt = "SELECT p.personas_sexo AS sexo, COUNT(*) AS cnt
                    FROM inscripciones_alumnos ia JOIN personas p ON p.personas_id = ia.personas_id WHERE 1=1
                    $filtroEsc
                    $filtroFor
                    $filtroAn
                    AND ia.fecha_ingreso <= '$fecha_min'
                    GROUP BY p.personas_sexo";
                    $qCnt = mysqli_query($conexion, $sqlCnt);
                    $varones = $mujeres = 0;
                    while ($r = mysqli_fetch_assoc($qCnt)) {
                        if (strtolower($r['sexo']) === 'masculino') $varones = (int)$r['cnt'];
                        else $mujeres = (int)$r['cnt'];
                    }
                    $total = $varones + $mujeres;

                    ?>

                    <div class="resumen-tablas contenedor">

                        <!-- TABLA 2 Y TABLA 4 -->
                        <div class="dos-resumen">
                            <div class="subtabla-res">

                                <!-- TABLA 1 - RESUMEN DE ENTRAS Y SALIDAS -->
                                <h3>Resumen de Entradas y Salidas</h3>
                                <table class="tabla tabla1 table table-striped table-hover tabla-resumen-1">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>V</th>
                                            <th>M</th>
                                            <th>T</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th>Inscritos al 1° día</th>
                                            <td><?= $varones ?></td>
                                            <td><?= $mujeres ?></td>
                                            <td><?= $total ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- TABLA 4 - RESUMEN GENERAL DE PROMOCION -->
                            <div class="subtabla-res">
                                <h3>Resumen General de Promoción</h3>
                                <table class="tabla tabla1 table table-striped table-hover tabla-resumen-4">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>V</th>
                                            <th>M</th>
                                            <th>T</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th>Total de Alumnos Inscriptos</th>
                                            <td><?= $rTot['ins_va']  ?></td>
                                            <td><?= $rTot['ins_mu']  ?></td>
                                            <td><?= $rTot['ins_to']  ?></td>
                                        </tr>
                                        <tr>
                                            <th>Total de Alumnos Promocionados</th>
                                            <td><?= $rPro['pro_va']  ?></td>
                                            <td><?= $rPro['pro_mu']  ?></td>
                                            <td><?= $rPro['pro_to']  ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TABLA 2 Y TABLA 3 -->
                        <?php

                        // TABLA 2 - MOVIMIENTO MENSUAL

                        // CONSTRUIR FILTROS
                        $filters = [];
                        if ($escuela_id) $filters[] = "ia.escuelas_id = " . (int)$escuela_id;
                        if ($form_id)    $filters[] = "ia.formaciones_profesionales_id = " . (int)$form_id;
                        if ($anio)       $filters[] = "ia.anio_ingreso = " . (int)$anio;

                        $filters_sql = $filters ? ' AND ' . implode(' AND ', $filters) : '';

                        // ENTRADAS POR MES
                        $sqlEntr = "
                            SELECT MONTH(ia.fecha_ingreso) AS mes,
                                    SUM(p.personas_sexo='Masculino') AS asi_va,
                                    SUM(p.personas_sexo='Femenino')  AS asi_mu,
                                    COUNT(*)                        AS asi_to
                                FROM inscripciones_alumnos ia
                                JOIN personas p ON p.personas_id = ia.personas_id
                            WHERE ia.fecha_ingreso IS NOT NULL
                            {$filters_sql}
                            GROUP BY MONTH(ia.fecha_ingreso)
                            ";
                        $resEntr = mysqli_query($conexion, $sqlEntr);

                        // SALIDAS POR MES
                        $sqlSal = "
                            SELECT MONTH(ia.fecha_estado) AS mes,
                                    SUM(p.personas_sexo='Masculino' AND ia.estado IN('PROMOCIONO','ABANDONO')) AS ina_va,
                                    SUM(p.personas_sexo='Femenino'  AND ia.estado IN('PROMOCIONO','ABANDONO')) AS ina_mu,
                                    COUNT(*)                                                       AS ina_to
                                FROM inscripciones_alumnos ia
                                JOIN personas p ON p.personas_id = ia.personas_id
                            WHERE ia.fecha_estado IS NOT NULL
                                AND ia.estado IN('PROMOCIONO','ABANDONO')
                            {$filters_sql}
                            GROUP BY MONTH(ia.fecha_estado)
                                ";

                        $resSal = mysqli_query($conexion, $sqlSal);
                        $salidas = [];
                        while ($r = mysqli_fetch_assoc($resSal)) {
                            $salidas[(int)$r['mes']] = $r;
                        }

                        // CARGAR TABLA DE MOVIMIENTOS
                        $mov_insc = [];
                        for ($m = 1; $m <= 12; $m++) {
                            $e = $entradas[$m] ?? ['asi_va' => 0, 'asi_mu' => 0, 'asi_to' => 0];
                            $s = $salidas[$m]  ?? ['ina_va' => 0, 'ina_mu' => 0, 'ina_to' => 0];
                            $mov_insc[$m] = [
                                'asi_va' => (int)$e['asi_va'],
                                'asi_mu' => (int)$e['asi_mu'],
                                'asi_to' => (int)$e['asi_to'],
                                'ina_va' => (int)$s['ina_va'],
                                'ina_mu' => (int)$s['ina_mu'],
                                'ina_to' => (int)$s['ina_to'],
                                'q_va'  => (int)$e['asi_va'] - (int)$s['ina_va'],
                                'q_mu'  => (int)$e['asi_mu'] - (int)$s['ina_mu'],
                                'q_to'  => (int)$e['asi_to'] - (int)$s['ina_to'],
                            ];
                        }

                        // TABLA 3 - RESUMEN MENSUAL DE ASISTENCIAS E INASISTENCIAS
                        $sqlReg = "SELECT r.registros_mes,
                                        COALESCE(r.registros_asi_va,0)    AS asi_va,
                                        COALESCE(r.registros_asi_mu,0)    AS asi_mu,
                                        COALESCE(r.registros_asi_to,0)    AS asi_to,
                                        COALESCE(r.registros_ina_va,0)    AS ina_va,
                                        COALESCE(r.registros_ina_mu,0)    AS ina_mu,
                                        COALESCE(r.registros_ina_to,0)    AS ina_to,
                                        COALESCE(r.registros_asi_me_va,0) AS me_va,
                                        COALESCE(r.registros_asi_me_mu,0) AS me_mu,
                                        COALESCE(r.registros_asi_me_to,0) AS me_to,
                                        COALESCE(r.registros_por_asi_va,0) AS p_va,
                                        COALESCE(r.registros_por_asi_mu,0) AS p_mu,
                                        COALESCE(r.registros_por_asi_to,0) AS p_to
                                    FROM registros r
                                    JOIN institucional i ON i.institucional_id = r.institucional_id
                                    WHERE 1=1
                                    " . ($anio ? "AND r.registros_anio = {$anio}" : '') . "
                                    " . ($escuela_id ? "AND i.escuelas_id = {$escuela_id}" : '') . "
                                    " . ($form_id ? "AND i.formaciones_profesionales_id = {$form_id}" : '') . "
                                ";

                        $qReg = mysqli_query($conexion, $sqlReg);
                        $registros = [];
                        while ($fila = mysqli_fetch_assoc($qReg)) {
                            $registros[(int)$fila['registros_mes']] = $fila;
                        }

                        $meses = [
                            'Enero' => 1,
                            'Febrero' => 2,
                            'Marzo' => 3,
                            'Abril' => 4,
                            'Mayo' => 5,
                            'Junio' => 6,
                            'Julio' => 7,
                            'Agosto' => 8,
                            'Septiembre' => 9,
                            'Octubre' => 10,
                            'Noviembre' => 11,
                            'Diciembre' => 12
                        ];
                        ?>

                        <!-- TABLA 2 Y TABLA 3 -->
                        <div class="tablas-container doble contenedor">
                            <div class="subtabla">
                                <h3>Movimiento Mensual</h3>
                                <table class="tabla tabla1 table table-striped table-hover tabla-resumen-mov">
                                    <thead>
                                        <tr>
                                            <th rowspan="2">Mes</th>
                                            <th colspan="3">Entradas</th>
                                            <th colspan="3">Salidas</th>
                                            <th colspan="3">Quedan</th>
                                        </tr>
                                        <tr>
                                            <th>V</th>
                                            <th>M</th>
                                            <th>T</th>
                                            <th>V</th>
                                            <th>M</th>
                                            <th>T</th>
                                            <th>V</th>
                                            <th>M</th>
                                            <th>T</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($meses as $nom => $num):
                                            $r = $mov_insc[$num] ?? [
                                                'asi_va' => 0,
                                                'asi_mu' => 0,
                                                'asi_to' => 0,
                                                'ina_va' => 0,
                                                'ina_mu' => 0,
                                                'ina_to' => 0,
                                                'q_va' => 0,
                                                'q_mu' => 0,
                                                'q_to' => 0
                                            ];
                                            $qv = $r['q_va'];
                                            $qm = $r['q_mu'];
                                            $qt = $r['q_to'];
                                        ?>
                                            <tr>
                                                <td><?= $nom ?></td>
                                                <td><?= $r['asi_va'] ?></td>
                                                <td><?= $r['asi_mu'] ?></td>
                                                <td><?= $r['asi_to'] ?></td>
                                                <td><?= $r['ina_va'] ?></td>
                                                <td><?= $r['ina_mu'] ?></td>
                                                <td><?= $r['ina_to'] ?></td>
                                                <td><?= $qv ?></td>
                                                <td><?= $qm ?></td>
                                                <td><?= $qt ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="subtabla">
                                <h3>Resumen Mensual de Asistencias e Inasistencias</h3>
                                <table class="tabla tabla2 table table-striped table-hover tabla-resumen-asi">
                                    <thead>
                                        <tr>
                                            <th colspan="3">Asistencias</th>
                                            <th colspan="3">Inasistencias</th>
                                            <th colspan="3">Asist. Media</th>
                                            <th colspan="3">% Asist.</th>
                                        </tr>
                                        <tr>
                                            <th>V</th>
                                            <th>M</th>
                                            <th>T</th>
                                            <th>V</th>
                                            <th>M</th>
                                            <th>T</th>
                                            <th>V</th>
                                            <th>M</th>
                                            <th>T</th>
                                            <th>V</th>
                                            <th>M</th>
                                            <th>T</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($meses as $num):
                                            $r = $registros[$num] ?? [
                                                'asi_va' => 0,
                                                'asi_mu' => 0,
                                                'asi_to' => 0,
                                                'ina_va' => 0,
                                                'ina_mu' => 0,
                                                'ina_to' => 0,
                                                'me_va' => 0,
                                                'me_mu' => 0,
                                                'me_to' => 0,
                                                'p_va' => 0,
                                                'p_mu' => 0,
                                                'p_to' => 0
                                            ];
                                        ?>
                                            <tr>
                                                <td><?= $r['asi_va'] ?></td>
                                                <td><?= $r['asi_mu'] ?></td>
                                                <td><?= $r['asi_to'] ?></td>
                                                <td><?= $r['ina_va'] ?></td>
                                                <td><?= $r['ina_mu'] ?></td>
                                                <td><?= $r['ina_to'] ?></td>
                                                <td><?= $r['me_va'] ?></td>
                                                <td><?= $r['me_mu'] ?></td>
                                                <td><?= $r['me_to'] ?></td>
                                                <td><?= $r['p_va'] ?>%</td>
                                                <td><?= $r['p_mu'] ?>%</td>
                                                <td><?= $r['p_to'] ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php endif; ?>
                <?php endif; ?>

                <!-- ALUMNOS INSCRITOS -->
                <?php if ($tab === 'alumnos' && ($_GET['accion'] ?? '') !== 'add'): ?>
                    <h3>Alumnos Inscriptos</h3>
                    <div class="acciones-tabla">
                        <?php if (is_admin() || is_docente()): ?>
                            <button type="button" class="pill primary"
                                onclick="location.href='?escuela=<?= $escuela_id ?>&formacion=<?= $form_id ?>&anio=<?= $anio ?>&tab=alumnos&accion=add'">
                                + Ingresar Alumnos
                            </button>
                        <?php endif; ?>

                        <?php if (is_admin() || is_docente()): ?>
                            <button type="submit" class="pill" form="form-guardar-estados">
                                Guardar Cambios
                            </button>
                        <?php else: ?>
                        <?php endif; ?>
                    </div>

                    <form id="form-guardar-estados" method="post"
                        action="registros.php?tab=alumnos&amp;accion=guardar_estados">
                        <input type="hidden" name="escuela" value="<?= $escuela_id ?>">
                        <input type="hidden" name="formacion" value="<?= $form_id ?>">
                        <input type="hidden" name="anio" value="<?= $anio ?>">

                        <div class="contenedor">
                            <table class="tabla1 table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Apellido, Nombre</th>
                                        <th>DNI</th>
                                        <th>Edad</th>
                                        <th>Sexo</th>
                                        <th>Año</th>
                                        <th>Fecha Ingreso</th>
                                        <th>Teléfono</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT
                                                ia.inscripcion_id AS id,
                                                p.personas_apellido,
                                                p.personas_nombre,
                                                p.personas_dni,
                                                TIMESTAMPDIFF(YEAR,p.personas_fechnac,CURDATE()) AS edad,
                                                p.personas_sexo,
                                                ia.anio_ingreso AS ingreso_ano,
                                                ia.fecha_ingreso,
                                                ia.estado,
                                                t.telefonos_numero AS telefono
                                            FROM inscripciones_alumnos ia
                                            JOIN personas p ON p.personas_id = ia.personas_id
                                            LEFT JOIN telefonos t
                                                ON t.personas_id = p.personas_id
                                            AND t.telefonos_predeterminado = 1
                                            WHERE ia.escuelas_id = $escuela_id
                                                AND ia.formaciones_profesionales_id = $form_id
                                                AND ia.anio_ingreso = $anio
                                            ";

                                    $q = mysqli_query($conexion, $sql);
                                    $i = 1;
                                    while ($r = mysqli_fetch_assoc($q)):
                                    ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars("{$r['personas_apellido']}, {$r['personas_nombre']}") ?></td>
                                            <td><?= htmlspecialchars($r['personas_dni']) ?></td>
                                            <td><?= $r['edad'] ?></td>
                                            <td><?= htmlspecialchars($r['personas_sexo']) ?></td>
                                            <td><?= $r['ingreso_ano'] ?></td>
                                            <td><?= htmlspecialchars($r['fecha_ingreso']) ?></td>
                                            <td><?= htmlspecialchars($r['telefono'] ?? '-') ?></td>
                                            <td>
                                                <select name="estado[<?= $r['id'] ?>]" <?= is_director() ? 'disabled' : '' ?>>
                                                    <option value="CURSANDO" <?= $r['estado'] == 'CURSANDO'  ? 'selected' : '' ?>>CURSANDO</option>
                                                    <option value="PROMOCIONO" <?= $r['estado'] == 'PROMOCIONO' ? 'selected' : '' ?>>PROMOCIÓN</option>
                                                    <option value="ABANDONO" <?= $r['estado'] == 'ABANDONO'  ? 'selected' : '' ?>>ABANDONO</option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- INGRESAR ALUMNOS -->
                <?php if ($tab === 'alumnos' && ($_GET['accion'] ?? '') === 'add'): ?>
                    <?php
                    require_role_action(['ADMIN', 'DOCENTE']);
                    ?>
                    <h3>Ingresar Alumnos</h3>

                    <div class="botones-accion">
                        <button type="submit" class="pill primary" form="form-guardar-inscripciones">
                            Guardar Inscripciones
                        </button>

                        <a href="?escuela=<?= $escuela_id ?>&formacion=<?= $form_id ?>&anio=<?= $anio ?>&tab=alumnos"
                            class="pill">← Volver</a>
                    </div>

                    <?php
                    $where = "WHERE i.institucional_tipo = 'ALUMNO'
                                AND p.personas_id NOT IN (
                                    SELECT personas_id
                                    FROM inscripciones_alumnos
                                    WHERE anio_ingreso = {$anio}
                                )";


                    // BUSCADOR
                    if (!empty($_GET['q'])) {
                        $busq = mysqli_real_escape_string($conexion, $_GET['q']);
                        $where .= " AND (p.personas_apellido LIKE '%$busq%' OR p.personas_nombre LIKE '%$busq%')";
                    }

                    $q2 = mysqli_query(
                        $conexion,
                        "SELECT DISTINCT
                                    p.personas_id,
                                    p.personas_apellido,
                                    p.personas_nombre,
                                    p.personas_dni,
                                    TIMESTAMPDIFF(YEAR,p.personas_fechnac,CURDATE()) AS edad,
                                    t.telefonos_numero AS telefono
                                FROM personas p
                                JOIN institucional i ON i.personas_id = p.personas_id
                                LEFT JOIN telefonos t
                                ON t.personas_id = p.personas_id
                                AND t.telefonos_predeterminado = 1
                                $where
                                ORDER BY p.personas_apellido
                                LIMIT 100"
                    );

                    ?>

                    <!-- BUSCADOR Y CONTADOR -->
                    <div class="buscador-contador">
                        <!-- BUSCADOR -->
                        <form method="get" class="filtros" action="registros.php">
                            <input type="hidden" name="escuela" value="<?= $escuela_id ?>">
                            <input type="hidden" name="formacion" value="<?= $form_id ?>">
                            <input type="hidden" name="anio" value="<?= $anio ?>">
                            <input type="hidden" name="tab" value="alumnos">
                            <input type="hidden" name="accion" value="add">
                            <label>Buscar:
                                <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                            </label>
                            <button type="submit" class="pill">🔍</button>
                        </form>

                        <!-- CONTADOR -->
                        <p>Seleccionados: <span id="count">0</span></p>
                    </div>

                    <form id="form-guardar-inscripciones" method="post"
                        action="registros.php?tab=alumnos&amp;accion=guardar">
                        <input type="hidden" name="escuela" value="<?= $escuela_id ?>">
                        <input type="hidden" name="formacion" value="<?= $form_id ?>">
                        <input type="hidden" name="anio_ingreso" value="<?= $anio ?>">

                        <table class="tabla1 table table-striped table-hover" id="tbl-add">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Apellido, Nombre</th>
                                    <th>DNI</th>
                                    <th>Edad</th>
                                    <th>Teléfono</th>
                                    <th>✓</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $fila = 1;
                                while ($p = mysqli_fetch_assoc($q2)): ?>
                                    <tr>
                                        <td><?= $fila++ ?></td>
                                        <td><?= htmlspecialchars("{$p['personas_apellido']}, {$p['personas_nombre']}") ?></td>
                                        <td><?= htmlspecialchars($p['personas_dni']) ?></td>
                                        <td><?= $p['edad'] ?></td>
                                        <td><?= htmlspecialchars($p['telefono'] ?? '-') ?></td>
                                        <td><input type="checkbox" name="alumno_ids[]" value="<?= $p['personas_id'] ?>"></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </form>
                <?php endif; ?>

                <!-- CALCULADORA -->
                <?php if ($tab === 'calculadora'): ?>
                    <?php

                    // RECUPERO ALUMNOS INSCRIPTOS
                    $sql = "SELECT ia.inscripcion_id,p.personas_apellido,p.personas_nombre,
                                    p.personas_sexo AS sexo, ia.estado
                                FROM inscripciones_alumnos ia
                                JOIN personas p ON p.personas_id=ia.personas_id
                            WHERE ia.escuelas_id={$escuela_id}
                                AND ia.formaciones_profesionales_id={$form_id}
                                AND ia.anio_ingreso={$anio}
                            ORDER BY p.personas_apellido
                            ";

                    $qCalc = mysqli_query($conexion, $sql);
                    ?>
                    <div class="calculadora">
                        <h3>Calculadora</h3>
                        <div class="botones">
                            <button type="button" class="pill" onclick="limpiarCalculadora()">Vaciar tabla</button>
                            <?php if (is_admin() || is_docente()): ?>
                                <button type="submit" form="form-calculadora" class="pill primary">Guardar</button>
                            <?php endif; ?>
                        </div>

                        <!-- MENSAJE DE VALIDACIONES -->
                        <div id="validation_message" style="display:<?= $calc_error ? 'block' : 'none' ?>;" class="<?= $calc_error ? 'error' : '' ?>">
                            <?= $calc_error ? htmlspecialchars($calc_msg) : '' ?>
                        </div>

                        <form id="form-calculadora" method="post" action="registros.php?tab=calculadora&accion=guardar_calc">
                            <input type="hidden" name="escuela" value="<?= $escuela_id ?>">
                            <input type="hidden" name="formacion" value="<?= $form_id ?>">
                            <input type="hidden" name="anio" value="<?= $anio ?>">

                            <!-- CAMPOS OCULTOS PARA ENVIO -->
                            <input type="hidden" name="sum_asi_var" id="h_sum-asi-var" value="0">
                            <input type="hidden" name="sum_asi_muj" id="h_sum-asi-muj" value="0">
                            <input type="hidden" name="sum_asi_tot" id="h_sum-asi-tot" value="0">
                            <input type="hidden" name="sum_ina_var" id="h_sum-ina-var" value="0">
                            <input type="hidden" name="sum_ina_muj" id="h_sum-ina-muj" value="0">
                            <input type="hidden" name="sum_ina_tot" id="h_sum-ina-tot" value="0">
                            <input type="hidden" name="sum_asi_med_var" id="h_sum-asi-med-var" value="0">
                            <input type="hidden" name="sum_asi_med_muj" id="h_sum-asi-med-muj" value="0">
                            <input type="hidden" name="sum_asi_med_tot" id="h_sum-asi-med-tot" value="0">
                            <input type="hidden" name="sum_por_var" id="h_sum-por-var" value="0%">
                            <input type="hidden" name="sum_por_muj" id="h_sum-por-muj" value="0%">
                            <input type="hidden" name="sum_por_tot" id="h_sum-por-tot" value="0%">

                            <div class="tablas-container ">

                                <!-- TABLA 1 -->
                                <table class="tabla1 table table-striped table-hover" id="id_tabla1">
                                    <thead>
                                        <tr>
                                            <th style="width:15px">#</th>
                                            <th style="width:100px">Apellido, Nombre</th>
                                            <th style="width:30px">Sexo</th>
                                            <th style="width:30px">Total Asistencias</th>
                                            <th style="width:30px">Total Inasistencias</th>
                                            <th style="width:30px">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 0;
                                        while ($f = mysqli_fetch_assoc($qCalc)): ?>
                                            <tr>
                                                <td><?= ++$i ?></td>
                                                <td><?= htmlspecialchars("{$f['personas_apellido']}, {$f['personas_nombre']}") ?></td>
                                                <td><?= htmlspecialchars($f['sexo']) ?></td>
                                                <td>
                                                    <input type="number" name="asi[]" min="0" onchange="recalcular()" style="width:80%;text-align:center;"
                                                        value="<?= isset($_POST['asi'][$i - 1]) ? (int)$_POST['asi'][$i - 1] : '' ?>"
                                                        <?= is_director() ? 'readonly' : '' ?>>
                                                </td>
                                                <td>
                                                    <input type="number" name="ina[]" min="0" onchange="recalcular()" style="width:80%;text-align:center;"
                                                        value="<?= isset($_POST['ina'][$i - 1]) ? (int)$_POST['ina'][$i - 1] : '' ?>"
                                                        <?= is_director() ? 'readonly' : '' ?>>
                                                </td>
                                                <td><?= htmlspecialchars($f['estado']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>

                                <!-- TABLA 2 -->
                                <table class="tabla2 table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th colspan="3" style="width:30px">
                                                <select name="mes" onchange="recalcular()">
                                                    <option value="">-- Seleccione mes --</option>
                                                    <?php
                                                    $M = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                                    foreach ($M as $idx => $n) echo "<option value=\"" . ($idx + 1) . "\">$n</option>\n";
                                                    ?>
                                                </select>
                                            </th>
                                            <th colspan="2" style="width:30px">MES</th>
                                        </tr>
                                        <tr>
                                            <th colspan="3" style="width:30px">
                                                <input type="number" id="id_dias_habiles" name="dias_habiles" readonly style="width:80%;text-align:center;">
                                            </th>
                                            <th colspan="2" style="width:30px">DÍAS HÁBILES</th>
                                        </tr>
                                        <tr>
                                            <th style="width:30px">VARONES</th>
                                            <th style="width:30px">MUJERES</th>
                                            <th style="width:30px">TOTAL</th>
                                            <th colspan="2" style="width:30px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td id="sum-asi-var" style="width:30px"></td>
                                            <td id="sum-asi-muj" style="width:30px"></td>
                                            <td id="sum-asi-tot" style="width:30px"></td>
                                            <th style="width:30px">ASISTENCIA</th>
                                            <th rowspan="2" style="width:30px">TOTAL</th>
                                        </tr>
                                        <tr>
                                            <td id="sum-ina-var" style="width:30px"></td>
                                            <td id="sum-ina-muj" style="width:30px"></td>
                                            <td id="sum-ina-tot" style="width:30px"></td>
                                            <th style="width:30px">INASISTENCIA</th>
                                        </tr>
                                        <tr>
                                            <td id="sum-asi-med-var" style="width:30px"></td>
                                            <td id="sum-asi-med-muj" style="width:30px"></td>
                                            <td id="sum-asi-med-tot" style="width:30px"></td>
                                            <th colspan="2" style="width:30px">ASISTENCIA MEDIA</th>
                                        </tr>
                                        <tr>
                                            <td id="sum-por-var" style="width:30px"></td>
                                            <td id="sum-por-muj" style="width:30px"></td>
                                            <td id="sum-por-tot" style="width:30px"></td>
                                            <th colspan="2" style="width:30px">% DE ASISTENCIA</th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

        </section>

        <!-- PASAR DATOS DE PHP A JS -->
        <script>
            window._REGISTROS = {
                preForm: <?= json_encode($form_id) ?>,
                preEsc: <?= json_encode($escuela_id) ?>,
                preAnio: <?= json_encode($anio) ?>,
                docenteFullname: <?= json_encode($docente_fullname ?? '') ?>
            };
        </script>

    </main>

    <!-- FOOTER -->
    <?php include('footer.php'); ?>

    <!-- SCRIPT -->
    <script src="JS/validaciones_registros.js" defer></script>
    <script src="JS/registros.js" defer></script>

    <!-- SWEETALERT2 -->
    <?php
    $saved_calc = isset($_GET['saved_calc']) ? (int)$_GET['saved_calc'] : 0;
    $calc_error = isset($_GET['calc_error']) ? (int)$_GET['calc_error'] : 0;
    $calc_msg   = $_GET['calc_msg'] ?? '';

    $saved_insc = isset($_GET['saved_inscripciones']) ? (int)$_GET['saved_inscripciones'] : 0;
    $saved_est  = isset($_GET['saved_estados']) ? (int)$_GET['saved_estados'] : 0;
    ?>
    <?php if ($saved): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const m = document.getElementById('validation_message') || document.createElement('div');
                if (!document.getElementById('validation_message')) {
                    m.id = 'validation_message';
                    document.querySelector('main.fp-page').insertBefore(m, document.querySelector('.tablas-container'));
                }
                m.className = 'success';
                m.textContent = 'Guardado correctamente.';
                m.style.display = 'block';
                setTimeout(() => {
                    m.style.display = 'none';
                }, 1800);

                const saved = <?= (int)$saved ?>;
                const mode = <?= json_encode($saved_mode, JSON_UNESCAPED_UNICODE) ?>;

                if (!saved) return;
                let text = 'Guardado correctamente.';
                if (mode === 'alumnos_states') text = 'Cambios en Alumnos guardados correctamente.';
                else if (mode === 'ingresar_alumnos') text = 'Inscripciones guardadas correctamente.';
                else if (mode === 'calculadora') text = 'Datos de la calculadora guardados correctamente.';
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: text,
                    confirmButtonText: 'Aceptar',
                    allowOutsideClick: false
                }).then(function() {
                    try {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('saved');
                        url.searchParams.delete('mode');
                        window.history.replaceState({}, document.title, url.pathname + url.search);
                    } catch (e) {}
                });

            });
        </script>
    <?php endif; ?>

</body>

</html>