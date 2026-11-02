<?php
class FacturaPDF {
    private array $ops = [];
    private function text(float $x, float $y, string $value, int $size = 10, bool $bold = false): void {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
        $encoded = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $encoded ?: '');
        $this->ops[] = "BT /" . ($bold ? 'F2' : 'F1') . " $size Tf $x $y Td ($encoded) Tj ET";
    }
    private function line(float $x1,float $y1,float $x2,float $y2): void { $this->ops[] = "$x1 $y1 m $x2 $y2 l S"; }
    public function render(array $f): string {
        // Valores predeterminados de Metalúrgica San Jorge S.A.; pueden ser
        // reemplazados por el entorno del servidor para otras instalaciones.
        $issuer = getenv('METALGEST_FACTURA_RAZON_SOCIAL') ?: 'Metalúrgica San Jorge S.A.';
        $cuit = getenv('METALGEST_FACTURA_CUIT') ?: '30-70908964-8';
        $address = getenv('METALGEST_FACTURA_DOMICILIO') ?: 'Fray Luis Beltrán 1115, San Jorge, Santa Fe (2451)';
        $iva = getenv('METALGEST_FACTURA_CONDICION_IVA') ?: 'Responsable inscripto';
        $pv = getenv('METALGEST_FACTURA_PUNTO_VENTA') ?: 'A asignar en ARCA';
        $iibb = getenv('METALGEST_FACTURA_IIBB') ?: 'Convenio Multilateral - activo';
        $inicio = getenv('METALGEST_FACTURA_INICIO_ACTIVIDADES') ?: '02/2005';
        $rate = (float)($f['iva_alicuota'] ?? 0);
        $total = (float)$f['total'];
        $neto = $rate > 0 ? $total / (1 + $rate / 100) : $total;
        $ivaImporte = $total - $neto;
        $this->ops[] = '0.15 w 45 45 522 752 re S';
        $this->text(62, 758, $issuer, 18, true);
        $this->text(62, 739, 'Domicilio: ' . $address, 9);
        $this->text(62, 723, 'CUIT: ' . $cuit . '    IVA: ' . $iva, 9);
        $this->text(62, 707, 'Ingresos Brutos: ' . $iibb . '    Inicio actividades: ' . $inicio, 8);
        $this->ops[] = '360 690 185 86 re S';
        $this->text(378, 752, 'FACTURA ' . ($f['tipo_comprobante'] ?: 'B'), 15, true);
        $this->text(378, 730, 'Punto de venta: ' . $pv, 8);
        $this->text(378, 714, 'Comprobante: ' . ($f['numero'] ?: 'Sin número'), 9);
        $this->text(378, 698, 'Fecha: ' . ($f['fecha'] ?: ''), 9);
        $this->text(62, 675, 'DOCUMENTO INTERNO - BORRADOR - NO VÁLIDO COMO FACTURA FISCAL', 10, true);
        $this->line(62, 665, 550, 665);
        $this->text(62, 644, 'Cliente: ' . ($f['razon_social'] ?: ''), 10, true);
        $this->text(62, 625, 'CUIT: ' . ($f['cuit'] ?: 'No informado') . '    Domicilio: ' . ($f['direccion'] ?: 'No informado'), 9);
        $this->text(62, 606, 'Pedido: ' . ($f['id_pedido'] ? '#' . $f['id_pedido'] : '—') . '    Conversación: ' . ($f['asunto_chat'] ?: '—'), 9);
        $this->text(62, 588, 'Condición de venta: ' . ($f['condicion_venta'] ?: '—'), 9);
        $this->line(62, 576, 550, 576);
        $this->text(65, 558, 'Descripción', 9, true); $this->text(365, 558, 'Cantidad', 9, true); $this->text(455, 558, 'Importe', 9, true);
        $this->line(62, 550, 550, 550);
        $desc = trim(($f['descripcion'] ?? '') . ' ' . (($f['material'] ?? '') ? '- ' . $f['material'] : ''));
        $this->text(65, 528, iconv_substr($desc ?: 'Bienes/servicios de la conversación', 0, 52, 'UTF-8'), 9);
        $this->text(382, 528, (string)($f['cantidad'] ?: 1), 9);
        $this->text(455, 528, '$ ' . number_format($neto, 2, ',', '.'), 9);
        $this->line(62, 508, 550, 508);
        $this->text(365, 488, 'Neto gravado', 9); $this->text(455, 488, '$ ' . number_format($neto, 2, ',', '.'), 9);
        $this->text(365, 470, 'IVA (' . number_format($rate, 2, ',', '.') . '%)', 9); $this->text(455, 470, '$ ' . number_format($ivaImporte, 2, ',', '.'), 9);
        $this->text(365, 445, 'TOTAL', 12, true);
        $this->text(455, 445, '$ ' . number_format($total, 2, ',', '.'), 12, true);
        $this->text(62, 415, 'Estado administrativo: ' . ($f['estado'] ?: 'Pendiente'), 9);
        $this->text(62, 390, 'CAE: No emitido    Vencimiento CAE: —', 9, true);
        $this->text(62, 365, 'La validez fiscal requiere autorización electrónica de ARCA (CAE).', 9);
        $this->text(62, 80, 'Generado por MetalGest. No reemplaza un comprobante autorizado por ARCA.', 8);
        $content = implode("\n", $this->ops);
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream"
        ];
        $pdf = "%PDF-1.4\n%MetalGest\n"; $offsets = [0];
        foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i+1) . " 0 obj\n" . $object . "\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= 'xref' . "\n0 " . (count($objects)+1) . "\n0000000000 65535 f \n";
        for ($i=1; $i<count($offsets); $i++) $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        $pdf .= 'trailer << /Size ' . (count($objects)+1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $pdf;
    }
}
