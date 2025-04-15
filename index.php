<head>
    <title>LRT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <script src="js/bootstrap.min.js"></script>


    <script src="js/jquery-3.7.1.min.js"></script>
    <?php
    $station =  isset($_GET['sta']) ? $_GET['sta'] : "TUM";


    ob_start();
    ?>
    <script>
        const station = '<?= $station ?>';
    </script>
    <?php
    print ob_get_clean();
    ?>
</head>

<body>
    <div class="container p-3">
        <h1 class="display-3">輕鐵月台資訊顯示</h1>
        <div id="mtr_time">
        </div>

        <?php
        //$url = 'https://res.data.gov.hk/api/get-download-file?name=https%3A%2F%2Fopendata.mtr.com.hk%2Fdata%2Fmtr_lines_and_stations.csv';
        $url = 'lrt_stations.csv';

        // Fetch the CSV content
        $csvContent = file_get_contents($url);
        if ($csvContent !== false) {
            // Parse the CSV content into an array
            $rows = array_map('str_getcsv', explode("\n", $csvContent));
            $headers = $rows[0];
            array_shift($rows);
            $assocRows = [];
            //print_r($headers);
            foreach ($rows as $row) {
                //print_r(count($row));
                if (count($row) == 3) {
                    $assocRows[] = array_combine($headers, $row);
                }
            }
            // Print the array
            //var_dump($assocRows);
        } else {
            echo "Failed to load the CSV file.";
        }
        ?>
    </div>

</body>
<script>
    const mtrStationsData = <?= json_encode($assocRows); ?>;
    const routeData = <?= (file_get_contents("lrt_route.json")); ?>;
    jQuery(function() {
        jQuery("#mtr_time").append(
            jQuery("<select>", {
                id: "route",
                name: "route",
                class: "form-select"
            })
        );
        jQuery("#mtr_time").append(
            jQuery("<select>", {
                id: "stations",
                name: "stations",
                class: "form-select"

            })
        );

        var lineName = [];
        lineName.push("Please Select");
        routeData["routes"].forEach(function(element) {

            if (lineName.includes(element["name"]) == false) {
                lineName.push(element["name"]);
            }
        });

        var stationNames = [];
        mtrStationsData.forEach(function(station) {
            $("#stations").append($("<option>", {
                value: station["\ufeffStationID"],
                text: station["NameChi"],
            }).text(station["NameChi"]));
        })
        console.log(stationNames)
        <?php if (isset($_GET['staID'])) { ?>
            mtr(<?= $_GET['staID'] ?>);
            $("#stations").val(<?= $_GET['staID'] ?>)
        <?php } ?>


        lineName.forEach(function(element) {
            jQuery("#route").append(
                jQuery("<option>", {
                    value: element
                }).text(element)
            );
        });
        jQuery("#route").change(function() {
            var route = jQuery(this).val();
            var stationName = [];

            var routeStations = routeData.routes.find(r => r.name == route)['stations'];

            routeStations.forEach(function(stationID) {
                stationName.push({
                    value: stationID,
                    text: mtrStationsData.find(r => r["\ufeffStationID"] == stationID)['NameChi'],
                });
            })
            console.log(routeStations);
            jQuery("#stations").empty();
            jQuery("#stations").append(
                jQuery("<option>").text("Please Select")
            );
            stationName.forEach(function(station) {
                jQuery("#stations").append(
                    jQuery("<option>", {
                        value: station["value"]
                    }).text(station["text"])
                );
            });
            jQuery("#stations").change(function() {
                var StaID = $(this).val();
                mtr(StaID)
                history.pushState(
                    "",
                    "",
                    "?staID=" + StaID);
            });
            console.log(stationName);
        });
    });

    function findStationByCode(stationCode) {
        const station = mtrStationsData.find(
            (s) => s["Station Code"] === stationCode
        );
        if (station) {
            return {
                EnglishName: station["English Name"],
                ChineseName: station["Chinese Name"],
            };
        } else {
            return null; // Station not found
        }
    }

    function mtr(stationID) {


        jQuery.ajax({
            url: "https://rt.data.gov.hk/v1/transport/mtr/lrt/getSchedule",
            data: {

                station_id: stationID
            },
            method: "GET",
            success: function(data) {
                console.log(data);
                jQuery(".mtr,.platform").remove();
                $("#mtr_time").append($("<div>", {
                    class: "row g-3 mtr",
                    id: "platform"
                }))

                data.platform_list.forEach(function(platform) {
                    $("#platform").append($("<div>", {
                        class: "col-12 platform",
                        id: "platform_" + platform["platform_id"]
                    }).html("<h3> " + platform["platform_id"] + "號月台</h3>"));

                    platform["route_list"].forEach(function(route) {
                        var routeRow = $("<div>", {
                            class: "row g-1 g-sm-1 g-md-2 g-lg-3 align-items-center"
                        });
                        routeRow.append($("<div>", {
                            class: "col-2 col-sm-2 col-md-2 col-lg-2 text-center"
                        }).html($("<p>", {
                            class: "m-0 fs-5  fw-semibold"
                        }).css({
                            "width": "3em",
                            "border-radius": "30px",
                            "border": "5px solid " + routeData.routes.find(r => r.name == route["route_no"])['color']
                        }).append(route['route_no'])));
                        routeRow.append($("<div>", {
                            class: "col-5 col-sm-4 col-md-5 col-lg-6"
                        }).html("<p class='mb-0' style='font-size:2rem'>" + route['dest_ch'] + "</p><p class='mb-0' style='font-size:1.2rem'>" + route['dest_en'] + "</p>"));

                        var carLengthDisplay = $("<div>");
                        for (i = 0; i < route['train_length']; i++) {
                            carLengthDisplay.append($("<img>", {
                                src: "metro.svg",
                                width: "50%",
                                'title': 'car length display'
                            }));
                        }
                        routeRow.append($("<div>", {
                            class: "col-2 col-sm-3 col-md-2 col-lg-2 carLength"
                        }).append(carLengthDisplay));

                        if (route['time_ch'] !== "正在離開" && route['time_ch'] !== "即將抵達") {
                            var minRow = $("<div>", {
                                class: "row g-2"
                            });
                            minRow.append($("<span>", {
                                class: "col text-end fs-2"
                            }).css({}).append(route['time_ch'].replace("分鐘", "")));

                            minRow.append($("<p>", {
                                class: "col fs-6"
                            }).css({}).append("分鐘<br>Min"));
                        } else {
                            var minRow = $("<p>", {
                                class: "m-0 text-end fs-4"
                            }).css({}).append(route['time_ch']);
                        }
                        routeRow.append($("<div>", {
                            class: "col-3 col-sm-3 col-md-3 col-lg-2 "
                        }).append(minRow));

                        $("#platform_" + platform["platform_id"]).append(routeRow);
                    });
                    console.log(platform)
                })
            },
        });
    }
    jQuery(function() {
        //mtr();
    });
</script>