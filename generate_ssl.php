<?php
require_once 'vendor/autoload.php';
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use Afosto\Acme\Client;
use Cloudflare\API\Adapter\Guzzle;  // Import the necessary Cloudflare SDK classes
use Cloudflare\API\Endpoints\DNS;

function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $random_string;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $domain = $_POST['domain'];
    $acceptTOS = isset($_POST['accept_tos']) ? $_POST['accept_tos'] : '';

    if (empty($acceptTOS)) {
        echo "You must accept Let's Encrypt SA (pdf) to continue.";
        exit;
    }

    $adapter = new Local('data');
    $filesystem = new Filesystem($adapter);

    $client = new Client([
        'username' => $email,
        'fs'       => $filesystem,
        'mode'     => Client::MODE_STAGING,
    ]);

    $order = $client->createOrder([$domain]);

    $authorizations = $client->authorize($order);

    // Initialize Cloudflare API
    $apiToken = 'ZlNR52zqzolJFQ7U9iXXKzozZLHGa6Cahpo9Y7uD'; // Replace with your actual Cloudflare API token
    $api = new Guzzle($apiToken);

    foreach ($authorizations as $authorization) {
        $txtRecord = $authorization->getTxtRecord();
        $name = $txtRecord->getName();
        $value = $txtRecord->getValue();

        echo "Name: $name, Value: $value<br>";

        $subdomain = generate_random_string();
        $cname = "$subdomain.acme.mayank.in.eu.org";
        $txt_to_cname = [
            "name" => $cname,
            "value" => $value
        ];

        echo "CNAME Value: $cname<br>";
        echo "TXT Value to CNAME: $value<br>";

        // Add CNAME record to Cloudflare
        $dns = new DNS($api);
        $dns->addRecord('f31676cdcd680965e52c94b756770ca2', 'CNAME', $cname, 'acme.mayank.in.eu.org'); // Replace with your actual Zone ID

        // Add this information to Cloudflare or your DNS provider using their API
        // This is where you would integrate with your DNS provider (e.g., Cloudflare)
    }

    echo "Add a CNAME record to your DNS settings pointing to $cname";

    echo '<form action="verification.php"><input type="hidden" name="email" value="'.$email.'"><input type="hidden" name="domain" value="'.$domain.'"><input type="submit" value="Continue"></form>';
}
?>
