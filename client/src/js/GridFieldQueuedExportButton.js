/* global jQuery, ss */
// eslint-disable-next-line func-names
(function ($) {
  // eslint-disable-next-line no-shadow
  $.entwine('ss', ($) => {
    $('.gridfield-queued-export__status-pending').entwine({

      onadd() {
        const self = this;
        setTimeout(() => { self.checkStatus(); }, 2000);
      },

      checkStatus() {
        const url = this.parents('.gridfield-queued-export').attr('data-url');
        if (!url) return;

        if ($('.gridfield-queued-export__loading').length < 1) {
          $('.cms-content-header-info').append('<span class="icon font-icon-spinner gridfield-queued-export__loading"></span>');
        }

        const self = this;
        jQuery.ajax({
          headers: { 'X-Pjax': 'CurrentForm,Breadcrumbs' },
          url,
          type: 'GET',
          success(data, status, xhr) {
            if (!$.contains(document, self[0])) return;
            $('.cms-container').handleAjaxResponse(data, status, xhr);
          },
        });
      },
    });

    $('.gridfield-queued-export__status-available').entwine({
      onadd() {
        this.checkForDownloadStart();
      },

      checkForDownloadStart() {
        const self = this;
        if (!$.contains(document, self[0])) return;

        const id = this.parents('.gridfield-queued-export').attr('data-id');
        if (!id) return;

        if (document.cookie.match(new RegExp(`(^|;\\s*)downloaded_${id}\\s*=\\s*true(\\s*;|$)`, 'i'))) {
          this.replaceWith(
            `<p>${ss.i18n._t('GridFieldQueuedExportButton.DOWNLOADED', 'Your export has been downloaded.')}</p>`,
          );
        }

        setTimeout(() => { self.checkForDownloadStart(); }, 500);
      },
    });
  });
}(jQuery));
