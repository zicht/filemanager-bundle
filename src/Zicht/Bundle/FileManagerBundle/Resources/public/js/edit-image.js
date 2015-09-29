/**
 * Execute the MoxieManager API for edit links
 */
$(function () {
    $('.js-moxiemanager-edit-image').each(function () {
        var $trigger = $(this).find('.js-moxiemanager-edit-image__trigger'),
            $imagePreview = $(this).find('.js-moxiemanager-edit-image__image-preview'),
            fileUrl = $trigger.data('file-url');

        fileUrl = fileUrl.split('?')[0];

        $trigger.click(function (e) {
            e.preventDefault();

            moxman.edit({
                path: fileUrl,
                onsave: function (args) {
                    $.ajax({
                        url: '/admin/filemanager/clear-thumbnail',
                        data: {
                            path: fileUrl,
                            filter: 'cms'
                        },
                        success: function (response) {
                            // The response URL can not be refreshed somehow,
                            // Therefor, use the 'original' image as the source and force a
                            // browser reload by adding the timestamp. TODO find a way to do this better
                            $imagePreview.find('img').removeAttr("src").attr('src',  args.file.url + '?timestamp=' + new Date().getTime());
                            $imagePreview.find('img').css('width', '50px');
                        }
                    });
                }
            });
        });
    });
});
