(function($) {
    $.entwine("ss", function($) {

        $('.gridfield-queued-export__status-pending').entwine({

            onadd: function() {
                var self = this;
                setTimeout(function(){ self.checkStatus(); }, 2000);
            },

            checkStatus: function() {
                var url = this.parents('.gridfield-queued-export').attr('data-url');
                if (!url) return;

                if ($('.gridfield-queued-export__loading').length < 1) {
                    $('.cms-content-header-info').append('<span class="icon font-icon-spinner gridfield-queued-export__loading"></span>');
                }

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
        });

        $('.gridfield-queued-export__status-available').entwine({
            onadd: function(){
                this.checkForDownloadStart();
            },

            checkForDownloadStart: function(){
                var self = this;
                if (!$.contains(document, self[0])) return;

                var id = this.parents('.gridfield-queued-export').attr('data-id');
                if (!id) return;

                if(document.cookie.match(new RegExp("(^|;\\s*)downloaded_"+id+"\\s*=\\s*true(\\s*;|$)", 'i'))) {
                    this.replaceWith(
                        '<p>'+ss.i18n._t('GridFieldQueuedExportButton.DOWNLOADED', 'Your export has been downloaded.')+'</p>'
                    );
                }

                setTimeout(function(){ self.checkForDownloadStart(); }, 500);
            }
        });
    });
}(jQuery));