# Paynow Zimbabwe gateway module for WHMCS

A WHMCS payment gateway module for Paynow Zimbabwe web checkout. This is a
ground-up rebuild with no external dependencies.

## Requirements

- PHP 8.1 or newer, with the curl extension
- WHMCS 8.x or 9.x
- An active Paynow Zimbabwe merchant account with an integration ID and key

## Payment methods

Customers complete payment on the Paynow-hosted checkout page, which
presents every payment method enabled on your Paynow merchant account.
Supported methods include Zimswitch-enabled cards, Visa and Mastercard,
and mobile money wallets such as EcoCash, OneMoney, Omari and InnBucks.
No additional configuration is needed in WHMCS to enable or disable
individual methods; manage them in your Paynow merchant dashboard.

## Installation

Download or clone the repository, then copy the contents of the modules
directory into the WHMCS modules directory so that
modules/gateways/paynowzw.php sits alongside WHMCS's other gateway files.
Then activate "Paynow Zimbabwe" in WHMCS admin under payment gateway
settings and enter the integration ID and key.

## Test mode

While your Paynow integration is in test mode, Paynow requires transactions
to carry the merchant account email as the auth email. The Test Mode Email
setting exists for exactly this: enter your Paynow merchant account email
there while testing, and empty the field for production use.

## Currency note

The module sends the invoice amount as-is. The WHMCS currency configured
for the client must match the currency of the Paynow integration (for
example USD). When a payment confirms, the module records the settled
amount reported and hash-verified by Paynow.

## How it works

On checkout the customer is redirected to Paynow. Paynow notifies the
callback URL once the transaction resolves. The module verifies the
notification hash, polls Paynow for the authoritative status when a poll
URL is provided, uses the verified callback status otherwise, and marks
the invoice paid when the transaction is settled.

## License

MIT.
