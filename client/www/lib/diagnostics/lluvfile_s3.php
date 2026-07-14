<?php
// lluvfile.php
// reads from either a local path *or* any HTTP(S) URL

class NotFoundException extends Exception {}

class lluvFile {
    private $file;     // local path or URL
    private $lines;    // cached array of lines

    public function __construct(string $file) {
        $this->file = $file;
        // attempt to fetch
        $this->lines = @file($file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        if (! is_array($this->lines) || count($this->lines) === 0) {
            throw new NotFoundException("Cannot open $file");
        }
    }

    public function getPatternType(): string {
        foreach ($this->lines as $ln) {
            if (strpos($ln, 'PatternType') !== false) {
                $parts = preg_split('/\s+/', trim($ln));
                $val   = end($parts);
                return $val==="Measured" ? "m" : "i";
            }
        }
        return "i";
    }

    private function getTableData(string $startMarker, string $endMarker, $starttime=null, $endtime=null): array {
        $inSection = false;
        $headers   = [];
        $rows      = [];

        foreach ($this->lines as $ln) {
            if (! $inSection) {
                if (strpos($ln, $startMarker) !== false) {
                    $inSection = true;
                }
                continue;
            }
            if (strpos($ln, $endMarker) !== false) {
                break;
            }
            // look for header line
            if (strpos($ln, 'TableColumnTypes') !== false) {
                $parts   = explode(':', $ln, 2);
                $headers = preg_split('/\s+/', trim($parts[1]));
                continue;
            }
            if (empty($headers)) {
                continue;
            }
            $trimmed = trim($ln, "% \t\n\r");
            $cols    = preg_split('/\s+/', $trimmed);
            if (count($cols) !== count($headers) || ! is_numeric($cols[0])) {
                continue;
            }
            $data = array_combine($headers, $cols);

            // if there's a TIME column (“THRS”), enforce $starttime/$endtime
            if (isset($data['THRS'])) {
                $ts = gmmktime(
                   intval($data['THRS']),
                   intval($data['TMIN']),
                   intval($data['TSEC']),
                   intval($data['TMON']),
                   intval($data['TDAY']),
                   intval($data['TYRS'])
                );
                if (($starttime!==null && $ts < $starttime)
                 || ($endtime  !==null && $ts > $endtime)) {
                    continue;
                }
            }
            $rows[] = $data;
        }
        return $rows;
    }

    public function getLLUVData(): array {
        return $this->getTableData("%TableType: LLUV RDL", "%TableEnd:");
    }

    public function getRadialDiagnostics($s=null,$e=null){ 
      return $this->getTableData("%TableType: rads rad1", "%TableEnd: 2", $s, $e);
    }
    public function getHardwareDiagnostics($s=null,$e=null){
      return $this->getTableData("%TableType: rcvr rcv2", "%TableEnd: 3", $s, $e);
    }
    // getProcessInfo, getRadialMeta, etc. would be refactored similarly—
    // read $this->lines, look for markers, split & map columns, no exec/sed/grep.
}
