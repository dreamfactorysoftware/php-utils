<?php namespace DreamFactory\Library\Utility;

use DreamFactory\Library\Utility\Enums\DateTimeIntervals;

/**
 * Profiler includes
 */
if (!function_exists('xhprof_error')) {
    /** @noinspection PhpIncludeInspection */
    require_once 'xhprof_lib/utils/xhprof_lib.php';
    /** @noinspection PhpIncludeInspection */
    require_once 'xhprof_lib/utils/xhprof_runs.php';
}

/**
 * A simple profiling class that is xhprof-aware
 */
class Profiler
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type int
     */
    const XHPROF_NO_BUILTINS = 1;
    /**
     * @type int
     */
    const XHPROF_FLAGS_CPU = 2;
    /**
     * @type int
     */
    const XHPROF_FLAGS_MEMORY = 4;

    //*************************************************************************
    //	Members
    //*************************************************************************

    /**
     * @var array The runs I'm tracking
     */
    protected static $_profiles = [];
    /**
     * @type bool True if xhprof is available
     */
    protected static $_xhprof = false;

    //*************************************************************************
    //	Methods
    //*************************************************************************

    /**
     * @param string $id The id of this profile
     *
     * @return float
     */
    public static function start($id)
    {
        static::$_profiles[$id] = ['start' => microtime(true)];

        if (function_exists('xhprof_enable')) {
            static::$_xhprof = true;
            /** @noinspection PhpUndefinedConstantInspection */
            xhprof_enable(static::XHPROF_FLAGS_CPU + static::XHPROF_FLAGS_MEMORY);
        }

        return static::$_profiles[$id];
    }

    /**
     * Stops the timer. Returns elapsed as ms or pretty string
     *
     * @param string $id
     * @param bool   $prettyPrint If true, elapsed time will be returned in a state suitable for display
     *
     * @return float|string The elapsed time in ms
     */
    public static function stop($id, $prettyPrint = true)
    {
        if (!isset(static::$_profiles[$id]['start'])) {
            return 'not profiled';
        }

        static::$_profiles[$id]['stop'] = microtime(true);
        static::$_profiles[$id]['elapsed'] = (static::$_profiles[$id]['stop'] - static::$_profiles[$id]['start']);

        if (static::$_xhprof) {
            /** @noinspection PhpUndefinedFunctionInspection */
            /** @noinspection PhpUndefinedMethodInspection */
            /** @noinspection PhpUndefinedClassInspection */
            static::$_profiles[$id]['xhprof'] = [
                'data'     => $_data = xhprof_disable(),
                'run_name' => $_runName = $id . microtime(true),
                'runs'     => $_runs = new \XHProfRuns_Default(),
                'run_id'   => $_runId = $_runs->save_run($_data, $_runName),
                'url'      => 'http://xhprof.local/index.php?run=' . $_runId . '&source=' . $_runName,
            ];

            \Log::debug('~!~ profiler link: ' . static::$_profiles[$id]['xhprof']['url']);
        }

        return $prettyPrint ? static::elapsedAsString(static::$_profiles[$id]['elapsed']) : static::$_profiles[$id]['elapsed'];
    }

    /**
     * Full cycle profiler using a callable
     *
     * @param string   $id        The name of this profile
     * @param callable $callable  The code to profile
     * @param array    $arguments The arguments to send to the profile target
     * @param int      $count     The number of times to run $callable. Defaults to
     *
     * @return float
     */
    public static function profile($id, $callable, array $arguments = [], $count = 1)
    {
        $_runCount = 0;
        $_runs = [];

        $_runTemplate = function ($time) {
            return [
                'start'   => $time,
                'end'     => 0,
                'elapsed' => 0,
                'xhprof'  => null,
            ];
        };

        while ($count >= $_runCount--) {
            $_run = $_runTemplate($_time = microtime(true));

            if (static::$_xhprof) {
                /** @noinspection PhpUndefinedFunctionInspection */
                xhprof_enable();
            }

            call_user_func_array($callable, $arguments);

            if (static::$_xhprof) {
                /** @noinspection PhpUndefinedFunctionInspection */
                $_run['xhprof'] = xhprof_disable();
            }

            $_run['elapsed'] = (($_run['end'] = microtime(true)) - $_time);
            $_runs[] = $_run;

            unset($_run);
        }

        //	Summarize the runs
        $_runs['summary'] = static::_summarizeRuns($_runs);
        static::$_profiles[$id] = $_runs;

        //  Return the average
        return $_runs['summary']['average'];
    }

    /**
     * @param float[] $runs An array of run times
     *
     * @return array
     */
    protected static function _summarizeRuns(array $runs = null)
    {
        $_count = count($runs);
        $_total = round(array_sum($runs), 4);

        $_summary = [
            'iterations' => $_count,
            'total'      => $_total,
            'best'       => round(min($runs), 4),
            'worst'      => round(max($runs), 4),
            'average'    => round($_total / $_count, 4),
        ];

        return $_summary;
    }

    /**
     * @param float      $start
     * @param float|bool $stop
     *
     * @return string
     */
    public static function elapsedAsString($start, $stop = false)
    {
        static $_divisors = [
            'h' => DateTimeIntervals::US_PER_HOUR,
            'm' => DateTimeIntervals::US_PER_MINUTE,
            's' => DateTimeIntervals::US_PER_SECOND,
        ];

        $_ms = round((false === $stop ? $start : ($stop - $start)) * 1000);

        foreach ($_divisors as $_label => $_divisor) {
            if ($_ms >= $_divisor) {
                $_time = floor($_ms / $_divisor * 100.0) / 100.0;

                return $_time . $_label;
            }
        }

        return $_ms . 'ms';
    }

    /**
     * @param string $id The id of the profile run to retrieve
     *
     * @return array
     */
    public static function getRun($id)
    {
        return isset(static::$_profiles[$id]) ? static::$_profiles[$id] : false;
    }

    /**
     * Returns all run profiles for a single ID or all
     *
     * @param string $id A profile ID
     *
     * @return array
     */
    public static function all($id = null)
    {
        return static::getRun($id) ?: static::$_profiles;
    }
}
