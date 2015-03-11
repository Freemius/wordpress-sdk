(function (undef) {
    var global = this;

    // Namespace.
    global.FS = global.FS || {};

    global.FS.PostMessage = function ()
    {
        var
            _is_child = false,
            _postman = new NoJQueryPostMessageMixin('postMessage', 'receiveMessage'),
            _callbacks = {},
            _base_url,
            _parent_url = decodeURIComponent(document.location.hash.replace(/^#/, '')),
            _parent_subdomain = _parent_url.substring(0, _parent_url.indexOf('/', ('https://' === _parent_url.substring(0, ('https://').length)) ? 8 : 7)),
            _init = function () {
                _postman.receiveMessage(function (e) {
                    var data = JSON.parse(e.data);

                    if (_callbacks[data.type]) {
                        for (var i = 0; i < _callbacks[data.type].length; i++) {
                            // Execute type callbacks.
                            _callbacks[data.type][i](data.data);
                        }
                    }
                }, _base_url);
            };

        return {
            init : function (url)
            {
                _base_url = url;
                _init();

                // Automatically receive forward messages.
                FS.PostMessage.receive('forward', function (data){
                    window.location = data.url;
                });
            },
            init_child : function ()
            {
                this.init(_parent_subdomain);

                _is_child = true;

                // Post height of a child right after window is loaded.
                $(window).bind('load', function () {
                    FS.PostMessage.postHeight();
                });

            },
            postHeight : function (diff, wrapper) {
                diff = diff || 0;
                wrapper = wrapper || '#wrap_section';
                this.post('height', {
                    height: diff + $(wrapper).outerHeight(true)
                });
            },
            post : function (type, data, iframe)
            {
                if (iframe)
                {
                    // Post to iframe.
                    _postman.postMessage(JSON.stringify({
                        type: type,
                        data: data
                    }), iframe.src, iframe.contentWindow);
                }
                else {
                    // Post to parent.
                    _postman.postMessage(JSON.stringify({
                        type: type,
                        data: data
                    }), _parent_url, window.parent);
                }
            },
            receive: function (type, callback)
            {
                if (undef === _callbacks[type])
                    _callbacks[type] = [];

                _callbacks[type].push(callback);
            },
            parent_url: function ()
            {
                return _parent_url;
            },
            parent_subdomain: function ()
            {
                return _parent_subdomain;
            }
        };
    }();
})();