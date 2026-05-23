<?php

$conn = new mysqli("localhost", "root", "", "sicay");

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = $_POST['email'];

$token = bin2hex(random_bytes(32));

$stmt = $conn->prepare("
INSERT INTO password_resets(email, token)
VALUES (?, ?)
");

$stmt->bind_param("ss", $email, $token);
$stmt->execute();

$link = "http://localhost/AGUAS/reset-password.php?token=$token";

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    $mail->Username = 'TU_CORREO@gmail.com';

    $mail->Password = 'TU_APP_PASSWORD';

    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('TU_CORREO@gmail.com', 'sicay');

    $mail->addAddress($email);

    $mail->isHTML(true);

    $mail->Subject = 'Recuperar contraseña';

    $mail->Body = "
        <h2>Recuperación de contraseña</h2>

        <p>Haz clic en el siguiente enlace:</p>

        <a href='$link'>Recuperar contraseña</a>
    ";

    $mail->send();

    echo "Correo enviado correctamente";

} catch (Exception $e) {

    echo "Error al enviar correo: {$mail->ErrorInfo}";
}
?>