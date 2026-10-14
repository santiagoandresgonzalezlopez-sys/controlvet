<?php

declare(strict_types=1);

namespace ControlVet\Documentos\Microservicio\Pdf;

/**
 * Ruta: services/documentos/microservicio/Pdf/SimplePDF.php
 * (movido desde controlvet/core/SimplePDF.php del monolito v1.0.0)
 *
 * Generador de documentos PDF minimalista, escrito desde cero en PHP
 * puro (sin librerías externas ni Composer), para que ControlVet
 * funcione 100% offline bajo XAMPP tal como exige el documento de
 * requerimientos. Soporta texto con saltos de línea, negrita simple
 * (títulos) y líneas separadoras — suficiente para comprobantes,
 * resúmenes de consulta y fórmulas médicas.
 */

class SimplePDF
{
    private array $lines = [];   // [ 'type' => text|title|line|space, 'text' => ... ]
    private string $title = 'ControlVet';

    public function setDocumentTitle(string $title): void
    {
        $this->title = $title;
    }

    public function addTitle(string $text): void
    {
        $this->lines[] = ['type' => 'title', 'text' => $text];
    }

    public function addSubtitle(string $text): void
    {
        $this->lines[] = ['type' => 'subtitle', 'text' => $text];
    }

    public function addLine(string $text = ''): void
    {
        // Se parte manualmente cada 95 caracteres aprox. para evitar overflow.
        foreach (explode("\n", $text) as $chunk) {
            foreach (mb_str_split($chunk, 95) ?: [''] as $part) {
                $this->lines[] = ['type' => 'text', 'text' => $part];
            }
        }
    }

    public function addSeparator(): void
    {
        $this->lines[] = ['type' => 'separator'];
    }

    public function addSpace(): void
    {
        $this->lines[] = ['type' => 'space'];
    }

    /**
     * Escapa el texto y lo convierte a WinAnsi (Windows-1252), la
     * codificación declarada para la fuente estándar: así las tildes, la ñ
     * y el símbolo ° se muestran correctamente (en v1.0.0 salían corruptos).
     */
    private static function escape(string $text): string
    {
        $ansi = (string)mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $ansi);
    }

    /**
     * Construye el contenido binario del PDF (formato PDF 1.4 válido,
     * página tamaño carta, fuente Helvetica estándar) y lo retorna.
     */
    public function render(): string
    {
        $pageWidth  = 612; // Carta
        $pageHeight = 792;
        $marginX    = 50;
        $y          = $pageHeight - 60;
        $content    = "BT\n/F1 16 Tf\n{$marginX} {$y} Td\n(" . self::escape($this->title) . ") Tj\nET\n";
        $y -= 26;
        $content .= "0.6 w\n{$marginX} {$y} m\n" . ($pageWidth - $marginX) . " {$y} l\nS\n";
        $y -= 20;

        foreach ($this->lines as $line) {
            if ($y < 60) {
                break; // Documento de una página: suficiente para estos formatos cortos.
            }
            switch ($line['type']) {
                case 'title':
                    $content .= "BT\n/F1 13 Tf\n{$marginX} {$y} Td\n(" . self::escape($line['text']) . ") Tj\nET\n";
                    $y -= 20;
                    break;
                case 'subtitle':
                    $content .= "BT\n/F1 11 Tf\n{$marginX} {$y} Td\n(" . self::escape($line['text']) . ") Tj\nET\n";
                    $y -= 17;
                    break;
                case 'separator':
                    $content .= "0.4 w\n{$marginX} {$y} m\n" . ($pageWidth - $marginX) . " {$y} l\nS\n";
                    $y -= 14;
                    break;
                case 'space':
                    $y -= 10;
                    break;
                default:
                    $content .= "BT\n/F1 10 Tf\n{$marginX} {$y} Td\n(" . self::escape($line['text']) . ") Tj\nET\n";
                    $y -= 15;
            }
        }

        // Ensamble del objeto PDF (estructura mínima válida: catálogo, páginas, página, fuente, contenido).
        $objects = [];
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Contents 5 0 R >>\nendobj\n";
        $objects[4] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n";
        $objects[5] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $obj;
        }

        $xrefStart = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

        return $pdf;
    }


}
