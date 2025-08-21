<?php
session_start();

// Evitar que el navegador muestre páginas desde cache después del logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Comprobar login: adaptá 'user_id' por la variable de sesión que uses
if (empty($_SESSION['user_id'])) {
  header('Location: index.php', true, 303);
  exit;
}
?>

<?php
ob_start();
include('head.php');
include('header.php');
include('menu_lateral.php');
include('../BackEnd/conexion.php');

$action = $_GET['action'] ?? 'list';
$id     = $_GET['id']     ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Escuelas</title>

  <!-- ESTILOS -->
   <link rel="stylesheet" href="CSS/style_common.css">
  <link rel="stylesheet" href="CSS/style_app.css">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <!-- Leaflet JS con defer -->
  <script defer src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
</head>
<body>
  <main class="fp-page">

    <?php switch($action):

      // ────────────────────────────────────────────
      // AGREGAR
      case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          $nombre    = trim($_POST['escuelas_nombre']);
          $cue       = trim($_POST['escuelas_cue']);
          $domreal   = trim($_POST['domicilio_calle']);
          $mapsearch = trim($_POST['map_search']);
          $lat       = trim($_POST['domicilio_lat']);
          $lng       = trim($_POST['domicilio_lng']);

          if ($nombre === '') {
            $error = 'Debe ingresar un nombre.';
          } elseif ($cue === '' || !is_numeric($cue)) {
            $error = 'Debe ingresar un CUE numérico.';
          } elseif ($domreal === '') {
            $error = 'Debe ingresar la dirección real.';
          } elseif ($mapsearch === '') {
            $error = 'Debes ingresar texto para el mapa.';
          } elseif ($lat === '' || $lng === '') {
            $error = 'Espera a que se calculen las coordenadas.';
          } else {
            // Inserto domicilio
            $sqlDom = "
              INSERT INTO domicilios
                (domicilios_calle, domicilios_latitud, domicilios_longitud)
              VALUES (
                '".mysqli_real_escape_string($conexion,$domreal)."',
                '$lat','$lng'
              )";
            if (!mysqli_query($conexion, $sqlDom)) {
              $error = "Error domicilio: " . mysqli_error($conexion);
            } else {
              $domId = mysqli_insert_id($conexion);
              // Inserto escuela
              $sqlEsc = "
                INSERT INTO escuelas
                  (escuelas_nombre, escuelas_cue, domicilios_id)
                VALUES (
                  '".mysqli_real_escape_string($conexion,$nombre)."',
                  '".mysqli_real_escape_string($conexion,$cue)."',
                  '$domId'
                )";
              if (!mysqli_query($conexion, $sqlEsc)) {
                $error = "Error escuela: " . mysqli_error($conexion);
              } else {
                header("Location: escuelas.php");
                exit;
              }
            }
          }
        }
    ?>
      <h1 class="title">Agregar Escuela</h1>
      <?php if (!empty($error)): ?><p class="error"><?= $error ?></p><?php endif ?>

      <form id="escuelaForm" method="post" class="botones">
        <li class="boton-agregar">
          <div class="input-label">Nombre</div>
          <input name="escuelas_nombre" placeholder="Nombre"
                 value="<?= htmlspecialchars($_POST['escuelas_nombre'] ?? '') ?>">
        </li>
        <li class="boton-agregar">
          <div class="input-label">CUE</div>
          <input name="escuelas_cue" placeholder="CUE"
                 value="<?= htmlspecialchars($_POST['escuelas_cue'] ?? '') ?>">
        </li>
        <li class="boton-agregar" style="flex:2;">
          <div class="input-label">Dirección Real</div>
          <input name="domicilio_calle" id="domicilio_calle"
                 placeholder="Calle y número reales"
                 value="<?= htmlspecialchars($_POST['domicilio_calle'] ?? '') ?>"
                 style="width:100%;">
        </li>
        <li class="boton-agregar" style="flex:2;">
          <div class="input-label">Buscar en Mapa</div>
          <input name="map_search" id="map_search"
                 placeholder="Buscar en el mapa"
                 value="<?= htmlspecialchars($_POST['map_search'] ?? '') ?>"
                 style="width:100%;">
        </li>

        <input type="hidden" name="domicilio_lat" id="domicilio_lat"
               value="<?= htmlspecialchars($_POST['domicilio_lat'] ?? '') ?>">
        <input type="hidden" name="domicilio_lng" id="domicilio_lng"
               value="<?= htmlspecialchars($_POST['domicilio_lng'] ?? '') ?>">

        <div id="map" style="height:300px; width:100%; margin:1rem 0;"></div>

        <li class="boton-agregar"><button type="submit">Guardar</button></li>
        <li class="boton-volver">
          <a href="escuelas.php"><i class="bi bi-arrow-left-circle"></i> Cancelar</a>
        </li>
      </form>

      <script defer>
      document.addEventListener('DOMContentLoaded', function(){
        const map = L.map('map').setView([-28.4682,-65.7795],13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
          attribution:'&copy; OpenStreetMap'
        }).addTo(map);

        // Marker draggable
        const marker = L.marker([-28.4682,-65.7795],{ draggable:true })
          .addTo(map)
          .on('moveend', e => {
            const { lat, lng } = e.target.getLatLng();
            document.getElementById('domicilio_lat').value = lat;
            document.getElementById('domicilio_lng').value = lng;
          });

        function geocode(q){
          fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}`)
            .then(r=>r.json()).then(data=>{
              if(!data[0]) return alert('Dirección no encontrada');
              const lat = parseFloat(data[0].lat),
                    lon = parseFloat(data[0].lon);
              marker.setLatLng([lat,lon]);
              map.setView([lat,lon],16);
              document.getElementById('domicilio_lat').value = lat;
              document.getElementById('domicilio_lng').value = lon;
            })
            .catch(err=>{
              console.error('Error al geocodificar:', err);
              alert('Error al geocodificar la dirección.');
            });
        }

        const ms = document.getElementById('map_search');
        ms.addEventListener('change', ()=> geocode(ms.value));
        ms.addEventListener('keydown', e=>{
          if(e.key==='Enter'){
            e.preventDefault();
            geocode(ms.value);
          }
        });
      });
      </script>
    <?php
        break;

      // ────────────────────────────────────────────
// EDITAR
// ────────────────────────────────────────────
case 'edit':
  if (!$id) {
    header("Location: escuelas.php");
    exit;
  }

  // 1) Cargo datos actuales
  $r = mysqli_fetch_assoc(mysqli_query($conexion, "
    SELECT 
      e.escuelas_nombre, 
      e.escuelas_cue,
      d.domicilios_id, 
      d.domicilios_calle,
      d.domicilios_latitud, 
      d.domicilios_longitud
    FROM escuelas e
    LEFT JOIN domicilios d 
      ON e.domicilios_id = d.domicilios_id
    WHERE e.escuelas_id = '$id'
  "));
  if (!$r) {
    echo "<p>Error al cargar datos.</p>";
    break;
  }

  // 2) Pre‑llenar POST simulando la primera carga
  $_POST['escuelas_nombre']  = $_POST['escuelas_nombre']  ?? $r['escuelas_nombre'];
  $_POST['escuelas_cue']     = $_POST['escuelas_cue']     ?? $r['escuelas_cue'];
  $_POST['domicilio_calle']  = $_POST['domicilio_calle']  ?? $r['domicilios_calle'];
  $_POST['map_search']       = $_POST['map_search']       ?? $r['domicilios_calle'];
  $_POST['domicilio_lat']    = $_POST['domicilio_lat']    ?? $r['domicilios_latitud'];
  $_POST['domicilio_lng']    = $_POST['domicilio_lng']    ?? $r['domicilios_longitud'];
  // Oculto el id de domicilio
  $_POST['domicilios_id']    = $r['domicilios_id'];

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['escuelas_nombre']);
    $cue          = trim($_POST['escuelas_cue']);
    $domreal      = trim($_POST['domicilio_calle']);
    $mapsearch    = trim($_POST['map_search']);
    $lat          = trim($_POST['domicilio_lat']);
    $lng          = trim($_POST['domicilio_lng']);
    $domiciliosId = intval($_POST['domicilios_id']);

    // Validaciones básicas
    if ($nombre === '') {
      $error = 'Debe ingresar un nombre.';
    } elseif ($cue === '' || !is_numeric($cue)) {
      $error = 'Debe ingresar un CUE numérico.';
    } elseif ($domreal === '') {
      $error = 'Debe ingresar la dirección real.';
    } elseif ($mapsearch === '') {
      $error = 'Debes ingresar texto para el mapa.';
    } elseif ($lat === '' || $lng === '') {
      $error = 'Espera a que se calculen las coordenadas.';
    } else {
      // 3) Actualizo domicilio
      $updDom = "
        UPDATE domicilios SET
          domicilios_calle   = '".mysqli_real_escape_string($conexion,$domreal)."',
          domicilios_latitud = '$lat',
          domicilios_longitud= '$lng'
        WHERE domicilios_id = $domiciliosId
      ";
      mysqli_query($conexion, $updDom);
      if (mysqli_errno($conexion)) {
        $error = "Error al actualizar domicilio: " . mysqli_error($conexion);
      } else {
        // 4) Actualizo escuela
        $updEsc = "
          UPDATE escuelas SET
            escuelas_nombre = '".mysqli_real_escape_string($conexion,$nombre)."',
            escuelas_cue    = '".mysqli_real_escape_string($conexion,$cue)."'
          WHERE escuelas_id = '$id'
        ";
        mysqli_query($conexion, $updEsc);
        if (mysqli_errno($conexion)) {
          $error = "Error al actualizar escuela: " . mysqli_error($conexion);
        } else {
          header("Location: escuelas.php");
          exit;
        }
      }
    }
  }
?>
  <h1 class="title">Editar Escuela</h1>
  <?php if (!empty($error)): ?><p class="error"><?= $error ?></p><?php endif ?>

  <form id="escuelaForm" method="post" class="botones">
    <li class="boton-agregar">
      <div class="input-label">Nombre</div>
      <input name="escuelas_nombre" placeholder="Nombre"
             value="<?= htmlspecialchars($_POST['escuelas_nombre']) ?>">
    </li>

    <li class="boton-agregar">
      <div class="input-label">CUE</div>
      <input name="escuelas_cue" placeholder="CUE"
             value="<?= htmlspecialchars($_POST['escuelas_cue']) ?>">
    </li>

    <li class="boton-agregar" style="flex:2;">
      <div class="input-label">Dirección real</div>
      <input name="domicilio_calle" id="domicilio_calle"
             placeholder="Calle y número reales"
             value="<?= htmlspecialchars($_POST['domicilio_calle']) ?>"
             style="width:100%;">
    </li>

    <li class="boton-agregar" style="flex:2;">
      <div class="input-label">Buscar en mapa</div>
      <input name="map_search" id="map_search"
             placeholder="Buscar en el mapa"
             value="<?= htmlspecialchars($_POST['map_search']) ?>"
             style="width:100%;">
    </li>

    <!-- campos ocultos -->
    <input type="hidden" name="domicilio_lat" id="domicilio_lat"
           value="<?= htmlspecialchars($_POST['domicilio_lat']) ?>">
    <input type="hidden" name="domicilio_lng" id="domicilio_lng"
           value="<?= htmlspecialchars($_POST['domicilio_lng']) ?>">
    <input type="hidden" name="domicilios_id"
           value="<?= htmlspecialchars($_POST['domicilios_id']) ?>">

    <div id="map" style="height:300px; width:100%; margin:1rem 0;"></div>

    <li class="boton-agregar"><button type="submit">Guardar Cambios</button></li>
    <li class="boton-volver">
      <a href="escuelas.php"><i class="bi bi-arrow-left-circle"></i> Cancelar</a>
    </li>
  </form>

  <script defer>
  document.addEventListener('DOMContentLoaded', function(){
    // inicialización del mapa y marcador igual que en 'add',
    // pero centrado en las coordenadas ya guardadas en el campo oculto:
    let lat = parseFloat("<?= $r['domicilios_latitud'] ?>"),
        lon = parseFloat("<?= $r['domicilios_longitud'] ?>");
    const center = [lat, lon];
    const map = L.map('map').setView(center, 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
      attribution:'&copy; OpenStreetMap'
    }).addTo(map);

    let marker = L.marker(center, {draggable:true})
      .addTo(map)
      .on('moveend', e=>{
        const {lat,lng} = e.target.getLatLng();
        document.getElementById('domicilio_lat').value = lat;
        document.getElementById('domicilio_lng').value = lng;
      });

    function geocode(q){
      fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}`)
        .then(r=>r.json()).then(data=>{
          if(!data[0]) return alert('Dirección no encontrada.');
          const la = parseFloat(data[0].lat),
                lo = parseFloat(data[0].lon);
          marker.setLatLng([la,lo]);
          map.setView([la,lo],16);
          document.getElementById('domicilio_lat').value = la;
          document.getElementById('domicilio_lng').value = lo;
        });
    }

    const ms = document.getElementById('map_search');
    ms.addEventListener('change', ()=>geocode(ms.value));
    ms.addEventListener('keydown', e=>{
      if(e.key==='Enter'){ e.preventDefault(); geocode(ms.value); }
    });
  });
  </script>
<?php
  break;

      // ────────────────────────────────────────────
      // ELIMINAR
      case 'delete':
        if (!$id) { header("Location: escuelas.php"); exit; }
        // muestro mismos datos que en 'view'
        $r2 = mysqli_fetch_assoc(mysqli_query($conexion, "
          SELECT
            e.escuelas_nombre   AS Nombre,
            e.escuelas_cue      AS CUE,
            d.domicilios_calle  AS Domicilio
          FROM escuelas e
          LEFT JOIN domicilios d ON e.domicilios_id=d.domicilios_id
          WHERE e.escuelas_id='$id'
        "));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          // marco eliminado escuela + domicilio
          mysqli_query($conexion, "
            UPDATE escuelas SET escuelas_eliminado='1' WHERE escuelas_id='$id'
          ");
          $domId = mysqli_fetch_row(mysqli_query($conexion, "
            SELECT domicilios_id FROM escuelas WHERE escuelas_id='$id'
          "))[0];
          mysqli_query($conexion, "
            UPDATE domicilios SET domicilios_eliminado='1' WHERE domicilios_id='$domId'
          ");
          header("Location: escuelas.php");
          exit;
        }
    ?>
      <h1 class="title">Eliminar Escuela “<?= htmlspecialchars($r2['Nombre']) ?>”</h1>
      <div class="contenedor">
        <table class="table">
          <tr><th style="width:30%;text-align:left;">Nombre</th><td><?= htmlspecialchars($r2['Nombre']) ?></td></tr>
          <tr><th style="text-align:left;">CUE</th><td><?= htmlspecialchars($r2['CUE']) ?></td></tr>
          <tr><th style="text-align:left;">Domicilio</th><td><?= htmlspecialchars($r2['Domicilio']) ?></td></tr>
        </table>
      </div>
      <form method="post" class="botones">
        <li class="boton-agregar"><button type="submit">Sí, eliminar</button></li>
        <li class="boton-volver"><a href="escuelas.php"><i class="bi bi-arrow-left-circle"></i> No, cancelar</a></li>
      </form>
    <?php
        break;

      // ────────────────────────────────────────────
      // MOSTRAR DETALLE
      case 'view':
        if (!$id) { header("Location: escuelas.php"); exit; }
        $r2 = mysqli_fetch_assoc(mysqli_query($conexion, "
          SELECT
            e.escuelas_nombre   AS Nombre,
            e.escuelas_cue      AS CUE,
            d.domicilios_calle  AS Domicilio,
            d.domicilios_latitud  AS Latitud,
            d.domicilios_longitud AS Longitud
          FROM escuelas e
          LEFT JOIN domicilios d ON e.domicilios_id=d.domicilios_id
          WHERE e.escuelas_id='$id'
        "));
    ?>
      <h1 class="title">Detalles de “<?= htmlspecialchars($r2['Nombre']) ?>”</h1>
      <div class="contenedor">
        <table class="table">
          <tr><th style="width:30%;text-align:left;">Nombre</th><td><?= htmlspecialchars($r2['Nombre']) ?></td></tr>
          <tr><th style="text-align:left;">CUE</th><td><?= htmlspecialchars($r2['CUE']) ?></td></tr>
          <tr><th style="text-align:left;">Domicilio</th><td><?= htmlspecialchars($r2['Domicilio']) ?></td></tr>
        </table>
        <?php if ($r2['Latitud'] && $r2['Longitud']): ?>
          <div style="margin-top:1.5rem;">
            <iframe width="100%" height="350"
                    src="https://maps.google.com/maps?q=<?= $r2['Latitud'] ?>,<?= $r2['Longitud'] ?>&z=15&output=embed"
                    style="border:0;" loading="lazy"></iframe>
          </div>
        <?php endif; ?>
      </div>
    <?php
        break;

      // ────────────────────────────────────────────
      // LISTAR ELIMINADOS
      case 'deleted':
        $res = mysqli_query($conexion,"
          SELECT e.escuelas_id, e.escuelas_nombre, e.escuelas_cue, d.domicilios_id
          FROM escuelas e
          LEFT JOIN domicilios d ON e.domicilios_id=d.domicilios_id
          WHERE e.escuelas_eliminado='1'
        ");
        $cnt = 0;
    ?>
      <h1 class="title">Escuelas Eliminadas</h1>
      <ul class="botones">
        <li class="boton-volver"><a href="escuelas.php"><i class="bi bi-arrow-left-circle"></i> Volver al Listado</a></li>
      </ul>
      <div class="contenedor">
        <table class="table table-striped table-hover">
          <thead><tr><th>#</th><th>Nombre</th><th>CUE</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php while($rw = mysqli_fetch_assoc($res)): ?>
            <tr>
              <td><?= ++$cnt ?></td>
              <td><?= htmlspecialchars($rw['escuelas_nombre']) ?></td>
              <td><?= htmlspecialchars($rw['escuelas_cue']) ?></td>
              <td>
                <a href="escuelas.php?action=restore&id=<?= $rw['escuelas_id'] ?>" title="Restaurar">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php
        break;

      // ────────────────────────────────────────────
// RESTAURAR
case 'restore':
  if($id) {
    // Primero obtengo domicilios_id
    $row = mysqli_fetch_assoc(mysqli_query($conexion, "
      SELECT domicilios_id FROM escuelas WHERE escuelas_id='$id'
    "));
    $domId = $row['domicilios_id'];

    // 1) resto la bandera en escuelas
    mysqli_query($conexion, "
      UPDATE escuelas SET escuelas_eliminado='0' WHERE escuelas_id='$id'
    ");
    // 2) resto la bandera en domicilios
    mysqli_query($conexion, "
      UPDATE domicilios SET domicilios_eliminado='0' WHERE domicilios_id='$domId'
    ");
  }
  // Redirijo de vuelta a la lista de eliminados
  header("Location: escuelas.php?action=deleted");
  exit;
  break;

      // ────────────────────────────────────────────
      // LISTAR NORMALES
      default:
        $res = mysqli_query($conexion,"
          SELECT escuelas_id, escuelas_nombre, escuelas_cue
          FROM escuelas
          WHERE escuelas_eliminado='0'
        ");
        $cnt = 0;
    ?>
      <h1 class="title">Escuelas</h1>
      <ul class="botones">
        <li class="boton-agregar"><a href="escuelas.php?action=add"><i class="bi bi-plus-circle"></i> Agregar</a></li>
        <li class="boton-volver"><a href="escuelas.php?action=deleted"><i class="bi bi-eye-slash"></i> Mostrar Eliminados</a></li>
      </ul>
      <div class="contenedor">
        <table class="table table-striped table-hover">
          <thead><tr><th>#</th><th>Nombre</th><th>CUE</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php while ($rw = mysqli_fetch_assoc($res)): ?>
            <tr>
              <td><?= ++$cnt ?></td>
              <td><?= htmlspecialchars($rw['escuelas_nombre']) ?></td>
              <td><?= htmlspecialchars($rw['escuelas_cue']) ?></td>
              <td>
                <a href="escuelas.php?action=view&id=<?= $rw['escuelas_id'] ?>" title="Ver Más"><i class="bi bi-eye"></i></a>
                <a href="escuelas.php?action=edit&id=<?= $rw['escuelas_id'] ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                <a href="escuelas.php?action=delete&id=<?= $rw['escuelas_id'] ?>" title="Eliminar"><i class="bi bi-trash3" style="color:red;"></i></a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endswitch; ?>

  </main>
  <?php include('footer.php'); ?>
</body>
</html>
<?php ob_end_flush(); ?>
