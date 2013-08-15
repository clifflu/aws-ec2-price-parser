/**
 * AWS ec2 pricing tool module
 * @param  {[type]} $){} [description]
 * @return {[type]}        [description]
 */

ec2_pricing = (function ($){
    var _data, 
        _xhr;

    /**
     * Filter output, returns a promise object
     * 
     * @param  {[type]} cond [description]
     * @return {[type]}      [description]
     */
    function filter(cond) {
        var out, payload;

        if ("undefined" === typeof cond )
            return _xhr;

        out = new $.Deferred();

        _xhr.done(function(d){
            payload = _filter((d), cond);
            out.resolve(payload);
        })

        return out.promise();
    }

    /**
     * the blocking filter
     * 
     * @param  {[type]} d [description]
     * @param  {[type]} cond [description]
     * @return {[type]}      [description]
     */
    function _filter(d, cond) {
        var output = {};

        _filter_recursive(d.data, output, cond, 0);

        return {
            "sequence": d.sequence,
            "tag": d.tags,
            "pricing": output
        };
    }

    function _in_filter(directive, filter_list) {
        if ("undefined" == typeof filter_list) {
            return true;
        }

        return (-1 !== filter_list.indexOf(directive));
    }

    function _filter_recursive(d, o, cond, depth) {
        var i, my_filter;

        if ("undefined" == typeof (_data.sequence[depth])) {
            $.extend(o, d);
            return;
        }

        my_filter = cond[_data.sequence[depth]];
        if (!(
            "[object Array]" === Object.prototype.toString.call(cond[_data.sequence[depth]]) ||
            "undefined" === typeof cond[_data.sequence[depth]]
        ))
            my_filter = [cond[_data.sequence[depth]]];


        for(i in d) {
            if (!d.hasOwnProperty(i))
                continue;

            if (!_in_filter(i, my_filter))
                continue;

            o[i] = {};
            _filter_recursive(d[i], o[i], cond, depth+1);
        }
    }

    function init(){
        _xhr = $.getJSON('ec2-pricing.php').
            done(function(data){_data = data;});
    }

    init();

    return {
        filter: filter
    };
})(jQuery);

// export as an AMD module
if ( typeof define === "function" && define.amd ) {
    define("ec2_pricing", [], function () { return ec2_pricing; } );
}
