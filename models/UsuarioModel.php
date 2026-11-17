<?php
// este archivo realiza operaciones relacionadas con los usurios en la base de datos, permite buscar un usuario por su nombre
class UsuarioModel {
    public function __construct(private PDO $db) {}// __construct es el constructor, PDO establece el tipo de dato recibido
    // $db es el nombre de la propiedad que almacenará la conexión a la base de datos

    public function buscarPorUsuario(string $usuario): ?array {//string $usuario recibe un parámetro llamado $usuario que debe ser una cadena de texto
    //: ?array indica el tipo de dato que puede devolver
        $st = $this->db->prepare('SELECT id, usuario, password FROM usuarios WHERE usuario = :usuario');// $st variable donde se almacena el reaultado de preparar la consulta
        // $this hace referencia al objeto actual, ->db accede a la conexión PDO almacenada en el constructor
        // ->prepare() método de PDO que prepara una consulta SQL
        $st->execute([':usuario' => $usuario]);//->execute() ejecuta la consulta
        return $st->fetch() ?: null;// fetch() obtiene una fila del resultado de la consulta
    }

    public function crear(string $usuario, string $password): int {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $st = $this->db->prepare('INSERT INTO usuarios (usuario, password) VALUES (:usuario, :password)');
        $st->execute([
            ':usuario'  => $usuario,
            ':password' => $hash,
        ]);
        return (int)$this->db->lastInsertId();
    }
}