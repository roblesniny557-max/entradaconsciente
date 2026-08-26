<?php
    require_once "../model/conexion.php";
    $db = conexion();

    $id = $_GET['id'];

    try {
        $sql = "DELETE FROM datosbasicos WHERE id_estudiante = ?";
        $ejecutar = $db->prepare($sql);
        $ejecutar->execute([$id]);

        header("Location: ../view/index.php?res=del&mensajito=Estudiante eliminado correctamente");
        exit();

    } catch(Exception $e) {
        header("Location: ../view/index.php?res=error&mensajito=Error al eliminar");
        exit();
    }
?>
