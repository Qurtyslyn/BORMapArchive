<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Bor Map Archive</title>
    <!--Leaflet CSS and JS Plugins -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"
   integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A=="
   crossorigin=""/>
   <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"
   integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA=="
   crossorigin=""></script>

    <link rel="stylesheet" href="css/styledLayerControl.css" />
	<script src="src/styledLayerControl.js"></script>
    
    <!-- LeafletGPX plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-gpx/1.4.0/gpx.min.js"></script>

    <script src="leaflet.awesome-markers.js"></script>
    <link rel="stylesheet" href="leaflet.awesome-markers.css">
    <script src="https://kit.fontawesome.com/8064aa9388.js" crossorigin="anonymous"></script>

    <!-- Leaflet Layer Tree Plug in https://github.com/jjimenezshaw/Leaflet.Control.Layers.Tree -->
    <script src="L.Control.Layers.Tree.js"></script>
    <link rel="stylesheet" href="L.Control.Layers.Tree.css" />
    <style>
      #map {
        width: 100%;
        height: 960px;
      }
    </style>
  </head>
  <body>
    <div id="map"></div>
    <script type="text/javascript">

        document.getElementById("map").style.height = (window.innerHeight - 20) + "px";

        function getUniqueValues(arr)
        {
            var newArr = [];
            for(let i in arr)
            {
                if(newArr.indexOf(arr[i]) == -1)
                {
                    newArr.push(arr[i]);
                }
            }
            
            return newArr;
        }

        function newRoute(course, color)
        {
            var route = new L.GPX(course, {
                async: true,
                polyline_options: {
                    color: color,
                    opacity: 0.75,
                    weight: 5,
                    lineCap: 'round'
                },
                marker_options: {
                    iconSize: [25,25],
                    startIconUrl: '',
                    endIconUrl: '',
                    shadowUrl: '',
                    wptIconUrls: {
                        'Checkpoint': 'Icons/' + color + '-checkpoint.png',
                        'Recovery': 'Icons/' + color + '-recovery.png',
                        'RoadCrossing': 'Icons/' + color + '-roadcrossing.png',
                        'StartFinish': 'Icons/' + color + '-startfinish.png',
                        'Radio': 'Icons/' + color + '-radio.png',
                        'Pits': 'Icons/' + color + '-pits.png',
                        'Monitor': 'Icons/' + color + '-monitor.png',
                        '': 'Icons/' + color + '.png'
                    }
                },
                
            });

            return route;
        }
    
        var colors = ['red','teal','darkorange','darkgreen','hotpink','blue','gold','violet','maroon','deepskyblue'];

        //https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png
        //http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png
        var topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: 'Map Data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> | Map Display: &copy; <a href="http://opentopomap.org/"> OpenTopoMap </a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
            maxZoom: 18,
            subdomains: ['a','b','c']
        });

        var streets = L.tileLayer( 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 18,
            subdomains: ['a','b','c']
        });

        map = L.map('map', {
            center: [40.643901, -114.131361],
            zoom: 14,
            layers: [topo],
            doubleClickZoom: false
        });

        var backgrounds = [
            {
                groupName : "Background Map",
                expanded : true,
                layers : {
                    "Topographic" : topo,
                    "Basic" : streets
                }
          }
          ];

        var files = [        <?php
            if($dh = opendir("Maps/")){
              while (($file = readdir($dh)) !== false){
                if ($file == '.' or $file == '..') continue;
                echo '"' . $file . '",';
              }
              closedir($dh);
            }

        ?>];
        
        
        // Get unique locations for races
        var locations = [];
        var organizations = [];
        
        for(let index in files)
        {
            var nameArray = files[index].toString().split('-');
            
            locations.push(nameArray[1]);
            organizations.push(nameArray[0]);
            //locations.push((files[index].toString().split(' '))[0]);
        }
        
        //locations = locations.unique();
        
        var list = getUniqueValues(locations);
        var orgs = getUniqueValues(organizations);

        var baseTree = {label: 'Base Layers',
            children: [
                {
                    label: 'Topographic',
                    layer: topo
                },
                {
                    label: 'Basic',
                    layer: streets
                }
            ]
        };
        var overlayTree = {label: 'Overlay Layers', children: []};

        for(let i in orgs)
        {
            overlayTree.children.push({label: orgs[i], selectAllCheckbox: true, children: []});
        }

        var colorCount = 0;
        for(let i in files.sort())
        {
            var nameArray = files[i].toString().split('-');
            var name = files[i].toString().split('-')[1] + " " + files[i].toString().split('-')[2];

            if(colorCount == colors.length)
            {
                colorCount = 0;
            }
            var course = 'Maps/' + files[i];

            var group = new L.LayerGroup();

            newRoute(course,colors[colorCount]).on('loaded', function(e) {
                    map.fitBounds(e.target.getBounds());
            }).addTo(group);
            
            //Add the Locations to the Tree under the Orgs
            for(let j in overlayTree.children)
            {
                if(overlayTree.children[j].label == nameArray[0])
                {
                    //If there are no children, add the location and map
                    if(overlayTree.children[j].children.length == 0)
                    {
                        overlayTree.children[j].children.push({label: nameArray[1], selectAllCheckbox: true, children: []});
                        overlayTree.children[j].children[0].children.push({label: name, layer: group});
                    }//If there are locations, and it matches the last one, add the map.
                    else if (overlayTree.children[j].children[overlayTree.children[j].children.length-1].label == nameArray[1])
                    {
                        overlayTree.children[j].children[overlayTree.children[j].children.length-1].children.push({label: name, layer: group});
                    }//If it doesn't match any locations, add the location and the map.
                    else
                    {
                        overlayTree.children[j].children.push({label: nameArray[1], selectAllCheckbox: true, children: []});
                        overlayTree.children[j].children[overlayTree.children[j].children.length-1].children.push({label: name, layer: group});
                    }
                }
            }

            colorCount++;


        }


        /*var colorCount = 0;
        for(let item in files.sort())
        {
            if(colorCount == colors.length)
            {
                colorCount = 0;
            }
            var course = 'Maps/' + files[item];

            var group = new L.LayerGroup();

            newRoute(course,colors[colorCount]).on('loaded', function(e) {
                    map.fitBounds(e.target.getBounds());
            }).addTo(group);

            var name = files[item].toString().split('-')[1] + " " + files[item].toString().split('-')[2];

            var org = files[item].toString().split('-')[0];
            var location = files[item].toString().split('-')[1];

            for(let i in overlayTree.children)
            {
                
                if(overlayTree.children[i].label == org)
                {
                


                    //overlayTree.children[i].children.push({label: name, layer: group});
                }
            }

        }*/

        L.control.layers.tree(baseTree,overlayTree).addTo(map);
        //Make Groups
        //for(let i in list)
        //{
        //    this[list[i]] = new L.LayerGroup();
        //}
            
            
        var overlays = [];    
        //for(let index in orgs)
        //{
        //    overlays.push({
        //        groupName : orgs[index],
        //        expanded: true, 
        //        layers: {}
                
        //    });
        //}
        
        var options = {
            collapsed : false,
            group_maxHeight: "500px"
        }
        
        //var control = L.Control.styledLayerControl(backgrounds,overlays,options);
        /*var control = L.Control.styledLayerControl(backgrounds,overlays,options);

        for(let i in orgs)
        {
            var group = new L.LayerGroup();
            control.addOverlay(group,orgs[i],{groupName: orgs[i]});
        }

        //Adding GPX Routes to the Map
        //https://github.com/mpetazzoni/leaflet-gpx
        var colorCount = 0;
        for(let item in files.sort()) {
            if(colorCount == colors.length)
            {
                colorCount = 0;
            }
            var course = 'Maps/' + files[item];
          
            var group = new L.LayerGroup();

            newRoute(course,colors[colorCount]).on('loaded', function(e) {
                    map.fitBounds(e.target.getBounds());
            }).addTo(group);
          
          var name = files[item].toString().split('-')[1] + " " + files[item].toString().split('-')[2];
          control.addOverlay(group,name.split(".")[0],{groupName: name.split(".")[0]});
          
          //group.addTo(map);

          //overlays[(files[item].split('.')[0])] = route;
          //control.addOverlay(group,files[item].toString().split('.')[0],groupName : files[item].toString().split(" ")[0]
            //);
          
          colorCount++;
        }

        //var control = L.Control.styledLayerControl(backgrounds,overlays, {collapsed : false}).addTo(map);
        
        map.addControl(control);*/

        function resizeMapWithWindowChange()
        {
            document.getElementById("map").style.height = (window.innerHeight - 20) + "px";
            map.invalidateSize();
        }
        
        window.addEventListener("resize", resizeMapWithWindowChange);

        //L.easyButton('<img alt="Wendover" src="Icons/Wendover.png"/>',function//(btn,map){
        //    map.setView(['40.645','-114.127753']);
        //},'Wendover').addTo(map);

        //var buttons = [
         // L.easyButton('<img alt="Wendover" src="Icons/Wendover.png"/>',function//(btn,map){map.setView(['40.645','-114.127753']);},'Wendover'),
        //  L.easyButton('<img alt="Wendover" src="Icons/Wendover.png"/>',function//(btn,map){map.setView(['40.645','-114.127753']);},'Wendover')
        //];

        //L.easyBar(buttons).addTo(map);
        //map.removeLayer(marker);


    </script>
  </body>
</html>