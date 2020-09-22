Mautic.emailOnLoad = (function (emailOnLoadCore) {
    return function (container, response) {

        if (!email_creation_show_bcc) {
            mQuery('#emailform_bccAddress').parent().parent().hide();
        }

        mQuery('#emailform_assetAttachments').parent().hide();

        // Launch original Mautic.emailOnLoad function
        emailOnLoadCore(container, response);

    }
})(Mautic.emailOnLoad);

Mautic.formActionOnLoad = (function (formActionOnLoadCore) {
    return function (container, response) {

        if (!email_creation_show_bcc) {
            mQuery('#formaction_properties_bcc').parent().parent().hide();
        }

        // Launch original Mautic.emailOnLoad function
        formActionOnLoadCore(container, response);

    }
})(Mautic.formActionOnLoad);


Mautic.userOnLoad = (function (userOnLoadCore) {
    return function (container, response) {

        userOnLoadCore(container, response);

        if (mQuery(container + ' #list-search').length < 1) {
            mQuery('#user_firstName, #user_lastName, #user_username, #user_email').attr('readonly', 'readonly');
            mQuery('#user_plainPassword').remove();
            mQuery('#user_plainPassword_password, #user_plainPassword_confirm').parents('.row').remove();
        } else {
            mQuery('.fa-plus').parents('.btn').hide();
        }

    }
})(Mautic.userOnLoad);