$(function () {
	$(".bubble").hover(
		function () { 
			if($("#bubble").size() == 0) {
				var bubble = $("<div>").attr("id", "bubble");
				$("body").append(bubble);
			}
			var off = $(this).offset({ scroll : false });
			$("#bubble").html($(this).attr("longdesc").replace("|","<br />")).css({ "left" : off.left + "px", "top" : (off.top + 18) + "px" }).show();
		},
		function () {
			$("#bubble").hide();
		}
	);
});