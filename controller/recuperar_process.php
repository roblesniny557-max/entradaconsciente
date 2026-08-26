<?php
session_start();
require_once "../model/conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user          = trim($_POST['usuario'] ?? '');
    $claveMaestra  = trim($_POST['claveMaestra'] ?? '');
    $nuevaPassword = trim($_POST['nuevaPassword'] ?? '');

    if (empty($user) || empty($claveMaestra) || empty($nuevaPassword)) {
        header("Location: ../view/login.php?error=campos_vacios");
        exit();
    }

    // Validación estricta de la clave maestra
    if ($claveMaestra !== "consciente2026") {
        header("Location: ../view/login.php?error=clave_maestra_incorrecta");
        exit();
    }

    try {
        $db = conexion();

        // Verificar si el usuario existe en la tabla usuarios
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE LOWER(usuario) = LOWER(?) LIMIT 1");
        $stmt->execute([$user]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            header("Location: ../view/login.php?error=usuario_no_encontrado");
            exit();
        }

        // Generar Hash seguro BCRYPT para la nueva clave
        $newHash = password_hash($nuevaPassword, PASSWORD_BCRYPT);

        // Actualización en la base de datos
        $updateStmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $updateStmt->execute([$newHash, $usuario['id']]);

        header("Location: ../view/login.php?success=1");
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