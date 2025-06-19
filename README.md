# PayTR Payment Gateway for FOSSBilling

## Installation Guide

This guide will walk you through installing the PayTR payment gateway for your FOSSBilling installation.

### Prerequisites
- FOSSBilling installation (v0.5.0 or later recommended)
- PHP 7.4 or higher with cURL extension enabled
- PayTR merchant account (get your credentials from PayTR panel)

### Installation Steps

1. **Download the Gateway Files**
   - Clone this repository or download the ZIP file
   - Locate the `Paytr.php` file in the repository

2. **Upload to Your FOSSBilling Installation**
   - Upload the `Paytr.php` file to your FOSSBilling installation at:
     ```
     /library/Payment/Adapter/Paytr.php
     ```

3. **Configure Payment Gateway**
   - Log in to your FOSSBilling admin panel
   - Navigate to `System > Payment Gateways`
   - Click "New Gateway"
   - Select "PayTR" from the dropdown list
   - Configure the settings:
     - Enable/disable test mode
     - Set other preferences as needed
   - Click "Save"

4. **Configure PayTR Credentials**
   - Edit the `Paytr.php` file and replace these placeholders with your actual PayTR credentials:
     ```php
     $merchant_id   = 'XXX'; // Replace with your PayTR merchant ID
     $merchant_key  = 'XXX'; // Replace with your PayTR merchant key
     $merchant_salt = 'XXX'; // Replace with your PayTR merchant salt
     ```
   - Also update the callback URLs if needed:
     ```php
     $merchant_ok_url = "https://alanadi.com/basarili/";
     $merchant_fail_url = "https://alanadi.com/basarisiz/";
     ```

5. **Test the Integration**
   - Create a test order in your FOSSBilling installation
   - Select PayTR as the payment method
   - Verify the payment process works correctly in both test and live modes

### Configuration Options

The PayTR gateway offers these configuration options in the FOSSBilling admin panel:

- **Test Mode**: Enable/disable test mode (default: enabled)
- **One-time Payments**: Enable/disable support for one-time payments (default: enabled)

### Callback Setup

The gateway automatically handles callbacks from PayTR. Ensure these settings are correct:

1. In your PayTR merchant panel, set the callback URL to:
   ```
   https://yourdomain.com/bb-ipn.php
   ```

2. The gateway will automatically verify the payment status and mark invoices as paid.

### Troubleshooting

If you encounter issues:

1. **Payment Not Processing**
   - Verify your PayTR credentials are correct
   - Check that your server can connect to `https://www.paytr.com`
   - Ensure cURL is enabled on your PHP installation

2. **Callback Not Working**
   - Verify the callback URL in PayTR panel matches your FOSSBilling installation
   - Check your server error logs for any issues
   - Ensure your server's firewall isn't blocking PayTR's IPs

3. **Hash Verification Failed**
   - Double-check your merchant_key and merchant_salt values
   - Ensure there are no trailing spaces in your credentials

### Support

For support with this gateway:
- Open an issue on GitHub
- Consult FOSSBilling documentation
- Refer to PayTR's official API documentation

### License

This payment gateway is released under the [Apache License 2.0](https://www.apache.org/licenses/LICENSE-2.0).
