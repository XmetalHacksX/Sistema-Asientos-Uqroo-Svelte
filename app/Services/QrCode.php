<?php

namespace App\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Writer;

/**
 * QrCode
 *
 * Clase de compatibilidad fluida para emular simplesoftwareio/simple-qrcode
 * usando bacon/bacon-qr-code v3.x (instalado en el proyecto).
 */
class QrCode
{
    protected int $size = 250;
    protected int $margin = 4;
    protected ?Rgb $foregroundColor = null;
    protected ?Rgb $backgroundColor = null;

    /**
     * Define el tamaño en píxeles.
     */
    public static function size(int $size): self
    {
        $instance = new static();
        $instance->size = $size;
        return $instance;
    }

    /**
     * Define el color del QR (foreground).
     */
    public function color(int $r, int $g, int $b): self
    {
        $this->foregroundColor = new Rgb($r, $g, $b);
        return $this;
    }

    /**
     * Define el color del fondo (background).
     */
    public function backgroundColor(int $r, int $g, int $b): self
    {
        $this->backgroundColor = new Rgb($r, $g, $b);
        return $this;
    }

    /**
     * Define el margen/quiet zone.
     */
    public function margin(int $margin): self
    {
        $this->margin = $margin;
        return $this;
    }

    /**
     * Genera la representación SVG del código QR.
     */
    public function generate(string $data): string
    {
        $fg = $this->foregroundColor ?: new Rgb(0, 0, 0);
        $bg = $this->backgroundColor ?: new Rgb(255, 255, 255);

        $fill = Fill::uniformColor($bg, $fg);

        $renderer = new ImageRenderer(
            new RendererStyle($this->size, $this->margin, null, null, $fill),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($data);
    }
}
