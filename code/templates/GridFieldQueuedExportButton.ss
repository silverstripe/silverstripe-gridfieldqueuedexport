<div class="cms-content cms-tabset center" data-layout-type="border" data-pjax-fragment="Content">

    <div class="cms-content-header north">
        <div class="cms-content-header-info">
            <% include BackLink_Button %>
            <% include CMSBreadcrumbs %>
        </div>
    </div>

    <div class="cms-content-fields center ui-widget-content cms-panel-padded" data-layout-type="border">
        <p>&nbsp;</p>
        <% if $Link %>
           <p><%t GridFieldQueuedExportButton.AVAILABLE 'Your export is available.' %> <a href="$Link"><%t GridFieldQueuedExportButton.DOWNLOAD_CSV 'Click here to download file as CSV.' %></a></p>
        <% else_if $ErrorMessage %>
           <p>$ErrorMessage.XML</p>
        <% else %>
           <p>
               <%t GridFieldQueuedExportButton.PREPARING_EXPORT 'Preparing export. This page will automatically refresh when export is available. You can bookmark this page and come back later if you like.' %>
           </p>
           <p><%t GridFieldQueuedExportButton.EXPORTED_COUNT '{count} out of {total} records exported' count=$Count total=$Total %></p>
        <% end_if %>
       <p><a href="$Backlink.ATT"><%t GridFieldQueuedExportButton.RETURN 'Return to {name}' name=$GridName %></a></p>
    </div>

</div>
