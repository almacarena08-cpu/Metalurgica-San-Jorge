<?php
require_once __DIR__ . '/clases/php/database.php';
require_once __DIR__ . '/clases/php/Libro.php';
require_once __DIR__ . '/clases/daos/LibroDAO.php';

$dao = new LibroDAO();

$action = $_GET['action'] ?? null;
if ($action === 'create') {
    $nombre = $_POST['nombre'] ?? 'Libro nuevo';
    $cantidad = intval($_POST['cantidad'] ?? 1);
    $libro = new Libro($nombre, $cantidad);
    $created = $dao->create($libro);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'libro' => ['id' => $created->id, 'nombre' => $created->nombre, 'cantidad' => $created->cantidad]]);
    exit;
}

if ($action === 'list' || $action === 'list_admin') {
    $list = $dao->getAll();
    $out = array_map(function($l){ return ['id' => $l->id, 'nombre' => $l->nombre, 'cantidad' => $l->cantidad]; }, $list);
    header('Content-Type: application/json');
    echo json_encode($out);
    exit;
}

if ($action === 'list_client') {
    $list = $dao->getAll();
    $filtered = array_filter($list, function($l){ return $l->cantidad > 0; });
    $out = array_map(function($l){ return ['id' => $l->id, 'nombre' => $l->nombre, 'cantidad' => $l->cantidad]; }, $filtered);
    header('Content-Type: application/json');
    echo json_encode(array_values($out));
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    $ok = false;
    if ($id > 0) {
        $ok = $dao->delete($id);
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

if ($action === 'request') {
    $nombre = $_POST['nombre'] ?? null;
    $cantidad = intval($_POST['cantidad'] ?? 1);
    header('Content-Type: application/json');
    if (!$nombre) {
        echo json_encode(['success' => false, 'message' => 'nombre requerido']);
        exit;
    }
    $res = $dao->requestByName($nombre, $cantidad);
    if ($res) {
        echo json_encode(['success' => true, 'libro' => ['id' => $res->id, 'nombre' => $res->nombre, 'cantidad' => $res->cantidad]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'no disponible o no encontrado']);
    }
    exit;
}

echo "API mínima: usa ?action=list, ?action=list_client, ?action=create (POST nombre,cantidad), ?action=delete (POST id), ?action=request (POST nombre,cantidad)";
