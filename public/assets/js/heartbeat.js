(function () {
    if (typeof sessionHeartbeatInit !== 'undefined') return;
    sessionHeartbeatInit = true;

    var interval = 30000;

    function ping() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'index.php?route=api_heartbeat&_=' + Date.now(), true);
        xhr.send();
    }

    ping();
    var timer = setInterval(ping, interval);

    window.addEventListener('beforeunload', function () {
        clearInterval(timer);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'index.php?route=api_heartbeat&_=' + Date.now(), true);
        xhr.send();
    });
})();
