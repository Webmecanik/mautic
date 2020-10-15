/**
 * Initialize theme selection
 *
 * @param themeField
 */
Mautic.initSelectTheme = (function (initSelectTheme) {
    return function (themeField) {
        let builderUrl = mQuery('#builder_url');

        // Replace Mautic URL by plugin URL
        if (builderUrl.length) {
            if (builderUrl.val().indexOf('email') !== -1) {
                url = builderUrl.val().replace('s/emails/builder', 's/brickbuilder/email');
            } else {
                url = builderUrl.val().replace('s/pages/builder','s/brickbuilder/page');
            }
            builderUrl.val(url);
        }

        // Launch original Mautic.initSelectTheme function
        initSelectTheme(themeField);

    }
})(Mautic.initSelectTheme);

/**
 * Launch builder
 *
 * @param formName
 * @param actionName
 */

window.document.fileManagerInsertImageCallbackCore = window.document.fileManagerInsertImageCallback;

Mautic.brickBuilderAutoSaveLoad = 0;
Mautic.launchBuilderAutoSave = function (formName) {
    Mautic.brickBuilderAutoSaveLoad = 1;
    Mautic.launchBuilder(formName);
    return false;
};

Mautic.launchBuilderCore = Mautic.launchBuilder;

Mautic.launchBuilder = function (formName) {
    mQuery('.builder-active').removeClass('builder-active');
    mQuery('section#app-wrapper').height('100%');
    if (Mautic.isThemeSupportedBrickBuilder()) {
        Mautic.showChangeThemeWarning = true;
        // Prepare HTML
        if (!mQuery("#brickbuilder").length) {
            mQuery('<div id="brickbuilder"></div>').insertBefore(mQuery('.builder'));
        }
        mQuery("#brickbuilder").addClass("builder-active");
        mQuery('html').css('font-size', '100%');
        mQuery('body').css('overflow-y', 'hidden');
        Mautic.initBrick(formName);
        window.document.fileManagerInsertImageCallback = function(selector, url) {
            window.brickConfig.onImageSelected(url);
        }
    }else{
        Mautic.reArrangeStyles();
        Mautic.launchBuilderCore(formName);
        window.document.fileManagerInsertImageCallback = function(selector, url) {
            window.document.fileManagerInsertImageCallbackCore(selector, url);
        }
    }
};

/**
 * Initialize BrickBuilder
 *
 * @param object
 */
Mautic.initBrick = function (object) {
    if (object === 'emailform') {
        let textareaHtml = mQuery('textarea.builder-html');
        let textareaMjml = mQuery('textarea.builder-mjml');
        let textareaVariants = mQuery('textarea.builder-variants');
        let textareaFields = mQuery('textarea.builder-fields');
        let textareaAutoSave = mQuery('textarea.builder-autosave');

        if (textareaMjml.length &&  textareaMjml.val().length) {

            let builderUrl = mQuery('#builder_url');
            let autoSaveUrl = builderUrl.val().replace('s/brickbuilder/email', 's/brickbuilder/autosave/email');

            let html = textareaMjml.val();

            if (Mautic.brickBuilderAutoSaveLoad && textareaAutoSave.val().length) {
                html = textareaAutoSave.val();
            }

            var fields = [];
            var fieldsType = {};
            mQuery('select[data-mautic="available_filters"] option').each(function(index, value){


                let mauticFieldType = mQuery(this).data('field-type');
                fieldsType[mQuery(this).val()] = mauticFieldType;
                var editorType = 'text';
                var template = mQuery('.'+mQuery(this).val()+'-template');
                switch (mauticFieldType) {
                    case 'select':
                    case 'boolean':
                    case 'assets':
                        editorType = 'select';
                        break;
                    case 'multiselect':
                        editorType = 'multiSelect';
                        break;
                    case 'date':
                    case 'datetime':
                    case 'time':
                        editorType = mauticFieldType;
                        break;
                    default:
                        if (template.length) {
                            editorType = 'select';
                        }
                        break;
                }


                var operators = [];

                var mauticOperators = mQuery(this).data('field-operators');

                if (mauticOperators) {
                    for (const [key, value] of Object.entries(mauticOperators)) {
                    let editorTypeForOperator = editorType;
                        if (editorType == 'select' && ['in', '!in'].indexOf(key) > -1) {
                            editorTypeForOperator = 'multiSelect';
                        }else if (['empty', '!empty'].indexOf(key) > -1) {
                            editorTypeForOperator = 'empty';
                        }
                        if(['select', 'multiSelect'].indexOf(editorTypeForOperator) < 0 || (['select', 'multiSelect'].indexOf(editorTypeForOperator) > -1 && ['regexp', '!regexp'].indexOf(key) == -1)) {
                            operators.push({
                                name: key,
                                label: value,
                                editorType: editorTypeForOperator
                            });
                        }
                    }
                    if (['select', 'multiSelect'].indexOf(editorType) > -1) {
                        var values = [];

                        if (template.length) {
                            mQuery.each(template.prop("options"), function(i, opt) {
                                if (opt.value) {
                                    values.push(
                                        {
                                            name: opt.value,
                                            label: opt.textContent,
                                        }
                                    );
                                }
                            });
                        }else {
                            var mauticList = mQuery(this).data('field-list');
                            for (const [key, value] of Object.entries(mauticList)) {
                                values.push({
                                    name: key,
                                    label: value
                                });
                            }
                        }

                        fields.push({
                            name: mQuery(this).val(),
                            label: mQuery(this).text(),
                            values: values,
                            operators: operators,
                            fieldType: mauticFieldType
                        });
                    }
                    else {
                        fields.push({
                            name: mQuery(this).val(),
                            label: mQuery(this).text(),
                            operators: operators,
                            fieldType: mauticFieldType
                        });
                    }
                }
            })

            window.brickConfig = {
                ...window.brickConfig,
                rootElement: "brickbuilder",
                autoSave: {
                    delay: 10000,
                    path: autoSaveUrl,
                },
                variants: {
                    fields: fields,
                    combinators: [{name: "or", label: "Or"}],
                },
                language: brickBuilderLanguage,
                languagePath: brickBuilderLanguagePath,
                tokens: emailTokens,
                onImageSelection: () => { // The will be called when the user wants to select an image from the backend
                    Mautic.openMediaManager();
                },
                onImageSelected: (imageUrl) => { // Callback to call with the selected image url from media widget
                    return imageUrl;
                },
                onCancel: (html, state, variants) => {
                    Mautic.closeBrickBuilder();
                },
                onFinish: (html, state, variants) => {
                    textareaHtml.val(html);
                    textareaMjml.val(JSON.stringify(state));
                    textareaVariants.val(variants);
                    textareaFields.val(JSON.stringify(fieldsType));
                    Mautic.closeBrickBuilder();
                },
            };

            if(html.includes("<mjml>")){
                window.brickConfig = {
                    ...window.brickConfig,
                    initialMjml : html,
                }
                delete window.brickConfig.initialState;
            }else{
                window.brickConfig = {
                    ...window.brickConfig,
                    initialState : JSON.parse(html),
                }
                delete window.brickConfig.initialMjml;
            }
            window.brickConfig.init();
        }
    }
};

Mautic.closeBrickBuilder = function () {
    Mautic.reArrangeStyles();
    window.brickConfig.destroy();
};

Mautic.reArrangeStyles = function () {
    mQuery('.builder-active').removeClass('builder-active');
    mQuery('body').css('overflow-y', 'auto');
    mQuery('section#app-wrapper').height('auto');
};

Mautic.isThemeSupportedBrickBuilder = function() {
    return !mQuery('.builder').hasClass('code-mode') && (mQuery('textarea.builder-mjml').length && mQuery('textarea.builder-mjml').val().length);
}

/**
 * Set theme's HTML
 *
 * @param theme
 */
Mautic.setThemeHtml = function(theme) {
    setupButtonLoadingIndicator(true);
    
    // Load template and fill field
    mQuery.ajax({
        url: mQuery('#builder_url').val(),
        data: 'template=' + theme,
        dataType: 'json',
        success: function (response) {
            let textareaHtml = mQuery('textarea.builder-html');
            let textareaMjml = mQuery('textarea.builder-mjml');

            textareaHtml.val(response.templateHtml);

            if (typeof textareaMjml !== 'undefined') {
                textareaMjml.val(response.templateMjml);

                // If MJML template, generate HTML before save
                if (!textareaHtml.val().length && textareaMjml.val().length) {

                }
            }
        },
        error: function (request, textStatus, errorThrown) {
            console.log("setThemeHtml - Request failed: " + textStatus);
        },
        complete: function() {
            setupButtonLoadingIndicator(false);
        }
    });
};

/**
 * Manage button loading indicator
 *
 * @param activate - true or false
 */
let setupButtonLoadingIndicator = function (activate) {
    let builderButton = mQuery('.btn-builder');
    let saveButton = mQuery('.btn-save');
    let applyButton = mQuery('.btn-apply');

    if (activate) {
        Mautic.activateButtonLoadingIndicator(builderButton);
        Mautic.activateButtonLoadingIndicator(saveButton);
        Mautic.activateButtonLoadingIndicator(applyButton);
    } else {
        Mautic.removeButtonLoadingIndicator(builderButton);
        Mautic.removeButtonLoadingIndicator(saveButton);
        Mautic.removeButtonLoadingIndicator(applyButton);
    }
};
