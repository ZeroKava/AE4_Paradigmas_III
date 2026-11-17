<?php
class CompraModel {
    public function __construct(private PDO $db) {}

    // Devuelve el porcentaje de descuento del cupón, o null si no existe
    public function descuento(string $codigo): ?int {
        $st = $this->db->prepare('SELECT descuento FROM cupones WHERE codigo = :codigo');
        $st->execute([':codigo' => strtoupper($codigo)]);
        $fila = $st->fetch();
        return $fila ? (int)$fila['descuento'] : null;
    }

    // El total se calcula en el servidor: nunca se confía en el precio que manda el cliente
    public function crear(array $cliente, array $items, string $cupon): array {
        $this->db->beginTransaction();
        try {
            //prepara la consulta para obtener el precio real de cada producto directamente desde mysql
            $buscar = $this->db->prepare('SELECT precio FROM productos WHERE id = :id');
            $total = 0.0;
            $lineas = [];
            //recorre los ítems enviados por el carrito, verifica que existan y acumula el subtotal
            foreach ($items as $it) {
                $buscar->execute([':id' => $it['id']]);
                $p = $buscar->fetch();
                if (!$p) throw new InvalidArgumentException('Producto inexistente.');
                $total += $p['precio'] * $it['cantidad'];
                $lineas[] = [$it['id'], $it['cantidad'], $p['precio']];
            }
            //en caso de que se envie un cupon aca se valida la existencia y aplica el porcentaje de descuento
            $desc = 0;
            if ($cupon !== '') {
                $desc = $this->descuento($cupon);
                if ($desc === null) throw new InvalidArgumentException('Cupón inválido.');
            }
            $total = round($total * (1 - $desc / 100), 2);

            $st = $this->db->prepare('INSERT INTO compras (nombre,direccion,telefono,email,pago,cupon,total)
                VALUES (:nombre,:direccion,:telefono,:email,:pago,:cupon,:total)');
            $st->execute($cliente + [':cupon' => $cupon !== '' ? strtoupper($cupon) : null, ':total' => $total]);
            $compraId = (int)$this->db->lastInsertId();

            $ins = $this->db->prepare('INSERT INTO compra_items (compra_id,producto_id,cantidad,precio_unitario)
                                       VALUES (:c,:p,:q,:u)');
            foreach ($lineas as [$pid, $q, $u]) {
                $ins->execute([':c' => $compraId, ':p' => $pid, ':q' => $q, ':u' => $u]);
            }
            $this->db->commit();
            return ['id' => $compraId, 'total' => $total];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
