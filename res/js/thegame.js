function getUniqueId(size) {
  var chars = "0123456789abcdefghijklmnopqurstuvwxyzABCDEFGHIJKLMNOPQURSTUVWXYZ";
  var str = "";
  for (var i = 0; i < size; i++) {

    str += chars.substr(Math.floor(Math.random() * 62), 1);
  }
  return str;
}

function getTitle() {
  title = "";
  try {
    if (window.location != window.parent.location && document.getElementById("title")) {
      return document.getElementById("title").firstChild;
    }
  } catch (e) {
  }

  if (document.title) {
    return document.title;
  }
}

function loadJs(file, params, callbacks, overwrite) {

  if (overwrite || !jsloader[file]) {
    jsloader[file] = document.createElement('scr' + 'ipt');

  }
  jsloader[file].setAttribute("type", "text/javascript");
  jsloader[file].setAttribute("sr" + "c", file + "?" + params);
  jsloader[file].onreadystatechange = function () {
    if (this.readyState == 'complete') {
      callbacks["onComplete"].call();
    }
  }

  jsloader[file].onload = function () { callbacks["onComplete"].call(); }

  if (typeof jsloader[file] != "undefined") {
    document.getElementsByTagName("head")[0].appendChild(jsloader[file]);
  }
}

function loadCss(href) {
  var l = document.createElement('link');
  l.rel = 'stylesheet';
  l.href = href;

  var h = document.getElementsByTagName('head')[0];
  h.parentNode.insertBefore(l, h);
}

function openPaymentWindow(url) {
  var w = 1024;
  var h = 840;

  var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
  var dualScreenTop = window.screenTop != undefined ? window.screenTop : window.screenY;
  var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
  var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

  var left = ((width / 2) - (w / 2)) + dualScreenLeft;
  var top = ((height / 2) - (h / 2)) + dualScreenTop;

  var a = document.createElement('a');
  a.href = url;
  a.style.display = 'none';
  a.addEventListener('click', function (e) {
    e.preventDefault();

    if (paymentWindow && !paymentWindow.closed) {
      paymentWindow.location.href = url;
    } else {
      paymentWindow = window.open(this.href, '', 'scrollbars=yes, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
    }
    paymentWindow.focus();
    this.parentNode.removeChild(this);
  });

  var e = document.getElementsByTagName('body')[0].appendChild(a);
  e.click();
}

function offerwall(param) {
    if (typeof window.playaOfferwall == "undefined") {
        window.playaOfferwall = new Offerwall();
    }

    window.playaOfferwall.show(offerwallUrl + param)
}

function mosh_offer_wall(uid, gender) {
  console.log('mosh_offer_wall: ' + uid);
  let matches = uid.match(/^([0-9]+)_([0-9]+)_([0-9]+)_([0-9]+)/);
  let param = "";

  if (!matches || matches.length < 5) {
    return;
  }

  console.log('game id: ' + matches[4]);

  switch (Number(matches[4])) {
    case 1:
      param = "s=1";
      break;
    case 2:
      param = "s=7";
      break;
    case 3:
      param = "s=8";
      break;
    default:
      console.log('no offerwall available for this game');
      return;
  }

  loadJs("//offerwall.mship.de/offer-wall.php", param, {
    "onComplete": function () {
      var d = new Date();
      var requestId = "3c62903b84c4c0c1fefedc4cfbe21c55&time=" + d.getTime() + "&uid=" + uid + "_" + getUniqueId(5) + "&gender=" + gender;

      if (mshipOfferWallApi.canShow()) {
        mshipOfferWallApi.show({
          onOpen: function () {
            //alert('Offerwall layer opened');
          },
          onClose: function () {
            //alert('Offerwall layer closed');
          },
          onNotAvailable: function () {
            //alert('Offerwall is no available');
          }
        }, requestId);
      }
    }
  }, true);
}

function makeGgsApiCall(method, url, jwt, params, callback) {
  if (method != 'POST' && method != 'GET') {
    return null;
  }

  if (!url.match(/^https:\/\/shopweb(-test)?.goodgamestudios.com\//)) {
    return null;
  }

  if (jwt.match(/[:]/)) {
    return null;
  }

  var xhr = new XMLHttpRequest();
  if ("withCredentials" in xhr) {
    // XHR for Chrome/Firefox/Opera/Safari.
    xhr.open(method, url, true);
  } else if (typeof XDomainRequest != "undefined") {
    xhr = new XDomainRequest();
    xhr.open(method, url);
  } else {
    console.log("error: XHR not supported");
    return null;
  }

  xhr.setRequestHeader('Accept', 'application/json');
  xhr.setRequestHeader('Authorization', 'Bearer ' + jwt);

  if (params != null && typeof params === 'object') {
    var str = "";
    for (var key in params) {
      if (str != "") {
        str += "&";
      }
      str += key + "=" + encodeURIComponent(params[key]);
    }
    params = str;
  }

  if (method == "POST") {
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  }

  xhr.onreadystatechange = function () {
    console.log("ready " + method + " " + url);
    if (this.readyState != 4 || this.status < 200 || this.status >= 300) {
      return null;
    }
    var flash = document.getElementsByTagName('embed')[0];
    if (method == 'POST' && url.match(/\/api\/checkout/)) {
      var obj = JSON.parse(xhr.responseText);
      openPaymentWindow(obj.checkoutUrl);
    } else {
      flash[callback](xhr.responseText);
    }
  }
  xhr.send(params);
}

function resetCookieConsent() {
    var cookieName = "playa-cookie-consent";
    document.cookie = cookieName + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    if (typeof Optanon.ToggleInfoDisplay() === "function") {
        Optanon.ToggleInfoDisplay();
    }
}

///////////////////////// Start
try {
  console.log('init console... done');
}
catch (e) {
  console = {
    log: function () { },
    error: function () { },
    info: function () { },
    debug: function () { }
  }
}

if (!Element.prototype.matches) {
  Element.prototype.matches =
    Element.prototype.matchesSelector ||
    Element.prototype.mozMatchesSelector ||
    Element.prototype.msMatchesSelector ||
    Element.prototype.oMatchesSelector ||
    Element.prototype.webkitMatchesSelector ||
    function (s) {
      var matches = (this.document || this.ownerDocument).querySelectorAll(s),
        i = matches.length;
      while (--i >= 0 && matches.item(i) !== this) { }
      return i > -1;
    };
}

var paymentWindow = null;
var servernameshort = "";
if (getTitle() && /\(([^\)]+)\)$/.exec(getTitle())) {
  servernameshort = (RegExp.$1);
}

var jsloader = {};
var popupIframe = {};

window.addEventListener('message', function (e) {
  var key = e.message ? 'message' : 'data';
  var data = e[key];
  //run function//
  if ((typeof data === 'string' || data instanceof String) && data.substr(0, 13) == "close-iframe-") {
    el = document.getElementById(data.substr(13));
    if (!el) {
      return;
    }
    el.parentNode.removeChild(el);
    location.hash = 0;

  }
}, false);
