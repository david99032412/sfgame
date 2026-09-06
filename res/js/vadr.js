function VideoAdRequester(adSlotName, placementId, targetNode, statusNode) {
    var statusEmum = {
        uninitialized: 0, loading: 1, loaded: 2, initializing: 3, ready: 4
    };

    var _modal;

    var _status = 0;

    var _playerId;
    var _isRequested = false;
    var _lastRequested = 0;
    var _isPlaying = false;
    var _isResultDisplayed = false;

    // minimum time in seconds between requests
    var _coolDown = 30;

    console.log('creating new instance of video ad requester. adSlotName: ' + adSlotName + " placementId: " + String(placementId));

    var init = function () {
        if (_status >= statusEmum.initializing) {
            return;
        }

        _status = statusEmum.initializing;

        /* An error occured when trying to play a video ad. After this callback, no video is being rendered and a new ad has to be requested first: */
        AyetVideoSdk.callbackError = function (e) {
            _isPlaying = false;
            console.error("callbackError called: " + JSON.stringify(e));
        };

        /* After this callback, a video will start playing: */
        AyetVideoSdk.callbackPlaying = function () {
            _isPlaying = true;
            console.log("callbackPlaying called");
        };

        /* After this callback, playing the video is finished and the player is closed: */
        AyetVideoSdk.callbackComplete = function (details) {
            _isPlaying = false;
            console.log("callbackComplete called: " + JSON.stringify(details));
        };

        /* This callback is sent once a second while a video is being played: */
        AyetVideoSdk.callbackProgess = function (remainingSeconds) {
            if (_isResultDisplayed === true) {
                return;
            }
            statusNode.innerText = Math.round(remainingSeconds) + "s remaining";
        };

        /* This callback is sent if a video has been completed and the impression has gone through fraud checks (see "Rewarding Users" for more details): */
        AyetVideoSdk.callbackRewarded = function (details) {
            _isPlaying = false;
            _isResultDisplayed = true;
            console.log("callbackRewarded: " + JSON.stringify(details));

            if (details.status !== 'success') {
                statusNode.innerText = "Undefined status";
                return;
            }
            statusNode.innerText = "You earned " + String(details.currency) + " coins";
        };

        AyetVideoSdk.init(placementId, _playerId, null).then(function () {
            console.log("finished initialization!");
            _status = statusEmum.ready;
        });
    };

    var load = function () {
        if (_status >= statusEmum.loading) {
            return;
        }

        _status = statusEmum.loading;

        console.log('loading sdk ...');

        var scriptElement = document.createElement('scr' + 'ipt');

        scriptElement.setAttribute("type", "text/javascript");
        scriptElement.setAttribute("sr" + "c", 'https://d1mys92jzce605.cloudfront.net/offerwall/js/ayetvideosdk.min.js');
        scriptElement.onreadystatechange = function () {
            init();
        };

        scriptElement.onload = function () {
            init();
        };

        if (typeof scriptElement !== "undefined") {
            document.getElementsByTagName("head")[0].appendChild(scriptElement);
        }
    };

    this.requestVideo = function (playerId) {
        if (typeof _playerId !== 'undefined' && playerId != _playerId ) {
            console.log("player id has changed. Resetting sdk.", playerId, _playerId);
            AyetVideoSdk.destroy();
            _status = 0;
        }

        _playerId = playerId;
        if (_status < statusEmum.ready) {
            load();
            return;
        }

        if (_isPlaying === true) {
            return false;
        }

        if (AyetVideoSdk.isAdAvailable()) {
            console.log("video is available!");
            return true;
        }

        if (_isRequested === true) {
            return false;
        }

        var currentTime = Math.floor((new Date()).getTime() / 1000);
        if (_lastRequested > currentTime - _coolDown) {
            return false;
        }

        _lastRequested = currentTime;
        _isRequested = true;

        console.log("calling requestAd isPlaying: " + String(_isPlaying) + " status: " + String(_status));

        AyetVideoSdk.requestAd(adSlotName, function () { // success callback function
            console.log("requestAd successful");
            _isRequested = false;
        }, function (msg) { // error callback function
            console.error("requestAd failed: " + msg);
            _isRequested = false;
        }).then(function () {
            _isRequested = false;
        }, function (error) {
            console.error(error.message);

            _isRequested = false;
        });

        return false;
    };

    this.showVideo = function () {
        if (!AyetVideoSdk.isAdAvailable()) {
            return false;
        }

        if (typeof targetNode === 'undefined' || _modal !== 'undefined') {
            this.showModal();
        }
        _isResultDisplayed = false;
        statusNode.innerText = '';
        AyetVideoSdk.playInPageAd(targetNode);

        return true;
    };

    this.showModal = function () {
        if (typeof _modal !== "undefined") {
            _modal.style.display = "block";
            return;
        }

        // Modal
        var modal = document.createElement('div');
        modal.setAttribute('id', 'videoModal');
        modal.classList.add('video-modal');

        // Modal content
        var modalContent = document.createElement('div');
        modalContent.classList.add('video-modal-content');

        // Modal header
        var modalHeader = document.createElement('div');
        modalHeader.classList.add('video-modal-header');

        var modalCloseButton = document.createElement('span');
        modalCloseButton.innerText = '\u00D7';
        modalCloseButton.classList.add('video-close');
        modalCloseButton.addEventListener('click', function () {
            AyetVideoSdk.destroy();
            modal.style.display = "none";
        });
        modalHeader.appendChild(modalCloseButton);

        var modalTitle = document.createElement('h2');
        modalTitle.innerText = 'Video Box';
        modalHeader.appendChild(modalTitle);
        modalContent.appendChild(modalHeader);

        // Model body
        var modalBody = document.createElement('div');
        modalBody.classList.add('video-modal-body');
        targetNode = modalBody;

        modalContent.appendChild(modalBody);

        // Modal footer
        var modalFooter = document.createElement('div');
        modalFooter.classList.add('video-modal-footer');
        var modalFooterText = document.createElement('h3');
        statusNode = modalFooterText;
        modalFooter.appendChild(modalFooterText);
        modalContent.appendChild(modalFooter);

        modal.appendChild(modalContent);

        _modal = document.getElementsByTagName('body')[0].appendChild(modal);

        _modal.style.display = "block";
    }
}
