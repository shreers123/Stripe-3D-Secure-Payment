<?php
include 'vendor/autoload.php';
\Stripe\Stripe::setApiKey('STRIPE_KEY');

$body = json_decode(file_get_contents('php://input'), true);
if(isset($body['charge']) && $body['charge']){
    try {
        $intent = [];
        if (array_key_exists('paymentMethod', $body)) {
            $intent = \Stripe\PaymentIntent::create([
                "payment_method" => $body['paymentMethod'],
                "amount" => '1000',
                "currency" => 'inr',
                "confirm" => "true",
                "setup_future_usage" => "off_session"
            ]);
            generatePaymentResponse($intent);
            die();
        } else if (array_key_exists('paymentIntent', $body)) {
            // Confirming the payment intent in stripe
            $intent = \Stripe\PaymentIntent::retrieve($body['paymentIntent']);
            $intent = $intent->confirm();
            generatePaymentResponse($intent);
            die();
        }
    } catch(Exception $e) {
		echo json_encode($e->getMessage());
        die();
    }
}


function generatePaymentResponse($intent) {
    if (($intent['status'] == 'requires_source_action' || $intent['status'] == 'requires_action') &&
        $intent['next_action']['type'] == 'use_stripe_sdk') {
        echo json_encode(array(
            'requires_action' => true,
            'payment_intent_client_secret' => $intent['client_secret']
        ));
    }
    else if ($intent['status'] == 'requires_capture') {
        echo json_encode(array(
            'success' => true,
            'payment_intent_id' => $intent['id'],
            'status' => $intent['status']
        ));
    }
    elseif($intent['status'] == "succeeded"){
        echo json_encode(array(
            'success' => true,
            'status' => $intent['status']
        ));
    }
    else {
        //  Invalid status
        echo json_encode(array(
            'success' => false,
            'status' => $intent['status']
        ));
    }
}

die();
