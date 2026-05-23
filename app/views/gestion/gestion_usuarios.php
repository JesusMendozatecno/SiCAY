<?php
verificar_sesion();
$uid = intval($_SESSION['id_usuario']);
$stmt = $con->prepare("SELECT * FROM usuario WHERE id = ?");
$stmt->bind_param("i", $uid); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc(); $stmt->close();
$current_lang = $user['language'] ?? 'es';
$is_admin = ($user['rol'] ?? '') === 'Admin';

// Stats for activity tab
$stmt2 = $con->prepare("SELECT COUNT(*) as c FROM activity_log WHERE user_id = ?");
$stmt2->bind_param("i", $uid); $stmt2->execute();
$stats = $stmt2->get_result()->fetch_assoc();
$total_activities = $stats['c']; $stmt2->close();
//comentario
?>
<!DOCTYPE html>
<html lang="<?php echo hsc($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('profile'); ?> - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/gestion/gestion_usuarios.css">
    <link rel="stylesheet" href="assets/css/cropper.min.css">
</head>
<body>
<div id="bubbles"></div>

<div class="profile-wrapper">
    <div class="profile-header">
        <div class="flex-center gap-3">
            <a href="index.php?route=dashboard" class="btn-back" title="<?php echo __('back'); ?>">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1><?php echo __('profile'); ?></h1>
        </div>
        <span class="text-muted">
            <?php echo __('last_access'); ?>: <?php echo $user['last_login_at'] ? date("d/m/Y H:i", strtotime($user['last_login_at'])) : __('loading'); ?>
        </span>
    </div>

    <div class="profile-layout">
        <!-- SIDEBAR -->
        <div class="profile-sidebar">
            <div class="profile-card">
                <div class="avatar-wrap" onclick="document.getElementById('avatarInput').click()">
                    <?php if($user['avatar']): ?>
                        <img id="profileAvatarImg" src="assets/img/avatars/<?php echo hsc($user['avatar']); ?>" alt="Avatar">
                    <?php else: ?>
                        <i class="fas fa-user fa-3x avatar-placeholder" id="profileAvatarPlaceholder"></i>
                    <?php endif; ?>
                    <div class="avatar-overlay"><i class="fas fa-camera"></i></div>
                </div>
                <input type="file" id="avatarInput" hidden accept="image/*" onchange="onAvatarSelect(event)">
                <h2 class="profile-name"><?php echo hsc($user['nombre']); ?></h2>
                <p class="profile-email"><?php echo hsc($user['correo']); ?></p>
                <span class="profile-role role-<?php echo strtolower($user['rol']); ?>"><?php echo hsc($user['rol']); ?></span>
            </div>
            <nav class="profile-nav">
                <button class="profile-nav-item active" data-tab="tab-perfil"><i class="fas fa-user"></i> <?php echo __('profile'); ?></button>
                <button class="profile-nav-item" data-tab="tab-seguridad"><i class="fas fa-shield-alt"></i> <?php echo __('security'); ?></button>
                <button class="profile-nav-item" data-tab="tab-configuracion"><i class="fas fa-cog"></i> <?php echo __('configuration'); ?></button>
                <button class="profile-nav-item" data-tab="tab-actividad"><i class="fas fa-chart-line"></i> <?php echo __('activity'); ?></button>
                <?php if($is_admin): ?>
                <div class="profile-nav-divider"></div>
                <div class="profile-nav-section-label"><?php echo __('admin_panel'); ?></div>
                <button class="profile-nav-item" data-tab="tab-admin"><i class="fas fa-user-shield"></i> <?php echo __('administration'); ?></button>
                <?php endif; ?>
                <div class="profile-nav-divider"></div>
                <a href="index.php?route=salir" class="profile-nav-item" style="color:#dc2626;text-decoration:none;display:flex;">
                    <i class="fas fa-sign-out-alt"></i> <?php echo __('close_session'); ?>
                </a>
            </nav>
        </div>

        <!-- CONTENT -->
        <div class="profile-content">

            <!-- TAB: PERFIL -->
            <div class="profile-tab active" id="tab-perfil">
                <h2 class="tab-title"><?php echo __('profile'); ?></h2>
                <p class="tab-subtitle"><?php echo __('user_info'); ?></p>
                <form id="formProfile">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="grid-2">
                        <div class="form-group">
                            <label><?php echo __('name'); ?></label>
                            <input type="text" class="form-input" name="nombre" value="<?php echo hsc($user['nombre']); ?>" required disabled>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('username'); ?></label>
                            <input type="text" class="form-input" name="usuario" value="<?php echo hsc($user['usuario']); ?>" required disabled>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('email'); ?></label>
                            <input type="email" class="form-input" name="correo" value="<?php echo hsc($user['correo']); ?>" required disabled>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-3">
                        <button type="button" id="btnEditProfile" class="btn btn-secondary" onclick="toggleEditProfile()"><i class="fas fa-pen"></i> <?php echo __('edit'); ?></button>
                        <button type="submit" id="btnSaveProfile" class="btn btn-primary" style="display:none;"><i class="fas fa-check"></i> <?php echo __('save_changes'); ?></button>
                        <button type="button" id="btnCancelProfile" class="btn btn-outline" style="display:none;" onclick="cancelEditProfile()"><?php echo __('cancel'); ?></button>
                    </div>
                </form>
                <?php if($user['avatar']): ?>
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarAvatar()"><i class="fas fa-trash"></i> <?php echo __('delete'); ?> <?php echo strtolower(__('avatar')); ?></button>
                </div>
                <?php endif; ?>
            </div>

            <!-- TAB: SEGURIDAD -->
            <div class="profile-tab" id="tab-seguridad">
                <h2 class="tab-title"><?php echo __('security'); ?></h2>
                <p class="tab-subtitle"><?php echo __('two_factor_desc'); ?></p>

                <h4 class="section-title"><?php echo __('update_password'); ?></h4>
                <form id="formPassword" class="form-max-420">
                    <input type="hidden" name="action" value="update_password">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="form-group">
                        <label><?php echo __('current_password'); ?></label>
                        <input type="password" class="form-input" name="current_password" required>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label><?php echo __('new_password'); ?></label>
                            <input type="password" class="form-input" name="new_password" minlength="6" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('confirm_password'); ?></label>
                            <input type="password" class="form-input" name="confirm_password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo __('update_password'); ?></button>
                </form>

                <hr class="divider">
                <div class="flex-between">
                    <div>
                        <h4 class="section-title" style="margin:0;"><?php echo __('two_factor'); ?></h4>
                        <p class="text-muted" style="margin:4px 0 0;"><?php echo __('two_factor_desc'); ?></p>
                    </div>
                    <div class="toggle-switch <?php echo $user['two_factor_enabled'] ? 'active' : ''; ?>" id="toggle2FA" onclick="toggleFA()"></div>
                </div>

                <hr class="divider">
                <div class="flex-between mb-4">
                    <div>
                        <h4 class="section-title" style="margin:0;"><?php echo __('active_sessions'); ?></h4>
                        <p class="text-muted" style="margin:4px 0 0;"><?php echo __('active_sessions_desc'); ?></p>
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="cerrarOtrasSesiones()"><i class="fas fa-sign-out-alt"></i> <?php echo __('close_other_sessions'); ?></button>
                </div>
                <div id="sessionList"><p class="text-muted"><?php echo __('loading'); ?></p></div>
            </div>

            <!-- TAB: CONFIGURACIÓN -->
            <div class="profile-tab" id="tab-configuracion">
                <h2 class="tab-title"><?php echo __('configuration'); ?></h2>
                <p class="tab-subtitle"><?php echo __('notifications_mail_desc'); ?></p>

                <h4 class="section-title"><?php echo __('theme'); ?></h4>
                <div class="theme-toggle mb-4">
                    <div class="theme-option <?php echo ($user['tema'] ?? 'claro') === 'claro' ? 'selected' : ''; ?>" data-theme="claro" onclick="cambiarTema('claro')">
                        <i class="fas fa-sun"></i> <span><?php echo __('light'); ?></span>
                    </div>
                    <div class="theme-option <?php echo ($user['tema'] ?? '') === 'oscuro' ? 'selected' : ''; ?>" data-theme="oscuro" onclick="cambiarTema('oscuro')">
                        <i class="fas fa-moon"></i> <span><?php echo __('dark'); ?></span>
                    </div>
                </div>

                <h4 class="section-title"><?php echo __('language'); ?></h4>
                <div class="form-max-200">
                    <select class="form-input" id="selectLanguage" onchange="cambiarIdioma(this.value)">
                        <option value="es" <?php echo $current_lang === 'es' ? 'selected' : ''; ?>><?php echo __('spanish'); ?></option>
                        <option value="en" <?php echo $current_lang === 'en' ? 'selected' : ''; ?>><?php echo __('english'); ?></option>
                    </select>
                </div>

                <h4 class="section-title"><?php echo __('accent_color'); ?></h4>
                <div class="color-presets" id="colorPresets">
                    <?php $colors = ['#123C69','#1F6AE1','#7c3aed','#db2777','#dc2626','#ea580c','#ca8a04','#16a34a','#0d9488']; ?>
                    <?php foreach($colors as $c): ?>
                    <div class="color-preset <?php echo ($user['accent_color'] ?? '#123C69') === $c ? 'selected' : ''; ?>" style="background:<?php echo $c; ?>;" data-color="<?php echo $c; ?>" onclick="cambiarColor('<?php echo $c; ?>')"></div>
                    <?php endforeach; ?>
                </div>

                <hr class="divider">
                <h4 class="section-title"><?php echo __('notifications'); ?></h4>
                <div class="toggle-row">
                    <div><div class="toggle-label"><?php echo __('notifications_mail'); ?></div><div class="toggle-desc"><?php echo __('notifications_mail_desc'); ?></div></div>
                    <select class="form-input form-input-auto" id="notifEmail" onchange="guardarNotificaciones()">
                        <option value="all" <?php echo ($user['notification_email'] ?? 'all') === 'all' ? 'selected' : ''; ?>><?php echo __('all'); ?></option>
                        <option value="important" <?php echo ($user['notification_email'] ?? '') === 'important' ? 'selected' : ''; ?>><?php echo __('important_only'); ?></option>
                        <option value="none" <?php echo ($user['notification_email'] ?? '') === 'none' ? 'selected' : ''; ?>><?php echo __('none'); ?></option>
                    </select>
                </div>
                <div class="toggle-row">
                    <div><div class="toggle-label"><?php echo __('notifications_system'); ?></div><div class="toggle-desc"><?php echo __('notifications_system_desc'); ?></div></div>
                    <select class="form-input form-input-auto" id="notifSystem" onchange="guardarNotificaciones()">
                        <option value="all" <?php echo ($user['notification_system'] ?? 'all') === 'all' ? 'selected' : ''; ?>><?php echo __('all'); ?></option>
                        <option value="important" <?php echo ($user['notification_system'] ?? '') === 'important' ? 'selected' : ''; ?>><?php echo __('important_only'); ?></option>
                        <option value="none" <?php echo ($user['notification_system'] ?? '') === 'none' ? 'selected' : ''; ?>><?php echo __('none'); ?></option>
                    </select>
                </div>
                <div class="toggle-row">
                    <div><div class="toggle-label"><?php echo __('profile_public'); ?></div><div class="toggle-desc"><?php echo __('profile_public_desc'); ?></div></div>
                    <div class="toggle-switch <?php echo ($user['profile_public'] ?? 1) ? 'active' : ''; ?>" id="togglePrivacy" onclick="togglePrivacidad()"></div>
                </div>
            </div>

            <!-- TAB: ACTIVIDAD -->
            <div class="profile-tab" id="tab-actividad">
                <h2 class="tab-title"><?php echo __('my_activity'); ?></h2>
                <p class="tab-subtitle"><?php echo __('activity_desc'); ?></p>

                <div id="activityStats" class="grid-3 mb-4">
                    <div class="stat-card-mini"><h4 id="statTotal">0</h4><p><?php echo __('my_activity'); ?></p></div>
                    <div class="stat-card-mini"><h4 id="statMiembro">—</h4><p><?php echo __('member_since'); ?></p></div>
                    <div class="stat-card-mini"><h4 id="statUltimoAcceso">—</h4><p><?php echo __('last_access'); ?></p></div>
                </div>
                <div id="activityList" class="activity-scroll">
                    <p class="text-muted text-center" style="padding:20px;"><?php echo __('loading'); ?></p>
                </div>
            </div>

            <!-- TAB: ADMIN (solo admin) -->
            <?php if($is_admin): ?>
            <div class="profile-tab" id="tab-admin">
                <h2 class="tab-title"><?php echo __('administration'); ?></h2>
                <p class="tab-subtitle"><?php echo __('admin_panel'); ?></p>

                <div class="admin-tabs">
                    <button class="btn btn-secondary btn-sm active admin-tab-btn" data-admin-tab="admin-users" onclick="cambiarAdminTab('admin-users')"><i class="fas fa-users"></i> <?php echo __('users'); ?></button>
                    <button class="btn btn-secondary btn-sm admin-tab-btn" data-admin-tab="admin-activity" onclick="cambiarAdminTab('admin-activity')"><i class="fas fa-chart-line"></i> <?php echo __('global_activity'); ?></button>
                    <button class="btn btn-secondary btn-sm admin-tab-btn" data-admin-tab="admin-config" onclick="cambiarAdminTab('admin-config')"><i class="fas fa-globe"></i> <?php echo __('global_config'); ?></button>
                </div>

                <div class="admin-subtab" id="admin-users">
                    <div class="search-bar">
                        <input type="text" class="form-input" id="adminSearch" placeholder="<?php echo __('search'); ?>..." oninput="cargarAdminUsuarios()">
                        <select class="form-input form-input-auto" id="adminRoleFilter" onchange="cargarAdminUsuarios()">
                            <option value=""><?php echo __('all'); ?></option>
                            <option value="Admin"><?php echo __('administrator'); ?></option>
                            <option value="Operador"><?php echo __('operator'); ?></option>
                        </select>
                    </div>
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th><?php echo __('name'); ?></th><th><?php echo __('email'); ?></th><th><?php echo __('role'); ?></th><th><?php echo __('actions'); ?></th></tr></thead>
                            <tbody id="adminUsersBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="admin-subtab" id="admin-activity" style="display:none;">
                    <div id="adminActivityList" class="activity-scroll">
                        <p class="text-muted text-center" style="padding:20px;"><?php echo __('loading'); ?></p>
                    </div>
                </div>

                <div class="admin-subtab" id="admin-config" style="display:none;">
                    <form id="formGlobalConfig">
                        <input type="hidden" name="action" value="save_global_config">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <div class="form-group">
                            <label><?php echo __('system_name'); ?></label>
                            <input type="text" class="form-input" name="app_name" value="<?php echo hsc(get_system_config('app_name', 'SICAY')); ?>">
                        </div>
                        <div class="form-group">
                            <label><?php echo __('default_theme'); ?></label>
                            <select class="form-input form-input-auto" name="default_theme">
                                <option value="light" <?php echo get_system_config('default_theme','light') === 'light' ? 'selected' : ''; ?>><?php echo __('light'); ?></option>
                                <option value="dark" <?php echo get_system_config('default_theme','') === 'dark' ? 'selected' : ''; ?>><?php echo __('dark'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('security_level'); ?></label>
                            <select class="form-input form-input-auto" name="security_level">
                                <option value="low" <?php echo get_system_config('security_level','medium') === 'low' ? 'selected' : ''; ?>><?php echo __('low'); ?></option>
                                <option value="medium" <?php echo get_system_config('security_level','medium') === 'medium' ? 'selected' : ''; ?>><?php echo __('medium'); ?></option>
                                <option value="high" <?php echo get_system_config('security_level','') === 'high' ? 'selected' : ''; ?>><?php echo __('high'); ?></option>
                            </select>
                        </div>
                        <div class="toggle-row">
                            <div><div class="toggle-label"><?php echo __('maintenance_mode'); ?></div><div class="toggle-desc"><?php echo __('maintenance_desc'); ?></div></div>
                            <input type="checkbox" name="maintenance_mode" value="1" <?php echo is_maintenance_mode() ? 'checked' : ''; ?> style="width:20px;height:20px;">
                        </div>
                        <button type="submit" class="btn btn-primary mt-3"><?php echo __('save_global_config'); ?></button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Cropper -->
<div class="modal-overlay" id="cropModal">
    <div class="modal-box" style="max-width:550px;">
        <h3><?php echo __('avatar'); ?></h3>
        <p class="text-muted" style="margin:0 0 16px;">Arrastra para ajustar la imagen al área deseada</p>
        <div id="cropContainer" style="max-height:350px;overflow:hidden;"></div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="cerrarCrop()"><?php echo __('cancel'); ?></button>
            <button class="btn btn-primary" onclick="confirmarCrop()"><?php echo __('save_changes'); ?></button>
        </div>
    </div>
</div>

<!-- Modal Notificacion -->
<div class="modal-overlay" id="modalEnviarNotificacion">
    <div class="modal-box" style="max-width:480px;">
        <h3><i class="fas fa-bell"></i> <?php echo __('send_notification'); ?></h3>
        <form id="formEnviarNotificacion">
            <input type="hidden" name="action" value="send_notification">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="user_id" id="notifUserId">
            <div class="form-group">
                <label><?php echo __('name'); ?>:</label>
                <p style="margin:4px 0 8px;font-weight:600;color:white;" id="notifUserName">—</p>
            </div>
            <div class="form-group">
                <label><?php echo __('title'); ?></label>
                <input type="text" class="form-input" name="title" required>
            </div>
            <div class="form-group">
                <label><?php echo __('message'); ?></label>
                <textarea class="form-input" name="message" required rows="4"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalNotificacion()"><?php echo __('cancel'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo __('send'); ?></button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/cropper.min.js"></script>
<script>window.CURRENT_USER_ID=<?php echo $uid; ?>;</script>
<script src="assets/js/gestion/gestion_usuarios.js"></script>
</body>
</html>