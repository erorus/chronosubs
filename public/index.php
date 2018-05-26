<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../constants.php';

$scriptTag = '';
if (!isset($_GET['code'])) {
    header('Link: </main.css>; as=style; rel=preload', false);
} else {
    header('Link: </main.js>; as=script; rel=preload', false);
    $scriptTag = '<script src="main.js"></script>';
}

$client = new Google_Client();
$client->setClientId(OAUTH_CLIENT);
$client->setClientSecret(OAUTH_SECRET);
$client->setScopes(Google_Service_YouTube::YOUTUBE_READONLY);
$client->setRedirectUri(OAUTH_REDIRECT_URI);

echo <<<EOF
<html>
<head>
    <title>ChronoSubs</title>
    <meta name="viewport" content="width=600">
    <link rel="stylesheet" href="main.css">
    $scriptTag
</head>
<body>
    <a href="./" class="header">ChronoSubs</a>

EOF;

if (isset($_GET['code'])) {
    CheckSubscriptions($client, $_GET['code']);
} else {
    echo <<<'EOF'
<div id="intro">
This site will:
<ol><li>Open a read-only connection to your YouTube account</li>
<li>Find your YouTube subscriptions</li>
<li>List their videos in chronological order</li>
</ol>
That's it. 

<noscript><p><b>You'll need to enable JavaScript.</b></noscript>
</div>
EOF;

    echo sprintf('<div class="load-box"><input type="button" onclick="location.href=\'%s\';" value="Load My Subscriptions"></div>', $client->createAuthUrl());
}

echo '</body></html>';

function CheckSubscriptions($client, $code) {
    $client->authenticate($code);

    $accessToken = $client->getAccessToken();

    if (!$accessToken) {
        echo '<div class="error">We could not get an access token from Youtube for your account.</div>';
        return;
    }

    $subChannels = GetSubscribedChannels($client, $accessToken);

    if (!$subChannels) {
        echo '<div class="error">We encountered an error while fetching your subscribed channels.</div>';
        return;
    }

    $json = json_encode($subChannels, JSON_NUMERIC_CHECK);

    echo '<div id="status"><div id="status-bar"></div></div><script>LoadChannels(', $json, ');</script><div id="video-list"></div>';
}

function GetSubscribedChannels($client, $access_token) {
    if (!is_array($access_token)) {
        $access_token = ['access_token' => $access_token, 'token_type' => 'Bearer'];
    }

    $client->setAccessToken($access_token);

    $youtube = new Google_Service_YouTube($client);

    $mc = GetMemcache();

    $channels = [];
    $listSubSettings = ['mine' => true, 'maxResults' => 50, 'order' => 'relevance'];
    do {
        $subscriptions = $youtube->subscriptions->listSubscriptions('snippet', $listSubSettings);
        foreach ($subscriptions->getItems() as $subscription) {
            $channelId = $subscription->snippet->resourceId->channelId;
            if (isset($channels[$channelId])) {
                continue;
            }

            $loops = 0;
            do {
                $code = rtrim(strtr(base64_encode(random_bytes(20)), '+/', '-_'), '=');
                if (++$loops > 15) {
                    return [];
                }
            } while ($mc->add(CHANNEL_RECENT_KEY_PREFIX . $code, $channelId, 180) === false);

            $channels[$channelId] = [
                'id' => $channelId,
                'title' => $subscription->snippet->title,
                'code' => $code,
                ];
        }
        $listSubSettings['pageToken'] = $subscriptions->getNextPageToken();
    } while ($listSubSettings['pageToken']);

    return array_values($channels);
}

