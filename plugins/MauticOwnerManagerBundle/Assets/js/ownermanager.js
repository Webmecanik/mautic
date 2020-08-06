
Mautic.ownerManagerOnLoad = function (container) {
};


Mautic.getOwnerManagerPropertiesForm = function(actionType) {
    Mautic.activateLabelLoadingIndicator('owner_manager_type');

    var query = "action=plugin:MauticOwnerManager:getActionForm&actionType=" + actionType;
    mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        dataType: "json",
        success: function (response) {
            if (typeof response.html != 'undefined') {
                mQuery('#ownerManagerProperties').html(response.html);
                Mautic.onPageLoad('#ownerManagerProperties', response);
            }
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        },
        complete: function() {
            Mautic.removeLabelLoadingIndicator();
        }
    });
};
