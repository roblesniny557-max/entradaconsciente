<?php
    require_once "../model/conexion.php";
    $db = conexion();

    $id  = $_POST['id_estudiante'];
    $doc = $_POST['documento'];
    $nom = $_POST['nombre'];
    $cel = $_POST['celular'];
    $gra = $_POST['grado'];
    $sex = $_POST['sexo'];

    try {
        $sql = "UPDATE datosbasicos SET documento = ?, nombre = ?, celular = ?, grado = ?, sexo = ? WHERE id_estudiante = ?";
        $ejecutar = $db->prepare($sql);
        $ejecutar->execute([$doc, $nom, $cel, $gra, $sex, $id]);

        header("Location: ../view/index.php?res=edit&mensajito=Estudiante actualizado correctamente");
        exit();

    } catch(Exception $e) {
        header("Location: ../view/index.php?res=error&mensajito=Error al actualizar");
        exit();
    }
?>
