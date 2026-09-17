<?php
namespace App\Controllers;

// Importamos el molde del Modelo
use App\Models\ClienteModel;

class EstadoController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function ver() {
        // 1. Preparamos la respuesta base
        $respuesta = [
            'status' => 'ok',
            'message' => 'La API responde',
            'base_datos' => 'conectada',
            'tabla_usuarios' => 'no existe todavía',
            'cantidad_usuarios' => 0
        ];

        // 2. Instanciamos el Modelo (El Cocinero) pasándole la conexión
        $clienteModel = new ClienteModel($this->db);

        // 3. Le pedimos los datos al Modelo
        $cantidad = $clienteModel->contarClientes();

        // 4. Lógica de negocio (Controlador evaluando qué pasó)
        if ($cantidad !== false) {
            $respuesta['tabla_usuarios'] = 'ok';
            $respuesta['cantidad_usuarios'] = $cantidad;
        } else {
            $respuesta['tabla_usuarios'] = 'Falta crear la tabla usuarios (phpMyAdmin o init.sql)';
        }

        // 5. Entregamos la caja cerrada (JSON) al repartidor
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }
}