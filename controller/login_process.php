<?php
session_start();
require_once "../model/conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if (empty($user) || empty($pass)) {
        header("Location: ../view/login.php?error=campos_vacios");
        exit();
    }

    try {
        $db = conexion();

        // Consulta directa a la tabla usuarios
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE LOWER(usuario) = LOWER(?) LIMIT 1");
        $stmt->execute([$user]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $hashAlmacenado = $usuario['password'];

            // Comprobación de contraseña (cifrada con BCRYPT o texto plano)
            if (password_verify($pass, $hashAlmacenado) || $pass === $hashAlmacenado) {
                $_SESSION['usuario'] = $usuario['usuario'];
                $_SESSION['nombre']  = $usuario['nombre'];
                $_SESSION['rol']     = $usuario['rol'];

                header("Location: ../view/index.php");
                exit();
            }
        }

        header("Location: ../view/login.php?error=datos_incorrectos");
        exit();

    } catch (Exception $e) {
        header("Location: ../view/login.php?error=db_error");
        exit();
    }
} else {
    header("Location: ../view/login.php");
    exit();
}
?>