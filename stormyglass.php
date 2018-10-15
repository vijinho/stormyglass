<?php
/**
 * stormyglass.php - CLI script for interacting with stormglass api
 * relies on command-line tools, tested on MacOS.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2018 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @see https://docs.stormglass.io
 */
ini_set('default_charset', 'utf-8');
ini_set('mbstring.encoding_translation', 'On');
ini_set('mbstring.func_overload', 6);
ini_set('auto_detect_line_endings',TRUE);

// detect if run in web mode or cli
switch (php_sapi_name()) {
    case 'cli':
        break;
    default:
    case 'cli-server': // run as web-service
        define('DEBUG', 0);
        $save_data = 0;
        $params = [
            'key',
            'date-from',
            'date-to',
            'latitude',
            'longitude',
            'source',
            'params',
            'refresh',
            'cities',
            'city-id',
            'average',
            'search-city'
        ];

        // filter input variables
        $_REQUEST = array_change_key_case($_REQUEST);
        $keys = array_intersect($params, array_keys($_REQUEST));
        $params = [];
        foreach ($_REQUEST as $k => $v) {
            if (!in_array($k, $keys)) {
                unset($_REQUEST[$k]);
                continue;
            }
            $v = trim(strip_tags(filter_var(urldecode($v), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)));
            if (!empty($v)) {
                $_REQUEST[$k] = $v;
                // params to command line
                $params['--' . $k] = escapeshellarg($v);
            } else {
                $params['--' . $k] = '';
            }
        }

        // build command line
        $php = cmd_execute('which php');
        $cmd = $php[0] . ' ' . $_SERVER['SCRIPT_FILENAME'] . ' --echo ';
        foreach ($params as $k => $v) {
            $cmd .= (empty($v)) ? " $k" : " $k=$v";
        }

        // exexute command line and quit
        $data = shell_execute($cmd);
        header('Content-Type: application/json');
        echo $data['stdout'];
        exit;
}

//-----------------------------------------------------------------------------
// required commands check
$requirements = [
    'curl' => 'tool: curl - https://curl.haxx.se',
];

$commands = get_commands($requirements);

if (empty($commands)) {
    verbose("Error: Missing commands.", $commands);
    exit;
}

// load stormyglass.ini file
$config = parse_ini_file(dirname(__FILE__) . '/stormyglass.ini', true);

date_default_timezone_set($config['settings']['timezone']);

// split the comma-separated values from the .ini file:
$source            = preg_split("/,/", $config['api']['sources']);
$source            = array_unique($source);
sort($source);
$config['api']['sources'] = $source;
unset($source);

$params           = preg_split("/,/", $config['api']['params']);
$params           = array_unique($params);
sort($params);
$config['api']['params'] = $params;
unset($params);

//-----------------------------------------------------------------------------
// define command-line options
// see https://secure.php.net/manual/en/function.getopt.php
// : - required, :: - optional

$options = getopt("hvdtk:f:oer",
    [
    'help', 'verbose', 'debug', 'test', 'offline', 'echo',
    'dir:', 'filename:',
    'key:',
    'date-from:', 'date-to:', 'timezone:',
    'latitude:', 'longitude:',
    'source:',
    'params:',
    'format:',
    'refresh',
    'cities',
    'city-id:',
    'search-city:',
    'average'
    ]);

$do = [];
foreach ([
'verbose' => ['v', 'verbose'],
 'test'    => ['t', 'test'],
 'debug'   => ['d', 'debug'],
 'test'    => ['t', 'test'],
 'offline' => ['o', 'offline'],
 'echo'    => ['e', 'echo'],
 'refresh' => ['r', 'refresh'],
 'cities' => [null, 'cities'],
 'average' => [null, 'average'],
 'search-city' => [null, 'search-city']
] as $i => $opts) {
    $do[$i] = (int) (array_key_exists($opts[0], $options) || array_key_exists($opts[1],
            $options));
}

if (array_key_exists('debug', $do) && !empty($do['debug'])) {
    $do['verbose']      = $options['verbose'] = 1;
}

ksort($do);

//-----------------------------------------------------------------------------
// defines (int) - forces 0 or 1 value

define('DEBUG', (int) $do['debug']);
define('VERBOSE', (int) $do['verbose']);
define('TEST', (int) $do['test']);
define('OFFLINE', (int) $do['offline']);
define('SG_API_URL_POINT', $config['api']['url']['point']);
define('SG_API_URL_AREA', $config['api']['url']['area']);

debug('CONFIG', $config);
debug("COMMANDS:", $commands);
debug('OPTIONS:', $do);

if (TEST) {
    verbose(sprintf('TEST Mode. Overriding latitude and longitude with config.ini values: %f latitutde, %f longitude', $config['test']['latitude'], $config['test']['longitude']));
    $options['latitude']  = $config['test']['latitude'];
    $options['longitude'] = $config['test']['longitude'];
}

//-----------------------------------------------------------------------------
// help
if (empty($options) || array_key_exists('h', $options) || array_key_exists('help',
        $options)) {
    options:

    $readme_file = dirname(__FILE__) . '/README.md';
    if (file_exists($readme_file)) {
        $readme = file_get_contents('README.md');
        if (!empty($readme)) {
            output($readme . "\n");
        }
    }

    print "Requirements:\n";
    foreach ($requirements as $cmd => $desc) {
        printf("%s:\n\t%s\n", $cmd, $desc);
    }

    print join("\n",
            [
        "Usage: php stormyglass.php",
        "Call to the stormglass API - https://docs.stormglass.io",
        "(Specifying any other unknown argument options will be ignored.)\n",
        "\t-h,  --help                   Display this help and exit",
        "\t-v,  --verbose                Run in verbose mode",
        "\t-d,  --debug                  Run in debug mode (implies also -v, --verbose)",
        "\t-t,  --test                   Run in test mode, using co-ordinates for Skagen, Denmark from stormyglass.ini file by default.",
        "\t-o,  --offline                Do not go-online when performing tasks (only use local files for url resolution for example)",
        "\t-e,  --echo                   (Optional) Echo/output the result to stdout if successful",
        "\t     --cities                 List known cities with id, names and geolocation co-ordinates then exit.",
        "\t     --search-city=<text>     Search for city using supplied text.",
        "\t-r,  --refresh                (Optional) Force cache-refresh",
        "\t-k,  --key={api key}          (Required) Stormglass API key (loaded from stormyglass.ini if not set)'",
        "\t     --city-id={city_id}      (Optional) Specify GeoNames city id (in cities.json file) for required latitude/longitude values",
        "\t     --latitude={-90 - 90}    (Required) Latitude (decimal degrees)",
        "\t     --longitude={-180 - 180} (Required) Longitude (decimal degrees)",
        "\t     --source={all}           (Optional) Source. Default: 'all'.  One of (" . join(', ',
            $config['api']['sources']) . ")",
        "\t     --params={}              (Optional) Param(s). Comma-separated. (" . join(', ',
            $config['api']['params']) . ")",
        "\t     --date-from={now}        (Optional) Start date/time (at most 48 hours before current UTC), default 'today 00:00:00' see: https://secure.php.net/manual/en/function.strtotime.php",
        "\t     --date-to={all}          (Optional) End date/time for last forecast, default 'all' see: https://secure.php.net/manual/en/function.strtotime.php ",
        "\t     --average                Return the average of the combined results from across the various sources.",
        "\t     --dir=                   (Optional) Directory for storing files (sys_get_temp_dir() if not specified)",
        "\t-f,  --filename={output.}     (Optional) Filename for output data from operation",
        "\t     --format={json}          (Optional) Output format for output filename (reserved for future): json (default)",
    ]);

    // goto jump here if there's a problem
    errors:
    if (!empty($errors)) {
        if (is_array($errors)) {
             echo json_encode(['errors' => $errors], JSON_PRETTY_PRINT);
        }
    } else {
        output("\nNo errors occurred.\n");
    }

    goto end;
    exit;
}

//-----------------------------------------------------------------------------
// initialise variables

$errors = []; // errors to be output if a problem occurred
$output = []; // data to be output at the end
$save_data = true; // save the array $data in 'output:' section at the end
$format = '';
if (!empty($options['format'])) {
    $format = $options['format'];
}
switch ($format) {
    default:
    case 'json':
        $format = 'json';
}
define('OUTPUT_FORMAT', $format);
verbose("OUTPUT_FORMAT: $format");

//-----------------------------------------------------------------------------
// get dir and file for output

$dir = sys_get_temp_dir();
if (!empty($options['dir'])) {
    $dir = $options['dir'];
}
$dircheck = realpath($dir);
if (empty($dircheck) || !is_dir($dircheck)) {
    $errors[] = "You must specify a valid directory!";
    goto errors;
}

$output_filename = !empty($options['filename']) ? $options['filename'] : '';


//-----------------------------------------------------------------------------
// list cities
//
// data for cities from http://download.geonames.org/export/dump/
$data_dir  = realpath(dirname(__FILE__)) . '/data';
$cities_file = $data_dir . '/cities15000.tsv';
if (!file_exists($cities_file)) {
    $errors[] = "Missing cities file: $cities_file";
    goto errors;
}
define('FILE_TSV_CITIES', $cities_file);

if ($do['cities']) {
    $data = [];
    $output_filename = realpath($dir) . '/' . 'cities.json';
    // remove old-file if refresh
    if ($do['refresh']) {
        unlink($output_filename);
    }
    if (file_exists($output_filename)) {
        $data = json_load($output_filename);
        if (is_array($data) && count($data)) {
            debug("Cached city data loaded from: $output_filename");
            $save_data = false;
        } else {
            debug("Cached file data not found for: $cache_file");
        }
    }
    if (empty($data)) {
        $data = getCities();
    }
    // save cities and finish
    goto output;
}

//-----------------------------------------------------------------------------
// get api key

$key = '';
if (!empty($options['k'])) {
    $key = $options['k'];
} else if (!empty($options['key'])) {
    $key = $options['key'];
}
if (empty($key)) {
    $key = $config['api']['key'];
}
if (empty($key)) {
    $errors[] = "You must specify an api key!";
    goto errors;
}

define('SG_API_KEY', $key);

debug("Using API key: $key");

//-----------------------------------------------------------------------------
// get latitude, longitude
if (!empty($options['city-id'])) {
    $city_id = (int) $options['city-id'];
    if ($city_id < 1) {
        $errors[] = "Invalid city id: $city_id";
        goto errors;
    }
    $city = getCities($city_id);
    if (empty($city)) {
        $errors[] = "City not found with id: $city_id";
        goto errors;
    }
    debug("Found city with id: $city_id", $city);
    $city = $city[$city_id];
    $options['longitude'] = $city['longitude'];
    $options['latitude'] = $city['latitude'];
}

// search for city
if (!empty($options['search-city'])) {
    $search = trim(strtolower($options['search-city']));
    $cities = getCities();
    // removes all unmatched cities
    foreach ($cities as $i => $city) {
        if (false !== stristr($city['city'], $search) || false !== stristr($city['ascii'], $search)) {
            continue;
        }
        if (empty($city['names'])) {
            continue;
        }
        foreach ($city['names'] as $name) {
            if (false !== stristr($name, $search)) {
                continue;
            }
        }
        unset($cities[$i]);
    }
    if (empty($cities)) {
        $errors[] = "No matching cities matched for city: '$search'";
        goto errors;
    }
    $data = $cities;
    goto output;
}

$latitude = array_key_exists('latitude', $options) ? (float) $options['latitude']
        : null;
$latitude = (float) $latitude;
if (-90 > $latitude || 90 < $latitude) {
    $errors[] = "You must specify a value for latitude (-90 to 90)!";
    goto errors;
}
verbose("Latitude: $latitude");

$longitude = array_key_exists('longitude', $options) ? (float) $options['longitude']
        : null;
if (-180 > $longitude || 180 < $longitude) {
    $errors[] = "You must specify a value for longitude (-180 to 180)!";
    goto errors;
}
verbose("Longitude: $longitude");

// get the sources, sort
$source = '';
if (!empty($options['source'])) {
    $source = strtolower(trim($options['source']));
}
if (!empty($source)) {
    $source = preg_split("/,/", $source);
    if (!empty($source)) {
        $source = array_unique($source);
        sort($source);
    }
    if (is_string($source)) {
        $source = [$source];
    }
}
if (is_array($source)) {
    foreach ($source as $v) {
        if (!in_array($v, $config['api']['sources'])) {
            $errors[] = "Unknown source(s) specified: '$v'. Must be at least one of (" . join(', ',
                    $config['api']['sources']) . ")";
            goto errors;
        }
    }
}
if (!empty($source)) {
    verbose("Source:", $source);
    $source = join(',', $source);
}

// get the params, sort
$params = [];
if (!empty($options['params'])) {
    $params = trim($options['params']);
}
if (!empty($params)) {
    $params = preg_split("/,/", $params);
    if (!empty($source)) {
        $params = array_unique($params);
        sort($params);
    }
}
if (is_string($params)) {
    $params = [$params];
} else if (empty($params)) {
    $params = [];
}
if (is_array($params)) {
    foreach ($params as $v) {
        if (!in_array($v, $config['api']['params'])) {
            $errors[] = "Unknown param(s) specified: '$v'. Must be at least one of (" . join(', ',
                    $config['api']['params']) . ")";
            goto errors;
        }
    }
}
if (!empty($params)) {
    verbose("Param(s):", $params);
    $params = join(',', $params);
}

//-----------------------------------------------------------------------------
// get date from/to from command-line

$date_from = '';
if (!empty($options['date-from'])) {
    $date_from = $options['date-from'];
}
if (!empty($date_from)) {
    $date_from = strtotime($date_from);
    if (false === $date_from) {
        $errors[] = sprintf("Unable to parse --date-from: %s",
            $options['date-from']);
    }
    verbose(sprintf("Fetching results FROM date/time '%s': %s",
            $options['date-from'], gmdate('r', $date_from)));
}

$date_to = '';
if (!empty($options['date-to'])) {
    $date_to = $options['date-to'];
    $date_to = strtotime($date_to);
    if (false === $date_to) {
        $errors[] = sprintf("Unable to parse --date-to: %s", $options['date-to']);
    }
    verbose(sprintf("Fetching results TO date/time '%s': %s",
            $options['date-to'], gmdate('r', $date_to)));
}


//-----------------------------------------------------------------------------
// MAIN
// set up request params for sg_point_request($request_params)
$request_params = [
    'lat'    => $latitude,
    'lng'    => $longitude,
    'source' => $source,
    'params' => $params,
    'start'  => $date_from,
    'end'    => $date_to,
];
foreach ($request_params as $k => $v) {
    if (empty($v)) {
        unset($request_params[$k]);
    }
}
verbose('Request params:', $request_params);

// load from cache
$cache_key  = join('-', array_keys($request_params)) . '-' . join('_',
        $request_params);
$cache_dir  = realpath($dir);
$cache_file = $cache_dir . '/' . $cache_key . '.json';

// load from cache, expire if out-of-date in order to refresh after
if (!$do['refresh'] && file_exists($cache_file)) {
    $expired = time() > ($config['settings']['cache']['seconds'] + filemtime($cache_file));
    if ($expired) {
        unlink($cache_file);
    } else {
        $data = json_load($cache_file);
    }
}

// not in cache!
if (!empty($data)) {
    debug("Cached data loaded from: $cache_file");
} else {
    debug("Cached file data not found for: $cache_file");
    $data = sg_point_request($request_params);
    if (empty($data) || !is_array($data) || array_key_exists('errors', $data)) {
        if (array_key_exists('errors', $data)) {
            foreach ($data['errors'] as $param => $error) {
                $errors[] = sprintf("%s: %s", $param, $error);
            }
        }
        goto errors;
    }

    // add city to meta-data
    if (!empty($data) && !empty($city) && array_key_exists('meta', $data)) {
        $data['meta']['city'] = $city;
    }

    // cache the result
    $save = json_save($cache_file, $data);
    if (true !== $save) {
        $errors[] = "\nFailed encoding JSON cached results output file:\n\t$cache_file\n";
        $errors[] = "\nJSON Error: $save\n";
        goto errors;
    } else {
        verbose(sprintf("JSON written to cached results output file:\n\t%s (%d bytes)\n",
                $cache_file, filesize($cache_file)));
    }
}

// average-out results
if ($do['average'] && !empty($data)) {

    // load from cache
    $cache_file = $cache_dir . '/avg-' . $cache_key . '.json';

    // load from cache, expire if out-of-date in order to refresh after
    if (!$do['refresh'] && file_exists($cache_file)) {
        $expired = time() > ($config['settings']['cache']['seconds'] + filemtime($cache_file));
        if ($expired) {
            unlink($cache_file);
        } else {
            $averaged_data = json_load($cache_file);
        }
    }

    // not in cache!
    if (!empty($averaged_data)) {
        debug("Cached averaged data loaded from: $cache_file");
        $data = $averaged_data;
    } else {
        debug("Cached file with averaged data not found for: $cache_file");

        foreach ($data['hours'] as $i => $dataset) {
            foreach ($dataset as $field => $source) {
                if ('time' === $field && is_string($source)) {
                    $time = strtotime($source);
                    $dataset['unixtime'] = $time;
                } else if (is_array($source)) {
                    $value = 0;
                    $nvalues = 0;
                    foreach ($source as $values) {
                        if (array_key_exists('value', $values)) {
                            $v = $values['value'];
                            if (is_numeric($v)) {
                                $float = (string)(float) $v;
                                if (is_int($v)) {
                                    $v = (int) $v;
                                } else if (is_float($v) || $v === $float) {
                                    $v = (float) $v;
                                } else {
                                    $v = (int) $v;
                                }
                            }
                            if (0 !== $v) {
                                $nvalues++;
                                $value += $v;
                            }
                        }
                    }
                    $average = (0 === $value) ? 0 : round($value / $nvalues, 3);
                    $source['average'] = $average;
                    $dataset[$field] = $average;
                }
            }
            // remove numerical incremental index, make index the unixtime
            unset($data['hours'][$i]);
            $data['hours'][$dataset['unixtime']] = $dataset;
        }

        // sort results
        ksort($data['hours']);

        // cache the result
        $save = json_save($cache_file, $data);
        if (true !== $save) {
            $errors[] = "\nFailed encoding JSON cached average results output file:\n\t$cache_file\n";
            $errors[] = "\nJSON Error: $save\n";
            goto errors;
        } else {
            verbose(sprintf("JSON written to cached average results output file:\n\t%s (%d bytes)\n",
                    $cache_file, filesize($cache_file)));
        }
    }
}

//-----------------------------------------------------------------------------
// final output of data

output:

// display any errors
if (!empty($errors)) {
    goto errors;
}

// set data to write to file
if (is_array($data) && !empty($data)) {
    $output = $data;
}

// only write/display output if we have some!
if (!empty($output)) {

    if ($save_data && !empty($output_filename)) {
        $file = $output_filename;
        switch (OUTPUT_FORMAT) {
            default:
            case 'json':
                $save = json_save($file, $output);
                if (true !== $save) {
                    $errors[] = "\nFailed encoding JSON output file:\n\t$file\n";
                    $errors[] = "\nJSON Error: $save\n";
                    goto errors;
                } else {
                    verbose(sprintf("JSON written to output file:\n\t%s (%d bytes)\n",
                            $file, filesize($file)));
                }
                break;
        }

    }

    // output data if --echo
    if ($do['echo']) {
        echo json_encode($output, JSON_PRETTY_PRINT);
    }
}

end:

debug(sprintf("Memory used (%s) MB (current/peak).", get_memory_used()));
output("\n");
exit;

//-----------------------------------------------------------------------------
// functions used above

/**
 * Output string, to STDERR if available
 *
 * @param  string { string to output
 * @param  boolean $STDERR write to stderr if it is available
 */
function output($text, $STDERR = true)
{
    if (!empty($STDERR) && defined('STDERR')) {
        fwrite(STDERR, $text);
    } else {
        echo $text;
    }
}


/**
 * Dump debug data if DEBUG constant is set
 *
 * @param  optional string $string string to output
 * @param  optional mixed $data to dump
 * @return boolean true if string output, false if not
 */
function debug($string = '', $data = [])
{
    if (DEBUG) {
        output(trim('[D ' . get_memory_used() . '] ' . $string) . "\n");
        if (!empty($data)) {
            output(print_r($data, 1));
        }
        return true;
    }
    return false;
}


/**
 * Output string if VERBOSE constant is set
 *
 * @param  string $string string to output
 * @param  optional mixed $data to dump
 * @return boolean true if string output, false if not
 */
function verbose($string, $data = [])
{
    if (VERBOSE && !empty($string)) {
        output(trim('[V' . ((DEBUG) ? ' ' . get_memory_used() : '') . '] ' . $string) . "\n");
        if (!empty($data)) {
            output(print_r($data, 1));
        }
        return true;
    }
    return false;
}


/**
 * Return the memory used by the script, (current/peak)
 *
 * @return string memory used
 */
function get_memory_used()
{
    return(
        ceil(memory_get_usage() / 1024 / 1024) . '/' .
        ceil(memory_get_peak_usage() / 1024 / 1024));
}


/**
 * check required commands installed and get path
 *
 * @param  array $requirements [][command -> description]
 * @return mixed array [command -> path] or string errors
 */
function get_commands($requirements = [])
{
    static $commands = []; // cli command paths

    $found = true;
    foreach ($requirements as $tool => $description) {
        if (!array_key_exists($tool, $commands)) {
            $found = false;
            break;
        }
    }
    if ($found) {
        return $commands;
    }

    $errors = [];
    foreach ($requirements as $tool => $description) {
        $cmd = cmd_execute("which $tool");
        if (empty($cmd)) {
            $errors[] = "Error: Missing requirement: $tool - " . $description;
        } else {
            $commands[$tool] = $cmd[0];
        }
    }

    if (!empty($errors)) {
        output(join("\n", $errors) . "\n");
    }

    return $commands;
}


/**
 * Execute a command and return streams as an array of
 * stdin, stdout, stderr
 *
 * @param  string $cmd command to execute
 * @return array|false array $streams | boolean false if failure
 * @see    https://secure.php.net/manual/en/function.proc-open.php
 */
function shell_execute($cmd)
{
    $process = proc_open(
        $cmd,
        [
        ['pipe', 'r'],
        ['pipe', 'w'],
        ['pipe', 'w']
        ], $pipes
    );
    if (is_resource($process)) {
        $streams = [];
        foreach ($pipes as $p => $v) {
            $streams[] = stream_get_contents($pipes[$p]);
        }
        proc_close($process);
        return [
            'stdin'  => $streams[0],
            'stdout' => $streams[1],
            'stderr' => $streams[2]
        ];
    }
    return false;
}


/**
 * Execute a command and return output of stdout or throw exception of stderr
 *
 * @param  string $cmd command to execute
 * @param  boolean $split split returned results? default on newline
 * @param  string $exp regular expression to preg_split to split on
 * @return mixed string $stdout | Exception if failure
 * @see    shell_execute($cmd)
 */
function cmd_execute($cmd, $split = true, $exp = "/\n/")
{
    $result = shell_execute($cmd);
    if (!empty($result['stderr'])) {
        throw new Exception($result['stderr']);
    }
    $data = $result['stdout'];
    if (empty($split) || empty($exp) || empty($data)) {
        return $data;
    }
    return preg_split($exp, $data);
}


/**
 * Encode array character encoding recursively
 *
 * @param mixed $data
 * @param string $to_charset convert to encoding
 * @param string $from_charset convert from encoding
 * @return mixed
 */
function to_charset($data, $to_charset = 'UTF-8', $from_charset = 'auto')
{
    if (is_numeric($data)) {
        $float = (string)(float) $data;
        if (is_int($data)) {
            return (int) $data;
        } else if (is_float($data) || $data === $float) {
            return (float) $data;
        } else {
            return (int) $data;
        }
    } else if (is_string($data)) {
        return mb_convert_encoding($data, $to_charset, $from_charset);
    } else if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = to_charset($value, $to_charset, $from_charset);
        }
    } else if (is_object($data)) {
        foreach ($data as $key => $value) {
            $data->$key = to_charset($value, $to_charset, $from_charset);
        }
    }
    return $data;
}


/**
 * Load a json file and return a php array of the content
 *
 * @param  string $file the json filename
 * @return string|array error string or data array
 */
function json_load($file)
{
    $data = [];
    if (file_exists($file)) {
        $data = to_charset(file_get_contents($file));
        $data = json_decode(
            mb_convert_encoding($data, 'UTF-8', "auto"), true, 512,
            JSON_OBJECT_AS_ARRAY || JSON_BIGINT_AS_STRING
        );
    }
    if (null === $data) {
        return json_last_error_msg();
    }
    if (is_array($data)) {
        $data = to_charset($data);
    }
    return $data;
}


/**
 * Save data array to a json
 *
 * @param  string $file the json filename
 * @param  array $data data to save
 * @param  string optional $prepend string to prepend in the file
 * @param  string optional $append string to append to the file
 * @return boolean true|string TRUE if success or string error message
 */
function json_save($file, $data, $prepend = '', $append = '')
{
    if (empty($data)) {
        return 'No data to write to file.';
    }
    if (is_array($data)) {
        $data = to_charset($data);
    }
    if (!file_put_contents($file,
            $prepend . json_encode($data, JSON_PRETTY_PRINT) . $append)) {
        $error = json_last_error_msg();
        if (empty($error)) {
            $error = sprintf("Unknown Error writing file: '%s' (Prepend: '%s', Append: '%s')",
                $file, $prepend, $append);
        }
        return $error;
    }
    return true;
}


/**
 * Send a "point request" to the stormglass API and return the result as a PHP array
 *
 * @param array $request_params
 * @param array $options to merge in for curl (timeout (int), max_time (int), user_agent (string))
 * @return boolean|string|array of results. false or string if error
 * @see https://docs.stormglass.io/?shell#point-request
 */
function sg_point_request($request_params, $options = [])
{
    $url = SG_API_URL_POINT . '?' . http_build_query($request_params);

    $commands = get_commands();
    $curl     = $commands['curl'];

    $timeout    = !empty($options['timeout']) ? (int) $options['timeout'] : 3;
    $max_time   = !empty($options['max_time']) ? (int) $options['max_time'] : $timeout
        * 10;
    $user_agent = !empty($options['user_agent']) ? $options['user_agent'] : '';

    $curl_options_auth = '-H "Authorization: ' . SG_API_KEY . '"';
    $curl_options      = "--connect-timeout $timeout --max-time $max_time --ciphers ALL -k";
    $curl_url_resolve  = "curl $curl_options_auth $curl_options -L -s " . escapeshellarg($url);

    if (OFFLINE) {
        debug(sprintf("OFFLINE MODE! Can't request:\n\t%s\n\t", $curl_url_resolve));
        return false;
    }

    // execute request
    $data = cmd_execute($curl_url_resolve, false);

    // decode json data to php
    if (!empty($data)) {
        $return = to_charset($data);
        $return = json_decode(
            mb_convert_encoding($return, 'UTF-8', "auto"), true, 512,
            JSON_OBJECT_AS_ARRAY || JSON_BIGINT_AS_STRING
        );
    }

    if (empty($data) || empty($return)) {
        $return = sprintf("JSON decode failed: %s\nData:\n\t",
                json_last_error_msg()) . print_r($data, 1);
    }

    // extract errors as messages of param field name => error text array
    $errors = [];
    if (array_key_exists('errors', $return)) {
        foreach ($return['errors'] as $param => $errs) {
            foreach ($errs as $i => $e) {
                $errors[$param] = $e;
            }
        }
        debug('Full error response from stormglass:',  print_r($data,1));
    }

    if (count($errors)) {
        $return['errors'] = $errors;
    }

    // sort meta-data to aid redability
    ksort($return['meta']);
    sort($return['meta']['params']);

    return $return;
}

/**
 * Read in geonames cities TSV file
 *
 * @param optional int $id id of city to find
 * @return array of cities or city
 * @see http://download.geonames.org/export/dump/
 * @url http://www.geonames.org/export/
 */
function getCities($id = 0) {
    $fh = fopen(FILE_TSV_CITIES, 'r');
    $cities = [];
    if (!empty($id)) {
        $id = (int) $id;
        if ($id < 1) {
            return [];
        }
    }
    while ($data = fgetcsv($fh, 0, "\t")) {
        $data = to_charset($data);
        $geoname_id = (int) $data[0];
        $city = [
            'id' => $data[0],
            'country_code' => $data[8],
            'state' => $data[10],
            'city' => $data[1],
            'ascii' => $data[2],
            'names' => preg_split('/,/', $data[3]),
            'latitude' => (float) $data[4],
            'longitude' => (float) $data[5],
            'elevation' => (int) $data[16],
            'population' => empty($data[15]) ? null : (int) $data[15],
            'timezone' => $data[17],
        ];
        if (empty($id)) {
            $cities[$geoname_id] = $city;
        } else if ($id === $geoname_id) {
            $cities = [$geoname_id => $city];
            break;
        }
    }
    fclose($fh);
    return $cities;
}
