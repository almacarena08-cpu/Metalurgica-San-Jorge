<?php
require_once __DIR__ . '/BaseDAO.php';

class PedidoDAO extends BaseDAO {
    public function listar() {
        $stmt = $this->db()->query(
            'SELECT p.id_pedido, p.id_chat_origen, p.fecha, p.descripcion, p.cantidad, p.material, p.fecha_entrega,
                    p.estado, c.razon_social, c.email
             FROM pedido p
             INNER JOIN cliente c ON c.id_cliente = p.id_cliente
             ORDER BY p.id_pedido DESC'
        );
        return $stmt->fetchAll();
    }

    public function crear(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $idCliente = (int)($datos['id_cliente'] ?? 0);
            if ($idCliente <= 0) {
                $stmtCliente = $pdo->prepare(
                    'INSERT INTO cliente (razon_social, cuit, telefono, email, direccion)
                     VALUES (:razon_social, :cuit, :telefono, :email, :direccion)'
                );
                $stmtCliente->execute([
                    ':razon_social' => $datos['razon_social'],
                    ':cuit' => $datos['cuit'] ?? '',
                    ':telefono' => $datos['telefono'] ?? '',
                    ':email' => $datos['email'] ?? '',
                    ':direccion' => $datos['direccion'] ?? ''
                ]);
                $idCliente = (int)$pdo->lastInsertId();
            }

            $stmtPedido = $pdo->prepare(
                'INSERT INTO pedido (id_cliente, id_chat_origen, fecha, descripcion, cantidad, material, fecha_entrega, estado)
                 VALUES (:id_cliente, NULL, CURDATE(), :descripcion, :cantidad, :material, :fecha_entrega, :estado)'
            );
            $stmtPedido->execute([
                ':id_cliente' => $idCliente,
                ':descripcion' => $datos['descripcion'],
                ':cantidad' => (int)$datos['cantidad'],
                ':material' => $datos['material'],
                ':fecha_entrega' => $datos['fecha_entrega'] ?? null,
                ':estado' => 'Pendiente'
            ]);
            $idPedido = (int)$pdo->lastInsertId();
            $this->insertarSeguimiento($pdo, $idPedido, 'Pedido registrado', 'Pedido creado desde Administración.', 'Administración', $datos['id_usuario'] ?? null);
            $pdo->commit();
            return $idPedido;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function crearDesdeConversacion(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $idChat = (int)($datos['id_chat'] ?? 0);
            $chat = $pdo->prepare(
                'SELECT c.id_chat, c.id_cliente, p.id_pedido
                 FROM chat_conversacion c
                 LEFT JOIN pedido p ON p.id_chat_origen = c.id_chat
                 WHERE c.id_chat = :id_chat
                 FOR UPDATE'
            );
            $chat->execute([':id_chat' => $idChat]);
            $conversation = $chat->fetch();
            if (!$conversation) throw new InvalidArgumentException('La conversación no existe.');
            if (!empty($conversation['id_pedido'])) throw new InvalidArgumentException('Esta conversación ya tiene un pedido asociado.');

            $stmt = $pdo->prepare(
                'INSERT INTO pedido (id_cliente, id_chat_origen, fecha, descripcion, cantidad, material, fecha_entrega, estado)
                 VALUES (:id_cliente, :id_chat, CURDATE(), :descripcion, :cantidad, :material, :fecha_entrega, :estado)'
            );
            $stmt->execute([
                ':id_cliente' => (int)$conversation['id_cliente'],
                ':id_chat' => $idChat,
                ':descripcion' => $datos['descripcion'],
                ':cantidad' => (int)$datos['cantidad'],
                ':material' => $datos['material'],
                ':fecha_entrega' => $datos['fecha_entrega'] ?? null,
                ':estado' => 'Pedido registrado'
            ]);
            $idPedido = (int)$pdo->lastInsertId();
            $this->insertarSeguimiento(
                $pdo,
                $idPedido,
                'Pedido registrado',
                'Administración confirmó los datos acordados y registró el pedido.',
                'Administración',
                $datos['id_usuario'] ?? null
            );
            $updateChat = $pdo->prepare(
                'UPDATE chat_conversacion SET estado = "Pedido registrado", fecha_actualizacion = NOW() WHERE id_chat = :id_chat'
            );
            $updateChat->execute([':id_chat' => $idChat]);
            $pdo->commit();
            return $idPedido;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    private function insertarSeguimiento(PDO $pdo, int $idPedido, string $estado, string $descripcion, string $area, $idUsuario) {
        $stmt = $pdo->prepare(
            'INSERT INTO pedido_seguimiento (id_pedido, estado, descripcion, area, id_usuario, visible_cliente, fecha)
             VALUES (:id_pedido, :estado, :descripcion, :area, :id_usuario, 1, NOW())'
        );
        $stmt->execute([
            ':id_pedido' => $idPedido,
            ':estado' => $estado,
            ':descripcion' => $descripcion,
            ':area' => $area,
            ':id_usuario' => $idUsuario && (int)$idUsuario > 0 ? (int)$idUsuario : null
        ]);
    }
}
