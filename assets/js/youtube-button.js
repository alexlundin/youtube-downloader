(function() {
    tinymce.create('tinymce.plugins.download', {
        init : function(ed, url) {
            ed.addButton('download', {
                title : 'Скачивание видео',
                image : url+'/img/youtube.png',
                onclick : function() {
                    ed.selection.setContent('[youtube-downloader]');
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('download', tinymce.plugins.download);
})();
