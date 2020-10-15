Mautic.emailOnLoad = (function (emailOnLoadCore) {
    return function (container, response) {

        if (mQuery("#email-container .theme-list").length) {
            mQuery("#email-container .theme-list").show();
            if (typeof themes_to_hide != 'undefined') {
                themes_to_hide.forEach(function (theme) {
                    var obj = mQuery("#email-container .theme-list  #theme-" + theme).parent();
                    if (!obj.find('.theme-selected').length) {
                        obj.hide();
                    }
                });
            }

        }

        // Launch original Mautic.emailOnLoad function
        emailOnLoadCore(container, response);

    }
})(Mautic.emailOnLoad);
