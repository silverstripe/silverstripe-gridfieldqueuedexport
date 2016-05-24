(function($) {
    $.entwine("ss", function($) {

        $('.gridfield-queued-export__status').entwine({

            onadd: function() {
                var self = this;
                setTimeout(function(){ self.checkStatus(); }, 2000);
            },

            checkStatus: function() {
                var url = this.parent('.gridfield-queued-export').attr('data-url');
                if (!url) return;

                $('.cms-content-header-info').append('<span class="gridfield-queued-export__loading"></span>');

                var self = this;
                jQuery.ajax({
                    headers: {"X-Pjax" : "CurrentForm,Breadcrumbs"},
                    url: url,
                    type: 'GET',
                    success: function(data, status, xhr) {
                        if (!$.contains(document, self[0])) return;
                        $('.cms-container').handleAjaxResponse(data, status, xhr);
                    }
                });
            }
        })
    });
}(jQuery));