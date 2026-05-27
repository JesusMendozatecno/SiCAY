<?php
$notifCount = 0;
$userAvatar = null;
if (isset($con) && isset($_SESSION['id_usuario'])) {
    $uid = intval($_SESSION['id_usuario']);
    $stmt = $con->prepare("SELECT avatar FROM usuario WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $userAvatar = $row['avatar'] ?? null;
        $stmt->close();
    }
    $nStmt = $con->prepare("SELECT COUNT(*) as c FROM notifications WHERE to_user_id = ? AND read_at IS NULL");
    if ($nStmt) {
        $nStmt->bind_param("i", $uid);
        $nStmt->execute();
        $notifCount = $nStmt->get_result()->fetch_assoc()['c'] ?? 0;
        $nStmt->close();
    }
}
$avatarUrl = $userAvatar ? 'assets/img/avatars/' . hsc($userAvatar) : null;
?>
<header class="global-header">
    <div class="header-inner">
        <div class="header-left">
            <div class="nav-brand">
                <img src="assets/img/EUhOGzfWAAAHZC4-removebg-preview.png" alt="Aguas de Yaracuy" class="header-logo">
                <div class="brand-text">
                    <span class="main-name">Aguas de Yaracuy</span>
                    <span class="sub-name">Departamento de Calidad</span>
                </div>
            </div>
        </div>
        <div class="header-right">
            <div class="notif-dropdown">
                <button class="notif-btn" title="Notificaciones" onclick="toggleNotifPanel()">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge" id="notifBadgeHeader"<?php echo $notifCount < 1 ? ' style="display:none"' : ''; ?>><?php echo $notifCount > 0 ? ($notifCount > 99 ? '99+' : $notifCount) : ''; ?></span>
                </button>
                <div class="notif-panel" id="notifPanel">
                    <div class="notif-panel-header">
                        <span>Notificaciones</span>
                        <button class="notif-mark-all" onclick="marcarTodasLeidas()">Marcar todo leído</button>
                    </div>
                    <div class="notif-panel-body" id="notifPanelBody">
                        <p class="text-muted" style="padding:20px;text-align:center;">Cargando...</p>
                    </div>
                </div>
            </div>
            <div class="user-dropdown">
                <button class="user-btn" onclick="toggleDropdown()">
                    <?php if ($avatarUrl): ?>
                    <img src="<?php echo $avatarUrl; ?>" class="user-avatar-img">
                    <?php else: ?>
                    <i class="fas fa-user-circle user-avatar-icon"></i>
                    <?php endif; ?>
                    <span class="user-btn-name"><?php echo hsc($_SESSION['usuario']); ?></span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="dropdown-menu" id="dropdownMenu">
                    <div class="dropdown-user-info">
                        <?php if ($avatarUrl): ?>
                        <img src="<?php echo $avatarUrl; ?>" class="dropdown-avatar-img">
                        <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                        <div>
                            <div class="dropdown-user-name"><?php echo hsc($_SESSION['usuario']); ?></div>
                            <div class="dropdown-user-email"><?php echo hsc($_SESSION['correo'] ?? ''); ?></div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="index.php?route=gestion_usuarios" class="dropdown-item"><i class="fas fa-user-cog"></i> <?php echo __('profile'); ?></a>
                    <div class="dropdown-divider"></div>
                    <a href="index.php?route=salir" class="dropdown-item dropdown-logout"><i class="fas fa-sign-out-alt"></i> <?php echo __('close_session'); ?></a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Notification Detail Modal -->
<div id="notifDetailOverlay" class="notif-detail-overlay">
    <div class="notif-detail-card">
        <div class="nd-top">
            <h4><i class="fas fa-bell"></i> Detalle de notificación</h4>
            <button class="nd-close" onclick="cerrarDetalleNotificacion()">&times;</button>
        </div>
        <div class="notif-detail-body" id="notifDetailBody">
            <div class="detail-loading">Cargando...</div>
        </div>
    </div>
</div>

<script>var headerNotifCount=<?php echo $notifCount; ?>;</script>
