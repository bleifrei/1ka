
function insidePoly(poly, pointx, pointy, vre_id) {
 	//console.log("Za poly: "+vre_id+" je x: "+pointx+" y: "+pointy);
	
    var i, j;
    var inside = false;
    for (i = 0, j = poly.length - 1; i < poly.length; j = i++) {
		//console.log(poly[i].x+" "+poly[i].y);
        if(((poly[i].y > pointy) != (poly[j].y > pointy)) && (pointx < (poly[j].x-poly[i].x) * (pointy-poly[i].y) / (poly[j].y-poly[i].y) + poly[i].x) ) inside = !inside;		
    }
	//console.log("inside je: "+inside);
    return inside;
}