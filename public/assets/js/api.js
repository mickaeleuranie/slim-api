var API = {
    call: function (path, data, onSuccess, onError, method) {
        var url = '/v1/';

        xhr = new XMLHttpRequest();

        if ('get' === method) {
            xhr.open('GET', path + '?' + this.serialize(data));
            // xhr.onload = this.callback(xhr, onSuccess, onError);
            xhr.onload = function() {
                console.log(xhr);
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    onSuccess(response);
                    return true;
                }
                else if (xhr.status !== 200) {
                    var response = JSON.parse(xhr.responseText);
                    onError(response);
                    return false;
                }
            };
            xhr.send();

        } else if ('post' === method) {
            xhr.open('POST', path);
            xhr.setRequestHeader('Content-Type', 'application/json');
            // xhr.onload = this.callback(onSuccess, onError);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    onSuccess(response);
                    return true;
                }
                else if (xhr.status !== 200) {
                    var response = JSON.parse(xhr.responseText);
                    onError(response);
                    return false;
                }
            };
            xhr.send(JSON.stringify(data));
        }
    },

    get: function (path, data, onSuccess, onError) {
        return this.call(path, data, onSuccess, onError, 'get');
    },

    post: function (path, data, onSuccess, onError) {
        console.log('POST', data);
        return this.call(path, data, onSuccess, onError, 'post');
    },

    callback: function (xhr, onSuccess, onError) {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            onSuccess(response);
            return true;
        }
        else if (xhr.status !== 200) {
            var response = JSON.parse(xhr.responseText);
            onError(response);
            return false;
        }
    },

    serialize: function (obj, prefix) {
        let str = this.serializeRecursive(obj, prefix);
        return str.replace(/&{2,}/g, '&').replace(/&$/, '');
    },

    serializeRecursive: function (obj, prefix) {
        var str = [], p;
        for(p in obj) {
          if (obj.hasOwnProperty(p)) {
            var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
            str.push((v !== null && typeof v === "object") ?
              this.serializeRecursive(v, k) :
              encodeURIComponent(k) + "=" + encodeURIComponent(v));
          }
        }
        // handle empty arrays
        if (undefined !== p && 1 === str.length && undefined !== str[0] && '' === str[0]) {
          var temp = p + '[' + ']';
          str[0] = encodeURIComponent(temp) + "=";
        }
        return str.join("&");
    }
};