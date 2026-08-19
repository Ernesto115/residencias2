<?php

class db {
    public $servername = "localhost";
    public $username = "root";
    public $password = "mysql123";
    public $dbname = "databasetransportistas2";
    public $conn = null;

    public function __construct(){
    }

    public function conectar(){
        try {
            $this->conn = new PDO(
                "mysql:host=$this->servername;dbname=$this->dbname;port=3306",
                $this->username,
                $this->password);
            // Configurar PDO para que lance excepciones
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch(PDOException $e) {
            // Se elimina el echo. Opcionalmente puedes usar error_log($e->getMessage()); para ver el error en el log del servidor.
            return null;
        }
    }   

    public function desconectar(){
        $this->conn = null;
    }   

    public function insertar($sql){
        if ($this->conn === null) $this->conectar();
        try {
            $this->conn->exec($sql);
            return true; // Éxito
        } catch(PDOException $e) {
            return false; // Error
        }
    }

    public function actualizar($sql){
        $this->conn = $this->conectar();
        try {
            $this->conn->exec($sql);
            return true; // Éxito
        } catch(PDOException $e) {
            return false; // Error
        }
    }

    public function eliminar($sql){
        $this->conn = $this->conectar();
        try {
            $this->conn->exec($sql);
            return true; // Éxito
        } catch(PDOException $e) {
            return false; // Error
        }
    }

    public function obtenerRegistros($sql){
        $this->conn = $this->conectar();
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $datos = $stmt->fetchAll();
            $this->desconectar();
            return $datos;
        } catch(PDOException $e) {
            return []; // Devuelve un arreglo vacío si hay error
        }
    }

    public function obtenerPorId($sql){
        $this->conn = $this->conectar();
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $datos = $stmt->fetchAll();
            $this->desconectar();
            return $datos;
        } catch(PDOException $e) {
            return []; // Devuelve un arreglo vacío si hay error
        }
    }

    public function buscar($sql){
        $this->conn = $this->conectar();
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $datos = $stmt->fetchAll();
            $this->desconectar();
            return $datos;
        } catch(PDOException $e) {
            return []; // Devuelve un arreglo vacío si hay error
        }
    }
}
?>