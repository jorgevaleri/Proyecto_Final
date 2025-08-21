<?php
ob_start();
include('head.php');
include('header.php');
include('../BackEnd/conexion.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// NO iniciar sesión aquí: registrarse no debe loguear al usuario
$errors = [];
$show_step = 1; // 1 = persona (cliente), 2 = usuario (cliente or server validation error)

// Traer selects comunes (escuelas, formaciones)
$escuelas = mysqli_query($conexion, "SELECT escuelas_id, escuelas_nombre FROM escuelas WHERE escuelas_eliminado=0 ORDER BY escuelas_nombre");
$formaciones = mysqli_query($conexion, "SELECT formaciones_profesionales_id, formaciones_profesionales_nombre FROM formaciones_profesionales WHERE formaciones_profesionales_eliminado=0 ORDER BY formaciones_profesionales_nombre");

// Si recibimos el POST final (registrarse), procesar todo en transacción.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'final') {
  // Recoger datos
  $ape = trim($_POST['personas_apellido'] ?? '');
  $nom = trim($_POST['personas_nombre'] ?? '');
  $dni = trim($_POST['personas_dni'] ?? '');
  $fn  = trim($_POST['personas_fechnac'] ?? '');
  $sex = trim($_POST['personas_sexo'] ?? '');

  $inst_tipo = trim($_POST['inst_tipo'] ?? '');
  $inst_escuela = (int)($_POST['inst_escuela'] ?? 0);
  $inst_formacion = (int)($_POST['inst_formacion'] ?? 0);

  $telefonos_numero = $_POST['telefonos_numero'] ?? [];
  $telefonos_descripcion = $_POST['telefonos_descripcion'] ?? [];
  $telefonos_pred = isset($_POST['telefonos_predeterminado']) ? (int)$_POST['telefonos_predeterminado'] : 0;

  $domicilios_calle = $_POST['domicilios_calle'] ?? [];
  $domicilios_descripcion = $_POST['domicilios_descripcion'] ?? [];
  $domicilios_lat = $_POST['domicilios_latitud'] ?? [];
  $domicilios_lng = $_POST['domicilios_longitud'] ?? [];
  $domicilios_pred = isset($_POST['domicilios_predeterminado']) ? (int)$_POST['domicilios_predeterminado'] : 0;

  $email = trim($_POST['usuarios_email'] ?? '');
  $pass = $_POST['usuarios_clave'] ?? '';
  $pass_conf = $_POST['usuarios_clave_conf'] ?? '';
  $rol = $_POST['usuarios_rol'] ?? 'DOCENTE';

  // Validaciones básicas persona/usuario
  if ($ape === '' || $nom === '' || $dni === '') $errors[] = 'Completá apellido, nombre y DNI.';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresá un email válido.';
  if ($pass === '' || $pass_conf === '' ) $errors[] = 'Completá ambos campos de contraseña.';
  if ($pass !== $pass_conf) $errors[] = 'Las contraseñas no coinciden.';
  if (strlen($pass) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres.';

  // Validaciones institucional (solo Director o Docente permitidos)
  if (!in_array($inst_tipo, ['Director','Docente'])) {
    $errors[] = 'Seleccioná tipo institucional válido (Director o Docente).';
  } else {
    if ($inst_tipo === 'Director' && $inst_escuela <= 0) {
      $errors[] = 'Seleccioná la escuela para el Director.';
    }
    if ($inst_tipo === 'Docente') {
      if ($inst_escuela <= 0) $errors[] = 'Seleccioná la escuela para el Docente.';
      if ($inst_formacion <= 0) $errors[] = 'Seleccioná la formación profesional para el Docente.';
    }
  }

  // Evitar email duplicado en usuarios activos
  $email_safe = mysqli_real_escape_string($conexion, $email);
  $resEmail = mysqli_query($conexion, "SELECT usuarios_id FROM usuarios WHERE usuarios_email='$email_safe' AND usuarios_eliminado=0 LIMIT 1");
  if ($resEmail && mysqli_num_rows($resEmail) > 0) $errors[] = 'El email ya está en uso por otro usuario.';

  if (count($errors) === 0) {
    // Guardar TODO en transacción
    try {
      mysqli_begin_transaction($conexion);

      // Insert persona
      $ape_s = mysqli_real_escape_string($conexion, $ape);
      $nom_s = mysqli_real_escape_string($conexion, $nom);
      $dni_s = mysqli_real_escape_string($conexion, $dni);
      $fn_s  = mysqli_real_escape_string($conexion, $fn);
      $sex_s = mysqli_real_escape_string($conexion, $sex);

      $sqlP = "INSERT INTO personas (personas_apellido,personas_nombre,personas_dni,personas_fechnac,personas_sexo,personas_eliminado)
               VALUES ('$ape_s','$nom_s','$dni_s','$fn_s','$sex_s',0)";
      mysqli_query($conexion, $sqlP);
      $persona_id = mysqli_insert_id($conexion);

      // Domicilios
      foreach ($domicilios_calle as $i => $calle) {
        $c  = mysqli_real_escape_string($conexion, $calle);
        $d  = mysqli_real_escape_string($conexion, $domicilios_descripcion[$i] ?? '');
        $la = mysqli_real_escape_string($conexion, $domicilios_lat[$i] ?? '');
        $ln = mysqli_real_escape_string($conexion, $domicilios_lng[$i] ?? '');
        $pr = ($i == (int)$domicilios_pred) ? 1 : 0;
        mysqli_query($conexion, "INSERT INTO domicilios (domicilios_calle,domicilios_descripcion,domicilios_latitud,domicilios_longitud,domicilios_predeterminado,personas_id)
          VALUES ('$c','$d','$la','$ln',$pr,$persona_id)");
      }

      // Telefonos
      foreach ($telefonos_numero as $i => $num) {
        $n = mysqli_real_escape_string($conexion, $num);
        $d = mysqli_real_escape_string($conexion, $telefonos_descripcion[$i] ?? '');
        $pr = ($i == (int)$telefonos_pred) ? 1 : 0;
        mysqli_query($conexion, "INSERT INTO telefonos (telefonos_numero,telefonos_descripcion,telefonos_predeterminado,personas_id)
          VALUES ('$n','$d',$pr,$persona_id)");
      }

      // Institucional
      // borrar previos por si acaso (aunque persona es nueva)
      mysqli_query($conexion, "DELETE FROM institucional WHERE personas_id=$persona_id");
      if ($inst_tipo === 'Director') {
        $inst_esc = (int) $inst_escuela;
        mysqli_query($conexion, "INSERT INTO institucional (institucional_tipo, escuelas_id, personas_id) VALUES ('Director',$inst_esc,$persona_id)");
      } elseif ($inst_tipo === 'Docente') {
        $inst_esc = (int) $inst_escuela;
        $inst_form = (int) $inst_formacion;
        mysqli_query($conexion, "INSERT INTO institucional (institucional_tipo, escuelas_id, formaciones_profesionales_id, personas_id) VALUES ('Docente',$inst_esc,$inst_form,$persona_id)");
      }

      // Usuario
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $hash_s = mysqli_real_escape_string($conexion, $hash);
      $rol_s = mysqli_real_escape_string($conexion, $rol);

      $sqlU = "INSERT INTO usuarios (personas_id, usuarios_email, usuarios_clave, usuarios_eliminado, usuarios_rol)
               VALUES ($persona_id, '$email_safe', '$hash_s', 0, '$rol_s')";
      mysqli_query($conexion, $sqlU);

      // Commit
      mysqli_commit($conexion);

      // Redirigir a logeo.php
      header('Location: logeo.php', true, 303);
      exit;
    } catch (Exception $e) {
      mysqli_rollback($conexion);
      $errors[] = 'Error al guardar: ' . $e->getMessage();
      $show_step = 2;
    }
  } else {
    // Mostrar errores y el paso 2 para que el usuario los corrija
    $show_step = 2;
  }
} // end final POST

// Si NO es POST final, la interacción ocurre en el cliente (JS) entre pasos 1 y 2.
// Para mantener valores cuando hubo error, vamos a reutilizar $_POST en el HTML.
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrarse</title>
  <link rel="stylesheet" href="CSS/style_common.css">
  <link rel="stylesheet" href="CSS/style_app.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <script defer src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
</head>
<body>
  <main class="fp-page">
    <h1>Registro</h1>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <?php foreach ($errors as $err): ?>
          <p><?= htmlspecialchars($err) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Un único form que al final hace POST con step=final -->
    <form id="regForm" method="post">
      <input type="hidden" name="step" value="final"> <!-- siempre final en el POST real -->

      <!-- STEP 1: Persona (cliente) -->
      <section id="step1" style="display: <?= $show_step === 1 ? 'block' : 'none' ?>">
        <h2>Paso 1 — Datos Personales</h2>
        <label>Apellido:<br>
          <input name="personas_apellido" id="personas_apellido" required value="<?= htmlspecialchars($_POST['personas_apellido'] ?? '') ?>">
        </label><br>
        <label>Nombre:<br>
          <input name="personas_nombre" id="personas_nombre" required value="<?= htmlspecialchars($_POST['personas_nombre'] ?? '') ?>">
        </label><br>
        <label>DNI:<br>
          <input name="personas_dni" id="personas_dni" required value="<?= htmlspecialchars($_POST['personas_dni'] ?? '') ?>">
        </label><br>
        <label>Fecha Nac.:<br>
          <input type="date" name="personas_fechnac" id="personas_fechnac" required value="<?= htmlspecialchars($_POST['personas_fechnac'] ?? '') ?>">
        </label><br>
        <label>Sexo:<br>
          <select name="personas_sexo" id="personas_sexo" required>
            <option value="">--</option>
            <option value="Masculino" <?= (isset($_POST['personas_sexo']) && $_POST['personas_sexo']=='Masculino')?'selected':'' ?>>Masculino</option>
            <option value="Femenino"  <?= (isset($_POST['personas_sexo']) && $_POST['personas_sexo']=='Femenino')?'selected':'' ?>>Femenino</option>
          </select>
        </label><br>

        <!-- DOMICILIOS -->
        <h3>Domicilios</h3>
        <button type="button" id="addDom">Agregar domicilio</button>
        <div id="doms">
          <?php
          $doms_post = $_POST['domicilios_calle'] ?? [];
          if (!empty($doms_post) && is_array($doms_post)):
            foreach ($doms_post as $i => $val):
              $c = htmlspecialchars($val);
              $desc = htmlspecialchars($_POST['domicilios_descripcion'][$i] ?? '');
              $lat = htmlspecialchars($_POST['domicilios_latitud'][$i] ?? '');
              $lng = htmlspecialchars($_POST['domicilios_longitud'][$i] ?? '');
              $pred = (isset($_POST['domicilios_predeterminado']) && (int)$_POST['domicilios_predeterminado'] === $i) ? 'checked' : '';
          ?>
            <div class="dom-block">
              <?php if ($i > 0): ?><button type="button" class="del-dom">❌</button><?php endif; ?>
              <label>Calle y número<br><input name="domicilios_calle[]" placeholder="Calle y número" required value="<?= $c ?>"></label>
              <label>Descripción<br><input name="domicilios_descripcion[]" placeholder="Descripción" value="<?= $desc ?>"></label>
              <label>Predeterminado <input type="radio" name="domicilios_predeterminado" value="<?= $i ?>" <?= $pred ?>></label>
              <label>Buscar dirección<br><input class="map-search" placeholder="Buscar dirección"></label>
              <button type="button" class="btn-search">Buscar</button>
              <div class="map" style="height:150px;"></div>
              <input type="hidden" name="domicilios_latitud[]" value="<?= $lat ?>">
              <input type="hidden" name="domicilios_longitud[]" value="<?= $lng ?>">
            </div>
          <?php
            endforeach;
          else:
          ?>
            <div class="dom-block">
              <label>Calle y número<br><input name="domicilios_calle[]" placeholder="Calle y número" required></label>
              <label>Descripción<br><input name="domicilios_descripcion[]" placeholder="Descripción"></label>
              <label>Predeterminado <input type="radio" name="domicilios_predeterminado" value="0" checked></label>
              <label>Buscar dirección<br><input class="map-search" placeholder="Buscar dirección"></label>
              <button type="button" class="btn-search">Buscar</button>
              <div class="map" style="height:150px;"></div>
              <input type="hidden" name="domicilios_latitud[]">
              <input type="hidden" name="domicilios_longitud[]">
            </div>
          <?php endif; ?>
        </div>

        <!-- TELÉFONOS -->
        <h3>Teléfonos</h3>
        <button type="button" id="addTel">Agregar teléfono</button>
        <div id="tels">
          <?php
          $tels_post = $_POST['telefonos_numero'] ?? [];
          if (!empty($tels_post) && is_array($tels_post)):
            foreach ($tels_post as $i => $val):
              $num = htmlspecialchars($val);
              $desc = htmlspecialchars($_POST['telefonos_descripcion'][$i] ?? '');
              $pred = (isset($_POST['telefonos_predeterminado']) && (int)$_POST['telefonos_predeterminado'] === $i) ? 'checked' : '';
          ?>
            <div class="tel-block">
              <?php if ($i > 0): ?><button type="button" class="del-tel">❌</button><?php endif; ?>
              <label>Número<br><input name="telefonos_numero[]" placeholder="Número" required value="<?= $num ?>"></label>
              <label>Descripción<br><input name="telefonos_descripcion[]" placeholder="Descripción" value="<?= $desc ?>"></label>
              <label>Predeterminado <input type="radio" name="telefonos_predeterminado" value="<?= $i ?>" <?= $pred ?>></label>
            </div>
          <?php
            endforeach;
          else:
          ?>
            <div class="tel-block">
              <label>Número<br><input name="telefonos_numero[]" placeholder="Número" required></label>
              <label>Descripción<br><input name="telefonos_descripcion[]" placeholder="Descripción"></label>
              <label>Predeterminado <input type="radio" name="telefonos_predeterminado" value="0" checked></label>
            </div>
          <?php endif; ?>
        </div>

        <!-- INSTITUCIONAL -->
        <h3>Institucional</h3>
        <label>Tipo de Persona:<br>
          <select name="inst_tipo" id="inst_tipo" required>
            <option value="">--</option>
            <option value="Director" <?= (isset($_POST['inst_tipo']) && $_POST['inst_tipo']=='Director')?'selected':'' ?>>Director</option>
            <option value="Docente"  <?= (isset($_POST['inst_tipo']) && $_POST['inst_tipo']=='Docente')?'selected':'' ?>>Docente</option>
            <!-- Alumno eliminado -->
          </select>
        </label><br>

        <label id="label_escuela">Escuela:<br>
          <select name="inst_escuela" id="inst_escuela">
            <option value="">--</option>
            <?php mysqli_data_seek($escuelas, 0); while ($e = mysqli_fetch_assoc($escuelas)): ?>
              <option value="<?= $e['escuelas_id'] ?>" <?= (isset($_POST['inst_escuela']) && $_POST['inst_escuela']==$e['escuelas_id'])?'selected':'' ?>>
                <?= htmlspecialchars($e['escuelas_nombre']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </label><br>

        <label id="label_formacion">Formación Profesional:<br>
          <select name="inst_formacion" id="inst_formacion">
            <option value="">--</option>
            <?php mysqli_data_seek($formaciones, 0); while ($f = mysqli_fetch_assoc($formaciones)): ?>
              <option value="<?= $f['formaciones_profesionales_id'] ?>" <?= (isset($_POST['inst_formacion']) && $_POST['inst_formacion']==$f['formaciones_profesionales_id'])?'selected':'' ?>>
                <?= htmlspecialchars($f['formaciones_profesionales_nombre']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </label><br>

        <!-- BOTÓN "Siguiente" (no envía el formulario) -->
        <button type="button" id="toStep2">Siguiente</button>
      </section>

      <!-- STEP 2: Usuario (cliente) -->
      <section id="step2" style="display: <?= $show_step === 2 ? 'block' : 'none' ?>">
        <h2>Paso 2 — Datos de Usuario</h2>

        <p><strong>Persona:</strong>
          <?= htmlspecialchars($_POST['personas_apellido'] ?? '') ?>,
          <?= htmlspecialchars($_POST['personas_nombre'] ?? '') ?>
        </p>

        <label>Rol<br>
          <select name="usuarios_rol" required>
            <option value="ADMINISTRADOR">ADMINISTRADOR</option>
            <option value="DIRECTOR">DIRECTOR</option>
            <option value="DOCENTE" selected>DOCENTE</option>
          </select>
        </label><br>

        <label>Email<br>
          <input type="email" name="usuarios_email" id="usuarios_email" required value="<?= htmlspecialchars($_POST['usuarios_email'] ?? '') ?>">
        </label><br>

        <label>Contraseña<br>
          <input type="password" name="usuarios_clave" id="usuarios_clave" required>
          <i class="bi bi-eye-slash toggle-password" toggle="#usuarios_clave" title="Mostrar/Ocultar"></i>
        </label><br>

        <label>Confirmar Contraseña<br>
          <input type="password" name="usuarios_clave_conf" id="usuarios_clave_conf" required>
          <i class="bi bi-eye-slash toggle-password" toggle="#usuarios_clave_conf" title="Mostrar/Ocultar"></i>
        </label><br>

        <!-- BOTONES: Volver (cliente) y Registrarse (envía POST final) -->
        <button type="button" id="backToStep1">Volver</button>
        <button type="submit" id="btnRegister">Registrarse</button>
      </section>
    </form>

  </main>

  <?php include('footer.php'); ?>

  <script>
    // Manejo de pasos en cliente
    document.addEventListener('DOMContentLoaded', () => {
      const step1 = document.getElementById('step1');
      const step2 = document.getElementById('step2');
      const toStep2 = document.getElementById('toStep2');
      const backToStep1 = document.getElementById('backToStep1');

      const regForm = document.getElementById('regForm');

      toStep2 && toStep2.addEventListener('click', () => {
        // Validaciones básicas en cliente antes de pasar a paso 2
        const ape = document.getElementById('personas_apellido').value.trim();
        const nom = document.getElementById('personas_nombre').value.trim();
        const dni = document.getElementById('personas_dni').value.trim();
        if (!ape || !nom || !dni) {
          return alert('Completá apellido, nombre y DNI antes de continuar.');
        }
        // mostrar paso 2
        step1.style.display = 'none';
        step2.style.display = 'block';
      });

      backToStep1 && backToStep1.addEventListener('click', () => {
        step2.style.display = 'none';
        step1.style.display = 'block';
        // Los valores permanecen porque son los mismos inputs del form
      });

      // Toggle mostrar/ocultar contraseña
      document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', () => {
          const inp = document.querySelector(btn.getAttribute('toggle'));
          if (inp.type === 'password') {
            inp.type = 'text';
            btn.classList.replace('bi-eye-slash', 'bi-eye');
          } else {
            inp.type = 'password';
            btn.classList.replace('bi-eye', 'bi-eye-slash');
          }
        });
      });

      // === DOMICILIOS: clonación y mapas ===
      const doms = document.getElementById('doms'),
        baseDom = doms ? doms.querySelector('.dom-block') : null,
        btnDom = document.getElementById('addDom');

      function initDom(block, idx) {
        const delBtn = block.querySelector('.del-dom');
        if (delBtn) delBtn.addEventListener('click', () => block.remove());

        const mapEl = block.querySelector('.map'),
          search = block.querySelector('.map-search'),
          btnSearch = block.querySelector('.btn-search'),
          latInput = block.querySelector('input[name="domicilios_latitud[]"]'),
          lngInput = block.querySelector('input[name="domicilios_longitud[]"]');

        if (typeof L === 'undefined') return;

        mapEl.innerHTML = '';
        const map = L.map(mapEl).setView([-28.4682, -65.7795], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '© OpenStreetMap'
        }).addTo(map);

        const marker = L.marker(map.getCenter(), { draggable: true })
          .addTo(map)
          .on('moveend', e => {
            const { lat, lng } = e.target.getLatLng();
            latInput.value = lat;
            lngInput.value = lng;
          });

        if (latInput.value && lngInput.value) {
          const la = parseFloat(latInput.value), lo = parseFloat(lngInput.value);
          marker.setLatLng([la, lo]);
          map.setView([la, lo], 15);
        }

        btnSearch && btnSearch.addEventListener('click', () => {
          const q = search.value.trim();
          if (!q) return alert('Ingresa una dirección');
          fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(data => {
              if (!data[0]) return alert('Dirección no encontrada');
              const la = parseFloat(data[0].lat), lo = parseFloat(data[0].lon);
              marker.setLatLng([la, lo]);
              map.setView([la, lo], 15);
              latInput.value = la;
              lngInput.value = lo;
            })
            .catch(() => alert('Error al buscar en el mapa'));
        });
      }

      if (doms) {
        doms.querySelectorAll('.dom-block').forEach((blk, i) => initDom(blk, i));
        btnDom && btnDom.addEventListener('click', () => {
          const idx = doms.children.length;
          const clone = baseDom.cloneNode(true);
          clone.querySelectorAll('input:not([type="radio"])').forEach(i => i.value = '');
          const radio = clone.querySelector('input[type="radio"]');
          if (radio) radio.value = idx;
          if (!clone.querySelector('.del-dom')) {
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'del-dom';
            del.textContent = '❌';
            clone.insertBefore(del, clone.firstChild);
          }
          doms.appendChild(clone);
          initDom(clone, idx);
        });
      }

      // TELEFONOS
      const tels = document.getElementById('tels'),
        baseTel = tels ? tels.querySelector('.tel-block') : null,
        btnTel = document.getElementById('addTel');

      function initTel(block, idx) {
        const delBtn = block.querySelector('.del-tel');
        if (delBtn) delBtn.addEventListener('click', () => block.remove());
      }

      if (tels) {
        tels.querySelectorAll('.tel-block').forEach((blk, i) => initTel(blk, i));
        btnTel && btnTel.addEventListener('click', () => {
          const idx = tels.children.length;
          const clone = baseTel.cloneNode(true);
          clone.querySelectorAll('input:not([type="radio"])').forEach(i => i.value = '');
          const radio = clone.querySelector('input[type="radio"]');
          if (radio) radio.value = idx;
          if (!clone.querySelector('.del-tel')) {
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'del-tel';
            del.textContent = '❌';
            clone.insertBefore(del, clone.firstChild);
          }
          tels.appendChild(clone);
          initTel(clone, idx);
        });
      }

      // Institucional toggle (actualizado: sin 'Alumno')
      const tipoSel = document.getElementById('inst_tipo');
      const escuelaLab = document.getElementById('label_escuela');
      const formacionLab = document.getElementById('label_formacion');

      function toggleInstitucional() {
        const val = tipoSel ? tipoSel.value : '';
        if (val === 'Director') {
          escuelaLab && (escuelaLab.style.display = 'block');
          formacionLab && (formacionLab.style.display = 'none');
        } else if (val === 'Docente') {
          escuelaLab && (escuelaLab.style.display = 'block');
          formacionLab && (formacionLab.style.display = 'block');
        } else {
          escuelaLab && (escuelaLab.style.display = 'none');
          formacionLab && (formacionLab.style.display = 'none');
        }
      }
      tipoSel && tipoSel.addEventListener('change', toggleInstitucional);
      toggleInstitucional();
    });
  </script>
</body>
</html>
<?php ob_end_flush(); ?>
