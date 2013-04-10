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
});