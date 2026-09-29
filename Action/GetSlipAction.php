<?php

namespace Omnibus\MondialRelay\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Model\Label;
use Omnibus\Request\GetSlip;
use Omnibus\Request\Request;
use Omnibus\MondialRelay\Api;

/** WSI3_GetEtiquettes: a booked label again, 10x15 (thermal printers), else A4. */
final class GetSlipAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct(private readonly bool $download = false)
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof GetSlip && Label::PDF === $request->format;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof GetSlip);
        $result = $this->api->call('WSI3_GetEtiquettes', ['Expeditions' => $request->trackingNumber, 'Langue' => 'FR']);
        $path = trim((string) $result->URL_PDF_10x15) ?: trim((string) $result->URL_PDF_A4);
        $url = Api::SITE.$path;

        $request->setResult(new Label(
            'mondial_relay',
            $request->trackingNumber,
            $this->download ? $this->api->download($url) : null,
            url: $url,
            trackingUrl: TrackingAction::publicUrl($request->trackingNumber, $this->api->enseigne()),
        ));
    }
}
