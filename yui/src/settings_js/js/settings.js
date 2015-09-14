/**
 * Created by devon on 8/09/15.
 */

M.local_autotagger = M.local_autotagger || {};
var NS = M.local_autotagger.settings_js = {};

NS.init = function () {
    Y.one(Y.config.doc).delegate('click', this.get_new_lang_name, '#id_new_langbutton');
};

NS.get_new_lang_name = function () {
    var lang_name = prompt('Please enter your name', '');
    if (lang_name != null) {
        //set hidden element to lang_name
        Y.one('input[name=lang_name]').set('value', lang_name);
    }
};