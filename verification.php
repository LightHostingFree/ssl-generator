<?php
require_once 'vendor/autoload.php';
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use Afosto\Acme\Client;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $domain = $_POST['domain'];

    $client = new Client([
        'username' => $email,
        'mode'     => Client::MODE_STAGING,
    ]);

    $order = $client->getOrder($order);

    foreach ($authorizations as $authorization) {
        $client->verify($authorization->getDnsChallenge());

        // Additional verification and sleep logic
        if (!$client->selfTest($authorization, Client::VALIDATION_DNS)) {
            throw new \Exception('Could not verify ownership via DNS');
        }
    }

    if ($client->finalizeOrder($order)) {
        $certificate = $client->getCertificate($order);
        $privateKey = $certificate->getPrivateKey();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>SSL Generator</title>
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        </head>
        <body class="bg-light">

        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h2 class="mb-0 text-center">SSL Generation Successful</h2>
                        </div>
                        <div class="card-body">
                            <p class="lead text-center">Your SSL certificate has been generated and verified successfully!</p>
                            <hr>
                            <div class="form-group">
                                <label for="certificate">Certificate:</label>
                                <textarea class="form-control" id="certificate" rows="5" readonly><?php echo $certificate->getCertificate(); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="privateKey">Private Key:</label>
                                <textarea class="form-control" id="privateKey" rows="5" readonly><?php echo $privateKey; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        </body>
        </html>
        <?php
    } else {
        echo "Failed to verify SSL certificate. Please check DNS settings and try again.";
    }
}
?>
