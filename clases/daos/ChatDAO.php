<?php
require_once __DIR__ . '/BaseDAO.php';

class ChatDAO extends BaseDAO {
    public function listar() {
        return $this->db()->query(
            'SELECT c.id_chat, c.id_cliente, c.asunto, c.estado, c.fecha_creacion, c.fecha_actualizacion,
                    cl.razon_social AS nombre, cl.email,
                    COALESCE(ultimo.mensaje, "") AS ultimo_mensaje,
                    COALESCE(ultimo.autor_tipo, "") AS ultimo_autor
             FROM chat_conversacion c
             INNER JOIN cliente cl ON cl.id_cliente = c.id_cliente
             LEFT JOIN chat_mensaje_web ultimo ON ultimo.id_mensaje = (
                SELECT cm.id_mensaje
                FROM chat_mensaje_web cm
                WHERE cm.id_chat = c.id_chat
                ORDER BY cm.fecha DESC, cm.id_mensaje DESC
                LIMIT 1
             )
             ORDER BY c.fecha_actualizacion DESC, c.id_chat DESC'
        )->fetchAll();
    }

    public function listarPorCliente(int $idCliente) {
        $stmt = $this->db()->prepare(
            'SELECT c.id_chat, c.id_cliente, c.asunto, c.estado, c.fecha_creacion, c.fecha_actualizacion,
                    cl.razon_social AS nombre, cl.email,
                    COALESCE(ultimo.mensaje, "") AS ultimo_mensaje,
                    COALESCE(ultimo.autor_tipo, "") AS ultimo_autor
             FROM chat_conversacion c
             INNER JOIN cliente cl ON cl.id_cliente = c.id_cliente
             LEFT JOIN chat_mensaje_web ultimo ON ultimo.id_mensaje = (
                SELECT cm.id_mensaje
                FROM chat_mensaje_web cm
                WHERE cm.id_chat = c.id_chat
                ORDER BY cm.fecha DESC, cm.id_mensaje DESC
                LIMIT 1
             )
             WHERE c.id_cliente = :id_cliente
             ORDER BY c.fecha_actualizacion DESC, c.id_chat DESC'
        );
        $stmt->execute([':id_cliente' => $idCliente]);
        return $stmt->fetchAll();
    }

    public function obtenerMensajes(int $idChat) {
        $stmt = $this->db()->prepare(
            'SELECT cm.id_mensaje, cm.id_chat, cm.id_usuario, cm.autor_tipo, cm.mensaje, cm.fecha,
                    CASE
                        WHEN cm.autor_tipo = "Cliente" THEN cl.razon_social
                        ELSE CONCAT(COALESCE(u.nombre, ""), " ", COALESCE(u.apellido, ""))
                    END AS autor_nombre
             FROM chat_mensaje_web cm
             INNER JOIN chat_conversacion c ON c.id_chat = cm.id_chat
             INNER JOIN cliente cl ON cl.id_cliente = c.id_cliente
             LEFT JOIN usuario u ON u.id_usuario = cm.id_usuario
             WHERE cm.id_chat = :id_chat
             ORDER BY cm.fecha ASC, cm.id_mensaje ASC'
        );
        $stmt->execute([':id_chat' => $idChat]);
        return $stmt->fetchAll();
    }

    public function crearConversacionCliente(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO chat_conversacion (id_cliente, asunto, estado, fecha_creacion, fecha_actualizacion)
                 VALUES (:id_cliente, :asunto, "Abierto", NOW(), NOW())'
            );
            $stmt->execute([
                ':id_cliente' => (int)$datos['id_cliente'],
                ':asunto' => $datos['asunto'] ?? 'Consulta a administracion'
            ]);
            $idChat = (int)$pdo->lastInsertId();
            $this->insertarMensaje($idChat, (int)$datos['id_usuario'], 'Cliente', $datos['mensaje'], $pdo);
            $pdo->commit();
            return $idChat;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function enviarMensaje(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $idChat = (int)$datos['id_chat'];
            $this->insertarMensaje($idChat, (int)$datos['id_usuario'], $datos['autor_tipo'], $datos['mensaje'], $pdo);
            $estado = $datos['autor_tipo'] === 'Administracion' ? 'Respondido' : 'Abierto';
            $stmt = $pdo->prepare(
                'UPDATE chat_conversacion
                 SET estado = :estado,
                     fecha_actualizacion = NOW()
                 WHERE id_chat = :id_chat'
            );
            $stmt->execute([':estado' => $estado, ':id_chat' => $idChat]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function insertarMensaje(int $idChat, int $idUsuario, string $autorTipo, string $mensaje, PDO $pdo) {
        $stmt = $pdo->prepare(
            'INSERT INTO chat_mensaje_web (id_chat, id_usuario, autor_tipo, mensaje, fecha)
             VALUES (:id_chat, :id_usuario, :autor_tipo, :mensaje, NOW())'
        );
        $stmt->execute([
            ':id_chat' => $idChat,
            ':id_usuario' => $idUsuario,
            ':autor_tipo' => $autorTipo,
            ':mensaje' => $mensaje
        ]);
    }
}
