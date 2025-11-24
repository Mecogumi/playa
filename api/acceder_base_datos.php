<?php
function abrirConexion(){
    //Abre una conexion a la base de datos
    $pconector = mysqli_connect($GLOBALS["servidor"], $GLOBALS["usuario"],$GLOBALS["contrasena"]) or die(mysqli_connect_error());
    return $pconector;
}
//---------------------------------------
function seleccionarBaseDatos ($pconector){
    //Permite seleccionar una base de datos
    mysqli_select_db($pconector, $GLOBALS["base_datos"])or die(mysqli_connect_error($pconector));
}
//---------------------------------------
function cerrarConexion($pconector){
    //Cierra una conexión
    mysqli_close($pconector);
}
//----------------------------------------
function existeRegistro($pconector, $cquery){
    //Verifica la existencia de la información solicitada(a traves de una sentencia SQL)
    //en la base de datos.
    $lexiste_referencia = true;
    $lresult = mysqli_query($pconector, $cquery);

    if (!$lresult){
        $cerror = "No fue posible recuperar la informaci&oacute;n de la base de datos.<br>";
        $cerror .= "SQL: $cquery <br>";
        $cerror .= "Descripci&oacute;n: " .mysqli_connect_error($pconector);
        die($cerror);
    }
    else{
        //Verifica que no existe un registro igual al qe se va a insertar
        if (mysqli_num_rows($lresult) == 0){
            $lexiste_referencia = false;
        }
    }

    //Libera la memoria asociada al resultado de la consulta
    mysqli_free_result($lresult);

    return $lexiste_referencia;

}
//-----------------------------------------
function insertarDatos($pconector, $cquery){

    //Inserta un registro en las base de datos
    $lentrada_creada = false;
    $lresult = mysqli_query($pconector,$cquery);
    if (!$lresult){
        $cerror = "Ocurri&oacute; un error al acceder a la base de datos.<br>";
        $cerror .= "SQL: $cquery <br>";
        $cerror .= "Descripci&oacute;n: ".mysqli_connect_error($pconector);
        die ($cerror);
    }
    else{
        if (mysqli_affected_rows($pconector) > 0){
            $lentrada_creada = true;
        }
    }

    return $lentrada_creada;
}
//-----------------------------------------
function extraerRegistro($pconector, $cquery){

    /*Lee información solicitada (a través de una sentencia SQL) de la base de datos y la almacena
    en un arreglo que devuelve como parametro de salida.
    Advertencia: utilizar esta función únicamente cuando se espere un sólo registro como resultado*/

    $aregistro = array();
    $lresult = mysqli_query($pconector, $cquery);
    if(!$lresult){
        $cerror = "No fue posible recuperar la informaci&oacute;n de la base de datos.<br>";
        $cerror .= "SQL: $cquery <br>";
        $cerror .= "Descripci&oacute;n: ".mysqli_connect_error($pconector);
        die($cerror);
    }
    else{
        if(mysqli_num_rows($lresult) > 0){
            $aregistro = mysqli_fetch_array($lresult);
        }
    }

    //Libera la memoria asociada al resultado de la consulta
    mysqli_free_result($lresult);
    reset($aregistro);

    return $aregistro;
}
//---------------------------------------
function editarDatos($pconector, $cquery){

    //Modifica, edita o actualiza uno o más registros de la base de datos
    $ledicion_completada = false;
    $lresult = mysqli_query($pconector, $cquery);
    if (!$lresult){
        $cerror = "Ocurri&oacute; un error al acceder a la base da datos. <br>";
        $cerror .= "SQL: $cquery <br>";
        $cerror .= "Descripci&oacute;n ".mysqli_connect_error($pconector);
        die($cerror);
    }
    else{
        $ledicion_completada = true;
    }

    return $ledicion_completada;
}
//-----------------------------------------
function borrarDatos ($pconector, $cquery){

    //Elimina uno o más registros de la base de datos
    $laccion_completada = false;
    $lresult = mysqli_query ($pconector, $cquery);
    if (!$lresult){
        $cerror = "Ocurri&oacute; un error al acceder a la base de datos. <br>";
        $cerror .= "SQL: $cquery <br>";
        $cerror .= "Descripci&oacute;n ".mysqli_connect_error($pconector);
        die($cerror);
    }
    else{
        $laccion_completada = true;
    }

    return $laccion_completada;
}
?>