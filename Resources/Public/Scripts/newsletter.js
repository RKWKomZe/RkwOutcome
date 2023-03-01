/* solve conflict with TYPO3.jQuery: https://www.typo3.net/forum/thematik/zeige/thema/123300/ */
TYPO3.jQuery(document).ready(function() {

    TYPO3.jQuery('a.formcopy').click(function() {

        var href = TYPO3.jQuery(this).attr('href');

        var mail = TYPO3.jQuery('input.mailfield').val()
        var language = TYPO3.jQuery('.languagefield option:selected').val()

        TYPO3.jQuery(this).attr('href', href + '&tx_rkwnewsletter_tools_rkwnewslettermanagement%5Bgivenemail%5D=' + mail + '&tx_rkwnewsletter_tools_rkwnewslettermanagement%5Bgivenlanguage%5D=' + language);
    });

/*
    jQuery('table.tx_rkwnewsletter tr td input').blur(function() {
        jQuery(this).hide();jQuery.ajax({
            sync: 'true',
            url:  'index.php',
            type: 'POST',

            data: {
                eID: "ajaxDispatcher",
                request: {
                    extensionName:  'RkwNewsletter',
                //    pluginName:     'RkwManagement',
                    controller:     'Release',
                    action:         'updateReleaseName',
                    arguments: {
                      //  'invisibleProjects': invisibleProjects
                    }
                }
            },
            dataType: "json",


            success: function(content) {

                console.log(content);
            },
            error: function(error) {
                console.log(error);
                console.log('Hier ist was fehlgeschlagen');
            }

        })
    });

*/
});