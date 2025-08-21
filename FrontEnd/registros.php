<?php
// registros.php
// CONTROL DE CACHE Y SESION
if (session_status() === PHP_SESSION_NONE) session_start();

// Evitar que el navegador muestre páginas desde cache después del logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Comprobar login
if (empty($_SESSION['user_id'])) {
    header('Location: index.php', true, 303);
    exit;
}

// Mostrar errores en desarrollo
ini_set('display_errors', 1);
error_reporting(E_ALL);

// includes: conexion + helpers (asegurate la ruta)
require_once __DIR__ . '/../BackEnd/conexion.php';
require_once __DIR__ . '/auth_helpers.php';

// Solo roles que pueden ver la página
require_role_view(['ADMIN', 'DIRECTOR', 'DOCENTE']);
$role = current_role();

// --- Autocompletar para DOCENTE o DIRECTOR: escuela, formacion y (nombre docente si aplica) ---
$docente_fullname = $docente_fullname ?? '';
$escuela_id = $escuela_id ?? 0;
$form_id    = $form_id    ?? 0;

if (is_docente() || is_director()) {
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $personas_id = (int)($_SESSION['personas_id'] ?? 0);

    // Intentar obtener personas_id desde la tabla usuarios si no está en la sesión
    if (!$personas_id && $user_id) {
        $qr = mysqli_query($conexion, "SELECT personas_id FROM usuarios WHERE usuarios_id = {$user_id} LIMIT 1");
        if ($qr && mysqli_num_rows($qr)) {
            $personas_id = (int) mysqli_fetch_assoc($qr)['personas_id'];
        }
    }

    if ($personas_id) {
        // Si es DOCENTE, mostramos su nombre en el input 'Docente'
        if (is_docente()) {
            $q = mysqli_query($conexion, "SELECT personas_nombre, personas_apellido FROM personas WHERE personas_id = {$personas_id} LIMIT 1");
            if ($q && mysqli_num_rows($q)) {
                $r = mysqli_fetch_assoc($q);
                $docente_fullname = trim(($r['personas_nombre'] ?? '') . ' ' . ($r['personas_apellido'] ?? ''));
            }
        }

        // Buscar la institucional asociada (tomamos la primera que encuentre)
        $q2 = mysqli_query($conexion,
            "SELECT escuelas_id, formaciones_profesionales_id
               FROM institucional
              WHERE personas_id = {$personas_id}
              ORDER BY institucional_id ASC
              LIMIT 1"
        );
        if ($q2 && mysqli_num_rows($q2)) {
            $inst = mysqli_fetch_assoc($q2);
            $escuela_id = (int)($inst['escuelas_id'] ?? 0);
            $form_id    = (int)($inst['formaciones_profesionales_id'] ?? 0);
        }

        // Si hay escuela pero no formacion, intentar tomar alguna formacion existente para esa escuela
        if ($escuela_id && !$form_id) {
            $q3 = mysqli_query($conexion,
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

// 1) AJAX endpoints
if (isset($_GET['endpoint'])) {
    header('Content-Type: application/json');
    $escuela   = (int)($_GET['escuela']   ?? 0);
    $formacion = (int)($_GET['formacion'] ?? 0);

    if ($_GET['endpoint'] === 'formaciones') {
        // READ: disponible para todos los roles con view
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
        $out = [];
        while ($r = mysqli_fetch_assoc($q)) $out[] = $r;
        echo json_encode($out);
        exit;
    }

    if ($_GET['endpoint'] === 'docente') {
        // READ: disponible para todos los roles con view
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
        echo json_encode($doc);
        exit;
    }

    if ($_GET['endpoint'] === 'update_estado') {
        // MODIFICACIÓN via AJAX: solo ADMIN y DOCENTE
        require_role_action(['ADMIN', 'DOCENTE']);

        header('Content-Type: application/json');
        $pid    = (int)($_GET['personas_id'] ?? 0);
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
            echo json_encode(['ok' => (bool)$ok, 'error' => $ok ? '' : mysqli_error($conexion)]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos']);
        }
        exit;
    }
}

// -------------------------
// Capturo filtros y pestaña
// -------------------------
// NOTA: usamos null como valor "no seleccionado" para distinguir "vino por GET" vs "no vino"
$escuela_id = array_key_exists('escuela', $_REQUEST) && $_REQUEST['escuela'] !== '' ? (int)$_REQUEST['escuela'] : null;
$form_id    = array_key_exists('formacion', $_REQUEST) && $_REQUEST['formacion'] !== '' ? (int)$_REQUEST['formacion'] : null;
$anio       = isset($_REQUEST['anio']) && $_REQUEST['anio'] !== '' ? (int)$_REQUEST['anio'] : 0;
$tab        = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : 'resumen';

// inicializaciones
$docente_fullname = $docente_fullname ?? '';

// -------------------------
// Si es DOCENTE o DIRECTOR y NO vino escuela por GET -> rellenar desde institucional
// -------------------------
if ((is_docente() || is_director()) && empty($escuela_id)) {

    // obtener personas_id: preferimos el que tengas en sesión, si no, buscar en usuarios
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $personas_id = (int)($_SESSION['personas_id'] ?? 0);

    if (!$personas_id && $user_id) {
        $qr = mysqli_query($conexion, "SELECT personas_id FROM usuarios WHERE usuarios_id = {$user_id} LIMIT 1");
        if ($qr && mysqli_num_rows($qr)) {
            $personas_id = (int) mysqli_fetch_assoc($qr)['personas_id'];
        }
    }

    if ($personas_id) {
        // Si es DOCENTE, recuperar su nombre completo (opcional para director)
        if (is_docente()) {
            $q = mysqli_query($conexion, "SELECT personas_nombre, personas_apellido FROM personas WHERE personas_id = {$personas_id} LIMIT 1");
            if ($q && mysqli_num_rows($q)) {
                $r = mysqli_fetch_assoc($q);
                $docente_fullname = trim(($r['personas_nombre'] ?? '') . ' ' . ($r['personas_apellido'] ?? ''));
            }
        }

        // Buscar la institucional asociada (tomamos la primera que encuentre)
        $q2 = mysqli_query($conexion,
            "SELECT escuelas_id, formaciones_profesionales_id
               FROM institucional
              WHERE personas_id = {$personas_id}
              ORDER BY institucional_id ASC
              LIMIT 1"
        );
        if ($q2 && mysqli_num_rows($q2)) {
            $inst = mysqli_fetch_assoc($q2);
            $escuela_id = (int)($inst['escuelas_id'] ?? 0);
            $form_id    = isset($inst['formaciones_profesionales_id']) ? (int)$inst['formaciones_profesionales_id'] : null;
        }

        // Si hay escuela pero no formacion, intentar tomar alguna formacion existente para esa escuela
        if (!empty($escuela_id) && empty($form_id)) {
            $q3 = mysqli_query($conexion,
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

// Finalmente normalizamos para que el resto del código (opts(...) y JS) reciba enteros
$escuela_id = $escuela_id ? (int)$escuela_id : 0;
$form_id    = $form_id    ? (int)$form_id    : 0;

// 2) Manejo de formularios

// a) Guardar nuevas inscripciones (POST) -> ADMIN y DOCENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['accion'] ?? '') === 'guardar') {
    require_role_action(['ADMIN', 'DOCENTE']);

    $esc   = (int)$_POST['escuela'];
    $form  = (int)$_POST['formacion'];
    $anioI = (int)$_POST['anio_ingreso'];
    $ids   = $_POST['alumno_ids'] ?? [];

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
    header("Location: registros.php?escuela=$esc&formacion=$form&anio=$anioI&tab=alumnos");
    exit;
}

// b) Guardar cambios de estados (POST) -> ADMIN y DOCENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['accion'] ?? '') === 'guardar_estados') {
    require_role_action(['ADMIN', 'DOCENTE']);

    $estados = $_POST['estado'] ?? [];
    $hoy     = date('Y-m-d');
    foreach ($estados as $insc_id => $estado) {
        $e = in_array($estado, ['CURSANDO', 'PROMOCIONO', 'ABANDONO'])
            ? $estado : 'CURSANDO';
        mysqli_query(
            $conexion,
            "UPDATE inscripciones_alumnos
               SET estado      = '$e',
                   fecha_estado = '$hoy'
             WHERE inscripcion_id = " . intval($insc_id)
        );
    }
    header("Location: registros.php?escuela={$escuela_id}&formacion={$form_id}&anio={$anio}&tab=alumnos");
    exit;
}

// 5) Guardar cálculos (tab Calculadora) -> ADMIN y DOCENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['accion'] ?? '') === 'guardar_calc') {
    require_role_action(['ADMIN', 'DOCENTE']);

    $anio = (int)($_POST['anio'] ?? 0);
    $mes  = (int)($_POST['mes'] ?? 0);
    $dias = (int)($_POST['dias_habiles'] ?? 0);

    $asiVar = (int)($_POST['sum_asi_var'] ?? 0);
    $asiMuj = (int)($_POST['sum_asi_muj'] ?? 0);
    $asiTot = (int)($_POST['sum_asi_tot'] ?? 0);
    $inaVar = (int)($_POST['sum_ina_var'] ?? 0);
    $inaMuj = (int)($_POST['sum_ina_muj'] ?? 0);
    $inaTot = (int)($_POST['sum_ina_tot'] ?? 0);
    $meVar  = (int)($_POST['sum_asi_med_var'] ?? 0);
    $meMuj  = (int)($_POST['sum_asi_med_muj'] ?? 0);
    $meTot  = (int)($_POST['sum_asi_med_tot'] ?? 0);
    $porVar = isset($_POST['sum_por_var']) ? (float)rtrim($_POST['sum_por_var'], '%') : 0;
    $porMuj = isset($_POST['sum_por_muj']) ? (float)rtrim($_POST['sum_por_muj'], '%') : 0;
    $porTot = isset($_POST['sum_por_tot']) ? (float)rtrim($_POST['sum_por_tot'], '%') : 0;

    $res = mysqli_query(
        $conexion,
        "SELECT institucional_id
           FROM institucional
          WHERE escuelas_id={$escuela_id}
            AND formaciones_profesionales_id={$form_id}
          LIMIT 1"
    );
    $inst = $res && mysqli_num_rows($res) ? (int)mysqli_fetch_row($res)[0] : 0;

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

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: registros.php?escuela=$escuela_id&formacion=$form_id&anio=$anio&tab=calculadora");
    exit;
}

// -----------------------------------------------------------------------------
// Renderizado de la página
// -----------------------------------------------------------------------------
include('head.php');
include('header.php');
include('menu_lateral.php');

// Helper para <option>
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

// Cargo escuelas
$escuelas = mysqli_query(
    $conexion,
    "SELECT escuelas_id, escuelas_nombre
     FROM escuelas
     WHERE escuelas_eliminado=0
     ORDER BY escuelas_nombre"
);

?>
<main class="fp-page">

    <!-- ESTILOS -->
    <link rel="stylesheet" href="CSS/style_common.css">
    <link rel="stylesheet" href="CSS/style_app.css">

    <h1>Registros</h1>

   <!-- filtros -->
<form method="get" class="filtros">
    <?php if (is_docente()): ?>
        <label>Escuela:
            <!-- select visible pero deshabilitado para que el docente no pueda cambiar -->
            <select id="escuela" name="escuela_disabled" disabled>
                <option value="">--</option>
                <?= opts(mysqli_fetch_all($escuelas, MYSQLI_ASSOC), 'escuelas_id', 'escuelas_nombre', $escuela_id) ?>
            </select>
            <input type="hidden" name="escuela" value="<?= (int)$escuela_id ?>">
        </label>

        <label>Formación Profesional:
            <select id="formacion" name="formacion_disabled" disabled>
                <option value="">--</option>
            </select>
            <input type="hidden" name="formacion" value="<?= (int)$form_id ?>">
        </label>

    <?php elseif (is_director()): ?>
        <label>Escuela:
            <!-- escuela fija (seleccionada) -->
            <select id="escuela" name="escuela_disabled" disabled>
                <option value="">--</option>
                <?= opts(mysqli_fetch_all($escuelas, MYSQLI_ASSOC), 'escuelas_id', 'escuelas_nombre', $escuela_id) ?>
            </select>
            <input type="hidden" name="escuela" value="<?= (int)$escuela_id ?>">
        </label>

        <label>Formación Profesional:
            <!-- FORMACION editable para director -->
            <select name="formacion" id="formacion">
                <option value="">--</option>
            </select>
        </label>

    <?php else: ?>
        <label>Escuela:
            <select name="escuela" id="escuela">
                <option value="">--</option>
                <?= opts(mysqli_fetch_all($escuelas, MYSQLI_ASSOC), 'escuelas_id', 'escuelas_nombre', $escuela_id) ?>
            </select>
        </label>

        <label>Formación Profesional:
            <select name="formacion" id="formacion">
                <option value="">--</option>
            </select>
        </label>
    <?php endif; ?>

    <label>Año:
        <select name="anio" id="anio">
            <option value="">--</option>
            <?php for ($y = 2020; $y <= 2030; $y++): ?>
                <option value="<?= $y ?>" <?= $y === $anio ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </label>

    <label>Docente:
        <input type="text" id="docente" readonly style="width:300px" placeholder="Docente"
               value="<?= htmlspecialchars($docente_fullname ?? '') ?>">
    </label>

    <button type="submit">Filtrar</button>
</form>

    <!-- pestañas -->
    <nav class="tabs">
        <form method="get" class="tabs-form">
            <input type="hidden" name="escuela" value="<?= $escuela_id ?>">
            <input type="hidden" name="formacion" value="<?= $form_id ?>">
            <input type="hidden" name="anio" value="<?= $anio ?>">

            <?php
            // Construimos tabs según rol: DIRECTOR no ve 'calculadora'
            $tabs = ['resumen' => 'Resumen', 'alumnos' => 'Alumnos'];
            if (!is_director()) $tabs['calculadora'] = 'Calculadora';

            foreach ($tabs as $k => $lbl): ?>
                <button type="submit" name="tab" value="<?= $k ?>"
                    class="<?= $tab == $k ? 'active' : '' ?>"><?= $lbl ?></button>
            <?php endforeach; ?>
        </form>
    </nav>

    <?php
    // Evitar acceso directo por URL a 'calculadora' si el rol es DIRECTOR
    if (is_director() && $tab === 'calculadora') {
        // redirigimos a resumen con los mismos filtros
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
                /// ───────────────────────────────────────────────────────────────
                // 0) Filtros comunes (solo si fueron seleccionados)
                $filtroEsc = $escuela_id ? "AND ia.escuelas_id = {$escuela_id}" : "";
                $filtroFor = $form_id    ? "AND ia.formaciones_profesionales_id = {$form_id}" : "";
                $filtroAn  = $anio       ? "AND ia.anio_ingreso = {$anio}" : "";

                // 1) TOTAL INSCRITOS (por sexo y total)
                $sqlTot = "
      SELECT
        SUM(p.personas_sexo='Masculino')   AS ins_va,
        SUM(p.personas_sexo='Femenino')    AS ins_mu,
        COUNT(*)                           AS ins_to
      FROM inscripciones_alumnos ia
      JOIN personas p ON p.personas_id = ia.personas_id
      WHERE 1=1
        $filtroEsc
        $filtroFor
        $filtroAn
    ";
                $rTot = mysqli_fetch_assoc(mysqli_query($conexion, $sqlTot));

                // 2) TOTAL PROMOCIONADOS (solo estado='PROMOCIONO')
                $sqlPro = "
      SELECT
        SUM(p.personas_sexo='Masculino' AND ia.estado='PROMOCIONO') AS pro_va,
        SUM(p.personas_sexo='Femenino'  AND ia.estado='PROMOCIONO') AS pro_mu,
        SUM(ia.estado='PROMOCIONO')                              AS pro_to
      FROM inscripciones_alumnos ia
      JOIN personas p ON p.personas_id = ia.personas_id
      WHERE 1=1
        $filtroEsc
        $filtroFor
        $filtroAn
    ";
                $rPro = mysqli_fetch_assoc(mysqli_query($conexion, $sqlPro));

                // DATOS PARA TABLA 1
                // ───────────────────────────────────────────────────
                // 1) Obtener la fecha más antigua de inscripción (solo si existe filtro de año, si no tomamos la mínima global con filtros)
                $sqlFecha = "
  SELECT MIN(fecha_ingreso) AS fecha_min
    FROM inscripciones_alumnos ia
   WHERE 1=1
     $filtroEsc
     $filtroFor
     $filtroAn
";
                $rF = mysqli_fetch_assoc(mysqli_query($conexion, $sqlFecha));
                $fecha_min = $rF['fecha_min'] ?? date('Y-m-d');

                // 2) Contar varones y mujeres hasta esa fecha (inscritos al 1° día)
                $sqlCnt = "
  SELECT p.personas_sexo AS sexo, COUNT(*) AS cnt
    FROM inscripciones_alumnos ia
    JOIN personas p ON p.personas_id = ia.personas_id
   WHERE 1=1
     $filtroEsc
     $filtroFor
     $filtroAn
     AND ia.fecha_ingreso <= '$fecha_min'
   GROUP BY p.personas_sexo
";
                $qCnt = mysqli_query($conexion, $sqlCnt);
                $varones = $mujeres = 0;
                while ($r = mysqli_fetch_assoc($qCnt)) {
                    if (strtolower($r['sexo']) === 'masculino') $varones = (int)$r['cnt'];
                    else                                      $mujeres = (int)$r['cnt'];
                }
                $total = $varones + $mujeres;
                ?>

                <div class="resumen-tablas">
                    <!-- tablas 1 y 4 lado a lado -->
                    <div class="dos-resumen">
                        <div class="subtabla-res">

                            <!-- TABLA 1 - RESUMEN DE ENTRAS Y SALIDAS -->
                            <h3>Resumen de Entradas y Salidas</h3>
                            <table class="tabla tabla1 tabla-resumen-1">
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
                            <table class="tabla tabla1 tabla-resumen-4">
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
                                        <th>Total de Alumnos Inscritos</th>
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

                    // -----------------------------------------------------------------
                    // TABLA 2 - MOVIMIENTO MENSUAL (se genera desde inscripciones_alumnos)
                    // -----------------------------------------------------------------

                    // 1) Entradas por mes (fecha_ingreso)
                    $sqlEntr = "
  SELECT MONTH(ia.fecha_ingreso) AS mes,
         SUM(p.personas_sexo='Masculino') AS asi_va,
         SUM(p.personas_sexo='Femenino')  AS asi_mu,
         COUNT(*)                        AS asi_to
    FROM inscripciones_alumnos ia
    JOIN personas p ON p.personas_id = ia.personas_id
   WHERE ia.fecha_ingreso IS NOT NULL
     " . ($escuela_id ? "AND ia.escuelas_id = {$escuela_id}" : '') . "
     " . ($form_id    ? "AND ia.formaciones_profesionales_id = {$form_id}" : '') . "
     " . ($anio       ? "AND ia.anio_ingreso = {$anio}" : '') . "
   GROUP BY MONTH(ia.fecha_ingreso)
";
                    $resEntr = mysqli_query($conexion, $sqlEntr);
                    $entradas = [];
                    while ($r = mysqli_fetch_assoc($resEntr)) {
                        $entradas[(int)$r['mes']] = $r;
                    }

                    // 2) Salidas por mes (fecha_estado con estados PROMOCIONO o ABANDONO)
                    $sqlSal = "
  SELECT MONTH(ia.fecha_estado) AS mes,
         SUM(p.personas_sexo='Masculino' AND ia.estado IN('PROMOCIONO','ABANDONO')) AS ina_va,
         SUM(p.personas_sexo='Femenino'  AND ia.estado IN('PROMOCIONO','ABANDONO')) AS ina_mu,
         COUNT(*)                                                       AS ina_to
    FROM inscripciones_alumnos ia
    JOIN personas p ON p.personas_id=ia.personas_id
   WHERE ia.fecha_estado IS NOT NULL
     AND ia.estado IN('PROMOCIONO','ABANDONO')
     " . ($escuela_id ? "AND ia.escuelas_id = {$escuela_id}" : '') . "
     " . ($form_id    ? "AND ia.formaciones_profesionales_id = {$form_id}" : '') . "
     " . ($anio       ? "AND ia.anio_ingreso = {$anio}" : '') . "
   GROUP BY MONTH(ia.fecha_estado)
";
                    $resSal = mysqli_query($conexion, $sqlSal);
                    $salidas = [];
                    while ($r = mysqli_fetch_assoc($resSal)) {
                        $salidas[(int)$r['mes']] = $r;
                    }

                    // 3) Combinar en $mov_insc (clave = mes)
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
                            // aquí "quedan" simple: entradas - salidas (por mes)
                            'q_va'  => (int)$e['asi_va'] - (int)$s['ina_va'],
                            'q_mu'  => (int)$e['asi_mu'] - (int)$s['ina_mu'],
                            'q_to'  => (int)$e['asi_to'] - (int)$s['ina_to'],
                        ];
                    }

                    // -----------------------------------------------------------------
                    // TABLA 3 - RESUMEN MENSUAL DE ASISTENCIAS E INASISTENCIAS (desde registros)
                    // -----------------------------------------------------------------
                    $sqlReg = "
    SELECT r.registros_mes,
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
                    <div class="tablas-container">
                        <div class="subtabla">
                            <h3>Movimiento Mensual</h3>
                            <table class="tabla tabla1 tabla-resumen-mov">
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
                            <table class="tabla tabla2 tabla-resumen-asi">
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
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- ALUMNOS INSCRITOS -->
        <?php if ($tab === 'alumnos' && ($_GET['accion'] ?? '') !== 'add'): ?>
            <h3>Alumnos Inscritos</h3>
            <div class="acciones-tabla">
                <?php if (is_admin() || is_docente()): ?>
                    <button type="button" class="filtros__btn"
                        onclick="location.href='?escuela=<?= $escuela_id ?>&formacion=<?= $form_id ?>&anio=<?= $anio ?>&tab=alumnos&accion=add'">
                        + Ingresar Alumnos
                    </button>
                <?php endif; ?>

                <?php if (is_admin() || is_docente()): ?>
                    <button type="submit" class="filtros__btn" form="form-guardar-estados">
                        Guardar Cambios
                    </button>
                <?php else: ?>
                    <!-- DIRECTOR solo ve, no puede guardar -->
                <?php endif; ?>
            </div>

            <form id="form-guardar-estados" method="post"
                action="registros.php?tab=alumnos&amp;accion=guardar_estados">
                <input type="hidden" name="escuela" value="<?= $escuela_id ?>">
                <input type="hidden" name="formacion" value="<?= $form_id ?>">
                <input type="hidden" name="anio" value="<?= $anio ?>">

                <table class="tabla1">
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
                        $sql = "
              SELECT
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
            </form>
        <?php endif; ?>

        <!-- INGRESAR ALUMNOS -->
        <?php if ($tab === 'alumnos' && ($_GET['accion'] ?? '') === 'add'): ?>
            <?php
            // Solo ADMIN y DOCENTE pueden ver esta vista
            require_role_action(['ADMIN', 'DOCENTE']);
            ?>
            <h3>Ingresar Alumnos</h3>

            <div class="botones-accion">
                <button type="submit" class="btn volver" form="form-guardar-inscripciones">
                    Guardar Inscripciones
                </button>

                <a href="?escuela=<?= $escuela_id ?>&formacion=<?= $form_id ?>&anio=<?= $anio ?>&tab=alumnos"
                    class="btn volver">← Volver</a>
            </div>

            <?php
            // excluyo solo los ya inscritos este año
// excluyo solo los ya inscritos este año
$where = "
    WHERE i.institucional_tipo = 'ALUMNO'
      AND p.personas_id NOT IN (
        SELECT personas_id
          FROM inscripciones_alumnos
         WHERE anio_ingreso = {$anio}
       )";

// Búsqueda por texto (si corresponde)
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

            <div class="buscador-contador">
                <!-- buscador -->
                <form method="get" class="filtros" action="registros.php">
                    <input type="hidden" name="escuela" value="<?= $escuela_id ?>">
                    <input type="hidden" name="formacion" value="<?= $form_id ?>">
                    <input type="hidden" name="anio" value="<?= $anio ?>">
                    <input type="hidden" name="tab" value="alumnos">
                    <input type="hidden" name="accion" value="add">
                    <label>Buscar:
                        <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    </label>
                    <button type="submit">🔍</button>
                </form>

                <!-- contador -->
                <p>Seleccionados: <span id="count">0</span></p>
            </div>

            <form id="form-guardar-inscripciones" method="post"
                action="registros.php?tab=alumnos&amp;accion=guardar">
                <input type="hidden" name="escuela" value="<?= $escuela_id ?>">
                <input type="hidden" name="formacion" value="<?= $form_id ?>">
                <input type="hidden" name="anio_ingreso" value="<?= $anio ?>">

                <table class="tabla1" id="tbl-add">
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

            <script>
                const tbl = document.getElementById('tbl-add'),
                    countSpan = document.getElementById('count');
                tbl.querySelectorAll('input[type=checkbox]').forEach(cb => {
                    cb.addEventListener('change', e => {
                        e.target.closest('tr').classList.toggle('selected', e.target.checked);
                        countSpan.textContent = tbl.querySelectorAll('input[type=checkbox]:checked').length;
                    });
                });
            </script>
        <?php endif; ?>

        <!-- CALCULADORA -->
        <?php if ($tab === 'calculadora'): ?>
            <?php
            // Recupero alumnos inscritos
            $sql = "
          SELECT ia.inscripcion_id,p.personas_apellido,p.personas_nombre,
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
                <h2>Calculadora</h2>
                <div class="botones">
                    <button type="button" onclick="limpiarCalculadora()">Vaciar tabla</button>
                    <?php if (is_admin() || is_docente()): ?>
                        <button type="submit" form="form-calculadora">Guardar</button>
                    <?php endif; ?>
                </div>
                <form id="form-calculadora" method="post" action="registros.php?tab=calculadora&accion=guardar_calc">
                    <input type="hidden" name="escuela" value="<?= $escuela_id ?>">
                    <input type="hidden" name="formacion" value="<?= $form_id ?>">
                    <input type="hidden" name="anio" value="<?= $anio ?>">

                    <!-- Campos ocultos para envío -->
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
                        <!-- Tabla1 -->
                        <table class="tabla1" id="id_tabla1">
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
                                <?php $i = 1;
                                while ($f = mysqli_fetch_assoc($qCalc)): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars("{$f['personas_apellido']}, {$f['personas_nombre']}") ?></td>
                                        <td><?= htmlspecialchars($f['sexo']) ?></td>
                                        <td><input type="number" name="asi" min="0" onchange="recalcular()" style="width:80%;text-align:center;" <?= is_director() ? 'readonly' : '' ?>></td>
                                        <td><input type="number" name="ina" min="0" onchange="recalcular()" style="width:80%;text-align:center;" <?= is_director() ? 'readonly' : '' ?>></td>
                                        <td><?= htmlspecialchars($f['estado']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <!-- Tabla2 (sin cambios funcionales) -->
                        <table class="tabla2">
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
                                        <input type="number" id="dias-habiles" name="dias_habiles" readonly style="width:80%;text-align:center;">
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

    <!-- JAVASCRIPT -->
    <!-- JS selects dependientes -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const esc = document.getElementById('escuela'),
                frm = document.getElementById('formacion'),
                doc = document.getElementById('docente'),
                preForm = <?= json_encode($form_id) ?>;

            function loadForm(e) {
                frm.innerHTML = '<option>Cargando…</option>';
                fetch(`registros.php?endpoint=formaciones&escuela=${e}`)
                    .then(r => r.json()).then(js => {
                        let h = '<option value="">--</option>';
                        js.forEach(o => {
                            h += `<option value="${o.id}"${o.id==preForm?' selected':''}>${o.nombre}</option>`;
                        });
                        frm.innerHTML = h;
                        if (preForm) loadDoc(e, preForm);
                    });
            }

            function loadDoc(e, f) {
                doc.value = 'Cargando…';
                fetch(`registros.php?endpoint=docente&escuela=${e}&formacion=${f}`)
                    .then(r => r.json()).then(j => {
                        doc.value = j.personas_nombre ? `${j.personas_nombre} ${j.personas_apellido}` : '';
                    });
            }
            esc.addEventListener('change', () => {
                if (esc.value) loadForm(esc.value);
                else {
                    frm.innerHTML = '<option value="">--</option>';
                    doc.value = '';
                }
            });
            frm.addEventListener('change', () => {
                if (esc.value && frm.value) loadDoc(esc.value, frm.value);
                else doc.value = '';
            });
            if (esc.value) esc.dispatchEvent(new Event('change'));
        });

        // Recalcula todo (mismo código que tenías)
        function recalcular() {
            const rows = document.querySelectorAll('.calculadora .tabla1 tbody tr');
            let asiVar = 0,
                asiMuj = 0,
                inaVar = 0,
                inaMuj = 0;

            // 1) Sumar asistencias/inasistencias
            rows.forEach(r => {
                const sexo = r.cells[2].textContent.trim();
                const asi = +r.cells[3].querySelector('input').value || 0;
                const ina = +r.cells[4].querySelector('input').value || 0;
                if (sexo === 'Masculino') {
                    asiVar += asi;
                    inaVar += ina;
                } else {
                    asiMuj += asi;
                    inaMuj += ina;
                }
            });

            // 2) Totales
            const asiTot = asiVar + asiMuj;
            const inaTot = inaVar + inaMuj;

            // 3) Días hábiles = suma de la primera fila
            let dias = 1;
            if (rows.length) {
                const f = rows[0];
                dias = (+f.cells[3].querySelector('input').value || 0) +
                    (+f.cells[4].querySelector('input').value || 0);
                if (dias < 1) dias = 1;
            }

            // 4) Medias
            const medVar = Math.round(asiVar / dias);
            const medMuj = Math.round(asiMuj / dias);
            const medTot = Math.round(asiTot / dias);

            // 5) Porcentajes
            const pct = (a, b) => b ? Math.round(a * 100 / b) + '%' : '0%';
            const pctVar = pct(asiVar, asiVar + inaVar);
            const pctMuj = pct(asiMuj, asiMuj + inaMuj);
            const pctTot = pct(asiTot, asiTot + inaTot);

            // 6) Pintar en pantalla
            document.getElementById('sum-asi-var').textContent = asiVar;
            document.getElementById('sum-asi-muj').textContent = asiMuj;
            document.getElementById('sum-asi-tot').textContent = asiTot;
            document.getElementById('sum-ina-var').textContent = inaVar;
            document.getElementById('sum-ina-muj').textContent = inaMuj;
            document.getElementById('sum-ina-tot').textContent = inaTot;
            document.getElementById('sum-asi-med-var').textContent = medVar;
            document.getElementById('sum-asi-med-muj').textContent = medMuj;
            document.getElementById('sum-asi-med-tot').textContent = medTot;
            document.getElementById('sum-por-var').textContent = pctVar;
            document.getElementById('sum-por-muj').textContent = pctMuj;
            document.getElementById('sum-por-tot').textContent = pctTot;
            document.getElementById('dias-habiles').value = dias;

            // 7) Sincronizar hidden inputs
            document.getElementById('h_sum-asi-var').value = asiVar;
            document.getElementById('h_sum-asi-muj').value = asiMuj;
            document.getElementById('h_sum-asi-tot').value = asiTot;
            document.getElementById('h_sum-ina-var').value = inaVar;
            document.getElementById('h_sum-ina-muj').value = inaMuj;
            document.getElementById('h_sum-ina-tot').value = inaTot;
            document.getElementById('h_sum-asi-med-var').value = medVar;
            document.getElementById('h_sum-asi-med-muj').value = medMuj;
            document.getElementById('h_sum-asi-med-tot').value = medTot;
            document.getElementById('h_sum-por-var').value = pctVar;
            document.getElementById('h_sum-por-muj').value = pctMuj;
            document.getElementById('h_sum-por-tot').value = pctTot;
        }

        // Vaciar tabla
        function limpiarCalculadora() {
            document.querySelectorAll('.calculadora .tabla1 tbody tr').forEach(r => {
                r.cells[3].querySelector('input').value = '';
                r.cells[4].querySelector('input').value = '';
            });
            document.getElementById('dias-habiles').value = '';
            recalcular();
        }

        // Eventos
        document.addEventListener('input', e => {
            if (e.target.closest('.calculadora .tabla1') ||
                e.target.name === 'mes') recalcular();
        });
        document.addEventListener('DOMContentLoaded', recalcular);
    </script>
</main>
<?php include('footer.php'); ?>