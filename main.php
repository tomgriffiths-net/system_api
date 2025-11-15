<?php
class system_api{
    public static function getProcessCpuUsage(int $processId):float{
        $float = floatval(shell_exec('packages\\system_api\\files\\cpuUsage.exe ' . $processId));
        if($float > 100){
            $float = 100;
        }
        if($float < 0){
            $float = 0;
        }
        return $float;
    }
    public static function getOpenWindows():array{
        $return = array();
        $windows = array();
        $processes = shell_exec('powershell -command "Get-Process | Select-Object Id, MainWindowTitle"');
        $processes = explode("\n",$processes);
        $windowTitlePos = strpos($processes[2],"-")+2;
        array_shift($processes);
        array_shift($processes);
        array_shift($processes);
        foreach($processes as $processInfo){
            $substring = substr($processInfo,$windowTitlePos+1,1);
            if($substring !== " " && $substring !== ""){
                $windows[] =  trim($processInfo);
            }
        }

        foreach($windows as $window){
            $spacePos = strpos($window," ");
            $windowPid = substr($window,0,$spacePos);
            $windowName = substr($window,$spacePos+1);
            $return[$windowName] = $windowPid;
        }

        return $return;
    }
    public static function getProcessChildProcesses(int $parentPid):array{
        $return = [];
        $childProcesses = explode("\n",shell_exec('powershell -command "Get-CimInstance Win32_Process -Filter "ParentProcessId=' . $parentPid . '" | Select-Object ProcessId, Name"'));
        array_shift($childProcesses);
        array_shift($childProcesses);
        array_shift($childProcesses);
        foreach($childProcesses as $childProcess){
            $pid = trim(substr($childProcess, 0, 9));
            $process = trim(substr($childProcess, 9));

            if(is_numeric($pid)){
                $return[$process] = intval($pid);
            }
        }
        return $return;
    }
    public static function getProcessMemoryUsage(int $pid):int{
        if(is_numeric($pid)){
            $lines = explode("\n",shell_exec('tasklist /fi "pid eq ' . $pid . '"'));
            if(isset($lines[3])){
                $processInfo = trim($lines[3]);
                $memend = strripos($processInfo," ");
                $memstart = strripos(substr($processInfo,0,$memend-1)," ");
                $memstr = substr($processInfo,$memstart+1,$memend-$memstart);
                return round(str_replace(",","",$memstr)/1024);
            }
        }
        return 0;
    }
    public static function isVisualRedistributableInstalled(string $version="14.0"):bool{
        if(preg_match("/^[0-9.]+$/", $version)){

            exec('reg query "HKLM\SOFTWARE\Microsoft\VisualStudio\\' . $version . '\VC\Runtimes\x64" /v Installed', $output, $ret);

            if($ret === 0){
                foreach($output as $line){
                    if(preg_match('/Installed\s+REG_DWORD\s+0x1/i', $line)){
                        return true;
                    }
                }
            }
        }

        return false;
    }
}