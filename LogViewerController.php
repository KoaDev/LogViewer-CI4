<?php namespace App\Controllers;

class LogViewerController extends BaseController
{
    private static $levelClasses = [
        'CRITICAL' => 'danger',
        'EMERGENCY' => 'danger',
        'ERROR' => 'danger',
        'INFO' => 'success',
        'DEBUG' => 'info',
        'NOTICE' => 'success',
        'WARNING' => 'warning',

    ];

    const LOG_LEVEL_PATTERN = "/^((ERROR)|(INFO)|(ALERT)|(EMERGENCY)|(NOTICE)|(WARNING)|(DEBUG)|(CRITICAL))/";
    const LOG_LINE_START_PATTERN = "/((ERROR)|(INFO)|(ALERT)|(EMERGENCY)|(NOTICE)|(WARNING)|(DEBUG)|(CRITICAL))[\s\-\d:\.\/]+(-->)/";
    const LOG_DATE_PATTERN = ["/^((ERROR)|(INFO)|(ALERT)|(EMERGENCY)|(NOTICE)|(WARNING)|(DEBUG)|(CRITICAL))\s\-\s/", "/\s(-->)/"];

    const MAX_LOG_SIZE = 52428800;
    const MAX_STRING_LENGTH = 300;

    private function processLogs($logs)
    {

        if (is_null($logs)) {
            return null;
        }

        $superLog = [];

        foreach ($logs as $log) {

            $logLineStart = $this->getLogLineStart($log);

            if (!empty($logLineStart)) {
                $level = $this->getLogLevel($logLineStart);
                $data = [
                    "level" => $level,
                    "date" => $this->getLogDate($logLineStart),
                    "class" => self::$levelClasses[$level],
                ];

                $logMessage = preg_replace(self::LOG_LINE_START_PATTERN, '', $log);

                if (strlen($logMessage) > self::MAX_STRING_LENGTH) {
                    $data['content'] = substr($logMessage, 0, self::MAX_STRING_LENGTH);
                    $data["extra"] = substr($logMessage, (self::MAX_STRING_LENGTH + 1));
                } else {
                    $data["content"] = $logMessage;
                }

                array_push($superLog, $data);

            } else if (!empty($superLog)) {
                $prevLog = $superLog[count($superLog) - 1];
                $extra = (array_key_exists("extra", $prevLog)) ? $prevLog["extra"] : "";
                $prevLog["extra"] = $extra . "<br>" . $log;
                $superLog[count($superLog) - 1] = $prevLog;
            } else {

//               array_push($superLog, [
//                   "level" => "INFO",
//                   "date" => "",
//                   "icon" => self::$levelsIcon["INFO"],
//                   "class" => self::$levelClasses["INFO"],
//                   "content" => $log
//               ]);
            }
        }
        return $superLog;
    }

    private function getLogLineStart($line)
    {
        preg_match(self::LOG_LINE_START_PATTERN, $line, $matches);
        if (!empty($matches)) {
            return $matches[0];
        }
        return "";
    }

    private function getLogLevel($logLineStart)
    {
        preg_match(self::LOG_LEVEL_PATTERN, $logLineStart, $matches);
        return $matches[0];
    }

    private function getLogDate($logLineStart)
    {
        return preg_replace(self::LOG_DATE_PATTERN, '', $logLineStart);
    }

    private function getLogs($fileName)
    {
        $size = filesize($fileName);
        if (!$size || $size > self::MAX_LOG_SIZE)
            return null;
        return file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    public function index()
    {
        $getLogs = $this->getLogs(WRITEPATH . '/logs/log-' . date('Y-m-d') . '.log');
        $data = $this->processLogs($getLogs);


        return view('LogViewerView', [
            'data' => $data,
        ]);
    }

}
