<?php
require_once __DIR__ . '/BaseDAO.php';

class ChatDAO extends BaseDAO {
    public function listar() {
        return $this->db()->query(
            'SELECT c.id_chat, c.id_cliente, c.asunto, c.estado, c.fecha_creacion, c.fecha_actualizacion,
                    p.id_pedido,
                    cl.razon_social AS nombre, cl.email,
                    COALESCE(ultimo.mensaje, "") AS ultimo_mensaje,
                    COALESCE(ultimo.autor_tipo, "") AS ultimo_autor
             FROM chat_conversacion c
             INNER JOIN cliente cl ON cl.id_cliente = c.id_cliente
             LEFT JOIN pedido p ON p.id_chat_origen = c.id_chat
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
                    p.id_pedido,
                    cl.razon_social AS nombre, cl.email,
                    COALESCE(ultimo.mensaje, "") AS ultimo_mensaje,
                    COALESCE(ultimo.autor_tipo, "") AS ultimo_autor
             FROM chat_conversacion c
             INNER JOIN cliente cl ON cl.id_cliente = c.id_cliente
             LEFT JOIN pedido p ON p.id_chat_origen = c.id_chat
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
        $mensajes = $stmt->fetchAll();
        if (!$mensajes) {
            return [];
        }
        $ids = array_map(static function ($mensaje) {
            return (int)$mensaje['id_mensaje'];
        }, $mensajes);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $adjuntos = $this->db()->prepare(
            "SELECT id_adjunto, id_mensaje, nombre_original, tipo_mime, tamano
             FROM chat_adjunto
             WHERE id_mensaje IN ($placeholders)
             ORDER BY id_adjunto"
        );
        $adjuntos->execute($ids);
        $adjuntosPorMensaje = [];
        foreach ($adjuntos->fetchAll() as $adjunto) {
            $adjuntosPorMensaje[(int)$adjunto['id_mensaje']][] = $adjunto;
        }
        foreach ($mensajes as &$mensaje) {
            $mensaje['adjuntos'] = $adjuntosPorMensaje[(int)$mensaje['id_mensaje']] ?? [];
        }
        unset($mensaje);
        return $mensajes;
    }

    public function obtenerAdjunto(int $idAdjunto) {
        $stmt = $this->db()->prepare(
            'SELECT a.id_adjunto, a.id_mensaje, a.nombre_original, a.clave_archivo, a.tipo_mime, a.tamano,
                    c.id_chat, c.id_cliente
             FROM chat_adjunto a
             INNER JOIN chat_mensaje_web m ON m.id_mensaje = a.id_mensaje
             INNER JOIN chat_conversacion c ON c.id_chat = m.id_chat
             WHERE a.id_adjunto = :id_adjunto
             LIMIT 1'
        );
        $stmt->execute([':id_adjunto' => $idAdjunto]);
        return $stmt->fetch();
    }

    public function perteneceACliente(int $idChat, int $idCliente) {
        $stmt = $this->db()->prepare(
            'SELECT 1 FROM chat_conversacion
             WHERE id_chat = :id_chat AND id_cliente = :id_cliente
             LIMIT 1'
        );
        $stmt->execute([':id_chat' => $idChat, ':id_cliente' => $idCliente]);
        return (bool)$stmt->fetchColumn();
    }

    public function obtenerContacto(int $idChat) {
        $stmt = $this->db()->prepare(
            'SELECT c.id_cliente, c.cliente_ultimo_acceso, cl.email, cl.razon_social
             FROM chat_conversacion c
             INNER JOIN cliente cl ON cl.id_cliente = c.id_cliente
             WHERE c.id_chat = :id_chat LIMIT 1'
        );
        $stmt->execute([':id_chat' => $idChat]);
        return $stmt->fetch();
    }

    public function registrarActividadCliente(int $idChat, int $idCliente) {
        $stmt = $this->db()->prepare(
            'UPDATE chat_conversacion SET cliente_ultimo_acceso = NOW()
             WHERE id_chat = :id_chat AND id_cliente = :id_cliente'
        );
        $stmt->execute([':id_chat' => $idChat, ':id_cliente' => $idCliente]);
        return $stmt->rowCount() > 0;
    }

    public function clienteActivoRecientemente(int $idChat, int $segundos = 45) {
        $segundos = max(10, min(120, $segundos));
        $stmt = $this->db()->prepare(
            'SELECT 1 FROM chat_conversacion
             WHERE id_chat = :id_chat
               AND cliente_ultimo_acceso >= DATE_SUB(NOW(), INTERVAL ' . $segundos . ' SECOND)
             LIMIT 1'
        );
        $stmt->execute([':id_chat' => $idChat]);
        return (bool)$stmt->fetchColumn();
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
            $idMensaje = $this->insertarMensaje($idChat, null, 'Cliente', $datos['mensaje'], $pdo);
            $this->insertarAdjuntos($idMensaje, $datos['adjuntos'] ?? [], $pdo);
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
            $idUsuario = $datos['autor_tipo'] === 'Cliente' ? null : (int)$datos['id_usuario'];
            $idMensaje = $this->insertarMensaje($idChat, $idUsuario, $datos['autor_tipo'], $datos['mensaje'], $pdo);
            $this->insertarAdjuntos($idMensaje, $datos['adjuntos'] ?? [], $pdo);
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

    private function insertarMensaje(int $idChat, ?int $idUsuario, string $autorTipo, string $mensaje, PDO $pdo) {
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
        return (int)$pdo->lastInsertId();
    }

    private function insertarAdjuntos(int $idMensaje, array $adjuntos, PDO $pdo) {
        if (!$adjuntos) {
            return;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO chat_adjunto (id_mensaje, nombre_original, clave_archivo, tipo_mime, tamano)
             VALUES (:id_mensaje, :nombre_original, :clave_archivo, :tipo_mime, :tamano)'
        );
        foreach ($adjuntos as $adjunto) {
            $stmt->execute([
                ':id_mensaje' => $idMensaje,
                ':nombre_original' => $adjunto['nombre_original'],
                ':clave_archivo' => $adjunto['clave_archivo'],
                ':tipo_mime' => $adjunto['tipo_mime'],
                ':tamano' => $adjunto['tamano']
            ]);
        }
    }
}
