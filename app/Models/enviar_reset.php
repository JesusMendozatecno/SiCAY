<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$conn = new mysqli("localhost", "usuario", "password", "basedatos");

$email = $_POST['email'];

// Verificar si existe
$sql = "SELECT id FROM usuarios WHERE correo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $token = bin2hex(random_bytes(32));
    $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // Guardar token
    $sql = "UPDATE usuarios SET reset_token=?, token_expira=? WHERE correo=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $token, $expira, $email);
    $stmt->execute();

    // Crear enlace
    $link = "http://tusitio.com/reset.php?token=" . $token;

    // Enviar correo
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'TU_CORREO@gmail.com';
        $mail->Password = 'TU_APP_PASSWORD';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('TU_CORREO@gmail.com', 'Soporte');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Recuperar contraseña';
        $mail->Body = "Haz clic en el siguiente enlace:<br><a href='$link'>$link</a>";

        $mail->send();

        echo "ok";

    } catch (Exception $e) {
        echo "error al enviar correo";
    }

} else {
    echo "correo no existe";
}
?>