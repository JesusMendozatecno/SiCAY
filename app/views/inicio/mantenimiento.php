<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('under_maintenance'); ?> - SICAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0B1C2D 0%, #123C69 50%, #1F6AE1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
        }
        .bubbles {
            position: fixed; width: 100%; height: 100%; top: 0; left: 0;
            pointer-events: none; z-index: 0;
        }
        .bubble {
            position: absolute; bottom: -100px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
            animation: rise 12s linear infinite;
        }
        @keyframes rise {
            0% { transform: translateY(0) scale(1); opacity: 0; }
            10% { opacity: 0.4; }
            100% { transform: translateY(-110vh) scale(0.5); opacity: 0; }
        }
        .card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 30px;
            padding: 60px 50px;
            text-align: center;
            max-width: 520px;
            width: 90%;
            z-index: 1;
            position: relative;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
        }
        .card i.icon-main { font-size: 4rem; color: #f1c40f; margin-bottom: 20px; }
        .card h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 12px; }
        .card p { color: rgba(255,255,255,0.7); font-size: 0.95rem; margin-bottom: 30px; line-height: 1.6; }
        .card .btn-home {
            display: inline-flex; align-items: center; gap: 10px;
            background: #1F6AE1; color: white; text-decoration: none;
            padding: 14px 32px; border-radius: 12px; font-weight: 600;
            transition: 0.3s; font-size: 0.95rem;
        }
        .card .btn-home:hover { background: #1554b3; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(31,106,225,0.3); }
        @media (max-width: 480px) {
            .card { padding: 40px 25px; }
            .card h1 { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <div class="bubbles" id="bubbles"></div>
    <div class="card">
        <i class="fas fa-tools icon-main"></i>
        <h1><?php echo __('under_maintenance'); ?></h1>
        <p><?php echo __('under_maintenance_desc'); ?></p>
        <a href="index.php?route=index" class="btn-home">
            <i class="fas fa-home"></i> <?php echo __('go_home'); ?>
        </a>
    </div>
    <script>
        for(let i=0;i<20;i++){
            let b=document.createElement('div');
            b.className='bubble';
            let s=Math.random()*60+20;
            b.style.width=s+'px';b.style.height=s+'px';
            b.style.left=Math.random()*100+'vw';
            b.style.animationDuration=(Math.random()*8+6)+'s';
            b.style.animationDelay=(Math.random()*8)+'s';
            document.getElementById('bubbles').appendChild(b);
        }
    </script>
</body>
</html>