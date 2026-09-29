# omnibus/mondial-relay

Mondial Relay for [glitchr/omnibus](https://gitlab.glitchr.dev/public-repository/agnostic/omnibus/omnibus):
relay points, labels and tracking through its Web_Services.asmx (plain HTTP, no ext-soap needed).

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

License: LGPL-3.0-or-later.
