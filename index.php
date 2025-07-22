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

        //Get the unique values in an Array and return an Array of only those values
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

        //Creat a new Route for display based on a GPX file
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
    
        //Colors to rotate through for routes
        var colors = ['red','teal','darkorange','darkgreen','hotpink','blue','gold','violet','maroon','deepskyblue'];

        //Create the Topo layer of the Map
        var topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: 'Map Data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> | Map Display: &copy; <a href="http://opentopomap.org/"> OpenTopoMap </a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
            maxZoom: 18,
            subdomains: ['a','b','c']
        });

        //Create the Streets layer of the Map
        var streets = L.tileLayer( 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 18,
            subdomains: ['a','b','c']
        });

        //Create the Map, Centered south of Wendover, UT
        map = L.map('map', {
            center: [40.643901, -114.131361],
            zoom: 14,
            layers: [topo],
            doubleClickZoom: false
        });

        //Create Background Options for the Map Menu
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

        //Create an array of Files that are currently in the Maps Directory
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
            //Name format for files is Organization Abbreviations-Race Name-Year.GPX
            var nameArray = files[index].toString().split('-');
            
            locations.push(nameArray[1]);
            organizations.push(nameArray[0]);
            
        }
        
        //Get Unique values for Locations and Organizations for Menu
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
        
        //Add organizations to Menu
        for(let i in orgs)
        {
            overlayTree.children.push({label: orgs[i], selectAllCheckbox: true, children: []});
        }

        //Add race maps to Menu
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
                    if(overlayTree.children[j].children.length == 0)
                    {
                        overlayTree.children[j].children.push({label: nameArray[1], selectAllCheckbox: true, children: []});
                        overlayTree.children[j].children[0].children.push({label: name, layer: group});
                    }
                    else if (overlayTree.children[j].children[overlayTree.children[j].children.length-1].label == nameArray[1])
                    {
                        overlayTree.children[j].children[overlayTree.children[j].children.length-1].children.push({label: name, layer: group});
                    }
                    else
                    {
                        overlayTree.children[j].children.push({label: nameArray[1], selectAllCheckbox: true, children: []});
                        overlayTree.children[j].children[overlayTree.children[j].children.length-1].children.push({label: name, layer: group});
                    }
                }
            }

            colorCount++;


        }

        //Add Layer to Map
        L.control.layers.tree(baseTree,overlayTree,{selectorBack: true}).addTo(map);
            
        var overlays = [];    

        var options = {
            collapsed : false,
            group_maxHeight: "500px"
        }

        //Resize the Map to fit the current window size
        function resizeMapWithWindowChange()
        {
            document.getElementById("map").style.height = (window.innerHeight - 20) + "px";
            map.invalidateSize();
        }
        
        window.addEventListener("resize", resizeMapWithWindowChange);

    </script>
  </body>
</html>
