<?php

class conectar
{
    private $server = "localhost";
    private $user = "root";
    private $pass = "";
    private $bd = "SICAY";
    public $conexion;
    public function conexion()
    {
        $this->conexion = new mysqli(
            $this->server,
            $this->user,
            $this->pass,
            $this->bd
        );
        return $this->conexion;
    }
}

?>