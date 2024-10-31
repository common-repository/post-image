// JavaScript Document
function showImage(path, abs_path) {
	var image 		= document.getElementById('select_picture').value;
	if (image == 0) {
		if (document.getElementById('view_def_image')) {
			document.getElementById('view_def_image').style.display = 'none';
		}
		document.getElementById('view_image').innerHTML = '';
	} else {
		if (document.getElementById('view_def_image')) {
			document.getElementById('view_def_image').style.display = 'none';
		}
		document.getElementById('view_image').style.display 	= 'block';
		var fullimage 	=  path + image;
		var abs_image   = path + 'image.php?m=t&p=' + abs_path + '&f=' + image;
		var imgTag		= "<img src='"+ abs_image +"' border='0' />";
		document.getElementById('view_image').innerHTML = imgTag;
	}
}