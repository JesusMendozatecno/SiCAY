<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$conn = $con;

$email = $_POST['email'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Correo inválido";
    exit();
}

$sql = "SELECT id FROM usuario WHERE correo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $token = bin2hex(random_bytes(32));
    $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

    $sql = "UPDATE usuario SET reset_token=?, token_expira=? WHERE correo=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $token, $expira, $email);
    $stmt->execute();

    $link = "https://" . $_SERVER['HTTP_HOST'] . "/aguas/public/index.php?route=reset_password&token=" . $token;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aguasyaracuy85@gmail.com';
        $mail->Password   = 'bmvx yonw qurs msgu';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('aguasyaracuy85@gmail.com', 'Aguas de Yaracuy - Departamento de Calidad');
        $mail->addAddress($email);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Recuperación de contraseña - SICAY';
        $mail->isHTML(true);
        $mail->Body = "
        <div style='font-family:Arial;max-width:500px;margin:auto;padding:30px;background:#f4f7fc;border-radius:15px;'>
            <div style='text-align:center;margin-bottom:25px;'>
                <h2 style='color:#123C69;'>SICAY</h2>
                <p style='color:#666;'>Sistema Integral de Control de Aguas</p>
            </div>
            <div style='background:white;padding:25px;border-radius:12px;'>
                <h3 style='color:#123C69;margin-top:0;'>Recuperación de contraseña</h3>
                <p style='color:#555;'>Haz clic en el siguiente botón para restablecer tu contraseña:</p>
                <div style='text-align:center;margin:25px 0;'>
                    <a href='$link' style='background:#1F6AE1;color:white;padding:14px 30px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;'>Restablecer Contraseña</a>
                </div>
                <p style='color:#999;font-size:0.85rem;'>Este enlace expirará en 1 hora. Si no solicitaste este cambio, ignora este correo.</p>
            </div>
            <p style='text-align:center;color:#aaa;font-size:0.8rem;margin-top:20px;'>Hidroven Yaracuy - Departamento de Calidad</p>
        </div>";
        $mail->send();
        echo "ok";
    } catch (Exception $e) {
        echo "error al enviar correo";
        error_log("PHPMailer error: " . $mail->ErrorInfo);
    }
} else {
    echo "correo no existe";
}
