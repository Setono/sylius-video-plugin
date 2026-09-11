/*
 * Shop-side player bootstrap for video types that render a `<video data-setono-sylius-video-player>`
 * element (the Cloudflare Stream type: HLS/DASH manifests, which browsers other than Safari cannot
 * play natively). Video.js and its stylesheet are fetched on demand, the first time a player has to
 * be mounted, from the URLs on this script's own tag (`data-video-js-script` /
 * `data-video-js-stylesheet`), so pages without a video load nothing extra.
 *
 * Players present at page load are mounted automatically. Markup inserted later (a gallery that
 * swaps a player in when a visitor presses play) is mounted with `window.SetonoSyliusVideo.mount(root)`
 * and released with `window.SetonoSyliusVideo.unmount(root)` before it is removed again.
 * `data-setono-sylius-video-player` may hold a JSON object of Video.js options.
 */
(function () {
    'use strict';

    var current = document.currentScript;
    var config = {
        script: (current && current.getAttribute('data-video-js-script')) || 'https://cdn.jsdelivr.net/npm/video.js@8.24.0/dist/video.min.js',
        stylesheet: (current && current.getAttribute('data-video-js-stylesheet')) || 'https://cdn.jsdelivr.net/npm/video.js@8.24.0/dist/video-js.min.css'
    };
    var loading = null;

    function loadStylesheet() {
        if (!config.stylesheet || document.querySelector('link[data-setono-sylius-video-js]')) {
            return;
        }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = config.stylesheet;
        link.setAttribute('data-setono-sylius-video-js', '');
        document.head.appendChild(link);
    }

    function loadVideoJs() {
        if (window.videojs) {
            return Promise.resolve(window.videojs);
        }
        if (loading) {
            return loading;
        }
        loading = new Promise(function (resolve, reject) {
            loadStylesheet();
            var script = document.createElement('script');
            script.src = config.script;
            script.async = true;
            script.onload = function () {
                if (window.videojs) {
                    resolve(window.videojs);
                } else {
                    loading = null;
                    reject(new Error('The script at ' + config.script + ' did not define window.videojs.'));
                }
            };
            script.onerror = function () {
                loading = null;
                reject(new Error('Could not load Video.js from ' + config.script + '.'));
            };
            document.head.appendChild(script);
        });
        return loading;
    }

    function playersIn(root) {
        root = root || document;
        var players = [];
        if (root.matches && root.matches('[data-setono-sylius-video-player]')) {
            players.push(root);
        }
        if (root.querySelectorAll) {
            root.querySelectorAll('[data-setono-sylius-video-player]').forEach(function (element) {
                players.push(element);
            });
        }
        return players;
    }

    function optionsOf(element) {
        try {
            return JSON.parse(element.getAttribute('data-setono-sylius-video-player') || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    /**
     * Mounts every player under root (or root itself) and resolves with the Video.js player
     * instances, existing ones included.
     */
    function mount(root) {
        var elements = playersIn(root);
        if (elements.length === 0) {
            return Promise.resolve([]);
        }
        return loadVideoJs().then(function (videojs) {
            return elements.map(function (element) {
                if (!element.setonoSyliusVideoPlayer) {
                    element.setonoSyliusVideoPlayer = videojs(element, Object.assign({ fluid: true }, optionsOf(element)));
                }
                return element.setonoSyliusVideoPlayer;
            });
        });
    }

    /**
     * Disposes the players under root (or root itself); Video.js removes their elements.
     */
    function unmount(root) {
        playersIn(root).forEach(function (element) {
            var player = element.setonoSyliusVideoPlayer;
            if (player) {
                element.setonoSyliusVideoPlayer = null;
                player.dispose();
            }
        });
    }

    window.SetonoSyliusVideo = { mount: mount, unmount: unmount, loadVideoJs: loadVideoJs };

    function boot() {
        mount(document).catch(function (error) {
            if (window.console) {
                window.console.error(error);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
