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
           <p>Your export is available. <a href="$Link">Click here to download file as CSV.</a></p>
           <p><a href="$Backlink">Return to $GridName</a></p>
        <% else_if $ErrorMessage %>
           <p>$ErrorMessage</p>
           <p><a href="$Backlink">Return to $GridName</a></p>
        <% else %>
           <p>
               Preparing export. This page will automatically refresh when export is available.
               You can bookmark this page and come back later if you like.
           </p>
           <p>$Count out of $Total records exported</p>
        <% end_if %>
    </div>

</div>
