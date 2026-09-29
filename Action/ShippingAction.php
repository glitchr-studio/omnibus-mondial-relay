<?php

namespace Omnibus\MondialRelay\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Model\Address;
use Omnibus\Model\Label;
use Omnibus\Request\Request;
use Omnibus\Request\Shipping;
use Omnibus\MondialRelay\Api;

/**
 * WSI2_CreationEtiquette: books the shipment - to a relay point when it has
 * one (service 24R), else to the door (service HOM, or the one asked) - and
 * gives the expedition number and the label's link.
 */
final class ShippingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct(private readonly string $collection = 'CCC', private readonly bool $download = false)
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Shipping;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Shipping);
        $shipment = $request->shipment;
        [$relayCountry, $relay] = $shipment->pickupPoint ? self::relay($shipment->pickupPoint, $shipment->recipient->country) : ['', ''];
        $parcel = $shipment->parcels[0];

        $result = $this->api->call('WSI2_CreationEtiquette', [
            'ModeCol' => $this->collection,
            'ModeLiv' => $shipment->service ?? ($relay ? '24R' : 'HOM'),
            'NDossier' => Api::text($shipment->reference, 15),
            'NClient' => '',
            ...self::party('Expe', $shipment->sender),
            ...self::party('Dest', $shipment->recipient),
            'Poids' => max(15, $shipment->weight()),
            'Longueur' => $parcel->length ?? '',
            'Taille' => '',
            'NbColis' => \count($shipment->parcels),
            'CRT_Valeur' => '0',
            'CRT_Devise' => '',
            'Exp_Valeur' => '',
            'Exp_Devise' => '',
            'COL_Rel_Pays' => '',
            'COL_Rel' => '',
            'LIV_Rel_Pays' => $relayCountry,
            'LIV_Rel' => $relay,
            'TAvisage' => '',
            'TReprise' => '',
            'Montage' => '',
            'TRDV' => '',
            'Assurance' => (string) $shipment->option('insurance', ''),
            'Instructions' => Api::text((string) $shipment->option('instructions', ''), 31),
        ]);

        $number = trim((string) $result->ExpeditionNum);
        $url = Api::SITE.trim((string) $result->URL_Etiquette);
        $request->setResult(new Label(
            'mondial_relay',
            $number,
            $this->download ? $this->api->download($url) : null,
            url: $url,
            trackingUrl: TrackingAction::publicUrl($number, $this->api->enseigne()),
        ));
    }

    /** "FR-066974", or "066974" in the recipient's country. */
    private static function relay(string $id, string $country): array
    {
        return str_contains($id, '-') ? explode('-', $id, 2) : [strtoupper($country), $id];
    }

    /** @return array<string, string> Expe_Langage, Expe_Ad1..4, Expe_Ville... in the documented order */
    private static function party(string $prefix, Address $address): array
    {
        return [
            $prefix.'_Langage' => 'FR',
            $prefix.'_Ad1' => Api::text($address->name),
            $prefix.'_Ad2' => Api::text($address->company),
            $prefix.'_Ad3' => Api::text($address->line(0)),
            $prefix.'_Ad4' => Api::text($address->line(1)),
            $prefix.'_Ville' => Api::text($address->city, 26),
            $prefix.'_CP' => $address->postcode,
            $prefix.'_Pays' => strtoupper($address->country),
            $prefix.'_Tel1' => Api::phone($address->phone),
            $prefix.'_Tel2' => '',
            $prefix.'_Mail' => (string) $address->email,
        ];
    }
}
