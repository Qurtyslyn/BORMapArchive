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
            layers: [topo]
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
        
        for(let index in files)
        {
            locations.push((files[index].toString().split(' '))[0]);
        }
        
        //locations = locations.unique();
        
        var list = getUniqueValues(locations);
        
        
        //Make Groups
        for(let i in list)
        {
            this[list[i]] = new L.LayerGroup();
        }
            
            
        var overlays = [];    
        for(let index in list)
        {
            overlays.push({
                groupName : list[index],
                expanded: true, 
                layers: {}
                
            });
        }
        
        var options = {
            collapsed : false,
            group_maxHeight: "500px"
        }
        
        //var control = L.Control.styledLayerControl(backgrounds,overlays,options);
        var control = L.Control.styledLayerControl(backgrounds,overlays,options);

        

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

            var route = new L.GPX(course, {
                async: true,
                polyline_options: {
                    color: colors[colorCount],
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
                        'Checkpoint': 'Icons/' + colors[colorCount] + '-checkpoint.png',
                        'Recovery': 'Icons/' + colors[colorCount] + '-recovery.png',
                        'RoadCrossing': 'Icons/' + colors[colorCount] + '-roadcrossing.png',
                        'StartFinish': 'Icons/' + colors[colorCount] + '-startfinish.png',
                        'Radio': 'Icons/' + colors[colorCount] + '-radio.png',
                        'Pits': 'Icons/' + colors[colorCount] + '-pits.png',
                        'Monitor': 'Icons/' + colors[colorCount] + '-monitor.png',
                        '': 'Icons/' + colors[colorCount] + '.png'
                    }
                },
                
            }).on('loaded', function(e) {
                    map.fitBounds(e.target.getBounds());
          }).addTo(group);
          
          control.addOverlay(group,files[item].toString().split('.')[0],{groupName: files[item].toString().split(" ")[0]});
          
          group.addTo(map);

          //overlays[(files[item].split('.')[0])] = route;
          //control.addOverlay(group,files[item].toString().split('.')[0],groupName : files[item].toString().split(" ")[0]
            //);
          
          colorCount++;
        }

        //var control = L.Control.styledLayerControl(backgrounds,overlays, {collapsed : false}).addTo(map);
        
        map.addControl(control);

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