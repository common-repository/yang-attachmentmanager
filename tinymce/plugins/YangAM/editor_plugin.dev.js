(function() {
	tinymce.PluginManager.requireLangPack('YangAM');
	tinymce.create('tinymce.plugins.YangAMPlugin', {
		init : function(ed, url) {
			ed.addCommand('mceDownloadInsert', function() {
				ed.execCommand('mceInsertContent', 0, insertDownload('visual', ''));
			});
			ed.addButton('YangAM', {
				title : 'YangAM.insert_download',
				cmd : 'mceDownloadInsert',
				image : url + '/img/attachment.gif'
			});
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('YangAM', n.nodeName == 'IMG');
			});
		},

		createControl : function(n, cm) {
			return null;
		},
		getInfo : function() {
			return {
				longname : 'Yang-Attachment',
				author : 'haibor',
				authorurl : 'http://www.nuodou.com',
				infourl : 'http://www.nuodou.com/a/1000.html',
				version : "1.00"
			};
		}
	});
	tinymce.PluginManager.add('YangAM', tinymce.plugins.YangAMPlugin);
})();