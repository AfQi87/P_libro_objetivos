<?php
  include "./templates/nav.php";
    echo('<div class="container">');

      $id_cli = $_POST["id_cli"];
      $id_lib = $_GET["id"];
      $coleccion = $_GET["coleccion"];
      $estado = $_GET["estado"];
      $busqueda = 'EXITOSA';
      $r = array();
      $cont = 0;

      $objetivos = array();
      $base_hechos = array();
      $obj_premisa = array();
      $obj_premisa1 = array();
      $validaciones = array();
      $encontrado = 0;
      $prem = 0;
      $prem1 = 0;
      $id_reglas = 1;
      $reglas = array();
      //=========================================================================asignando BH, busqueda
      $base_hechos["BUSQUEDA"] = $busqueda;
      array_push($reglas, 'R1');
      //=========================================================================asignando BH, código-libro
      $sql = " SELECT count(libro.id) AS cantidad FROM libro
        WHERE libro.id = $id_lib";
      $resultado = $conexion->query($sql);
      $row = $resultado->fetch_array();
      $cantidad = $row["cantidad"];

      if($cantidad > 0){
        $codigo = 'VALIDO';
        array_push($reglas, 'R5');
      }else{
        $codigo = 'NO_VALIDO';
        array_push($reglas, 'R7');
      }
      $base_hechos["CODIGO"] = $codigo;
      //=========================================================================asignando BH, estado-libro
      $base_hechos["ESTADO"] = $estado;
      if($base_hechos["ESTADO"] != 'DISPONIBLE'){
        array_push($reglas, 'R8');
      }
      //=========================================================================asignando BH, consulta-libro
      if((strcmp($coleccion, 'GENERAL') === 0) || (strcmp($coleccion, 'RESERVA') === 0)){
        $consulta = 'EXTERNA';
      }else{
        $consulta = 'EN_SALA';
        array_push($reglas, 'R9');
      }
      $base_hechos["CONSULTA"] = $consulta;
      //=========================================================================asignando BH, id-usuario
      $sql = "SELECT count(usuario.id) AS cant_usu FROM usuario
        WHERE usuario.id = $id_cli";
      $resultado = $conexion->query($sql);
      $row = $resultado->fetch_array();
      $cant_usu = $row["cant_usu"];

      if($cant_usu > 0){
        $id_usu = 'REGISTRADO';
        array_push($reglas, 'R6');
      }else{
        $id_usu = 'NO_REGISTRADO';
        array_push($reglas, 'R10');
      }
      $base_hechos["ID"] = $id_usu;
      //=========================================================================asignando BH, pres-pen
      $sql = "SELECT COUNT(prestamo.usuario_id) AS CPP FROM prestamo
        JOIN usuario ON prestamo.usuario_id=usuario.id
        JOIN pres_estado ON prestamo.pres_estado_id=pres_estado.id
        WHERE pres_estado.estado='PENDIENTE' AND usuario_id = $id_cli
        GROUP BY (usuario.id)";
      $resultado = $conexion->query($sql);
      $row = $resultado->fetch_array();
      $cant_pres = $row["CPP"];
      if($cant_pres < 3){
        $prest_pend = 'NO_EXCEDE';
      }else{
        $prest_pend = 'EXCEDE';
        array_push($reglas, 'R11');
      }
      $base_hechos["PRES_PEN"] = $prest_pend;
      //=========================================================================asignando BH, sanciones
      $sql = "SELECT COUNT(sancion.id) AS SP FROM sancion
      JOIN prestamo ON sancion.prestamo_id=prestamo.id
      JOIN usuario ON prestamo.usuario_id=usuario.id
      JOIN san_estado ON san_estado.id=sancion.san_estado_id
      WHERE san_estado.estado <> 'CUMPLIDO' AND lev_fecha > CURDATE() AND usuario_id = $id_cli
      GROUP BY (prestamo.usuario_id)";
      $resultado = $conexion->query($sql);
      $row = $resultado->fetch_array();
      $can_san = $row["SP"];
      if($can_san > 0){
        $sancion = 'VIGENTE';
        array_push($reglas, 'R12');
      }else{
        $sancion = 'NO_VIGENTE';
      }
      $base_hechos["SANCION"] = $sancion;
      //==========================================================================================================================
      $sql = "SELECT regla.id, objeto.nombre FROM obj_regla
        JOIN regla ON obj_regla.regla_id=regla.id
        JOIN objeto ON obj_regla.objeto_id=objeto.id
        JOIN obj_tipo ON obj_tipo.des='CONCLUSION'
        WHERE valor LIKE 'APROBADO'";
      $resultado = $conexion->query($sql);
      $row = $resultado->fetch_array();
      $objeto = strtoupper($row["nombre"]);
      array_push($objetivos, strtoupper($row["nombre"]));
      $regla = strtoupper($row["id"]);
      //========================================================================================================================
      $sql = "SELECT objeto.nombre FROM obj_regla
      JOIN regla ON obj_regla.regla_id=regla.id
      JOIN objeto ON obj_regla.objeto_id=objeto.id
      JOIN obj_tipo ON obj_regla.obj_tipo_id=obj_tipo.id
      WHERE regla.id=$regla AND obj_tipo.des LIKE 'PREMISA'";

      if ($resultado = $conexion->query($sql)){
        while($row = $resultado->fetch_array()){
          array_push($obj_premisa, $row['nombre']);
        }
      }

      //=========================================================================================================================
      foreach($obj_premisa as $ind => $obj){
        foreach($base_hechos as $hecho => $valor){
          if($obj == $hecho){
            $encontrado = 1;
          }
        }
        unset($obj_premisa[$ind]);
        if($encontrado == 1){
          $obj_premisa1[$obj] = 'EXITOSA';
          $encontrado = 0;
        }else{
          array_push($objetivos, $obj);
          $sql = "SELECT regla.id as id FROM obj_regla
            JOIN regla ON obj_regla.regla_id=regla.id
            JOIN objeto ON obj_regla.objeto_id=objeto.id
            JOIN obj_tipo ON obj_regla.obj_tipo_id=obj_tipo.id AND obj_tipo.des='CONCLUSION'
            WHERE objeto.nombre = '$obj'";
          if ($resultado = $conexion->query($sql)){
            while($row = $resultado->fetch_array()){
              array_push($r, $row['id']);
            }
          }

          foreach($r as $indice => $reg){
            $sql = "SELECT objeto.nombre FROM obj_regla
              JOIN regla ON obj_regla.regla_id=regla.id
              JOIN objeto ON obj_regla.objeto_id=objeto.id
              JOIN obj_tipo ON obj_regla.obj_tipo_id=obj_tipo.id
              WHERE regla.id=$reg AND obj_tipo.des LIKE 'PREMISA'";

            if ($resultado = $conexion->query($sql)){
              while($row = $resultado->fetch_array()){
                foreach($obj_premisa as $obj_p){
                  if($obj_p == $row['nombre']){
                    $cont = 1;
                  }
                }
                if($cont == 0){
                  array_push($obj_premisa, $row['nombre']);
                }else{
                  $cont = 0;
                }
              }
            }
            unset($r[$indice]);
          }
          foreach($obj_premisa as $indice => $value){
            foreach($base_hechos as $key => $val){
              if($value == $key){
                $validaciones[$key] = $val;
                unset($obj_premisa[$indice]);
              }
            }
          }
          if($id_reglas == 1){
            foreach($validaciones as $index => $valor){
              $sql = "SELECT obj_regla.valor FROM obj_regla
              JOIN regla ON obj_regla.regla_id=regla.id
              JOIN objeto ON obj_regla.objeto_id=objeto.id
              JOIN obj_tipo ON obj_regla.obj_tipo_id=obj_tipo.id
              WHERE objeto.nombre = '$index' AND obj_tipo.des LIKE 'PREMISA' AND regla.id = 5";
              $resultado = $conexion->query($sql);
              $row = $resultado->fetch_array();
              if(strtoupper($row["valor"]) == $valor){
                $prem++;
                unset($validaciones[$index]);
              }
            }
            if($prem >= 3){
              $obj_premisa1['LIBRO'] = 'APTO';
              $base_hechos['LIBRO'] = 'APTO';
            }else{
              $obj_premisa1['LIBRO'] = 'NO_APTO';
              $base_hechos['LIBRO'] = 'NO_APTO';
              array_push($reglas, 'R3');
            }
          }else{
            foreach($validaciones as $index => $valor){
              $sql = "SELECT obj_regla.valor FROM obj_regla
              JOIN regla ON obj_regla.regla_id=regla.id
              JOIN objeto ON obj_regla.objeto_id=objeto.id
              JOIN obj_tipo ON obj_regla.obj_tipo_id=obj_tipo.id
              WHERE objeto.nombre = '$index' AND obj_tipo.des LIKE 'PREMISA' AND regla.id = 6";
              $resultado = $conexion->query($sql);
              $row = $resultado->fetch_array();
              if(strtoupper($row["valor"]) == $valor){
                $prem++;
                unset($validaciones[$index]);
              }
            }
            if($prem >= 3){
              $obj_premisa1['USUARIO'] = 'AUTORIZADO';
              $base_hechos['USUARIO'] = 'AUTORIZADO';
            }else{
              $obj_premisa1['USUARIO'] = 'NO_AUTORIZADO';
              $base_hechos['USUARIO'] = 'NO_AUTORIZADO';
              array_push($reglas, 'R4');
            }
          }
          $prem=0;
          $id_reglas ++;
        }
      }
      foreach($base_hechos as $index => $valor){
        $sql = "SELECT obj_regla.valor FROM obj_regla
        JOIN regla ON obj_regla.regla_id=regla.id
        JOIN objeto ON obj_regla.objeto_id=objeto.id
        JOIN obj_tipo ON obj_regla.obj_tipo_id=obj_tipo.id
        WHERE objeto.nombre = '$index' AND obj_tipo.des LIKE 'PREMISA' AND regla.id = 1";
        $resultado = $conexion->query($sql);
        $row = $resultado->fetch_array();
        if(strtoupper($row["valor"]) == $valor){
          $prem++;
        }
      }

      if($prem >= 3){
        echo('
        <script>
          Swal.fire({
            title: "Prestamo Aprobado",
            text: "Su solicitud cumple con todas las condiciones",
            icon: "success",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Información"
          }).then((result) => {
            if (result.isConfirmed) {
              Swal.fire(
                "Prestamo Aprobado!",
                "Se cumplieron las siguientes reglas<br><br>');
                foreach($reglas as $reg){
                  switch($reg){
                    case 'R1':
                      echo('<h6 align=left>R1: La busqueda del libro fue exitosa</h6>');
                      break;
                    case 'R2':
                      echo('<h6 align=left>R2: La busqueda del libro no fue exitosa</h6>');
                      break;
                    case 'R3':
                      echo('<h6 align=left>R3: El libro no fue Apto para el prestamo</h6>');
                      break;
                    case 'R4':
                      echo('<h6 align=left>R4: El usuario no fue autorizado para el prestamo</h6>');
                      break;
                    case 'R5':
                      echo('<h6 align=left>R5: El libro fue Apto para el prestamo</h6>');
                      break;
                    case 'R6':
                      echo('<h6 align=left>R6: El usuario fue autorizado para el prestamo</h6>');
                      break;
                    case 'R7':
                      echo('<h6 align=left>R7: El codigo del libro no es valido</h6>');
                      break;
                    case 'R8':
                      echo('<h6 align=left>R8: El estado del libro no esta disponible</h6>');
                      break;
                    case 'R9':
                      echo('<h6 align=left>R9: El estado de la consulta del libro es en sala</h6>');
                      break;
                    case 'R10':
                      echo('<h6 align=left>R10: El usuario no esta registrado</h6>');
                      break;
                    case 'R11':
                      echo('<h6 align=left>R11: El usuario excede el numero de prestamos posibles (mayor a 3 libros)</h6>');
                      break;
                    case 'R12':
                      echo('<h6 align=left>R12: El usuario tiene sanciones vigentes</h6>');
                      break;
                  }
                }
                echo('",
                "success"
              )
            }
          })
        </script>
      ');
      }else{
        echo('
        <script>
          Swal.fire({
            title: "Prestamo No Aprobado",
            text: "Su solicitud no cumple con todas las condiciones",
            icon: "error",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Información"
          }).then((result) => {
            if (result.isConfirmed) {
              Swal.fire(
                "Prestamo No Aprovado!",
                "No se cumplieron las siguientes reglas<br><br>');
                foreach($reglas as $reg){
                  switch($reg){
                    case 'R2':
                      echo('<h6 align=left>R2: La busqueda del libro no fue exitosa</h6>');
                      break;
                    case 'R3':
                      echo('<h6 align=left>R3: El libro no fue Apto para el prestamo</h6>');
                      break;
                    case 'R4':
                      echo('<h6 align=left>R4: El usuario no fue autorizado para el prestamo</h6>');
                      break;
                    case 'R7':
                      echo('<h6 align=left>R7: El codigo del libro no es valido</h6>');
                      break;
                    case 'R8':
                      echo('<h6 align=left>R8: El estado del libro no esta disponible</h6>');
                      break;
                    case 'R9':
                      echo('<h6 align=left>R9: El estado de la consulta del libro es en sala</h6>');
                      break;
                    case 'R10':
                      echo('<h6 align=left>R10: El usuario no esta registrado</h6>');
                      break;
                    case 'R11':
                      echo('<h6 align=left>R11: El usuario excede el numero de prestamos posibles (mayor a 3 libros)</h6>');
                      break;
                    case 'R12':
                      echo('<h6 align=left>R12: El usuario tiene sanciones vigentes</h6>');
                      break;
                  }
                }
                echo('",
                "warning"
              )
            }
          })
        </script>
      ');
      }
    echo('</div>');
  include "./templates/footer.php";
?>
