function main() {
    if (history.replaceState && location.search) {
        history.replaceState({}, 'ChronoSubs', location.pathname);
    }
}

var channels = [];
var channelLookup = {};
var channelCount = 0;
var channelsLoaded = 0;

var maxLoads = 3;
var maxChannelsPerLoad = 8;

var videos = [];
var xhrs = 0;

function LoadChannels(o) {
    channels = o;
    channelCount = o.length;

    for (var x = 0; x < o.length; x++) {
        channelLookup[o[x].id] = o[x];
    }

    UpdateStatus();

    LoadNextChannel();
}

function FinishVideoList() {
    videos.sort(function(a,b) {
        return b.published - a.published || a.channel.title.localeCompare(b.channel.title) || a.title.localeCompare(b.title);
    });

    videos.splice(100);

    document.getElementById('status').style.display = 'none';

    var mainDiv = document.getElementById('video-list');

    for (var x = 0; x < videos.length; x++) {
        var aspect = videos[x].thumbnail.width / videos[x].thumbnail.height;
        var width = Math.min(250, videos[x].thumbnail.width);

        var d = document.createElement('div');
        d.className = 'video-block';
        d.style.maxWidth = '' + width + 'px';

        var a = document.createElement('a');
        a.href = 'https://www.youtube.com/channel/' + videos[x].channel.id;
        a.className = 'channel';
        a.appendChild(document.createTextNode(videos[x].channel.title));
        d.appendChild(a);

        a = document.createElement('a');
        a.href = 'https://www.youtube.com/watch?v=' + videos[x].id;
        a.className = 'video';
        d.appendChild(a);

        var img = document.createElement('img');
        img.src = videos[x].thumbnail.url;
        img.width = width;
        img.height = Math.round(width / aspect);
        a.appendChild(img);

        a.appendChild(document.createElement('br'));

        var s = document.createElement('span');
        s.className = 'title';
        s.appendChild(document.createTextNode(videos[x].title));
        a.appendChild(s);

        var dt = new Date(videos[x].published * 1000);

        s = document.createElement('span');
        s.className = 'date';
        s.appendChild(document.createTextNode(dt.toLocaleString()));
        d.appendChild(s);

        mainDiv.appendChild(d);
    }
}

function LoadNextChannel() {
    if (xhrs >= maxLoads) {
        return;
    }
    if (!channels.length) {
        if (xhrs == 0) {
            FinishVideoList();
        }
        return;
    }

    xhrs++;
    var subset = channels.splice(0, maxChannelsPerLoad);
    var codes = '';
    for (var x = 0; x < subset.length; x++) {
        codes += (codes ? ',' : '') + subset[x].code;
    }

    var offset = 0;
    var delimiter = String.fromCharCode(0);

    var req = new XMLHttpRequest();
    req.open('GET', 'channelsRecent.php?code=' + codes, true);
    req.onreadystatechange = function() {
        if (req.readyState < 3) {
            return;
        }

        while (req.responseText.indexOf(delimiter, offset) >= 0) {
            channelsLoaded += ParseChannelJson(req.responseText.substring(offset, req.responseText.indexOf(delimiter, offset)));
            offset = req.responseText.indexOf(delimiter, offset) + 1;

            UpdateStatus();
        }

        if (req.readyState == 4) {
            xhrs--;
            LoadNextChannel();
        }
    };
    req.send();

    LoadNextChannel();
}

function ParseChannelJson(jsonString) {
    if (!jsonString) {
        return 0;
    }

    var json = JSON.parse(jsonString);
    for (var x = 0; x < json.length; x++) {
        json[x].channel.title = channelLookup[json[x].channel.id].title;
        channelLookup[json[x].channel.id].seen = true;
        videos.push(json[x]);
    }
    return json.length > 0 ? 1 : 0;
}

function UpdateStatus() {
    var d = document.getElementById('status-bar');
    d.style.width = '' + ((channelsLoaded / channelCount) * 100) + '%';
}

main();