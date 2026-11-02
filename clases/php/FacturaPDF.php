<?php
class FacturaPDF {
    public function render(array $factura) {
        $total = (float)($factura['total'] ?? 0);
        $alicuota = (float)($factura['iva_alicuota'] ?? 0);
        $neto = $alicuota > 0 ? $total / (1 + $alicuota / 100) : $total;
        $iva = $total - $neto;
        $lines = [
            ['BORRADOR DE FACTURA - SIN VALIDEZ FISCAL', 18],
            ['Metalurgica San Jorge', 14],
            ['Numero: ' . ($factura['numero'] ?? ''), 11],
            ['Fecha: ' . ($factura['fecha'] ?? ''), 11],
            ['Comprobante: ' . ($factura['tipo_comprobante'] ?? ''), 11],
            ['Cliente: ' . ($factura['razon_social'] ?? ''), 11],
            ['CUIT: ' . ($factura['cuit'] ?? 'No informado'), 11],
            ['Direccion: ' . ($factura['direccion'] ?? 'No informada'), 11],
            ['Conversacion: ' . ($factura['asunto_chat'] ?? 'Consulta'), 11],
            ['Pedido: ' . ($factura['id_pedido'] ?? 'Sin pedido vinculado'), 11],
            ['Descripcion: ' . ($factura['pedido_descripcion'] ?? 'No especificada'), 11],
            ['Condicion de venta: ' . ($factura['condicion_venta'] ?? ''), 11],
            ['Estado: ' . ($factura['estado'] ?? ''), 11],
            ['Neto estimado: $ ' . number_format($neto, 2, ',', '.'), 12],
            ['IVA (' . number_format($alicuota, 2, ',', '.') . '%): $ ' . number_format($iva, 2, ',', '.'), 12],
            ['TOTAL: $ ' . number_format($total, 2, ',', '.'), 15],
            ['Este documento es un borrador y no tiene autorizacion CAE de ARCA.', 10]
        ];

        $content = "BT\n";
        $y = 790;
        foreach ($lines as $index => [$text, $fontSize]) {
            if ($index === 1 || $index === 13 || $index === 16) {
                $y -= 12;
            }
            $content .= '/F1 ' . $fontSize . " Tf\n1 0 0 1 50 $y Tm\n";
            $content .= '(' . $this->escape($text) . ") Tj\n";
            $y -= $fontSize + 12;
        }
        $content .= "ET\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream"
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";
        return $pdf;
    }

    private function escape($text) {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', (string)$text);
        if ($encoded === false) {
            throw new RuntimeException('No se pudo codificar el contenido del PDF.');
        }
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $encoded);
    }
}
