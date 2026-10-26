<?php

namespace App\Support;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Properties;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Arma archivos de Excel (.xlsx) con las hojas de un reporte: una de resumen (título, filtros y totales) y varias
// tablas con encabezado de color, filtros, fila fija y formatos de dinero, fechas y porcentajes.
class ExcelReport
{
    public const DINERO = '"S/ "#,##0.00';

    private const NARANJA = 'FF8C42';

    private const NARANJA_CLARO = 'FFF3E8';

    private const GRIS_CLARO = 'F6F7F9';

    private const FORMATOS = [
        'dinero' => self::DINERO,
        'entero' => '#,##0',
        'porcentaje' => '0.0%',
        'fecha' => 'dd/mm/yyyy',
    ];

    /**
     * Hoja de tabla. $columnas: [[encabezado, tipo, ancho]], con tipo texto|entero|dinero|porcentaje|fecha.
     * $totales: fila opcional al final, con la misma cantidad de celdas que columnas (null = vacía).
     */
    public static function tabla(string $nombre, array $columnas, array $filas, ?array $totales = null): array
    {
        return ['tipo' => 'tabla', 'nombre' => $nombre, 'columnas' => $columnas, 'filas' => $filas, 'totales' => $totales];
    }

    /**
     * Hoja de resumen. $lineas: [etiqueta, valor, tipo?]. Una etiqueta que empieza con "#" es un título de sección.
     */
    public static function resumen(string $nombre, string $titulo, array $lineas): array
    {
        return ['tipo' => 'resumen', 'nombre' => $nombre, 'titulo' => $titulo, 'lineas' => $lineas];
    }

    public static function descargar(string $archivo, array $hojas, string $titulo = 'Reporte'): StreamedResponse
    {
        return response()->streamDownload(function () use ($hojas, $titulo) {
            $opciones = new Options(properties: new Properties(title: $titulo, application: 'Street Urban', creator: 'Street Urban', lastModifiedBy: 'Street Urban'));

            $writer = new Writer($opciones);
            $writer->openToFile('php://output');

            foreach ($hojas as $i => $hoja) {
                $sheet = $i === 0 ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
                $sheet->setName(self::nombreHoja($hoja['nombre']));

                $hoja['tipo'] === 'tabla'
                    ? self::escribirTabla($writer, $sheet, $hoja)
                    : self::escribirResumen($writer, $sheet, $hoja);
            }

            $writer->close();
        }, $archivo, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private static function escribirResumen(Writer $writer, $sheet, array $hoja): void
    {
        $sheet->setColumnWidth(36, 1);
        $sheet->setColumnWidth(30, 2);

        $titulo = (new Style)->withFontBold(true)->withFontSize(16)->withFontColor(self::NARANJA);
        $seccion = (new Style)->withFontBold(true)->withBackgroundColor(self::NARANJA_CLARO)->withFontColor('7A3E10');
        $etiqueta = (new Style)->withFontBold(true)->withFontColor('4B5563');

        $writer->addRow(Row::fromValuesWithStyle([$hoja['titulo']], $titulo, 26));
        $writer->addRow(Row::fromValues([]));

        foreach ($hoja['lineas'] as $linea) {
            [$texto, $valor] = [$linea[0], $linea[1] ?? null];

            if (str_starts_with($texto, '#')) {
                $writer->addRow(Row::fromValues([]));
                $writer->addRow(Row::fromValuesWithStyle([ltrim($texto, '# '), ''], $seccion));

                continue;
            }

            $estiloValor = self::estiloColumna($linea[2] ?? 'texto', false)->withCellAlignment(CellAlignment::LEFT)->withFontBold(true);
            $writer->addRow(new Row([Cell::fromValue($texto, $etiqueta), Cell::fromValue($valor, $estiloValor)]));
        }
    }

    private static function escribirTabla(Writer $writer, $sheet, array $hoja): void
    {
        $columnas = $hoja['columnas'];
        $cantidad = count($columnas);

        foreach ($columnas as $i => [, , $ancho]) {
            $sheet->setColumnWidth($ancho ?? 16, $i + 1);
        }

        $encabezado = (new Style)
            ->withFontBold(true)->withFontColor('FFFFFF')->withBackgroundColor(self::NARANJA)
            ->withCellAlignment(CellAlignment::CENTER)->withCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->withShouldWrapText(true);

        $writer->addRow(Row::fromValuesWithStyle(array_column($columnas, 0), $encabezado, 28));

        foreach ($hoja['filas'] as $n => $fila) {
            $cells = [];
            foreach ($columnas as $i => $col) {
                $cells[] = Cell::fromValue($fila[$i] ?? null, self::estiloColumna($col[1], $n % 2 === 1));
            }
            $writer->addRow(new Row($cells));
        }

        if ($hoja['totales']) {
            $cells = [];
            foreach ($columnas as $i => $col) {
                $estilo = self::estiloColumna($col[1], false)
                    ->withFontBold(true)->withBackgroundColor(self::NARANJA_CLARO)
                    ->withBorder(new Border(new BorderPart(\OpenSpout\Common\Entity\Style\BorderName::TOP, '999999')));
                $cells[] = Cell::fromValue($hoja['totales'][$i] ?? null, $estilo);
            }
            $writer->addRow(new Row($cells));
        }

        $sheet->setSheetView(new SheetView(freezeRow: 2));

        if (count($hoja['filas']) > 0) {
            $sheet->setAutoFilter(new AutoFilter(0, 1, $cantidad - 1, count($hoja['filas']) + 1));
        }
    }

    private static function estiloColumna(string $tipo, bool $zebra): Style
    {
        $estilo = new Style;

        if (isset(self::FORMATOS[$tipo])) {
            $estilo = $estilo->withFormat(self::FORMATOS[$tipo])->withCellAlignment($tipo === 'fecha' ? CellAlignment::CENTER : CellAlignment::RIGHT);
        } elseif ($tipo === 'texto') {
            $estilo = $estilo->withCellAlignment(CellAlignment::LEFT);
        }

        return $zebra ? $estilo->withBackgroundColor(self::GRIS_CLARO) : $estilo;
    }

    // Excel limita el nombre de hoja a 31 caracteres y no admite estos símbolos.
    private static function nombreHoja(string $nombre): string
    {
        return mb_substr(str_replace(['[', ']', ':', '*', '?', '/', '\\'], '', $nombre), 0, 31);
    }
}
