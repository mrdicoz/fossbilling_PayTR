<?php

class Payment_Adapter_Paytr extends Payment_AdapterAbstract
{
    protected $config = [];
    protected $di;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function setDi(Pimple\Container $di): void
    {
        $this->di = $di;
    }

    // Ayar ekranı (test modu panelden ayarlanır)
    public static function getConfig()
    {
        return [
            'supports_one_time_payments' => [
                'label' => 'Tek seferlik ödeme destekleniyor',
                'type' => 'bool',
                'default' => true,
            ],
            'test_mode' => [
                'label' => 'Test Modu',
                'type' => 'bool',
                'default' => true,
            ]
        ];
    }

    // Ödeme adımı (iframe başlatma)
    public function getHtml($api_admin, $invoice_id, $subscription = false): string
    {
        $invoice = $api_admin->invoice_get(['id' => $invoice_id]);

        // PayTR bilgileriniz (bunları panelinizden alın)
        $merchant_id   = 'XXX'; // PayTR panelinizden alın
        $merchant_key  = 'XXX'; // PayTR panelinizden alın
        $merchant_salt = 'XXX'; // PayTR panelinizden alın

        // Panelden test/canlı modunu otomatik al
        $test_mode = $this->config['test_mode'] ? 1 : 0;

        // Fatura/müşteri bilgileri
        $order_id = $invoice['id'];
        $amount = $invoice['total'];
        $email = $invoice['buyer']['email'];
        $user_name = trim($invoice['buyer']['first_name'] . ' ' . $invoice['buyer']['last_name']);
        $user_address = ($invoice['buyer']['address'] ?? '') . ' ' . ($invoice['buyer']['address_2'] ?? '');
        $user_phone = $invoice['buyer']['phone'] ?? '';

        // Sepet oluşturma
        $user_basket_array = [];
        foreach ($invoice['lines'] as $item) {
            $urun_adi = $item['title'];
            $fiyat = number_format((float)$item['price'], 2, '.', '');
            $adet = isset($item['quantity']) ? (int)$item['quantity'] : 1;
            $user_basket_array[] = [$urun_adi, $fiyat, $adet];
        }
        $user_basket = base64_encode(json_encode($user_basket_array, JSON_UNESCAPED_UNICODE));

        // Diğer alanlar
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $payment_amount = intval($amount * 100);
        $merchant_oid = $order_id;
        $merchant_ok_url = "https://alanadi.com/basarili/";
        $merchant_fail_url = "https://alanadi.com/basarisiz/";
        $timeout_limit = 60;
        $debug_on = 1;
        $no_installment = 0;
        $max_installment = 0;
        $currency = "TL";

        // Hash stringi!
        $hash_str = $merchant_id . $user_ip . $merchant_oid . $email . $payment_amount . $user_basket . $no_installment . $max_installment . $currency . $test_mode;
        $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));

        // PayTR'ye token almak için istek
        $post_vals = [
            'merchant_id'      => $merchant_id,
            'user_ip'          => $user_ip,
            'merchant_oid'     => $merchant_oid,
            'email'            => $email,
            'payment_amount'   => $payment_amount,
            'paytr_token'      => $paytr_token,
            'user_basket'      => $user_basket,
            'debug_on'         => $debug_on,
            'no_installment'   => $no_installment,
            'max_installment'  => $max_installment,
            'user_name'        => $user_name,
            'user_address'     => $user_address,
            'user_phone'       => $user_phone,
            'merchant_ok_url'  => $merchant_ok_url,
            'merchant_fail_url'=> $merchant_fail_url,
            'timeout_limit'    => $timeout_limit,
            'currency'         => $currency,
            'test_mode'        => $test_mode
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1) ;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $result = @curl_exec($ch);

        if(curl_errno($ch))
            die("PAYTR IFRAME connection error. err:".curl_error($ch));

        curl_close($ch);

        $result = json_decode($result, 1);

        if($result['status'] == 'success') {
            $token = $result['token'];
        } else {
            die("PAYTR IFRAME failed. reason:".$result['reason']);
        }

        // Kullanıcıya iframe döndür
        $iframe_code = '<script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
        <iframe src="https://www.paytr.com/odeme/guvenli/'.$token.'" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%;"></iframe>
        <script>iFrameResize({},\'#paytriframe\');</script>';

        return $iframe_code;
    }

    // Eski sürümler için hâlâ processPayment çağrılırsa, onu da getHtml’e yönlendir
    public function processPayment($invoice, $subscription = false)
    {
        return $this->getHtml($invoice);
    }

    // Callback (bildirim)
    public function processTransaction($api_admin, $id, $post, $gateway_id)
    {
        // FOSSBilling'in callback datası iç içe gelir!
        $paytr = $post['post'];

        // PayTR bilgileriniz (bunları panelinizden alın)
        $merchant_key   = 'XXX'; // PayTR panelinizden alın
        $merchant_salt  = 'XXX'; // PayTR panelinizden alın

        $hash_str = $paytr['merchant_oid'] . $merchant_salt . $paytr['status'] . $paytr['total_amount'];
        $hash = base64_encode(hash_hmac('sha256', $hash_str, $merchant_key, true));

        if ($hash != $paytr['hash']) {
            die('PAYTR notification failed: invalid hash');
        }

        if ($paytr['status'] == 'success') {
            $api_admin->invoice_mark_as_paid(['id' => $paytr['merchant_oid']]);
        }

        echo "OK";
        exit;
    }
}
