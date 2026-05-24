<?php

namespace App\Filament\Actions;

use App\Models\Coupon;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;

class ShowCouponQrCodeAction
{
    public static function make(?string $name = 'showCouponQrCode'): Action
    {
        return Action::make($name)
            ->label('Ver QR')
            ->icon('heroicon-o-qr-code')
            ->color('gray')
            ->modalHeading('Codigo QR del cupon')
            ->modalDescription('Escanea este QR para cobrar el cupon.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(function (Coupon $record): View {
                $url = $record->qr_redeem_url;

                if (! is_string($url) || $url === '') {
                    return view('filament.actions.show-coupon-qr-code', [
                        'url' => null,
                        'message' => 'Activa el cobro por QR y guarda el cupon para generar el codigo.',
                    ]);
                }

                $options = new QROptions([
                    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                    'outputBase64' => false,
                    'eccLevel' => QRCode::ECC_H,
                    'scale' => 8,
                    'addQuietzone' => true,
                ]);

                $qrSvg = (new QRCode($options))->render($url);
                $qrDownloadSvg = self::extractSvgMarkup($qrSvg);

                return view('filament.actions.show-coupon-qr-code', [
                    'url' => $url,
                    'qrSvg' => $qrSvg,
                    'qrDownloadUrl' => 'data:image/svg+xml;base64,'.base64_encode($qrDownloadSvg),
                    'qrDownloadName' => sprintf('coupon-qr-%s.svg', $record->getKey()),
                ]);
            })
            ->action(static fn (): null => null);
    }

    public static function extractSvgMarkup(string $html): string
    {
        if (preg_match('/<svg\\b[^>]*>.*<\\/svg>/is', $html, $matches) === 1) {
            return $matches[0];
        }

        return $html;
    }
}
