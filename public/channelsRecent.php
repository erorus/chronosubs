<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../constants.php';

if (!isset($_GET['code'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

header('Content-type: text/plain; charset=utf-8');

$codes = explode(',', $_GET['code'], 11);
array_splice($codes, 10);

$mc = GetMemcache();

foreach ($codes as $code) {
    $code = preg_replace('/[^A-Za-z0-9_-]+/', '', substr($code, 0, 32));
    if (!$code) {
        continue;
    }

    $cacheKey = CHANNEL_RECENT_KEY_PREFIX . $code;

    $data = $mc->get($cacheKey);
    if ($data === false) {
        continue;
    }
    $mc->delete($cacheKey);

    echo json_encode(GetChannelVideos($data)), chr(0);
    flush();
}

function GetChannelVideos($channelId) {
    $cacheKey = 'videos-' . $channelId;

    $mc = GetMemcache();
    $data = $mc->get($cacheKey);
    if ($data !== false) {
        return $data;
    }

    $yt = GetAnonymousYoutubeService();
    $snippets = $yt->search->listSearch('snippet', [
        'channelId' => $channelId,
        'type' => 'video',
        'maxResults' => 10,
        'order' => 'date',
        'safeSearch' => 'none',
    ]);

    $data = [];
    foreach ($snippets->getItems() as $snippet) {
        $thumbs = $snippet->snippet->getThumbnails();
        $thumb = $thumbs->getStandard() ?? $thumbs->getMedium() ?? $thumbs->getDefault();

        $data[] = [
            'channel' => [
                'id' => $channelId,
            ],
            'id' => $snippet->id->videoId,
            'published' => strtotime($snippet->snippet->getPublishedAt()),
            'title' => $snippet->snippet->getTitle(),
            'thumbnail' => [
                'height' => $thumb->getHeight(),
                'width' => $thumb->getWidth(),
                'url' => $thumb->getUrl(),
            ],
        ];
    }

    $mc->set($cacheKey, $data, 60 * 60);

    return $data;
}

function GetAnonymousYoutubeService() {
    static $yt = false;
    if ($yt !== false) {
        return $yt;
    }

    $client = new Google_Client();
    $client->setDeveloperKey(API_KEY);
    $yt = new Google_Service_YouTube($client);

    return $yt;
}

