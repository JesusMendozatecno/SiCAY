// Bubbles background
(function(){
    var c=document.getElementById('bubbles');
    if(!c)return;
    for(var i=0;i<20;i++){
        var b=document.createElement('div');b.className='bubble';
        var s=Math.random()*50+20;b.style.width=s+'px';b.style.height=s+'px';
        b.style.left=Math.random()*100+'vw';
        b.style.animationDuration=(Math.random()*5+6)+'s';
        b.style.animationDelay=(Math.random()*6)+'s';
        c.appendChild(b);
    }
})();

// Tab switching
document.querySelectorAll('.profile-nav-item[data-tab]').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('.profile-nav-item').forEach(function(b){b.classList.remove('active');});
        btn.classList.add('active');
        document.querySelectorAll('.profile-tab').forEach(function(t){t.classList.remove('active');});
        document.getElementById(btn.dataset.tab).classList.add('active');
    });
});

// Profile edit mode
var editMode=false;
function toggleEditProfile(){
    editMode=!editMode;
    var inputs=document.querySelectorAll('#formProfile .form-input');
    inputs.forEach(function(i){i.disabled=!editMode;});
    document.getElementById('btnEditProfile').style.display=editMode?'none':'';
    document.getElementById('btnSaveProfile').style.display=editMode?'':'none';
    document.getElementById('btnCancelProfile').style.display=editMode?'':'none';
    if(editMode) document.querySelector('#formProfile .form-input').focus();
}
function cancelEditProfile(){
    editMode=true; toggleEditProfile();
    location.reload();
}

// --- AJAX API helper ---
function apiCall(formData, onSuccess, onError){
    var xhr=new XMLHttpRequest();
    xhr.open('POST','index.php?route=api_profile',true);
    xhr.onload=function(){
        if(xhr.status===200){
            try{
                var r=JSON.parse(xhr.responseText);
                if(r.success){if(onSuccess)onSuccess(r);}else{if(onError)onError(r);showToast(r.error||'Error','error');}
            }catch(e){if(onError)onError({error:'Invalid response'});showToast('Error de conexión','error');}
        }else{
            if(onError)onError({error:'Server error'});showToast('Error del servidor','error');
        }
    };
    xhr.onerror=function(){if(onError)onError({error:'Network error'});showToast('Error de red','error');};
    xhr.send(formData);
}

function showToast(msg,type){
    type=type||'info';
    var t=document.createElement('div');
    t.className='alert-toast '+type;
    var icons={success:'fa-check-circle',error:'fa-exclamation-triangle',info:'fa-info-circle'};
    t.innerHTML='<i class="fas '+icons[type]+'"></i> '+msg;
    document.body.appendChild(t);
    setTimeout(function(){t.style.opacity='0';t.style.transition='opacity 0.3s';setTimeout(function(){t.remove();},300);},3000);
}

// --- TAB: PROFILE ---
document.getElementById('formProfile').addEventListener('submit',function(e){
    e.preventDefault();
    var fd=new FormData(this);
    apiCall(fd,function(r){
        showToast(r.message||'Cambios guardados','success');
        editMode=true;toggleEditProfile();
        loadProfileData();
    });
});

function loadProfileData(){
    var fd=new FormData();fd.append('action','get_profile');fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){
        if(r.data){
            document.querySelector('.profile-name').textContent=r.data.nombre;
            document.querySelector('.profile-email').textContent=r.data.correo;
        }
    });
}

// Avatar with Cropper
var avatarFile=null;
var cropper=null;
window.onAvatarSelect=function(e){
    var file=e.target.files[0];if(!file)return;
    avatarFile=file;
    var reader=new FileReader();
    reader.onload=function(ev){
        document.getElementById('cropModal').classList.add('show');
        var cc=document.getElementById('cropContainer');
        cc.innerHTML='';
        if(cropper){cropper.destroy();cropper=null;}
        var img=document.createElement('img');
        img.id='cropImage';
        img.style.maxWidth='100%';
        img.onload=function(){
            cropper=new Cropper(img,{
                aspectRatio:1/1,
                viewMode:1,
                autoCropArea:0.8,
                movable:true,
                zoomable:true,
                rotatable:false
            });
        };
        img.src=ev.target.result;
        cc.appendChild(img);
    };
    reader.readAsDataURL(file);
};
function cerrarCrop(){
    if(cropper){cropper.destroy();cropper=null;}
    document.getElementById('cropModal').classList.remove('show');
}
function confirmarCrop(){
    if(!cropper||!avatarFile)return;
    var canvas=cropper.getCroppedCanvas({width:300,height:300});
    canvas.toBlob(function(blob){
        var fd=new FormData();
        fd.append('action','upload_avatar');
        fd.append('avatar',blob,avatarFile.name);
        fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
        apiCall(fd,function(r){
            showToast(r.message||'Foto actualizada','success');
            cerrarCrop();
            if(r.avatar){
                var img=document.getElementById('profileAvatarImg');
                var wrap=document.querySelector('.avatar-wrap');
                if(!img&&wrap){
                    var plc=document.getElementById('profileAvatarPlaceholder');
                    if(plc)plc.remove();
                    img=document.createElement('img');
                    img.id='profileAvatarImg';
                    img.alt='Avatar';
                    wrap.insertBefore(img,wrap.querySelector('.avatar-overlay')||null);
                }
                if(img){
                    img.src='assets/img/avatars/'+r.avatar+'?t='+Date.now();
                    img.style.display='block';
                }
                var plc=document.getElementById('profileAvatarPlaceholder');
                if(plc)plc.style.display='none';
            }
        });
    },'image/jpeg',0.9);
}
function eliminarAvatar(){
    if(!confirm('¿Eliminar foto de perfil?'))return;
    var fd=new FormData();fd.append('action','delete_avatar');fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){
        showToast(r.message||'Foto eliminada','success');
        var img=document.getElementById('profileAvatarImg');
        if(img)img.style.display='none';
        var plc=document.getElementById('profileAvatarPlaceholder');
        if(!plc){
            var wrap=document.querySelector('.avatar-wrap');
            if(wrap){
                plc=document.createElement('i');
                plc.className='fas fa-user fa-3x avatar-placeholder';
                plc.id='profileAvatarPlaceholder';
                wrap.insertBefore(plc,wrap.querySelector('.avatar-overlay')||null);
            }
        }
        if(plc)plc.style.display='block';
    });
}

// --- TAB: SEGURIDAD ---
document.getElementById('formPassword').addEventListener('submit',function(e){
    e.preventDefault();
    var pwd=this.querySelector('[name=new_password]').value;
    var conf=this.querySelector('[name=confirm_password]').value;
    if(pwd!==conf){showToast('Las contraseñas no coinciden','error');return;}
    if(pwd.length<6){showToast('La contraseña debe tener al menos 6 caracteres','error');return;}
    var fd=new FormData(this);
    apiCall(fd,function(r){showToast(r.message||'Contraseña actualizada','success');e.target.reset();});
});

function toggleFA(){
    var el=document.getElementById('toggle2FA');
    var newState=el.classList.contains('active')?0:1;
    var fd=new FormData();fd.append('action','toggle_2fa');fd.append('enabled',newState);fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){el.classList.toggle('active');showToast(r.message||'2FA actualizado','success');});
}

function cargarSesiones(){
    var el=document.getElementById('sessionList');if(!el)return;
    el.innerHTML='<p class="text-muted">Cargando...</p>';
    var fd=new FormData();fd.append('action','get_sessions');fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){
        if(r.sessions&&r.sessions.length>0){
            var h='';
            r.sessions.forEach(function(s){
                var cls=s.is_current?'activity-item current-session':'activity-item';
                h+='<div class="'+cls+'">'+
                    '<div class="flex-between">'+
                    '<span class="act-action">'+(s.is_current?'🟢 Sesión actual - ':'')+(s.ip_address||'—')+'</span>'+
                    '<span class="act-date">'+s.created_at+'</span>'+
                    '</div>'+
                    '<div class="act-details">'+(s.user_agent||'—').substring(0,80)+'</div>'+
                    (!s.is_current?'<button class="btn btn-outline-danger btn-sm mt-1" onclick="cerrarSesion('+s.id+')"><i class="fas fa-times"></i> Cerrar</button>':'')+
                    '</div>';
            });
            el.innerHTML=h;
        }else{
            el.innerHTML='<p class="text-muted">No hay sesiones activas</p>';
        }
    });
}
function cerrarSesion(id){
    var fd=new FormData();fd.append('action','close_session');fd.append('session_id',id);fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){showToast(r.message||'Sesión cerrada','success');cargarSesiones();});
}
function cerrarOtrasSesiones(){
    var fd=new FormData();fd.append('action','close_other_sessions');fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){showToast(r.message||'Sesiones cerradas','success');cargarSesiones();});
}

// --- TAB: CONFIGURACIÓN ---
function cambiarTema(tema){
    var fd=new FormData();fd.append('action','update_config');fd.append('tema',tema);fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){
        document.querySelectorAll('.theme-option').forEach(function(el){
            el.classList.toggle('selected',el.dataset.theme===tema);
        });
        // Apply dark mode class to body
        document.body.classList.toggle('dark-mode',tema==='oscuro');
        showToast(r.message||'Tema actualizado','success');
    });
}
function cambiarIdioma(lang){
    var fd=new FormData();fd.append('action','update_config');fd.append('language',lang);fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){
        showToast(r.message||'Idioma actualizado. Recargando...','success');
        setTimeout(function(){location.reload();},800);
    });
}
function cambiarColor(color){
    document.querySelectorAll('.color-preset').forEach(function(el){el.classList.toggle('selected',el.dataset.color===color);});
    var fd=new FormData();fd.append('action','update_config');fd.append('accent_color',color);fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){showToast(r.message||'Color actualizado','success');});
}
function guardarNotificaciones(){
    var email=document.getElementById('notifEmail').value;
    var sys=document.getElementById('notifSystem').value;
    var fd=new FormData();fd.append('action','update_config');
    fd.append('notification_email',email);fd.append('notification_system',sys);
    fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){showToast(r.message||'Preferencias guardadas','success');});
}
function togglePrivacidad(){
    var el=document.getElementById('togglePrivacy');
    var newState=el.classList.contains('active')?0:1;
    var fd=new FormData();fd.append('action','update_config');fd.append('profile_public',newState);fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){el.classList.toggle('active');showToast(r.message||'Privacidad actualizada','success');});
}

// --- TAB: ACTIVIDAD ---
function cargarActividad(page){
    page=page||1;
    var el=document.getElementById('activityList');if(!el)return;
    el.innerHTML='<p class="text-muted text-center" style="padding:20px;">Cargando...</p>';
    var fd=new FormData();fd.append('action','get_activity');fd.append('page',page);fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){
        // Stats
        if(r.stats){
            document.getElementById('statTotal').textContent=r.stats.total||0;
            document.getElementById('statMiembro').textContent=r.stats.member_since||'—';
            document.getElementById('statUltimoAcceso').textContent=r.stats.last_login||'—';
        }
        if(r.activities&&r.activities.length>0){
            var h='';
            r.activities.forEach(function(a){
                h+='<div class="activity-item">'+
                    '<div class="flex-between"><span class="act-action">'+hsc(a.action)+'</span><span class="act-date">'+a.created_at+'</span></div>'+
                    (a.details?'<div class="act-details">'+hsc(a.details)+'</div>':'')+
                    '</div>';
            });
            // Pagination
            if(r.has_more){
                h+='<div style="text-align:center;padding:10px;">'+
                    '<button class="btn btn-secondary btn-sm" onclick="cargarActividad('+(page+1)+')">Cargar más</button></div>';
            }
            el.innerHTML=h;
        }else{
            el.innerHTML='<p class="text-muted text-center" style="padding:20px;">No hay actividad registrada</p>';
        }
    });
}
function hsc(s){if(!s)return'';var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML;}

// --- TAB: ADMIN ---
function cambiarAdminTab(tab){
    document.querySelectorAll('.admin-tab-btn').forEach(function(b){b.classList.remove('active');});
    document.querySelector('.admin-tab-btn[data-admin-tab="'+tab+'"]').classList.add('active');
    document.querySelectorAll('.admin-subtab').forEach(function(t){t.style.display='none';});
    document.getElementById(tab).style.display='block';
    if(tab==='admin-users') cargarAdminUsuarios();
    if(tab==='admin-activity') cargarAdminActividad();
}

function cargarAdminUsuarios(){
    var fd=new FormData();fd.append('action','get_users');
    fd.append('search',document.getElementById('adminSearch').value);
    fd.append('role',document.getElementById('adminRoleFilter').value);
    fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){
        var tbody=document.getElementById('adminUsersBody');
        if(r.users&&r.users.length>0){
            var h='';
            r.users.forEach(function(u){
                var roleColor='role-'+u.rol.toLowerCase();
                var roleEscaped=hsc(u.rol);
                h+='<tr><td><strong>'+hsc(u.nombre)+'</strong></td>'+
                    '<td>'+hsc(u.correo)+'</td>'+
                    '<td><span class="profile-role '+roleColor+'" style="font-size:0.7rem;">'+roleEscaped+'</span></td>'+
                    '<td>'+
                    '<button class="action-btn edit" onclick="editarUsuario('+u.id+',\''+roleEscaped+'\')" title="Editar"><i class="fas fa-pen"></i></button>'+
                    '<button class="action-btn send" onclick="abrirNotificacion('+u.id+',\''+hsc(u.nombre)+'\')" title="Notificar"><i class="fas fa-bell"></i></button>'+
                    (u.id!=window.CURRENT_USER_ID?'<button class="action-btn danger" onclick="eliminarUsuario('+u.id+')" title="Eliminar"><i class="fas fa-trash"></i></button>':'')+
                    '</td></tr>';
            });
            tbody.innerHTML=h;
        }else{
            tbody.innerHTML='<tr><td colspan="4" style="text-align:center;color:rgba(255,255,255,0.4);padding:20px;">No se encontraron usuarios</td></tr>';
        }
    });
}

function editarUsuario(id, currentRole){
    currentRole=currentRole||'Operador';
    var overlay=document.createElement('div');
    overlay.className='modal-overlay';overlay.style.display='flex';
    overlay.innerHTML='<div class="modal-box" style="max-width:350px;">'+
        '<h3>Cambiar rol</h3>'+
        '<div class="form-group">'+
        '<label>Selecciona el nuevo rol</label>'+
        '<select class="form-input" id="selectNewRole">'+
        '<option value="Admin"'+(currentRole==='Admin'?' selected':'')+'>Admin</option>'+
        '<option value="Operador"'+(currentRole==='Operador'?' selected':'')+'>Operador</option>'+
        '</select></div>'+
        '<div class="modal-actions">'+
        '<button class="btn btn-secondary" onclick="this.closest(\'.modal-overlay\').remove()">Cancelar</button>'+
        '<button class="btn btn-primary" id="btnSaveRole">Guardar</button>'+
        '</div></div>';
    document.body.appendChild(overlay);
    document.getElementById('btnSaveRole').addEventListener('click',function(){
        var newRole=document.getElementById('selectNewRole').value;
        var fd=new FormData();fd.append('action','update_user_role');fd.append('user_id',id);fd.append('role',newRole);
        fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
        apiCall(fd,function(r){showToast(r.message||'Rol actualizado','success');cargarAdminUsuarios();overlay.remove();});
    });
}

function eliminarUsuario(id){
    if(!confirm('¿Seguro que desea eliminar este usuario?'))return;
    var fd=new FormData();fd.append('action','delete_user');fd.append('user_id',id);fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){showToast(r.message||'Usuario eliminado','success');cargarAdminUsuarios();});
}

function abrirNotificacion(id,nombre){
    document.getElementById('notifUserId').value=id;
    document.getElementById('notifUserName').textContent=nombre;
    document.getElementById('modalEnviarNotificacion').classList.add('show');
}
function cerrarModalNotificacion(){document.getElementById('modalEnviarNotificacion').classList.remove('show');}

document.getElementById('formEnviarNotificacion').addEventListener('submit',function(e){
    e.preventDefault();
    var fd=new FormData(this);
    apiCall(fd,function(r){
        showToast(r.message||'Notificación enviada','success');
        cerrarModalNotificacion();
        e.target.reset();
    });
});

function cargarAdminActividad(){
    var fd=new FormData();fd.append('action','get_global_activity');fd.append('csrf_token',document.querySelector('#formProfile [name=csrf_token]').value);
    apiCall(fd,function(r){
        var el=document.getElementById('adminActivityList');
        if(r.activities&&r.activities.length>0){
            var h='';
            r.activities.forEach(function(a){
                h+='<div class="activity-item">'+
                    '<div class="flex-between"><span class="act-action">'+hsc(a.user_name||'—')+': '+hsc(a.action)+'</span><span class="act-date">'+a.created_at+'</span></div>'+
                    (a.details?'<div class="act-details">'+hsc(a.details)+'</div>':'')+
                    '</div>';
            });
            el.innerHTML=h;
        }else{
            el.innerHTML='<p class="text-muted text-center" style="padding:20px;">No hay actividad global</p>';
        }
    });
}

document.getElementById('formGlobalConfig').addEventListener('submit',function(e){
    e.preventDefault();
    var fd=new FormData(this);
    apiCall(fd,function(r){showToast(r.message||'Configuración guardada','success');});
});

// Load data on tab activation
document.querySelector('.profile-nav-item[data-tab="tab-seguridad"]').addEventListener('click',function(){
    setTimeout(cargarSesiones,100);
});
document.querySelector('.profile-nav-item[data-tab="tab-actividad"]').addEventListener('click',function(){
    setTimeout(function(){cargarActividad(1);},100);
});
document.querySelector('.profile-nav-item[data-tab="tab-admin"]').addEventListener('click',function(){
    setTimeout(function(){cargarAdminUsuarios();},100);
});
