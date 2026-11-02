<?php
require_once __DIR__ . '/BaseDAO.php';

class ChatDAO extends BaseDAO {
    public function obtenerOCrearChatPedido(int $idCliente, int $idPedido): int {
        if (!$this->hasColumn('chat_conversacion', 'id_pedido')) {
            throw new RuntimeException('Falta aplicar sql/alter_facturas_chat.sql para asociar chats con pedidos.');
        }
        $stmt = $this->db()->prepare('SELECT id_chat FROM chat_conversacion WHERE id_pedido=:id_pedido LIMIT 1');
        $stmt->execute([':id_pedido' => $idPedido]);
        $idChat = (int)$stmt->fetchColumn();
        if ($idChat > 0) return $idChat;
        $stmt = $this->db()->prepare('INSERT INTO chat_conversacion(id_cliente,id_pedido,asunto,estado,fecha_creacion,fecha_actualizacion) VALUES(:id_cliente,:id_pedido,:asunto,"Abierto",NOW(),NOW())');
        $stmt->execute([':id_cliente'=>$idCliente, ':id_pedido'=>$idPedido, ':asunto'=>'Factura del pedido #' . $idPedido]);
        return (int)$this->db()->lastInsertId();
    }

    public function asociarPedido(int $idChat, int $idPedido): void {
        if ($idPedido <= 0 || !$this->hasColumn('chat_conversacion', 'id_pedido')) return;
        $stmt = $this->db()->prepare('SELECT id_cliente,id_pedido FROM chat_conversacion WHERE id_chat=:id_chat LIMIT 1');
        $stmt->execute([':id_chat'=>$idChat]); $chat=$stmt->fetch();
        if (!$chat) throw new RuntimeException('No existe la conversación indicada.');
        if ((int)$chat['id_pedido'] === $idPedido) return;
        if ((int)$chat['id_pedido'] > 0) throw new RuntimeException('La conversación ya está asociada a otro pedido.');
        $other=$this->db()->prepare('SELECT id_chat FROM chat_conversacion WHERE id_pedido=:pedido LIMIT 1');
        $other->execute([':pedido'=>$idPedido]);
        if ((int)$other->fetchColumn() > 0) throw new RuntimeException('El pedido ya tiene otra conversación asociada.');
        $update=$this->db()->prepare('UPDATE chat_conversacion SET id_pedido=:pedido WHERE id_chat=:chat');
        $update->execute([':pedido'=>$idPedido,':chat'=>$idChat]);
    }

    public function listar() {
        return $this->db()->query(
            'SELECT c.id_chat, c.id_cliente, c.id_pedido, c.asunto, c.estado, c.fecha_creacion, c.fecha_actualizacion,
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

    public function listarAdjuntos(int $idChat) {
        $stmt = $this->db()->prepare(
            'SELECT a.id_adjunto, a.id_mensaje, a.nombre_original, a.tipo_mime, a.tamano
             FROM chat_adjunto a
             INNER JOIN chat_mensaje_web m ON m.id_mensaje = a.id_mensaje
             WHERE m.id_chat = :id_chat
             ORDER BY a.id_adjunto ASC'
        );
        $stmt->execute([':id_chat' => $idChat]);
        return $stmt->fetchAll();
    }

    public function obtenerAdjunto(int $idAdjunto) {
        $stmt = $this->db()->prepare(
            'SELECT a.id_adjunto, a.nombre_original, a.clave_archivo, a.tipo_mime
             FROM chat_adjunto a
             WHERE a.id_adjunto = :id_adjunto LIMIT 1'
        );
        $stmt->execute([':id_adjunto' => $idAdjunto]);
        return $stmt->fetch();
    }

    public function existe(int $idChat) {
        $stmt = $this->db()->prepare('SELECT 1 FROM chat_conversacion WHERE id_chat = :id_chat LIMIT 1');
        $stmt->execute([':id_chat' => $idChat]);
        return (bool)$stmt->fetchColumn();
    }

    public function enviarRespuesta(int $idChat, int $idUsuario, string $mensaje, array $adjuntos = []) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO chat_mensaje_web (id_chat, id_usuario, autor_tipo, mensaje, fecha)
                 VALUES (:id_chat, :id_usuario, "Administracion", :mensaje, NOW())'
            );
            $stmt->execute([
                ':id_chat' => $idChat,
                ':id_usuario' => $idUsuario,
                ':mensaje' => $mensaje
            ]);
            $idMensaje = (int)$pdo->lastInsertId();
            if ($adjuntos) {
                $insertarAdjunto = $pdo->prepare(
                    'INSERT INTO chat_adjunto (id_mensaje, nombre_original, clave_archivo, tipo_mime, tamano)
                     VALUES (:id_mensaje, :nombre_original, :clave_archivo, :tipo_mime, :tamano)'
                );
                foreach ($adjuntos as $adjunto) {
                    $insertarAdjunto->execute([
                        ':id_mensaje' => $idMensaje,
                        ':nombre_original' => $adjunto['nombre_original'],
                        ':clave_archivo' => $adjunto['clave_archivo'],
                        ':tipo_mime' => $adjunto['tipo_mime'],
                        ':tamano' => $adjunto['tamano']
                    ]);
                }
            }
            $actualizar = $pdo->prepare(
                'UPDATE chat_conversacion
                 SET estado = "Respondido", fecha_actualizacion = NOW()
                 WHERE id_chat = :id_chat'
            );
            $actualizar->execute([':id_chat' => $idChat]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
