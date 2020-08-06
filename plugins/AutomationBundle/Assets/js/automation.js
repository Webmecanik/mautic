Mautic.emailOnLoadCore = Mautic.emailOnLoad;
Mautic.emailOnLoad = function(container, response) {

    Mautic.emailOnLoadCore(container, response);

    if (email_creation_show_bcc) {
        mQuery('#emailform_bccAddress').parent().parent().hide();
    }
}

Mautic.formActionOnLoadCore = Mautic.formActionOnLoad;
Mautic.formActionOnLoad = function(container, response) {

    Mautic.formActionOnLoadCore(container, response);

    if (email_creation_show_bcc) {
        mQuery('#formaction_properties_bcc').parent().parent().hide();
    }
}

Mautic.userOnLoadCore = Mautic.userOnLoad;
Mautic.userOnLoad = function (container, response) {

    Mautic.userOnLoadCore(container, response);

    if (mQuery(container + ' #list-search').length) {
        mQuery('#toolbar, #userTable tbody tr:first-child, #userTable tbody tr:first-child + tr').hide();
        mQuery('.user-list tr').each(function () {
            mQuery(this).children('*:first').remove();
        })
    }else{
        mQuery('#user_firstName, #user_lastName, #user_username, #user_email').attr('readonly', 'readonly');
        mQuery('#user_plainPassword').remove();
        mQuery('#user_plainPassword_password, #user_plainPassword_confirm').parents('.row').remove();
    }
}