<div class="cms-content flexbox-area-grow fill-height gridfield-queued-export" data-layout-type="border" data-pjax-fragment="Content" data-ID="$ID" data-url="$Link">

    <div class="cms-content-header north vertical-align-items">
        <div class="cms-content-header-info fill-width vertical-align-items">
            <% include SilverStripe\\Admin\\BackLink_Button %>
            <% include SilverStripe\\Admin\\CMSBreadcrumbs %>
        </div>
    </div>

    <div class="cms-content-fields panel panel--padded flexbox-area-grow gridfield-queued-export__status" data-layout-type="border">
        <% if $DownloadLink %>
           <p class="gridfield-queued-export__status-available">
               <%t SilverStripe\\GridfieldQueuedExport\\GridFieldQueuedExportButton.AVAILABLE 'Your export is available.' %>
               <a target="_blank" href="$DownloadLink"><%t SilverStripe\\GridfieldQueuedExport\\GridFieldQueuedExportButton.DOWNLOAD_CSV 'Click here to download file as CSV.' %></a>
           </p>
        <% else_if $ErrorMessage %>
            <p class="gridfield-queued-export__status-error">
                $ErrorMessage.XML
            </p>
        <% else %>
           <p class="gridfield-queued-export__status-pending">
               <%t SilverStripe\\GridfieldQueuedExport\\GridFieldQueuedExportButton.PREPARING_EXPORT 'Preparing export. This page will automatically refresh when export is available. You can bookmark this page and come back later if you like.' %>
           </p>
           <p><%t SilverStripe\\GridfieldQueuedExport\\GridFieldQueuedExportButton.EXPORTED_COUNT '{count} out of {total} records exported' count=$Count total=$Total %></p>
        <% end_if %>
        <p><a href="$Backlink.ATT" class="btn btn-outline-secondary font-icon-angle-left"}><%t SilverStripe\\GridfieldQueuedExport\\GridFieldQueuedExportButton.RETURN 'Return to {name}' name=$GridName %></a></p>
    </div>

</div>
