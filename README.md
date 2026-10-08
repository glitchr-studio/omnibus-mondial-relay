# omnibus/mondial-relay

Mondial Relay for [glitchr/omnibus](https://github.com/glitchr-studio/omnibus):
relay points, labels and tracking through its Web_Services.asmx (plain HTTP, no ext-soap needed).

```php
$gateway = (new MondialRelayGatewayFactory($http))->create($options);   // $http: the HTTP client to call with (the application's, HttpClient::create() in plain PHP); the options below
```

No framework needed: the package requires `glitchr/omnibus` and `symfony/http-client`. In a
Symfony application, the same through the bundle's configuration:

```yaml
omnibus:
    gateways:
        relais:
            factory: mondial_relay
            options:
                enseigne: '%env(MONDIAL_RELAY_ENSEIGNE)%'      # the contract's brand code
                private_key: '%env(MONDIAL_RELAY_PRIVATE_KEY)%'
                sandbox: false         # true: Mondial Relay's public test brand, no contract needed
                collection: CCC        # CCC: collected at the shop; REL: dropped at a relay
                download_labels: false # true: the PDF inside the Label, not only its URL
                rates: [...]           # no rating service: Omnibus\Action\ConfiguredRatingAction
```

No rating nor cancellation service: prices are the shop's contract, and a label not handed over expires.

License: MIT since 2026-10-09; earlier versions remain published under LGPL-3.0-or-later.
