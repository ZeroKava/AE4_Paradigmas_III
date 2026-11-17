<?php
// este archivo permite conectar la aplicación php con una base de datos mysql

class Conexion {
    private static ?PDO $pdo = null;// private indica que la propiedad solo puede utilizarse desde dentro de la prolia clase
    //static indica que la propiedad pertenece a la clase y no a un objeto particular, accedemos con self::$pdo y no con $objeto = new Conexion()
    //PDO puede almacenar un objeto de conexion PDO y "?" permite que sea null
    //$pdo es el nombre de la propiedad y "= null" porque originalmente no existe ninguna coneción almacenada

    public static function get(): PDO {// function declara una función o método, get es su nombre y : PDO indica el tipo de dato
    //que debe devolver el método
        if (self::$pdo === null) { // comprueba si la propiedad $pdo todavía tiene el valor null
        //  === comprueba que el valor y el tipo sean iguales
            self::$pdo = new PDO(//crea un nuevo objeto PDO y lo almacena en la propiedad $pdo
                //new permite crear una instancia de una clase y PDO es una clase en php que permite conectarse a diferentes sistemas de bases de datos
                'mysql:host=localhost;dbname=emporio_brillante;charset=utf8mb4',//este parametro indica a PDO donde está la base de datos y que configuración debe utilizar
                'root',//indica el usuario de mysql con el que php se va a conectar
                '',// contraseña
                [//opciones de PDO
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,// indica que pdo debe lanzar una excepción cuando ocurre un error
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,// indica que los resultados se devuelven como arrays asociativos
                    PDO::ATTR_EMULATE_PREPARES   => false,//controla si pdo debe simular consultas preparadas o utilizar preparaciones nativas del controlador de la base de datos
                ]
            );
        }
        return self::$pdo;
    }
}