<?php
// Persistencia de los formularios de contacto y encuesta
class MensajeModel {
    public function __construct(private PDO $db) {}
    //registra una nueva consulta del formulario de contacto en la tabla consultas y devuelve su id generado
    public function guardarConsulta(string $nombre, string $email, string $servicio, string $consulta): int {
        //prepara la sentencia INSERT con marcadores nominales
        $st = $this->db->prepare('INSERT INTO consultas (nombre,email,servicio,consulta)
                                  VALUES (:nombre,:email,:servicio,:consulta)');
        //vincula y ejecuta de forma segura los parámetros ya validados
        $st->execute([':nombre' => $nombre, ':email' => $email, ':servicio' => $servicio, ':consulta' => $consulta]);
        return (int)$this->db->lastInsertId();
    }

    public function guardarEncuesta(int $satisfaccion, string $recomienda, string $comentario): int {
        $st = $this->db->prepare('INSERT INTO encuestas (satisfaccion,recomienda,comentario)
                                  VALUES (:s,:r,:c)');
        $st->execute([':s' => $satisfaccion, ':r' => $recomienda, ':c' => $comentario]);
        return (int)$this->db->lastInsertId();
    }
}
