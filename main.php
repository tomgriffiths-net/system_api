<?php
class system_api{
    public static function getProcessCpuUsage($processId):float{
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
    public static function getProcessChildProcesses($parentPid){
        $return = array();
        $childProcesses = explode("\n",shell_exec('wmic process where "parentprocessid=' . $parentPid . '" get caption,processid'));
        array_shift($childProcesses);
        foreach($childProcesses as $childProcess){
            $childProcess = trim($childProcess);
            $pid = trim(substr($childProcess,strpos($childProcess," ")+1));
            if(is_numeric($pid)){
                $return[substr($childProcess,0,strpos($childProcess," "))] = $pid;
            }
        }
        return $return;
    }
    public static function getProcessMemoryUsage($pid):int{
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
}