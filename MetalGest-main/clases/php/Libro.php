<?php
class Libro {
    public $id;
    public $nombre;
    public $cantidad;

    public function __construct($nombre = null, $cantidad = null, $id = null) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->cantidad = $cantidad;
    }
}
