$(document).ready(function () {
    $('#slider2').bxSlider({
        displaySlideQty: 4,
        moveSlideQty: 4,
        auto: true,
        controls: true
    });



    // Tabs
    //	$('#tabs').tabs();

    //hover states on the static widgets
    $('#dialog_link, ul#icons li').hover(

    function () {
        $(this).addClass('ui-state-hover');
    }, function () {
        $(this).removeClass('ui-state-hover');
    });

    $('#slider1').bxSlider({
        auto: true,
        controls: false,
        captions: true,
        pause: 6000,
        mode: 'fade',
        pager: true
    });

    //
    // Enable selectBox control and bind events
    //
 /*   $(".custom-class1").selectBox().focus(function () {
        $("#console").append('Focus on ' + $(this).attr('name') + '<br />');
        $("#console")[0].scrollTop = $("#console")[0].scrollHeight;
    }).blur(function () {
        $("#console").append('Blur on ' + $(this).attr('name') + '<br />');
        $("#console")[0].scrollTop = $("#console")[0].scrollHeight;
    }).change(function () {
        $("#console").append('Change on ' + $(this).attr('name') + ': ' + $(this).val() + '<br />');
        $("#console")[0].scrollTop = $("#console")[0].scrollHeight;
    });*/
	// the following code allows for single click in select dropdowns 
	// and is essential for the proper functioning of User Registration's 
	// show/hide of Personal and Company details
	$(".custom-class1, .test").selectBox({

	//effect: "slide"
});
$(".custom-class1").css({

	visibility: "visible"
});
});