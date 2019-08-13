<?php 

require dirname(__DIR__) . '/vendor/autoload.php';

use League\OAuth2\Client\Provider\Geocaching;
use League\OAuth2\Client\Provider\Exception\GeocachingIdentityProviderException;

define('GEOCACHING_CLIENT_ID',     '');
define('GEOCACHING_CLIENT_SECRET', '');
define('GEOCACHING_ENVIRONMENT',   'staging'); // staging, production
define('GEOCACHING_CALLBACK',      'http://localhost:8000');

session_start();

// STAGING
$provider = new Geocaching([
    'clientId'       => GEOCACHING_CLIENT_ID,
    'clientSecret'   => GEOCACHING_CLIENT_SECRET,
    'response_type'  => 'code',
    'scope'          => '*',
    'redirectUri'    => GEOCACHING_CALLBACK,
    'environment'    => GEOCACHING_ENVIRONMENT
]);

if (!isset($_GET['code'])) {
    $_SESSION['codeVerifier']  = Geocaching::createCodeVerifier();
    $_SESSION['codeChallenge'] = Geocaching::createCodeChallenge($_SESSION['codeVerifier']);

    $pkce = ['code_challenge'        => $_SESSION['codeChallenge'],
             'code_challenge_method' => 'S256',
    ];

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl($pkce);
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit();

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {
    // Try to get an access token (using the authorization code grant)

    try {
        $token = $provider->getAccessToken('authorization_code', [
            'code'          => $_GET['code'],
            'code_verifier' => $_SESSION['codeVerifier'],
        ]);
    } catch(GeocachingIdentityProviderException $e) {
        exit($e->getMessage());
    }
    // Optional: Now you have a token you can look up a users profile data
    try {
        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        // printf('Hello %s!', $user->getReferenceCode());
        echo "<pre>";
        print_r($user->toArray());
        echo "</pre>";

    } catch (Exception $e) {
        // Failed to get user details
        exit($e->getMessage());
    }

    // Use this to interact with an API on the users behalf
    // echo "Token: " . $token->getToken();
}
