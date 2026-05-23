<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hidroven Yaracuy - Gestión de Calidad</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <img src="assets/img/EUhOGzfWAAAHZC4-removebg-preview.png" alt="Logo" class="logo-img">
            <div class="brand-text">
                <span class="main-name">Hidroven Yaracuy</span>
                <span class="sub-name">Departamento de Calidad</span>
            </div>
        </div>
        <div class="nav-links" id="navLinks">
            <a href="#inicio" class="nav-link active">Inicio</a>
            <a href="#sistema" class="nav-link">Sistema</a>
            <a href="#contacto" class="nav-link">Contacto</a>
            <a href="index.php?route=login" class="nav-btn register">Iniciar</a>
        </div>
        <button class="nav-toggle" id="navToggle" aria-label="Menú"><i class="fas fa-bars"></i></button>
    </div>
</nav>

<section id="inicio" class="hero">
    <div id="bg-slide" class="slide-overlay"></div>
    <div class="hero-content">
        <div class="hero-card">
            <h1>Sistema Integral del Departamento de Calidad de Agua</h1>
            <p>Monitoreo avanzado y control de potabilización para el estado Yaracuy.</p>
            <a href="index.php?route=login" class="nav-btn register" style="padding: 15px 35px; font-size: 1.1rem; display: inline-block; text-decoration: none;">Empezar ahora</a>
        </div>
    </div>
</section>

<section id="sistema" class="section-info">
    <div class="section-container">
        <h2 class="section-title"><i class="fas fa-cogs"></i> Sobre el Sistema</h2>
        <p class="section-subtitle">SICAY — Sistema Integral de Control de Aguas de Yaracuy</p>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-tint feature-icon"></i>
                <h3>Monitoreo en Tiempo Real</h3>
                <p>Registro diario de cloro residual, pH, turbiedad y parámetros críticos de todas las plantas del estado.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-boxes feature-icon"></i>
                <h3>Control de Inventarios</h3>
                <p>Gestión de sustancias químicas, consumo y movimientos de insumos para potabilización.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chart-bar feature-icon"></i>
                <h3>Reportes Automatizados</h3>
                <p>Generación de reportes históricos, estadísticas y análisis de calidad del agua.</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-industry feature-icon"></i>
                <h3>Gestión de Plantas</h3>
                <p>Administración de plantas potabilizadoras, pozos profundos y estaciones cloradoras del estado.</p>
            </div>
           
        </div>
    </div>
</section>

<section id="contacto" class="section-contact">
    <div class="section-container">
        <h2 class="section-title"><i class="fas fa-address-card"></i> Contacto</h2>
        <p class="section-subtitle">Desarrolladores y responsables del sistema</p>
        <div class="contact-grid">
            <div class="contact-card">
                <div class="contact-avatar"><i class="fas fa-user-circle"></i></div>
                <h3>Paola Inojosa</h3>
                <p class="contact-role">Desarrolladora del Sistema</p>
                <p class="contact-desc">Analista y desarrolladora principal encargada del diseño, implementación y puesta en marcha del sistema SICAY.</p>
                <div class="contact-links">
                    <a href="mailto:paola.inojosa@hidrovenyaracuy.gob.ve" title="Correo"><i class="fas fa-envelope"></i></a>
                    <a href="#" title="Teléfono"><i class="fas fa-phone"></i></a>
                </div>
            </div>
            <div class="contact-card card-owner">
                <div class="contact-avatar"><i class="fas fa-user-circle"></i></div>
                <h3>Zoivett Aponte</h3>
                <p class="contact-role">Desarrolladora del Sistema</p>
                <p class="contact-desc">Creadora y desarrolladora del sistema, encargada de la arquitectura, base de datos y funcionalidades del sistema.</p>
                <div class="contact-links">
                    <a href="mailto:zoivett.aponte@hidrovenyaracuy.gob.ve" title="Correo"><i class="fas fa-envelope"></i></a>
                    <a href="#" title="Teléfono"><i class="fas fa-phone"></i></a>
                </div>
            </div>
            <div class="contact-card">
                <div class="contact-avatar"><i class="fas fa-building"></i></div>
                <h3>Hidroven Yaracuy</h3>
                <p class="contact-role">Entidad Propietaria</p>
                <p class="contact-desc">Hidrológica del estado Yaracuy, comprometida con la calidad del agua y el servicio público de potabilización.</p>
                <div class="contact-links">
                    <a href="mailto:info@hidrovenyaracuy.gob.ve" title="Correo"><i class="fas fa-envelope"></i></a>
                    <a href="#" title="Ubicación"><i class="fas fa-map-marker-alt"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<footer id="footer" class="main-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <img src="assets/img/EUhOGzfWAAAHZC4-removebg-preview.png" alt="Logo" class="footer-logo">
            <div>
                <h4>Hidroven Yaracuy</h4>
                <p>Departamento de Calidad de Agua</p>
            </div>
        </div>
        <div class="footer-contact">
            <p><i class="fas fa-map-marker-alt"></i> San Felipe, Estado Yaracuy, Venezuela</p>
            <p><i class="fas fa-envelope"></i> calidad@hidrovenyaracuy.gob.ve</p>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Hidroven Yaracuy — Todos los derechos reservados</p>
            <p class="dev-team">Desarrollado por: <strong>Paola Inojosa</strong> & <strong>Zoivett Aponte</strong></p>
        </div>
    </div>
</footer>

<script src="assets/js/index.js"></script>

</body>
</html>
