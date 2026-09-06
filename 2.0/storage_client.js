class LocalStorageClient {
  constructor(targetUrl, id) {
    this.iframeLoaded = false;
    this.waitIframeTimer = null;
    this.iframeTrials = 0;
    this.playastorageTargetUrl = targetUrl;
    this.id = id;
    this.logPrefix = 'PLAYASTORAGE:';

    if (document.getElementById('playastorage')) {
      this.playastorageIframe = document.getElementById('playastorage');
    } else {
      var iframe = document.createElement('iframe');
      iframe.style.width = "0px";
      iframe.style.height = "0px";
      iframe.style.display = "none";
      iframe.id = "playastorage";
      this.playastorageIframe = document.getElementsByTagName('body')[0].appendChild(iframe);
    }

    var instance = this;
    this.playastorageIframe.onload = function () {
      instance.logMessage('playastorage iframe loaded');
      instance.iframeLoaded = true;
    };

    this.playastorageIframe.src = targetUrl + "/2.0/storage.php";

    return this;
  }

  logMessage(...args) {
    if (!args || args.length == 0) {
      return;
    }

    let verbosity = location.search.split('verbosity=').splice(1).join('').split('&')[0];

    if (verbosity < 4) {
      return;
    }

    let logPefix = this.logPrefix;
    if (logPefix.length > 0) {
      args.unshift(logPefix);
    }

    console.log.apply(null, args);
  }

  displayMessage(event) {
    this.logMessage('received response: ', event.data);
    let localData = event.data;
    if (!localData) {
      return;
    }
    let context = document.getElementById(this.id);

    if (!context) {
      context = this;
    }

    switch (localData[0]) {
      case "get":
        for (let name in localData[1]) {
          if (!localData[1].hasOwnProperty(name)) {
            continue;
          }
          if (typeof context.PlayaStorageDataCallback == 'function') {
            this.logMessage("calling AS PlayaStorageDataCallback(" + name + ", " + localData[1][name] + ")");
            try {
              context.PlayaStorageDataCallback(name, localData[1][name]);
            } catch (e) {
              this.logMessage(e.message);
            }
          } else {
            this.logMessage("set", name, localData[1][name]);
          }
        }
        break;
      case "get_all":
        if (typeof context.PlayaStorageDataAllCallback == 'function') {
          this.logMessage("calling AS PlayaStorageDataAllCallback(...)");
          try {
            context.PlayaStorageDataAllCallback(localData);
          } catch (e) {
            this.logMessage(e.message);
          }
        }

        for (let o in localData[1]) {
          for (let name in localData[1][o]) {
            if (!localData[1][o].hasOwnProperty(name)) {
              continue;
            }
            if (typeof context.PlayaStorageDataCallback == 'function') {
              this.logMessage("calling AS PlayaStorageDataCallback(" + name + ", " + localData[1][o][name] + ")");
              try {
                context.PlayaStorageDataCallback(name, localData[1][o][name]);
              } catch (e) {
                this.logMessage(e.message);
              }
            } else {
              this.logMessage("set", name, localData[1][o][name]);
            }
          }
        }
        this.NotifyGame(true);
        break;
      default:
      // nothing
    }
  }

  NotifyGame(state) {
    let context = document.getElementById(this.id);
    if (typeof context.PlayaStorageInitCallback == 'function') {
      this.logMessage("calling AS PlayaStorageInitCallback(" + state + ")");
      context.PlayaStorageInitCallback(state);
    } else {
      this.logMessage("PlayaStorageInitCallback() not defined")
    }
  }
  Init() {
    this.logMessage("PlayaStorageInit called");

    let test = 'test';
    let result = false;

    this.logMessage(this.playastorageTargetUrl);

    if (typeof window.localStorage !== 'undefined') {
      try {
        window.localStorage.setItem(test, test);
        window.localStorage.removeItem(test);

        result = true;
      } catch (e) {
        this.NotifyGame(false);
        return;
      }
    } else {
      this.NotifyGame(false);
      return;
    }

    var instance = this;
    this.waitIframeTimer = window.setInterval(function () {
      instance.iframeTrials = instance.iframeTrials + 1;

      if (instance.iframeLoaded == true) {
        instance.iframeLoaded = false;
        window.clearInterval(instance.waitIframeTimer);
        instance.playastorageIframe.contentWindow.postMessage(['get_all'], instance.playastorageTargetUrl);
      } else if (instance.iframeTrials > 10) {
        window.clearInterval(instance.waitIframeTimer);
        instance.iframeTrials = 0;
        instance.NotifyGame(false);
      }
    }, 300);
  }

  Get(key) {
    this.logMessage("PlayaStorageGet(" + key + ")")
    let win = this.playastorageIframe.contentWindow;
    win.postMessage(['get', key], this.playastorageTargetUrl);
  }

  Set(key, value) {
    this.logMessage("PlayaStorageSet(" + key + ", " + value + ")");
    let win = this.playastorageIframe.contentWindow;
    win.postMessage(['set', key, value], this.playastorageTargetUrl);
  }

  Unset(key) {
    this.logMessage("PlayaStorageUnset(" + key + ")")
    let win = this.playastorageIframe.contentWindow;
    win.postMessage(['unset', key], this.playastorageTargetUrl);
  }

  Clear() {
    this.logMessage("PlayaStorageClear()")
    let win = this.playastorageIframe.contentWindow;
    win.postMessage(['clear'], this.playastorageTargetUrl);
  }
};
