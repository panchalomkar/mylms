YUI(M.yui.loader).use('node', function(Y) {
    Y.on('domready', function() {
        var node = Y.one('.userprofile');
        if (node) {
            var selector = node.one('.page-context-header');
            if (selector) {
                node = selector;
            }
            var html = '<div class="local_mail_sendmessage">' +
                '<form action="' + M.cfg.wwwroot + '/local/mail/create.php" method="post">' +
                '<input type="hidden" name="c" value="' + M.local_mail.course +'" />' +
                '<input type="hidden" name="r" value="' + M.local_mail.recipient +'" />' +
                '<input type="hidden" name="sesskey" value="' + M.cfg.sesskey +'" />' +
                '<input type="submit" class="btn btn-secondary" value="' + M.util.get_string('sendmessage', 'local_mail') + '" />' +
                '</form>' +
                '</div>';
            var form = Y.Node.create(html);
            node.append(form);
        }
    });
});
