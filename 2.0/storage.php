<!doctype html>
<html lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Playa Storage</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="cache-control" content="max-age=0" />
        <meta http-equiv="cache-control" content="no-cache" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
        <meta http-equiv="pragma" content="no-cache" />
        <link rel="shortcut icon" href=""/>
    </head>
    <body>
    <script type="text/javascript">
    window.addEventListener("message", function(evt) {
      switch (evt.data[0]) {
          case "get":
              var obj = {};
              obj[evt.data[1]] = localStorage.getItem(evt.data[1]);
              evt.source.postMessage(["get", obj], evt.origin);
              break;
          case "get_all":
              var d = [];
              for (var i = 0, len = localStorage.length; i < len; ++i) {
                  var obj = {};
                  obj[localStorage.key(i)] = localStorage.getItem(localStorage.key(i));
                  d.push(obj);
              }
              evt.source.postMessage(["get_all", d], evt.origin);
              break;
          case "set":
              localStorage.setItem(evt.data[1], evt.data[2]);
              break;
          case "unset":
              localStorage.removeItem(evt.data[1]);
              break;
          case "clear":
              localStorage.clear();
              break;
      }
    }, false);
    </script>
    </body>
</html>
