<?php
//se comunica con la base de datos para realizar operaciones sobre la tabla productos
class ProductoModel {
    public function __construct(private PDO $db) {}// __construct es el constructor, PDO establece el tipo de dato recibido

    public function listar(?string $tipo = null): array { //?string el parametro debe ser una cadena de texto o null, $tipo nombre del parametro
    //: array indica que debe devolver un array 
        if ($tipo !== null) { //si se recibió un tipo de producto se ejecuta
            $st = $this->db->prepare('SELECT * FROM productos WHERE tipo = :tipo ORDER BY id'); //$st variable donde se guarda la consulta preparada
            //$this->db accede a la conexión PDO
            $st->execute([':tipo' => $tipo]);
            //ejecuta la consulta
        } else {
            $st = $this->db->query('SELECT * FROM productos ORDER BY id');
            //ejecuta directamente una consulta SQL que no necesita parámetros
        }
        return $st->fetchAll(); //fechtAll() recupera todas las filas obtenidas por la consulta
    }

    public function obtener(int $id): ?array { //recibe un entero
        $st = $this->db->prepare('SELECT * FROM productos WHERE id = :id');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    public function crear(array $p): int {
        $st = $this->db->prepare('INSERT INTO productos (nombre,tipo,categoria,descripcion,precio,imagen)
        VALUES (:nombre,:tipo,:categoria,:descripcion,:precio,:imagen)');
        $st->execute($p);
        return (int)$this->db->lastInsertId(); //(int) convierte el resultado en entero
        //->lastInsertId() obtiene id generado por una inserción en esa conexión
    }

    public function actualizar(int $id, array $p): bool {
        $st = $this->db->prepare('UPDATE productos SET nombre=:nombre, tipo=:tipo, categoria=:categoria,
        descripcion=:descripcion, precio=:precio, imagen=:imagen WHERE id=:id');
        $st->execute($p + [':id' => $id]); // + es para unir arrays
        return $this->obtener($id) !== null;
    }

    public function eliminar(int $id): bool {
        $st = $this->db->prepare('DELETE FROM productos WHERE id = :id');
        $st->execute([':id' => $id]);
        return $st->rowCount() > 0; //rowCount() devuelve la cantidad de filas afectadas, >0 comprueba que sean más que 0
    }
}
