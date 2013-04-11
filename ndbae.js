jQuery(document).ready(function($) 
{
    $('body').append('<div id="tooltip" class="shadow stitched"></div>');
    $('#tooltip').hide();
    var $tooltip = $('#tooltip');

    $('a.ndbae-act-link').each(
            function()
            {
                var $this = $(this),
                    $title = this.title;
                $title = $title.replace( '[-', '<span class="single-blog">[ </span>' );
                $title = $title.replace( '-]', '<span class="single-blog"> ]</span>' );
                $title = $title.replace(
                        / - - /g,
                        function($1)
                        {
                            return('<span class="single-blog"> ] - - - [ </span>');
                        }
                );

                $this.hover(
                        function()
                        {
                            this.title = '';
                            $tooltip.html($title).show();
                        },
                        function()
                        {
                            this.title = $title;
                            $tooltip.html('').hide();
                        }
                );

                $this.mousemove(
                        function(e)
                        {
                            $tooltip.css({
                                top: e.pageY - 25,
                                left: e.pageX + 20
                            }
                            );
                        } // end function 
                ); // end each()
            });
            
    function show_hide_network_plugins(what)
    {
        // Update plugins count
        var all_inactive = $("#the-list").children().not(".plugin-update-tr").length;
        var really_inactive = all_inactive - $('a.ndbae-act-link').length;
        if( what )
            $( '.displaying-num ').text(really_inactive+' items');
        else
            $( '.displaying-num ').text(all_inactive+' items');
        
        $('a.ndbae-act-link').each(function()
        {
            if (what)
            {
                // In theory, the plugin is not network activated
                $(this).closest('tr.inactive').hide();
                
                // Hide update notice if exists
                if ($(this).closest('tr.inactive').next().is('tr.plugin-update-tr'))
                    $(this).closest('tr.inactive').next().hide();
            }
            else
            {
                $(this).closest('tr.inactive').show();
                if ($(this).closest('tr.inactive').next().is('tr.plugin-update-tr'))
                    $(this).closest('tr.inactive').next().show();
            }
        });
    }

    $("#hide_network_but_local").click(function() {
        if ($(this).is(':checked'))
            show_hide_network_plugins(true);
        else
            show_hide_network_plugins(false);
    });

});