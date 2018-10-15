# stormyglass  - a command-line php script for Storm Glass - The Global Marine Weather Forecast API

CLI script `stormyglass.php` calls the [stormglass weather API](https://docs.stormglass.io/) and writes the JSON to a file if successful. Optionally will output the result to *stdout*.

The script can also be run as a web-service with PHP's in-built webserver for testing the JSON request/responses.

## Features

- Runs on the command-line
- Uses a simple [stormyglass.ini](stormyglass.ini.dist) configuration file for options
- Uses command-line [curl](https://curl.haxx.se)
- Can be called as a stand-alone webservice using the php command line built-in server
- Validates parameters before sending
- Provided with global city data (populations > 15000) from http://download.geonames.org/export/dump/ (cities15000.zip) listed/saved as JSON using --cities option, see also [data/cities15000.txt](data/cities15000.txt)
- When calling with a city-id, adds the city information to the 'meta' information returned in the JSON result
- Option to search for city information with --search-city
- Caches the result (json-encoded) to avoiding sending repeat-requests with configurable cache age time (in seconds)
- Cache filename is human-readable - format is: keys-values ie. *cache/lat-lng-57.7333333_10.6.json*
- Option to return results averaged-out across sources
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

## Returned results fields/columns/keys

- time - Timestamp in UTC
- airTemperature - Air temperature in degrees celsius
- airPressure - Air pressure in hPa
- cloudCover - Total cloud coverage in percent
- currentDirection -Direction of current.
- currentSpeed - Speed of current in meters per second.
- gust - Wind gust in m/s
- humidity - Relative humidity in percent
- iceCover - Proportion, 0-1
- precipitation - Mean precipitation in kg/m²
- seaLevel - Height of sea level in MLLW (tides).
- snowDepth - Depth of snow in meters
- swellDirection - Direction of swell waves. 0° indicates swell coming from north
- swellHeight - Height of swell waves in meters
- swellPeriod - Period of swell waves in seconds
- visiblity	Horizontal - visibility in km
- waterTemperature - Water temperature in degrees celsius
- waveDirection - Direction of combined wind and swell waves. 0° indicates waves coming from north
- waveHeight - Height of combined wind and swell waves
- wavePeriod - Period of combined wind and swell waves
- windWaveDirection - Direction of wind waves. 0° indicates waves coming from north
- windWaveHeight - Height of wind waves
- windWavePeriod - Period of wind waves
- windDirection	- Direction of wind. 0° indicates wind coming from north
- windSpeed	- Speed of wind in meters per second

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
             --search-city=<text>     Search for city using supplied text.
        -r,  --refresh                (Optional) Force cache-refresh
        -k,  --key={api key}          (Required) Stormglass API key (loaded from stormyglass.ini if not set)'
             --city-id={city_id}      (Optional) Specify GeoNames city id (in cities.json file) for required latitude/longitude values
             --latitude={-90 - 90}    (Required) Latitude (decimal degrees)
             --longitude={-180 - 180} (Required) Longitude (decimal degrees)
             --source={all}           (Optional) Source. Default: 'all'.  One of (all, dwd, fcoo, fmi, meteo, meto, noaa, sg, smhi, wt, yr)
             --params={}              (Optional) Param(s). Comma-separated. (airPressure, airTemperature, cloudCover, currentDirection, currentSpeed, gust, humidity, precipitation, seaLevel, swellDirection, swellHeight, swellPeriod, visiblity, waterTemperature, waveDirection, waveHeight, wavePeriod, windDirection, windSpeed, windWaveDirection, windWaveHeight, windWavePeriod)
             --date-from={now}        (Optional) Start date/time (at most 48 hours before current UTC), default 'today 00:00:00' see: https://secure.php.net/manual/en/function.strtotime.php
             --date-to={all}          (Optional) End date/time for last forecast, default 'all' see: https://secure.php.net/manual/en/function.strtotime.php
             --average                Return the average of the combined results from across the various sources.
             --dir={.}                (Optional) Directory for storing files (current dir if not specified)
        -f,  --filename={output.}     (Optional) Filename for output data from operation
             --format={json}          (Optional) Output format for output filename (reserved for future): json (default)
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

## Search city example

`php stormyglass.php --echo --search-city=birmingham`

Results:

```
{
    "2655603": {
        "id": 2655603,
        "country_code": "GB",
        "state": "ENG",
        "city": "Birmingham",
        "ascii": "Birmingham",
        "names": [
            "BHX",
            "Birmin'gxam",
            "Birmingam",
            "Birmingamas",
            "Birmingem",
            "Birmingema",
            "Birmingham",
            "Birminghamia",
            "Birminghem",
            "Gorad Birmingem",
            "Mpermincham",
            "bamingamu",
            "barming'hyam",
            "barmingahama",
            "barmingahema",
            "barmingham",
            "barminghama",
            "beoming-eom",
            "birmingemi",
            "bo ming han",
            "bo ming han shi",
            "brmngm",
            "brmynghham",
            "byrmngam",
            "parminkam"
        ],
        "latitude": 52.48142,
        "longitude": -1.89983,
        "elevation": 149,
        "population": null,
        "timezone": "Europe\/London"
    },
    "4049979": {
        "id": 4049979,
        "country_code": "US",
        "state": "AL",
        "city": "Birmingham",
        "ascii": "Birmingham",
        "names": [
            "BHM",
            "Bermincham",
            "Bermingkham",
            "Birmingam",
            "Birmingamas",
            "Birmingem",
            "Birmingema",
            "Birmingham",
            "Birmingham i Alabama",
            "Birminhem",
            "Gorad Birmingem",
            "baminguhamu",
            "baminhamu",
            "barmingahema",
            "barmingahyama",
            "beominghaem",
            "birmingemi",
            "bo ming han",
            "brmnghham",
            "brmyngm  alabama",
            "parminkam"
        ],
        "latitude": 33.52066,
        "longitude": -86.80249,
        "elevation": 187,
        "population": 187,
        "timezone": "America\/Chicago"
    },
    "4986172": {
        "id": 4986172,
        "country_code": "US",
        "state": "MI",
        "city": "Birmingham",
        "ascii": "Birmingham",
        "names": [
            "Bermingkham",
            "Birmingem",
            "Birmingkham",
            "brmnghham",
            "byrmngam  myshygan"
        ],
        "latitude": 42.5467,
        "longitude": -83.21132,
        "elevation": 241,
        "population": 237,
        "timezone": "America\/Detroit"
    }
}

```

## Test using averaged-out results

Return averaged-out results across sources, using 'unixtime' as the index for 'hours'

`php stormyglass.php --debug --test --average`

```
{
    "hours": {
        "1539302400": {
            "airTemperature": 13,
            "cloudCover": 0,
            "currentDirection": 230,
            "currentSpeed": 0,
            "gust": 10,
            "humidity": 85,
            "precipitation": 0,
            "pressure": 1016,
            "seaLevel": 0,
            "swellDirection": 182,
            "swellHeight": 0,
            "swellPeriod": 3,
            "time": "2018-10-12T00:00:00+00:00",
            "visibility": 26,
            "waterTemperature": 11,
            "waveDirection": 134,
            "waveHeight": 1,
            "wavePeriod": 3,
            "windDirection": 144,
            "windSpeed": 6,
            "windWaveDirection": 138,
            "windWaveHeight": 1,
            "windWavePeriod": 3,
            "unixtime": 1539302400
        },
        "1539306000": {
            "airTemperature": 13,
            "cloudCover": 0,
            "currentDirection": 220,
            "currentSpeed": 0,
            "gust": 9,
            "humidity": 86,
            "precipitation": 0,
            "pressure": 1017,
            "seaLevel": 0,
            "swellDirection": 161,
            "swellHeight": 0,
            "swellPeriod": 3,
            "time": "2018-10-12T01:00:00+00:00",
            "visibility": 25,
            "waterTemperature": 11,
            "waveDirection": 134,
            "waveHeight": 1,
            "wavePeriod": 3,
            "windDirection": 150,
            "windSpeed": 5,
            "windWaveDirection": 138,
            "windWaveHeight": 1,
            "windWavePeriod": 3,
            "unixtime": 1539306000
        },
<!--- SNIP --->
    },
    "meta": {
        "cost": 1,
        "dailyQuota": 50,
        "end": "2018-10-22 00:00",
        "lat": 57,
        "lng": 10,
        "params": [
            "waterTemperature",
            "wavePeriod",
            "waveDirection",
            "waveDirection",
            "waveHeight",
            "windWaveDirection",
            "windWaveHeight",
            "windWavePeriod",
            "swellPeriod",
            "swellDirection",
            "swellHeight",
            "windSpeed",
            "windDirection",
            "airTemperature",
            "precipitation",
            "gust",
            "cloudCover",
            "humidity",
            "pressure",
            "visibility",
            "seaLevel",
            "currentSpeed",
            "currentDirection"
        ],
        "requestCount": 5,
        "start": "2018-10-12 00:00"
    }
}
```

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

## Test example using GeoNames CityID and data from today until next Sunday

This will search the data for Barcelona (city id 3128760) from today (00:00) until 'next Sunday (00:00)', save the result to 'barcelona.json' and also echo the output and debug information, piping it into 'less' to view on the command line.

`php stormyglass.php --city-id=3128760 --average --filename=barcelona.json --date-from=today --date-to='next sunday' --echo --debug 2>&1 | less`


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

## Running as a webservice

### Starting the service
1. Start the PHP webserver with `php -S 127.0.0.1:12312`
2. Browse the URL: http://127.0.0.1:12312/stormyglass.php with GET/POST parameters as required.

*NOTE:* The webservice only allows the following parameters which will be filtered and passed to the command-line script:

- 'key'
- 'date-from'
- 'date-to'
- 'latitude'
- 'longitude'
- 'source'
- 'params'
- 'refresh'
- 'cities'
- 'city-id'
- 'search-city'
- 'average'

### Webservice Example 1

e.g. For -id 999999 http://127.0.0.1:12312/stormyglass.php?city-id=999999

http://127.0.0.1:12312/stormyglass.php?city-id=999999

Returns:

```
{
    "errors": [
        "City not found with id: 292672222"
    ]
}
```

### Webservice Example 2

Search for city 'wolverhampton'

http://127.0.0.1:12312/stormyglass.php?search-city=wolverhampton

Result:

```
{
    "2633691": {
        "id": 2633691,
        "country_code": "GB",
        "state": "ENG",
        "city": "Wolverhampton",
        "ascii": "Wolverhampton",
        "names": [
            "Goulverchampton",
            "Heantun",
            "Ulvurkhamptun",
            "Vulvergempton",
            "Vulverhamptonas",
            "Vulverhempton",
            "Vulverhemptona",
            "Vulverkhempton",
            "Wolverhampton",
            "Wulfrunehantona",
            "XVW",
            "ulbeohaempeuteon",
            "u~oruvu~ahanputon",
            "wlwrhmptwn",
            "wu er fu han pu dun",
            "wwlbrhmptwn",
            "wwlfrhambtwn",
            "\u0393\u03bf\u03c5\u03bb\u03b2\u03b5\u03c1\u03c7\u03ac\u03bc\u03c0\u03c4\u03bf\u03bd",
            "\u0412\u0443\u043b\u0432\u0435\u0440\u0433\u0435\u043c\u043f\u0442\u043e\u043d",
            "\u0412\u0443\u043b\u0432\u0435\u0440\u0445\u0435\u043c\u043f\u0442\u043e\u043d",
            "\u0423\u043b\u0432\u044a\u0440\u0445\u0430\u043c\u043f\u0442\u044a\u043d",
            "\u054e\u0578\u0582\u056c\u057e\u0565\u0580\u0570\u0565\u0574\u057a\u057f\u0578\u0576",
            "\u05d5\u05d5\u05d0\u05dc\u05d5\u05d5\u05e2\u05e8\u05d4\u05d0\u05de\u05e4\u05d8\u05d0\u05df",
            "\u05d5\u05d5\u05dc\u05d1\u05e8\u05d4\u05de\u05e4\u05d8\u05d5\u05df",
            "\u0648\u0644\u0648\u0631\u0647\u0645\u067e\u062a\u0648\u0646",
            "\u0648\u0648\u0644\u0641\u0631\u0647\u0627\u0645\u0628\u062a\u0648\u0646",
            "\u0648\u0648\u0644\u0648\u0631\u06c1\u06cc\u0645\u067e\u0679\u0646",
            "\u0e27\u0e38\u0e25\u0e40\u0e27\u0e2d\u0e23\u0e4c\u0e41\u0e2e\u0e21\u0e1b\u0e4c\u0e15\u0e31\u0e19",
            "\u10d5\u10e3\u10da\u10d5\u10d4\u10e0\u10f0\u10d4\u10db\u10de\u10e2\u10dd\u10dc\u10d8",
            "\u30a6\u30a9\u30eb\u30f4\u30a1\u30fc\u30cf\u30f3\u30d7\u30c8\u30f3",
            "\u4f0d\u723e\u5f17\u6f22\u666e\u9813",
            "\uc6b8\ubc84\ud584\ud504\ud134"
        ],
        "latitude": 52.58547,
        "longitude": -2.12296,
        "elevation": 160,
        "population": null,
        "timezone": "Europe\/London"
    }
}
```

The ID 2633691 can then be used to fetch the data for that city.


### Webservice Example 3

Get averaged-out results from the web-service for city 1120985:

http://127.0.0.1:12312/stormyglass.php?city-id=1120985&average

Results:

```
{
    "hours": {
        "1539388800": {
            "airTemperature": 21.07,
            "cloudCover": 0,
            "currentDirection": 0,
            "currentSpeed": 0,
            "gust": 5.88,
            "humidity": 14.35,
            "precipitation": 0,
            "pressure": 1014.57,
            "seaLevel": 0,
            "swellDirection": 0,
            "swellHeight": 0,
            "swellPeriod": 0,
            "time": "2018-10-13T00:00:00+00:00",
            "visibility": 24.1,
            "waterTemperature": 18.55,
            "waveDirection": 0,
            "waveHeight": 0,
            "wavePeriod": 0,
            "windDirection": 0,
            "windSpeed": 0,
            "windWaveDirection": 0,
            "windWaveHeight": 0,
            "windWavePeriod": 0,
            "unixtime": 1539388800
        },
<!--- SNIP --->
    "meta": {
        "cost": 1,
        "dailyQuota": 50,
        "end": "2018-10-23 00:00",
        "lat": 30.95962,
        "lng": 61.86037,
        "params": [
            "airTemperature",
            "cloudCover",
            "currentDirection",
            "currentSpeed",
            "gust",
            "humidity",
            "precipitation",
            "pressure",
            "seaLevel",
            "swellDirection",
            "swellHeight",
            "swellPeriod",
            "visibility",
            "waterTemperature",
            "waveDirection",
            "waveDirection",
            "waveHeight",
            "wavePeriod",
            "windDirection",
            "windSpeed",
            "windWaveDirection",
            "windWaveHeight",
            "windWavePeriod"
        ],
        "requestCount": 4,
        "start": "2018-10-13 00:00",
        "city": {
            "id": 1120985,
            "country_code": "AF",
            "state": 19,
            "city": "Zaranj",
            "ascii": "Zaranj",
            "names": [
                "Sarandsch",
                "ZAJ",
                "Zaranas",
                "Zarandj",
                "Zarandz",
                "Zarandzas",
                "Zarandzh",
                "Zarand\u017c",
                "Zarand\u017eas",
                "Zarang",
                "Zarani",
                "Zaranj",
                "Zaran\u011d",
                "Zerenc",
                "Zhazang",
                "Z\u0259r\u0259nc",
                "zaranja",
                "zha lan ji",
                "zrnj",
                "\u0417\u0430\u0440\u0430\u043d\u0434\u0436",
                "\u0417\u0430\u0440\u0430\u043d\u0438",
                "\u0417\u0430\u0440\u0430\u043d\u04b7",
                "\u0632\u0631\u0646\u062c",
                "\u091c\u093c\u0930\u0902\u091c",
                "\u624e\u5170\u5b63"
            ],
            "latitude": 30.95962,
            "longitude": 61.86037,
            "elevation": 489,
            "population": null,
            "timezone": "Asia\/Kabul"
        }
    }
}
```


## See also

- [stormglass dashboard](https://dashboard.stormglass.io)

## To do

- [Area Request](https://docs.stormglass.io/?shell#area-request) - not yet implemented due to limited API account (uses up requests!)

--
vijay@yoyo.org
