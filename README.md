# stormyglass

CLI script `stormyglass.php` calls the [stormglass weather API](https://docs.stormglass.io/) and writes the JSON to a file if successful. Optionally will output the result to *stdout*.

## Features

- Runs on the command-line
- Uses a simple [stormyglass.ini](stormyglass.ini.dist) configuration file for options
- Uses command-line [curl](https://curl.haxx.se)
- Validates parameters before sending
- Provided with global city data (populations > 15000) from http://download.geonames.org/export/dump/ (cities15000.zip) listed/saved as JSON using --cities option, see also [data/cities15000.txt](data/cities15000.txt)
- Caches the result (json-encoded) to avoiding sending repeat-requests with configurable cache age time (in seconds)
- Cache filename is human-readable - format is: key1-key-2..._value1_value2....json, ie. *cache/lat-lng-57.7333333_10.6.json*
- All messages when running with `--debug` or `--verbose` are to *stderr* to avoid interference with *stdout*
- Can output the result if successful to *stdout*
- Errors are output in JSON as 'errors' with just a bunch of strings with each error message as opposed to errors => param => array(0 => error) format, e.g.:

```
{
    "errors": [
        "Unknown param(s) specified: 'humiditys'. Must be at least one of (airPressure, airTemperature, cloudCover, currentDirection, currentSpeed, gust, humidity, precipitation, seaLevel, swellDirection, swellHeight, swellPeriod, visiblity, waterTemperature, waveDirection, waveHeight, wavePeriod, windDirection, windSpeed, windWaveDirection, windWaveHeight, windWavePeriod)"
    ]
}
```

## Instructions

### Command-line options

```
Usage: php stormyglass.php
Call to the stormglass API - https://docs.stormglass.io
(Specifying any other unknown argument options will be ignored.)

        -h,  --help                   Display this help and exit
        -v,  --verbose                Run in verbose mode
        -d,  --debug                  Run in debug mode (implies also -v, --verbose)
        -t,  --test                   Run in test mode, using co-ordinates for Skagen, Denmark from stormyglass.ini file by default.
        -o,  --offline                Do not go-online when performing tasks (only use local files for url resolution for example)
        -e,  --echo                   (Optional) Echo/output the result to stdout if successful
             --cities                 List known cities with id, names and geolocation co-ordinates then exit.
        -r,  --refresh                (Optional) Force cache-refresh
        -k,  --key={api key}          (Required) Stormglass API key (loaded from stormyglass.ini if not set)'
             --city-id={city_id}      (Optional) Specify GeoNames city id (in cities.json file) for required latitude/longitude values
             --latitude={-90 - 90}    (Required) Latitude (decimal degrees)
             --longitude={-180 - 180} (Required) Longitude (decimal degrees)
             --source={all}           (Optional) Source. Default: 'all'.  One of (all, dwd, fcoo, fmi, meteo, meto, noaa, sg, smhi, wt, yr)
             --params={}              (Optional) Param(s). Comma-separated. (airPressure, airTemperature, cloudCover, currentDirection, currentSpeed, gust, humidity, precipitation, seaLevel, swellDirection, swellHeight, swellPeriod, visiblity, waterTemperature, waveDirection, waveHeight, wavePeriod, windDirection, windSpeed, windWaveDirection, windWaveHeight, windWavePeriod)
             --date-from={now}        (Optional) Start date/time (at most 48 hours before current UTC), default 'today 00:00:00' see: https://secure.php.net/manual/en/function.strtotime.php
             --date-to={all}          (Optional) End date/time for last forecast, default 'all' see: https://secure.php.net/manual/en/function.strtotime.php
             --dir={.}                (Optional) Directory for storing files (current dir if not specified)
        -f,  --filename={output.}     (Optional) Filename for output data from operation, default is 'output.{--format}'
             --format={json}          (Optional) Output format for script data: json (default)
```

### Requirements/Installation

- An API key [from stormglass](https://stormglass.io/) (free sign-up is 50 request/daily)
- PHP7
- curl (command-line)
- Copy the `stormyglass.ini.dist` to `stormyglass.ini` and add your API key there.

## Testing Example

Run the following to run the test and view in 'less' text viewer:

`php stormyglass.php --debug --test 2>&1 | less`

This will use the default co-ordinates of Skagen, Denmark and if successful will write-out a json file to the `cache` directory.

Running the same command-line again will retrieve the data from the cached file, e.g. this, which will write the result also to a file called `skagen.json`

`php stormyglass.php --verbose --filename=skagen.json --test 2>&1 | less`

Output result to *stdout*

`php stormyglass.php --filename=skagen.json --test --echo`

Output the result to *stdout*, view messages whilst running, and redirect to into another file:

`php stormyglass.php --filename=skagen.json --test --debug --echo 2>&1 >myfile.json`

## Test example using GeoNames City ID

Searches for city with ID '8349222'

```
php stormyglass.php --city-id=8349222 --debug
```

Debug data of city result:

```
Array
(
    [8349222] => Array
        (
            [id] => 8349222
            [country_code] => AU
            [state] => 2
            [city] => Punchbowl
            [ascii] => Punchbowl
            [names] => Array
                (
                    [0] =>
                )

            [latitude] => -33.92893
            [longitude] => 151.05111
            [elevation] => 19
            [population] =>
            [timezone] => Australia/Sydney
        )

)
```

Data saved in: `cache/lat-lng--33.92893_151.05111.json`


## Offline mode debugging example

An example for debugging - get the wave-height and humidity for Liverpool, UK using data from The Met Office for yesterday 7am until next thursday, 2230 and display the request that would be sent:

`php stormyglass.php --debug --filename=liverpool.json --source=met --latitude=53.416667 --longitude=-2.9779400 --params=waveHeight,humidity --date-from='yesterday, 7am' --date-to='next Friday, 2230' --echo 2>&1 | less`

Note the *V* is (--verbose) mode output, and the *D* is (--debug) mode output, and the numbers following in format 9/9 are memory (used/peak memory used) in script

Result:

```
[D 1/1] CONFIG
Array
(
    [timezone] => UTC
    [cache] => Array
        (
            [seconds] => 86400
        )

    [key] => **<API_KEY>**
    [url] => Array
        (
            [point] => https://api.stormglass.io/point
            [area] => https://api.stormglass.io/area
        )

    [sources] => Array
        (
            [0] => all
            [1] => dwd
            [2] => fcoo
            [3] => fmi
            [4] => meteo
            [5] => meto
            [6] => noaa
            [7] => sg
            [8] => smhi
            [9] => wt
            [10] => yr
        )

    [params] => Array
        (
            [0] => airPressure
            [1] => airTemperature
            [2] => cloudCover
            [3] => currentDirection
            [4] => currentSpeed
            [5] => gust
            [6] => humidity
            [7] => precipitation
            [8] => seaLevel
            [9] => swellDirection
            [10] => swellHeight
            [11] => swellPeriod
            [12] => visiblity
            [13] => waterTemperature
            [14] => waveDirection
            [15] => waveHeight
            [16] => wavePeriod
            [17] => windDirection
            [18] => windSpeed
            [18] => windSpeed
            [19] => windWaveDirection
            [20] => windWaveHeight
            [21] => windWavePeriod
        )

    [latitude] => 57.7333333
    [longitude] => 10.6
)
[D 1/1] COMMANDS:
Array
(
    [curl] => /usr/local/bin/curl
)
[D 1/1] OPTIONS:
Array
(
    [debug] => 1
    [echo] => 1
    [offline] => 1
    [test] => 0
    [verbose] => 1
)
[V 1/1] OUTPUT_FORMAT: json
[D 1/1] Using API key: **<API_KEY>**
[V 1/1] Latitude: 53.416667
[V 1/1] Longitude: -2.97794
[V 1/1] Param(s):
Array
(
    [0] => waveHeight
    [1] => humidity
)
[V 1/1] Filtering tweets FROM date/time 'yesterday, 7am': Wed, 10 Oct 2018 07:00:00 +0000
[V 1/1] Filtering tweets TO date/time 'next Friday, 2230': Fri, 12 Oct 2018 22:30:00 +0000
[V 1/1] Request params:
Array
(
    [lat] => 53.416667
    [lng] => -2.97794
    [params] => waveHeight,humidity
    [start] => 2018-10-10T07:00:00+00:00
    [end] => 2018-10-12T22:30:00+00:00
)
[D 1/1] Cached file data not found for: /Users/vijay/src/stormyglass/cache/e5afc408fd978d2a46783cc3f770fdcf1821ec5b.json
[D 1/1] OFFLINE MODE! Can't request:
        curl -H "Authorization: **<API_KEY>**" --connect-timeout 3 --max-time 30 --ciphers ALL -k -L -s 'https://api.stormglass.io/point?lat=53.416667&lng=-2.97794&params=waveHeight%2Chumidity&start=2018-10-10T07%3A00%3A00%2B00%3A00&end=2018-10-12T22%3A30%3A00%2B00%3A00'

Error(s):
        - Request failed:
```

The request obviously failed because we used `--offline`! Remove it to actually perform the request.

The actual result:

```
{
    "hours": [
        {
            "humidity": [
                {
                    "source": "sg",
                    "value": 89.98
                },
                {
                    "source": "noaa",
                    "value": 94.16
                },
                {
                    "source": "wrf",
                    "value": 73.94
                },
                {
                    "source": "dwd",
                    "value": 89.98
                }
            ],
            "time": "2018-10-10T07:00:00+00:00",
            "waveHeight": [
                {
                    "source": "sg",
                    "value": 0.3
                },
                {
                    "source": "meto",
                    "value": 0.3
                },
                {
                    "source": "dwd",
                    "value": 0.25
                },
                {
                    "source": "meteo",
                    "value": 0.3
                }
            ]
        },
<---- SNIP!!! ---->
    ],
    "meta": {
        "cost": 1,
        "dailyQuota": 50,
        "end": "2018-10-12 22:30",
        "lat": 53.416667,
        "lng": -2.97794,
        "params": [
            "waveHeight",
            "humidity"
        ],
        "requestCount": 14,
        "start": "2018-10-10 07:00"
    }
}
```

## Updating cities list

- Go to [http://download.geonames.org/export/dump/](http://download.geonames.org/export/dump/)
- Download [cities15000.zip](http://download.geonames.org/export/dump/cities15000.zip)
- Unzip and save to [data/cities.tsv](data/cities.tsv)
- Run script with '-r' to refresh cache and '--cities' to re-create [cache/cities.json](cache/cities.json)

## See also

- [stormglass dashboard](https://dashboard.stormglass.io)

## To do

- [Area Request](https://docs.stormglass.io/?shell#area-request) - not yet implemented due to limited API account (uses up requests!)

--
vijay@yoyo.org
