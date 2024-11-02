$(document).ready(function()
{
	if (window.location.href.indexOf("&autologin=true") > 0)
	{
		$("#appsellLoginBtn").trigger('click');
	}
});

function appSellGetUrlVars(url) 
{
  var vars = {};
  var parts = url.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
      vars[key] = value;
  });
  return vars;
}

function changeAppSellVideo(event, element)
{
	event.preventDefault();

	var url = $(element).attr('data-video');
	var parts = appSellGetUrlVars(url);
	$('#appsellLeftVideos').html('<iframe src="https://www.youtube.com/embed/'+parts.v+'" style="padding-left:22px; margin-top:34px; max-width: 100%;" width="460" height="249" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>');
}