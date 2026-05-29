<?php
verificar_sesion();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "SICAY";

$mensaje = "";

// ── Respaldo manual (existente) ──
if (isset($_POST['generar_respaldo'])) {
    verificar_csrf($_POST['csrf_token'] ?? '');
    $backup_dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
    if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

    $fecha = date("Y-m-d_H-i-s");
    $archivo_sql = $backup_dir . "respaldo_SICAY_{$fecha}.sql";

    $comando = sprintf(
        '"%s" -h %s -u %s %s > "%s"',
        "C:\\xampp\\mysql\\bin\\mysqldump.exe",
        escapeshellarg($host),
        escapeshellarg($user),
        escapeshellarg($db),
        $archivo_sql
    );

    system($comando, $resultado);

    if ($resultado === 0) {
        if (class_exists('ZipArchive')) {
            $archivo_zip = $backup_dir . "respaldo_SICAY_{$fecha}.zip";
            $zip = new ZipArchive();
            if ($zip->open($archivo_zip, ZipArchive::CREATE) === true) {
                $zip->addFile($archivo_sql, "respaldo_SICAY_{$fecha}.sql");
                $zip->close();
                unlink($archivo_sql);
                $nombre_final = "respaldo_SICAY_{$fecha}.zip";
            } else {
                $nombre_final = "respaldo_SICAY_{$fecha}.sql";
            }
        } else {
            $nombre_final = "respaldo_SICAY_{$fecha}.sql";
        }

        $url_descarga = "index.php?route=backup_auto&action=download&file=" . urlencode($nombre_final);
        $mensaje = "<div class='alert success'>
                        <i class='fas fa-check-circle'></i> Respaldo creado con éxito en almacenamiento seguro.<br>
                        <a href='$url_descarga' class='btn-download-link'>
                            <i class='fas fa-file-download'></i> DESCARGAR
                        </a>
                    </div>";
    } else {
        $mensaje = "<div class='alert error'>
                        <i class='fas fa-exclamation-triangle'></i> Error al generar el respaldo. Verifique los permisos de mysqldump.
                    </div>";
    }
}

// ── Config keys para respaldo automático ──
$config_keys = ['backup_enabled','backup_frequency','backup_retention','backup_last_run','backup_last_file','backup_next_run'];
foreach ($config_keys as $k) {
    $stmt = $con->prepare("SELECT COUNT(*) as c FROM system_config WHERE config_key = ?");
    $stmt->bind_param("s", $k); $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (($r['c'] ?? 0) == 0) {
        $defaults = [
            'backup_enabled' => '0',
            'backup_frequency' => 'daily',
            'backup_retention' => '10',
            'backup_last_run' => 'Nunca',
            'backup_last_file' => '',
            'backup_next_run' => 'No programado'
        ];
        $v = $defaults[$k] ?? '';
        $ins = $con->prepare("INSERT INTO system_config (config_key, config_value) VALUES (?, ?)");
        $ins->bind_param("ss", $k, $v);
        $ins->execute(); $ins->close();
    }
}

$bk_enabled    = get_system_config('backup_enabled', '0');
$bk_frequency  = get_system_config('backup_frequency', 'daily');
$bk_retention  = get_system_config('backup_retention', '10');
$bk_last_run   = get_system_config('backup_last_run', 'Nunca');
$bk_last_file  = get_system_config('backup_last_file', '');
$bk_next_run   = get_system_config('backup_next_run', 'No programado');

$backup_dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
$bk_files = is_dir($backup_dir) ? glob($backup_dir . "respaldo_SICAY_*") : [];
$bk_count = count($bk_files);
$bk_total_size = 0;
$bk_list = [];
foreach ($bk_files as $f) {
    $s = filesize($f);
    $bk_total_size += $s;
    $bk_list[] = [
        'name' => basename($f),
        'size' => $s,
        'size_hr' => $s > 1048576 ? round($s / 1048576, 2) . ' MB' : round($s / 1024, 2) . ' KB',
        'date' => date("Y-m-d H:i:s", filemtime($f))
    ];
}
rsort($bk_list);

$freq_labels = ['hourly' => 'Cada hora', 'daily' => 'Cada día', 'weekly' => 'Cada semana', 'monthly' => 'Cada mes'];
$enabled_checked = $bk_enabled === '1' ? 'checked' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Copia de Seguridad - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/seguridad/respaldo.css">
</head>
<body>

<div id="bubbles"></div>

<div class="backup-layout">

    <!-- ── CARD: Respaldo Manual ── -->
    <div class="card card-wide">
        <i class="fas fa-database icon-db"></i>
        <h2>Copia de Seguridad Manual</h2>
        <p>Este proceso generará un archivo SQL comprimido con toda la estructura y datos del sistema SICAY. El archivo se almacena en <code>storage/backups/</code>, fuera del directorio público del servidor web.</p>

        <?php echo $mensaje; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <button type="submit" name="generar_respaldo" class="btn-respaldo">
                <i class="fas fa-shield-alt"></i> Generar Respaldo Ahora
            </button>
        </form>

        <a href="index.php?route=ajustes" class="btn-volver">
            <i class="fas fa-arrow-left"></i> Volver a Configuración
        </a>
    </div>

    <!-- ── CARD: Respaldo Automático Programado ── -->
    <div class="scheduler-section">
        <div class="scheduler-header">
            <i class="fas fa-clock icon-clock"></i>
            <h2><i class="fas fa-robot"></i> Respaldo Automático Programado</h2>
            <p>Configure respaldos automáticos recurrentes. Se ejecutan en segundo plano cuando alguien usa el sistema, sin necesidad de tareas del sistema operativo.</p>
        </div>

        <div class="msg-banner" id="msgBanner">
            <i class="fas fa-info-circle"></i>
            <span id="msgText"></span>
        </div>

        <!-- Status badges -->
        <div class="status-bar">
            <span class="status-badge">
                <span class="badge-dot <?php echo $bk_enabled === '1' ? 'green' : 'red'; ?>"></span>
                Respaldo: <?php echo $bk_enabled === '1' ? 'ACTIVO' : 'INACTIVO'; ?>
            </span>
            <span class="status-badge">
                <span class="badge-dot blue"></span>
                Último: <?php echo hsc($bk_last_run); ?>
            </span>
            <span class="status-badge">
                <span class="badge-dot yellow"></span>
                Próximo: <?php echo hsc($bk_next_run); ?>
            </span>
            <span class="status-badge">
                <span class="badge-dot blue"></span>
                Backups: <?php echo $bk_count; ?>
            </span>
        </div>

        <!-- Config form -->
        <form id="autoBackupForm">
            <?php echo csrf_field(); ?>

            <div class="toggle-wrap">
                <span class="toggle-label">Activar respaldo automático</span>
                <label class="toggle-switch">
                    <input type="checkbox" name="backup_enabled" value="1" id="toggleAuto" <?php echo $enabled_checked; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="scheduler-grid">
                <div class="scheduler-group">
                    <label for="bkFrequency"><i class="fas fa-calendar-alt"></i> Frecuencia</label>
                    <select name="backup_frequency" id="bkFrequency">
                        <option value="hourly"  <?php echo $bk_frequency === 'hourly' ? 'selected' : ''; ?>>Cada hora</option>
                        <option value="daily"   <?php echo $bk_frequency === 'daily' ? 'selected' : ''; ?>>Cada día</option>
                        <option value="weekly"  <?php echo $bk_frequency === 'weekly' ? 'selected' : ''; ?>>Cada semana</option>
                        <option value="monthly" <?php echo $bk_frequency === 'monthly' ? 'selected' : ''; ?>>Cada mes</option>
                    </select>
                </div>
                <div class="scheduler-group">
                    <label for="bkRetention"><i class="fas fa-trash-alt"></i> Retención (backups a conservar)</label>
                    <select name="backup_retention" id="bkRetention">
                        <?php foreach ([3,5,10,15,20,30,50] as $n): ?>
                        <option value="<?php echo $n; ?>" <?php echo intval($bk_retention) === $n ? 'selected' : ''; ?>><?php echo $n; ?> backups</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-save-config" id="btnSaveConfig">
                <i class="fas fa-save"></i> Guardar Configuración
            </button>
        </form>

        <!-- Backup list -->
        <div class="backup-table-wrap">
            <?php if (empty($bk_list)): ?>
                <div class="backup-table-empty">
                    <i class="fas fa-inbox" style="font-size:32px;display:block;margin-bottom:10px;"></i>
                    No hay respaldos almacenados. Genere el primer respaldo manual o active la programación automática.
                </div>
            <?php else: ?>
            <table class="backup-table">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Fecha</th>
                        <th>Tamaño</th>
                        <th style="text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bk_list as $b): ?>
                    <tr>
                        <td><i class="fas fa-file-archive"></i> <?php echo hsc($b['name']); ?></td>
                        <td><?php echo $b['date']; ?></td>
                        <td><?php echo $b['size_hr']; ?></td>
                        <td style="text-align:center;">
                            <a href="index.php?route=backup_auto&action=download&file=<?php echo urlencode($b['name']); ?>" class="btn-sm btn-sm-download" title="Descargar">
                                <i class="fas fa-download"></i>
                            </a>
                            <button class="btn-sm btn-sm-delete" data-file="<?php echo hsc($b['name']); ?>" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="backup-summary">
            <span><i class="fas fa-folder"></i> Total: <?php echo $bk_count; ?> respaldo(s)</span>
            <span><i class="fas fa-hdd"></i> Tamaño total: <?php echo $bk_total_size > 1048576 ? round($bk_total_size / 1048576, 2) . ' MB' : round($bk_total_size / 1024, 2) . ' KB'; ?></span>
            <span><i class="fas fa-shield-alt"></i> Ubicación: storage/backups/</span>
        </div>
    </div>

</div>

<script src="assets/js/seguridad/respaldo.js"></script>
<script>
(function() {
    const form = document.getElementById('autoBackupForm');
    const btn = document.getElementById('btnSaveConfig');
    const banner = document.getElementById('msgBanner');
    const msgText = document.getElementById('msgText');

    function showMsg(text, type) {
        banner.className = 'msg-banner show ' + type;
        msgText.textContent = text;
        setTimeout(() => { banner.className = 'msg-banner'; }, 5000);
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-sm"></span> Guardando...';

        const fd = new FormData(form);
        fd.set('action', 'save_config');

        fetch('index.php?route=backup_auto', {
            method: 'POST',
            body: new URLSearchParams(fd)
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showMsg(data.message, 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                showMsg(data.error || 'Error al guardar', 'error');
            }
        })
        .catch(() => showMsg('Error de conexión', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Guardar Configuración';
        });
    });

    // Delete backup
    document.querySelectorAll('.btn-sm-delete').forEach(b => {
        b.addEventListener('click', function() {
            if (!confirm('¿Eliminar este respaldo permanentemente?')) return;
            const file = this.dataset.file;
            const btnDel = this;
            btnDel.innerHTML = '<span class="spinner-sm"></span>';

            const fd = new URLSearchParams();
            fd.set('action', 'delete');
            fd.set('file', file);
            fd.set('csrf_token', '<?php echo csrf_token(); ?>');

            fetch('index.php?route=backup_auto', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    btnDel.closest('tr').remove();
                    showMsg('Respaldo eliminado', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMsg(data.error || 'Error al eliminar', 'error');
                    btnDel.innerHTML = '<i class="fas fa-trash"></i>';
                }
            })
            .catch(() => { showMsg('Error de conexión', 'error'); btnDel.innerHTML = '<i class="fas fa-trash"></i>'; });
        });
    });
})();
</script>

</body>
</html>
